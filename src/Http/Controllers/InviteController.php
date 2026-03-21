<?php

namespace Wirechat\Wirechat\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Invite;

class InviteController extends Controller
{
    protected function resolveInvite(string $token): Invite
    {
        $panel = Wirechat::currentPanel();

        abort_if($panel === null, 404);

        $invite = Invite::query()
            ->where('panel_id', $panel->getId())
            ->where('token', $token)
            ->with(['inviteable.conversation', 'inviteable.cover'])
            ->firstOrFail();

        abort_unless($invite->isActive(), 410, 'Invite link is no longer active.');

        return $invite;
    }

    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $group = $invite->inviteable;

        abort_unless($group instanceof Group, 404);

        $conversation = $group->conversation()->with(['participants.participantable'])->firstOrFail();
        $auth = $request->user();

        abort_unless($auth !== null, 401);

        if ($auth->belongsToConversation($conversation)) {
            return redirect()->to(Wirechat::currentPanel()->chatRoute($conversation->id));
        }

        $participant = $conversation->participants()
            ->withoutGlobalScopes()
            ->whereParticipantable($auth)
            ->first();

        $joinBlocked = (bool) ($participant?->isRemovedByAdmin() && ! $group->admins_must_approve_new_members);

        return view('wirechat::pages.invite', [
            'panel' => Wirechat::currentPanel()->getId(),
            'invite' => $invite,
            'group' => $group,
            'conversation' => $conversation,
            'membersPreview' => $conversation->participants->take(5),
            'requiresAdminApproval' => (bool) $group->admins_must_approve_new_members,
            'hasPendingJoinRequest' => $group->hasPendingJoinRequest($auth),
            'joinBlocked' => $joinBlocked,
        ]);
    }

    public function join(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $group = $invite->inviteable;

        abort_unless($group instanceof Group, 404);

        $conversation = $group->conversation;
        $auth = $request->user();

        abort_unless($auth !== null, 401);

        if ($auth->belongsToConversation($conversation)) {
            return redirect()->to(Wirechat::currentPanel()->chatRoute($conversation->id));
        }

        if ($group->admins_must_approve_new_members) {
            $group->requestToJoin($auth);

            return redirect()
                ->to(Wirechat::currentPanel()->inviteRoute($invite->token))
                ->with('wirechat_invite_notice', __('wirechat::chat.group.invite_link.page.messages.request_submitted'));
        }

        $conversation->join($auth);
        $invite->markUsed();

        return redirect()->to(Wirechat::currentPanel()->chatRoute($conversation->id));
    }
}
