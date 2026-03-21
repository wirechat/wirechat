<?php

return [
    /**-------------------------
     * Chat
     *------------------------*/
    'labels' => [
        'you_replied_to_yourself' => 'Vous vous êtes répondu',
        'participant_replied_to_you' => ':sender vous a répondu',
        'participant_replied_to_themself' => ':sender s\'est répondu à lui-même',
        'participant_replied_other_participant' => ':sender a répondu à :receiver',
        'you' => 'Vous',
        'user' => 'Utilisateur',
        'replying_to' => 'Répondre à :participant',
        'replying_to_yourself' => 'Vous répondre',
        'attachment' => 'Pièce jointe',
    ],

    'inputs' => [
        'message' => [
            'label' => 'Message',
            'placeholder' => 'Écrire un message',
        ],
        'media' => [
            'label' => 'Média',
            'placeholder' => 'Média',
        ],
        'files' => [
            'label' => 'Fichiers',
            'placeholder' => 'Fichiers',
        ],
    ],

    'message_groups' => [
        'today' => "Aujourd'hui",
        'yesterday' => 'Hier',
    ],

    'actions' => [
        'open_group_info' => [
            'label' => 'Infos du groupe',
        ],
        'open_chat_info' => [
            'label' => 'Infos du chat',
        ],
        'close_chat' => [
            'label' => 'Fermer le chat',
        ],
        'clear_chat' => [
            'label' => "Effacer l'historique du chat",
            'confirmation_message' => "Êtes-vous sûr de vouloir effacer votre historique de chat? Cela n'effacera que votre chat et n'affectera pas les autres participants.",
        ],
        'delete_chat' => [
            'label' => 'Supprimer le chat',
            'confirmation_message' => 'Êtes-vous sûr de vouloir supprimer ce chat? Cela ne supprimera le chat que de votre côté et ne le supprimera pas pour les autres participants.',
        ],
        'delete_for_everyone' => [
            'label' => 'Supprimer pour tout le monde',
            'confirmation_message' => 'Êtes-vous sûr?',
        ],
        'delete_for_me' => [
            'label' => 'Supprimer pour moi',
            'confirmation_message' => 'Êtes-vous sûr?',
        ],
        'reply' => [
            'label' => 'Répondre',
        ],
        'exit_group' => [
            'label' => 'Quitter le groupe',
            'confirmation_message' => 'Êtes-vous sûr de vouloir quitter ce groupe?',
        ],
        'upload_file' => [
            'label' => 'Fichier',
        ],
        'upload_media' => [
            'label' => 'Photos et vidéos',
        ],
    ],

    'messages' => [
        'cannot_exit_self_or_private_conversation' => 'Impossible de quitter une conversation avec soi-même ou une conversation privée',
        'owner_cannot_exit_conversation' => 'Le propriétaire ne peut pas quitter la conversation',
        'rate_limit' => 'Trop de tentatives! Veuillez ralentir',
        'conversation_not_found' => 'Conversation introuvable.',
        'conversation_id_required' => 'Un identifiant de conversation est requis',
        'invalid_conversation_input' => 'Entrée de conversation non valide.',
    ],

    /**-------------------------
     * Info Component
     *------------------------*/
    'info' => [
        'heading' => [
            'label' => 'Infos du chat',
        ],
        'actions' => [
            'delete_chat' => [
                'label' => 'Supprimer le chat',
                'confirmation_message' => 'Êtes-vous sûr de vouloir supprimer ce chat? Cela ne supprimera le chat que de votre côté et ne le supprimera pas pour les autres participants.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'Seules les conversations privées et avec soi-même sont autorisées',
        ],
    ],

    /**-------------------------
     * Group Folder
     *------------------------*/
    'group' => [
        // Group info component
        'info' => [
            'heading' => [
                'label' => 'Infos du groupe',
            ],
            'labels' => [
                'members' => 'Membres',
                'add_description' => 'Ajouter une description de groupe',
            ],
            'inputs' => [
                'name' => [
                    'label' => 'Nom du groupe',
                    'placeholder' => 'Entrer le nom',
                ],
                'description' => [
                    'label' => 'Description',
                    'placeholder' => 'Facultatif',
                ],
                'photo' => [
                    'label' => 'Photo',
                ],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'Supprimer le groupe',
                    'confirmation_message' => 'Êtes-vous sûr de vouloir supprimer ce groupe?',
                    'helper_text' => 'Avant de pouvoir supprimer le groupe, vous devez retirer tous les membres du groupe.',
                ],
                'add_members' => [
                    'label' => 'Ajouter des membres',
                ],
                'group_permissions' => [
                    'label' => 'Permissions du groupe',
                ],
                'invite_via_link' => [
                    'label' => 'Inviter via un lien du groupe',
                ],
                'exit_group' => [
                    'label' => 'Quitter le groupe',
                    'confirmation_message' => 'Êtes-vous sûr de vouloir quitter le groupe?',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Seules les conversations de groupe sont autorisées',
            ],
        ],
        // Members component
        'members' => [
            'heading' => [
                'label' => 'Membres',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Rechercher',
                    'placeholder' => 'Rechercher des membres',
                ],
            ],
            'labels' => [
                'members' => 'Membres',
                'owner' => 'Propriétaire',
                'admin' => 'Admin',
                'no_members_found' => 'Aucun membre trouvé',
            ],
            'actions' => [
                'send_message_to_yourself' => [
                    'label' => 'Vous envoyer un message',
                ],
                'send_message_to_member' => [
                    'label' => 'Envoyer un message à :member',
                ],
                'dismiss_admin' => [
                    'label' => "Révoquer le statut d'administrateur",
                    'confirmation_message' => "Êtes-vous sûr de vouloir révoquer le statut d'administrateur de :member?",
                ],
                'make_admin' => [
                    'label' => 'Nommer administrateur',
                    'confirmation_message' => 'Êtes-vous sûr de vouloir nommer :member administrateur?',
                ],
                'remove_from_group' => [
                    'label' => 'Retirer',
                    'confirmation_message' => 'Êtes-vous sûr de vouloir retirer :member de ce groupe?',
                ],
                'load_more' => [
                    'label' => 'Charger plus',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Seules les conversations de groupe sont autorisées',
            ],
        ],
        // add-Members component
        'add_members' => [
            'heading' => [
                'label' => 'Ajouter des membres',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Rechercher',
                    'placeholder' => 'Rechercher',
                ],
            ],
            'labels' => [],
            'actions' => [
                'save' => [
                    'label' => 'Enregistrer',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Seules les conversations de groupe sont autorisées',
                'members_limit_error' => 'Le nombre de membres ne peut pas dépasser :count',
                'member_already_exists' => 'Déjà ajouté au groupe',
            ],
        ],
        // permissions component
        'permissions' => [
            'heading' => [
                'label' => 'Permissions',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Rechercher',
                    'placeholder' => 'Rechercher',
                ],
            ],
            'labels' => [
                'members_can' => 'Les membres peuvent',
            ],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'Modifier les informations du groupe',
                    'helper_text' => "Cela inclut le nom, l'icône et la description",
                ],
                'send_messages' => [
                    'label' => 'Envoyer des messages',
                ],
                'add_other_members' => [
                    'label' => "Ajouter d'autres membres",
                ],
                'admin_approval' => [
                    'label' => 'Approuver les nouveaux membres',
                    'helper_text' => "Exiger l'approbation d'un administrateur pour les personnes qui rejoignent via un lien d'invitation",
                ],
            ],
            'messages' => [],
        ],
        'invite_link' => [
            'heading' => [
                'label' => 'Inviter au groupe via un lien',
            ],
            'labels' => [
                'admin_approval_enabled' => 'Les membres doivent être approuvés par les administrateurs pour rejoindre ce groupe.',
                'admin_approval_disabled' => "Les membres n'ont pas besoin de l'approbation des administrateurs pour rejoindre ce groupe.",
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'Modifier dans les permissions du groupe',
                ],
                'send_via_chat' => [
                    'label' => 'Envoyer le lien via le chat',
                ],
                'copy_link' => [
                    'label' => 'Copier le lien',
                ],
                'reset_link' => [
                    'label' => 'Réinitialiser le lien',
                ],
            ],
            'messages' => [
                'copied_success' => "Lien d'invitation copié.",
                'reset_success' => "Lien d'invitation du groupe réinitialisé.",
            ],
            'send_via_chat' => [
                'heading' => [
                    'label' => "Envoyer le lien d'invitation",
                ],
                'inputs' => [
                    'search' => [
                        'placeholder' => 'Rechercher des utilisateurs',
                    ],
                ],
                'actions' => [
                    'send' => [
                        'label' => 'Envoyer',
                    ],
                ],
                'messages' => [
                    'invite_message' => "Rejoignez :group via ce lien d'invitation : :url",
                    'sent_success' => "Lien d'invitation envoyé à :count discussions.",
                ],
            ],
            'page' => [
                'labels' => [
                    'invited_to_group' => 'Vous avez été invité à rejoindre un groupe',
                    'group_fallback' => 'Groupe',
                    'members_count' => 'Membres :count',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Annuler',
                    ],
                    'join_group' => [
                        'label' => 'Rejoindre le groupe',
                    ],
                    'request_to_join' => [
                        'label' => 'Demander à rejoindre',
                    ],
                ],
                'messages' => [
                    'join_directly' => "Vous pouvez rejoindre ce groupe immédiatement via ce lien d'invitation.",
                    'request_required' => "Les administrateurs doivent approuver les nouveaux membres avant qu'ils puissent rejoindre ce groupe.",
                    'request_pending' => "Votre demande d'adhésion est en attente de l'approbation d'un administrateur.",
                    'request_submitted' => "Votre demande d'adhésion a été envoyée aux administrateurs du groupe.",
                    'join_blocked' => "Vous ne pouvez pas rejoindre ce groupe avec ce lien d'invitation pour le moment.",
                ],
            ],
        ],
    ],
];
