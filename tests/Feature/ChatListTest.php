<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Workbench\App\Models\User;


///Auth checks 
it('checks if users is authenticated before loading chatlist', function () {
    Livewire::test(ChatList::class)
    ->assertStatus(401);
});


test('authenticaed user can access chatlist ', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
    ->assertStatus(200);
});


///Content validations
it('has "chats label set in chatlist"', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
    ->assertSee('Chat');
});


it('doesnt shows search field if search is disabled in wirechat.config:tesiting Search placeholder', function () {

    Config::set("wirechat.user_search_allowed",false);

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
    ->assertDontSee('Search');
});



it('shows search field if search is enabled in wirechat.config:tesiting Search placeholder', function () {

    Config::set("wirechat.user_search_allowed", true);

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
    ->assertSee('Search');
});




describe('Chatlist',function(){


    it('it shows conversations items when user has chats', function () {

        $auth = User::factory()->create();
    
        $user1 = User::factory()->create(['name'=>'iam user 1']);
        $user2 = User::factory()->create(['name'=>'iam user 2']);

    
        //create conversation with user1
        $auth->createConversationWith($user1);

        //create conversation with user2
        $auth->createConversationWith($user2);
    
    
        Livewire::actingAs($auth)->test(ChatList::class)
        ->assertSee('iam user 1')
        ->assertSee('iam user 2');

    });


    it('it shows last message if it exists in chatlist', function () {

        $auth = User::factory()->create();
    
        $user1 = User::factory()->create(['name'=>'iam user 1']);
    
        //create conversation with user1
        $auth->createConversationWith($user1,message:'How are you doing');
    
    
        Livewire::actingAs($auth)->test(ChatList::class)
        ->assertSee('How are you doing');
    });




});




   

 
