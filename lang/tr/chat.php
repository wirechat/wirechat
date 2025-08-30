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
            ],
            'messages' => [
            ],
        ],

    ],

];
