<?php

use Laravel\Dusk\Browser;

test('basic example test', function () {

    $this->browse(function (Browser $browser) {
        $browser->visit('/')
                ->assertSee('Laravel News');
    });
});
