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
     * @param  array<int, bool|(Closure():bool)>  $conditions
     */
    final public function __construct(
        public Workflow $workflow,
        public null|ShouldQueue|Closure $action = null,
        public array $conditions = [],
    ) {
        //
    }

    public function isReady(): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (! value($condition)) {
                return false;
            }
        }

        return true;
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
        $this->conditions[] = $value;

        return $this;
    }

    /**
     * @param  string|array<string|int, string|CarbonInterval|bool|(Closure():bool)>  $steps
     */
    public function after(
        string|array $steps,
    ): static {

        foreach (Arr::wrap($steps) as $step => $delay) {
            if (
                is_int($step) &&
                is_bool($delay)
            ) {
                $this->when($delay);
            }

            if (
                is_int($step) &&
                $delay instanceof Closure
            ) {
                $this->when($delay);
            }

            if (
                is_int($step) &&
                is_string($delay)
            ) {
                $this->when(
                    fn () => (bool) $this
                        ->workflow
                        ->getItem($delay)
                        ?->isFinished()
                );
            }

            if (
                is_string($step) &&
                $delay instanceof CarbonInterval
            ) {
                $this->when(
                    fn () => (bool) $this
                        ->workflow
                        ->getItem($step)
                        ?->finished_at
                        ?->add($delay)
                        ?->isPast()
                );
            }
        }

        return $this;
    }
}
