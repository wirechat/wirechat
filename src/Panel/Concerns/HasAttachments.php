<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasAttachments
{
    protected bool|Closure|null $attachments = false;

    protected bool|Closure|null $fileAttachments = false;

    protected bool|Closure|null $mediaAttachments = false;

    protected int|Closure|null $maxUploads = 10;

    protected array|Closure|null $mediaMimes = ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'];

    protected int|Closure|null $mediaMaxUploadSize = 12288;

    protected array|Closure|null $fileMimes = ['zip', 'rar', 'txt', 'pdf'];

    protected int|Closure|null $fileMaxUploadSize = 12288;

    public function attachments(bool|Closure $condition = true): static
    {
        $this->attachments = $condition;

        if ($condition) {

            $this->fileAttachments = true;
            $this->mediaAttachments = true;
        }

        return $this;
    }

    public function fileAttachments(bool|Closure $condition = true): static
    {
        $this->fileAttachments = $condition;

        if ($condition === true) {
            $this->attachments = true;
        }

        return $this;
    }

    public function mediaAttachments(bool|Closure $condition = true): static
    {
        $this->mediaAttachments = $condition;

        if ($condition === true) {
            $this->attachments = true;
        }

        return $this;
    }

    public function maxUploads(int|Closure $max): static
    {
        $this->maxUploads = $max;

        return $this;
    }

    public function mediaMimes(array|Closure $mimes): static
    {
        $this->mediaMimes = $mimes;

        return $this;
    }

    public function mediaMaxUploadSize(int|Closure $size): static
    {
        $this->mediaMaxUploadSize = $size;

        return $this;
    }

    public function fileMimes(array|Closure $mimes): static
    {
        $this->fileMimes = $mimes;

        return $this;
    }

    public function fileMaxUploadSize(int|Closure $size): static
    {
        $this->fileMaxUploadSize = $size;

        return $this;
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
        return $this->attachments === true
            || $this->fileAttachments === true
            || $this->mediaAttachments === true;
    }

    public function hasMediaAttachments(): bool
    {
        return $this->hasAttachments() && ! empty($this->mediaMimes) && $this->mediaAttachments === true;
    }

    public function hasFileAttachments(): bool
    {
        return $this->hasAttachments() && ! empty($this->fileMimes) && $this->fileAttachments === true;
    }
}
