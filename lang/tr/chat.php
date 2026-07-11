<?php

return [

    /**-------------------------
     * Chat
     *------------------------*/
    'labels' => [

        'you_replied_to_yourself' => 'Kendinize cevap verdininiz',
        'participant_replied_to_you' => ':sender sana cevap verdi',
        'participant_replied_to_themself' => ':sender kendine cevap verdi',
        'participant_replied_other_participant' => ':sender, :receiver\'ya cevap verdi',
        'you' => 'Sen',
        'user' => 'Kullanıcı',
        'replying_to' => ':participant\'ya cevap veriliyor',
        'replying_to_yourself' => 'Kendinize cevap veriyorsunuz',
        'attachment' => 'Ek',
    ],

    'inputs' => [
        'message' => [
            'label' => 'Mesaj',
            'placeholder' => 'Mesaj yazınız',
        ],
    ],

    'message_groups' => [
        'today' => 'Bugün',
        'yesterday' => 'Dün',
    ],

    'actions' => [
        'open_group_info' => [
            'label' => 'Grup Bilgisi',
        ],
        'open_chat_info' => [
            'label' => 'Sohbet Bilgisi',
        ],
        'close_chat' => [
            'label' => 'Sohbeti Kapat',
        ],
        'clear_chat' => [
            'label' => 'Sohbet Geçmişini Temizle',
            'confirmation_message' => 'Sohbet geçmişini temizlemek istediğinizden emin misiniz? Bu sadece sizin sohbetinizi temizleyecektir ve diğer katılımcıları etkilemeyecektir.',
        ],
        'delete_chat' => [
            'label' => 'Sohbeti Sil',
            'confirmation_message' => 'Bu sohbeti silmek istediğinizden emin misiniz? Bu, sohbeti sadece sizin tarafınızdan kaldıracaktır, diğer katılımcılar için silinmeyecektir.',
        ],
        'delete_for_everyone' => [
            'label' => 'Herkes için sil',
            'confirmation_message' => 'Emin misiniz?',
        ],
        'delete_for_me' => [
            'label' => 'Benim için sil',
            'confirmation_message' => 'Emin misiniz?',
        ],
        'reply' => [
            'label' => 'Cevapla',
        ],
        'exit_group' => [
            'label' => 'Gruptan Çık',
            'confirmation_message' => 'Bu gruptan çıkmak istediğinizden emin misiniz?',
        ],
        'upload_file' => [
            'label' => 'Dosya',
        ],
        'upload_media' => [
            'label' => 'Fotoğraflar & Videolar',
        ],
    ],

    'messages' => [

        'cannot_exit_self_or_private_conversation' => 'Kendi veya özel sohbette çıkış yapılamaz',
        'owner_cannot_exit_conversation' => 'Sohbet sahibi çıkış yapamaz',
        'rate_limit' => 'Çok fazla deneme! Lütfen yavaşlayın',
        'conversation_not_found' => 'Sohbet bulunamadı.',
        'conversation_id_required' => 'Bir sohbet ID\'si gereklidir',
        'invalid_conversation_input' => 'Geçersiz sohbet girdisi.',
        'replies_unavailable' => 'Bu konuşmada mesajlaşma kullanılamıyor.',
    ],

    'message_request' => [
        'labels' => [
            'heading' => 'Mesaj isteği',
            'description' => 'Yanıt verebilmek için konuşmayı açıp isteği kabul et.',
            'outgoing_notice' => 'Bu mesaj isteği hâlâ beklemede. Alıcı konuşmaya katılmadan önce akışı inceleyebilir.',
        ],
        'actions' => [
            'accept' => [
                'label' => 'Kabul et',
            ],
            'dismiss' => [
                'label' => 'Reddet',
            ],
        ],
        'messages' => [
            'accepted' => 'Mesaj isteği kabul edildi.',
            'dismissed' => 'Mesaj isteği reddedildi.',
            'accept_required' => 'Mesaj göndermeden önce bu isteği kabul et.',
        ],
    ],

    /**-------------------------
     * Info Component
     *------------------------*/
    'info' => [
        'heading' => [
            'label' => 'Sohbet Bilgisi',
        ],
        'actions' => [
            'delete_chat' => [
                'label' => 'Sohbeti Sil',
                'confirmation_message' => 'Bu sohbeti silmek istediğinizden emin misiniz? Bu, sohbeti sadece sizin tarafınızdan kaldıracaktır, diğer katılımcılar için silinmeyecektir.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'Yalnızca özel ve kendine sohbetlere izin verilir',
        ],
    ],

    /**-------------------------
     * Group Folder
     *------------------------*/
    'group' => [

        'invite_message' => [
            'labels' => [
                'type' => 'Grup sohbeti daveti',
            ],
            'actions' => [
                'view_group' => [
                    'label' => 'Grubu görüntüle',
                ],
            ],
        ],

        // Group info component
        'info' => [
            'heading' => [
                'label' => 'Grup Bilgisi',
            ],
            'labels' => [
                'members' => 'Üyeler',
                'add_description' => 'Grup açıklaması ekle',
            ],
            'inputs' => [
                'name' => [
                    'label' => 'Grup Adı',
                    'placeholder' => 'Grup Adını giriniz',
                ],
                'description' => [
                    'label' => 'Açıklama',
                    'placeholder' => 'isteğe bağlı',
                ],
                'photo' => [
                    'label' => 'Fotoğrafı',
                ],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'Grubu Sil',
                    'confirmation_message' => 'Bu grubu silmek istediğinizden emin misiniz?',
                    'helper_text' => 'Grubu silebilmek için önce tüm grup üyelerini kaldırmanız gerekir.',
                ],
                'add_members' => [
                    'label' => 'Üye Ekle',
                ],
                'group_permissions' => [
                    'label' => 'Grup İzinleri',
                ],
                'invite_via_link' => [
                    'label' => 'Bağlantı ile Davet Et',
                ],
                'exit_group' => [
                    'label' => 'Gruptan Çık',
                    'confirmation_message' => 'Bu gruptan çıkmak istediğinizden emin misiniz?',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Yalnızca grup sohbetlerine izin verilir',
            ],
        ],
        // Members component
        'members' => [
            'heading' => [
                'label' => 'Üyeler',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Ara',
                    'placeholder' => 'Üyeleri Ara',
                ],
            ],
            'labels' => [
                'members' => 'Üyeler',
                'owner' => 'Sahibi',
                'admin' => 'Yönetici',
                'no_members_found' => 'Üye bulunamadı',
            ],
            'actions' => [
                'send_message_to_yourself' => [
                    'label' => 'Kendine Mesaj Gönder',
                ],
                'send_message_to_member' => [
                    'label' => ':member\'e Mesaj Gönder',
                ],
                'dismiss_admin' => [
                    'label' => 'Yönetici Yetkilerini Kaldır',
                    'confirmation_message' => ':member\'in yönetici yetkilerini kaldırmak istediğinizden emin misiniz?',
                ],
                'make_admin' => [
                    'label' => 'Yönetici Yap',
                    'confirmation_message' => ':member\'i yönetici yapmak istediğinizden emin misiniz?',
                ],
                'remove_from_group' => [
                    'label' => 'Kaldır',
                    'confirmation_message' => ':member\'i bu gruptan kaldırmak istediğinizden emin misiniz?',
                ],
                'block_member' => [
                    'label' => 'Üyeyi Yasakla',
                    'confirmation_message' => ':member kullanıcısını bu grupta yasaklamak istediğinizden emin misiniz?',
                ],
                'ban_member' => [
                    'label' => 'Üyeyi Yasakla',
                    'confirmation_message' => ':member kullanıcısını bu grupta yasaklamak istediğinizden emin misiniz?',
                ],
                'past_members' => [
                    'label' => 'Geçmiş Üyeler',
                ],
                'blocked_members' => [
                    'label' => 'Yasaklı Üyeler',
                ],
                'banned_members' => [
                    'label' => 'Yasaklı Üyeler',
                ],
                'load_more' => [
                    'label' => 'Daha fazla yükle',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Yalnızca grup sohbetlerine izin verilir',
            ],
        ],
        // add-Members component
        'add_members' => [
            'heading' => [
                'label' => 'Üye Ekle',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Ara',
                    'placeholder' => 'Ara',
                ],
            ],
            'labels' => [

            ],
            'actions' => [
                'save' => [
                    'label' => 'Kaydet',
                ],
                'invite_via_link' => [
                    'label' => 'Bağlantıyla gruba davet et',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Yalnızca grup sohbetlerine izin verilir',
                'members_limit_error' => 'Üye sayısı :count\'u aşamaz',
                'member_already_exists' => ' Zaten gruba eklenmiş',
            ],
        ],
        // permissions component
        'permissions' => [
            'heading' => [
                'label' => 'İzinler',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Ara',
                    'placeholder' => 'Ara',
                ],
            ],
            'labels' => [
                'members_can' => 'Üyeler şunları yapabilir',
                'admins_can' => 'Yöneticiler şunları yapabilir',
            ],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'Grup Bilgilerini Düzenle',
                    'helper_text' => 'Bu, isim, simge ve açıklamayı içerir',
                ],
                'send_messages' => [
                    'label' => 'Mesaj Gönder',
                ],
                'add_other_members' => [
                    'label' => 'Diğer Üyeleri Ekle',
                ],
                'invite_others_via_link' => [
                    'label' => 'Bağlantıyla Davet Et',
                    'helper_text' => 'Üyelerin birincil grup davet bağlantısını kopyalamasına ve göndermesine izin ver',
                ],
                'admin_approval' => [
                    'label' => 'Yeni Üyeleri Onayla',
                    'helper_text' => 'Davet bağlantısı üzerinden katılan kişilerin yöneticiler tarafından onaylanmasını iste',
                ],
            ],
            'messages' => [
            ],
        ],
        'invite_link' => [
            'heading' => [
                'label' => 'Davet Bağlantıları',
            ],
            'labels' => [
                'description' => 'Hesabı olan herkes bu bağlantılardan birini açabilir ve erişim ayarlarınıza göre grubunuza katılabilir.',
                'primary_link' => 'Birincil Bağlantı',
                'admin_approval_enabled' => 'Bu gruba katılmak için üyelerin yönetici onayına ihtiyacı var.',
                'admin_approval_disabled' => 'Bu gruba katılmak için üyelerin yönetici onayına ihtiyacı yok.',
                'primary_link_usage_empty' => 'Henüz kimse katılmadı',
                'primary_link_usage_limited' => ':usages / :limit kullanım',
                'primary_link_usage_total' => 'Şu ana kadar :usages katılım',
                'group_access' => 'Grup Erişimi',
                'group_access_requires_approval' => 'Bu bağlantıları açan kişilerin katılmadan önce yönetici onayı alması gerekir.',
                'group_access_open' => 'Bu bağlantıları açan kişiler hemen gruba katılabilir.',
                'join_requests' => 'Katılma İstekleri',
                'join_requests_helper' => 'Bu gruba katılmak isteyen kişileri inceleyin.',
                'additional_links' => 'Ek Bağlantılar',
                'additional_links_helper' => 'Kendi son kullanma süresi ve kullanım sınırı olan ek davet bağlantıları oluşturun.',
                'additional_link_usage_limited' => ':usages / :limit kullanım',
                'additional_link_usage_total' => ':usages kullanım',
                'additional_link_expires' => ':time sona erer',
                'additional_link_never_expires' => 'Süresi dolmaz',
                'additional_links_empty' => 'Henüz ek bağlantı yok. Sınırlı bir kampanya, geçici bir davet veya özel bir tanışma akışı için yeni bir tane oluşturun.',
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'Grup izinleri',
                ],
                'send_via_chat' => [
                    'label' => 'Bağlantıyı Sohbetle Gönder',
                ],
                'copy_link' => [
                    'label' => 'Bağlantıyı Kopyala',
                ],
                'reset_link' => [
                    'label' => 'Bağlantıyı Sıfırla',
                ],
                'create_new_link' => [
                    'label' => 'Yeni Bağlantı Oluştur',
                ],
                'load_more' => [
                    'label' => 'Daha Fazla Yükle',
                ],
            ],
            'messages' => [
                'copied_success' => 'Davet bağlantısı kopyalandı.',
                'copy_prompt' => 'Bu bağlantıyı kopyala',
                'reset_success' => 'Grup davet bağlantısı sıfırlandı.',
            ],
            'create' => [
                'heading' => [
                    'label' => 'Yeni davet bağlantısı',
                ],
                'inputs' => [
                    'name' => [
                        'placeholder' => 'Bağlantı adı (isteğe bağlı)',
                        'helper_text' => 'Bu adı yalnızca yöneticiler görebilir.',
                    ],
                ],
                'sections' => [
                    'expiry' => [
                        'label' => 'Bağlantı süresi',
                    ],
                    'usage' => [
                        'label' => 'Katılım sınırı',
                    ],
                ],
                'options' => [
                    'expiry' => [
                        '1_hour' => '1 saat',
                        '1_day' => '1 gün',
                        '1_week' => '1 hafta',
                        'never' => 'Süresiz',
                    ],
                    'usage' => [
                        'unlimited' => 'Sınırsız',
                    ],
                ],
                'labels' => [
                    'approval_notice' => 'Onay süreci yine grubun erişim ayarlarına bağlıdır. Herkese açık gruplarda kişiler hemen katılabilir; özel veya onay gerektiren gruplarda ise katılma isteği oluşturulur.',
                ],
                'actions' => [
                    'create' => [
                        'label' => 'Bağlantı oluştur',
                    ],
                ],
                'messages' => [
                    'created_success' => 'Davet bağlantısı oluşturuldu.',
                ],
            ],
            'show' => [
                'heading' => [
                    'label' => 'Davet bağlantısı',
                ],
                'labels' => [
                    'link' => 'Bağlantı',
                    'created_by' => 'Bağlantıyı oluşturan',
                    'unknown' => 'Bilinmiyor',
                    'uses' => 'Kullanım',
                    'limit' => 'Sınır',
                    'unlimited' => 'Sınırsız',
                    'expires' => 'Sona erme',
                    'never' => 'Asla',
                ],
                'actions' => [
                    'copy_link' => [
                        'label' => 'Bağlantıyı kopyala',
                    ],
                    'share_link' => [
                        'label' => 'Bağlantıyı paylaş',
                    ],
                    'revoke' => [
                        'label' => 'Bağlantıyı iptal et',
                    ],
                ],
                'messages' => [
                    'copied_success' => 'Davet bağlantısı kopyalandı.',
                    'copy_prompt' => 'Bu bağlantıyı kopyala',
                    'revoke_confirmation' => 'Bu davet bağlantısını iptal etmek istediğinizden emin misiniz?',
                    'revoked_success' => 'Davet bağlantısı iptal edildi.',
                ],
            ],
            'send_via_chat' => [
                'heading' => [
                    'label' => 'Davet Bağlantısı Gönder',
                ],
                'inputs' => [
                    'search' => [
                        'placeholder' => 'Kullanıcı ara',
                    ],
                ],
                'actions' => [
                    'send' => [
                        'label' => 'Gönder',
                    ],
                ],
                'messages' => [
                    'invite_message' => ':group grubuna bu bağlantıyla katıl: :url',
                    'unavailable_left' => ':member bu gruptan ayrıldı ve yeniden katılmak için davet bağlantısını kendisi açmalıdır.',
                    'unavailable_removed' => ':member bu gruptan çıkarıldı ve grup davet bağlantısı alamaz.',
                    'unavailable_blocked' => ':member bu gruptan yasaklandığı için grup davet bağlantısı alamaz.',
                    'sent_success' => 'Davet bağlantısı :count sohbete gönderildi.',
                ],
            ],
            'page' => [
                'labels' => [
                    'invited_to_group' => 'Bir gruba katılman için davet edildin',
                    'group_fallback' => 'Grup',
                    'invite_title' => 'Grup Sohbeti Daveti',
                    'members_count' => 'Üyeler :count',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'İptal',
                    ],
                    'continue' => [
                        'label' => 'Devam Et',
                    ],
                    'join_group' => [
                        'label' => 'Gruba Katıl',
                    ],
                    'request_to_join' => [
                        'label' => 'Katılma İsteği Gönder',
                    ],
                ],
                'messages' => [
                    'invited_to_join_at' => ':app üzerinde bu gruba katılman için davet edildin.',
                    'join_directly' => 'Bu davet bağlantısıyla gruba hemen katılabilirsin.',
                    'request_required' => 'Yeni üyelerin bu gruba katılmadan önce yönetici onayı alması gerekir.',
                    'request_pending' => 'Katılma isteğin yönetici onayı bekliyor.',
                    'request_submitted' => 'Katılma isteğin grup yöneticilerine gönderildi.',
                    'join_blocked' => 'Şu anda bu davet bağlantısıyla gruba katılamazsın.',
                ],
            ],
        ],
        'join' => [
            'requests' => [
                'heading' => [
                    'label' => 'Katılma İstekleri',
                ],
                'labels' => [
                    'description' => 'Bir davet bağlantısıyla bu gruba katılmak isteyen kişileri inceleyin ve yönetin.',
                    'review' => 'İncele',
                    'summary' => '{1} katılma isteği|[2,*] katılma isteği',
                    'unknown_user' => 'Bilinmeyen kullanıcı',
                    'requested_at' => 'İstendi: :time',
                    'via_invite_link' => 'davet bağlantısıyla',
                    'empty_state' => 'Şu anda bekleyen katılma isteği yok.',
                    'count' => ':count Katılma İsteği',
                ],
                'actions' => [
                    'approve' => [
                        'label' => 'Gruba Ekle',
                    ],
                    'approve_all' => [
                        'label' => 'Tümünü Kabul Et',
                        'confirmation_message' => 'Bekleyen tüm katılma isteklerini kabul etmek istediğinize emin misiniz?',
                    ],
                    'dismiss' => [
                        'label' => 'Reddet',
                    ],
                    'dismiss_banner' => [
                        'label' => 'Katılma istekleri bildirimini kapat',
                    ],
                    'dismiss_all' => [
                        'label' => 'Tümünü Reddet',
                        'confirmation_message' => 'Bekleyen tüm katılma isteklerini reddetmek istediğinize emin misiniz?',
                    ],
                    'load_more' => [
                        'label' => 'Daha Fazlasını Yükle',
                    ],
                ],
                'messages' => [
                    'approved_success' => 'Katılma isteği onaylandı.',
                    'approved_all_success' => '{1} :count katılma isteği onaylandı.|[2,*] :count katılma isteği onaylandı.',
                    'dismissed_success' => 'Katılma isteği reddedildi.',
                    'dismissed_all_success' => '{1} :count katılma isteği reddedildi.|[2,*] :count katılma isteği reddedildi.',
                ],
            ],
            'lobby' => [
                'heading' => [
                    'label' => 'Gruba Katıl',
                ],
                'labels' => [
                    'default_group_name' => 'Grup',
                    'members_count' => ':count üye',
                    'more_members' => ':count üye daha',
                    'already_member' => 'Bu grubun zaten bir üyesisiniz.',
                    'join_blocked' => 'Şu anda bu davet bağlantısıyla gruba katılamazsınız.',
                    'pending_review' => 'Katılma isteğiniz zaten yönetici incelemesinde.',
                    'requires_approval' => 'Bu gruba katılmak için önce yönetici onayı gerekir.',
                    'open_access' => 'Bu gruba hemen katılabilirsiniz.',
                ],
                'actions' => [
                    'cancel' => [
                        'label' => 'Vazgeç',
                    ],
                    'open_group' => [
                        'label' => 'Grubu Aç',
                    ],
                    'request_pending' => [
                        'label' => 'İstek Bekliyor',
                    ],
                    'request_to_join' => [
                        'label' => 'Katılma İsteği Gönder',
                    ],
                    'join_group' => [
                        'label' => 'Gruba Katıl',
                    ],
                ],
                'messages' => [
                    'invite_inactive' => 'Bu davet bağlantısı artık aktif değil.',
                    'join_blocked' => 'Şu anda bu davet bağlantısıyla gruba katılamazsınız.',
                    'pending_request' => 'Katılma isteğiniz zaten beklemede.',
                    'request_sent' => 'Katılma isteğiniz yöneticilere gönderildi.',
                ],
            ],
        ],
        'past_members' => [
            'heading' => [
                'label' => 'Geçmiş Üyeler',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Geçmiş üyeleri ara',
                ],
            ],
            'labels' => [
                'no_results' => 'Geçmiş üye bulunamadı',
                'reason_left' => 'Gruptan ayrıldı',
                'reason_removed' => 'Bir yönetici tarafından çıkarıldı',
                'reason_blocked' => 'Bir yönetici tarafından yasaklandı',
                'at' => ':time',
            ],
        ],
        'blocked_members' => [
            'heading' => [
                'label' => 'Yasaklı Üyeler',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Yasaklı üyeleri ara',
                ],
            ],
            'labels' => [
                'no_results' => 'Yasaklı üye bulunamadı',
                'helper' => 'Yasaklı üyeler, yasak kaldırılana kadar yeniden katılamaz.',
            ],
            'actions' => [
                'lift_block' => [
                    'label' => 'Yasağı Kaldır',
                    'confirmation_message' => ':member için yasağı kaldırmak istediğinizden emin misiniz?',
                ],
            ],
            'messages' => [
                'unblocked_success' => ':member artık grup davet bağlantısıyla yeniden katılabilir.',
            ],
        ],
        'banned_members' => [
            'heading' => [
                'label' => 'Yasaklı Üyeler',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Yasaklı üyeleri ara',
                ],
            ],
            'labels' => [
                'no_results' => 'Yasaklı üye bulunamadı',
                'helper' => 'Yasaklı üyeler, yasak kaldırılana kadar yeniden katılamaz.',
            ],
            'actions' => [
                'lift_ban' => [
                    'label' => 'Yasağı Kaldır',
                    'confirmation_message' => ':member için yasağı kaldırmak istediğinizden emin misiniz?',
                ],
            ],
            'messages' => [
                'unbanned_success' => ':member artık grup davet bağlantısıyla yeniden katılabilir.',
            ],
        ],

    ],

];
