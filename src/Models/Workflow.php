<?php

declare(strict_types=1);

namespace Elegantly\Workflow\Models;

use Carbon\Carbon;
use Elegantly\Workflow\WorkflowDefinition;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property int $id
 * @property string $type Definition class
 * @property ?string $step
 * @property WorkflowDefinition $definition Serialized Workflow definition
 * @property Collection<int, WorkflowItem> $items
 * @property ?int $model_id
 * @property ?class-string<Model> $model_type
 * @property ?Model $model
 * @property ?array<array-key, mixed> $metadata
 * @property ?Carbon $finished_at
 * @property ?Carbon $canceled_at
 * @property ?Carbon $failed_at
 * @property Carbon $updated_at
 * @property Carbon $created_at
 */
class Workflow extends Model
{
    protected $guarded = [];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        //
    ];

    /**
     * @return array<string, mixed>
     */
    public function casts()
    {
        return [
            'finished_at' => 'datetime',
            'canceled_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<WorkflowItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WorkflowItem::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return Attribute<WorkflowDefinition, WorkflowDefinition>
     */
    protected function definition(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value) {
                if (is_string($value)) {
                    return unserialize($value);
                }
                if ($value instanceof WorkflowDefinition) {
                    return $value;
                }

                return null;
            },
            set: function (WorkflowDefinition $value) {
                return [
                    'definition' => serialize($value),
                    'type' => $value::class,
                ];
            },
        );
    }

    public function pause(): static
    {
        return $this->markAsCanceled();
    }

    public function resume(): static
    {
        $this->canceled_at = null;
        $this->save();

        return $this;
    }

    public function isFinished(): bool
    {
        return (bool) $this->finished_at;
    }

    public function isFailed(): bool
    {
        return (bool) $this->failed_at;
    }

    public function isCanceled(): bool
    {
        return (bool) $this->canceled_at;
    }

    public function markAsFailed(): static
    {
        $this->failed_at = now();
        $this->save();

        return $this;
    }

    public function markAsFinished(?Carbon $date = null): static
    {
        $this->finished_at = $date ?? now();
        $this->save();

        return $this;
    }

    public function markAsCanceled(): static
    {
        $this->canceled_at = now();
        $this->save();

        return $this;
    }

    /**
     * @return SupportCollection<int, string>
     */
    public function getRemainingSteps(): SupportCollection
    {
        return $this
            ->definition
            ->steps($this)
            ->keys()
            ->diff(
                $this->items
                    ->where('finished_at', '!=', null)
                    ->where('canceled_at', '!=', null)
                    ->map(fn ($item) => $item->name)
            );
    }

    public function getItem(string $name): ?WorkflowItem
    {
        return $this->items->firstWhere('name', $name);
    }

    public function getOrCreateItem(string $name): WorkflowItem
    {
        if ($item = $this->getItem($name)) {
            return $item;
        }

        $item = $this->items()->create([
            'name' => $name,
        ]);

        $this->items->push($item);

        return $item;
    }

    public function isStepFinished(string $name): bool
    {
        return (bool) $this->getItem($name)?->isFinished();
    }

    public function isStepCanceled(string $name): bool
    {
        return (bool) $this->getItem($name)?->isCanceled();
    }

    public function isStepFailed(string $name): bool
    {
        return (bool) $this->getItem($name)?->isFailed();
    }

    public function isStepDispatched(string $name): bool
    {
        return (bool) $this->getItem($name)?->isDispatched();
    }

    public function isStepPending(string $name): bool
    {
        return (bool) $this->getItem($name)?->isPending();
    }

    public function markStepAsFinished(string $name): static
    {
        $item = $this->getOrCreateItem($name);
        $item->markAsFinished();

        $this->step = $name;

        if ($this->getRemainingSteps()->isEmpty()) {
            $this->finished_at = $item->finished_at?->clone();
        }

        $this->save();

        return $this;
    }

    public function markStepAsFailed(string $name): static
    {
        $item = $this->getOrCreateItem($name);
        $item->markAsFailed();

        if (! $this->failed_at) {
            $this->failed_at = $item->failed_at?->clone();
            $this->save();
        }

        return $this;
    }

    public function markStepAsCanceled(string $name): static
    {
        $item = $this->getOrCreateItem($name);
        $item->markAsCanceled();

        return $this;
    }

    public function run(): void
    {
        $this->definition->beforeRun($this);

        if (
            $this->isFinished() ||
            $this->isFailed() ||
            $this->isCanceled()
        ) {
            return;
        }

        if (! $this->definition->shouldRun($this)) {
            return;
        }

        $this->definition->run($this);

        $this->definition->afterRun($this);
    }
}
