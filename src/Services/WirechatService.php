<?php

namespace Wirechat\Wirechat\Services;

use Wirechat\Wirechat\Exceptions\NoPanelProvidedException;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelRegistry;

class WirechatService
{
    protected PanelRegistry $registry;

    public function __construct()
    {
        $this->registry = app(PanelRegistry::class);
    }

    /**
     * Get a panel by ID or provider class, falling back to the default panel.
     */
    public function getPanel(?string $idOrClass = null): ?Panel
    {

        return $this->registry->get($idOrClass);

    }

    /**
     * Get  panels
     */
    public function panels(): ?array
    {
        return $this->registry->all();

    }

    public function storage(): StorageService
    {

        return new StorageService;
    }

    public function currentPanel(): ?Panel
    {
        return $this->registry->getCurrent();
    }

    /**
     * Get the default panel.
     *
     * @throws NoPanelProvidedException
     */
    public function getDefaultPanel(): ?Panel
    {
        return $this->registry->getDefault();
    }

    /**
     * Get the color used to be used in as themse
     */
    public static function getColor(): string
    {
        return config('wirechat.color', '#3b82f6');
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
     * Get the wirechat storage folder from the configuration.
     *
     * @return string The storage folder.
     *
     * @deprecated Use Wirechat::storage()->directory() instead.
     */
    public static function storageFolder(): string
    {
        return (new StorageService)->attachmentsDirectory();
    }

    /**
     * Get the wirechat disk visibility from the configuration.
     *
     * @return string The disk visibility.
     *
     * @deprecated Use Wirechat::storage()->visibility() instead.
     */
    public static function diskVisibility(): string
    {
        return (new StorageService)->visibility();
    }

    /**
     * Get the wirechat disk visibility from the configuration.
     *
     * @return string The disk visibility.
     *
     * @deprecated Use Wirechat::storage()->visibility() instead.
     */
    public static function storageDisk(): string
    {
        return (new StorageService)->disk();
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
     * Determine if the application prefers to use UUIDs instead of
     * auto-incrementing IDs for the conversations table.
     *
     * This method first checks the new configuration key:
     * `wirechat.uses_uuid_for_conversations`.
     *
     * For backwards compatibility, it will fall back to the old key:
     * `wirechat.uuids` if the new one is not set.
     */
    public static function usesUuidForConversations(): bool
    {
        return (bool) config('wirechat.uses_uuid_for_conversations',
            config('wirechat.uuids', false) // legacy fallback
        );
    }

    /**
     * Legacy method: Check if the application prefers to use UUIDs
     * for the conversations table.
     *
     * @deprecated since 0.4.0 Use {@see usesUuidForConversations()} instead.
     */
    public static function usesUuid(): bool
    {
        return static::usesUuidForConversations();
    }

    /**
     * Get the Action model class from the configuration.
     *
     * @return class-string<\Wirechat\Wirechat\Models\Action> The Action model class.
     */
    public static function actionModelClass(): string
    {
        return (string) config('wirechat.models.action');
    }

    /**
     * Get the Attachment model class from the configuration.
     *
     * @return class-string<\Wirechat\Wirechat\Models\Attachment> The Attachment model class.
     */
    public static function attachmentModelClass(): string
    {
        return (string) config('wirechat.models.attachment');
    }

    /**
     * Get the Conversation model class from the configuration.
     *
     * @return class-string<\Wirechat\Wirechat\Models\Conversation> The Conversation model class.
     */
    public static function conversationModelClass(): string
    {
        return (string) config('wirechat.models.conversation');
    }

    /**
     * Get the Group model class from the configuration.
     *
     * @return class-string<\Wirechat\Wirechat\Models\Group> The Group model class.
     */
    public static function groupModelClass(): string
    {
        return (string) config('wirechat.models.group');
    }

    /**
     * Get the Message model class from the configuration.
     *
     * @return class-string<\Wirechat\Wirechat\Models\Message> The Message model class.
     */
    public static function messageModelClass(): string
    {
        return (string) config('wirechat.models.message');
    }

    /**
     * Get the Participant model class from the configuration.
     *
     * @return class-string<\Wirechat\Wirechat\Models\Participant> The Participant model class.
     */
    public static function participantModelClass(): string
    {
        return (string) config('wirechat.models.participant');
    }
}
