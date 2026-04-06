<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Facades\Wirechat;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $avatar_url
 * @property GroupType $type
 * @property bool $allow_members_to_send_messages
 * @property bool $allow_members_to_add_others
 * @property bool $allow_members_to_edit_group_info
 * @property int $admins_must_approve_new_members when turned on, admins must approve anyone who wants to join group
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Wirechat\Wirechat\Models\Conversation $conversation
 * @property-read \Wirechat\Wirechat\Models\Attachment|null $cover
 * @property-read string|null $cover_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Wirechat\Wirechat\Models\Invite> $inviteLinks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Wirechat\Wirechat\Models\JoinRequest> $joinRequests
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAdminsMustApproveNewMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAllowMembersToAddOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAllowMembersToEditGroupInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAllowMembersToSendMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
        'type' => GroupType::class,
        'allow_members_to_send_messages' => 'boolean',
        'allow_members_to_add_others' => 'boolean',
        'allow_members_to_edit_group_info' => 'boolean',
        'admins_must_approve_new_members' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('groups');
        parent::__construct($attributes);
    }

    protected static function booted(): void
    {
        static::deleted(function ($group) {
            if ($group->cover?->exists()) {
                $group->cover->delete();
            }

            $group->joinRequests()->delete();
            $group->inviteLinks()->delete();
        });
    }

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Wirechat\Wirechat\Workbench\Database\Factories\GroupFactory::new();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Wirechat::conversationModelClass());
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover?->url;
    }

    /**
     * Check if group is owned by
     */
    public function isOwnedBy(Model|Authenticatable $user): bool
    {
        $conversation = $this->conversation;

        if ($conversation->relationLoaded('participants')) {
            return $conversation->participants->contains(function ($participant) use ($user) {
                return $participant->participantable_id == $user->getKey()
                    && $participant->participantable_type == $user->getMorphClass()
                    && $participant->role == ParticipantRole::OWNER;
            });
        }

        return $conversation->participants()
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', $user->getMorphClass())
            ->where('role', ParticipantRole::OWNER)
            ->exists();
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(Wirechat::attachmentModelClass(), 'attachable');
    }

    public function inviteLinks(): MorphMany
    {
        return $this->morphMany(Invite::class, 'inviteable');
    }

    public function joinRequests(): MorphMany
    {
        return $this->morphMany(JoinRequest::class, 'joinable');
    }

    public function pendingJoinRequests(): MorphMany
    {
        return $this->joinRequests()->pending();
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

    public function isPublic(): bool
    {
        return $this->type === GroupType::PUBLIC;
    }

    public function isPrivateAccess(): bool
    {
        return $this->type === GroupType::PRIVATE;
    }

    public function requiresInviteApproval(): bool
    {
        return $this->isPrivateAccess() || (bool) $this->admins_must_approve_new_members;
    }

    public function inviteJoinBlockedFor(Model|Authenticatable $user): bool
    {
        $participant = $this->conversation
            ->participants()
            ->withoutGlobalScopes()
            ->whereParticipantable($user)
            ->first();

        if (! $participant) {
            return false;
        }

        return $participant->isBlockedByAdmin();
    }

    public function hasPendingJoinRequest(Model|Authenticatable $user): bool
    {
        return $this->pendingJoinRequests()
            ->whereRequester($user)
            ->exists();
    }

    public function requestToJoin(Model|Authenticatable $user, ?Invite $invite = null): JoinRequest
    {
        $request = $this->pendingJoinRequests()
            ->whereRequester($user)
            ->latest('id')
            ->first();

        if ($request) {
            if ($invite) {
                $request->forceFill([
                    'invite_id' => $invite->getKey(),
                    'data' => ['invite_id' => $invite->getKey(), 'token' => $invite->token],
                ])->save();
            }

            return $request->refresh();
        }

        return $this->joinRequests()->create([
            'requester_id' => $user->getKey(),
            'requester_type' => $user->getMorphClass(),
            'invite_id' => $invite?->getKey(),
            'data' => $invite ? ['invite_id' => $invite->getKey(), 'token' => $invite->token] : null,
        ]);
    }

    public function acceptPendingJoinRequest(Model|Authenticatable $user, Model|Authenticatable|null $reviewedBy = null, bool $markInviteUsed = false): ?JoinRequest
    {
        $request = $this->pendingJoinRequests()
            ->whereRequester($user)
            ->with('invite')
            ->latest('id')
            ->first();

        if (! $request) {
            return null;
        }

        $request->approve($reviewedBy);

        if ($markInviteUsed && $request->invite?->isActive()) {
            $request->invite->markUsed();
        }

        return $request->refresh();
    }

    public function dismissPendingJoinRequest(Model|Authenticatable $user, Model|Authenticatable|null $reviewedBy = null): ?JoinRequest
    {
        $request = $this->pendingJoinRequests()
            ->whereRequester($user)
            ->latest('id')
            ->first();

        if (! $request) {
            return null;
        }

        $request->dismiss($reviewedBy);

        return $request->refresh();
    }

    public function clearJoinRequest(Model|Authenticatable $user, Model|Authenticatable|null $reviewedBy = null, bool $markInviteUsed = false): ?JoinRequest
    {
        return $this->acceptPendingJoinRequest($user, $reviewedBy, $markInviteUsed);
    }
}
