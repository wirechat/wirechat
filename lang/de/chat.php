<?php

return [

    /**-------------------------
     * Chat
     *------------------------*/
    'labels' => [

        'you_replied_to_yourself' => 'Du hast dir selbst geantwortet',
        'participant_replied_to_you' => ':Sender hat dir geantwortet',
        'participant_replied_to_themself' => ':Sender hat sich selbst geantwortet',
        'participant_replied_other_participant' => ':Sender hat an :Receiver geantwortet',
        'you' => 'Du',
        'user' => 'Benutzer',
        'replying_to' => 'Antwort an :Participant',
        'replying_to_yourself' => 'Antwort an dich selbst',
        'attachment' => 'Anhang',
    ],

    'inputs' => [
        'message' => [
            'label' => 'Nachricht',
            'placeholder' => 'Nachricht eingeben',
        ],
        'media' => [
            'label' => 'Medien',
            'placeholder' => 'Medien',
        ],
        'files' => [
            'label' => 'Dateien',
            'placeholder' => 'Dateien',
        ],
    ],

    'message_groups' => [
        'today' => 'Heute',
        'yesterday' => 'Gestern',

    ],

    'actions' => [
        'open_group_info' => [
            'label' => 'Gruppeninfo',
        ],
        'open_chat_info' => [
            'label' => 'Chatinfo',
        ],
        'close_chat' => [
            'label' => 'Chat schließen',
        ],
        'clear_chat' => [
            'label' => 'Chatverlauf löschen',
            'confirmation_message' => 'Möchten Sie Ihren Chatverlauf wirklich löschen? Dies löscht nur Ihren Chat und hat keine Auswirkungen auf andere Teilnehmer.',
        ],
        'delete_chat' => [
            'label' => 'Chat löschen',
            'confirmation_message' => 'Möchten Sie diesen Chat wirklich löschen? Dadurch wird der Chat nur von Ihnen entfernt, nicht aber für andere Teilnehmer.',
        ],

        'delete_for_everyone' => [
            'label' => 'Für alle löschen',
            'confirmation_message' => 'Sind Sie sicher?',
        ],
        'delete_for_me' => [
            'label' => 'Für mich löschen',
            'confirmation_message' => 'Sind Sie sicher?',
        ],
        'reply' => [
            'label' => 'Antworten',
        ],

        'exit_group' => [
            'label' => 'Gruppe verlassen',
            'confirmation_message' => 'Möchten Sie diese Gruppe wirklich verlassen?',
        ],
        'upload_file' => [
            'label' => 'Datei',
        ],
        'upload_media' => [
            'label' => 'Fotos & Videos',
        ],
    ],

    'messages' => [

        'cannot_exit_self_or_private_conversation' => 'Selbst- oder private Konversation kann nicht beendet werden.',
        'owner_cannot_exit_conversation' => 'Der Besitzer kann die Konversation nicht beenden.',
        'rate_limit' => 'Zu viele Versuche! Bitte langsamer werden.',
        'conversation_not_found' => 'Konversation nicht gefunden.',
        'conversation_id_required' => 'Eine Konversations-ID ist erforderlich.',
        'invalid_conversation_input' => 'Ungültige Konversationseingabe.',
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
                'label' => 'Chat löschen',
                'confirmation_message' => 'Möchten Sie diesen Chat wirklich löschen? Dadurch wird der Chat nur von Ihrer Seite entfernt und nicht für andere Teilnehmer gelöscht.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'Nur private und persönliche Gespräche sind erlaubt',
        ],

    ],

    /**-------------------------
     * Group Folder
     *------------------------*/

    'group' => [

        // Gruppeninfo-Komponente
        'info' => [
            'heading' => [
                'label' => 'Gruppeninfo',
            ],
            'labels' => [
                'members' => 'Mitglieder',
                'add_description' => 'Gruppenbeschreibung hinzufügen',
            ],
            'inputs' => [
                'name' => [
                    'label' => 'Gruppenname',
                    'placeholder' => 'Name eingeben',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'placeholder' => 'Optional',
                ],
                'photo' => [
                    'label' => 'Foto',
                ],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'Gruppe löschen',
                    'confirmation_message' => 'Möchten Sie diese Gruppe wirklich löschen?',
                    'helper_text' => 'Bevor Sie die Gruppe löschen können, müssen Sie Alle Gruppenmitglieder entfernen.',
                ],
                'add_members' => [
                    'label' => 'Mitglieder hinzufügen',
                ],
                'group_permissions' => [
                    'label' => 'Gruppenberechtigungen',
                ],
                'invite_via_link' => [
                    'label' => 'Per Gruppenlink einladen',
                ],
                'exit_group' => [
                    'label' => 'Gruppe verlassen',
                    'confirmation_message' => 'Möchten Sie die Gruppe wirklich verlassen?',

                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Nur Gruppenkonversationen erlaubt',
            ],
        ],
        // Members component
        'members' => [
            'heading' => [
                'label' => 'Mitglieder',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Suchen',
                    'placeholder' => 'Mitglieder suchen',
                ],
            ],
            'labels' => [
                'members' => 'Mitglieder',
                'owner' => 'Eigentümer',
                'admin' => 'Administrator',
                'no_members_found' => 'Keine Mitglieder gefunden',
            ],
            'actions' => [
                'send_message_to_yourself' => [
                    'label' => 'Sich selbst eine Nachricht senden',

                ],
                'send_message_to_member' => [
                    'label' => 'Nachricht an :member',

                ],
                'dismiss_admin' => [
                    'label' => 'Als Administrator entlassen',
                    'confirmation_message' => 'Möchten Sie :member wirklich als Administrator entlassen?',
                ],
                'make_admin' => [
                    'label' => 'Zum Administrator machen',
                    'confirmation_message' => 'Möchten Sie :member wirklich zum Administrator machen?',
                ],
                'remove_from_group' => [
                    'label' => 'Entfernen',
                    'confirmation_message' => 'Möchten Sie :member wirklich aus dieser Gruppe entfernen?',
                ],
                'load_more' => [
                    'label' => 'Mehr laden',
                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Nur Gruppenkonversationen erlaubt',
            ],
        ],
        // add-Members component
        'add_members' => [
            'heading' => [
                'label' => 'Mitglieder hinzufügen',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Suchen',
                    'placeholder' => 'Suchen',
                ],
            ],
            'labels' => [

            ],
            'actions' => [
                'save' => [
                    'label' => 'Speichern',

                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Nur Gruppenkonversationen erlaubt',
                'members_limit_error' => 'Mitgliederzahl darf :count nicht überschreiten',
                'member_already_exists' => 'Bereits zur Gruppe hinzugefügt',
            ],
        ],
        // permissions component
        'permissions' => [
            'heading' => [
                'label' => 'Berechtigungen',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Suchen',
                    'placeholder' => 'Suchen',
                ],
            ],
            'labels' => [
                'members_can' => 'Mitglieder können',

            ],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'Gruppeninformationen bearbeiten',
                    'helper_text' => 'Dies umfasst Name, Symbol und Beschreibung',
                ],
                'send_messages' => [
                    'label' => 'Nachrichten senden',
                ],
                'add_other_members' => [
                    'label' => 'Weitere Mitglieder hinzufügen',
                ],
                'admin_approval' => [
                    'label' => 'Neue Mitglieder bestätigen',
                    'helper_text' => 'Administratoren müssen Personen bestätigen, die über einen Einladungslink beitreten',
                ],

            ],
            'messages' => [
            ],
        ],
        'invite_link' => [
            'heading' => [
                'label' => 'Per Link zur Gruppe einladen',
            ],
            'labels' => [
                'admin_approval_enabled' => 'Mitglieder benötigen die Bestätigung eines Administrators, um dieser Gruppe beizutreten.',
                'admin_approval_disabled' => 'Mitglieder benötigen keine Administrator-Bestätigung, um dieser Gruppe beizutreten.',
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'In Gruppenberechtigungen bearbeiten',
                ],
                'send_via_chat' => [
                    'label' => 'Link per Chat senden',
                ],
                'copy_link' => [
                    'label' => 'Link kopieren',
                ],
                'reset_link' => [
                    'label' => 'Link zurücksetzen',
                ],
            ],
            'messages' => [
                'copied_success' => 'Einladungslink kopiert.',
                'reset_success' => 'Gruppen-Einladungslink zurückgesetzt.',
            ],
            'send_via_chat' => [
                'heading' => [
                    'label' => 'Einladungslink senden',
                ],
                'inputs' => [
                    'search' => [
                        'placeholder' => 'Benutzer suchen',
                    ],
                ],
                'actions' => [
                    'send' => [
                        'label' => 'Senden',
                    ],
                ],
                'messages' => [
                    'invite_message' => 'Tritt :group über diesen Einladungslink bei: :url',
                    'sent_success' => 'Einladungslink an :count Chats gesendet.',
                ],
            ],
            'page' => [
                'labels' => [
                    'invited_to_group' => 'Du wurdest eingeladen, einer Gruppe beizutreten',
                    'group_fallback' => 'Gruppe',
                    'members_count' => 'Mitglieder :count',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Abbrechen',
                    ],
                    'join_group' => [
                        'label' => 'Gruppe beitreten',
                    ],
                    'request_to_join' => [
                        'label' => 'Beitritt anfragen',
                    ],
                ],
                'messages' => [
                    'join_directly' => 'Du kannst dieser Gruppe sofort über diesen Einladungslink beitreten.',
                    'request_required' => 'Administratoren müssen neue Mitglieder bestätigen, bevor sie dieser Gruppe beitreten können.',
                    'request_pending' => 'Deine Beitrittsanfrage wartet auf die Bestätigung eines Administrators.',
                    'request_submitted' => 'Deine Beitrittsanfrage wurde an die Gruppenadministratoren gesendet.',
                    'join_blocked' => 'Du kannst dieser Gruppe mit diesem Einladungslink derzeit nicht beitreten.',
                ],
            ],
        ],

    ],

];
