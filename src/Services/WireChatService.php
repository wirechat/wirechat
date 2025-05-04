<?php

namespace Namu\WireChat\Services;

use Illuminate\Support\Facades\Schema;

class WireChatService
{
    /**
     * Get the color used to be used in as themse
     */
    public static function getColor(): string
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

    /**
     * Get the table prefix from the configuration.
     *
     * @return string|null The table prefix or null if not set.
     */
    public static function tablePrefix(): ?string
    {
        return config('wirechat.table_prefix');
    }

    /**
     * Format the table name with the table prefix.
     *
     * @param  string  $table  The table name to format.
     * @return string The formatted table name.
     */
    public static function formatTableName(string $table): string
    {
        return config('wirechat.table_prefix').$table;
    }

    /**
     * Check if the new group modal button can be shown.
     *
     * @return bool True if the new group modal button can be shown, false otherwise.
     */
    public static function showNewGroupModalButton(): bool
    {
        return config('wirechat.show_new_group_modal_button', false);
    }

    /**
     * Check if chat search is allowed.
     *
     * @return bool True if chat search is allowed, false otherwise.
     */
    public static function allowChatsSearch(): bool
    {
        return config('wirechat.allow_chats_search', false);
    }

    /**
     * Check if the new chat modal button can be shown.
     *
     * @return bool True if the new chat modal button can be shown, false otherwise.
     */
    public static function showNewChatModalButton(): bool
    {
        return config('wirechat.show_new_chat_modal_button', false);
    }

    /**
     * Get the maximum number of members allowed per group.
     *
     * @return int The maximum number of members.
     */
    public static function maxGroupMembers(): int
    {
        return (int) config('wirechat.max_group_members', 1000);
    }

    /**
     * Get the wirechat storage disk from the configuration.
     *
     * @return string The storage disk.
     */
    public static function storageDisk(): string
    {
        return (string) config('wirechat.attachments.storage_disk', 'public');
    }

    /**
     * Get the wirechat storage folder from the configuration.
     *
     * @return string The storage folder.
     */
    public static function storageFolder(): string
    {
        return (string) config('wirechat.attachments.storage_folder', 'attachments');
    }

    /**
     * Get the wirechat messages queue from the configuration.
     *
     * @return string The messages queue.
     */
    public static function messagesQueue(): string
    {
        return (string) config('wirechat.broadcasting.messages_queue', 'default');
    }

    /**
     * Get the wirechat notifications queue from the configuration.
     *
     * @return string The notifications queue.
     */
    public static function notificationsQueue(): string
    {
        return (string) config('wirechat.broadcasting.notifications_queue', 'default');
    }

    /**
     * Get the route name for the index page.
     *
     * @return string The index route name.
     */
    public static function indexRouteName(): string
    {
        return 'chats';
    }

    /**
     * Get the route name for the chat view page.
     *
     * @return string The chat view route name.
     */
    public static function viewRouteName(): string
    {
        return 'chat';
    }

    /**
     * Check if notifications are enabled for Wirechat.
     *
     * @return bool True if notifications are enabled, false otherwise.
     */
    public static function notificationsEnabled(): bool
    {
        return (bool) config('wirechat.notifications.enabled', false);
    }

    /**
     * Check if application preferes to use UUID instead of incremental primary ID for conversation table
     */
    public static function usesUuid(): bool
    {
        return (bool) config('wirechat.uuids', false);
    }
}
