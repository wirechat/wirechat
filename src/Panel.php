<?php

namespace Namu\WireChat;

use Closure;
use Illuminate\Support\Arr;
use Namu\WireChat\Panel\Concerns\HasActions;
use Namu\WireChat\Panel\Concerns\HasAttachments;
use Namu\WireChat\Panel\Concerns\HasAuth;
use Namu\WireChat\Panel\Concerns\HasBrandName;
use Namu\WireChat\Panel\Concerns\HasBroadcasting;
use Namu\WireChat\Panel\Concerns\HasChatsSearch;
use Namu\WireChat\Panel\Concerns\HasFavicon;
use Namu\WireChat\Panel\Concerns\HasGroups;
use Namu\WireChat\Panel\Concerns\HasHeading;
use Namu\WireChat\Panel\Concerns\HasId;
use Namu\WireChat\Panel\Concerns\HasMiddleware;
use Namu\WireChat\Panel\Concerns\HasNotifications;
use Namu\WireChat\Panel\Concerns\HasParticipantableSearchColumns;
use Namu\WireChat\Panel\Concerns\HasRoutes;
use Namu\WireChat\Panel\Concerns\HasSpaMode;
use Namu\WireChat\Support\EvaluatesClosures;

class Panel
{
    use EvaluatesClosures;
    use HasMiddleware;
    use HasRoutes;
    use HasId;
    use HasBrandName;
    use HasFavicon;
    use HasSpaMode;
    use HasNotifications;
    use HasAttachments;
    use HasGroups;
    use HasBroadcasting;
    use HasAuth;
    use HasChatsSearch;
    use HasActions;
    use HasHeading;
    use HasParticipantableSearchColumns;

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


    protected function evaluate($value)
    {
        return $value instanceof Closure ? call_user_func($value, $this) : $value;
    }
}
