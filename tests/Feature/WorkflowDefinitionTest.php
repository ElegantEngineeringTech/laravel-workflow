<?php

use Elegantly\Workflow\Tests\TestWorkflowDefinition;

it('can run a workflow', function () {
    $definition = new TestWorkflowDefinition(
        user: 'foo'
    );

    $workflow = $definition->start();

    expect($workflow->exists)->toBe(true);
    expect($workflow->items)->toHaveLength(2);

    $workflow->refresh();

    expect($workflow->step)->toBe('welcome-bis');

    foreach ($workflow->items as $item) {
        expect($item->dispatched_at)->not->toBe(null);
        expect($item->failed_at)->toBe(null);
        expect($item->finished_at)->not->toBe(null);
    }

    $workflow->run();
    expect($workflow->items)->toHaveLength(3);

    $workflow->refresh();
    expect($workflow->step)->toBe('after-welcome');

    $workflow->run();
    expect($workflow->items)->toHaveLength(3);

    $workflow->refresh();
    expect($workflow->step)->toBe('after-welcome');

    $this->travel(11)->minutes();

    $workflow->run();
    expect($workflow->items)->toHaveLength(4);

    $workflow->refresh();
    expect($workflow->step)->toBe('10min-after-welcome');
});
