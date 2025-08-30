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

            ],
            'messages' => [
            ],
        ],

    ],

];
