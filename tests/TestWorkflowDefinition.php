<?php

namespace Elegantly\Workflow\Tests;

use Carbon\CarbonInterval;
use Elegantly\Workflow\Models\Workflow;
use Elegantly\Workflow\WorkflowDefinition;
use Elegantly\Workflow\WorkflowStep;
use Illuminate\Support\Collection;

class TestWorkflowDefinition extends WorkflowDefinition
{
    public function __construct(
        public string $user
    ) {
        //
    }

    public function steps(Workflow $workflow): Collection
    {
        return collect()
            ->put(
                'welcome',
                WorkflowStep::make($workflow)
                    ->action(function (): void {
                        //
                    })
            )
            ->put(
                'welcome-bis',
                WorkflowStep::make($workflow)->action(new TestJob)
            )
            ->put(
                'after-welcome',
                WorkflowStep::make($workflow)
                    ->after('welcome')
                    ->action(function (): void {
                        //
                    })
            )
            ->put(
                '10min-after-welcome',
                WorkflowStep::make($workflow)
                    ->after([
                        'welcome' => CarbonInterval::minutes(10),
                    ])
                    ->after('welcome-bis')
                    ->action(function (): void {
                        //
                    })
            );
    }
}
