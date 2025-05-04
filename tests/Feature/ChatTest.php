<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\MessageType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\MessageDeleted;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Helpers\Helper;
use Namu\WireChat\Jobs\BroadcastMessage;
use Namu\WireChat\Jobs\NotifyParticipants;
use Namu\WireChat\Livewire\Chat\Chat as ChatBox;
use Namu\WireChat\Livewire\Chats\Chats as Chatlist;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

// /Auth checks
it('checks if users is authenticated before loading chatbox', function () {
    Livewire::test(ChatBox::class, ['conversation' => 1])
        ->assertStatus(401);
});

test('authenticaed user can access chatbox ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->withParticipants([$auth])->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
        ->assertStatus(200);
});

test('returns 404 if conversation is not found', function () {
    $auth = User::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => 1])
        ->assertStatus(404);
});

test('returns 403(Forbidden) if user doesnt not bleong to conversation', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
        ->assertStatus(403);
});

describe('Presense', function () {

    describe('header', function () {

        test('it_shows_suffix_you_in_user_name_if_user_has_self_conversation', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // Create-conversation with user1
            $conversation = $auth->createConversationWith($auth, 'hello');

            // dd($conversation);

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert-both-conversations visible before typing
            $request
                ->assertSee('Test')
                ->assertSee('(You)');
        });

        test('it shows "show_chat_info" and doesnt show "show_group_info"  if is private conversation', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // create conversation with user1
            $conversation = $auth->createConversationWith(User::factory()->create(), 'hello');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert both conversations visible before typing
            $request->assertSeeHtml('dusk="show_chat_info"');
            $request->assertDontSeeHtml('dusk="show_group_info"');
        });

        test('it shows "show_chat_info" and doesnt show "show_group_info"  if is self conversation', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // create conversation with user1
            $conversation = $auth->createConversationWith($auth, 'hello');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert both conversations visible before typing
            $request->assertSeeHtml('dusk="show_chat_info"');
            $request->assertDontSeeHtml('dusk="show_group_info"');
        });

        test('it  shows "show_group_info" and doesnt show "show_chat_info"  if is group', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // create conversation with user1
            $conversation = $auth->createGroup('My Group');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert both conversations visible before typing
            $request->assertDontSeeHtml('dusk="show_chat_info"');
            $request->assertSeeHtml('dusk="show_group_info"');
        });
    });

    test('it_can_show_correctly_formatted_time', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // Create a conversation with participants
        $conversation = $auth->createConversationWith($receiver);

        // Set specific times for testing purposes
        Carbon::setTestNow(now()->today());
        // Create messages with different timestamps
        $todayMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Message from today',
        ]);

        Carbon::setTestNow(now()->subDay());
        $yesterdayMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Message from yesterday',
        ]);

        Carbon::setTestNow(now()->subDay(2));
        $thisWeekMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Message from this week',
        ]);

        Carbon::setTestNow(now()->subWeeks(2));
        $olderMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'Older message',
        ]);

        // Expected outputs based on the message created_at timestamps
        $todayExpected = Helper::formatChatDate($todayMessage->created_at);
        $yesterdayExpected = Helper::formatChatDate($yesterdayMessage->created_at);
        $thisWeekExpected = Helper::formatChatDate($thisWeekMessage->created_at);
        $olderExpected = Helper::formatChatDate($olderMessage->created_at);

        // Run the test
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee($todayExpected)        // Assert "1:00 PM"
            ->assertSee($yesterdayExpected)    // Assert "Yesterday 3:00 PM"
            ->assertSee($thisWeekExpected)     // Assert "Mon 9:00 AM" (or whatever day it is)
            ->assertSee($olderExpected)        // Assert "08/31/24"
            ->assertSee('Message from today')
            ->assertSee('Message from yesterday')
            ->assertSee('Message from this week')
            ->assertSee('Older message');
    });

    test('it_doesnt_show_upload_trigger_if_attachments_not_enabled', function () {

        Config::set('wirechat.allow_media_attachments', false);
        Config::set('wirechat.allow_file_attachments', false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertDontSeeHtml('dusk="upload-trigger-button"');
    });

    test('it_shows_upload_trigger_if_any_one_of_attachments_is_enabled', function () {

        Config::set('wirechat.allow_media_attachments', true);
        Config::set('wirechat.allow_file_attachments', false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="upload-trigger-button"');
    });

    test('it_shows_file_upload_input_if_enabled', function () {

        Config::set('wirechat.allow_media_attachments', false);
        Config::set('wirechat.allow_file_attachments', true);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="file-upload-input"');
    });

    test('it_doesnt_show_file_upload_input_if_not_enabled', function () {

        Config::set('wirechat.allow_media_attachments', true);
        Config::set('wirechat.allow_file_attachments', false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertDontSeeHtml('dusk="file-upload-input"');
    });

    test('it_shows_media_upload_input_if_enabled', function () {

        Config::set('wirechat.allow_media_attachments', true);
        Config::set('wirechat.allow_file_attachments', false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="media-upload-input"');
    });

    test('it_doesnt_show_media_upload_input_if_not_enabled', function () {

        Config::set('wirechat.allow_media_attachments', false);
        Config::set('wirechat.allow_file_attachments', true);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertDontSeeHtml('dusk="media-upload-input"');
    });

    test('it_shows_emoji_trigger_button', function () {

        Config::set('wirechat.allow_media_attachments', true);
        Config::set('wirechat.allow_file_attachments', false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="emoji-trigger-button"');
    });
});

describe('mount()', function () {

    test('it renders component when conversation is passed as Id ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request->assertOk();
    });

    test('it renders component when conversation is passed as Conversation Model ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation]);

        $request->assertOk();
    });

    test('it aborts 422 if conversation is passsed as invalid input', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => []]);

        $request->assertStatus(422);
    });

    test('it aborts 422 if conversation is passsed as null', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => null]);

        $request->assertStatus(422, 'A conversation is required');
    });

    test('updates the auth particiapnt  last_active_at field when component is opened', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(3));
        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(4));
        // $this->actingAs($auth);

        $participant = $conversation->participant($auth);
        expect($participant->last_active_at)->toBe(null);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $participant->refresh();

        expect($participant->last_active_at)->not->toBe(null);
    });

    test('When NOT Widget it does not dispatches "refresh" event after succesfully loading chat', function () {
        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');
        $auth->sendMessageTo($conversation, 'hi');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');

        // login as user not auth (Owner)
        $request = Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => false]);

        $request
            ->assertStatus(200)
            ->assertNotDispatched('refresh');
    });

    // test('When Widget it dispatches "refresh" event after succesfully loading chat', function () {
    test('because event is fired in blade x-init so it\'s not testable so we just check it\'s presence ', function () {
        $auth = User::factory()->create();
        $user = User::factory()->create();

        // create group
        $conversation = $auth->createConversationWith($auth, 'hi');
        // login as user not auth (Owner)
        Carbon::setTestNow(now()->subSeconds(60));

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

        Carbon::setTestNow();

        $request
            ->assertOK()
            ->assertSeeHtml('$wire.dispatch(\'chat-opened\',{conversation:conversationId})');
    });
});

describe('Validation', function () {

    test('message body is required', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', null)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertHasErrors('body', 'required');
    });

    test('file attachment count must not exceed value specified in config && it dispatces wirechat-toast error', function () {

        // set config value
        Config::set('wirechat.attachments.max_uploads', 13);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add 12 files
        $files = [];
        for ($i = 0; $i < 15; $i++) {
            // code...
            $files[] = UploadedFile::fake()->create('document.pdf');
        }
        $this->withoutExceptionHandling();
        //  dd($files);
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $files)
            ->call('sendMessage')
            ->assertHasErrors('files');

        $request->assertDispatched('wirechat-toast');
        //     dd($request->errors());

    });

    test('file  size must not exceed value specified in config && it dispatces wirechat-toast error', function () {

        // set config value

        Config::set('wirechat.attachments.media_max_upload_size', 125);
        Config::set('wirechat.attachments.file_max_upload_size', 125);
        $values = ['pdf'];
        Config::set('wirechat.attachments.file_mimes', $values);
        //
        Config::set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:200']);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add 12 files
        $files[] = UploadedFile::fake()->create('document.pdf', 140);

        //  dd($files);
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $files)
            ->call('sendMessage')
            ->assertHasErrors('files.0');

        //    $request->assertDispatched('wirechat-toast');
        //  dd($request->errors());

    });
    test('media size(KB) must not exceed value specified in config && it dispatces wirechat-toast error', function () {

        // set config value
        Config::set('wirechat.attachments.media_max_upload_size', 125);
        Config::set('wirechat.attachments.file_max_upload_size', 125);

        //
        Config::set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:150']);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add 12 files
        $files[] = UploadedFile::fake()->create('document.png', 150);

        // dd($files);
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $files)
            ->call('sendMessage')
            ->assertHasErrors('media.0');

        // $request->assertDispatched('wirechat-toast');
        //  dd($request->errors());

    });

    test('media  Mimes must be the ones  specified in config && it dispatces wirechat-toast error', function () {

        // set config value

        $values = ['png'];
        Config::set('wirechat.attachments.media_mimes', $values);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add PDF
        $files[] = UploadedFile::fake()->create('document.jpg', 120);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $files)
            ->call('sendMessage')
            ->assertHasErrors('media.0');
        //  ->assertHasErrors(['media.0'=>__('wirechat::validation.mimes', ['attribute' => __('wirechat::chat.inputs.media.label'),'values'=>'png'])]);

    });
});

describe('Box presence test: ', function () {

    test('it shows receiver name when conversation is loaded in chatbox', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();
        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('John');
    });

    test('it still shows receiver name  when Conversation has Mixed Model Participants', function () {
        $auth = User::factory()->create();
        $receiver = Admin::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();
        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('John');
    });

    test('it shows group name if conversation is group', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('My Group');
    });

    test('It shows Clear Chat button and method  is wired if conversation is Private', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($participant);
        //
        Livewire::actingAs($participant)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('clearConversation')
            ->assertSeeText('Clear Chat');
    });

    test('It shows Clear Chat button and method  is wired if conversation is Self', function () {
        $auth = User::factory()->create();

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth);
        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('clearConversation')
            ->assertSeeText('Clear Chat');
    });

    test('it shows Exit Group button and method  is wired if conversation is Group and auth is not Owner', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($participant)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('exitConversation')
            ->assertSeeText('Exit Group');
    });

    test('It doesnt show Exit Group button and wire method if auth is Owner', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodNotWired('exitConversation')
            ->assertDontSeeText('Exit Group');
    });

    it('Doesn\'nt show Clear Chat History button and method  is wired if conversation is group', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodNotWired('clearConversation')
            ->assertDontSeeText('Clear Chat History');
    });

    it('Doesn\'nt show Delete chat button and method  is wired if conversation is group', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodNotWired('deleteConversation')
            ->assertDontSeeText('Delete Group');
    });

    test('it shows "Delete Chat" button label if Conversation  is Private', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // add participant
        $conversation = $auth->createConversationWith($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($participant)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('deleteConversation')
            ->assertSeeText('Delete Chat');
    });

    test('it loads messages if they Exists in the conversation', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $auth->sendMessageTo($receiver, message: 'How are you');
        $receiver->sendMessageTo($auth, message: 'i am good thanks');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('How are you')
            ->assertSee('i am good thanks');
    });

    test('it shows sendable names if conversation is group ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant

        $conversation->addParticipant(User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'Micheal']));
        $conversation->addParticipant(User::factory()->withMessage($conversation, 'How can i repay you ')->create(['name' => 'Levo']));
        $conversation->addParticipant(User::factory()->withMessage($conversation, 'Wonderful')->create(['name' => 'Luis']));

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Micheal')
            ->assertSee('Levo')
            ->assertSee('Luis');
    });

    test('it doesnt show auth name if conversation is group unless auth has replied to another you or vice versa ', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // send message
        $auth->sendMessageTo($conversation, 'Message from owner');

        // add participant

        $conversation->addParticipant(User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'Micheal']));
        $conversation->addParticipant(User::factory()->withMessage($conversation, 'How can i repay you ')->create(['name' => 'Levo']));
        $conversation->addParticipant(User::factory()->withMessage($conversation, 'Wonderful')->create(['name' => 'Luis']));

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Message from owner')
            ->assertdontSeeText('Namu');
    });

    test('it shows dusk="disappearing_messages_icon" if disappearingTurnedOn for conversation', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // turn on disappearing
        $conversation->turnOnDisappearing(3600);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('dusk="disappearing_messages_icon"');
    });

    test('it doesnt shows dusk="disappearing_messages_icon" if disappearingTurnedOFF for conversation', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // turn on disappearing
        $conversation->turnOffDisappearing();

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="disappearing_messages_icon"');
    });

    describe('IsWidget:', function () {

        test('it renders $dispatch("close-chat") BUT not redirect to chats index', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            // turn on disappearing
            $conversation->turnOffDisappearing();

            // dd($conversation);
            Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true])
                ->assertDontSeeHtml('href="'.route(WireChat::indexRouteName()).'"')
                ->assertSeeHtml('dusk="return_to_home_button_dispatch"')
                ->assertDontSeeHtml('dusk="return_to_home_button_link"');
            //                ->assertMethodWired('$dispatch(\'close-chat\')');

        });

        test('it doesnt render $dispatch("close-chat") BUT Renders redirect to chats index', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            // turn on disappearing
            $conversation->turnOffDisappearing();

            // dd($conversation);
            Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => false])
                ->assertSeeHtml('href="'.route(WireChat::indexRouteName()).'"')
                ->assertDontSeeHtml('dusk="return_to_home_button_dispatch"')
                ->assertSeeHtml('dusk="return_to_home_button_link"')
                ->assertDontSeeHtml('@click="$dispatch(\'close-chat\')"');
        });
    });

    // test('it shows message time', function () {
    //     $auth = User::factory()->create();

    //     $receiver = User::factory()->create(['name' => 'John']);
    //     $conversation = Conversation::factory()
    //                     ->withParticipants([$auth,$receiver])
    //         ->create();

    //     //send messages
    //     $auth->sendMessageTo($receiver, message: 'How are you');

    //      Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sendable_type' => get_class($auth), // Polymorphic sender type
    //         'sendable_id' =>$auth->id, // Polymorphic sender ID
    //         'body' => 'How are you',
    //         'created_at'=>now()->subDay()
    //     ]);

    //     Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
    //         ->assertSee('Yesterday');
    // })->skip();

});

describe('Message actions: Viewing Private Chat', function () {

    /**
     * Delete for me
     */
    test('it doest shows dusk selector : "delete_message_for_everyone"  if message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $receiver->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    /**
     * Delete for me
     */
    test('it  shows dusk selector : "delete_message_for_me"  if message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $receiver->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_me"');
    });

    test('it shows dusk selector : "delete_message_for_me"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_me"');
    });
});

describe('Message actions:Viewing Group Chat', function () {

    test('it shows dusk selector : "delete_message_for_everyone"  if auth is OWNER & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone" if auth is ADMIN & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $admin = User::factory()->create(['name' => 'User Admin']);

        $conversation = $auth->createGroup('My Group');

        // add admin
        $conversation->addParticipant($admin, ParticipantRole::ADMIN);

        // add participant and send messsage
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($admin)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);
        $user->sendMessageTo($conversation, 'Hi');

        // add participant

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Hi') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it doesnt show dusk selectors : "delete_message_for_everyone"  if auth is PARTICIPANT and does not own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);

        // add participant and send message by random user
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message  but not options
            ->assertDontSeeHtml('dusk="delete_message_for_everyone"');
    });

    /**
     * Delete for me
     */
    test('it doesnt show dusk selector : "delete_message_for_me"  if auth is OWNER & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it doesnt show dusk selector : "delete_message_for_me" if auth is ADMIN & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $admin = User::factory()->create(['name' => 'User Admin']);

        $conversation = $auth->createGroup('My Group');

        // add admin
        $conversation->addParticipant($admin, ParticipantRole::ADMIN);

        // add participant and send messsage
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($admin)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it doesnt show dusk selector : "delete_message_for_me"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User Admin']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);
        $user->sendMessageTo($conversation, 'Hi');

        // add participant

        // dd($conversation);
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Hi') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it doesnt show dusk selector : "delete_message_for_me"   if auth is PARTICIPANT and does not own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);

        // add participant and send message by random user
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message  but not options
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });
});

describe('Testing permissions accssibility ', function () {

    test('it shows footer & message actions but NOT "Only admins can send messages" label if auth is Owner', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $participant = User::factory()->create(['name' => 'John']);
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        // test
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it still shows footer & message actions but does not show "Only admins can send messages" label if auth is Owner when send_messages permission is off', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $participant = User::factory()->create(['name' => 'John']);
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = false;
        $group->save();

        // test
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it shows footer & message actions but NOT "Only admins can send messages" label if is Admin and send_messages permission is on', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = true;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it still shows chat-footer& message actions but NOT "Only admins can send messages" label if auth is Admin when send_messages permission is off', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = false;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it shows chat-footer & message actions but NOT "Only admins can send messages" label if auth is PARTICIPANT when send_messages permission is on', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = true;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it does not shows chat-footer & message actions but show "Only admins can send messages" label if auth is PARTICIPANT when send_messages permission is off', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = false;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Only admins can send messages')
            ->assertDontSeeHtml('id="chat-footer"')
            ->assertDontSeeHtml('dusk="message_actions"');
    });

    // todo: dispatch refresh event after updating permissions

});

describe('Sending messages ', function () {

    // message
    test('it renders new message to chatbox when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage')
            ->assertSee('New message');
    });

    test('it saves new message to database when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $messageExists = Message::where('body', 'New message')->exists();

        expect($messageExists)->toBe(true);
    });

    test('it saves text: message type as TEXT', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::where('body', 'New message')->first();

        expect($message->type)->toBe(MessageType::TEXT);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when message is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage')
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });

    test('it doesn not pushes job "BroadcastMessage" when message is sent', function () {
        Event::fake();
        Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertNotPushed(BroadcastMessage::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    });

    test('it broadcasts event "MessageCreated" when message is sent', function () {
        Event::fake();
        //   Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    });

    test('it pushes job "NotifyParticipants" when conversation is private', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it does not push job "NotifyParticipants" when conversation is Self', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $conversation = $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        Queue::assertNotPushed(NotifyParticipants::class);
    });

    test('it only pushes job "NotifyParticipants" when conversation is a Group ', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it broadcasts event "NotifyParticipant" when message is sent to private conversation', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Event::assertDispatched(NotifyParticipant::class, function ($event) use ($receiver) {
            return $event->participant->participantable_id == $receiver->id;
        });
    });

    test('it broadcasts event "NotifyParticipant" to all members of group-except owner of message when message is sent', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add members
        for ($i = 0; $i < 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);
    });

    test('it does not broadcasts event "NotifyParticipant" to member who exited the group-meaning expect only 20 event not 21', function () {
        Event::fake();
        //  Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add members

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);

        for ($i = 0; $i < 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        //   $user->sendMessageTo($conversation, 'hi');
        $user->exitConversation($conversation); // exit here

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        //    Carbon::setTestNow(now()->addSeconds(6));

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);
    });

    test('it does not broadcasts event "MessageCreated" if it is SelfConversation', function () {
        Event::fake();
        //   Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Event::assertNotDispatched(MessageCreated::class);
    });

    test('sending messages is rate limited by 60 in 60 seconds', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        Carbon::setTestNow(Carbon::now()); // Freeze the current time

        for ($i = 0; $i < 60; $i++) {
            \Illuminate\Support\Facades\RateLimiter::increment('send-message:'.$auth->id);
        }

        // Move the time forward slightly for the 61st message
        Carbon::setTestNow(Carbon::now()->addSeconds(4));
        // on 61 abort
        $request->set('body', 'New message')->call('sendMessage');

        $request->assertStatus(429);
    });

    // sending like
    test('it renders heart(❤️) to chatbox when it sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike')
            ->assertSee('❤️');
    });

    test('it saves the heart(❤️) to database when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $messageExists = Message::where('body', '❤️')->exists();
        expect($messageExists)->toBe(true);
    });

    test('it saves textheart(❤️): message type as TEXT', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::where('body', '❤️')->first();

        expect($message->type)->toBe(MessageType::TEXT);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike')
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });

    test('it Broadcaste event job "MessageCreted" when sendLike is called and conversation is Group', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        event::assertDispatched(MessageCreated::class);
    });

    test('it Broadcaste event job "MessageCreted" when sendLike is called and conversation is PRIVATE', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::PRIVATE]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        event::assertDispatched(MessageCreated::class);
    });

    test('it does not Broadcaste event job "MessageCreted" when sendLike is called and conversation is SELF', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::SELF]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        event::assertNotDispatched(MessageCreated::class);
    });

    test('it pushed job "NotifyParticipants" when sendLike is called and is GROUP', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it pushes job "NotifyParticipants" when sendLike is called and is PRIVATE', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::PRIVATE]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class);
    });

    test('it does not pushed job "NotifyParticipants" when sendLike is called and is SELF', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::SELF]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertNotPushed(NotifyParticipants::class);
    });

    test('it broadcasts event "NotifyParticipant" when sendLike is called when conversation is PRIVATE', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::PRIVATE]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Event::assertDispatched(NotifyParticipant::class, function ($event) use ($receiver) {
            return $event->participant->participantable_id == $receiver->id;
        });
    });

    test('it does not broadcasts event "NotifyParticipant" when sendLike is called when conversation is SELF', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::SELF]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Event::assertNotDispatched(NotifyParticipant::class);
    });

    test('it does not broadcasts event "NotifyParticipant" when sendLike is called when conversation is GROUP', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Event::assertNotDispatched(NotifyParticipant::class);
    });

    test('sending hearts(❤️) is rate limited by 50 in 60 seconds', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        for ($i = 0; $i < 60; $i++) {
            \Illuminate\Support\Facades\RateLimiter::increment('send-message:'.$auth->id);
        }

        // Test that the rate limit is hit
        $request->call('sendLike');
        $request->assertStatus(429);
    });

    // attchements
    test('it saves image record to databse when created & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $messageExists = Attachment::all();
        expect(count($messageExists))->toBe(1);
    });

    test('it saves image to storage when created & clears files properties when done', function () {
        Storage::fake('public');

        Config::set('wirechat.attachments.storage_disk', 'public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();
        Storage::disk('public')->assertExists(WireChat::storageFolder().'/'.$attachment->file_anme);
    });

    test('it saves file visibility as public when storage_disk is public', function () {
        Storage::fake('public');

        Config::set('wirechat.attachments.storage_disk', 'public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();
        $visibility = Storage::disk('public')->getVisibility(WireChat::storageFolder().'/'.$attachment->file_anme);

        expect($visibility)->toBe('public');
    });

    test('it saves file visibility as public when storage_disk is s3', function () {
        Storage::fake('s3');

        Config::set('wirechat.attachments.storage_disk', 's3');
        Config::set('wirechat.attachments.disk_visibility', 'private');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();
        $visibility = Storage::disk('s3')->getVisibility(WireChat::storageFolder().'/'.$attachment->file_anme);

        expect($visibility)->toBe('public');
    });

    test('it saves image: message type as attachemnt ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $message = $conversation->messages()->first();

        expect($message->type)->toBe(MessageType::ATTACHMENT);
    });

    test('it renders image  to chatbox when it attachement is sent & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            ->assertSeeHtml('<img ')
            // now assert that media is back to empty
            ->assertSet('media', []);

        // $messageExists = Attachment::all();
        // dd($messageExists);

    });

    // video
    test('it saves video to databse when created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage');

        $messageExists = Attachment::all();
        expect(count($messageExists))->toBe(1);
    });

    test('it saves video to storage when created', function () {
        Storage::fake('public');

        Config::set('wirechat.attachments.storage_disk', 'public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage');

        $attachment = Attachment::first();
        Storage::disk('public')->assertExists(WireChat::storageFolder().'/'.$attachment->file_anme);
    });

    test('it saves video: message type as attachemnt', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage');

        $message = $conversation->messages()->first();

        expect($message->type)->toBe(MessageType::ATTACHMENT);
    });

    test('it saves file to databse when created & clears files properties when done', function () {

        config::set('wirechat.attachments.storage_disk', 'public');
        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file[] = UploadedFile::fake()->create('photo.pdf', '400', 'application/pdf');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $file)
            ->call('sendMessage')
            // now assert that file is back to empty
            ->assertSet('files', []);

        $messageExists = Attachment::all();

        expect(count($messageExists))->toBe(1);
    });

    test('it saves file to storage when created & clears files properties when done', function () {

        config::set('wirechat.attachments.storage_disk', 'public');
        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file[] = UploadedFile::fake()->create('photo.pdf', '400', 'application/pdf');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $file)
            ->call('sendMessage')
            // now assert that file is back to empty
            ->assertSet('files', []);

        $attachment = Attachment::first();
        Storage::disk('public')->assertExists(WireChat::storageFolder().'/'.$attachment->file_anme);
    });

    test('dispatched event is listened to in chatlist after message is created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // assert no message yet
        $chatListComponet = Livewire::actingAs($auth)->test(ChatList::class)->assertDontSee('new message');

        // send message
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'new message')
            ->call('sendMessage');

        // assert message created
        $chatListComponet->dispatch('refresh')->assertSee('new message');
    });
});

describe('Sending reply', function () {

    // reply messages

    test('it throws Payload DecryptException error if id is not encrypted', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('setReply', $message->id)
            ->assertStatus(500);
    })->throws(\Illuminate\Contracts\Encryption\DecryptException::class);

    test('it doesnt throw DecryptException invalid error if id is encrypted', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('setReply', encrypt($message->id));
    })->throwsNoExceptions();

    test('it returns abort(404) when replying if message does not belong to this conversation or is not owned by any participant', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // create random message not belonging to auth user
        $randomuser = User::factory()->create();
        $randomUSer2 = User::factory()->create();
        $randomMessage = $randomuser->sendMessageTo($randomUSer2, message: 'How are you');

        // send message
        $auth->sendMessageTo($receiver, message: 'How are you');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('setReply', encrypt($randomMessage->id))
            ->assertStatus(404);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('it can set reply message when setReply is called', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->assertSet('replyMessage.id', $message->id);

    });

    test('it shows "replying to yourself" when auth is replying to own message ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        // dd($conversation->id,$message->conversation_id);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->call('$refresh')
            // we test seprate because the text is not in same HTML tag
            ->assertSee('Replying to')
            ->assertSee('Yourself');
    });
    test('it dispatches "focus-input-field" when reply is set', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->assertDispatched('focus-input-field');
    });

    test('it can remove reply message when removeReply is called ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('removeReply')
            ->assertSet('replyMessage', null);
    });
});

describe('Deleting Conversation', function () {

    test('it redirects to chats route after deleting conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call('deleteConversation')
            ->assertStatus(200)
            ->assertRedirect(route(WireChat::indexRouteName()));
    });

    test('Logged in user can still access deleted conversation in chat route or chatbox', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        //    dd($receiver->sendMessageTo($auth, message: '4')->conversation->id,$conversation->id);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation');

        // assert chatbox
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertStatus(200);

        // assert chat route
        $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))->assertStatus(200);
    });

    test('user can regain access to deleted conversation if receiver/other user send a new message', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('deleteConversation');

        Carbon::setTestNow(now()->addSeconds(4));

        // let receiver send a new message
        $receiver->sendMessageTo($auth, message: '5');

        // assert conversation will be null
        expect($auth->conversations()->first())->not->toBe(null);

        // also assert that user receives 403 forbidden
        $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))->assertStatus(200);
    });

    test('user can regain access to deleted conversation if they send a new message after deleting conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('deleteConversation');

        Carbon::setTestNow(now()->addSeconds(4));
        // let auth send a new message to conversation after deleting
        $auth->sendMessageTo($receiver, message: '5');

        // assert conversation will be null
        expect($auth->conversations()->first())->not->toBe(null);

        // also assert that user receives 403 forbidden
        $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))->assertStatus(200);
    });

    test('deleted convesation should be available in database if only one user has deleted it', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // $conversation->deleteFor($auth);

        //  $conversation = Conversation::all();
        // dd($conversation);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('deleteConversation');

        $conversation = Conversation::withoutGlobalScopes()->find($conversation->id);
        expect($conversation)->not->toBe(null);
    });

    test('user shold not be able to see previous messages present when conversation was deleted if they send a new message, but should be able to see new ones ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        Carbon::setTestNow(now()->addMinute(4));
        $request->call('deleteConversation');

        Auth::logout();
        // send new message in order to gain access to converstion
        Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');

        // open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert user can't see previous messages
        $request2
            ->assertDontSee('1 message')
            ->assertDontSee('2 message')
            ->assertDontSee('3 message')
            ->assertDontSee('4 message');

        // assert user can see new messages
        $request2
            ->assertSee('5 message');
    });

    test('receiver in the conversation should be able to see all messages even when auth/other user deletes conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // /reqeust for $auth to delete conversation
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation');

        Auth::logout();

        // //send after deleting conversation
        Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');
        // dd($message,$conversation);

        // /request for $receiver to access conversation
        $request = Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert receiver can see previous messages
        $request
            ->assertSee('1 message')
            ->assertSee('2 message')
            ->assertSee('3 message')
            ->assertSee('4 message');

        // assert user can see new messages
        $request->assertSee('5 message');
    });

    test('it resets conversation_deleted_at value of auth-particiapant if new message is added to conversation by other user and user opens chat ', function () {

        $auth = User::factory()->create(['name' => 'Mike']);
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->subMinutes(20));

        $conversation = $auth->createConversationWith($receiver, 'hi');

        Carbon::setTestNow(now()->addMinutes(4));

        // /load and delete conversation
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation');

        // send message from receiver && reset TIME

        Carbon::setTestNow(now()->addMinutes(10));

        $message = $auth->sendMessageTo($conversation, message: '4 message');

        // load again
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertOk();

        // assert

        $authParticipant = $conversation->participant($auth);

        //  dd(['message'=>$message->created_at->toString(),'participant'=>$authParticipant->conversation_deleted_at->toString(),'conversation'=>$conversation->updated_at->toString()]);
        expect($authParticipant->conversation_deleted_at)->toBe(null);
    });

    describe('IsWidget:--', function () {

        test('it does not redirects to chats route after deleting conversation', function () {
            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertStatus(200)
                ->assertNoRedirect();
        });

        test('it dispatches "close-chat" evnt after deleting conversation', function () {
            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertDispatched('close-chat');
        });

        test('it dispatches "chat-deleted" event after Deleting conversation', function () {

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertDispatched('chat-deleted');
        });

        test('Deleted chat should no longer appea in Chats componnet when "chat-deleted" event is dispacted after Deleting conversation', function () {

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver, 'Hello my message');

            // Open chats list
            $CHATLIST = Livewire::actingAs($auth)->test(Chatlist::class);

            // Assert conversation is visible
            $CHATLIST->assertViewHas('conversations', function ($conversation) {
                return count($conversation) == 1;
            });

            // login into chat component
            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertDispatched('chat-deleted');

            // Assert conversation no longer visible in chats after claring chat
            $CHATLIST->dispatch('chat-deleted', $conversation->id)->assertViewHas('conversations', function ($conversation) {
                return count($conversation) == 0;
            });
        });
    });

    // test('it does not also resets conversation_deleted_at value of auth-particiapant they send new message from Chat within component to conversation themselves ', function () {

    //     $auth = User::factory()->create();
    //     $receiver = User::factory()->create(['name' => 'John']);

    //     $conversation = $auth->createConversationWith($receiver);

    //     $authParticipant = $conversation->participant($auth);

    //     //auth -> receiver
    //     $auth->sendMessageTo($receiver, message: '1 message');
    //     $auth->sendMessageTo($receiver, message: '2 message');

    //     //receiver -> auth
    //     $receiver->sendMessageTo($auth, message: '3 message');
    //     $receiver->sendMessageTo($auth, message: '4 message');

    //     ///load and delete conversation
    //     Carbon::setTestNow(now()->addSeconds(4));
    //     Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->call("deleteConversation");

    //     //assert not null
    //     $authParticipant->refresh();
    //     expect($authParticipant->conversation_deleted_at)->not->toBe(null);

    //     //load again
    //     Carbon::setTestNow(now()->addSeconds(20));
    //     Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
    //     ->set('body','hello')
    //     ->call('sendMessage');

    //     //assert
    //     $authParticipant->refresh();
    //     expect($authParticipant->conversation_deleted_at)->toBe(null);

    // });

});

describe('Clearing Conversation', function () {

    test('user should still have access after deleting conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('clearConversation');

        Auth::logout();
        // send new message in order to gain access to converstion
        // Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');

        // open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertOk();
    });

    test('user shold not be able to see previous messages present after conversation was clear if they send a new message, but should be able to see new ones ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('clearConversation');

        Auth::logout();
        // send new message in order to gain access to converstion
        Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');

        // open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert user can't see previous messages
        $request2
            ->assertDontSee('1 message')
            ->assertDontSee('2 message')
            ->assertDontSee('3 message')
            ->assertDontSee('4 message');

        // assert user can see new messages
        $request2
            ->assertSee('5 message');
    });

    test('it redirects to chats route after clearing conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call('clearConversation')
            ->assertStatus(200)
            ->assertRedirect(route(WireChat::indexRouteName()));
    });

    test('user can still open conversatoin after clearing it ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call('clearConversation');

        // assert
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertOk();
    });

    describe('IsWidget:', function () {

        test('it does not redirects to chats route after deleting conversation', function () {
            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertStatus(200)
                ->assertNoRedirect();
        });

        test('it dispatches "close-chat" event after clearing conversation', function () {
            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertDispatched('close-chat');
        });

        test('it dispatches "refresh" event after Clearing conversation', function () {

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertDispatched('refresh');
        });

        test('message is cleared/updated in Chats componnet when refresh "refresh" event is dispacted after Clearing conversation', function () {

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver, 'Hello my message');

            // Open chats list
            $CHATLIST = Livewire::actingAs($auth)->test(Chatlist::class);
            // Assert messsage is visible
            $CHATLIST->assertSee('Hello my message');

            // login into chat component
            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertDispatched('refresh');

            // Assert message no longer visible in chats after claring chat
            $CHATLIST->dispatch('refresh')->assertDontSee('Hello my message');
        });
    });
});

describe('Exiting Conversation', function () {

    test('user cannot access conversation after exiting', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');
        $user->exitConversation($conversation); // exit here

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertStatus(403);
    });

    test('it redirects after exiting conversation to chats', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('exitConversation')
            ->assertRedirect(route(WireChat::indexRouteName()));
    });

    test('owner cannot exit conversation', function () {
        //    Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');
        $auth->sendMessageTo($conversation, 'hi');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('exitConversation')
            ->assertStatus(403, 'Owner cannot exit conversation');

        expect($auth->belongsToConversation($conversation))->toBe(true);
    });

    test('Throws error if user tries to exit priveat or self conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver, 'hello');

        // login as user not auth (Owner)
        Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('exitConversation')
            ->assertStatus(403, 'Cannot exit self or private conversation');

        expect($auth->belongsToConversation($conversation))->toBe(true);
    });

    describe('IsWidget:', function () {

        test('it does not redirects to chats route after Exiting Group conversation', function () {
            $auth = User::factory()->create();

            // create group
            $conversation = $auth->createGroup(name: 'New group', description: 'description');
            $auth->sendMessageTo($conversation, 'hi');

            // add user and exit conversation
            $user = User::factory()->create();
            $conversation->addParticipant($user);
            $user->sendMessageTo($conversation, 'hi');

            // login as user not auth (Owner)
            $request = Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('exitConversation')
                ->assertStatus(200)
                ->assertNoRedirect();
        });

        test('it dispatches "close-chat" evnt after Exiting Group conversation', function () {
            $auth = User::factory()->create();

            // create group
            $conversation = $auth->createGroup(name: 'New group', description: 'description');
            $auth->sendMessageTo($conversation, 'hi');

            // add user and exit conversation
            $user = User::factory()->create();
            $conversation->addParticipant($user);
            $user->sendMessageTo($conversation, 'hi');

            // login as user not auth (Owner)
            $request = Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('exitConversation')
                ->assertStatus(200)
                ->assertDispatched('close-chat');
        });
    });
});

describe('deleteMessage ForEveryone', function () {

    test('user cannot delete message that does not belong to them ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($otherUserMessage->id))
            ->assertStatus(403);

        $messageAvailable = Message::find($otherUserMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->not->toBe(null);
    });

    test('IN GROUP: Admin can delete message that does not belong to them ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $admin = User::factory()->create(['name' => 'User Admin']);

        $conversation = $auth->createGroup('My Group');

        // add admin
        $conversation->addParticipant($admin, ParticipantRole::ADMIN);

        // add participant and send messsage

        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($admin)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt('1'))
            ->assertStatus(200);

        $messageAvailable = Message::find('1');

        // /assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('deleted message is removed from blade', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert count 4
        $request->assertViewHas('loadedMessages', function ($messages) {
            return count($messages->flatten()) == 4;
        });

        // call deleteForMe
        $request->call('deleteForEveryone', encrypt($authMessage->id));

        // assert count no 3
        $request->assertViewHas('loadedMessages', function ($messages) {
            return count($messages->flatten()) == 3;
        });
    });
    test('it throws DecryptException if id is not Encrypted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert count 4
        $request->assertViewHas('loadedMessages', function ($messages) {
            return count($messages->flatten()) == 4;
        });

        // call deleteForMe
        $request->call('deleteForEveryone', $authMessage->id);

        // assert count no 3
        $request->asssertStatus(500);
    })->throws(\Illuminate\Contracts\Encryption\DecryptException::class);

    test('it does Not throws DecryptException if id is Encrypted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // call deleteForMe
        $request->call('deleteForEveryone', encrypt($authMessage->id));

        // assert count no 3
    })->throwsNoExceptions();

    test('deleted message is removed database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($authMessage->id));

        $messageAvailable = Message::find($authMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('it deletes attachment from database when message is deleted ', function () {

        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        $file[] = UploadedFile::fake()->image('photo.png');

        // run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            // add attachment
            ->set('media', $file)
            ->call('sendMessage');

        // /lets make sure atttachemnt is present in database

        expect(count(Attachment::all()))->toBe(1);

        // Now lets unsend message
        // here assuming that the message ID is 1 since it is the first one
        $request->call('deleteForEveryone', encrypt(1));

        // /assert attachment no longer avaible in database
        expect(count(Attachment::all()))->toBe(0);
    });

    test('it deletes attachment file from folder when message is deleted ', function () {

        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        $file[] = UploadedFile::fake()->image('photo.png');

        // run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            // add attachment
            ->set('media', $file)
            ->call('sendMessage');

        $attachmentModel = Attachment::first();
        $messageModel = Message::first();

        // Now lets unsend message
        // here assuming that the message ID is 1 since it is the first one
        $request->call('deleteForMe', encrypt($messageModel->id));

        Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->assertMissing($attachmentModel->file_name);
    });

    test('it disptaches refresh event and removes deleted message from chatlist', function () {

        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $CHATLIST = Livewire::actingAs($auth)->test(ChatList::class)->assertSee('This is message');

        // run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            // add attachment
            ->call('deleteForEveryone', encrypt(1))
            ->assertDispatched('refresh');

        // assert
        $CHATLIST->dispatch('refresh')->assertDontSee('This is message');
    });

    test('it will delete actual message but still show parent message when deleted ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // send reply
        $request->call('setReply', encrypt(1))->set('body', 'This is reply')->call('sendMessage');

        // assert messsage visible
        $request->assertSee('This is reply');

        // call deleteForMe
        $request->call('deleteForEveryone', encrypt('1'));

        // now assert still see 'This is message' message
        $request->assertSee('This is message');
    });

    test('it broadcasts event "NotifyParticipant" when sendLike is called', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($authMessage->id));

        Event::assertDispatched(MessageDeleted::class, function ($event) use ($authMessage) {
            return $event->message->id === $authMessage->id;
        });
    });
});

describe('deletForMe', function () {

    test('it throws DecryptException if message id is not Encrypted  ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', $otherUserMessage->id)
            ->assertStatus(500);

    })->throws(\Illuminate\Contracts\Encryption\DecryptException::class);

    test('it doesnt throws DecryptException if message id is Encrypted  ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($otherUserMessage->id))
            ->assertStatus(200);

    });

    test('user can delete-for-me message that does not belong to them ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($otherUserMessage->id))
            ->assertStatus(200);

        $messageAvailable = Message::find($otherUserMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('deleted message is removed from blade', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert count 4
        $request->assertViewHas('loadedMessages', function ($messages) {
            return count($messages->flatten()) == 4;
        });

        // call deleteForMe
        $request->call('deleteForMe', encrypt($authMessage->id));

        // assert count no 3
        $request->assertViewHas('loadedMessages', function ($messages) {
            return count($messages->flatten()) == 3;
        });
    });

    test('deleted message is not removed database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;

        // dd($conversation);
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($authMessage->id));

        $messageAvailable = Message::withoutGlobalScopes()->find($authMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->not->toBe(null);
    });

    test('it disptaches refresh event and removes deleted message from chatlist', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $CHATLIST = Livewire::actingAs($auth)->test(ChatList::class)->assertSee('This is message');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // call deleteForMe
        $request->call('deleteForMe', encrypt('1'))
            ->assertDispatched('refresh');

        // assert
        $CHATLIST->dispatch('refresh')->assertDontSee('This is message');
    });

    test('it will delete actual message but still show parent message when deleted ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // send reply
        $request->call('setReply', encrypt(1))->set('body', 'This is reply')->call('sendMessage');

        // assert messsage visible
        $request->assertSee('This is reply');

        // call deleteForMe
        $request->call('deleteForMe', encrypt('1'))->assertDispatched('refresh');

        // now assert still see 'This is message' message
        $request->assertSee('This is message');
    });
});
