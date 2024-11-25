<?php

use Namu\WireChat\Enums\Actions;

it('has delete action value as delete', function () {

    expect(Actions::DELETE->value)->toBe('delete');

});

it('has archive action value as archive', function () {

    expect(Actions::ARCHIVE->value)->toBe('archive');

});
