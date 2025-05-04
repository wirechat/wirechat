<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Use UUIDs for Conversations
    |--------------------------------------------------------------------------
    |
    | Determines the primary key type for the conversations table and related
    | relationships. When enabled, UUIDs (version 7 if supported, otherwise
    | version 4) will be used during initial migrations.
    |
    | ⚠️ This setting is intended for **new applications only** and does not
    | affect how new conversations are created at runtime. It controls whether
    | migrations generate UUID-based keys or unsigned big integers.
    |
    */
    'uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be prefixed to all Wirechat-related database tables.
    | Useful if you're sharing a database with other apps or packages.
    |
    */
    'table_prefix' => 'wire_',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Specify the fully qualified class name of the Default model used for user search
    | within Wirechat. This is used when searching for users (e.g., to
    | start a new conversation)
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | Configure the queues used for broadcasting messages and notifications.
    | 'messages_queue' is used for real-time chat events.
    | 'notifications_queue' handles alert or notification broadcasts.
    |
    */
    'broadcasting' => [
        'messages_queue' => 'messages',
        'notifications_queue' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Color
    |--------------------------------------------------------------------------
    |
    | Define the primary UI color used in the chat interface.
    | This will be used to highlight buttons and elements.
    |
    */
    'color' => '#a855f7',

    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    |
    | The route where users are redirected when they leave or close the chat UI.
    | This can be any valid route or URL in your application.
    |
    */
    'home_route' => '/',

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the URL prefix, middleware stack, and guards for all Wirechat
    | routes. This gives you control over route access and grouping.
    |
    */
    'routes' => [
        'prefix' => 'chats',
        'middleware' => ['web', 'auth:web'],
        'guards' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout View
    |--------------------------------------------------------------------------
    |
    | This is the layout that will be used when rendering Wirechat components
    | via built-in routes like /chats or /chats/{id}. The $slot will contain
    | the dynamic chat content.
    |
    */
    'layout' => 'wirechat::layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific frontend features of Wirechat.
    |
    */
    'show_new_chat_modal_button' => true,
    'show_new_group_modal_button' => true,
    'allow_chats_search' => true,
    'allow_media_attachments' => true,
    'allow_file_attachments' => true,

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Enable and configure notifications for incoming messages or events.
    | 'main_sw_script' should point to your service worker JS file.
    |
    */
    'notifications' => [
        'enabled' => true,
        'main_sw_script' => 'sw.js', // Relative to public path
    ],

    /*
    |--------------------------------------------------------------------------
    | User Searchable Fields
    |--------------------------------------------------------------------------
    |
    | Define which columns to search when users are looking for other users
    | to chat with. These fields should exist on your User model.
    |
    */
    'user_searchable_fields' => ['name'],

    /*
    |--------------------------------------------------------------------------
    | Maximum Group Members
    |--------------------------------------------------------------------------
    |
    | Set a limit to how many users can be added to a single group chat.
    |
    */
    'max_group_members' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | Configure media and file uploads within conversations. Control disk usage,
    | visibility, allowed MIME types, and maximum upload sizes.
    |
    */
    'attachments' => [
        'storage_folder' => 'attachments',
        'storage_disk' => 'public',
        'disk_visibility' => 'public', // Use 'private' to enforce temporary URLs

        'max_uploads' => 10,

        // Media Upload Settings
        'media_mimes' => ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'],
        'media_max_upload_size' => 12288, // Size in KB (12 MB)

        // File Upload Settings
        'file_mimes' => ['zip', 'rar', 'txt', 'pdf'],
        'file_max_upload_size' => 12288, // Size in KB (12 MB)
    ],

];
