<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wirechat\Wirechat\Facades\Wirechat;

trait ProtectsGroupAddPrivacy
{
    /**
     * @return Collection<int, Model>
     */
    protected function groupAddableSearchResultModels(): Collection
    {
        if (blank($this->search)) {
            return collect();
        }

        return collect($this->panel()->searchUsers($this->search)->collection)
            ->map(fn ($resource) => $resource->resource ?? null)
            ->filter(fn ($model) => $model instanceof Model)
            ->filter(fn (Model $model): bool => $this->canBeAddedToGroups($model))
            ->values();
    }

    protected function resolveGroupAddableSearchResult($id, string $class): ?Model
    {
        $model = $this->resolvePanelSearchResult($id, $class);

        if ($model) {
            $this->authorizeCanBeAddedToGroups($model);
        }

        return $model;
    }

    protected function authorizeCanBeAddedToGroups(Model $model): void
    {
        abort_unless(
            $this->canBeAddedToGroups($model),
            403,
            __('wirechat::chat.group.add_members.messages.group_add_privacy_denied', [
                'member' => data_get($model, 'wirechat_name', __('wirechat::chat.labels.user')),
            ])
        );
    }

    protected function canBeAddedToGroups(Model $model): bool
    {
        return Wirechat::settings($model)->groups_can_add_me;
    }
}
