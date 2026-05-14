<?php

namespace Wirechat\Wirechat\Settings;

final class UserSettings
{
    public function __construct(
        public bool $notifications_enabled = true,
        public bool $direct_message_notifications_enabled = true,
        public bool $group_message_notifications_enabled = true,
        public bool $notification_previews_enabled = true,
        public bool $sound_enabled = true,
        public bool $read_receipts_enabled = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data = []): self
    {
        return new self(
            notifications_enabled: (bool) ($data['notifications_enabled'] ?? true),
            direct_message_notifications_enabled: (bool) ($data['direct_message_notifications_enabled'] ?? true),
            group_message_notifications_enabled: (bool) ($data['group_message_notifications_enabled'] ?? true),
            notification_previews_enabled: (bool) ($data['notification_previews_enabled'] ?? true),
            sound_enabled: (bool) ($data['sound_enabled'] ?? true),
            read_receipts_enabled: (bool) ($data['read_receipts_enabled'] ?? true),
        );
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'notifications_enabled' => $this->notifications_enabled,
            'direct_message_notifications_enabled' => $this->direct_message_notifications_enabled,
            'group_message_notifications_enabled' => $this->group_message_notifications_enabled,
            'notification_previews_enabled' => $this->notification_previews_enabled,
            'sound_enabled' => $this->sound_enabled,
            'read_receipts_enabled' => $this->read_receipts_enabled,
        ];
    }
}
