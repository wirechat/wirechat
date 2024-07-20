<?php

namespace Orchestra\Workbench;

use Illuminate\Support\Collection;

class BuildParser
{
    /**
     * Get Workbench build steps.
     *
     * @param  array<int|string, array<string, mixed>|string>  $config
     * @return \Illuminate\Support\Collection<string, array<string, mixed>>
     */
    public static function make(array $config): Collection
    {
        return Collection::make($config)
            ->mapWithKeys(static function (array|string $build) {
                /** @var string $name */
                $name = match (true) {
                    \is_array($build) => array_key_first($build),
                    \is_string($build) => $build,
                };

                /** @var array<string, mixed> $options */
                $options = match (true) {
                    \is_array($build) => $build[array_key_first($build)],
                    \is_string($build) => [],
                };

                return [
                    $name => Collection::make($options)->mapWithKeys(static fn ($value, $key) => [$key => $value])->all(),
                ];
            });
    }
}
