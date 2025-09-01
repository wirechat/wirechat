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
    | ⚠️ This setting is intended for **new applications only**
    |
    */
    'table_prefix' => 'wirechat_',

    /*
     |--------------------------------------------------------------------------
     | Storage
     |--------------------------------------------------------------------------
     |
     | Global configuration for Wirechat file storage. Defines the disk,
     | directory, and visibility used for saving attachments.
     |
     */
    'storage' => [
        'disk' => 'public',
        'directory' => 'attachments',
        'visibility' => 'public',
    ],

];
