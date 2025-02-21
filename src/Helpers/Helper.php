<?php

namespace Namu\WireChat\Helpers;

use Carbon\Carbon;

class Helper
{
    /**
     * Formats file extensions for use in the 'accept' attribute of an input element.
     *
     * This function takes an array of file extensions (without the leading dot)
     * and formats them with leading dots and comma separators for use in the 'accept'
     * attribute of an HTML input element.
     *
     * @param  array  $fileExtensions  The array of file extensions to format.
     * @return string The formatted string for the 'accept' attribute.
     */
    public static function formattedMediaMimesForAcceptAttribute(): string
    {
        $fileExtensions = config('wirechat.attachments.media_mimes');

        return '.'.implode(',.', $fileExtensions);
    }

    /**
     * Formats file extensions for use in the 'accept' attribute of an input element.
     *
     * This function takes an array of file extensions (without the leading dot)
     * and formats them with leading dots and comma separators for use in the 'accept'
     * attribute of an HTML input element.
     *
     * @param  array  $fileExtensions  The array of file extensions to format.
     * @return string The formatted string for the 'accept' attribute.
     */
    public static function formattedFileMimesForAcceptAttribute(): string
    {
        $fileExtensions = config('wirechat.attachments.file_mimes');

        return '.'.implode(',.', $fileExtensions);
    }

    /**
     * format date for chats
     */
    public static function formatChatDate(Carbon $timestamp): string
    {

        $messageDate = $timestamp;

        $groupKey = '';
        if ($messageDate->isToday()) {
            $groupKey = __('Today');
        } elseif ($messageDate->isYesterday()) {
            $groupKey = __('Yesterday');
        } elseif ($messageDate->greaterThanOrEqualTo(now()->subDays(7))) {
            $groupKey = $messageDate->format('l');
        } else {
            $groupKey = $messageDate->format('d/m/Y');
        }

        return $groupKey;
    }
}
