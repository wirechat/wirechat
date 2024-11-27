<?php

namespace Namu\WireChat\Services;

use Illuminate\Support\Facades\Schema;

class WireChatService
{
    /**
     * Get the color used to be used in as themse
     */
    public function getColor()
    {
        return config('wirechat.color', '#3b82f6');
    }

    /**
     * Retrieve the searchable fields defined in configuration
     * and check if they exist in the database table schema.
     *
     * @return array|null The array of searchable fields or null if none found.
     */
    public function searchableFields(): ?array
    {
        // Define the fields specified as searchable in the configuration
        $fieldsToCheck = config('wirechat.user_searchable_fields');

        //  // Get the table name associated with the model
        //  $tableName = $this->getTable();

        //  // Get the list of columns in the database table
        //  $tableColumns = Schema::getColumnListing($tableName);

        //  // Filter the fields to include only those that exist in the table schema
        //  $searchableFields = array_intersect($fieldsToCheck, $tableColumns);

        return $fieldsToCheck ?: null;
    }

    //Get table prefix from congif
    public static function tablePrefix(): ?string
    {
        return config('wirechat.table_prefix');
    }

    //return a formatted tabel name
    public static function formatTableName($table): string
    {
        return config('wirechat.table_prefix').$table;
    }

    //return a formatted tabel name
    public static function allowsGroups(): bool
    {
        return config('wirechat.allow_new_group_modal', false);
    }

    /**
     * Checks if should show new group modal
     */
    public static function allowsNewGroupModal(): bool
    {
        return config('wirechat.allow_new_group_modal', false);
    }

    /**
     * Checks if new group modal button can be shown
     */
    public static function showNewGroupModalButton(): bool
    {
        return config('wirechat.show_new_group_modal_button', false);
    }

    /**
     * Checks if should show new chat modal
     */
    public static function allowsNewChatModal(): bool
    {
        return config('wirechat.allow_new_chat_modal', false);
    }

    /**
     * Checks if new chat modal button can be shown
     */
    public static function showNewChatModalButton(): bool
    {
        return config('wirechat.show_new_chat_modal_button', false);
    }

    /**
     * Maximum members allowed per group
     */
    public static function maxGroupMembers(): int
    {
        return (int) config('wirechat.max_group_members', 1000);
    }

    /**
     * Get wirechat storage disk
     */
    public static function storageDisk(): string
    {
        return (string) config('wirechat.attachments.storage_disk', 'public');
    }

    /**
     * Get wirechat storage folder
     */
    public static function storageFolder(): string
    {
        return (string) config('wirechat.attachments.storage_folder', 'attachments');
    }

    /**
     * Get wirechat storage disk
     */
    public static function messagesQueue(): string
    {
        return (string) config('wirechat.broadcasting.messages_queue', 'default');
    }

    /**
     * Get wirechat storage disk
     */
    public static function notificationsQueue(): string
    {
        return (string) config('wirechat.broadcasting.notifications_queue', 'default');
    }

    /**
     * Get route name for index
     */
    public static function indexRouteName(): string
    {
        return 'chats';
    }

    /**
     * Get route name for chat view
     */
    public static function viewRouteName(): string
    {
        return 'chat';
    }
}
