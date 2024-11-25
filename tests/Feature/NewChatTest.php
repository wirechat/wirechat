<?php

///Presence test

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Components\NewChat;
use Workbench\App\Models\User as ModelsUser;

it('user must be authenticated', function () {
    $auth = ModelsUser::factory()->create();
    $request = Livewire::test(NewChat::class);

    $request->assertStatus(401);

});

// it('aborts 503 is feature not available', function () {
//     Config::set('wirechat.show_new_group_modal_button',false);
//     $auth = ModelsUser::factory()->create();

//     $request = Livewire::actingAs($auth)->test(NewChat::class);

//     $request->assertStatus(503,'The NewChat feature is currently unavailable.');

// });

it('Title is set ', function () {
    $auth = ModelsUser::factory()->create();
    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertSee('New Chat');

});

it('can filter users if search input is set', function () {
    $auth = ModelsUser::factory()->create();

    //create user
    ModelsUser::factory()->create(['name' => 'John']);

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->set('search', 'Joh')->assertSee('John');

});

test('search_users_field_is_set_correctly', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request
        ->assertSeeHtml('dusk="search_users_field"');

});

test('close_modal_button_is_set_correctly', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request
        ->assertSeeHtml('dusk="close_modal_button"');

});

it('shows New group if allowed', function () {

    Config::set('wirechat.show_new_group_modal_button', true);
    $auth = ModelsUser::factory()->create();

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertSee('New group')
        ->assertSeeHtml('@dusk="open_new_group_modal_button"');

});

it('doesnt shows New group if not allowed', function () {

    Config::set('wirechat.show_new_group_modal_button', false);
    $auth = ModelsUser::factory()->create();

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertDontSee('New group')
        ->assertDontSeeHtml('@dusk="open_new_group_modal_button"');

});

test('it shows new group button if user canCreateNewGroups==TRUE (email is verified)', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request->assertSee('New group')
        ->assertSeeHtml('@dusk="open_new_group_modal_button"');

});

test('it doesnt show new group button if canCreateNewGroups==FALSE(email  NOT is verified)', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => null]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request->assertDontSee('New group')
        ->assertDontSeeHtml('@dusk="open_new_group_modal_button"');

});
