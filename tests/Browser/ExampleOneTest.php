<?php

use Laravel\Dusk\Browser;

test('basic example test', function () {

    $this->withoutExceptionHandling()->browse(function (Browser $browser) {
        $browser->visit('/')
                ->assertSee('Laravel News');
    });
});
