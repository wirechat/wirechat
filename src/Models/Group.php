<?php

namespace Namu\WireChat\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Enums\GroupType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string $name
 * @property string $description
 * @property string $avatar_url
 * @property GroupType $type
 * @property bool $allow_members_to_send_messages
 * @property bool $allow_members_to_add_others
 * @property bool $allow_members_to_edit_group_info
 * @property bool $admins_must_approve_new_members
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Namu\WireChat\Models\Conversation $conversation
 * @property-read \Namu\WireChat\Models\Attachment $cover
 * @property-read string $cover_url
 */
class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'name',
        'description',
    ];

    protected $casts = [
        'type'=>GroupType::class,
        'allow_members_to_send_messages' => 'boolean',
        'allow_members_to_add_others' => 'boolean',
        'allow_members_to_edit_group_info' => 'boolean'
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('groups');
        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        // listen to deleted
        static::deleted(function ($group) {

            if ($group->cover?->exists()) {

                // delete cover
                $group->cover->delete();

                // also delete from storage
                if (Storage::disk(WireChat::storageDisk())->exists($group->cover->file_path)) {
                    Storage::disk(WireChat::storageDisk())->delete($group->cover->file_path);
                }
            }

        });
    }

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\GroupFactory::new();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover->url;

    }

    /**
     * Check if group is owned by
     * @return bool 
     */
    public function isOwnedBy(Model|Authenticatable $user): bool
    {

        $conversation = $this->conversation;

        // Check if participants are already loaded
        if ($conversation->relationLoaded('participants')) {
            // If loaded, simply check the existing collection
            return $conversation->participants->contains(function ($participant) use ($user) {
                return $participant->participantable_id == $user->getKey() &&
                    $participant->participantable_type == $user->getMorphClass() &&
                    $participant->role == ParticipantRole::OWNER;
            });
        }

        // If not loaded, perform the query
        return $conversation->participants()
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', $user->getMorphClass())
            ->where('role', ParticipantRole::OWNER)
            ->exists();
    }

    public function cover()
    {
        return $this->morphOne(Attachment::class, 'attachable');
    }

    /**
     * Permissions
     */
    public function allowsMembersToSendMessages(): bool
    {
        return $this->allow_members_to_send_messages == true;
    }

    public function allowsMembersToAddOthers(): bool
    {
        return $this->allow_members_to_add_others == true;
    }

    public function allowsMembersToEditGroupInfo(): bool
    {
        return $this->allow_members_to_edit_group_info == true;
    }
}
