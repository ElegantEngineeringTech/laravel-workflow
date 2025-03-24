# Scheduler based Workflow for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-workflow.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-workflow)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-workflow/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-workflow/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-workflow/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-workflow/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-workflow.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-workflow)

## Installation

You can install the package via composer:

```bash
composer require elegantly/laravel-workflow
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="workflow-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="workflow-config"
```

This is the contents of the published config file:

```php
return [

    'queue' => env('WORKFLOW_QUEUE'),

    'queue_connection' => env('WORKFLOW_QUEUE_CONNECTION'),

];
```

## Usage

### Defining your workflows

Define a workflow in a class like this one:

```php
namespace App\Workflows;

use Carbon\CarbonInterval;
use Elegantly\Workflow\Models\Workflow;
use Elegantly\Workflow\WorkflowDefinition;
use Elegantly\Workflow\WorkflowStep;
use Illuminate\Support\Collection;

class WelcomeUserWorkflow extends WorkflowDefinition
{
    public function __construct(
        public User $user
    ) {
        //
    }

    public function steps(Workflow $workflow): Collection
    {
        return collect()
            ->put(
                'welcome-email',
                WorkflowStep::make($workflow)
                    ->action(function (): void {
                        // send an email to the user
                    })
            )
            ->put(
                'export-user',
                WorkflowStep::make($workflow)
                    ->action(new ExportUserToCrmJob($this->user))
            )
            ->put(
                'product-tour-email',
                WorkflowStep::make($workflow)
                    ->after([
                        'welcome-email' => CarbonInterval::days(3)
                    ])
                    ->action(function (): void {
                        // Send another email to your user
                    })
            )
            ->put(
                'send-promo-code',
                WorkflowStep::make($workflow)
                    ->after([
                        'product-tour-email' => CarbonInterval::days(7),
                    ])
                    ->when(fn() => $this->user->hasNotPurchased())
                    ->action(function (): void {
                        //
                    })
            );
    }
}
```

### Running your workflow

```php
use Elegantly\Workflow\Commands\RunWorkflowsCommand;

$schedule->command(RunWorkflowsCommand::class)->everyMinutes();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Quentin Gabriele](https://github.com/QuentinGab)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
