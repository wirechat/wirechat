<?php

return [

    /**-------------------------
     * Chats
     *------------------------*/
    'labels' => [
        'heading' => 'Chats',
        'no_conversations_yet' => 'No conversations yet. Start a new chat when you are ready.',
        'you' => 'You',
        'attachment' => 'Attachment',
        'now' => 'Now',
        'load_more' => 'Load more',

    ],

    'inputs' => [
        'search' => [
            'label' => 'Search Conversations',
            'placeholder' => 'Search',
        ],
    ],

    'requests' => [
        'heading' => 'Message Requests',
        'actions' => [
            'open' => [
                'label' => 'Requests',
            ],
            'close' => [
                'label' => 'Close requests',
            ],
        ],
        'labels' => [
            'description' => 'Review incoming requests and keep track of requests you sent.',
            'incoming' => 'Incoming',
            'outgoing' => 'Sent',
            'pending' => 'Pending',
            'no_message' => 'No message yet',
            'empty_state' => 'You have no active message requests right now.',
            'incoming_empty_state' => 'You have no incoming message requests right now.',
            'outgoing_empty_state' => 'You have not sent any active message requests right now.',
        ],
    ],

    'settings' => [
        'heading' => 'Settings',
        'actions' => [
            'open' => [
                'label' => 'Settings',
            ],
            'close' => [
                'label' => 'Close settings',
            ],
            'back' => [
                'label' => 'Back',
            ],
        ],
        'labels' => [
            'profile' => 'Profile',
        ],
        'general' => [
            'heading' => 'General',
            'profile' => [
                'description' => 'Your profile in :app.',
            ],
        ],
        'options' => [
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Messages, groups, previews',
            ],
            'security_privacy' => [
                'label' => 'Security & Privacy',
                'description' => 'Groups',
            ],
        ],
        'notifications' => [
            'heading' => 'Notifications',
            'description' => 'Choose how :app should notify you about new activity.',
            'options' => [
                'messages' => [
                    'label' => 'Messages',
                    'description' => 'Notify when new direct messages arrive.',
                ],
                'groups' => [
                    'label' => 'Groups',
                    'description' => 'Notify when groups have new activity.',
                ],
                'previews' => [
                    'label' => 'Show previews',
                    'description' => 'Preview message text inside notifications.',
                ],
            ],
            'preview_disabled' => [
                'private_title' => 'New message',
                'private_body' => ':sender sent you a message',
                'group_body' => ':sender sent a message',
            ],
        ],
        'security_privacy' => [
            'heading' => 'Security & Privacy',
            'description' => 'Manage privacy controls for how other users can interact with you.',
            'options' => [
                'groups' => [
                    'label' => 'Groups',
                    'description' => 'Control who can add you to groups.',
                ],
            ],
            'groups' => [
                'heading' => 'Groups',
                'options' => [
                    'add_me' => [
                        'label' => 'Allow others to add me to groups',
                        'description' => 'When this is off, people cannot find you in group add searches or add you to groups in :app.',
                    ],
                ],
            ],
        ],
    ],

    'actions' => [
        'new_group' => [
            'label' => 'New Group',
        ],
    ],
];
