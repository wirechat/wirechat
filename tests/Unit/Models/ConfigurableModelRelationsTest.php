<?php

use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Action;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\JoinRequest;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;

beforeEach(function () {
    config([
        'wirechat.models.action' => Action::class,
        'wirechat.models.attachment' => Attachment::class,
        'wirechat.models.conversation' => Conversation::class,
        'wirechat.models.group' => Group::class,
        'wirechat.models.invite' => Invite::class,
        'wirechat.models.join_request' => JoinRequest::class,
        'wirechat.models.message' => Message::class,
        'wirechat.models.participant' => Participant::class,
    ]);

    Wirechat::resetTableNameCache();
});

it('uses configured model classes in package relations', function () {
    $customConversation = new class extends Conversation {};
    $customGroup = new class extends Group {};
    $customInvite = new class extends Invite {};
    $customJoinRequest = new class extends JoinRequest {};
    $customMessage = new class extends Message {};
    $customParticipant = new class extends Participant {};
    $customAttachment = new class extends Attachment {};
    $customAction = new class extends Action {};

    config([
        'wirechat.models.action' => get_class($customAction),
        'wirechat.models.attachment' => get_class($customAttachment),
        'wirechat.models.conversation' => get_class($customConversation),
        'wirechat.models.group' => get_class($customGroup),
        'wirechat.models.invite' => get_class($customInvite),
        'wirechat.models.join_request' => get_class($customJoinRequest),
        'wirechat.models.message' => get_class($customMessage),
        'wirechat.models.participant' => get_class($customParticipant),
    ]);

    Wirechat::resetTableNameCache();

    expect((new Conversation)->participants()->getRelated())->toBeInstanceOf(get_class($customParticipant))
        ->and((new Conversation)->messages()->getRelated())->toBeInstanceOf(get_class($customMessage))
        ->and((new Conversation)->group()->getRelated())->toBeInstanceOf(get_class($customGroup))
        ->and((new Group)->conversation()->getRelated())->toBeInstanceOf(get_class($customConversation))
        ->and((new Group)->inviteLinks()->getRelated())->toBeInstanceOf(get_class($customInvite))
        ->and((new Group)->joinRequests()->getRelated())->toBeInstanceOf(get_class($customJoinRequest))
        ->and((new JoinRequest)->invite()->getRelated())->toBeInstanceOf(get_class($customInvite))
        ->and((new Message)->conversation()->getRelated())->toBeInstanceOf(get_class($customConversation))
        ->and((new Message)->participant()->getRelated())->toBeInstanceOf(get_class($customParticipant))
        ->and((new Message)->attachment()->getRelated())->toBeInstanceOf(get_class($customAttachment))
        ->and((new Participant)->conversation()->getRelated())->toBeInstanceOf(get_class($customConversation))
        ->and((new Participant)->messages()->getRelated())->toBeInstanceOf(get_class($customMessage));
});
