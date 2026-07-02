<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Wirechat\Wirechat\Http\Resources\WirechatUserResource;

trait HasUsersSearch
{
    protected ?Closure $searchCallback = null;

    public function searchUsersUsing(Closure $callback): static
    {
        $this->searchCallback = $callback;

        return $this;
    }

    /**
     * Search for chatable users and return a standardized JSON resource collection.
     *
     * @return AnonymousResourceCollection
     */
    public function searchUsers(?string $needle)
    {
        return WirechatUserResource::collection(
            $this->runSearchCallback($needle)
        );
    }

    /**
     * Execute the search logic and return a collection of models.
     *
     * @return Collection
     */
    protected function runSearchCallback(?string $needle)
    {
        if (blank($needle)) {
            return collect();
        }

        if ($this->searchCallback) {
            // Expect the callback to return a Collection of models
            return ($this->searchCallback)($needle);
        }

        $userModel = $this->defaultSearchUserModel();

        // Default search: limit 20 results and return a collection
        // @phpstan-ignore-next-line
        return $userModel::query()
            ->where(function ($q) use ($needle) {
                foreach ($this->getSearchableAttributes() as $field) {
                    $q->orWhere($field, 'like', "%{$needle}%");
                }
            })
            ->limit(20)
            ->get();
    }

    /**
     * @return class-string<Model>
     */
    protected function defaultSearchUserModel(): string
    {
        $class = (string) config('wirechat.models.user', config('wirechat.user_model', 'App\\Models\\User'));

        if (! class_exists($class)) {
            throw new \InvalidArgumentException("Model class '{$class}' configured in 'wirechat.models.user' does not exist.");
        }

        if (! is_a($class, Model::class, true)) {
            throw new \InvalidArgumentException("Model class '{$class}' configured in 'wirechat.models.user' must extend '".Model::class."'.");
        }

        return $class;
    }
}
