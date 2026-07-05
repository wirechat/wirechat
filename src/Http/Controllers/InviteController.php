<?php

namespace Wirechat\Wirechat\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Invite;

class InviteController extends Controller
{
    protected function resolveInvite(string $token): Invite
    {
        $panel = Wirechat::currentPanel();

        abort_if($panel === null || ! $panel->hasGroupInvitations(), 404);

        $invite = Wirechat::inviteModelClass()::query()
            ->where('panel_id', $panel->getId())
            ->where('token', $token)
            ->with('inviteable')
            ->firstOrFail();

        abort_unless($invite->isActive(), 410, 'Invite link is no longer active.');

        return $invite;
    }

    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $group = $invite->inviteable;

        abort_unless($group instanceof Group, 404);

        /** @var Conversation $conversation */
        $conversation = $group->conversation()->with(['participants.participantable'])->firstOrFail();
        $auth = $request->user();
        $panel = Wirechat::currentPanel();

        abort_if($panel === null, 404);

        $isMember = false;
        $hasPendingJoinRequest = false;
        $joinBlocked = false;
        $requiresApproval = false;

        if ($auth !== null) {
            $isMember = $auth->belongsToConversation($conversation);

            // If the user is already a member, skip the invite preview entirely
            // and route them straight into the conversation.
            if ($isMember) {
                $chatRoute = $panel->chatRouteIfRegistered($conversation->id);

                if ($chatRoute !== null) {
                    return redirect()->to($chatRoute);
                }

                $mountUrl = $panel->chatUrl($conversation->id);

                abort_if($mountUrl === null, 404);

                $request->session()->put('wirechat_pending_conversation_id', $conversation->id);
                $request->session()->put('wirechat_pending_conversation_panel', $panel->getId());

                return redirect()->to($mountUrl);
            }

            $requiresApproval = $group->requiresInviteApproval();
            $hasPendingJoinRequest = $requiresApproval && $group->hasPendingJoinRequest($auth);
            $joinBlocked = $group->inviteJoinBlockedFor($auth);
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
            'requiresApproval' => $requiresApproval,
        ]);
    }

    public function join(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $panel = Wirechat::currentPanel();

        abort_if($panel === null, 404);

        abort_unless($invite->inviteable instanceof Group, 404);

        $request->session()->put('wirechat_pending_invite_token', $invite->token);
        $request->session()->put('wirechat_pending_invite_panel', $panel->getId());

        $redirect = $panel->getInviteJoinRedirectUrl($request)
            ?? $panel->chatsUrl();

        abort_if($redirect === null, 404);

        return redirect()->to($redirect);
    }
}
