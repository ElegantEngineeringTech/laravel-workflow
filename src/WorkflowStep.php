<?php

namespace Elegantly\Workflow;

use Carbon\CarbonInterval;
use Closure;
use Elegantly\Workflow\Models\Workflow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;

class WorkflowStep
{
    /**
     * @param  null|ShouldQueue|(Closure():void)  $action
     * @param  null|bool|(Closure():bool)  $condition
     */
    final public function __construct(
        public Workflow $workflow,
        public null|ShouldQueue|Closure $action = null,
        public null|bool|Closure $condition = null,
    ) {
        //
    }

    public function isReady(): bool
    {
        if (is_null($this->condition)) {
            return true;
        }

        return (bool) value($this->condition);
    }

    public static function make(Workflow $workflow): static
    {
        return new static(
            workflow: $workflow,
        );
    }

    public function action(ShouldQueue|Closure $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param  bool|(Closure():bool)  $value
     */
    public function when(
        bool|Closure $value
    ): static {
        $this->condition = $value;

        return $this;
    }

    /**
     * @param  string|array<string|int, string|CarbonInterval>  $steps
     */
    public function after(
        string|array $steps,
    ): static {

        return $this->when(
            fn () => collect(Arr::wrap($steps))
                ->map(function ($delay, $step) {

                    if (is_string($delay)) {
                        return (bool) $this
                            ->workflow
                            ->getItem($delay)
                            ?->isFinished();
                    }

                    if (
                        is_string($step) &&
                        $delay instanceof CarbonInterval
                    ) {
                        return (bool) $this
                            ->workflow
                            ->getItem($step)
                            ?->finished_at
                            ?->add($delay)
                            ?->isPast();
                    }

                    return false;
                })
                ->every(fn ($value) => $value === true)
        );
    }
}
