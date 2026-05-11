<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Wirechat\Wirechat\Helpers\Helper;

test('format chat date uses translated output for the current app locale', function () {
    $originalLocale = app()->getLocale();

    try {
        app()->setLocale('tr');
        Carbon::setTestNow(Carbon::parse('2026-04-09 12:00:00'));

        $today = Carbon::parse('2026-04-09 09:00:00');
        $yesterday = Carbon::parse('2026-04-08 09:00:00');
        $thisWeek = Carbon::parse('2026-04-07 09:00:00');
        $older = Carbon::parse('2026-03-20 09:00:00');

        expect(Helper::formatChatDate($today))->toBe(__('wirechat::chat.message_groups.today'))
            ->and(Helper::formatChatDate($yesterday))->toBe(__('wirechat::chat.message_groups.yesterday'))
            ->and(Helper::formatChatDate($thisWeek))->toBe($thisWeek->copy()->locale('tr')->translatedFormat('l'))
            ->and(Helper::formatChatDate($older))->toBe($older->copy()->locale('tr')->translatedFormat('d/m/Y'));
    } finally {
        Carbon::setTestNow();
        app()->setLocale($originalLocale);
    }
});

test('format chat date accepts immutable timestamps', function () {
    $originalLocale = app()->getLocale();

    try {
        app()->setLocale('tr');
        $frozenNow = CarbonImmutable::parse('2026-04-09 12:00:00');

        Carbon::setTestNow($frozenNow);
        CarbonImmutable::setTestNow($frozenNow);

        $today = CarbonImmutable::parse('2026-04-09 09:00:00');
        $yesterday = CarbonImmutable::parse('2026-04-08 09:00:00');
        $thisWeek = CarbonImmutable::parse('2026-04-07 09:00:00');
        $older = CarbonImmutable::parse('2026-03-20 09:00:00');

        expect(Helper::formatChatDate($today))->toBe(__('wirechat::chat.message_groups.today'))
            ->and(Helper::formatChatDate($yesterday))->toBe(__('wirechat::chat.message_groups.yesterday'))
            ->and(Helper::formatChatDate($thisWeek))->toBe($thisWeek->locale('tr')->translatedFormat('l'))
            ->and(Helper::formatChatDate($older))->toBe($older->locale('tr')->translatedFormat('d/m/Y'));
    } finally {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        app()->setLocale($originalLocale);
    }
});
