<?php

namespace Namu\WireChat;

use Closure;
use Namu\WireChat\Panel\Concerns\HasActions;
use Namu\WireChat\Panel\Concerns\HasAttachments;
use Namu\WireChat\Panel\Concerns\HasAuth;
use Namu\WireChat\Panel\Concerns\HasBroadcasting;
use Namu\WireChat\Panel\Concerns\HasChatablesSearch;
use Namu\WireChat\Panel\Concerns\HasChatMiddleware;
use Namu\WireChat\Panel\Concerns\HasChatsSearch;
use Namu\WireChat\Panel\Concerns\HasColors;
use Namu\WireChat\Panel\Concerns\HasGroups;
use Namu\WireChat\Panel\Concerns\HasHeading;
use Namu\WireChat\Panel\Concerns\HasId;
use Namu\WireChat\Panel\Concerns\HasLayout;
use Namu\WireChat\Panel\Concerns\HasMiddleware;
use Namu\WireChat\Panel\Concerns\HasRoutes;
use Namu\WireChat\Panel\Concerns\HasSearchableAttributes;
use Namu\WireChat\Panel\Concerns\HasWebPushNotifications;
use Namu\WireChat\Support\EvaluatesClosures;

class Panel
{
    use EvaluatesClosures;
    use HasActions;
    use HasAttachments;
    use HasAuth;
    use HasBroadcasting;
    use HasChatablesSearch;
    use HasChatMiddleware;
    use HasChatsSearch;
    use HasColors;
    use HasGroups;
    use HasHeading;
    use HasId;
    use HasLayout;
    use HasMiddleware;
    use HasRoutes;
    use HasSearchableAttributes;
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
        // WireChatColor::register($this->getColors());
    }
}
