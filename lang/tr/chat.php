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
                    'label' => 'Uyeyi Engelle',
                    'confirmation_message' => ':member kullanicisini bu grupta engellemek istediginizden emin misiniz?',
                ],
                'past_members' => [
                    'label' => 'Gecmis Uyeler',
                ],
                'blocked_members' => [
                    'label' => 'Engellenen Uyeler',
                ],
                'load_more' => [
                    'label' => 'Daha fazla yükle',
                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Yalnızca grup sohbetlerine izin verilir',
            ],
        ],
        'join_requests' => [
            'heading' => [
                'label' => 'Katilma Istekleri',
            ],
            'labels' => [
                'description' => 'Bir davet baglantisi ile bu gruba katilmak isteyen herkesi incele ve yonet.',
                'unknown_user' => 'Bilinmeyen kullanici',
                'requested_at' => ':time once istendi',
                'via_invite_link' => 'davet baglantisi ile',
                'empty_state' => 'Su anda bekleyen katilma istegi yok.',
            ],
            'actions' => [
                'approve' => [
                    'label' => 'Gruba Ekle',
                ],
                'dismiss' => [
                    'label' => 'Reddet',
                ],
            ],
            'messages' => [
                'approved_success' => 'Katilma istegi onaylandi.',
                'dismissed_success' => 'Katilma istegi reddedildi.',
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
                    'label' => 'Baglanti ile gruba davet et',
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
                'members_can' => 'Üyeler yapabilecek',
                'admins_can' => 'Yöneticiler yapabilecek',
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
                'description' => 'Bir hesabi olan herkes bu baglantilardan birini acabilir ve erisim ayarlarina gore grubuna katilabilir.',
                'primary_link' => 'Birincil Baglanti',
                'admin_approval_enabled' => 'Bu gruba katılmak için üyelerin yönetici onayına ihtiyacı var.',
                'admin_approval_disabled' => 'Bu gruba katılmak için üyelerin yönetici onayına ihtiyacı yok.',
                'primary_link_usage_empty' => 'Henuz kimse katilmadi',
                'primary_link_usage_limited' => ':usages / :limit kullanim',
                'primary_link_usage_total' => 'Su ana kadar :usages katilim',
                'group_access' => 'Grup Erisimi',
                'group_access_requires_approval' => 'Bu baglantilari acan kisilerin katilmadan once yonetici onayi almasi gerekir.',
                'group_access_open' => 'Bu baglantilari acan kisiler hemen katilabilir.',
                'join_requests' => 'Katilma Istekleri',
                'join_requests_helper' => 'Bu gruba katilmak isteyen kisileri incele.',
                'additional_links' => 'Ek Baglantilar',
                'additional_links_helper' => 'Kendi suresi dolma ve kullanim siniri olan ek davet baglantilari olustur.',
                'additional_link_usage_limited' => ':usages / :limit kullanim',
                'additional_link_usage_total' => ':usages kullanim',
                'additional_link_expires' => ':time sona erer',
                'additional_link_never_expires' => 'Suresi dolmaz',
                'additional_links_empty' => 'Henuz ek baglanti yok. Sinirli bir kampanya, gecici bir davet veya ozel bir tanisma akisi icin yeni bir tane olustur.',
            ],
            'actions' => [
                'edit_permissions' => [
                    'label' => 'Grup İzinlerinde Düzenle',
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
                    'label' => 'Yeni Baglanti Olustur',
                ],
            ],
            'messages' => [
                'copied_success' => 'Davet bağlantısı kopyalandı.',
                'copy_prompt' => 'Bu baglantiyi kopyala',
                'reset_success' => 'Grup davet bağlantısı sıfırlandı.',
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
                    'unavailable_left' => ':member bu gruptan ayrildi ve geri donmek icin davet baglantisini kendi acmalidir.',
                    'unavailable_removed' => ':member bu gruptan cikarildi ve grup davet baglantisi alamaz.',
                    'unavailable_blocked' => ':member bu grupta engelli oldugu icin grup davet baglantisi alamaz.',
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
        'past_members' => [
            'heading' => [
                'label' => 'Gecmis Uyeler',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Gecmis uyeleri ara',
                ],
            ],
            'labels' => [
                'no_results' => 'Gecmis uye bulunamadi',
                'reason_left' => 'Gruptan ayrildi',
                'reason_removed' => 'Bir yonetici tarafindan cikarildi',
                'reason_blocked' => 'Bir yonetici tarafindan engellendi',
                'at' => ':time',
            ],
        ],
        'blocked_members' => [
            'heading' => [
                'label' => 'Engellenen Uyeler',
            ],
            'inputs' => [
                'search' => [
                    'placeholder' => 'Engellenen uyeleri ara',
                ],
            ],
            'labels' => [
                'no_results' => 'Engellenen uye bulunamadi',
                'helper' => 'Engellenen uyeler, engel kaldirilana kadar tekrar katilamaz.',
            ],
            'actions' => [
                'lift_block' => [
                    'label' => 'Engeli Kaldir',
                    'confirmation_message' => ':member icin engeli kaldirmak istediginizden emin misiniz?',
                ],
            ],
            'messages' => [
                'unblocked_success' => ':member grup davet baglantisiyla yeniden katilabilir.',
            ],
        ],

    ],

];
