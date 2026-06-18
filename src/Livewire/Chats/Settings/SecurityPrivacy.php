<?php

namespace Wirechat\Wirechat\Livewire\Chats\Settings;

use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Services\WirechatSettingsManager;

class SecurityPrivacy extends SettingsPage
{
    public bool $groupsCanAddMe = true;

    public function mount(): void
    {
        parent::mount();

        $this->groupsCanAddMe = Wirechat::settings($this->settingsOwner())->groups_can_add_me;
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
        return view('wirechat::livewire.chats.settings.security-privacy');
    }
}
