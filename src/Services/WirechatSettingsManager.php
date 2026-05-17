<?php

namespace Wirechat\Wirechat\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Setting;
use Wirechat\Wirechat\Settings\UserSettings;

class WirechatSettingsManager
{
    public function for(Model $owner): UserSettings
    {
        $setting = $this->rowFor($owner);

        return UserSettings::fromArray($setting->data ?? []);
    }

    public function rowFor(Model $owner): ?Setting
    {
        return $this->queryFor($owner)->first();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateFor(Model $owner, array $values): UserSettings
    {
        $current = $this->for($owner);
        $next = UserSettings::fromArray(array_merge($current->toArray(), $values));

        if ($next->toArray() === $current->toArray()) {
            return $current;
        }

        return $this->saveFor($owner, $next);
    }

    public function saveFor(Model $owner, UserSettings|array $settings): UserSettings
    {
        $settings = is_array($settings) ? UserSettings::fromArray($settings) : $settings;
        $data = $this->changedData($settings);

        if ($data === []) {
            $this->queryFor($owner)->delete();

            return $settings;
        }

        Wirechat::settingModel()->newQuery()->updateOrCreate(
            [
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
            ],
            ['data' => $data]
        );

        return $settings;
    }

    /**
     * @return Builder<Setting>
     */
    protected function queryFor(Model $owner): Builder
    {
        return Wirechat::settingModel()->newQuery()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey());
    }

    /**
     * @return array<string, bool>
     */
    protected function changedData(UserSettings $settings): array
    {
        $defaults = (new UserSettings)->toArray();

        return collect($settings->toArray())
            ->filter(fn (bool $value, string $key): bool => $value !== $defaults[$key])
            ->all();
    }
}
