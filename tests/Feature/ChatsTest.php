<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Enums\MessageType;
use Namu\WireChat\Livewire\Chats\Chats as Chatlist;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

// /Auth checks
it('checks if users is authenticated before loading chatlist', function () {
    Livewire::test(Chatlist::class)
        ->assertStatus(401);
});

test('authenticaed user can access chatlist ', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertStatus(200);
});

describe('Presence check', function () {

    // /Content validations
    it('has "chats title set in chatlist" as defualt', function () {
        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('dusk="title"')
            ->assertSet('title', 'Chats')
            ->assertSee('Chats');
    });

    test('chat title can be set manually', function () {
        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['title' => 'Messages'])
            ->assertSee('Messages')
            ->assertSeeHtml('dusk="title"')
            ->assertDontSee('Chats');
    });

    test('chat title can be set to Null', function () {
        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['title' => null])
            ->assertDontSee('Chats')
            ->assertDontSeeHtml('dusk="title"')
            ->assertset('title', null);
    });

    it('shows_redirect_button', function () {

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('id="redirect-button"');
    });

    it('shows_header ', function () {

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('dusk="header"');
    });

    it('shows DOESNT show header when showNewChatModalButton && allowChatsSearch && showHomeRouteButton are false && title is NULL ', function () {

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, [
            'showNewChatModalButton' => false,
            'allowChatsSearch' => false,
            'showHomeRouteButton' => false,
            'title' => null,
        ])
            ->assertDontSeeHtml('dusk="header"');
    });

    it('doesnt shows search field if search is disabled in wirechat.config:tesiting Search placeholder', function () {

        Config::set('wirechat.allow_chats_search', false);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('Search')
            ->assertPropertyNotWired('search')
            ->assertDontSeeHtml('id="chats-search-field"');
    });

    it('doesnt shows search field if search MANUALLY disabled at widget level even if in wirechat.config.allow_chats_search is true', function () {

        Config::set('wirechat.allow_chats_search', true);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['allowChatsSearch' => false])
            ->assertDontSee('Search')
            ->assertPropertyNotWired('search')
            ->assertDontSeeHtml('id="chats-search-field"');
    });

    it('shows search field if search is enabled in wirechat.config.allow_chats_search Search placeholder', function () {

        Config::set('wirechat.allow_chats_search', true);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('Search')
            ->assertPropertyWired('search')
            ->assertSeeHtml('id="chats-search-field"');
    });

    it('shows search field even if search is DISABLED in wirechat.config.allow_chats_search but ENABLED at component level', function () {

        Config::set('wirechat.allow_chats_search', false);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['allowChatsSearch' => true])
            ->assertSee('Search')
            ->assertPropertyWired('search')
            ->assertSeeHtml('id="chats-search-field"');
    });

    test('it_shows_new_chat_modal_button_if_enabled_in_config', function () {

        Config::set('wirechat.show_new_chat_modal_button', true);
        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->test(Chatlist::class)
            ->assertSeeHtml('id="open-new-chat-modal-button"');
    });

    test('if "showNewChatModalButton" DISABLED  at component level it doesnt shows_new_chat_modal_button event if enabled_in_config', function () {

        Config::set('wirechat.show_new_chat_modal_button', true);
        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->test(Chatlist::class, ['showNewChatModalButton' => false])
            ->assertDontSeeHtml('id="open-new-chat-modal-button"');
    });

    test('it_does_not_show_new_chat_modal_button_if_not_enabled_in_config', function () {

        Config::set('wirechat.show_new_chat_modal_button', false);
        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->test(Chatlist::class)
            ->assertDontSeeHtml('id="open-new-chat-modal-button"');
    });

    test('if "showNewChatModalButton" ENABLED  at component level it still shows_new_chat_modal_button_if_not enabled_in_config  ', function () {

        Config::set('wirechat.show_new_chat_modal_button', false);
        $auth = User::factory()->create();
        Livewire::actingAs($auth)
            ->test(Chatlist::class, ['showNewChatModalButton' => true])
            ->assertSeeHtml('id="open-new-chat-modal-button"');
    });

    test('it_shows_load_more_button_if_user_can_load_more', function () {

        $auth = User::factory()->create();

        for ($i = 0; $i < 12; $i++) {

            $user = User::factory()->create();

            $auth->createConversationWith($user, 'hello');
        }

        // dd($conversation);
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('Load more')
            ->assertSeeHtml('dusk="loadMoreButton"');
    });

    test('it_does_not_show_load_more_button_if_user_cannot_load_more', function () {

        $auth = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {

            $user = User::factory()->create();

            $auth->createConversationWith($user, 'hello');
        }

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('Load more')
            ->assertDontSeeHtml('dusk="loadMoreButton"');
    });
    test('it shows dusk="disappearing_messages_icon" if disappearingTurnedOn for conversation', function () {

        $auth = User::factory()->create(['name' => 'Namu']);

        Carbon::setTestNowAndTimezone(now());
        $conversation = $auth->createGroup('My Group');

        $auth->sendMessageTo($conversation, 'hi');

        // turn on disappearing

        $conversation->turnOnDisappearing(3600);

        // dd($conversation->hasDisappearingTurnedOn());
        Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('dusk="disappearing_messages_icon" ');
    });

    test('it doesnt shows dusk="disappearing_messages_icon" if disappearingTurnedOFF for conversation', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        $auth->sendMessageTo($conversation, 'hi');

        // turn on disappearing
        $conversation->turnOffDisappearing();

        // dd($conversation);
        Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="disappearing_messages_icon"');
    });

    describe('IsWidget', function () {

        test('it doesnt  have dispatch "openChatWidget" when chats is not widget', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => false])
                ->assertDontSeeHtml('dusk="openChatWidgetButton"');
        });

        test('it  has dispatches "openChatWidget"when chats is widget', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => true])
                ->assertSeeHtml('dusk="openChatWidgetButton"');
        });

        test('it shows redirect home button when chats is NOT widget', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => false])
                ->assertSeeHtml('id="redirect-button"');
        });

        test('it doesnt show redirect home button when chats is NOT widget and :showHomeRouteButton is false', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => false, 'showHomeRouteButton' => false])
                ->assertDontSeeHtml('id="redirect-button"');
        });

        test('it doesnt shows redirect home button when chats is widget', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => true])
                ->assertDontSeeHtml('id="redirect-button"');
        });

        test('it still shows redirect home button when chats is widget but :showHomeRouteButton is true', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => true, 'showHomeRouteButton' => true])
                ->assertSeeHtml('id="redirect-button"');
        });

    });
});

describe('List', function () {

    it('shows label "No conversations yet" items when user does not have chats', function () {

        $auth = User::factory()->create();

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('No conversations yet');
    });

    it('loads conversations items when user has them', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);

        // create conversation with user1
        $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $auth->createConversationWith($user2, 'new message');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 2;
            });
    });

    it('shows chats names when conversations are loaded to Chats component ', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);

        // create conversation with user1
        $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $auth->createConversationWith($user2, 'new message');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('iam user 1')
            ->assertSee('iam user 2');
    });

    it('shows chats names when conversations of Mixed Participant Models are loaded to Chats component ', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = Admin::factory()->create(['name' => 'iam Admin']);

        // create conversation with user1
        $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $auth->createConversationWith($user2, 'new message');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('iam user 1')
            ->assertSee('iam Admin');
    });

    it('shows suffix (sender name ) if conversation is group and message does not belong to auth', function () {

        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        $participant->sendMessageTo($conversation, 'Hello');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('John:');
    });

    it('it shows group name if conversation is group', function () {

        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        $participant->sendMessageTo($conversation, 'Hello');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('My Group');
    });

    it('shows suffix Name (You) if user has a self conversation', function () {

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $auth->createConversationWith($auth, 'hello');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('Test')
            ->assertSee(__('You'))
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });

    it('does not load blank conversations(where not even deleted messages exists)', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);

        // !create BLANK conversation with user1
        $auth->createConversationWith($user1);

        // create conversation with user2
        $auth->createConversationWith($user2, 'new message');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('iam user 1') // Blank conversation should not load
            ->assertSee('iam user 2')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });

    it('does not load deleted conversations by user', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);

        // create conversation with user1
        $auth->createConversationWith($user1, 'nothing');

        // create conversation with user2
        $conversationToBeDeleted = $auth->createConversationWith($user2, 'nothing 2');

        // !now delete conversation with user 2
        $auth->deleteConversation($conversationToBeDeleted);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('iam user 1')
            ->assertDontSee('iam user 2')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });

    it('it shows last message and lable "you:" if it exists in chatlist', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('How are you doing')
            ->assertSee('You:');
    });

    it('it doesnt show label "you:" if last message doenst belong to auth', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        // here we delay the create messsage so that we can NOT have both messages with the same timestamp
        // now let's send message to auth
        $user1->sendMessageTo($auth, message: 'I am good');

        // dd($conversations,$messages);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('I am good') // see message
            ->assertDontSee('You:'); // assert not visible
    });

    it('shows unread message count "2" if message does not belong to user', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        // here we delay the create messsage so that we can NOT have both messages with the same timestamp
        // now let's send message to auth
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        // dd($conversations,$messages);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('dusk="unreadMessagesDot"');
    });
    it('Doesnt show unread message Dot if message does not belong to Auth and is Read', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        Carbon::setTestNowAndTimezone(now()->subSeconds(10));
        // create conversation with user1
        $conversation = $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        // here we delay the create messsage so that we can NOT have both messages with the same timestamp
        // now let's send message to auth
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        // reset time
        Carbon::setTestNowAndTimezone();
        $conversation->markAsRead($auth);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSeeHtml('dusk="unreadMessagesDot"');
    });

    it('still shows unread message Dot even if message belongs to Participant of Different Model', function () {

        $auth = User::factory()->create();

        $user1 = Admin::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        // here we delay the create messsage so that we can NOT have both messages with the same timestamp
        // now let's send message to auth
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        // dd($conversations,$messages);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('dusk="unreadMessagesDot"');
    });

    it('Doesnt shows unread message Dot if message is READ and belongs to Participant of Different Model', function () {

        $auth = User::factory()->create();
        $user1 = Admin::factory()->create(['name' => 'iam user 1']);

        // Set the initial time for the first message
        Carbon::setTestNow(now()->subSeconds(20));

        $conversation = $auth->createConversationWith($user1, message: 'How are you doing');

        $user1->sendMessageTo($auth, message: 'I am good');

        // Set the time for marking the conversation as read
        Carbon::setTestNow(now()->addSeconds(5));

        $conversation->markAsRead($auth);

        // Reset the time to the current moment
        Carbon::setTestNow();

        // Check unread message count
        $unreadCount = $conversation->getUnreadCountFor($auth);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSeeHtml('dusk="unreadMessagesDot"');
    });

    it('shows message time AS "now"  if less than a minute old', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($user1);

        Carbon::setTestNowAndTimezone(now());
        $lastMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'How are you doing',
        ]);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeText('now');
    });

    it('shows message time AS "shortAbsoluteDiffForHumans"  if more than a minute old', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($user1);

        Carbon::setTestNowAndTimezone(now());
        $lastMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'body' => 'How are you doing',
        ]);

        Carbon::setTestNowAndTimezone(now()->addMinute(2));

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSeeText('now')
            ->assertSeeText($lastMessage->created_at->shortAbsoluteDiffForHumans());
    });

    it('it shows attatchment lable if message contains file or image', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($user1);

        // manually create message so we can attach attachment id
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sendable_type' => get_class($auth),
            'sendable_id' => $auth->id,
            'type' => MessageType::ATTACHMENT,
        ]);

        $createdAttachment = Attachment::factory()->for($message, 'attachable')->create();

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('📎 Attachment');
    });

    test('deleted conversation should not appear in user chats list', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(1));
        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        // delete conversation
        Carbon::setTestNow(now()->addSeconds(4));
        $auth->deleteConversation($conversation);

        // start component
        $request = Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('John')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 0;
            });
    });
});

describe('Search', function () {

    it('it shows all conversations items when search query is null', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'John']);
        $user2 = User::factory()->create(['name' => 'Mary']);

        // create conversation with user1
        $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $auth->createConversationWith($user2, 'how are you doing');

        Livewire::actingAs($auth)->test(Chatlist::class, ['search' => null])
            ->assertSee('John')
            ->assertSee('Mary')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 2;
            });
    });

    it('can filter conversations when search query is filled', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'John']);
        $user2 = User::factory()->create(['name' => 'Mary']);

        // create conversation with user1
        $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $auth->createConversationWith($user2, 'how are you doing');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->set('search', 'John')
            ->assertSee('John')
            ->assertDontSee('Mary');

    });

    test('deleted conversation should  appear when searched', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        // delete conversation
        $auth->deleteConversation($conversation);

        // start component & search
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->set('search', 'John')
            ->assertSee('John')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });
});
