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
            $groupKey = 'Today';
        } elseif ($messageDate->isYesterday()) {
            $groupKey = 'Yesterday';
        } elseif ($messageDate->greaterThanOrEqualTo(now()->subDays(7))) {
            $groupKey = $messageDate->format('l');
        } else {
            $groupKey = $messageDate->format('d/m/Y');
        }

        return $groupKey;
    }

    /**
     * Check if a string contains only emojis.
     *
     * This method uses a regular expression to validate whether the provided string
     * consists entirely of emojis, including those that may have variation selectors
     * or be made up of emoji sequences.
     *
     * @param  string  $message  The message string to check.
     * @return bool Returns `true` if the message contains only emojis, `false` otherwise.
     */
    public static function isEmoji(string $message): bool
    {
        // Regular expression to match only emojis (including emoji sequences with variation selectors and zero width joiners)
        return preg_match('/^[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE0F}\x{200D}]+$/u', $message);
    }
}
