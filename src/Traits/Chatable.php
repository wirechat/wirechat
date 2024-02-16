<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Namu\WireChat\Models\Conversation;

/**
 * Trait Chatable
 *
 * This trait defines the behavior for models that can participate in conversations within the WireChat system.
 * It provides methods to establish relationships with conversations, define cover images for avatars,
 * and specify the route for redirecting to the user's profile page.
 *
 * @package Namu\WireChat\Traits
 */
trait Chatable
{
    /**
     * Establishes a relationship between the user and conversations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'sender_id')->orWhere('receiver_id', $this->id);
    }

    /**
     * Returns the URL for the cover image to be used as an avatar.
     *
     * @return string|null
     */
    public function wireChatCoverUrl(): ?string
    {
        return null;
    }

    /**
     * Returns the URL for the user's profile page.
     *
     * @return string|null
     */
    public function wireChatProfileUrl(): ?string
    {
        return null;
    }

    /**
     * Returns the display name for the user.
     *
     * @return string|null
     */
    public function wireChatDisplayName(): ?string
    {
        return $this->name ?? 'user';
    }
}
