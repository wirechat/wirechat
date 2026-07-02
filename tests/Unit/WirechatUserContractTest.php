<?php

use Wirechat\Wirechat\Contracts\WirechatUser;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;

test('optional user display hooks remain trait methods rather than required contract methods', function () {
    $contract = new ReflectionClass(WirechatUser::class);
    $trait = new ReflectionClass(InteractsWithWirechat::class);

    expect($contract->hasMethod('canSendMessageTo'))->toBeFalse()
        ->and($trait->hasMethod('canSendMessageTo'))->toBeTrue()
        ->and($contract->hasMethod('getWirechatSubtitleAttribute'))->toBeFalse()
        ->and($trait->hasMethod('getWirechatSubtitleAttribute'))->toBeTrue();
});
