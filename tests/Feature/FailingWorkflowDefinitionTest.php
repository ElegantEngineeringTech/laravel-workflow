<?php

declare(strict_types=1);

use Elegantly\Workflow\Tests\TestFailingWorkflowDefinition;

it('catches workflow failure', function () {
    $definition = new TestFailingWorkflowDefinition(
        user: 'foo'
    );

    $workflow = $definition->start();

    expect($workflow->exists)->toBe(true);
    expect($workflow->items)->toHaveLength(1);

    $workflow->refresh();
    expect($workflow->step)->toBe('welcome');

    try {
        $workflow->run();
    } catch (\Throwable $th) {
        // throw $th;
    }

    expect($workflow->items)->toHaveLength(2);

    $workflow->refresh();

    expect($workflow->step)->toBe('welcome');
    expect($workflow->failed_at)->not->toBe(null);
    expect($workflow->getItem('failing')?->failed_at)->not->toBe(null);
    expect($workflow->getItem('welcome')?->failed_at)->toBe(null);
});
