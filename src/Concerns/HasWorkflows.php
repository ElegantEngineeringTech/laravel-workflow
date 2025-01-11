<?php

declare(strict_types=1);

namespace Elegantly\Workflow\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWorkflows
{
    /**
     * @return MorphMany<Workflow, $this>
     */
    public function workflows(): MorphMany
    {
        return $this->morphMany(Workflow::class, 'model');
    }
}
