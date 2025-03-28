<?php

return [

    // new-chat component
    'chat' => [
        'labels' => [
            'heading' => ' New Chat',
            'you' => 'You',

        ],

        'inputs' => [
            'search' => [
                'label' => 'Search Conversations',
                'placeholder' => 'Search',
            ],
        ],

        'actions' => [
            'new_group' => [
                'label' => 'New group',
            ],

        ],

        'messages' => [

            'empty_search_result' => 'No users found matching your search.',
        ],
    ],

    // new-group component
    'group' => [
        'labels' => [
            'heading' => ' New Chat',
            'add_members' => ' Add Members',

        ],

        'inputs' => [
            'name' => [
                'label' => 'Group Name',
                'placeholder' => 'Enter Name',
            ],
            'description' => [
                'label' => 'Description',
                'placeholder' => 'Optional',
            ],
            'search' => [
                'label' => 'Search',
                'placeholder' => 'Search',
            ],
            'photo' => [
                'label' => 'Photo',
            ],
        ],

        'actions' => [
            'cancel' => [
                'label' => 'Cancel',
            ],
            'next' => [
                'label' => 'Next',
            ],
            'create' => [
                'label' => 'Create',
            ],

        ],

        'messages' => [
            'members_limit_error' => 'Members cannot exceed  :count',
            'empty_search_result' => 'No users found matching your search.',
        ],
    ],

];
