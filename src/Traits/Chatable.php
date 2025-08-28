<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Group;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Panel;

/**
 * @deprecated since 0.3.0 — use {@see \Namu\WireChat\Traits\InteractsWithWireChat} instead.
 * @method string getProfileUrlAttribute()
 *
 */
trait Chatable
{
    use Actor;
    use InteractsWithPanel;
    use InteractsWithWireChat;
}
