<?php

namespace Wirechat\Wirechat\Livewire\Chats\Settings;

use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Services\WirechatSettingsManager;

class Index extends SettingsPage
{
    public bool $messages = true;

    public bool $groups = true;

    public bool $previews = true;

    public bool $groupsCanAddMe = true;

    public function mount(): void
    {
        parent::mount();

        $settings = Wirechat::settings($this->settingsOwner());

        $this->messages = $settings->direct_message_notifications_enabled;
        $this->groups = $settings->group_message_notifications_enabled;
        $this->previews = $settings->notification_previews_enabled;
        $this->groupsCanAddMe = $settings->groups_can_add_me;
    }

    public function toggleNotificationSetting(string $setting): void
    {
        if (! in_array($setting, ['messages', 'groups', 'previews'], true)) {
            return;
        }

        $this->{$setting} = ! $this->{$setting};

        app(WirechatSettingsManager::class)->updateFor($this->settingsOwner(), [
            'direct_message_notifications_enabled' => $this->messages,
            'group_message_notifications_enabled' => $this->groups,
            'notification_previews_enabled' => $this->previews,
        ]);
    }

    public function toggleGroupsCanAddMe(): void
    {
        $this->groupsCanAddMe = ! $this->groupsCanAddMe;

        app(WirechatSettingsManager::class)->updateFor($this->settingsOwner(), [
            'groups_can_add_me' => $this->groupsCanAddMe,
        ]);
    }

    protected function settingsOwner(): Model
    {
        $owner = auth()->user();

        abort_unless($owner instanceof Model, 401);

        return $owner;
    }

    public function render()
    {
        return view('wirechat::livewire.chats.settings.index');
    }
}
