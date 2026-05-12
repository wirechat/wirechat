<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesPanelSearchResults
{
    protected function resolvePanelSearchResult($id, string $class): ?Model
    {
        if (blank($this->search)) {
            return null;
        }

        return collect($this->panel()->searchUsers($this->search)->collection)
            ->map(fn ($resource) => $resource->resource ?? null)
            ->filter(fn ($model) => $model instanceof Model)
            ->first(fn (Model $model): bool => (string) $model->getKey() === (string) $id
                && $model->getMorphClass() === $class);
    }
}
