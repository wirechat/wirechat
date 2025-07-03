<?php

namespace Namu\WireChat;

use Closure;
use Illuminate\Support\Arr;

class Panel
{
    protected string $id;
    protected string $path = '';
    protected string $layout = 'wirechat::layouts.app';
    protected string $routePrefix = '';
    protected array $middleware = [];
    protected array $guards = [];
    protected array $features = ['search' => true, 'notifications' => true];
    protected bool|Closure $isDefault = false;

    // Broadcasting
    protected string $messagesQueue = 'messages';
    protected string $notificationsQueue = 'default';

    // Notifications
    protected bool $notifications = true;
    protected string $mainSwScript = 'sw.js';

    // User Searchable Fields
    protected array $userSearchableFields = ['name'];

    // Maximum Group Members
    protected int $maxGroupMembers = 100;

    // Attachments
    protected string $storageFolder = 'attachments';
    protected string $storageDisk = 'object_storage';
    protected string $diskVisibility = 'private';
    protected int $maxUploads = 10;
    protected array $mediaMimes = ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'];
    protected int $mediaMaxUploadSize = 12288;
    protected array $fileMimes = ['zip', 'rar', 'txt', 'pdf'];
    protected int $fileMaxUploadSize = 12288;

    public static function make(string $id = ''): static
    {
        $panel = new static;
        $panel->id = $id;
        return $panel;
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function routePrefix(string $prefix): static
    {
        $this->routePrefix = $prefix;
        return $this;
    }

    public function middleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function guards(array $guards): static
    {
        $this->guards = $guards;
        return $this;
    }

    public function layout(string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function features(array $features): static
    {
        $this->features = array_merge($this->features, $features);
        return $this;
    }

    public function default(bool|Closure $condition = true): static
    {
        $this->isDefault = $condition;
        return $this;
    }

    // Broadcasting Methods
    public function messagesQueue(string|Closure $messagesQueue): static
    {
        $this->messagesQueue = $this->evaluate($messagesQueue);
        return $this;
    }

    public function notificationsQueue(string|Closure $notificationsQueue): static
    {
        $this->notificationsQueue = $this->evaluate($notificationsQueue);
        return $this;
    }

    // Notifications Methods
    public function notifications(bool $enabled=true): static
    {
        $this->notifications = $enabled;
        return $this;
    }

    public function mainSwScript(string $script): static
    {
        $this->mainSwScript = $script;
        return $this;
    }

    // User Searchable Fields Methods
    public function userSearchableFields(array $fields): static
    {
        $this->userSearchableFields = $fields;
        return $this;
    }

    // Maximum Group Members Methods
    public function maxGroupMembers(int $max): static
    {
        $this->maxGroupMembers = $max;
        return $this;
    }

    // Attachments Methods
    public function storageFolder(string $folder): static
    {
        $this->storageFolder = $folder;
        return $this;
    }

    public function storageDisk(string $disk): static
    {
        $this->storageDisk = $disk;
        return $this;
    }

    public function diskVisibility(string $visibility): static
    {
        $this->diskVisibility = $visibility;
        return $this;
    }

    public function maxUploads(int $max): static
    {
        $this->maxUploads = $max;
        return $this;
    }

    public function mediaMimes(array $mimes): static
    {
        $this->mediaMimes = $mimes;
        return $this;
    }

    public function mediaMaxUploadSize(int $size): static
    {
        $this->mediaMaxUploadSize = $size;
        return $this;
    }

    public function fileMimes(array $mimes): static
    {
        $this->fileMimes = $mimes;
        return $this;
    }

    public function fileMaxUploadSize(int $size): static
    {
        $this->fileMaxUploadSize = $size;
        return $this;
    }

    // Getter Methods
    public function getId(): string
    {
        return $this->id;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getGuards(): array
    {
        return $this->guards;
    }

    public function hasFeature(string $feature): bool
    {
        return Arr::get($this->features, $feature, false);
    }

    public function isDefault(): bool
    {
        return $this->evaluate($this->isDefault);
    }

    public function getMessagesQueue(): string
    {
        return $this->messagesQueue;
    }

    public function getNotificationsQueue(): string
    {
        return $this->notificationsQueue;
    }

    public function hasNotifications(): bool
    {
        return $this->notifications;
    }

    public function getMainSwScript(): string
    {
        return $this->mainSwScript;
    }

    public function getUserSearchableFields(): array
    {
        return $this->userSearchableFields;
    }

    public function getMaxGroupMembers(): int
    {
        return $this->maxGroupMembers;
    }

    public function getStorageFolder(): string
    {
        return $this->storageFolder;
    }

    public function getStorageDisk(): string
    {
        return $this->storageDisk;
    }

    public function getDiskVisibility(): string
    {
        return $this->diskVisibility;
    }

    public function getMaxUploads(): int
    {
        return $this->maxUploads;
    }

    public function getMediaMimes(): array
    {
        return $this->mediaMimes;
    }

    public function getMediaMaxUploadSize(): int
    {
        return $this->mediaMaxUploadSize;
    }

    public function getFileMimes(): array
    {
        return $this->fileMimes;
    }

    public function getFileMaxUploadSize(): int
    {
        return $this->fileMaxUploadSize;
    }

    protected function evaluate($value)
    {
        return $value instanceof Closure ? call_user_func($value, $this) : $value;
    }
}
