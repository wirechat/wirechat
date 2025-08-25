<?php

namespace Namu\WireChat;

use Closure;
use Namu\WireChat\Facades\WireChatColor;
use Namu\WireChat\Panel\Concerns\HasActions;
use Namu\WireChat\Panel\Concerns\HasAttachments;
use Namu\WireChat\Panel\Concerns\HasAuth;
use Namu\WireChat\Panel\Concerns\HasBrandName;
use Namu\WireChat\Panel\Concerns\HasBroadcasting;
use Namu\WireChat\Panel\Concerns\HasChatMiddleware;
use Namu\WireChat\Panel\Concerns\HasChatsSearch;
use Namu\WireChat\Panel\Concerns\HasColors;
use Namu\WireChat\Panel\Concerns\HasFavicon;
use Namu\WireChat\Panel\Concerns\HasGroups;
use Namu\WireChat\Panel\Concerns\HasHeading;
use Namu\WireChat\Panel\Concerns\HasId;
use Namu\WireChat\Panel\Concerns\HasLayout;
use Namu\WireChat\Panel\Concerns\HasMiddleware;
use Namu\WireChat\Panel\Concerns\HasRoutes;
use Namu\WireChat\Panel\Concerns\HasSearch;
use Namu\WireChat\Panel\Concerns\HasSearchableAttributes;
use Namu\WireChat\Panel\Concerns\HasSpaMode;
use Namu\WireChat\Panel\Concerns\HasWebPushNotifications;
use Namu\WireChat\Support\EvaluatesClosures;

class Panel
{
    use EvaluatesClosures;
    use HasActions;
    use HasAttachments;
    use HasAuth;
    use HasBrandName;
    use HasBroadcasting;
    use HasChatMiddleware;
    use HasChatsSearch;
    use HasSearch;
    use HasFavicon;
    use HasGroups;
    use HasHeading;
    use HasId;
    use HasColors;
    use HasLayout;
    use HasMiddleware;
    use HasRoutes;
    use HasSearchableAttributes;
    use HasSpaMode;
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


    protected function evaluate($value)
    {
        return $value instanceof Closure ? call_user_func($value, $this) : $value;
    }
}
