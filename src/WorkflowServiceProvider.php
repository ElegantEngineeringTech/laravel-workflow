<?php

declare(strict_types=1);

namespace Elegantly\Workflow;

use Elegantly\Workflow\Commands\RunWorkflowsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-workflow')
            ->hasConfigFile()
            ->hasCommand(RunWorkflowsCommand::class)
            ->hasMigration('create_workflows_table');
    }
}
