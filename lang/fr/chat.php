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
                'block_member' => [
                    'label' => 'Bloquer le membre',
                    'confirmation_message' => 'Voulez-vous vraiment bloquer :member de ce groupe?',
                ],
                'past_members' => [
                    'label' => 'Anciens membres',
                ],
                'blocked_members' => [
                    'label' => 'Membres bloqués',
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
                'invite_via_link' => [
                    'label' => 'Inviter dans le groupe via un lien',
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
                'admins_can' => 'Les administrateurs peuvent',
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
                'label' => "Liens d'invitation",
            ],
            'labels' => [
                'description' => "Toute personne disposant d'un compte pourra ouvrir l'un de ces liens et rejoindre votre groupe selon vos paramètres d'accès.",
                'primary_link' => 'Lien principal',
                'admin_approval_enabled' => 'Les membres doivent être approuvés par les administrateurs pour rejoindre ce groupe.',
                'admin_approval_disabled' => "Les membres n'ont pas besoin de l'approbation des administrateurs pour rejoindre ce groupe.",
                'primary_link_usage_empty' => "Personne n'a encore rejoint",
                'primary_link_usage_limited' => ':usages / :limit utilisations',
                'primary_link_usage_total' => ':usages adhésions jusqu’à présent',
                'group_access' => 'Accès au groupe',
                'group_access_requires_approval' => "Les personnes qui ouvrent ces liens devront obtenir l'approbation d'un administrateur avant de rejoindre le groupe.",
                'group_access_open' => 'Les personnes qui ouvrent ces liens peuvent rejoindre immédiatement le groupe.',
                'join_requests' => "Demandes d'adhésion",
                'join_requests_helper' => 'Examinez les personnes qui ont demandé à rejoindre ce groupe.',
                'additional_links' => 'Liens supplémentaires',
                'additional_links_helper' => "Créez des liens d'invitation supplémentaires avec leur propre expiration et leurs propres limites d'utilisation.",
                'additional_link_usage_limited' => ':usages / :limit utilisations',
                'additional_link_usage_total' => ':usages utilisations',
                'additional_link_expires' => 'Expire :time',
                'additional_link_never_expires' => "N'expire jamais",
                'additional_links_empty' => "Aucun lien supplémentaire pour le moment. Créez-en un pour une campagne limitée, une invitation temporaire ou un parcours d'intégration privé.",
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'Permissions du groupe',
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
                'create_new_link' => [
                    'label' => 'Créer un nouveau lien',
                ],
                'load_more' => [
                    'label' => 'Charger plus',
                ],
            ],
            'messages' => [
                'copied_success' => "Lien d'invitation copié.",
                'copy_prompt' => 'Copier ce lien',
                'reset_success' => "Lien d'invitation du groupe réinitialisé.",
            ],
            'create' => [
                'heading' => [
                    'label' => "Nouveau lien d'invitation",
                ],
                'inputs' => [
                    'name' => [
                        'placeholder' => 'Nom du lien (facultatif)',
                        'helper_text' => 'Seuls les administrateurs verront ce nom.',
                    ],
                ],
                'sections' => [
                    'expiry' => [
                        'label' => 'Durée de validité du lien',
                    ],
                    'usage' => [
                        'label' => 'Limite de participants',
                    ],
                ],
                'options' => [
                    'expiry' => [
                        '1_hour' => '1 heure',
                        '1_day' => '1 jour',
                        '1_week' => '1 semaine',
                        'never' => 'Sans expiration',
                    ],
                    'usage' => [
                        'unlimited' => 'Illimité',
                    ],
                ],
                'labels' => [
                    'approval_notice' => "L'approbation suit toujours les paramètres d'accès du groupe. Dans un groupe public, les personnes peuvent rejoindre immédiatement le groupe ; dans un groupe privé ou avec validation, une demande d'adhésion est créée.",
                ],
                'actions' => [
                    'create' => [
                        'label' => 'Créer le lien',
                    ],
                ],
                'messages' => [
                    'created_success' => "Lien d'invitation créé.",
                ],
            ],
            'show' => [
                'heading' => [
                    'label' => "Lien d'invitation",
                ],
                'labels' => [
                    'link' => 'Lien',
                    'created_by' => 'Lien créé par',
                    'unknown' => 'Inconnu',
                    'uses' => 'Utilisations',
                    'limit' => 'Limite',
                    'unlimited' => 'Illimité',
                    'expires' => 'Expiration',
                    'never' => 'Jamais',
                ],
                'actions' => [
                    'copy_link' => [
                        'label' => 'Copier le lien',
                    ],
                    'share_link' => [
                        'label' => 'Partager le lien',
                    ],
                    'revoke' => [
                        'label' => 'Révoquer le lien',
                    ],
                ],
                'messages' => [
                    'copied_success' => "Lien d'invitation copié.",
                    'copy_prompt' => 'Copier ce lien',
                    'revoke_confirmation' => "Voulez-vous vraiment révoquer ce lien d'invitation ?",
                    'revoked_success' => "Lien d'invitation révoqué.",
                ],
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
                    'unavailable_left' => ':member a quitté ce groupe et doit ouvrir lui-même le lien d\'invitation pour revenir.',
                    'unavailable_removed' => ':member a été retiré de ce groupe et ne peut pas recevoir de lien d\'invitation de groupe.',
                    'unavailable_blocked' => ':member est bloqué pour ce groupe et ne peut pas recevoir de lien d\'invitation de groupe.',
                    'sent_success' => "Lien d'invitation envoyé à :count discussions.",
                ],
            ],
            'page' => [
                'labels' => [
                    'invited_to_group' => 'Vous avez été invité à rejoindre un groupe',
                    'group_fallback' => 'Groupe',
                    'invite_title' => 'Invitation au chat de groupe',
                    'members_count' => 'Membres :count',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Annuler',
                    ],
                    'continue' => [
                        'label' => 'Continuer',
                    ],
                    'join_group' => [
                        'label' => 'Rejoindre le groupe',
                    ],
                    'request_to_join' => [
                        'label' => 'Demander à rejoindre',
                    ],
                ],
                'messages' => [
                    'invited_to_join_at' => 'Vous avez été invité à rejoindre ce groupe sur :app.',
                    'join_directly' => "Vous pouvez rejoindre ce groupe immédiatement via ce lien d'invitation.",
                    'request_required' => "Les administrateurs doivent approuver les nouveaux membres avant qu'ils puissent rejoindre ce groupe.",
                    'request_pending' => "Votre demande d'adhésion est en attente de l'approbation d'un administrateur.",
                    'request_submitted' => "Votre demande d'adhésion a été envoyée aux administrateurs du groupe.",
                    'join_blocked' => "Vous ne pouvez pas rejoindre ce groupe avec ce lien d'invitation pour le moment.",
                ],
            ],
        ],
        'join' => [
            'requests' => [
                'heading' => [
                    'label' => "Demandes d'adhésion",
                ],
                'labels' => [
                    'description' => "Examinez et gérez toutes les personnes qui ont demandé à rejoindre ce groupe via un lien d'invitation.",
                    'unknown_user' => 'Utilisateur inconnu',
                    'requested_at' => 'Demandée :time',
                    'via_invite_link' => "via le lien d'invitation",
                    'empty_state' => "Il n'y a aucune demande d'adhésion en attente pour le moment.",
                    'count' => '{1} :count demande d’adhésion|[2,*] :count demandes d’adhésion',
                ],
                'actions' => [
                    'approve' => [
                        'label' => 'Ajouter au groupe',
                    ],
                    'approve_all' => [
                        'label' => 'Tout accepter',
                        'confirmation_message' => 'Voulez-vous vraiment accepter toutes les demandes d’adhésion en attente ?',
                    ],
                    'dismiss' => [
                        'label' => 'Ignorer',
                    ],
                    'dismiss_all' => [
                        'label' => 'Tout refuser',
                        'confirmation_message' => 'Voulez-vous vraiment refuser toutes les demandes d’adhésion en attente ?',
                    ],
                    'load_more' => [
                        'label' => 'Charger plus',
                    ],
                ],
                'messages' => [
                    'approved_success' => "Demande d'adhésion approuvée.",
                    'approved_all_success' => '{1} :count demande d’adhésion approuvée.|[2,*] :count demandes d’adhésion approuvées.',
                    'dismissed_success' => "Demande d'adhésion ignorée.",
                    'dismissed_all_success' => '{1} :count demande d’adhésion refusée.|[2,*] :count demandes d’adhésion refusées.',
                ],
            ],
            'lobby' => [
                'heading' => [
                    'label' => 'Rejoindre le groupe',
                ],
                'labels' => [
                    'default_group_name' => 'Groupe',
                    'members_count' => '{1} :count membre|[2,*] :count membres',
                    'more_members' => '{1} :count membre supplémentaire|[2,*] :count membres supplémentaires',
                    'already_member' => 'Vous faites déjà partie de ce groupe.',
                    'join_blocked' => 'Vous ne pouvez pas rejoindre ce groupe avec ce lien d’invitation pour le moment.',
                    'pending_review' => 'Votre demande est déjà en attente de validation par un administrateur.',
                    'requires_approval' => 'Les nouveaux membres doivent être approuvés par un administrateur avant de rejoindre ce groupe.',
                    'open_access' => 'Vous pouvez rejoindre ce groupe immédiatement.',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Annuler',
                    ],
                    'open_group' => [
                        'label' => 'Ouvrir le groupe',
                    ],
                    'request_pending' => [
                        'label' => 'Demande en attente',
                    ],
                    'request_to_join' => [
                        'label' => 'Demander à rejoindre',
                    ],
                    'join_group' => [
                        'label' => 'Rejoindre le groupe',
                    ],
                ],
                'messages' => [
                    'invite_inactive' => 'Ce lien d’invitation n’est plus actif.',
                    'join_blocked' => 'Vous ne pouvez pas rejoindre ce groupe avec ce lien d’invitation pour le moment.',
                    'pending_request' => 'Votre demande est déjà en attente.',
                    'request_sent' => 'Votre demande a été envoyée aux administrateurs.',
                ],
            ],
        ],
        'past_members' => [
            'heading' => [
                'label' => 'Anciens membres',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Rechercher les anciens membres',
                ],
            ],
            'labels' => [
                'no_results' => 'Aucun ancien membre trouvé',
                'reason_left' => 'A quitté le groupe',
                'reason_removed' => 'Retiré par un administrateur',
                'reason_blocked' => 'Bloqué par un administrateur',
                'at' => ':time',
            ],
        ],
        'blocked_members' => [
            'heading' => [
                'label' => 'Membres bloqués',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Rechercher les membres bloqués',
                ],
            ],
            'labels' => [
                'no_results' => 'Aucun membre bloqué trouvé',
                'helper' => 'Les membres bloqués ne peuvent pas revenir tant que le blocage n\'est pas levé.',
            ],
            'actions' => [
                'lift_block' => [
                    'label' => 'Lever le blocage',
                    'confirmation_message' => 'Voulez-vous vraiment lever le blocage pour :member?',
                ],
            ],
            'messages' => [
                'unblocked_success' => ':member peut à nouveau rejoindre avec un lien d\'invitation de groupe.',
            ],
        ],
    ],
];
