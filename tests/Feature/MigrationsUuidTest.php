<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Models\Action;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Models\Setting;

beforeEach(function () {
    // Drop all tables to ensure clean state
    Schema::dropIfExists((new Action)->getTable());
    Schema::dropIfExists((new Attachment)->getTable());
    Schema::dropIfExists((new Setting)->getTable());
    Schema::dropIfExists((new Group)->getTable());
    Schema::dropIfExists((new Participant)->getTable());
    Schema::dropIfExists((new Message)->getTable());
    Schema::dropIfExists((new Conversation)->getTable());
});

/**
 * Helper to check if a column type matches expected string/UUID type across databases
 */
function isUuidColumnType(string $type): bool
{
    return match (strtolower($type)) {
        'varchar', 'char', 'uuid', 'character', 'nvarchar', 'uniqueidentifier', 'string' => true,
        default => false,
    };
}

/**
 * Helper to check if a column type matches expected integer type across databases
 */
function isIntegerColumnType(string $type): bool
{
    return match (true) {
        str_contains(strtolower($type), 'int') => true,
        default => false,
    };
}

describe('UUID configuration in migrations', function () {

    test('conversations table uses UUID when configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', true);
        Config::set('wirechat.uuids', true);
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Conversation)->getTable(), 'id');
        expect(isUuidColumnType($columnType))->toBeTrue();
    });

    test('conversations table uses integer and adds uuid column when UUID not configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', false);
        Config::set('wirechat.uuids', false);

        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $migration->up();
        $idType = Schema::getColumnType((new Conversation)->getTable(), 'id');
        expect(isIntegerColumnType($idType))->toBeTrue();
    });

    test('messages table conversation_id uses UUID when configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', true);
        Config::set('wirechat.uuids', true);

        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000004_create_wirechat_messages_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Message)->getTable(), 'conversation_id');
        expect(isUuidColumnType($columnType))->toBeTrue();
    });

    test('messages table conversation_id uses integer when UUID not configured', function () {
        Config::set('wirechat.uuids', false);
        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000004_create_wirechat_messages_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Message)->getTable(), 'conversation_id');
        expect(isIntegerColumnType($columnType))->toBeTrue();
    });

    test('participants table conversation_id uses UUID when configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', true);
        Config::set('wirechat.uuids', true);
        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000003_create_wirechat_participants_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Participant)->getTable(), 'conversation_id');
        expect(isUuidColumnType($columnType))->toBeTrue();
    });

    test('participants table conversation_id uses integer when UUID not configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', false);
        Config::set('wirechat.uuids', false);
        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000003_create_wirechat_participants_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Participant)->getTable(), 'conversation_id');
        expect(isIntegerColumnType($columnType))->toBeTrue();
    });

    test('groups table conversation_id uses UUID when configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', true);
        Config::set('wirechat.uuids', true);
        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000007_create_wirechat_groups_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Group)->getTable(), 'conversation_id');
        expect(isUuidColumnType($columnType))->toBeTrue();
    });

    test('groups table conversation_id uses integer when UUID not configured', function () {
        Config::set('wirechat.uses_uuid_for_conversations', false);
        Config::set('wirechat.uuids', false);
        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000007_create_wirechat_groups_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Group)->getTable(), 'conversation_id');
        expect(isIntegerColumnType($columnType))->toBeTrue();
    });

    test('attachments table uses  integer for polymorphic conversation ', function () {
        Config::set('wirechat.uuids', false);
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000002_create_wirechat_attachments_table.php';
        $migration->up();
        $columnType = Schema::getColumnType((new Attachment)->getTable(), 'attachable_id');
        expect(isUuidColumnType($columnType))->toBefalse();
    });

    test('actions table uses integer for polymorphic conversation', function () {
        Config::set('wirechat.uuids', false);
        $migration = include __DIR__.'/../../database/migrations/2024_11_01_000006_create_wirechat_actions_table.php';
        $migration->up();
        $actionableType = Schema::getColumnType((new Action)->getTable(), 'actionable_id');
        $actorType = Schema::getColumnType((new Action)->getTable(), 'actor_id');
        expect(isUuidColumnType($actionableType))->toBefalse();
        expect(isUuidColumnType($actorType))->toBefalse();
    });

    test('settings migration creates metadata columns invite permission and settings table', function () {
        Config::set('wirechat.uses_uuid_for_conversations', false);
        Config::set('wirechat.uuids', false);

        $convMigration = include __DIR__.'/../../database/migrations/2024_11_01_000001_create_wirechat_conversations_table.php';
        $convMigration->up();

        $groupMigration = include __DIR__.'/../../database/migrations/2024_11_01_000007_create_wirechat_groups_table.php';
        $groupMigration->up();

        $migration = include __DIR__.'/../../database/migrations/2026_05_13_000001_create_wirechat_settings_table.php';
        $migration->up();

        expect(Schema::hasColumn((new Conversation)->getTable(), 'meta'))->toBeTrue()
            ->and(Schema::hasColumn((new Group)->getTable(), 'meta'))->toBeTrue()
            ->and(Schema::hasColumn((new Group)->getTable(), 'allow_members_to_invite_others_via_link'))->toBeTrue()
            ->and(Schema::hasTable((new Setting)->getTable()))->toBeTrue()
            ->and(Schema::hasColumn((new Setting)->getTable(), 'owner_id'))->toBeTrue()
            ->and(Schema::hasColumn((new Setting)->getTable(), 'owner_type'))->toBeTrue()
            ->and(Schema::hasColumn((new Setting)->getTable(), 'data'))->toBeTrue();
    });
});
