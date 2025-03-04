<?php

return [

    /**
     * Table Prefix:
     * Sets the prefix for Wirechat database tables.
     */
    'table_prefix' => 'wire_',

    /**
     * User Model:
     * The model class representing your users.
     */
    'user_model' => \App\Models\User::class,

    /**
     * Broadcasting:
     * Configuration for message and notification queues.
     */
    'broadcasting' => [
        'messages_queue' => 'messages', // Queue for real-time messaging.
        'notifications_queue' => 'default', // Queue for chat notifications.
    ],

    /**
     * Theme Color:
     * The primary color used in the chat interface.
     */
    'color' => '#a855f7',

    /**
     * Home Route:
     * The route users are redirected to when they exit the chat.
     */
    'home_route' => '/',

    /**
     * Routes:
     * Configures the URL prefix and middleware for Wirechat routes.
     */
    'routes' => [
        'prefix' => '/chats',
        'middleware' => ['web', 'auth'],
        'guards' => ['web'],
    ],

    /**
     * Layout:
     * Specifies the view that will be used as the layout when rendering Wirechat components
     * via the default routes (e.g., /chats and /chats/{id}).
     * The returned view will serve as the parent layout, where the component content
     * will be rendered into the $slot.
     */
    'layout' => 'wirechat::layouts.app',

    /**
     * Features:
     * Toggle various chat features on or off.
     */
    'show_new_chat_modal_button' => true,
    'show_new_group_modal_button' => true,
    'allow_chats_search' => true,
    'allow_media_attachments' => true,
    'allow_file_attachments' => true,

    /**
     * --------------------------------------------
     * Notifications:
     * --------------------------------------------
     */
    'notifications' => [
        'enabled' => true,
        'main_sw_script' => 'sw.js',  // Path to the main service worker,usually in the public root folder
    ],

    /**
     * User Searchable Fields:
     * Fields to query when searching for users in Wirechat.
     */
    'user_searchable_fields' => ['name'],

    /**
     * Maximum Group Members:
     * Limits the number of members allowed in a group chat.
     */
    'max_group_members' => 3000,

    /**
     * Attachments:
     * Configuration for uploading media and files in chats.
     */
    'attachments' => [
        'storage_folder' => 'attachments',
        'storage_disk' => 'public',
        'max_uploads' => 10,

        // Media Config
        'media_mimes' => ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'],
        'media_max_upload_size' => 12288, // 12 MB

        // File Config
        'file_mimes' => ['zip', 'rar', 'txt', 'pdf'],
        'file_max_upload_size' => 12288, // 12 MB
    ],

];
