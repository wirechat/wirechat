<?php

return [

    /**-------------------------
     * Chats
     *------------------------*/
    'labels' => [
        'heading' => 'Chats',
        'no_conversations_yet' => 'Noch keine Konversationen',
        'you' => 'Du',
        'attachment' => 'Anhang',
        'now' => 'Jetzt',
        'load_more' => 'Mehr laden',

    ],

    'inputs' => [
        'search' => [
            'label' => 'Konversationen suchen',
            'placeholder' => 'Suchen',
        ],
    ],

    'requests' => [
        'heading' => 'Nachrichtenanfragen',
        'actions' => [
            'open' => [
                'label' => 'Anfragen',
            ],
            'close' => [
                'label' => 'Anfragen schliessen',
            ],
        ],
        'labels' => [
            'description' => 'Prüfe eingehende Anfragen und behalte gesendete Anfragen im Blick.',
            'incoming' => 'Eingehend',
            'outgoing' => 'Gesendet',
            'pending' => 'Ausstehend',
            'no_message' => 'Noch keine Nachricht',
            'empty_state' => 'Du hast aktuell keine aktiven Nachrichtenanfragen.',
            'incoming_empty_state' => 'Du hast aktuell keine eingehenden Nachrichtenanfragen.',
            'outgoing_empty_state' => 'Du hast aktuell keine aktiven gesendeten Nachrichtenanfragen.',
        ],
    ],

    'settings' => [
        'heading' => 'Einstellungen',
        'actions' => [
            'open' => [
                'label' => 'Einstellungen',
            ],
            'close' => [
                'label' => 'Einstellungen schließen',
            ],
            'back' => [
                'label' => 'Zurück',
            ],
        ],
        'labels' => [
            'profile' => 'Profil',
        ],
        'options' => [
            'notifications' => [
                'label' => 'Benachrichtigungen',
                'description' => 'Nachrichten, Gruppen, Töne',
            ],
        ],
        'notifications' => [
            'heading' => 'Benachrichtigungen',
            'options' => [
                'messages' => [
                    'label' => 'Nachrichten',
                    'description' => 'Benachrichtigen, wenn neue Direktnachrichten eingehen.',
                ],
                'groups' => [
                    'label' => 'Gruppen',
                    'description' => 'Benachrichtigen, wenn Gruppen neue Aktivitäten haben.',
                ],
                'previews' => [
                    'label' => 'Vorschauen anzeigen',
                    'description' => 'Nachrichtentext in Benachrichtigungen anzeigen.',
                ],
                'sounds' => [
                    'label' => 'Töne',
                    'description' => 'Ton bei eingehenden Nachrichten abspielen.',
                ],
            ],
        ],
    ],

    'actions' => [
        'new_group' => [
            'label' => 'Neue Gruppe',
        ],
    ],
];
