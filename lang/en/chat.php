<?php

return [

    /**-------------------------
     * Chat
     *------------------------*/
    'labels' => [

        'you_replied_to_yourself' => 'You replied to Yourself',
        'participant_replied_to_you' => ':sender replied to You',
        'participant_replied_to_themself' => ':sender replied to Themself',
        'participant_replied_other_participant' => ':sender replied to :receiver',
        'you' => 'You',
        'user' => 'User',
        'replying_to' => 'Replying to :participant',
        'replying_to_yourself' => 'Replying to Yourself',
        'attachment' => 'Attachment',
    ],

    'inputs' => [
        'message' => [
            'label' => 'Message',
            'placeholder' => 'Type a message',
        ],
        'media' => [
            'label' => 'Media',
            'placeholder' => 'Media',
        ],
        'files' => [
            'label' => 'Files',
            'placeholder' => 'Files',
        ],
    ],

    'message_groups' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',

    ],

    'actions' => [
        'open_group_info' => [
            'label' => 'Group Info',
        ],
        'open_chat_info' => [
            'label' => 'Chat Info',
        ],
        'close_chat' => [
            'label' => 'Close Chat',
        ],
        'clear_chat' => [
            'label' => 'Clear Chat History',
            'confirmation_message' => 'Are you sure you want to clear your chat history? This will only clear your chat and will not affect other participants.',
        ],
        'delete_chat' => [
            'label' => 'Delete Chat',
            'confirmation_message' => 'Are you sure you want to delete this chat? This will only remove the chat from your side and will not delete it for other participants.',
        ],

        'delete_for_everyone' => [
            'label' => 'Delete for everyone',
            'confirmation_message' => 'Are you sure?',
        ],
        'delete_for_me' => [
            'label' => 'Delete for me',
            'confirmation_message' => 'Are you sure?',
        ],
        'reply' => [
            'label' => 'Reply',
        ],

        'exit_group' => [
            'label' => 'Exit Group',
            'confirmation_message' => 'Are you sure you want to exit this group?',
        ],
        'upload_file' => [
            'label' => 'File',
        ],
        'upload_media' => [
            'label' => 'Photos & Videos',
        ],
    ],

    'messages' => [

        'cannot_exit_self_or_private_conversation' => 'Cannot exit self or private conversation',
        'owner_cannot_exit_conversation' => 'Owner cannot exit conversation',
        'rate_limit' => 'Too many attempts!, Please slow down',
        'conversation_not_found' => 'Conversation not found.',
        'conversation_id_required' => 'A conversation id is required',
        'invalid_conversation_input' => 'Invalid conversation input.',
    ],

    /**-------------------------
     * Info Component
     *------------------------*/

    'info' => [
        'heading' => [
            'label' => 'Chat Info',
        ],
        'actions' => [
            'delete_chat' => [
                'label' => 'Delete Chat',
                'confirmation_message' => 'Are you sure you want to delete this chat? This will only remove the chat from your side and will not delete it for other participants.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'Only private and self conversations allowed',
        ],

    ],

    /**-------------------------
     * Group Folder
     *------------------------*/

    'group' => [

        // Group info component
        'info' => [
            'heading' => [
                'label' => 'Group Info',
            ],
            'labels' => [
                'members' => 'Members',
                'add_description' => 'Add a group description',
            ],
            'inputs' => [
                'name' => [
                    'label' => 'Group name',
                    'placeholder' => 'Enter Name',
                ],
                'description' => [
                    'label' => 'Description',
                    'placeholder' => 'Optional',
                ],
                'photo' => [
                    'label' => 'Photo',
                ],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'Delete Group',
                    'confirmation_message' => 'Are you sure you want to delete this Group ?.',
                    'helper_text' => 'Before you can delete the group, you need to remove all group members.',
                ],
                'add_members' => [
                    'label' => 'Add Members',
                ],
                'group_permissions' => [
                    'label' => 'Group Permissions',
                ],
                'invite_via_link' => [
                    'label' => 'Invite Via Group Link',
                ],
                'exit_group' => [
                    'label' => 'Exit Group',
                    'confirmation_message' => 'Are you sure you want to exit Group ?.',

                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Only group conversations allowed',
            ],
        ],
        // Members component
        'members' => [
            'heading' => [
                'label' => 'Members',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Search',
                    'placeholder' => 'Search Members',
                ],
            ],
            'labels' => [
                'members' => 'Members',
                'owner' => 'Owner',
                'admin' => 'Admin',
                'no_members_found' => 'No Members found',
            ],
            'actions' => [
                'send_message_to_yourself' => [
                    'label' => 'Message Yourself',

                ],
                'send_message_to_member' => [
                    'label' => 'Message :member',

                ],
                'dismiss_admin' => [
                    'label' => 'Dismiss As Admin',
                    'confirmation_message' => 'Are you sure you want to dismiss :member as Admin ?.',
                ],
                'make_admin' => [
                    'label' => 'Make Admin',
                    'confirmation_message' => 'Are you sure you want to make :member an Admin ?.',
                ],
                'remove_from_group' => [
                    'label' => 'Remove',
                    'confirmation_message' => 'Are you sure you want remove :member from this Group ?.',
                ],
                'block_member' => [
                    'label' => 'Block Member',
                    'confirmation_message' => 'Are you sure you want to block :member from this group ?.',
                ],
                'past_members' => [
                    'label' => 'Past Members',
                ],
                'blocked_members' => [
                    'label' => 'Blocked Members',
                ],
                'load_more' => [
                    'label' => 'Load more',
                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Only group conversations allowed',
            ],
        ],
        // add-Members component
        'add_members' => [
            'heading' => [
                'label' => 'Add Members',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Search',
                    'placeholder' => 'Search',
                ],
            ],
            'labels' => [

            ],
            'actions' => [
                'save' => [
                    'label' => 'Save',

                ],
                'invite_via_link' => [
                    'label' => 'Invite to group via link',
                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Only group conversations allowed',
                'members_limit_error' => 'Members cannot exceed :count',
                'member_already_exists' => ' Already added to group',
            ],
        ],
        // permissions component
        'permissions' => [
            'heading' => [
                'label' => 'Permissions',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Search',
                    'placeholder' => 'Search',
                ],
            ],
            'labels' => [
                'members_can' => 'Members can',
                'admins_can' => 'Admins can',

            ],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'Edit Group Information',
                    'helper_text' => 'This includes the name, icon and description',
                ],
                'send_messages' => [
                    'label' => 'Send Messages',
                ],
                'add_other_members' => [
                    'label' => 'Add Other Members',
                ],
                'admin_approval' => [
                    'label' => 'Approve New Members',
                    'helper_text' => 'Require admins to approve people who join from an invite link',
                ],

            ],
            'messages' => [
            ],
        ],
        'invite_link' => [
            'heading' => [
                'label' => 'Invite Links',
            ],
            'labels' => [
                'description' => 'Anyone with an account will be able to open one of these links and join your group based on your access settings.',
                'primary_link' => 'Primary Link',
                'admin_approval_enabled' => 'Members need approval from admins to join this group.',
                'admin_approval_disabled' => 'Members do not need approval from admins to join this group.',
                'primary_link_usage_empty' => 'Nobody joined yet',
                'primary_link_usage_limited' => ':usages / :limit uses',
                'primary_link_usage_total' => ':usages joins so far',
                'group_access' => 'Group Access',
                'group_access_requires_approval' => 'People who open these links will need admin approval before they join.',
                'group_access_open' => 'People who open these links can join immediately.',
                'join_requests' => 'Join Requests',
                'join_requests_helper' => 'Review who asked to join this group.',
                'additional_links' => 'Additional Links',
                'additional_links_helper' => 'Create extra invite links with their own expiry and usage limits.',
                'additional_link_usage_limited' => ':usages / :limit uses',
                'additional_link_usage_total' => ':usages uses',
                'additional_link_expires' => 'Expires :time',
                'additional_link_never_expires' => 'Never expires',
                'additional_links_empty' => 'No extra links yet. Create one for a limited campaign, a temporary invite, or a private onboarding flow.',
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'Group Permissions',
                ],
                'send_via_chat' => [
                    'label' => 'Send Link Via Chat',
                ],
                'copy_link' => [
                    'label' => 'Copy Link',
                ],
                'reset_link' => [
                    'label' => 'Reset Link',
                ],
                'create_new_link' => [
                    'label' => 'Create New Link',
                ],
                'load_more' => [
                    'label' => 'Load More',
                ],
            ],
            'messages' => [
                'copied_success' => 'Invite link copied.',
                'copy_prompt' => 'Copy this link',
                'reset_success' => 'Group invite link reset.',
            ],
            'create' => [
                'heading' => [
                    'label' => 'New invite link',
                ],
                'inputs' => [
                    'name' => [
                        'placeholder' => 'Link name (optional)',
                        'helper_text' => 'Only admins can see this name.',
                    ],
                ],
                'sections' => [
                    'expiry' => [
                        'label' => 'Link availability',
                    ],
                    'usage' => [
                        'label' => 'Join limit',
                    ],
                ],
                'options' => [
                    'expiry' => [
                        '1_hour' => '1 hour',
                        '1_day' => '1 day',
                        '1_week' => '1 week',
                        'never' => 'Never',
                    ],
                    'usage' => [
                        'unlimited' => 'Unlimited',
                    ],
                ],
                'labels' => [
                    'approval_notice' => "Approval still follows your group's access settings. Public groups let people join right away, while private or approval-only groups create join requests.",
                ],
                'actions' => [
                    'create' => [
                        'label' => 'Create link',
                    ],
                ],
                'messages' => [
                    'created_success' => 'Invite link created.',
                ],
            ],
            'show' => [
                'heading' => [
                    'label' => 'Invite link',
                ],
                'labels' => [
                    'link' => 'Link',
                    'created_by' => 'Link created by',
                    'unknown' => 'Unknown',
                    'uses' => 'Uses',
                    'limit' => 'Limit',
                    'unlimited' => 'Unlimited',
                    'expires' => 'Expires',
                    'never' => 'Never',
                ],
                'actions' => [
                    'copy_link' => [
                        'label' => 'Copy link',
                    ],
                    'share_link' => [
                        'label' => 'Share link',
                    ],
                    'revoke' => [
                        'label' => 'Revoke link',
                    ],
                ],
                'messages' => [
                    'copied_success' => 'Invite link copied.',
                    'copy_prompt' => 'Copy this link',
                    'revoke_confirmation' => 'Are you sure you want to revoke this invite link?',
                    'revoked_success' => 'Invite link revoked.',
                ],
            ],
            'send_via_chat' => [
                'heading' => [
                    'label' => 'Send Invite Link',
                ],
                'inputs' => [
                    'search' => [
                        'placeholder' => 'Search users',
                    ],
                ],
                'actions' => [
                    'send' => [
                        'label' => 'Send',
                    ],
                ],
                'messages' => [
                    'invite_message' => 'Join :group via this invite link: :url',
                    'unavailable_left' => ':member left this group and must open the invite link personally to rejoin.',
                    'unavailable_removed' => ':member was removed from this group and cannot receive a group invite link.',
                    'unavailable_blocked' => ':member is blocked from this group and cannot receive a group invite link.',
                    'sent_success' => 'Invite link sent to :count chats.',
                ],
            ],
            'page' => [
                'labels' => [
                    'invited_to_group' => 'You were invited to join a group',
                    'group_fallback' => 'Group',
                    'invite_title' => 'Group Chat Invite',
                    'members_count' => 'Members :count',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Cancel',
                    ],
                    'continue' => [
                        'label' => 'Continue',
                    ],
                    'join_group' => [
                        'label' => 'Join Group',
                    ],
                    'request_to_join' => [
                        'label' => 'Request To Join',
                    ],
                ],
                'messages' => [
                    'invited_to_join_at' => "You've been invited to join this group on :app.",
                    'join_directly' => 'You can join this group immediately from this invite link.',
                    'request_required' => 'Admins must approve new members before they can join this group.',
                    'request_pending' => 'Your join request is pending admin approval.',
                    'request_submitted' => 'Your join request has been sent to the group admins.',
                    'join_blocked' => 'You cannot join this group with this invite link right now.',
                ],
            ],
        ],
        'join' => [
            'requests' => [
                'heading' => [
                    'label' => 'Join Requests',
                ],
                'labels' => [
                    'description' => 'Review and handle everyone who asked to join this group through an invite link.',
                    'unknown_user' => 'Unknown user',
                    'requested_at' => 'Requested :time',
                    'via_invite_link' => 'via invite link',
                    'empty_state' => 'There are no pending join requests right now.',
                    'count' => '{1} :count Join Request|[2,*] :count Join Requests',
                ],
                'actions' => [
                    'approve' => [
                        'label' => 'Add To Group',
                    ],
                    'dismiss' => [
                        'label' => 'Dismiss',
                    ],
                ],
                'messages' => [
                    'approved_success' => 'Join request approved.',
                    'dismissed_success' => 'Join request dismissed.',
                ],
            ],
            'lobby' => [
                'heading' => [
                    'label' => 'Join Group',
                ],
                'labels' => [
                    'default_group_name' => 'Group',
                    'members_count' => '{1} :count member|[2,*] :count members',
                    'more_members' => '{1} :count more member|[2,*] :count more members',
                    'already_member' => 'You are already a member of this group.',
                    'join_blocked' => 'You can’t join this group with this invite right now.',
                    'pending_review' => 'Your join request is already waiting for admin review.',
                    'requires_approval' => 'New members need admin approval before they can join this group.',
                    'open_access' => 'You can join this group right away.',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Cancel',
                    ],
                    'open_group' => [
                        'label' => 'Open Group',
                    ],
                    'request_pending' => [
                        'label' => 'Request Pending',
                    ],
                    'request_to_join' => [
                        'label' => 'Request To Join',
                    ],
                    'join_group' => [
                        'label' => 'Join Group',
                    ],
                ],
                'messages' => [
                    'invite_inactive' => 'This invite link is no longer active.',
                    'join_blocked' => 'You can’t join this group with this invite right now.',
                    'pending_request' => 'Your join request is already pending.',
                    'request_sent' => 'Your join request has been sent to the admins.',
                ],
            ],
        ],
        'past_members' => [
            'heading' => [
                'label' => 'Past Members',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Search past members',
                ],
            ],
            'labels' => [
                'no_results' => 'No past members found',
                'reason_left' => 'Left the group',
                'reason_removed' => 'Removed by an admin',
                'reason_blocked' => 'Blocked by an admin',
                'at' => ':time',
            ],
        ],
        'blocked_members' => [
            'heading' => [
                'label' => 'Blocked Members',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Search blocked members',
                ],
            ],
            'labels' => [
                'no_results' => 'No blocked members found',
                'helper' => 'Blocked members cannot rejoin until the block is lifted.',
            ],
            'actions' => [
                'lift_block' => [
                    'label' => 'Lift Block',
                    'confirmation_message' => 'Are you sure you want to lift the block for :member ?.',
                ],
            ],
            'messages' => [
                'unblocked_success' => ':member can join again with a group invite link.',
            ],
        ],

    ],

];
