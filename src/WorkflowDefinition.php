<?php

namespace Elegantly\Workflow;

use Elegantly\Workflow\Models\Workflow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Throwable;

abstract class WorkflowDefinition
{
    use SerializesModels;

    /**
     * @return Collection<string, WorkflowStep>
     */
    abstract public function steps(Workflow $workflow): Collection;

    public function start(): Workflow
    {
        $workflow = new Workflow;

        $workflow->definition = $this;

        $workflow->save();

        $workflow->run();

        return $workflow;
    }

    public function shouldCancel(Workflow $workflow): bool
    {
        return false;
    }

    public function shouldRun(Workflow $workflow): bool
    {
        return true;
    }

    public function beforeRun(Workflow $workflow): void
    {
        //
    }

    public function run(Workflow $workflow): void
    {
        $readySteps = $this
            ->steps($workflow)
            ->filter(function ($step, $name) use ($workflow) {
                return ! $workflow->isStepPending($name) &&
                    ! $workflow->isStepFinished($name) &&
                    ! $workflow->isStepCanceled($name) &&
                    ! $workflow->isStepFailed($name) &&
                    $step->isReady();
            });

        if ($readySteps->isEmpty()) {
            return;
        }

        $items = $workflow->items()->createMany(
            $readySteps->map(fn ($step, $name) => [
                'name' => $name,
                'dispatched_at' => now(),
            ])
        );

        $workflow->items->push(...$items);

        /**
         * @var array<int, string> $stepNames
         */
        $stepNames = $readySteps->keys()->toArray();
        $workflowId = $workflow->id;

        /** @var ?string $connection */
        $connection = config('workflow.queue_connection');
        /** @var ?string $queue */
        $queue = config('workflow.queue');

        Bus::chain([
            ...$readySteps->flatMap(function ($step, $name) use ($workflowId) {
                return [
                    $step->action,
                    function () use ($workflowId, $name) {
                        $workflow = Workflow::query()->findOrFail($workflowId);
                        $workflow->markStepAsFinished($name);
                    },
                ];
            }),
        ])
            ->catch(function (Throwable $e) use ($workflowId, $stepNames) {
                $workflow = Workflow::query()->findOrFail($workflowId);

                $item = $workflow
                    ->items
                    ->whereIn('name', $stepNames)
                    ->firstWhere('finished_at', null);

                if ($item) {
                    $workflow->markStepAsFailed($item->name);
                } else {
                    $workflow->markAsFailed();
                }
            })
            ->onConnection($connection)
            ->onQueue($queue)
            ->dispatch();
    }

    public function afterRun(Workflow $workflow): void
    {
        //
    }
}
