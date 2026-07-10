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

    'message_request' => [
        'labels' => [
            'heading' => 'Nachrichtenanfrage',
            'description' => 'Öffne die Konversation und akzeptiere sie, bevor du antworten kannst.',
            'outgoing_notice' => 'Diese Nachrichtenanfrage ist noch ausstehend. Der Empfänger kann den Verlauf prüfen, bevor er beitritt.',
        ],
        'actions' => [
            'accept' => [
                'label' => 'Akzeptieren',
            ],
            'dismiss' => [
                'label' => 'Ablehnen',
            ],
        ],
        'messages' => [
            'accepted' => 'Nachrichtenanfrage akzeptiert.',
            'dismissed' => 'Nachrichtenanfrage abgelehnt.',
            'accept_required' => 'Akzeptiere diese Nachrichtenanfrage, bevor du Nachrichten sendest.',
        ],
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

        'invite_message' => [
            'labels' => [
                'type' => 'Einladung zum Gruppenchat',
            ],
            'actions' => [
                'view_group' => [
                    'label' => 'Gruppe ansehen',
                ],
            ],
        ],

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
                    'label' => 'Per Link zur Gruppe einladen',
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
                'block_member' => [
                    'label' => 'Mitglied sperren',
                    'confirmation_message' => 'Möchten Sie :member wirklich für diese Gruppe sperren?',
                ],
                'ban_member' => [
                    'label' => 'Mitglied sperren',
                    'confirmation_message' => 'Möchten Sie :member wirklich für diese Gruppe sperren?',
                ],
                'past_members' => [
                    'label' => 'Ehemalige Mitglieder',
                ],
                'blocked_members' => [
                    'label' => 'Gesperrte Mitglieder',
                ],
                'banned_members' => [
                    'label' => 'Gesperrte Mitglieder',
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
                'invite_via_link' => [
                    'label' => 'Per Link zur Gruppe einladen',
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
                'admins_can' => 'Administratoren können',

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
                'invite_others_via_link' => [
                    'label' => 'Andere per Link einladen',
                    'helper_text' => 'Mitglieder dürfen den primären Gruppeneinladungslink kopieren und senden',
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
                'label' => 'Einladungslinks',
            ],
            'labels' => [
                'description' => 'Jede Person mit einem Konto kann einen dieser Links öffnen und deiner Gruppe entsprechend deiner Zugriffseinstellungen beitreten.',
                'primary_link' => 'Primärer Link',
                'admin_approval_enabled' => 'Mitglieder benötigen die Bestätigung eines Administrators, um dieser Gruppe beizutreten.',
                'admin_approval_disabled' => 'Mitglieder benötigen keine Administrator-Bestätigung, um dieser Gruppe beizutreten.',
                'primary_link_usage_empty' => 'Noch niemand ist beigetreten',
                'primary_link_usage_limited' => ':usages / :limit Nutzungen',
                'primary_link_usage_total' => ':usages Beitritte bisher',
                'group_access' => 'Gruppenzugriff',
                'group_access_requires_approval' => 'Personen, die diese Links öffnen, benötigen vor dem Beitritt die Bestätigung eines Administrators.',
                'group_access_open' => 'Personen, die diese Links öffnen, können sofort beitreten.',
                'join_requests' => 'Beitrittsanfragen',
                'join_requests_helper' => 'Überprüfe, wer den Beitritt zu dieser Gruppe angefragt hat.',
                'additional_links' => 'Zusätzliche Links',
                'additional_links_helper' => 'Erstelle zusätzliche Einladungslinks mit eigener Ablaufzeit und Nutzungslimits.',
                'additional_link_usage_limited' => ':usages / :limit Nutzungen',
                'additional_link_usage_total' => ':usages Nutzungen',
                'additional_link_expires' => 'Läuft :time ab',
                'additional_link_never_expires' => 'Läuft nie ab',
                'additional_links_empty' => 'Noch keine zusätzlichen Links. Erstelle einen für eine begrenzte Kampagne, eine temporäre Einladung oder einen privaten Onboarding-Ablauf.',
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'Gruppenberechtigungen',
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
                'create_new_link' => [
                    'label' => 'Neuen Link erstellen',
                ],
                'load_more' => [
                    'label' => 'Mehr laden',
                ],
            ],
            'messages' => [
                'copied_success' => 'Einladungslink kopiert.',
                'copy_prompt' => 'Diesen Link kopieren',
                'reset_success' => 'Gruppen-Einladungslink zurückgesetzt.',
            ],
            'create' => [
                'heading' => [
                    'label' => 'Neuer Einladungslink',
                ],
                'inputs' => [
                    'name' => [
                        'placeholder' => 'Linkname (optional)',
                        'helper_text' => 'Diesen Namen sehen nur Administratoren.',
                    ],
                ],
                'sections' => [
                    'expiry' => [
                        'label' => 'Gültigkeitsdauer des Links',
                    ],
                    'usage' => [
                        'label' => 'Teilnahmelimit',
                    ],
                ],
                'options' => [
                    'expiry' => [
                        '1_hour' => '1 Stunde',
                        '1_day' => '1 Tag',
                        '1_week' => '1 Woche',
                        'never' => 'Unbegrenzt',
                    ],
                    'usage' => [
                        'unlimited' => 'Unbegrenzt',
                    ],
                ],
                'labels' => [
                    'approval_notice' => 'Die Freigabe richtet sich weiterhin nach den Zugriffseinstellungen der Gruppe. In öffentlichen Gruppen können Personen sofort beitreten; in privaten Gruppen oder Gruppen mit Freigabepflicht wird zuerst eine Beitrittsanfrage erstellt.',
                ],
                'actions' => [
                    'create' => [
                        'label' => 'Link erstellen',
                    ],
                ],
                'messages' => [
                    'created_success' => 'Einladungslink erstellt.',
                ],
            ],
            'show' => [
                'heading' => [
                    'label' => 'Einladungslink',
                ],
                'labels' => [
                    'link' => 'Link',
                    'created_by' => 'Link erstellt von',
                    'unknown' => 'Unbekannt',
                    'uses' => 'Nutzungen',
                    'limit' => 'Limit',
                    'unlimited' => 'Unbegrenzt',
                    'expires' => 'Läuft ab',
                    'never' => 'Nie',
                ],
                'actions' => [
                    'copy_link' => [
                        'label' => 'Link kopieren',
                    ],
                    'share_link' => [
                        'label' => 'Link teilen',
                    ],
                    'revoke' => [
                        'label' => 'Link widerrufen',
                    ],
                ],
                'messages' => [
                    'copied_success' => 'Einladungslink kopiert.',
                    'copy_prompt' => 'Diesen Link kopieren',
                    'revoke_confirmation' => 'Möchtest du diesen Einladungslink wirklich widerrufen?',
                    'revoked_success' => 'Einladungslink widerrufen.',
                ],
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
                    'unavailable_left' => ':member hat die Gruppe verlassen und muss den Einladungslink selbst öffnen, um wieder beizutreten.',
                    'unavailable_removed' => ':member wurde aus dieser Gruppe entfernt und kann keinen Gruppeneinladungslink erhalten.',
                    'unavailable_blocked' => ':member ist aus dieser Gruppe verbannt und kann keinen Gruppeneinladungslink erhalten.',
                    'sent_success' => 'Einladungslink an :count Chats gesendet.',
                ],
            ],
            'page' => [
                'labels' => [
                    'invited_to_group' => 'Du wurdest eingeladen, einer Gruppe beizutreten',
                    'group_fallback' => 'Gruppe',
                    'invite_title' => 'Gruppenchat-Einladung',
                    'members_count' => 'Mitglieder :count',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Abbrechen',
                    ],
                    'continue' => [
                        'label' => 'Weiter',
                    ],
                    'join_group' => [
                        'label' => 'Gruppe beitreten',
                    ],
                    'request_to_join' => [
                        'label' => 'Beitritt anfragen',
                    ],
                ],
                'messages' => [
                    'invited_to_join_at' => 'Du wurdest eingeladen, dieser Gruppe auf :app beizutreten.',
                    'join_directly' => 'Du kannst dieser Gruppe sofort über diesen Einladungslink beitreten.',
                    'request_required' => 'Administratoren müssen neue Mitglieder bestätigen, bevor sie dieser Gruppe beitreten können.',
                    'request_pending' => 'Deine Beitrittsanfrage wartet auf die Bestätigung eines Administrators.',
                    'request_submitted' => 'Deine Beitrittsanfrage wurde an die Gruppenadministratoren gesendet.',
                    'join_blocked' => 'Du kannst dieser Gruppe mit diesem Einladungslink derzeit nicht beitreten.',
                ],
            ],
        ],
        'join' => [
            'requests' => [
                'heading' => [
                    'label' => 'Beitrittsanfragen',
                ],
                'labels' => [
                    'description' => 'Überprüfe und bearbeite alle Personen, die über einen Einladungslink den Beitritt zu dieser Gruppe angefragt haben.',
                    'review' => 'Prüfen',
                    'summary' => '{1} Beitrittsanfrage|[2,*] Beitrittsanfragen',
                    'unknown_user' => 'Unbekannter Benutzer',
                    'requested_at' => 'Angefragt: :time',
                    'via_invite_link' => 'über einen Einladungslink',
                    'empty_state' => 'Zurzeit gibt es keine ausstehenden Beitrittsanfragen.',
                    'count' => '{1} :count Beitrittsanfrage|[2,*] :count Beitrittsanfragen',
                ],
                'actions' => [
                    'approve' => [
                        'label' => 'Zur Gruppe hinzufügen',
                    ],
                    'approve_all' => [
                        'label' => 'Alle Annehmen',
                        'confirmation_message' => 'Möchtest du wirklich alle ausstehenden Beitrittsanfragen annehmen?',
                    ],
                    'dismiss' => [
                        'label' => 'Ablehnen',
                    ],
                    'dismiss_banner' => [
                        'label' => 'Hinweis zu Beitrittsanfragen ausblenden',
                    ],
                    'dismiss_all' => [
                        'label' => 'Alle Ablehnen',
                        'confirmation_message' => 'Möchtest du wirklich alle ausstehenden Beitrittsanfragen ablehnen?',
                    ],
                    'load_more' => [
                        'label' => 'Mehr Laden',
                    ],
                ],
                'messages' => [
                    'approved_success' => 'Beitrittsanfrage genehmigt.',
                    'approved_all_success' => '{1} :count Beitrittsanfrage genehmigt.|[2,*] :count Beitrittsanfragen genehmigt.',
                    'dismissed_success' => 'Beitrittsanfrage abgelehnt.',
                    'dismissed_all_success' => '{1} :count Beitrittsanfrage abgelehnt.|[2,*] :count Beitrittsanfragen abgelehnt.',
                ],
            ],
            'lobby' => [
                'heading' => [
                    'label' => 'Gruppe beitreten',
                ],
                'labels' => [
                    'default_group_name' => 'Gruppe',
                    'members_count' => '{1} :count Mitglied|[2,*] :count Mitglieder',
                    'more_members' => '{1} :count weiteres Mitglied|[2,*] :count weitere Mitglieder',
                    'already_member' => 'Du bist bereits Mitglied dieser Gruppe.',
                    'join_blocked' => 'Du kannst dieser Gruppe mit diesem Einladungslink derzeit nicht beitreten.',
                    'pending_review' => 'Deine Beitrittsanfrage wartet bereits auf die Prüfung durch einen Admin.',
                    'requires_approval' => 'Neue Mitglieder brauchen die Freigabe eines Admins, bevor sie dieser Gruppe beitreten können.',
                    'open_access' => 'Du kannst dieser Gruppe sofort beitreten.',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Abbrechen',
                    ],
                    'open_group' => [
                        'label' => 'Gruppe öffnen',
                    ],
                    'request_pending' => [
                        'label' => 'Anfrage ausstehend',
                    ],
                    'request_to_join' => [
                        'label' => 'Beitritt anfragen',
                    ],
                    'join_group' => [
                        'label' => 'Gruppe beitreten',
                    ],
                ],
                'messages' => [
                    'invite_inactive' => 'Dieser Einladungslink ist nicht mehr aktiv.',
                    'join_blocked' => 'Du kannst dieser Gruppe mit diesem Einladungslink derzeit nicht beitreten.',
                    'pending_request' => 'Deine Beitrittsanfrage ist bereits ausstehend.',
                    'request_sent' => 'Deine Beitrittsanfrage wurde an die Admins gesendet.',
                ],
            ],
        ],
        'past_members' => [
            'heading' => [
                'label' => 'Ehemalige Mitglieder',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Ehemalige Mitglieder suchen',
                ],
            ],
            'labels' => [
                'no_results' => 'Keine ehemaligen Mitglieder gefunden',
                'reason_left' => 'Gruppe verlassen',
                'reason_removed' => 'Von einem Administrator entfernt',
                'reason_blocked' => 'Von einem Administrator gebannt',
                'at' => ':time',
            ],
        ],
        'blocked_members' => [
            'heading' => [
                'label' => 'Gesperrte Mitglieder',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Gesperrte Mitglieder suchen',
                ],
            ],
            'labels' => [
                'no_results' => 'Keine gesperrten Mitglieder gefunden',
                'helper' => 'Gesperrte Mitglieder können nicht erneut beitreten, bis die Sperre aufgehoben wird.',
            ],
            'actions' => [
                'lift_block' => [
                    'label' => 'Sperre aufheben',
                    'confirmation_message' => 'Möchten Sie die Sperre für :member wirklich aufheben?',
                ],
            ],
            'messages' => [
                'unblocked_success' => ':member kann mit einem Gruppeneinladungslink wieder beitreten.',
            ],
        ],
        'banned_members' => [
            'heading' => [
                'label' => 'Gesperrte Mitglieder',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Gesperrte Mitglieder suchen',
                ],
            ],
            'labels' => [
                'no_results' => 'Keine gesperrten Mitglieder gefunden',
                'helper' => 'Gesperrte Mitglieder können nicht erneut beitreten, bis die Sperre aufgehoben wird.',
            ],
            'actions' => [
                'lift_ban' => [
                    'label' => 'Sperre aufheben',
                    'confirmation_message' => 'Möchten Sie die Sperre für :member wirklich aufheben?',
                ],
            ],
            'messages' => [
                'unbanned_success' => ':member kann mit einem Gruppeneinladungslink wieder beitreten.',
            ],
        ],

    ],

];
