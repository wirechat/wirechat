<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Wirechat\Wirechat\Enums\MessageType;
use Wirechat\Wirechat\Livewire\Chats\Chats as Chatlist;
use Wirechat\Wirechat\Livewire\Chats\Requests as RequestsDrawer;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\MessageRequest;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

// /Auth checks
it('checks if users is authenticated before loading chatlist', function () {
    Livewire::test(Chatlist::class)
        ->assertStatus(401);
});

test('authenticaed user can access chatlist ', function () {

    // Mutate the registered test panel

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertStatus(200);
});

test('it shows the requests drawer button in the chats header', function () {
    $auth = User::factory()->create();

    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertSeeHtml('id="open-requests-drawer-button"');
});

test('it hides the requests drawer button when message requests are disabled on the panel', function () {
    testPanelProvider()->messageRequests(false);

    $auth = User::factory()->create();

    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertDontSeeHtml('id="open-requests-drawer-button"');
});

test('pending request conversations stay out of the normal chats list', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $receiver = User::factory()->create(['name' => 'Pending User']);

    $auth->sendMessageRequestTo($receiver);

    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertDontSee('Pending User');
});

test('requests drawer shows its heading, description, tabs, and empty state', function () {
    $auth = User::factory()->create(['name' => 'Auth']);

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->assertSee(__('wirechat::chats.requests.heading'))
        ->assertSee(__('wirechat::chats.requests.labels.description'))
        ->assertSeeHtml('dusk="incoming-requests-tab"')
        ->assertSeeHtml('dusk="outgoing-requests-tab"')
        ->assertSee(__('wirechat::chats.requests.labels.empty_state'))
        ->assertSet('activeTab', 'incoming');
});

test('requests drawer is unavailable when message requests are disabled on the panel', function () {
    testPanelProvider()->messageRequests(false);

    $auth = User::factory()->create(['name' => 'Auth']);

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->assertNotFound();
});

test('tabs blade components render labels badges and content shells', function () {
    $html = Blade::render(<<<'BLADE'
        <x-wirechat::tabs.tabs label="Requests" dusk="requests-tabs">
            <x-wirechat::tabs.tab :active="true" :badge="3" dusk="incoming-tab">
                Incoming
            </x-wirechat::tabs.tab>

            <x-wirechat::tabs.tab :active="false">
                Outgoing
            </x-wirechat::tabs.tab>
        </x-wirechat::tabs.tabs>

        <x-wirechat::tabs.content :active="false" class="tab-panel">
            Panel body
        </x-wirechat::tabs.content>
    BLADE);

    $html = preg_replace('/\s+/', ' ', $html);

    expect($html)
        ->toContain('role="tablist"')
        ->toContain('aria-label="Requests"')
        ->toContain('dusk="incoming-tab"')
        ->toContain('Incoming')
        ->toContain('3')
        ->toContain('role="tabpanel"')
        ->toContain('aria-hidden="true"')
        ->toContain('hidden')
        ->toContain('Panel body');
});

test('icon component forwards custom classes to the rendered icon', function () {
    $html = Blade::render(<<<'BLADE'
        <x-wirechat::icon default="wirechat::icons.logout" class="size-4 text-red-500" />
    BLADE);

    expect($html)
        ->toContain('size-4')
        ->toContain('text-red-500');
});

test('dropdown trigger slot classes are applied to the trigger wrapper', function () {
    $html = Blade::render(<<<'BLADE'
        <x-wirechat::dropdown>
            <x-slot name="trigger" class="inline-flex items-center justify-center trigger-slot-test">
                <button type="button">Trigger</button>
            </x-slot>

            <x-slot name="content">
                Content
            </x-slot>
        </x-wirechat::dropdown>
    BLADE);

    expect($html)
        ->toContain('trigger-slot-test')
        ->toContain('Trigger')
        ->toContain('Content');
});

test('requests drawer defaults to the incoming tab when incoming requests exist', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $incomingSender = User::factory()->create(['name' => 'Incoming Sender']);
    $outgoingRecipient = User::factory()->create(['name' => 'Outgoing Recipient']);

    $incomingSender->sendMessageRequestTo($auth);
    $auth->sendMessageRequestTo($outgoingRecipient);

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->assertSet('activeTab', 'incoming')
        ->assertSee('Incoming Sender')
        ->assertDontSee('Outgoing Recipient');
});

test('requests drawer defaults to the outgoing tab when only outgoing requests exist', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $outgoingRecipient = User::factory()->create(['name' => 'Outgoing Recipient']);

    $auth->sendMessageRequestTo($outgoingRecipient);

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->assertSet('activeTab', 'outgoing')
        ->assertSee('Outgoing Recipient')
        ->assertDontSee(__('wirechat::chats.requests.labels.empty_state'));
});

test('requests drawer can switch between incoming and outgoing tabs', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $incomingSender = User::factory()->create(['name' => 'Incoming Sender']);
    $outgoingRecipient = User::factory()->create(['name' => 'Outgoing Recipient']);

    $incomingSender->sendMessageRequestTo($auth);
    $auth->sendMessageRequestTo($outgoingRecipient);

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->assertSet('activeTab', 'incoming')
        ->assertSee('Incoming Sender')
        ->assertDontSee('Outgoing Recipient')
        ->call('setActiveTab', 'outgoing')
        ->assertSet('activeTab', 'outgoing')
        ->assertSee('Outgoing Recipient')
        ->assertDontSee('Incoming Sender');
});

test('requests drawer shows a tab-specific empty state when the selected tab has no items', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $outgoingRecipient = User::factory()->create(['name' => 'Outgoing Recipient']);

    $auth->sendMessageRequestTo($outgoingRecipient);

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->assertSet('activeTab', 'outgoing')
        ->call('setActiveTab', 'incoming')
        ->assertSee(__('wirechat::chats.requests.labels.incoming_empty_state'))
        ->assertDontSee(__('wirechat::chats.requests.labels.empty_state'));
});

test('requests drawer redirects to the conversation in full-page mode', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $incomingSender = User::factory()->create(['name' => 'Incoming Sender']);

    $conversation = $incomingSender->sendMessageRequestTo($auth);
    $request = MessageRequest::query()->pending()->firstOrFail();

    Livewire::actingAs($auth)->test(RequestsDrawer::class)
        ->call('openConversation', $request->id)
        ->assertDispatched('closeChatListDrawer')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));
});

test('requests drawer opens the conversation in widget mode without redirecting', function () {
    $auth = User::factory()->create(['name' => 'Auth']);
    $incomingSender = User::factory()->create(['name' => 'Incoming Sender']);

    $incomingSender->sendMessageRequestTo($auth);
    $request = MessageRequest::query()->pending()->firstOrFail();

    Livewire::actingAs($auth)->test(RequestsDrawer::class, ['widget' => true])
        ->call('openConversation', $request->id)
        ->assertDispatched('closeChatListDrawer')
        ->assertDispatched('open-chat')
        ->assertNoRedirect();
});

test('it applies ui classes and styles to the chats shell only', function () {
    $auth = User::factory()->create();

    $response = Livewire::actingAs($auth)->test(Chatlist::class, [
        'class' => 'chats-shell-test',
        'styles' => [
            'min-height' => '20rem',
        ],
    ]);

    $html = $response->html();

    preg_match_all('/class="[^"]*chats-shell-test[^"]*"/', $html, $classMatches);
    preg_match_all('/style="min-height: 20rem;"/', $html, $styleMatches);

    expect($classMatches[0])->toHaveCount(1)
        ->and($styleMatches[0])->toHaveCount(1);
});

describe('Presence check', function () {

    // /Content validations
    it('has "chats heading set in chatlist" as defualt', function () {
        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('dusk="heading"')
            ->assertSet('heading', __('wirechat::chats.labels.heading'))
            ->assertSee(__('wirechat::chats.labels.heading'));
    });

    test('chat heading can be set directly on compoenent', function () {
        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['heading' => 'Messages'])
            ->assertSee('Messages')
            ->assertSeeHtml('dusk="heading"')
            ->assertDontSee(__('wirechat::chats.labels.heading'));
    });

    test('chat heading can be set directly on via provider', function () {
        testPanelProvider()->heading('new heading');

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('new heading')
            ->assertSeeHtml('dusk="heading"')
            ->assertDontSee(__('wirechat::chats.labels.heading'));
    });

    it('doesnt shows default heading when headiing  param is set to null at component level', function () {
        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['heading' => null])
            ->assertdontSee(__('wirechat::chats.labels.heading'))
            ->assertdontSeeHtml('dusk="heading"')
            ->assertNotset('heading', __('wirechat::chats.labels.heading'));
    });

    test('doesnt show heading but loads element  when set to empty string', function () {
        $auth = User::factory()->create();
        testPanelProvider()->heading('');

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee(__('wirechat::chats.labels.heading'))
            ->assertSeeHtml('dusk="heading"')
            ->assertset('heading', '');
    });

    it('shows_redirect_button', function () {
        testPanelProvider()->redirectToHomeAction();

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('id="redirect-button"');
    });

    it('shows_header ', function () {

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('dusk="header"');
    });

    it('shows DOESNT show header when createChatAction && chatsSearch && redirectToHomeAction are set false && heading is emtpy at component level', function () {

        $auth = User::factory()->create();

        Livewire::actingAs($auth)->test(Chatlist::class, [
            'createChatAction' => false,
            'chatsSearch' => false,
            'redirectToHomeAction' => false,
            'heading' => null,
        ])
            ->assertDontSeeHtml('dusk="header"');
    });

    it('shows DOESNT show header when panel values; createChatAction && chatsSearch && redirectToHomeAction are set false && heading is emtpy at Panel level', function () {

        $auth = User::factory()->create();

        testPanelProvider()
            ->chatsSearch(false)
            ->createChatAction(false)
            ->redirectToHomeAction(false)
            ->heading(null);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSeeHtml('dusk="header"');
    });

    it('doesnt shows search field if search is disabled in wirechat.config:tesiting Search placeholder', function () {

        //  Config::set('wirechat.allow_chats_search', false);
        testPanelProvider()->chatsSearch(false);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('Search')
            ->assertPropertyNotWired('search')
            ->assertDontSeeHtml('id="chats-search-field"');
    });

    it('doesnt shows search field if search MANUALLY disabled', function () {

        //  Config::set('wirechat.allow_chats_search', true);
        testPanelProvider()->chatsSearch(false);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('Search')
            ->assertPropertyNotWired('search')
            ->assertDontSeeHtml('id="chats-search-field"');
    });

    it('shows search field if search is enabled in wirechat.config.allow_chats_search Search placeholder', function () {

        //     Config::set('wirechat.allow_chats_search', true);
        testPanelProvider()->chatsSearch(true);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('Search')
            ->assertPropertyWired('search')
            ->assertSeeHtml('id="chats-search-field"');
    });

    it('shows search field even if search is DISABLED in wirechat.config.allow_chats_search but ENABLED at component level', function () {

        // Config::set('wirechat.allow_chats_search', false);
        testPanelProvider()->chatsSearch(false);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)->test(Chatlist::class, ['chatsSearch' => true])
            ->assertSee('Search')
            ->assertPropertyWired('search')
            ->assertSeeHtml('id="chats-search-field"');
    });

    test('it_shows_new_chat_modal_button_if_enabled_in_panel', function () {

        // Config::set('wirechat.show_new_chat_modal_button', true);

        testPanelProvider()->createChatAction();

        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->test(Chatlist::class)
            ->assertSeeHtml('id="open-new-chat-modal-button"');
    });

    test('if "createChatAction" DISABLED  at component level it doesnt shows_new_chat_modal_button event if enabled_in_panel', function () {

        //  Config::set('wirechat.show_new_chat_modal_button', true);

        testPanelProvider()->createChatAction();

        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->test(Chatlist::class, ['createChatAction' => false])
            ->assertDontSeeHtml('id="open-new-chat-modal-button"');
    });

    test('it_does_not_show_new_chat_modal_button_if_not_enabled_in_config', function () {

        testPanelProvider()->createChatAction(false);

        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->test(Chatlist::class)
            ->assertDontSeeHtml('id="open-new-chat-modal-button"');
    });

    test('if "createChatAction" ENABLED  at component level it still shows_new_chat_modal_button_if_not enabled_in_panel  ', function () {

        //    Config::set('wirechat.show_new_chat_modal_button', false);

        testPanelProvider()->createChatAction(false);

        $auth = User::factory()->create();
        Livewire::actingAs($auth)
            ->test(Chatlist::class, ['createChatAction' => true])
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
            ->assertSeeHtml('dusk="disappearing_messages_icon"');
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
            testPanelProvider()->redirectToHomeAction();

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => false])
                ->assertSeeHtml('id="redirect-button"');
        });

        test('it doesnt show redirect home button when chats is NOT widget and :redirectToHomeAction is false', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => false, 'redirectToHomeAction' => false])
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

        test('it still shows redirect home button when chats is widget but :redirectToHomeAction is true', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            $auth->sendMessageTo($conversation, 'hi');

            // dd($conversation);
            Livewire::actingAs($auth)->test(Chatlist::class, ['conversation' => $conversation->id, 'widget' => true, 'redirectToHomeAction' => true])
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
        $conversationWithJohn = $auth->createConversationWith($user1, 'hello');

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
        $conversationWithJohn = $auth->createConversationWith($user1, 'hello');

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
        $conversationWithJohn = $auth->createConversationWith($user1, 'hello');

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
            ->assertSee('(You)')
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

    it('reloads conversation ids when refresh is dispatched after a blank conversation gets its first message', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);

        // Load one existing non-blank conversation into the chat list ids.
        $auth->createConversationWith($user2, 'existing message');

        // Keep another conversation blank at first so it is excluded from the initial ids.
        $auth->createConversationWith($user1);

        $component = Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('existing message')
            ->assertDontSee('first message');

        // Sending to the same user reuses the existing blank conversation and gives it its first message.
        $auth->sendMessageTo($user1, 'first message');

        $component->dispatch('refresh')
            ->assertSee('first message')
            ->assertSee('iam user 1');
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

    it('shows unread message dot by default if message does not belong to user', function () {

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
            ->assertSeeHtml('data-show-unread-status="1"')
            ->assertSeeHtml('dusk="unreadMessagesDot"')
            ->assertDontSeeHtml('dusk="unreadMessagesCount"');
    });

    it('shows unread message count badge when panel unread type is count', function () {

        testPanelProvider()->unreadIndicator(type: UnreadIndicatorType::Count);

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        $response = Livewire::actingAs($auth)->test(Chatlist::class);
        $widgetResponse = Livewire::actingAs($auth)->test(Chatlist::class, ['widget' => true]);

        expect($response->html())
            ->toContain('dusk="unreadMessagesCount"')
            ->toMatch('/dusk="unreadMessagesCount"[\s\S]*?>\s*2\s*</')
            ->not->toContain('dusk="unreadMessagesDot"');

        expect($widgetResponse->html())
            ->toContain('dusk="unreadMessagesCount"')
            ->toMatch('/dusk="unreadMessagesCount"[\s\S]*?>\s*2\s*</')
            ->not->toContain('dusk="unreadMessagesDot"');
    });

    it('preloads unread message existence on conversations when panel unread indicator uses dot', function () {

        $auth = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $conversation = $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        /** @var \Illuminate\Support\Collection<int, Conversation> $conversations */
        $conversations = collect($component->instance()->conversations);
        $loadedConversation = $conversations->firstWhere('id', $conversation->id);

        expect($loadedConversation)->not->toBeNull()
            ->and((bool) $loadedConversation?->getAttribute('has_unread_messages'))->toBeTrue();
    });

    it('preloads unread message counts on conversations when panel unread type is count', function () {

        testPanelProvider()->unreadIndicator(type: UnreadIndicatorType::Count);

        $auth = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $conversation = $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        /** @var \Illuminate\Support\Collection<int, Conversation> $conversations */
        $conversations = collect($component->instance()->conversations);
        $loadedConversation = $conversations->firstWhere('id', $conversation->id);

        expect($loadedConversation)->not->toBeNull()
            ->and($loadedConversation?->getAttribute('unread_messages_count'))->toBe(2);
    });

    it('refreshes unread dot when refresh-chats event is dispatched after a new incoming message', function () {

        $auth = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $auth->createConversationWith($user1, message: 'How are you doing');

        $component = Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('data-show-unread-status="0"');

        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');

        $component->dispatch('refresh-chats')
            ->assertSeeHtml('data-show-unread-status="1"')
            ->assertSeeHtml('dusk="unreadMessagesDot"');
    });

    it('refreshes unread count badge when refresh-chats event is dispatched after a new incoming message', function () {

        testPanelProvider()->unreadIndicator(type: UnreadIndicatorType::Count);

        $auth = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $auth->createConversationWith($user1, message: 'How are you doing');

        $component = Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('data-show-unread-status="0"');

        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');

        $component->dispatch('refresh-chats')
            ->assertSeeHtml('data-show-unread-status="1"')
            ->assertSeeHtml('dusk="unreadMessagesCount"')
            ->assertSee('1');
    });

    it('shows unread indicator for another conversation while one chat is selected', function () {

        $auth = User::factory()->create();
        $userA = User::factory()->create(['name' => 'iam user a']);
        $userB = User::factory()->create(['name' => 'iam user b']);

        $conversationA = $auth->createConversationWith($userA, message: 'How are you doing');
        $conversationB = $auth->createConversationWith($userB, message: 'You there?');

        $component = Livewire::actingAs($auth)->test(Chatlist::class)
            ->set('selectedConversationId', (string) $conversationA->id)
            ->assertSeeHtml('data-show-unread-status="0"');

        sleep(1);
        $userB->sendMessageTo($auth, message: 'I am good');

        $component->dispatch('refresh-chats')
            ->assertSeeHtml('data-show-unread-status="1"')
            ->assertSeeHtml('dusk="unreadMessagesDot"');
    });

    it('keeps the unread dot visible when unread messages remain after auth sends the latest message', function () {

        $auth = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');
        sleep(1);
        $auth->sendMessageTo($user1, message: 'Replying now');

        $response = Livewire::actingAs($auth)->test(Chatlist::class);

        expect($response->html())
            ->toContain('dusk="unreadMessagesDot"')
            ->not->toContain('dusk="unreadMessagesCount"');
    });

    it('keeps the unread count badge visible when unread messages remain after auth sends the latest message', function () {

        testPanelProvider()->unreadIndicator(type: UnreadIndicatorType::Count);

        $auth = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');
        sleep(1);
        $auth->sendMessageTo($user1, message: 'Replying now');

        $response = Livewire::actingAs($auth)->test(Chatlist::class);

        expect($response->html())
            ->toContain('dusk="unreadMessagesCount"')
            ->toMatch('/dusk="unreadMessagesCount"[\s\S]*?>\s*2\s*</');
    });
    it('uses reactive preview classes so unread text de-emphasizes immediately when a chat is opened', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        $user1->sendMessageTo($auth, message: 'I am good');

        $html = Livewire::actingAs($auth)->test(Chatlist::class)->html();

        expect($html)
            ->toContain('dusk="messagePreviewBody"')
            ->toContain('dusk="messagePreviewTime"')
            ->toContain('font-semibold text-black')
            ->toContain('font-medium text-gray-800')
            ->not->toContain('showUnreadStatus && !false');
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
            ->assertSeeHtml('data-show-unread-status="0"');
    });

    it('still shows unread message Dot even if message belongs to Participant of Different Model', function () {

        $auth = User::factory()->create();

        $user1 = Admin::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        // sleep(1);
        // here we delay the create messsage so that we can NOT have both messages with the same timestamp
        // now let's send message to auth
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');

        // dd($conversations,$messages);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeHtml('data-show-unread-status="1"')
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
            ->assertSeeHtml('data-show-unread-status="0"');
    });

    it('shows message time AS "now"  if less than a minute old', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($user1);
        $participant = $conversation->participant($auth);

        Carbon::setTestNowAndTimezone(now());
        $lastMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'How are you doing',
        ]);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSeeText(__('wirechat::chats.labels.now'));
    });

    it('shows message time AS "shortAbsoluteDiffForHumans"  if more than a minute old', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($user1);
        $participant = $conversation->participant($auth);

        Carbon::setTestNowAndTimezone(now());
        $lastMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'How are you doing',
        ]);

        Carbon::setTestNowAndTimezone(now()->addMinute(2));

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSeeText('now')
            ->assertSeeText($lastMessage->created_at->shortAbsoluteDiffForHumans());
    });

    it('translates short absolute diff for humans when the locale is configured globally', function () {
        $originalLocale = app()->getLocale();
        $originalCarbonLocale = Carbon::getLocale();

        try {
            app()->setLocale('tr');
            Carbon::setLocale('tr');

            $auth = User::factory()->create();
            $user1 = User::factory()->create(['name' => 'iam user 1']);

            $conversation = $auth->createConversationWith($user1);
            $participant = $conversation->participant($auth);

            Carbon::setTestNowAndTimezone(now());
            $lastMessage = Message::create([
                'conversation_id' => $conversation->id,
                'participant_id' => $participant->id,
                'body' => 'How are you doing',
            ]);

            Carbon::setTestNowAndTimezone(now()->addHours(3));

            Livewire::actingAs($auth)->test(Chatlist::class)
                ->assertSeeText($lastMessage->created_at->shortAbsoluteDiffForHumans());
        } finally {
            Carbon::setTestNow();
            app()->setLocale($originalLocale);
            Carbon::setLocale($originalCarbonLocale);
        }
    });

    it('it shows attatchment lable if message contains file or image', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($user1);
        $participant = $conversation->participant($auth);

        // manually create message so we can attach attachment id
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
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

describe('Cursor pagination', function () {

    it('starts with empty conversationIds, null cursor and canLoadMore=false when user has no conversations', function () {
        $auth = User::factory()->create();

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSet('conversationIds', [])
            ->assertSet('cursorUpdatedAt', null)
            ->assertSet('cursorCreatedAt', null)
            ->assertSet('cursorId', null)
            ->assertSet('canLoadMore', false);
    });

    it('populates conversationIds and advances cursor after initial load', function () {
        $auth = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            $auth->createConversationWith($user, "message $i");
        }

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        $ids = $component->get('conversationIds');
        expect($ids)->toHaveCount(3);

        $component
            ->assertNotSet('cursorUpdatedAt', null)
            ->assertNotSet('cursorCreatedAt', null)
            ->assertNotSet('cursorId', null);
    });

    it('loads conversations ordered by most recently updated first', function () {
        $auth = User::factory()->create();

        // Create two conversations with different created_at but the same updated_at timestamp
        $baseTime = now();

        Carbon::setTestNow($baseTime->copy()->subSeconds(21));
        $user1 = User::factory()->create();
        $conv1 = $auth->createConversationWith($user1, 'message 1');

        Carbon::setTestNow($baseTime->copy()->subSeconds(20));
        $user2 = User::factory()->create();
        $conv2 = $auth->createConversationWith($user2, 'message 2');

        // Force conv1 to share the same updated_at as conv2 so created_at is the tiebreaker
        $conv1->forceFill(['updated_at' => $baseTime->copy()->subSeconds(20)])->saveQuietly();

        // More recent conversation; should always appear first
        Carbon::setTestNow($baseTime->copy()->subSeconds(10));
        $user3 = User::factory()->create();
        $conv3 = $auth->createConversationWith($user3, 'message 3');

        Carbon::setTestNow();

        $component = Livewire::actingAs($auth)->test(Chatlist::class);
        $ids = $component->get('conversationIds');

        // Most recently updated conversation should appear first
        expect($ids[0])->toBe($conv3->id)
            // When updated_at is the same, the more recently created conversation comes first (created_at DESC tiebreaker)
            ->and($ids[1])->toBe($conv2->id)
            ->and($ids[2])->toBe($conv1->id);
    });

    it('uses id DESC as third tie-breaker when updated_at and created_at are equal', function () {
        $auth = User::factory()->create();

        $baseTime = now()->subSeconds(10);

        // Create three conversations all sharing the same updated_at and created_at
        Carbon::setTestNow($baseTime);
        $user1 = User::factory()->create();
        $conv1 = $auth->createConversationWith($user1, 'message 1');

        Carbon::setTestNow($baseTime);
        $user2 = User::factory()->create();
        $conv2 = $auth->createConversationWith($user2, 'message 2');

        Carbon::setTestNow($baseTime);
        $user3 = User::factory()->create();
        $conv3 = $auth->createConversationWith($user3, 'message 3');

        // Force all three to share the same updated_at and created_at
        $sharedTimestamp = $baseTime->copy();
        foreach ([$conv1, $conv2, $conv3] as $conv) {
            $conv->forceFill([
                'updated_at' => $sharedTimestamp,
                'created_at' => $sharedTimestamp,
            ])->saveQuietly();
        }

        Carbon::setTestNow();

        $component = Livewire::actingAs($auth)->test(Chatlist::class);
        $ids = $component->get('conversationIds');

        // When all three timestamps are equal, id DESC should be the tie-breaker
        $sortedByIdDesc = collect([$conv1->id, $conv2->id, $conv3->id])
            ->sortDesc()
            ->values()
            ->all();

        expect($ids)->toBe($sortedByIdDesc);
    });

    it('appends new IDs on loadMore without reshuffling existing ones', function () {
        $auth = User::factory()->create();

        $baseTime = now();

        for ($i = 0; $i < 12; $i++) {
            Carbon::setTestNow($baseTime->copy()->subSeconds(120 - $i));
            $user = User::factory()->create();
            $auth->createConversationWith($user, "message $i");
        }
        Carbon::setTestNow();

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        $firstPageIds = $component->get('conversationIds');
        expect($firstPageIds)->toHaveCount(10);

        $component->call('loadMore');

        $allIds = $component->get('conversationIds');
        expect($allIds)->toHaveCount(12);

        // First 10 items must remain in the exact same positions
        foreach ($firstPageIds as $index => $id) {
            expect($allIds[$index])->toBe($id);
        }
    });

    it('does not change conversationIds when loadMore is called but canLoadMore is false', function () {
        $auth = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            $auth->createConversationWith($user, "message $i");
        }

        $component = Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSet('canLoadMore', false);

        $idsBefore = $component->get('conversationIds');

        $component->call('loadMore');

        expect($component->get('conversationIds'))->toBe($idsBefore);
    });

    it('removes conversation from conversationIds when chat-deleted event fires', function () {
        $auth = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation1 = $auth->createConversationWith($user1, 'hello');
        $conversation2 = $auth->createConversationWith($user2, 'world');

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        expect($component->get('conversationIds'))->toContain($conversation1->id);

        $component->dispatch('chat-deleted', $conversation1->id);

        $updatedIds = $component->get('conversationIds');
        expect($updatedIds)->not->toContain($conversation1->id)
            ->and($updatedIds)->toContain($conversation2->id);
    });

    it('restarts pagination from beginning when hardRefresh is called after loading all conversations', function () {
        $auth = User::factory()->create();

        $baseTime = now();

        for ($i = 0; $i < 12; $i++) {
            Carbon::setTestNow($baseTime->copy()->subSeconds(120 - $i));
            $user = User::factory()->create();
            $auth->createConversationWith($user, "message $i");
        }
        Carbon::setTestNow();

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        // First page: 10 loaded, can load more
        expect($component->get('conversationIds'))->toHaveCount(10);
        $component->assertSet('canLoadMore', true);

        // Load all remaining conversations
        $component->call('loadMore');
        expect($component->get('conversationIds'))->toHaveCount(12);
        $component->assertSet('canLoadMore', false);

        // hardRefresh resets cursor and restarts from first page
        $component->call('hardRefresh');
        expect($component->get('conversationIds'))->toHaveCount(10);
        $component->assertSet('canLoadMore', true);
    });

    it('restarts pagination from beginning when search is updated', function () {
        $auth = User::factory()->create();

        $baseTime = now();

        for ($i = 0; $i < 12; $i++) {
            Carbon::setTestNow($baseTime->copy()->subSeconds(120 - $i));
            $user = User::factory()->create();
            $auth->createConversationWith($user, "message $i");
        }
        Carbon::setTestNow();

        $component = Livewire::actingAs($auth)->test(Chatlist::class);

        // Advance to last page so cursor is deep
        $component->call('loadMore');
        expect($component->get('conversationIds'))->toHaveCount(12);
        $component->assertSet('canLoadMore', false);

        // Updating search resets cursor via hardRefresh; no match → cursor stays null
        $component->set('search', 'xyznonexistent');

        $component
            ->assertSet('conversationIds', [])
            ->assertSet('cursorUpdatedAt', null)
            ->assertSet('cursorCreatedAt', null)
            ->assertSet('cursorId', null)
            ->assertSet('canLoadMore', false);
    });
});

describe('Search', function () {

    it('it shows all conversations items when search query is null', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'John']);
        $user2 = User::factory()->create(['name' => 'Mary']);

        // create conversation with user1
        $conversationWithJohn = $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $conversationWithMary = $auth->createConversationWith($user2, 'how are you doing');

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
        $conversationWithJohn = $auth->createConversationWith($user1, 'hello');

        // create conversation with user2
        $conversationWithMary = $auth->createConversationWith($user2, 'how are you doing');

        $request = Livewire::actingAs($auth)->test(Chatlist::class);

        $request->set('search', 'John');

        $request->assertSee('John');
        $request->assertViewHas('conversations', function ($conversations) use ($conversationWithJohn, $conversationWithMary) {
            $ids = $conversations->pluck('id')->all();

            return count($ids) === 1
                && in_array($conversationWithJohn->id, $ids, true)
                && ! in_array($conversationWithMary->id, $ids, true);
        });
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
