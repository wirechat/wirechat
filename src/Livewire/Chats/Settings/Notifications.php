<?php

namespace Wirechat\Wirechat\Livewire\Chats\Settings;

use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Services\WirechatSettingsManager;

class Notifications extends SettingsPage
{
    public bool $messages = true;

    public bool $groups = true;

    public bool $previews = true;

    public function mount(): void
    {
        parent::mount();

        $owner = $this->settingsOwner();
        $settings = Wirechat::settings($owner);

        $this->messages = $settings->direct_message_notifications_enabled;
        $this->groups = $settings->group_message_notifications_enabled;
        $this->previews = $settings->notification_previews_enabled;
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

    protected function settingsOwner(): Model
    {
        $owner = auth()->user();

        abort_unless($owner instanceof Model, 401);

        return $owner;
    }

    public function render()
    {
        return view('wirechat::livewire.chats.settings.notifications');
    }
}
