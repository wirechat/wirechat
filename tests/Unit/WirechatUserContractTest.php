<?php

use Wirechat\Wirechat\Contracts\WirechatUser;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;

test('canSendMessageTo remains an optional trait hook rather than a required contract method', function () {
    $contract = new ReflectionClass(WirechatUser::class);
    $trait = new ReflectionClass(InteractsWithWirechat::class);

    expect($contract->hasMethod('canSendMessageTo'))->toBeFalse()
        ->and($trait->hasMethod('canSendMessageTo'))->toBeTrue();
});
