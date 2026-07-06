<?php

namespace Wirechat\Wirechat\Services;

use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Exceptions\NoPanelProvidedException;
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
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelRegistry;
use Wirechat\Wirechat\Services\Concerns\InteractsWithLinks;
use Wirechat\Wirechat\Settings\UserSettings;

class WirechatService
{
    use InteractsWithLinks;

    protected PanelRegistry $registry;

    protected array $tableNames = [];

    public function __construct()
    {
        $this->registry = app(PanelRegistry::class);
    }

    /**
     * Get a cached table name for a specific model, loading it lazily if needed.
     *
     * @param  string  $modelKey  The model key (e.g., 'action', 'message')
     * @param  callable  $modelFactory  Factory function to create the model instance
     * @return string The table name
     */
    protected function getCachedTableName(string $modelKey, callable $modelFactory): string
    {
        if (! isset($this->tableNames[$modelKey])) {
            $this->tableNames[$modelKey] = $modelFactory()->getTable();
        }

        return $this->tableNames[$modelKey];
    }

    /**
     * Reset the table name cache. Useful for testing or when config changes at runtime.
     *
     * @param  string|null  $modelKey  Optional specific model key to reset, or null to reset all
     */
    public function resetTableNameCache(?string $modelKey = null): void
    {
        if ($modelKey === null) {
            $this->tableNames = [];
        } else {
            unset($this->tableNames[$modelKey]);
        }
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

    public function settings(Model $owner): UserSettings
    {
        return app(WirechatSettingsManager::class)->for($owner);
    }

    public function settingsManager(): WirechatSettingsManager
    {
        return app(WirechatSettingsManager::class);
    }

    public function hasEncryption(): bool
    {
        return app(WirechatEncryption::class)->isEnabled();
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
        return (bool) app(PanelRegistry::class)->getCurrent()?->hasWebPushNotifications();
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
     * @return class-string<Action> The Action model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function actionModelClass(): string
    {
        $class = (string) config('wirechat.models.action', Action::class);
        $this->validateModelClass($class, Action::class, 'wirechat.models.action');

        return $class;
    }

    /**
     * Get the Attachment model class from the configuration.
     *
     * @return class-string<Attachment> The Attachment model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function attachmentModelClass(): string
    {
        $class = (string) config('wirechat.models.attachment', Attachment::class);
        $this->validateModelClass($class, Attachment::class, 'wirechat.models.attachment');

        return $class;
    }

    /**
     * Get the Conversation model class from the configuration.
     *
     * @return class-string<Conversation> The Conversation model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function conversationModelClass(): string
    {
        $class = (string) config('wirechat.models.conversation', Conversation::class);
        $this->validateModelClass($class, Conversation::class, 'wirechat.models.conversation');

        return $class;
    }

    /**
     * Get the Group model class from the configuration.
     *
     * @return class-string<Group> The Group model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function groupModelClass(): string
    {
        $class = (string) config('wirechat.models.group', Group::class);
        $this->validateModelClass($class, Group::class, 'wirechat.models.group');

        return $class;
    }

    /**
     * Get the Invite model class from the configuration.
     *
     * @return class-string<Invite> The Invite model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function inviteModelClass(): string
    {
        $class = (string) config('wirechat.models.invite', Invite::class);
        $this->validateModelClass($class, Invite::class, 'wirechat.models.invite');

        return $class;
    }

    /**
     * Get the JoinRequest model class from the configuration.
     *
     * @return class-string<JoinRequest> The JoinRequest model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function joinRequestModelClass(): string
    {
        $class = (string) config('wirechat.models.join_request', JoinRequest::class);
        $this->validateModelClass($class, JoinRequest::class, 'wirechat.models.join_request');

        return $class;
    }

    /**
     * Get the Message model class from the configuration.
     *
     * @return class-string<Message> The Message model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function messageModelClass(): string
    {
        $class = (string) config('wirechat.models.message', Message::class);
        $this->validateModelClass($class, Message::class, 'wirechat.models.message');

        return $class;
    }

    /**
     * Get the MessageRequest model class from the configuration.
     *
     * @return class-string<MessageRequest> The MessageRequest model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function messageRequestModelClass(): string
    {
        $class = (string) config('wirechat.models.message_request', MessageRequest::class);
        $this->validateModelClass($class, MessageRequest::class, 'wirechat.models.message_request');

        return $class;
    }

    /**
     * Get the Participant model class from the configuration.
     *
     * @return class-string<Participant> The Participant model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function participantModelClass(): string
    {
        $class = (string) config('wirechat.models.participant', Participant::class);
        $this->validateModelClass($class, Participant::class, 'wirechat.models.participant');

        return $class;
    }

    /**
     * Get the Setting model class from the configuration.
     *
     * @return class-string<Setting> The Setting model class.
     *
     * @throws \InvalidArgumentException When the configured class is invalid.
     */
    public function settingModelClass(): string
    {
        $class = (string) config('wirechat.models.setting', Setting::class);
        $this->validateModelClass($class, Setting::class, 'wirechat.models.setting');

        return $class;
    }

    /**
     * Validate that a model class exists and extends the expected base class.
     *
     * @param  string  $class  The class to validate.
     * @param  string  $baseClass  The expected base class.
     * @param  string  $configKey  The config key for error messages.
     *
     * @throws \InvalidArgumentException When the class is invalid.
     */
    protected function validateModelClass(string $class, string $baseClass, string $configKey): void
    {
        if (! class_exists($class)) {
            throw new \InvalidArgumentException(
                "Model class '{$class}' configured in '{$configKey}' does not exist."
            );
        }

        if (! is_a($class, $baseClass, true)) {
            throw new \InvalidArgumentException(
                "Model class '{$class}' configured in '{$configKey}' must extend '{$baseClass}'."
            );
        }
    }

    /**
     * Get the Action model table name.
     *
     * @return string The Action model table name.
     */
    public function actionModelTable(): string
    {
        return $this->getCachedTableName('action', fn () => $this->actionModel());
    }

    /**
     * Get the Attachment model table name.
     *
     * @return string The Attachment model table name.
     */
    public function attachmentModelTable(): string
    {
        return $this->getCachedTableName('attachment', fn () => $this->attachmentModel());
    }

    /**
     * Get the Conversation model table name.
     *
     * @return string The Conversation model table name.
     */
    public function conversationModelTable(): string
    {
        return $this->getCachedTableName('conversation', fn () => $this->conversationModel());
    }

    /**
     * Get the Group model table name.
     *
     * @return string The Group model table name.
     */
    public function groupModelTable(): string
    {
        return $this->getCachedTableName('group', fn () => $this->groupModel());
    }

    /**
     * Get the Invite model table name.
     *
     * @return string The Invite model table name.
     */
    public function inviteModelTable(): string
    {
        return $this->getCachedTableName('invite', fn () => $this->inviteModel());
    }

    /**
     * Get the JoinRequest model table name.
     *
     * @return string The JoinRequest model table name.
     */
    public function joinRequestModelTable(): string
    {
        return $this->getCachedTableName('join_request', fn () => $this->joinRequestModel());
    }

    /**
     * Get the Message model table name.
     *
     * @return string The Message model table name.
     */
    public function messageModelTable(): string
    {
        return $this->getCachedTableName('message', fn () => $this->messageModel());
    }

    /**
     * Get the MessageRequest model table name.
     *
     * @return string The MessageRequest model table name.
     */
    public function messageRequestModelTable(): string
    {
        return $this->getCachedTableName('message_request', fn () => $this->messageRequestModel());
    }

    /**
     * Get the Participant model table name.
     *
     * @return string The Participant model table name.
     */
    public function participantModelTable(): string
    {
        return $this->getCachedTableName('participant', fn () => $this->participantModel());
    }

    /**
     * Get the Setting model table name.
     *
     * @return string The Setting model table name.
     */
    public function settingModelTable(): string
    {
        return $this->getCachedTableName('setting', fn () => $this->settingModel());
    }

    /**
     * Create a new Action model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Action The Action model instance.
     */
    public function actionModel(array $attributes = []): Action
    {
        return new ($this->actionModelClass())($attributes);
    }

    /**
     * Create a new Attachment model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Attachment The Attachment model instance.
     */
    public function attachmentModel(array $attributes = []): Attachment
    {
        return new ($this->attachmentModelClass())($attributes);
    }

    /**
     * Create a new Conversation model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Conversation The Conversation model instance.
     */
    public function conversationModel(array $attributes = []): Conversation
    {
        return new ($this->conversationModelClass())($attributes);
    }

    /**
     * Create a new Group model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Group The Group model instance.
     */
    public function groupModel(array $attributes = []): Group
    {
        return new ($this->groupModelClass())($attributes);
    }

    /**
     * Create a new Invite model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Invite The Invite model instance.
     */
    public function inviteModel(array $attributes = []): Invite
    {
        return new ($this->inviteModelClass())($attributes);
    }

    /**
     * Create a new JoinRequest model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return JoinRequest The JoinRequest model instance.
     */
    public function joinRequestModel(array $attributes = []): JoinRequest
    {
        return new ($this->joinRequestModelClass())($attributes);
    }

    /**
     * Create a new Message model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Message The Message model instance.
     */
    public function messageModel(array $attributes = []): Message
    {
        return new ($this->messageModelClass())($attributes);
    }

    /**
     * Create a new MessageRequest model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return MessageRequest The MessageRequest model instance.
     */
    public function messageRequestModel(array $attributes = []): MessageRequest
    {
        return new ($this->messageRequestModelClass())($attributes);
    }

    /**
     * Create a new Participant model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Participant The Participant model instance.
     */
    public function participantModel(array $attributes = []): Participant
    {
        return new ($this->participantModelClass())($attributes);
    }

    /**
     * Create a new Setting model instance.
     *
     * @param  array  $attributes  The attributes to set on the model.
     * @return Setting The Setting model instance.
     */
    public function settingModel(array $attributes = []): Setting
    {
        return new ($this->settingModelClass())($attributes);
    }
}
