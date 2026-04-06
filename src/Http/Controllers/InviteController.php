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

        abort_if($panel === null || ! $panel->hasGroupInvitations(), 404);

        $invite = Invite::query()
            ->where('panel_id', $panel->getId())
            ->where('token', $token)
            ->with(['inviteable.conversation', 'inviteable.cover'])
            ->firstOrFail();

        abort_unless($invite->isActive(), 410, 'Invite link is no longer active.');

        return $invite;
    }

    public function show(Request $request, string $token): View
    {
        $invite = $this->resolveInvite($token);
        $group = $invite->inviteable;

        abort_unless($group instanceof Group, 404);

        /** @var \Wirechat\Wirechat\Models\Conversation $conversation */
        $conversation = $group->conversation()->with(['participants.participantable'])->firstOrFail();
        $auth = $request->user();

        $isMember = false;
        $hasPendingJoinRequest = false;
        $joinBlocked = false;

        if ($auth !== null) {
            $isMember = $auth->belongsToConversation($conversation);
            $hasPendingJoinRequest = ! $isMember && $group->hasPendingJoinRequest($auth);
            $joinBlocked = ! $isMember && $group->inviteJoinBlockedFor($auth);
        }

        return view('wirechat::pages.invite', [
            'panel' => Wirechat::currentPanel()->getId(),
            'invite' => $invite,
            'group' => $group,
            'conversation' => $conversation,
            'membersPreview' => $conversation->participants->take(5),
            'isMember' => $isMember,
            'hasPendingJoinRequest' => $hasPendingJoinRequest,
            'joinBlocked' => $joinBlocked,
        ]);
    }

    public function join(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $panel = Wirechat::currentPanel();

        $request->session()->put('wirechat_pending_invite_token', $invite->token);
        $request->session()->put('wirechat_pending_invite_panel', $panel?->getId());

        $redirect = $panel?->getInviteJoinRedirectUrl($request);

        return redirect()->to($redirect ?: $panel->chatsRoute());
    }
}
