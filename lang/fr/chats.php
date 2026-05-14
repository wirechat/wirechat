<?php

return [
    /**-------------------------
     * Chats
     *------------------------*/
    'labels' => [
        'heading' => 'Discussions',
        'no_conversations_yet' => 'Aucune discussion pour le moment',
        'you' => 'Vous',
        'attachment' => 'Pièce jointe',
        'now' => 'Maintenant',
        'load_more' => 'Charger plus',
    ],

    'inputs' => [
        'search' => [
            'label' => 'Rechercher des discussions',
            'placeholder' => 'Rechercher',
        ],
    ],

    'requests' => [
        'heading' => 'Demandes de message',
        'actions' => [
            'open' => [
                'label' => 'Demandes',
            ],
            'close' => [
                'label' => 'Fermer les demandes',
            ],
        ],
        'labels' => [
            'description' => 'Consultez les demandes reçues et suivez celles que vous avez envoyées.',
            'incoming' => 'Reçues',
            'outgoing' => 'Envoyées',
            'pending' => 'En attente',
            'no_message' => 'Aucun message pour le moment',
            'empty_state' => 'Vous n’avez aucune demande de message active pour le moment.',
            'incoming_empty_state' => 'Vous n’avez aucune demande de message reçue pour le moment.',
            'outgoing_empty_state' => 'Vous n’avez envoyé aucune demande de message active pour le moment.',
        ],
    ],

    'settings' => [
        'heading' => 'Paramètres',
        'actions' => [
            'open' => [
                'label' => 'Paramètres',
            ],
            'close' => [
                'label' => 'Fermer les paramètres',
            ],
            'back' => [
                'label' => 'Retour',
            ],
        ],
        'labels' => [
            'profile' => 'Profil',
        ],
        'options' => [
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Messages, groupes, sons',
            ],
        ],
        'notifications' => [
            'heading' => 'Notifications',
            'options' => [
                'messages' => [
                    'label' => 'Messages',
                    'description' => 'Recevoir une notification pour les nouveaux messages directs.',
                ],
                'groups' => [
                    'label' => 'Groupes',
                    'description' => 'Recevoir une notification pour les nouvelles activités de groupe.',
                ],
                'previews' => [
                    'label' => 'Afficher les aperçus',
                    'description' => 'Afficher le texte du message dans les notifications.',
                ],
                'sounds' => [
                    'label' => 'Sons',
                    'description' => 'Jouer un son pour les messages entrants.',
                ],
            ],
        ],
    ],

    'actions' => [
        'new_group' => [
            'label' => 'Nouveau Groupe',
        ],
    ], ];
