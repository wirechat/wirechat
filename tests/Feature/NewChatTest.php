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


it('aborts 503 is feature not available', function () {
    Config::set('wirechat.allow_new_chat_modal',false);
    $auth = ModelsUser::factory()->create();
    
    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertStatus(503,'The NewChat feature is currently unavailable.');

});


it('Title is set ', function () {
    $auth = ModelsUser::factory()->create();
    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertSee('New Chat');

});




it('can filter users if search input is set', function () {
    $auth = ModelsUser::factory()->create();

    //create user
    ModelsUser::factory()->create(['name'=>'John']);

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->set('search','Joh')->assertSee('John');

});



 

it('shows New group if allowed', function () {

    Config::set('wirechat.allow_new_group_modal',true);
    $auth = ModelsUser::factory()->create();

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertSee("New group");

});


it('doesnt shows New group if not allowed', function () {

    Config::set('wirechat.allow_new_group_modal',false);
    $auth = ModelsUser::factory()->create();

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertDontSee("New group");

});


