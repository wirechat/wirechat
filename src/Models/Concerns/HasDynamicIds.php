<?php

namespace Namu\WireChat\Models\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Namu\WireChat\Facades\WireChat;

trait HasDynamicIds
{
    /**
     * Initialize the trait.
     *
     * @return void
     */
    public function initializeHasDynamicIds()
    {
        $this->usesUniqueIds = WireChat::usesUuid();
    }

    /**
     * Generate a new unique key for the model (only for UUIDs).
     *
     * @return string|null
     */
    public function newUniqueId()
    {
        if (WireChat::usesUuid()) {
            /** @phpstan-ignore-next-line */
            if (method_exists(\Illuminate\Support\Str::class, 'uuid7')) {
                return (string) Str::uuid7();
            }

            return (string) Str::uuid();
        }

        return null;
    }

    /**
     * Determine if the given key is valid.
     *
     * @param  mixed  $value
     */
    protected function isValidUniqueId($value): bool
    {
        if (WireChat::usesUuid()) {
            return Str::isUuid($value);
        }

        // For integer IDs, check if the value is a positive integer
        return is_numeric($value) && (int) $value == $value && $value > 0;
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return $this->usesUniqueIds ? [$this->getKeyName()] : [];
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\Relation  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field && in_array($field, $this->uniqueIds()) && ! $this->isValidUniqueId($value)) {
            $this->handleInvalidUniqueId($value, $field);
        }

        if (! $field && in_array($this->getRouteKeyName(), $this->uniqueIds()) && ! $this->isValidUniqueId($value)) {
            $this->handleInvalidUniqueId($value, $field);
        }

        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return WireChat::usesUuid() ? 'string' : 'int';
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return ! WireChat::usesUuid();
    }

    /**
     * Throw an exception for the given invalid unique ID.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return never
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function handleInvalidUniqueId($value, $field)
    {
        throw (new ModelNotFoundException)->setModel(get_class($this), $value);
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasDynamicIds()
    {
        if (WireChat::usesUuid()) {
            static::creating(function ($model) {
                if (! $model->getKey()) {
                    $model->{$model->getKeyName()} = $model->newUniqueId();
                }
            });
        }
    }
}
