<?php

use Illuminate\Support\Facades\Config;
use Laravel\Dusk\Browser;
use Livewire\Component;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\ChatList;
use Workbench\App\Models\User;



// it('test', function () {
//     $this->browse(function ($browser) {
//         $browser->visit('/chats')->assertSee('Laravel');
//     });
// });

describe("Presence", function () {



    it('shows "Chats" title', function () {

        Config::set("wirechat.allow_chats_search", true);
        $auth = User::factory()->create();

        $this->withoutExceptionHandling();

        $this->actingAs($auth)
            ->visit('/chats')
            ->see('Chats');
    });


    it('shows  redirect button', function () {
        $auth = User::factory()->create();

        $this->actingAs($auth)
            ->visit('/chats')
            ->seeElement('#redirect-button');
    });


    it('shows  search field if enabled in config', function () {


        Config::set("wirechat.allow_chats_search", true);
        $auth = User::factory()->create();

        $this->actingAs($auth)
            ->visit('/chats')
            ->see('Search my conversations')
            ->seeElement('#chats-search-field');
    });


    it('does not shows search field if not enabled in config', function () {


        Config::set("wirechat.allow_chats_search", false);
        $auth = User::factory()->create();


        $this->actingAs($auth)
            ->visit('/chats')
            ->dontSee('Search my conversations')
            ->dontSeeElement('#chats-search-field');
    });


    it('shows new chat modal button if enabled in config', function () {


        Config::set("wirechat.allow_new_chat_modal", true);
        $auth = User::factory()->create();

        $this->actingAs($auth)
            ->visit('/chats')
            ->seeElement('#open-new-chat-modal');
    });


    it('does not shows new chat modal button if not enabled in config', function () {


        Config::set("wirechat.allow_new_chat_modal", false);
        $auth = User::factory()->create();

        $this->actingAs($auth)
            ->visit('/chats')
            ->dontSeeElement('#open-new-chat-modal');
    });
});




describe("Interaction", function () {


    test('It can filter chats by entering text in search', function () {
        Config::set("wirechat.allow_chats_search", true);

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create conversation with user1
        $auth->createConversationWith($user1, 'hello');


        //create conversation with user2
        $auth->createConversationWith($user2, 'new message');


        $request = $this->actingAs($auth)
                        ->visit('/chats');
        
        //Assert both conversations visible before typing
        $request->see('iam user 1')->see('iam user 2');

        //type
        $request->type('iam user 1',"#chats-search-field");

        //assert only one visible after typing
        $request->see('iam user 1')->dontSee('iam user 2');

    });


    test('It opens modal when open-new-chat-modal button is tapped', function () {
        Config::set("wirechat.allow_chats_search", true);

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create conversation with user1
        $auth->createConversationWith($user1, 'hello');


        //create conversation with user2
        $auth->createConversationWith($user2, 'new message');


        $request = $this->actingAs($auth)
                        ->visit('/chats')->click("#chats-search-field");
        
        //Assert both conversations visible before typing
        $request->see('iam user 1')->see('iam user 2');

        //type
        $request->click("#chats-search-field")->type('iam user 1',"chats_search")->seeInField('chats_search','iam user 1');

        //assert only one visible after typing
        $request->see('iam user 1')->dontSee('iam user 2');

    })->only();

    test('It can redirect to Chats view page', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create conversation with user1
        $auth->createConversationWith($user1, 'hello');


        //create conversation with user2
        $auth->createConversationWith($user2, 'new message');


        $this->withoutExceptionHandling();

        $request = $this->actingAs($auth)
            ->visit('/chats')
            ->see('iam user 1')
            ->click("iam user 1")
            ->seePageIs('/chats/1');
    });
});
