<?php

namespace Elegantly\Workflow;

use Elegantly\Workflow\Models\Workflow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

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

    public function shouldCancel(): bool
    {
        return false;
    }

    public function shouldRun(): bool
    {
        return true;
    }
}
