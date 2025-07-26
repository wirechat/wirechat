<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

trait HasAttachments
{
    protected bool | Closure | null $attachments = false;
    protected string | Closure | null $storageFolder = 'attachments';
    protected string | Closure | null $storageDisk = 'public';
    protected string | Closure | null $diskVisibility = 'public';
    protected int | Closure | null $maxUploads = 10;
    protected array | Closure | null $mediaMimes = ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'];
    protected int | Closure | null $mediaMaxUploadSize = 12288;
    protected array | Closure | null $fileMimes = ['zip', 'rar', 'txt', 'pdf'];
    protected int | Closure | null $fileMaxUploadSize = 12288;

    public function attachments(bool | Closure $condition = true): static
    {
        $this->attachments = $condition;
        return $this;
    }

    public function storageFolder(string | Closure $folder): static
    {
        $this->storageFolder = $folder;
        return $this;
    }

    public function storageDisk(string | Closure $disk): static
    {
        $this->storageDisk = $disk;
        return $this;
    }

    public function diskVisibility(string | Closure $visibility): static
    {
        $this->diskVisibility = $visibility;
        return $this;
    }

    public function maxUploads(int | Closure $max): static
    {
        $this->maxUploads = $max;
        return $this;
    }

    public function mediaMimes(array | Closure $mimes): static
    {
        $this->mediaMimes = $mimes;
        return $this;
    }

    public function mediaMaxUploadSize(int | Closure $size): static
    {
        $this->mediaMaxUploadSize = $size;
        return $this;
    }

    public function fileMimes(array | Closure $mimes): static
    {
        $this->fileMimes = $mimes;
        return $this;
    }

    public function fileMaxUploadSize(int | Closure $size): static
    {
        $this->fileMaxUploadSize = $size;
        return $this;
    }

    public function getAttachments(): ?bool
    {
        return $this->evaluate($this->attachments);
    }

    public function getStorageFolder(): ?string
    {
        return $this->evaluate($this->storageFolder);
    }

    public function getStorageDisk(): ?string
    {
        return $this->evaluate($this->storageDisk);
    }

    public function getDiskVisibility(): ?string
    {
        return $this->evaluate($this->diskVisibility);
    }

    public function getMaxUploads(): ?int
    {
        return $this->evaluate($this->maxUploads);
    }

    public function getMediaMimes(): ?array
    {
        return $this->evaluate($this->mediaMimes);
    }

    public function getMediaMaxUploadSize(): ?int
    {
        return $this->evaluate($this->mediaMaxUploadSize);
    }

    public function getFileMimes(): ?array
    {
        return $this->evaluate($this->fileMimes);
    }

    public function getFileMaxUploadSize(): ?int
    {
        return $this->evaluate($this->fileMaxUploadSize);
    }

    public function hasAttachments(): bool
    {
        return filled($this->getAttachments());
    }

    public function hasMediaAttachments(): bool
    {
        if (!$this->hasAttachments()) {
            return false;
        }
        return !empty($this->getMediaMimes());
    }

    public function hasFileAttachments(): bool
    {
        if (!$this->hasAttachments()) {
            return false;
        }
        return !empty($this->getFileMimes());
    }
}
