<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasConversationsQuery
{
    protected ?Closure $modifyConversationsQueryCallback = null;

    public function modifyConversationsQuery(?Closure $callback): static
    {
        $this->modifyConversationsQueryCallback = $callback;

        return $this;
    }

    public function applyConversationsQueryModifier(Builder $query, ?Model $auth = null): Builder
    {
        if ($this->modifyConversationsQueryCallback === null) {
            return $query;
        }

        $modifiedQuery = $this->evaluate(
            $this->modifyConversationsQueryCallback,
            [
                'query' => $query,
                'auth' => $auth,
            ],
            [
                Builder::class => $query,
                Model::class => $auth,
            ],
        );

        return $modifiedQuery instanceof Builder ? $modifiedQuery : $query;
    }
}
