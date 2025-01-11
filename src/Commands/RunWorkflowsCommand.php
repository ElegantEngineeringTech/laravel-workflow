<?php

declare(strict_types=1);

namespace Elegantly\Workflow\Commands;

use Elegantly\Workflow\Models\Workflow;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RunWorkflowsCommand extends Command
{
    public $signature = 'workflows:run';

    public $description = 'Run workflows';

    public function handle(): int
    {

        $query = Workflow::query()
            ->with(['items'])
            ->where('finished_at', '=', null)
            ->where('canceled_at', '=', null)
            ->where('failed_at', '=', null);

        $query->chunkById(1_000, function (Collection $workflows) {
            /**
             * @var Collection<int, Workflow> $workflows
             */
            foreach ($workflows as $workflow) {
                $workflow->run();
            }
        });

        return self::SUCCESS;
    }
}
