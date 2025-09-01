<?php

namespace Wirechat\Wirechat;

use Closure;
use Wirechat\Wirechat\Panel\Concerns\HasActions;
use Wirechat\Wirechat\Panel\Concerns\HasAttachments;
use Wirechat\Wirechat\Panel\Concerns\HasAuth;
use Wirechat\Wirechat\Panel\Concerns\HasBroadcasting;
use Wirechat\Wirechat\Panel\Concerns\HasChatMiddleware;
use Wirechat\Wirechat\Panel\Concerns\HasChatsSearch;
use Wirechat\Wirechat\Panel\Concerns\HasColors;
use Wirechat\Wirechat\Panel\Concerns\HasEmojiPicker;
use Wirechat\Wirechat\Panel\Concerns\HasFavicon;
use Wirechat\Wirechat\Panel\Concerns\HasGroups;
use Wirechat\Wirechat\Panel\Concerns\HasHeading;
use Wirechat\Wirechat\Panel\Concerns\HasId;
use Wirechat\Wirechat\Panel\Concerns\HasLayout;
use Wirechat\Wirechat\Panel\Concerns\HasMiddleware;
use Wirechat\Wirechat\Panel\Concerns\HasRoutes;
use Wirechat\Wirechat\Panel\Concerns\HasSearchableAttributes;
use Wirechat\Wirechat\Panel\Concerns\HasUsersSearch;
use Wirechat\Wirechat\Panel\Concerns\HasWebPushNotifications;
use Wirechat\Wirechat\Support\EvaluatesClosures;

class Panel
{
    use EvaluatesClosures;
    use HasActions;
    use HasAttachments;
    use HasAuth;
    use HasBroadcasting;
    use HasChatMiddleware;
    use HasChatsSearch;
    use HasColors;
    use HasEmojiPicker;
    use HasFavicon;
    use HasGroups;
    use HasHeading;
    use HasId;
    use HasLayout;
    use HasMiddleware;
    use HasRoutes;
    use HasSearchableAttributes;
    use HasUsersSearch;
    use HasWebPushNotifications;

    protected bool|Closure $isDefault = false;

    public static function make(): static
    {
        return app(static::class);

    }

    public function default(bool|Closure $condition = true): static
    {
        $this->isDefault = $condition;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->evaluate($this->isDefault);
    }

    public function register(): void
    {
        // WirechatColor::register($this->getColors());
    }
}
