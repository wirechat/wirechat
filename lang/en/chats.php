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
        'options' => [
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Messages, groups, previews',
            ],
        ],
        'notifications' => [
            'heading' => 'Notifications',
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
    ],

    'actions' => [
        'new_group' => [
            'label' => 'New Group',
        ],
    ],
];
