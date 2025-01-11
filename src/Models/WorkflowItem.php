<?php

declare(strict_types=1);

namespace Elegantly\Workflow\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int $workflow_id
 * @property ?Carbon $finished_at
 * @property ?Carbon $canceled_at
 * @property ?Carbon $failed_at
 * @property ?Carbon $dispatched_at
 * @property ?array<array-key, mixed> $metadata
 * @property Carbon $updated_at
 * @property Carbon $created_at
 */
class WorkflowItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, mixed>
     */
    public function casts(): array
    {
        return [
            'finished_at' => 'datetime',
            'canceled_at' => 'datetime',
            'failed_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function isFailed(): bool
    {
        return (bool) $this->failed_at;
    }

    public function isCanceled(): bool
    {
        return (bool) $this->canceled_at;
    }

    public function isFinished(): bool
    {
        return (bool) $this->finished_at;
    }

    public function isDispatched(): bool
    {
        return (bool) $this->dispatched_at;
    }

    public function isPending(): bool
    {
        return $this->isDispatched() &&
            ! $this->isFailed() &&
            ! $this->isCanceled() &&
            ! $this->isFinished();
    }

    public function markAsFailed(): static
    {
        $this->failed_at = now();
        $this->save();

        return $this;
    }

    public function markAsFinished(): static
    {
        $this->finished_at = now();
        $this->save();

        return $this;
    }

    public function markAsCanceled(): static
    {
        $this->canceled_at = now();
        $this->save();

        return $this;
    }
}
