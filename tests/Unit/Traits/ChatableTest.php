<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\GroupType;
use Namu\WireChat\Enums\MessageType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Group;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

describe('Getting conversations', function () {

    it('returns  correct conversations belonging to user', function () {

        $auth = User::factory()->create();
        //  dd($auth);

        $conversations = Conversation::factory(3)->withParticipants([$auth])->create();

        //  dd($conversations);
        // assert conversation belongs to user
        foreach ($conversations as $key => $conversation) {

            $conversationExists = $conversation->participants()
                ->where('participantable_id', $auth->id)
                ->where('participantable_type', get_class($auth))
                ->exists();
            expect($conversationExists)->toBe(true);
        }
    });

    it('returns correct number of conversations for user', function () {

        $auth = User::factory()->create();

        $conversations = Conversation::factory(3)->withParticipants([$auth])->create();

        // assert count
        expect(count($conversations))->toBe(3);

    });

});

describe('createConversationWith() ', function () {

    it('creates & returns created conversation when createConversationWith is called', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // assert
        $conversation = $auth->createConversationWith($receiver);
        // assert
        expect($conversation)->not->toBe(null);

        expect($conversation)->toBeInstanceOf(Conversation::class);
        // check database
        $conversation = Conversation::first();
        expect($conversation)->not->toBe(null);

    });

    it('aborts if canCreateNewChats() ==FALSE(in our case when user not verified)', function () {

        $auth = User::factory()->create(['email_verified_at' => null]);
        $receiver = User::factory()->create();

        // action
        $auth->createConversationWith($receiver);

        // check database
        $conversation = Conversation::withoutGlobalScopes()->get();
        expect($conversation)->toBe(null);

    })->throws(Exception::class, 'You do not have permission to create chats.');

    it('creates 2 participants for conversation when created', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        // Eager load the participants relationship

        $conversation = Conversation::find($conversation->id);

        // check database
        expect(count($conversation->participants))->toBe(2);

    });

    it('It saves role as owner for both paritipants', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        // Eager load the participants relationship

        $conversation = Conversation::find($conversation->id);

        $bothAreOwners = false;

        foreach ($conversation->participants as $key => $value) {

            $bothAreOwners = $value->role == ParticipantRole::OWNER;
            // code...
        }

        // check database
        expect($bothAreOwners)->toBe(true);

    });

    it('saved correct type and id in participants model', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        // Eager load the participants relationship

        $conversation = Conversation::find($conversation->id);

        // assert partipant $auth
        expect($conversation->participants()
            ->where('participantable_id', $auth->id)
            ->where('participantable_type', get_class($auth))
            ->exists())->toBe(true);

        // assert partipant $receiver
        expect($conversation->participants()
            ->where('participantable_id', $receiver->id)
            ->where('participantable_type', get_class($receiver))
            ->exists())->toBe(true);

    });

    test('The created conversation must be PRIVATE', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        // check database
        expect($conversation->type)->toBe(ConversationType::PRIVATE);

    });

    it('does not create double conversations if conversation already exists between two users', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation attempt 1
        $conversation1 = $auth->createConversationWith($receiver);

        // create conversation attempt 2
        $conversation2 = $receiver->createConversationWith($auth);
        expect($conversation2->id)->toBe($conversation1->id);

        // assert $auth and $receiver only has one conversation

        expect(count($auth->conversations))->toBe(1);

        expect(count($receiver->conversations))->toBe(1);

    });

    it('it creates message model when a message is passed ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver, message: 'Hello');

        // assert
        expect(count($conversation->messages))->toBe(1);

    });

    test('user can create conversation with themselves and participant must be 1 ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($auth, message: 'Hello');

        // Eager load the participants relationship

        $conversation = Conversation::find($conversation->id);

        $participants = $conversation->participants;

        expect(count($participants))->toBe(1);

        foreach ($participants as $key => $participant) {

            expect($participant->participantable_id)->toBe($auth->id);
            expect($participant->participantable_type)->toBe(get_class($auth));

        }

    });

    it('it does not create duplicate conversation is conversation already exists between same user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($auth, message: 'Hello');
        $conversation = $auth->createConversationWith($auth);
        $conversation = $auth->createConversationWith($auth);

        // Eager load the participants relationship
        expect(count(Conversation::all()))->toBe(1);

    });

});

describe('sendMessageTo() ', function () {

    it('aborts 403 is $model not extenting Chatable Trait ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        $participant = $conversation->participant($auth);

        // pass participant model - which does not use Triat Chatable
        $auth->sendMessageTo($participant, 'hello');

    })->throws(Exception::class, 'The provided model does not support chat functionality.');

    it('aborts 403 is user does not belong to conversation ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        $randomUser = User::factory()->create();
        $randomUserConversation = $randomUser->createConversationWith(User::factory()->create());

        // pass participant model - which does not use Triat Chatable
        $auth->sendMessageTo($randomUserConversation, 'hello');

    })->throws(Exception::class, 'You do not have access to this conversation.');

    it('can send message conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        $message = $auth->sendMessageTo($conversation, 'hello');

        // assert
        expect($message)->not->toBe(null);

        // check database
        $messageFromDB = Message::find($message->id);

        // assert content
        expect($messageFromDB->id)->toBe($message->id);
        expect($messageFromDB->body)->toBe($message->body);
        expect($messageFromDB->conversation_id)->toBe($conversation->id);

    });

    it('can send message Model', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $message = $auth->sendMessageTo($receiver, 'hello');

        // assert
        expect($message)->not->toBe(null);

        // check database
        $messageFromDB = Message::find($message->id);

        // assert content
        expect($messageFromDB->id)->toBe($message->id);
        expect($messageFromDB->body)->toBe($message->body);

    });

    it('saves defualt type as text', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $message = $auth->sendMessageTo($receiver, 'hello');

        // assert
        expect($message)->not->toBe(null);

        // check database
        $messageFromDB = Message::find($message->id);

        // assert content
        expect($messageFromDB->type)->toBe(MessageType::TEXT);

    });

    it('creates new conversation if it didn\'t alrady exist between the two users ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $message = $auth->sendMessageTo($receiver, 'hello');
        // assert

        $conversation = Conversation::first();

        // assert conversation id
        expect($conversation)->not->toBe(null);

        // assert conversation id
        expect($message->conversation_id)->toBe($conversation->id);

    });

    it('creates & returns created message when sendMessageTo is called', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $message = $auth->sendMessageTo($receiver, 'hello');
        // assert
        expect($message)->not->toBe(null);

        expect($message)->toBeInstanceOf(Message::class);
        // check database
        $conversation = Conversation::first();
        expect($conversation)->not->toBe(null);

    });

    it('saves created message to database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $message = $auth->sendMessageTo($receiver, 'hello');

        // assert
        expect($message)->not->toBe(null);

        // check database
        $messageFromDB = Message::find($message->id);

        // assert content
        expect($messageFromDB->id)->toBe($message->id);
        expect($messageFromDB->body)->toBe($message->body);

    });

    test('created message belongs to correct conversation ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        // send message

        $message = $auth->sendMessageTo($receiver, 'hello');

        expect($message->conversation_id)->toBe($conversation->id);

    });

    it('updates the conversation updated_at field when message is created', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        // we use sleep to avoid timestamps being the same during test
        sleep(1);

        $auth->sendMessageTo($receiver, 'hello');

        $conversationFromDB = Conversation::find($conversation->id);

        expect($conversationFromDB->updated_at)->toBeGreaterThan($conversation->updated_at);

    });

    it('updates the auth particiapnt  last_active_at field when message is created', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation
        $conversation = $auth->createConversationWith($receiver);

        $participant = $conversation->participant($auth);
        expect($participant->last_active_at)->toBe(null);

        // we use sleep to avoid timestamps being the same during test
        sleep(1);

        Carbon::setTestNow(now()->addSeconds(3));

        $auth->sendMessageTo($receiver, 'hello');

        $participant->refresh();

        expect($participant->last_active_at)->not->toBe(null);

    });

});

describe('belongsToConversation() ', function () {

    it('returns false if user does not belong to conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation = $auth->createConversationWith($receiver);

        // create conversation
        $randomUser = User::factory()->create();

        // assert
        expect($randomUser->belongsToConversation($conversation))->toBe(false);

    });

    it('returns true if user belongs to conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation = $auth->createConversationWith($receiver);

        // assert
        expect($auth->belongsToConversation($conversation))->toBe(true);

    });

    it('returns false if user exits conversation', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('Test', 'hello');

        // add member
        $receiver = User::factory()->create();
        $participant = $conversation->addParticipant($receiver);

        expect($receiver->belongsToConversation($conversation))->toBe(true);

        // exit conversation
        $participant->exitConversation();
        // assert
        expect($receiver->belongsToConversation($conversation))->toBe(false);

    });

    it('returns false if user is removed from Group by admin', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('Test', 'hello');

        // add member
        $receiver = User::factory()->create();
        $participant = $conversation->addParticipant($receiver);

        expect($receiver->belongsToConversation($conversation))->toBe(true);

        // exit conversation

        $participant->removeByAdmin($auth);
        // assert
        expect($receiver->belongsToConversation($conversation))->toBe(false);

    });

});

describe('hasConversationWith() ', function () {

    it('returns false if user does not have conversation with another user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation = $auth->createConversationWith($receiver);

        // create conversation
        $randomUser = User::factory()->create();

        // assert
        expect($randomUser->hasConversationWith($auth))->toBe(false);

    });

    it('returns true if user has conversation with another user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation = $auth->createConversationWith($receiver);

        // assert
        expect($receiver->hasConversationWith($auth))->toBe(true);

    });

    it('returns true if user has conversation with another of different Type', function () {

        $auth = User::factory()->create();
        $receiver = Admin::factory()->create();
        $conversation = $auth->createConversationWith($receiver);

        // assert
        expect($receiver->hasConversationWith($auth))->toBe(true);

    });

    it('returns true if user has conversation themselves', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // assert
        expect($auth->hasConversationWith($auth))->toBe(true);

    });

});

describe('getUnreadCount()', function () {

    it('returns correct number of unreadMessages if Conversation model is passed', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // send message to auth
        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');

        // Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(2);

    });

    it('returns correct number of unreadMessages for Admin if Conversation model is passed', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test');

        $receiver = Admin::factory()->create();
        $OtherAdmin = Admin::factory()->create();
        $OtherUser = User::factory()->create();

        $conversation->addParticipant($receiver);
        $conversation->addParticipant($OtherAdmin);
        $conversation->addParticipant($OtherUser);

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation
        // / $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Mark as read

        // auth -> receiver
        $auth->sendMessageTo($conversation, message: '1');
        $auth->sendMessageTo($conversation, message: '2');
        $auth->sendMessageTo($conversation, message: '3');

        Carbon::setTestNowAndTimezone(now()->subSeconds(5));
        //  $conversation->markAsRead($auth);

        Carbon::setTestNowAndTimezone(now()->subSeconds(10));

        // send message to auth
        // receiver -> auth
        $receiver->sendMessageTo($conversation, message: 'From Admin-1');
        $receiver->sendMessageTo($conversation, message: 'From Admin-2');

        $OtherAdmin->sendMessageTo($conversation, message: 'From OtherAdmin-1');
        $OtherAdmin->sendMessageTo($conversation, message: 'From OtherAdmin-2');

        $OtherUser->sendMessageTo($conversation, message: 'From User-1');
        $OtherUser->sendMessageTo($conversation, message: 'From User-2');

        // Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(6);

        // verify that all unread messages do not belong to user
        $unreadMessages = $conversation->unreadMessages($auth);

        $allDoNotBelongToAuth = true;
        foreach ($unreadMessages as $message) {
            $allDoNotBelongToAuth = $allDoNotBelongToAuth && ! $message->ownedBy($auth);
        }

        expect($allDoNotBelongToAuth)->toBe(true);

    });

    it('returns 0 unreadMessages if auth has read all messages', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test');

        $receiver = Admin::factory()->create();
        $OtherAdmin = Admin::factory()->create();
        $OtherUser = User::factory()->create();

        $conversation->addParticipant($receiver);
        $conversation->addParticipant($OtherAdmin);
        $conversation->addParticipant($OtherUser);

        // time travel
        Carbon::setTestNowAndTimezone(now()->subSeconds(30));

        // receiver -> auth
        $receiver->sendMessageTo($conversation, message: 'From Admin-1');
        $receiver->sendMessageTo($conversation, message: 'From Admin-2');

        // to ->auth
        $OtherAdmin->sendMessageTo($conversation, message: 'From OtherAdmin-1');
        $OtherAdmin->sendMessageTo($conversation, message: 'From OtherAdmin-2');

        // move 10 seconds fron last 20 mins
        Carbon::setTestNow(now()->addSeconds(10));

        // mark as read
        $conversation->markAsRead($auth);

        // reset fake time
        Carbon::setTestNow();

        //  Carbon::setTestNow(now()->subSeconds(10));
        //   Carbon::setTestNow(now());
        //  $OtherUser->sendMessageTo($conversation, message: 'From User-1');
        //  $OtherUser->sendMessageTo($conversation, message: 'From User-2');

        // Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(0);

    });

    it('returns "2" the correct number of unread new unread messages after marking previous messages as read', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test');

        $receiver = Admin::factory()->create();
        $OtherAdmin = Admin::factory()->create();
        $OtherUser = User::factory()->create();

        $conversation->addParticipant($receiver);
        $conversation->addParticipant($OtherAdmin);
        $conversation->addParticipant($OtherUser);

        // time travel
        Carbon::setTestNowAndTimezone(now()->subSeconds(30));

        // receiver -> auth
        $receiver->sendMessageTo($conversation, message: 'From Admin-1');
        $receiver->sendMessageTo($conversation, message: 'From Admin-2');

        // to ->auth
        $OtherAdmin->sendMessageTo($conversation, message: 'From OtherAdmin-1');
        $OtherAdmin->sendMessageTo($conversation, message: 'From OtherAdmin-2');

        // move 10 seconds fron last 20 mins
        Carbon::setTestNow(now()->addSeconds(10));

        // mark as read
        $conversation->markAsRead($auth);

        // reset fake time
        Carbon::setTestNow();

        // New Unread messages
        $OtherUser->sendMessageTo($conversation, message: 'From User-1');
        $OtherUser->sendMessageTo($conversation, message: 'From User-2');

        // Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(2);

    });

    it('returns all unread count if Conversation model is not passed', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // create first conversation and receiver messages
        Conversation::factory()->withParticipants([$auth, $receiver])->create();
        $receiver->sendMessageTo($auth, message: '1');
        $receiver->sendMessageTo($auth, message: '1');

        // create new conversation and receive messages
        $receiver2 = User::factory()->create();
        Conversation::factory()->withParticipants([$auth, $receiver2])->create();
        $receiver2->sendMessageTo($auth, message: 'new 1');
        $receiver2->sendMessageTo($auth, message: 'new 2');
        $receiver2->sendMessageTo($auth, message: 'new 3');

        // Assert number of total unread  count for $auth
        expect($auth->getUnreadCount())->toBe(5);

    });

    it('it returns a numeric value', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // send message to auth
        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');

        // Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBeNumeric();

    });

});

describe('createGroup', function () {

    it('it creates conversation in database', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // assert
        expect(Conversation::find($conversation))->not->toBe(null);

    });

    it('it creates room in database', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        $group = $conversation->group;

        // assert
        expect(Group::find($group->id)->id)->toBe($group->id);

    });

    it('it saves group data if correctly', function () {

        $auth = User::factory()->create();
        $photo = UploadedFile::fake()->create('photo.png');
        $conversation = $auth->createGroup(name: 'New group', description: 'description', photo: $photo);

        $group = $conversation->group;
        // assert

        expect($group->name)->toBe('New group');
        expect($group->description)->toBe('description');
        expect($group->cover)->not->toBe(null);
        expect($group->type)->toBe(GroupType::PRIVATE);

    });

    it('creates participant as owner to group', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        $participant = $conversation->participants()->first();

        // assert
        expect($participant->participantable_id)->toEqual($auth->id);

    });

    it('does not abort if canCreateNewGroups == TRUE(email is verified)', function () {

        $auth = User::factory()->create(['email_verified_at' => now()]);

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        expect($conversation)->not->toBe(null);

        expect(Conversation::count())->toBe(1);

    });

    it('aborts if canCreateNewGroups == FALSE(email NOT is verified)', function () {

        $auth = User::factory()->create(['email_verified_at' => null]);

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        expect($conversation)->toBe(null);

        expect(Conversation::withoutGlobalScopes()->count())->toBe(0);

    })->throws(Exception::class, 'You do not have permission to create groups.');

});

describe('Exit conversation', function () {

    test('Owner cannot exit conversation', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // assert
        expect($auth->exitConversation($conversation))->toBe(false);

    })->throws(Exception::class, 'Owner cannot exit conversation');

    test('User cannot exit from private conversation', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createConversationWith(User::factory()->create());

        // assert
        expect($auth->exitConversation($conversation))->toBe(false);

    })->throws(Exception::class, 'Participant cannot exit a private conversation');

    it('marks participant exited_at table when user exits conversation', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        $user = User::factory()->create();
        $conversation->addParticipant($user);

        $user->exitConversation($conversation);

        // get participant set withoutGlobalScopes =true becuaes at this point the user should be added to query
        $participant = $conversation->participant($user, true);

        // assert
        expect($participant->hasExited())->toBe(true);
        expect($participant->exited_at)->not->toBe(null);

    });

    it('it also deletes messages for user after exiting conversation', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        $user = User::factory()->create();
        $conversation->addParticipant($user);

        // Send to conversation
        $user->sendMessageTo($conversation, 'hello-1');
        $user->sendMessageTo($conversation, 'hello-2');
        $user->sendMessageTo($conversation, 'hello-3');

        $conversation = $user->sendMessageTo($conversation, 'hello-4')->conversation;

        // Assert Count
        expect($conversation->messages()->count())->toBe(4);

        // Authenticate

        $user->exitConversation($conversation);

        // dd(Action::all());

        $this->actingAs($user);

        expect($conversation->messages->count())->toBe(0);

    });

    it('returns false when checking if user belongs to conversation after exiting', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        $user = User::factory()->create();
        $conversation->addParticipant($user);

        $user->exitConversation($conversation);

        // get participant
        $participant = $conversation->participant($user);

        // assert
        expect($user->belongsToConversation($conversation))->toBe(false);

    });

});
