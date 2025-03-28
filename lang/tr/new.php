<?php

return [

    // new-chat component
    'chat' => [
        'labels' => [
            'heading' => 'Yeni Sohbet',
            'you' => 'Sen',
        ],

        'inputs' => [
            'search' => [
                'label' => 'Sohbetleri Ara',
                'placeholder' => 'Ara',
            ],
        ],

        'actions' => [
            'new_group' => [
                'label' => 'Yeni Grup',
            ],
        ],

        'messages' => [
            'empty_search_result' => 'Aramanızla eşleşen kullanıcı bulunamadı.',
        ],
    ],

    // new-group component
    'group' => [
        'labels' => [
            'heading' => 'Yeni Sohbet',
            'add_members' => 'Üye Ekle',
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
            'search' => [
                'label' => 'Ara',
                'placeholder' => 'Ara',
            ],
            'photo' => [
                'label' => 'Fotoğrafı',
            ],
        ],

        'actions' => [
            'cancel' => [
                'label' => 'İptal',
            ],
            'next' => [
                'label' => 'Sonraki',
            ],
            'create' => [
                'label' => 'Oluştur',
            ],
        ],

        'messages' => [
            'members_limit_error' => 'Üye sayısı :count\'ı aşamaz',
            'empty_search_result' => 'Aramanızla eşleşen kullanıcı bulunamadı.',
        ],
    ],

];
