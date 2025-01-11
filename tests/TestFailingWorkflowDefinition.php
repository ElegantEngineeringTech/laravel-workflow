<?php

declare(strict_types=1);

namespace Elegantly\Workflow\Tests;

use Carbon\CarbonInterval;
use Elegantly\Workflow\Models\Workflow;
use Elegantly\Workflow\WorkflowDefinition;
use Elegantly\Workflow\WorkflowStep;
use Exception;
use Illuminate\Support\Collection;

class TestFailingWorkflowDefinition extends WorkflowDefinition
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
                'failing',
                WorkflowStep::make($workflow)
                    ->after('welcome')
                    ->action(function (): void {
                        throw new Exception('Failing step');
                    })
            )
            ->put(
                '10min-after-welcome',
                WorkflowStep::make($workflow)
                    ->after([
                        'welcome' => CarbonInterval::minutes(10),
                    ])
                    ->action(function (): void {
                        //
                    })
            );
    }
}
