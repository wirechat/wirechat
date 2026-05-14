<?php

return [

    /**-------------------------
     * Chats
     *------------------------*/
    'labels' => [
        'heading' => 'Sohbetler',
        'no_conversations_yet' => 'Henüz sohbet yok',
        'you' => 'Sen',
        'attachment' => 'Ek',
        'now' => 'Şimdi',
        'load_more' => 'Daha fazla yükle',

    ],

    'inputs' => [
        'search' => [
            'label' => 'Sohbetleri Ara',
            'placeholder' => 'Ara',
        ],
    ],

    'requests' => [
        'heading' => 'Mesaj istekleri',
        'actions' => [
            'open' => [
                'label' => 'İstekler',
            ],
            'close' => [
                'label' => 'İstekleri kapat',
            ],
        ],
        'labels' => [
            'description' => 'Gelen istekleri gözden geçir ve gönderdiğin istekleri takip et.',
            'incoming' => 'Gelen',
            'outgoing' => 'Gönderilen',
            'pending' => 'Beklemede',
            'no_message' => 'Henüz mesaj yok',
            'empty_state' => 'Şu anda aktif bir mesaj isteğin yok.',
            'incoming_empty_state' => 'Şu anda gelen bir mesaj isteğin yok.',
            'outgoing_empty_state' => 'Şu anda gönderilmiş aktif bir mesaj isteğin yok.',
        ],
    ],

    'settings' => [
        'heading' => 'Ayarlar',
        'actions' => [
            'open' => [
                'label' => 'Ayarlar',
            ],
            'close' => [
                'label' => 'Ayarları kapat',
            ],
            'back' => [
                'label' => 'Geri',
            ],
        ],
        'labels' => [
            'profile' => 'Profil',
        ],
        'options' => [
            'notifications' => [
                'label' => 'Bildirimler',
                'description' => 'Mesajlar, gruplar, önizlemeler',
            ],
        ],
        'notifications' => [
            'heading' => 'Bildirimler',
            'options' => [
                'messages' => [
                    'label' => 'Mesajlar',
                    'description' => 'Yeni direkt mesajlar geldiğinde bildir.',
                ],
                'groups' => [
                    'label' => 'Gruplar',
                    'description' => 'Gruplarda yeni etkinlik olduğunda bildir.',
                ],
                'previews' => [
                    'label' => 'Önizlemeleri göster',
                    'description' => 'Bildirimlerde mesaj metnini önizle.',
                ],
            ],
            'preview_disabled' => [
                'private_title' => 'Yeni mesaj',
                'private_body' => ':sender sana bir mesaj gönderdi',
                'group_body' => ':sender bir mesaj gönderdi',
            ],
        ],
    ],

    'actions' => [
        'new_group' => [
            'label' => 'Yeni Grup',
        ],
    ],
];
