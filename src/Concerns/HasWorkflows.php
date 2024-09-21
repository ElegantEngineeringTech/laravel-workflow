<?php

namespace Elegantly\Workflow\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWorkflows
{
    /**
     * @return MorphMany<Workflow, Model>
     */
    public function workflows(): MorphMany
    {
        return $this->morphMany(Workflow::class, 'model');
    }
}
