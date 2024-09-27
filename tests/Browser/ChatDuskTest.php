<?php

namespace Namu\WireChat\Tests\Browser;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Tests\DuskTestCase;
use Workbench\App\Models\User;

class ChatDuskTest extends DuskTestCase
{



    /** @test */
    public function it_can_show_conversation_when_header_dropdown_is_clicked()
    {

        //  dd(config('livewire.layout'));
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'receiver']);


        $conversation =   $auth->createConversationWith($receiver, 'hi');
        $conversationID = $conversation->id;


        // Create a new class that sets the conversation to 1
        $component = new class extends Chat {
            public $conversation = 2;
        };


        Livewire::actingAs($auth)->visit($component)->assertSee('receiver');
    }



    public function it_can_show_correctly_formatted_time()
    {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // Create a conversation with participants


        $conversation = $auth->createConversationWith($receiver);

        // Set specific times for testing purposes
        $todayTime = now()->setTime(13, 0); // Today at 1:00 PM
        $yesterdayTime = now()->subDay()->setTime(15, 0); // Yesterday at 3:00 PM
        $thisWeekTime = now()->subDays(2)->setTime(9, 0); // Two days ago at 9:00 AM
        $olderTime = now()->subWeeks(2)->setTime(10, 30); // Two weeks ago at 10:30 AM


        // Create messages with different timestamps
        $todayMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Message from today',
            'created_at' => $todayTime
        ]);


        $yesterdayMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Message from yesterday',
            'created_at' => $yesterdayTime
        ]);

        $thisWeekMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Message from this week',
            'created_at' => $thisWeekTime
        ]);

        $olderMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Older message',
            'created_at' => $olderTime
        ]);

        // Expected outputs based on the message created_at timestamps
        $todayExpected = $todayTime->format('g:i A'); // e.g., "1:00 PM"
        $yesterdayExpected = 'Yesterday ' . $yesterdayTime->format('g:i A'); // e.g., "Yesterday 3:00 PM"
        $thisWeekExpected = $thisWeekTime->format('D g:i A'); // e.g., "Mon 9:00 AM"
        $olderExpected = $olderTime->format('m/d/y'); // e.g., "08/31/24"

        $component = new class extends Chat {
            public $conversation = 2;
        };


        // Run the test
        Livewire::actingAs($auth)
            ->visit($component)
            ->assertSee($todayExpected)        // Assert "1:00 PM"
            ->assertSee($yesterdayExpected)    // Assert "Yesterday 3:00 PM"
            ->assertSee($thisWeekExpected)     // Assert "Mon 9:00 AM" (or whatever day it is)
            ->assertSee($olderExpected)        // Assert "08/31/24"
            ->assertSee('Message from today')
            ->assertSee('Message from yesterday')
            ->assertSee('Message from this week')
            ->assertSee('Older message');
    }


    /** @test */
    public function it_shows_suffix_you_in_user_name_if_user_has_self_conversation()
    {

        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');


        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);


        //Assert both conversations visible before typing
        $request
            ->assertSee('Test (You)');
    }



    /**
     * ---
     * Testing Footer
     */


    /** @test */
    public function it_doesnt_show_upload_trigger_if_attachments_not_enabled()
    {
        Config::set('wirechat.allow_media_attachments', false);
        Config::set('wirechat.allow_file_attachments', false);


        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');

        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);

        //Assert both conversations visible before typing
        $request->assertNotPresent("@upload-trigger-button");
    }

    /** @test */
    public function it_shows_upload_trigger_if_any_one_of_attachments_is_enabled()
    {
        Config::set('wirechat.allow_media_attachments', true);
        Config::set('wirechat.allow_file_attachments', false);


        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');

        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);

        //Assert both conversations visible before typing
        $request->assertPresent("@upload-trigger-button");
    }

    /** @test */
    public function it_shows_file_upload_input_if_enabled()
    {
        Config::set('wirechat.allow_file_attachments', true);

        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');

        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);

        //Assert both conversations visible before typing
        $request->click("@upload-trigger-button")->assertPresent("@file-upload-input");
    }

    /** @test */
    public function it_shows_media_upload_input_if_enabled()
    {
        Config::set('wirechat.allow_media_attachments', true);

        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');

        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);

        //Assert both conversations visible before typing
        $request->click("@upload-trigger-button")->assertPresent("@media-upload-input");
    }

    public function it_shows_emoji_trigger()
    {

        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');

        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);

        //Assert both conversations visible before typing
        $request->assertPresent("@emoji-trigger-button");
    }


    public function it_shows_emoji_picker_when_button_is_clicked()
    {
       
        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $conversation =  $auth->createConversationWith($auth, 'hello');

        $component = new class extends Chat {
            public $conversation = 2;
        };


        $request =  Livewire::actingAs($auth)->visit($component);

        //Assert both conversations visible before typing
        $request->assertClicked("@emoji-trigger-button")->assertPresent("@emoji-picker");
    }



}
