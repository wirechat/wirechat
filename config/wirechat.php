<?php

use App\Models\User;
use Wirechat\Wirechat\Models\Action;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\JoinRequest;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\MessageRequest;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Models\Setting;

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
    'uses_uuid_for_conversations' => false,

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

    'models' => [
        'user' => User::class,
        'action' => Action::class,
        'attachment' => Attachment::class,
        'conversation' => Conversation::class,
        'group' => Group::class,
        'invite' => Invite::class,
        'join_request' => JoinRequest::class,
        'message' => Message::class,
        'message_request' => MessageRequest::class,
        'participant' => Participant::class,
        'setting' => Setting::class,
    ],

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
        'disk' => env('WIRECHAT_STORAGE_DISK', 'public'),
        'visibility' => 'public',
        'directories' => [
            'attachments' => 'attachments',
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Encryption
     |--------------------------------------------------------------------------
     |
     | Encrypt message bodies at rest. This uses Laravel's encryption service
     | and stores a versioned Wirechat envelope in the message body column.
     |
    */
    'encryption' => [
        'enabled' => env('WIRECHAT_ENCRYPTION_ENABLED', false),
        'compression' => [
            'enabled' => true,
            'min_bytes' => 512,
            'level' => 6,
            'require_smaller_output' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
     | Message URL Parsing
     |--------------------------------------------------------------------------
     |
     | Configure how Wirechat detects and links URLs in message bodies.
     | If allowed_tlds is null, all TLDs are allowed.
     | If allowed_tlds is an empty array, no TLDs are allowed.
     | If allowed_tlds is a list, only those TLDs will be recognized.
     | Defaults shown below.
     |
     */
    'message_url_parsing' => [
        'allow_bare_domains' => true,
        'allowed_tlds' => [
            'com', 'net', 'org', 'io', 'co', 'me', 'app', 'dev', 'ai', 'gg', 'tv',
            'info', 'biz', 'xyz', 'site', 'store', 'shop', 'pro', 'cloud',
        ],
    ],
];
