<?php

namespace Wirechat\Wirechat\Settings;

final class UserSettings
{
    public function __construct(
        public bool $notifications_enabled = true,
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
            'sound_enabled' => $this->sound_enabled,
            'read_receipts_enabled' => $this->read_receipts_enabled,
        ];
    }
}
