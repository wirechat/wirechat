<?php

use App\Livewire\Test;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\group;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

describe('MarkAsRead()', function () {
    it('marks messages as read', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation

        // auth -> receiver
        Carbon::setTestNowAndTimezone(now()->subMinutes(10));
        $auth->sendMessageTo($receiver, message: '1');
        $conversation = $auth->sendMessageTo($receiver, message: '2')->conversation;

        // send message to auth
        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        // Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(2);

        Carbon::setTestNowAndTimezone(now());
        // assert returns zero(0) when messages are marked as read
        $conversation->markAsRead();
        expect($auth->getUnreadCount($conversation))->toBe(0);
    });
});

describe('AddParticipant()', function () {

    it('can add a participants to a conversation', function () {

        $auth = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->addParticipant($auth);

        expect(count($conversation->participants()->get()))->toBe(1);
    });

    it('does not add same participant to conversation -aborts 422', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addParticipant($auth);

        $conversation->addParticipant($auth);

        // dd($conversation->users);

        expect(count($conversation->participants()->get()))->toBe(1);
    })->throws(Exception::class, 'Participant is already in the conversation.');

    it('does not add more than 2 participants to a PRIVATE conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        expect($conversation->participants()->count())->toBe(2);
    })->throws(Exception::class, 'Private conversations cannot have more than two participants.');

    it('does not add more than 1 participant to a SELF conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type' => ConversationType::SELF]);

        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create())
            ->assertStatus(422, 'Self conversations cannot have more than 1 participant.');

        expect($conversation->participants()->count())->toBe(1);

    })->throws(Exception::class, 'Self conversations cannot have more than one participant.');

    it('can add more than 2 participants if it is a  GROUP conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type' => ConversationType::GROUP]);

        // dd($conversation);
        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        expect($conversation->participants()->count())->toBe(4);
    });

    it('aborts if user who exited conversation is added', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test group');

        // add user
        $user = User::factory()->create(['name' => 'user']);
        $conversation->addParticipant($user);

        // assert count is 2
        expect($conversation->participants()->count())->toBe(2);

        // let user exit conversation
        $user->exitConversation($conversation);

        // assert new count is 1
        expect($conversation->participants()->count())->toBe(1);

        // attemp to readd user
        $conversation->addParticipant($user);

        // assert new count is still 1
        expect($conversation->participants()->count())->toBe(1);
    })->throws(Exception::class, 'Cannot add user because they left the group.');

    it('aborts if user who was removed is added and revive was false', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test group');

        // add user
        $user = User::factory()->create(['name' => 'user']);
        $conversation->addParticipant($user);

        // assert count is 2
        expect($conversation->participants()->count())->toBe(2);

        // remove user from group
        $userParticipant = $conversation->participant($user);
        $userParticipant->removeByAdmin($auth);

        // assert new count is 1
        expect($conversation->participants()->count())->toBe(1);

        // attemp to readd user
        $conversation->addParticipant($user);

        // assert new count is still 1
        expect($conversation->participants()->count())->toBe(1);
    })->throws(Exception::class, 'Cannot add user because they were removed from the group by an Admin.');

});

describe('getUnreadCountFor()', function () {

    it('returns unread messages count for the specified user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation
        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: '1')->conversation;
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '6');
        $receiver->sendMessageTo($auth, message: '7');

        // Assert number of unread messages for $auth

        expect($conversation->getUnreadCountFor($auth))->toBe(4);
    });

    it('returns unread messages count for the specified user of Mixed Types', function () {

        $auth = User::factory()->create();
        $receiver = Admin::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation
        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: '1')->conversation;
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '6');
        $receiver->sendMessageTo($auth, message: '7');

        // Assert number of unread messages for $auth

        expect($conversation->getUnreadCountFor($auth))->toBe(4);
    });
});

describe('readBy()', function () {

    it('returns true for read conversations', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Authenticate $auth
        $this->actingAs($auth);

        // Create conversation

        // auth -> receiver
        Carbon::setTestNowAndTimezone(now()->subMinutes(10));
        $auth->sendMessageTo($receiver, message: '1');
        $conversation = $auth->sendMessageTo($receiver, message: '2')->conversation;

        // send message to auth
        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        // Assert number of unread messages for $auth
        expect($conversation->readBy($auth))->toBe(false);

        Carbon::setTestNow(now()->addMinutes(4));

        // assert returns zero(0) when messages are marked as read
        $conversation->markAsRead();

        Carbon::setTestNowAndTimezone(now());

        expect($conversation->readBy($auth))->toBe(true);

    });

});

describe('deleteFor()', function () {

    it('load all conversations if not deleted', function () {
        $auth = User::factory()->create();

        // Authenticate
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo(User::factory()->create(), 'hello-2');
        $auth->sendMessageTo(User::factory()->create(), 'hello-3');

        // assert count

        // / dd($messages);
        expect($auth->conversations->count())->toBe(3);
    });

    it('aborts if user does not belong to conversation when deletingForMe', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver, 'hello');

        // Authenticate
        $this->actingAs($auth);

        // delete messages
        $conversation->deleteFor(User::factory()->create());

        // assert new count
        expect($conversation->count())->toBe(1);
    })->throws(Exception::class);

    it('loads deleted  conversations(for me) in query', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        Carbon::setTestNow(now()->addSeconds(1));

        $conversation3 = $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;
        $this->actingAs($auth);

        // Assert Count
        expect($auth->conversations()->withoutDeleted()->count())->toBe(3);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->deleteFor($auth);

        // conversations
        expect($auth->conversations()->count())->toBe(3);
    });

    it('loads deleted  conversations(for me) in query of Mixed Participant Models', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(Admin::factory()->create(), 'hello-2')->conversation;
        Carbon::setTestNow(now()->addSeconds(1));

        $conversation3 = $auth->sendMessageTo(Admin::factory()->create(['name' => 'john']), 'hello-3')->conversation;
        $this->actingAs($auth);

        // Assert Count
        expect($auth->conversations()->withoutDeleted()->count())->toBe(3);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation1->deleteFor($auth);

        // conversations
        expect($auth->conversations()->count())->toBe(3);
    });

    it('does not loads deleted  conversations(for me) in query when ->withoutDeleted() scope is used ', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        Carbon::setTestNow(now()->addSeconds(1));

        $conversation3 = $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;
        $this->actingAs($auth);

        // Assert Count
        expect($auth->conversations()->withoutDeleted()->count())->toBe(3);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->deleteFor($auth);

        // conversations
        expect($auth->conversations()->withoutDeleted()->count())->toBe(2);
    });

    it('does not loads deleted  conversations(for me) in query when ->withoutDeleted() scope is used in Mixed Participant Models ', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(Admin::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        Carbon::setTestNow(now()->addSeconds(1));

        $conversation3 = $auth->sendMessageTo(Admin::factory()->create(['name' => 'john']), 'hello-3')->conversation;
        $this->actingAs($auth);

        // Assert Count
        expect($auth->conversations()->withoutDeleted()->count())->toBe(3);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation1->deleteFor($auth);

        // conversations
        expect($auth->conversations()->withoutDeleted()->count())->toBe(2);
    });

    it('deletes and does not load deleted conversations(for me) if scopewithoutCleared is added--because delted conversations are CLEARED by default', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $conversation3 = $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        // Assert Count
        expect($auth->conversations()->withoutCleared()->count())->toBe(3);

        // Authenticate
        // $auth->refresh();

        $this->actingAs($auth);

        // Delete Conversation
        $conversation3->deleteFor($auth);

        // conversations
        expect($auth->conversations()->withoutCleared()->count())->toBe(2);
    });

    it('triggers delete messages for me when conversation is deleted', function () {

        // Dusk to

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');
        $conversation = $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        // Assert Count
        expect($conversation->messages()->count())->toBe(4);

        // Authenticate
        // $auth->refresh();

        $this->actingAs($auth);

        // Delete Conversation
        $conversation->deleteFor($auth);

        $this->actingAs($auth);

        expect($conversation->messages()->count())->toBe(0);
    });

    test('other user can still access the converstion if other user deletes it ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Send to receiver
        $conversation = $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        // Authenticate and delete 1
        $this->actingAs($auth);
        $conversation->deleteFor($auth);
        expect($auth->conversations()->withoutCleared()->count())->toBe(0);

        // Authenticate and delete 2
        $this->actingAs($receiver);
        expect($receiver->conversations()->withoutCleared()->count())->toBe(1);
    });

    test('it shows conversation again if new message is send to conversation after deleting', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Send to receiver
        Carbon::setTestNow(now()->addSeconds(1));
        $conversation = $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        // Authenticate and delete 1
        $this->actingAs($auth);
        Carbon::setTestNow(now()->addSeconds(2));

        $conversation->deleteFor($auth);

        // assert
        expect($auth->conversations()->withoutCleared()->count())->toBe(0);

        // send message to $auth
        Carbon::setTestNow(now()->addSeconds(3));
        $receiver->sendMessageTo($auth, 'hello-5');

        // assert again
        expect($auth->conversations()->count())->toBe(1);
    });

    it('permanetenly deletes the conversation if both Participants of Same Model in a private conversation has deleted conversation WITHOUT any new messages', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        Carbon::setTestNow(now()->subSeconds(20));

        // Send to receiver
        $conversation = $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        Carbon::setTestNow(now()->addSeconds(20));
        $auth->sendMessageTo($receiver, 'hello75');
        $receiver->sendMessageTo($auth, 'hello-5');

        // Reset time
        Carbon::setTestNow();

        // Authenticate and delete 1
        $this->actingAs($auth);
        $conversation->deleteFor($auth);

        Carbon::setTestNow();
        // Authenticate and delete 2
        $this->actingAs($receiver);
        $conversation->deleteFor($receiver);

        $this->assertDatabaseMissing((new Conversation)->getTable(), ['id' => $conversation->id]);
    });

    it('does not permanetenly delete the conversation even if both Participants of Same Model in a private conversation has deleted deleted BUT convefsation has new messages', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        Carbon::setTestNowAndTimezone(now()->subSeconds(20));

        // Send to receiver
        $conversation = $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        Carbon::setTestNow(now()->addSeconds(5));

        // Auth Delete
        $conversation->deleteFor($auth);

        Carbon::setTestNow(now()->addSeconds(5));

        $auth->sendMessageTo($receiver, 'hello75'); // Send new message

        Carbon::setTestNow();

        // Recevier Delete
        $conversation->deleteFor($receiver);

        $this->assertDatabaseHas((new Conversation)->getTable(), ['id' => $conversation->id]);
    });

    it('permanetenly deletes the conversation if both participants of Different Models ie(Admin/User) delete it CONSEQUTIVELY without any anew messages ', function () {

        $auth = User::factory()->create();
        $admin = Admin::factory()->create();

        // Travel back
        Carbon::setTestNow(now()->subSeconds(20));

        $conversation = $auth->createConversationWith($admin, 'hello-4');

        Carbon::setTestNow(now()->addSeconds(5));
        $conversation->deleteFor($auth);

        // Reset Time
        Carbon::setTestNow();

        // Authenticate and delete 2
        $conversation->deleteFor($admin);

        $this->assertDatabaseMissing((new Conversation)->getTable(), ['id' => $conversation->id]);
    });

    it('does not permanetenly delete the conversation even if both Participants of Mixed Model in a private conversation has deleted deleted BUT convefsation has new messages', function () {

        $auth = User::factory()->create();
        $receiver = Admin::factory()->create();

        Carbon::setTestNow(now()->subSeconds(20));

        // Send to receiver
        $conversation = $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        // Authenticate and delete 1

        $conversation->deleteFor($auth);

        Carbon::setTestNow(now()->addSeconds(10));

        $receiver->sendMessageTo($auth, 'hello-5');

        Carbon::setTestNow();

        $conversation->deleteFor($receiver);

        $this->assertDatabaseHas((new Conversation)->getTable(), ['id' => $conversation->id]);
    });

    it('completely deletes the conversation if conversation is self conversation with initiator(User)', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // Send to self
        $conversation = $auth->sendMessageTo($auth, 'hello-4')->conversation;

        // Authenticate and delete 1
        $conversation->deleteFor($auth);

        expect(Conversation::withoutGlobalScopes()->where('id', $conversation->id)->first())->toBe(null);
    });

    it('it saves or set conversation_deleted_at after deleting conversation ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($auth);
        // Send to self
        $conversation = $auth->createConversationWith($receiver, 'helo');

        // Authenticate and delete 1
        $conversation->deleteFor($auth);

        $participant = $conversation->participant($auth);

        expect($participant->conversation_deleted_at)->not->toBe(null);
    });

    it('it does not exludes deleted conversation from query if not new message is available', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($auth);
        // Send to self
        Carbon::setTestNow(now());

        $conversation = $auth->createConversationWith($receiver, 'helo');
        Carbon::setTestNow(now()->addMinute(20));

        // Authenticate and delete 1
        $conversation->deleteFor($auth);

        $auth->refresh();

        expect(count($auth->conversations()->get()))->toBe(1);
    });

    it('always adds deleted conversation to query even after sending new message', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($auth);
        // !we set custom time because time is same or models during test - maube it's bug
        Carbon::setTestNow(now());
        $conversation = $auth->createConversationWith($receiver, 'helo');

        Carbon::setTestNow(now()->addSeconds(10));

        // Authenticate and delete 1
        $conversation->deleteFor($auth);

        // assert 0 for now
        expect(count($auth->conversations()->get()))->toBe(1);

        // send me message
        Carbon::setTestNow(now()->addSeconds(20));
        $auth->sendMessageTo($receiver, 'hello');

        $auth->refresh();
        expect(count($auth->conversations()->get()))->toBe(1);
    });
});

describe('ClearFor()', function () {

    it('loads all conversations if not cleared', function () {
        $auth = User::factory()->create();

        // Authenticate
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo(User::factory()->create(), 'hello-2');
        $auth->sendMessageTo(User::factory()->create(), 'hello-3');

        // assert count

        // / dd($messages);
        expect($auth->conversations->count())->toBe(3);
    });

    it('aborts if user does not belong to conversation when deletingForMe', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver, 'hello');

        // Authenticate
        $this->actingAs($auth);

        // delete messages
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation->clearFor(User::factory()->create());

        // assert new count
        expect($conversation->count())->toBe(1);
    })->throws(Exception::class);

    it('cleared conversation still appear in query', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $conversation3 = $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        // Assert Count
        expect($auth->conversations()->count())->toBe(3);

        // Authenticate
        // $auth->refresh();

        $this->actingAs($auth);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->clearFor($auth);

        // conversations
        expect($auth->conversations()->count())->toBe(3);
    });

    it('cleared conversation of Mixed Models still appear in query', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $conversation1 = $auth->sendMessageTo(Admin::factory()->create(), 'hello-1')->conversation;
        $conversation2 = $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $conversation3 = $auth->sendMessageTo(Admin::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        // Assert Count
        expect($auth->conversations()->count())->toBe(3);

        // Authenticate
        // $auth->refresh();

        $this->actingAs($auth);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation1->clearFor($auth);

        // conversations
        expect($auth->conversations()->count())->toBe(3);
    });

    test('user cannot no longer see cleared messages', function () {

        // Dusk to

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(2));

        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(2));

        expect($conversation->messages()->count())->toBe(0);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // login so the messages scope will be applied
        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(4);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(10));
        $conversation->clearFor($auth);

        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(0);
    });

    test('Other user/users can still see cleared messages by auth', function () {

        // Dusk to
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(2));

        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(2));
        expect($conversation->messages()->count())->toBe(0);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // login so the messages scope will be applied
        Carbon::setTestNow(now()->addSeconds(10));
        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(4);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(20));
        $conversation->clearFor($auth);

        Auth::logout();
        // login as other user
        Carbon::setTestNow(now()->addSeconds(30));
        $this->actingAs($receiver);

        expect($conversation->messages()->count())->toBe(4);
    });

    test('Other user of Mixed Models can still see cleared messages by auth', function () {

        // Dusk to
        $auth = User::factory()->create();
        $receiver = Admin::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(2));

        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(2));
        expect($conversation->messages()->count())->toBe(0);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // login so the messages scope will be applied
        Carbon::setTestNow(now()->addSeconds(10));
        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(4);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(20));
        $conversation->clearFor($auth);

        Auth::logout();
        // login as other user
        Carbon::setTestNow(now()->addSeconds(30));
        $this->actingAs($receiver);

        expect($conversation->messages()->count())->toBe(4);
    });

    it('removes cleared conversations from query if withoutCleared() is used', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;

        Carbon::setTestNow(now()->addSeconds(2));
        $conversation3 = $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        // Assert Count
        expect($auth->conversations()->withoutCleared()->count())->toBe(4);

        // Authenticate
        // $auth->refresh();

        $this->actingAs($auth);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->clearFor($auth);

        // conversations
        expect($auth->conversations()->withoutCleared()->count())->toBe(3);
    });

    it('removes cleared conversations from query if withoutCleared() is used In Mixed Models', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;

        Carbon::setTestNow(now()->addSeconds(2));
        $conversation3 = $auth->sendMessageTo(Admin::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        // Assert Count
        expect($auth->conversations()->withoutCleared()->count())->toBe(4);

        // Authenticate
        // $auth->refresh();

        $this->actingAs($auth);

        // Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->clearFor($auth);

        // conversations
        expect($auth->conversations()->withoutCleared()->count())->toBe(3);
    });

});

describe('WithoutBlanks()', function () {

    it('it filters out blank messages when ->withoutBlanks() used ', function () {

        $auth = User::factory()->create();

        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;
        $conversation2 = $auth->createConversationWith(User::factory()->create())->conversation;

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));

        $user = User::factory()->create(['name' => 'john']);
        $messsage = $auth->sendMessageTo($user, 'hello-3');
        $messsag2 = $auth->sendMessageTo($user, 'hello-3');

        // reset timer
        Carbon::setTestNow(now()->addSeconds(5));

        $messsage->deleteFor($auth);
        $messsag2->deleteFor($auth);

        Carbon::setTestNow();

        $this->actingAs($auth);

        expect($auth->conversations()->withoutBlanks()->count())->toBe(0);

    });

    it('it filters out blank messages when ->withoutBlanks() used even with soft deleted messages ', function () {

        $auth = User::factory()->create();

        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;
        $conversation2 = $auth->createConversationWith(User::factory()->create())->conversation;

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));

        $user = User::factory()->create(['name' => 'john']);
        $messsage = $auth->sendMessageTo($user, 'hello-3');
        $messsag2 = $auth->sendMessageTo($user, 'hello-3');

        // create soft deleted message

        $conversation = $messsag2->conversation;

        Message::factory()->sender($auth)->create(['conversation_id' => $conversation->id, 'deleted_at' => now()]);

        // reset timer
        Carbon::setTestNow(now()->addSeconds(5));

        $messsage->deleteFor($auth);
        $messsag2->deleteFor($auth);

        Carbon::setTestNow();

        $this->actingAs($auth);

        expect($auth->conversations()->withoutBlanks()->count())->toBe(0);

    });

    it('it retrives conversation for other who did not delete all individual messages when ->withoutBlanks() used ', function () {

        $auth = User::factory()->create();

        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;
        $conversation2 = $auth->createConversationWith(User::factory()->create())->conversation;

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));

        $user = User::factory()->create(['name' => 'john']);
        $messsage = $auth->sendMessageTo($user, 'hello-3');
        $messsag2 = $auth->sendMessageTo($user, 'hello-3');

        // reset timer
        Carbon::setTestNow(now()->addSeconds(5));

        $messsage->deleteFor($auth);
        $messsag2->deleteFor($auth);

        Carbon::setTestNow();

        $this->actingAs($user);

        expect($auth->conversations()->withoutBlanks()->count())->toBe(1);

    });

    test('With Mixed Participants ,it retrives conversation for other who did not delete all individual messages when ->withoutBlanks() used ', function () {

        $auth = User::factory()->create();

        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(Admin::factory()->create())->conversation;
        $conversation2 = $auth->createConversationWith(User::factory()->create())->conversation;

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));

        $user = Admin::factory()->create(['name' => 'john']);
        $messsage = $auth->sendMessageTo($user, 'hello-3');
        $messsag2 = $auth->sendMessageTo($user, 'hello-3');

        // reset timer
        Carbon::setTestNow(now()->addSeconds(5));

        $messsage->deleteFor($auth);
        $messsag2->deleteFor($auth);

        Carbon::setTestNow();

        $this->actingAs($user);

        expect($auth->conversations()->withoutBlanks()->count())->toBe(1);

    });

    it('it retrievs all conversation when ->withoutBlanks() NOT used ', function () {

        // Dusk to
        $auth = User::factory()->create();

        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;
        $conversation2 = $auth->createConversationWith(User::factory()->create())->conversation;

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));

        $user = User::factory()->create(['name' => 'john']);
        $messsage = $auth->sendMessageTo($user, 'hello-3');
        $messsag2 = $auth->sendMessageTo($user, 'hello-3');

        // reset timer
        Carbon::setTestNow(now()->addSeconds(5));

        $messsage->deleteFor($auth);
        $messsag2->deleteFor($auth);

        Carbon::setTestNow();

        $this->actingAs($auth);

        expect($auth->conversations()->count())->toBe(4);

    });

    it('it filters out blank messages when ->withoutBlanks() used with Mixed Participants Models ', function () {

        // Dusk to
        $auth = Admin::factory()->create();
        Carbon::setTestNow(now()->subSeconds(10));
        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(Admin::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;

        Carbon::setTestNow();
        // Assert Count
        $this->actingAs($auth);

        expect($auth->conversations()->withoutBlanks()->count())->toBe(0);

    });
    it('it retrieves all  blank conversations when ->withoutBlanks() NOT used with Mixed Participants Models ', function () {

        // Dusk to
        $auth = Admin::factory()->create();
        Carbon::setTestNow(now()->subSeconds(10));
        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(Admin::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;

        Carbon::setTestNow();
        // Assert Count
        $this->actingAs($auth);

        expect($auth->conversations()->count())->toBe(3);

    });

    it('it filters out blank messages when ->withoutBlanks() in Mixed Model Participants used Exept those with filter Deleted Actions(as in Conversation is not blank if user has deleted only some messages) ', function () {

        // Dusk to
        $auth = Admin::factory()->create();

        Carbon::setTestNow(now()->subSeconds(10));
        // Send to receiver
        $auth->createConversationWith(User::factory()->create())->conversation;
        $auth->createConversationWith(User::factory()->create())->conversation;

        // create conversation with message
        $user = User::factory()->create(['name' => 'john']);
        $messsage = $auth->sendMessageTo($user, 'hello-3');
        $messsag2 = $auth->sendMessageTo($user, 'hello-3');

        // reset timer
        Carbon::setTestNow(now()->addSeconds(5));

        $messsage->deleteFor($auth);
        $messsag2->deleteFor($user);

        Carbon::setTestNow();

        $this->actingAs($auth);
        expect($auth->conversations()->withoutBlanks()->count())->toBe(1);

    });

});

describe('WithoutDeleted()', function () {

    it('retrieves all conversations even deleted when  withoutDeleted local scope is not used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(User::factory()->create());
        $conversation2 = $auth->createConversationWith(User::factory()->create());
        $conversation3 = $auth->createConversationWith(User::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        expect($auth->conversations->count())->toBe(3);

    });

    test('With mixed Paricipnats model it retrieves all conversations even deleted when  withoutDeleted local scope is not used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(User::factory()->create());
        $conversation2 = $auth->createConversationWith(Admin::factory()->create());
        $conversation3 = $auth->createConversationWith(Admin::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        expect($auth->conversations->count())->toBe(3);

    });

    it('exludes deleted conversation when  withoutDeleted local scope is used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(User::factory()->create());
        $conversation2 = $auth->createConversationWith(User::factory()->create());
        $conversation3 = $auth->createConversationWith(User::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        expect($auth->conversations()->withoutDeleted()->count())->toBe(2);

    });

    Test('With Mixed Participnats exludes deleted conversation when  withoutDeleted local scope is used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(Admin::factory()->create());
        $conversation2 = $auth->createConversationWith(Admin::factory()->create());
        $conversation3 = $auth->createConversationWith(User::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        expect($auth->conversations()->withoutDeleted()->count())->toBe(2);

    });

});

describe('WithDeleted()', function () {

    it('retrieves all conversations even deleted when  withDeleted() local scope is not used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(User::factory()->create());
        $conversation2 = $auth->createConversationWith(User::factory()->create());
        $conversation3 = $auth->createConversationWith(User::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        expect($auth->conversations->count())->toBe(3);

    });

    test('With mixed Paricipnats model it retrieves all conversations even deleted when  withDeleted() local scope is not used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(User::factory()->create());
        $conversation2 = $auth->createConversationWith(Admin::factory()->create());
        $conversation3 = $auth->createConversationWith(Admin::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        expect($auth->conversations->count())->toBe(3);

    });

    it('exludes deleted conversation when  withoutDeleted local scope is used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(User::factory()->create());
        $conversation2 = $auth->createConversationWith(User::factory()->create());
        $conversation3 = $auth->createConversationWith(User::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        $this->actingAs($auth);

        // Test withoutDeleted scope
        $withoutDeletedConversations = $auth->conversations()->withoutDeleted()->get();
        expect($withoutDeletedConversations->count())->toBe(2);

        // Test withDeleted scope
        $withDeletedConversations = $auth->conversations()->withDeleted()->get();
        expect($withDeletedConversations->count())->toBe(3);

    });

    Test('With Mixed Participnats exludes deleted conversation when  withoutDeleted local scope is used', function () {
        $auth = User::factory()->create();

        // create conversation with message
        Carbon::setTestNow(now()->subSeconds(10));
        // create conversations
        $conversation1 = $auth->createConversationWith(Admin::factory()->create());
        $conversation2 = $auth->createConversationWith(Admin::factory()->create());
        $conversation3 = $auth->createConversationWith(User::factory()->create());

        // delete conversations

        // reset timer
        Carbon::setTestNow();

        $conversation2->deleteFor($auth);

        // Authenticate
        // Authenticate
        $this->actingAs($auth);

        $withoutDeletedConversations = $auth->conversations;

        // Test withoutDeleted scope
        $withoutDeletedConversations = $auth->conversations()->withoutDeleted()->get();
        expect($withoutDeletedConversations->count())->toBe(2);

        // Test withDeleted scope
        $withDeletedConversations = $auth->conversations()->withDeleted()->get();
        expect($withDeletedConversations->count())->toBe(3);

    });

});

describe('deleting permanently()', function () {

    it('deletes all it\'s participants when conversation is deleted inluding exited and remove_by_admin', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type' => ConversationType::GROUP]);

        // dd($conversation);
        $conversation->addParticipant($auth);

        $participant = $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        // assert available
        expect($conversation->participants()->count())->toBe(4);

        // exit to create hidden participants
        $participant->exitConversation();

        // delete conversation

        $conversation->delete();

        expect($conversation->participants()->withoutGlobalScopes()->count())->toBe(0);
    });

    it('deletes group when conversation is deleted ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $group = $conversation->group;

        // get conversation reads
        expect(Group::find($group->id))->not->toBe(null);

        // Delete message
        $conversation->delete();

        // assert count
        expect(Group::find($group->id))->toBe(null);
    });

    it('deletes all messages when converstion is deleted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = Conversation::factory()->withParticipants([$receiver, $auth])->create(['type' => ConversationType::PRIVATE]);

        // dd($conversation);
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');

        // assert available
        expect($conversation->messages()->count())->toBe(5);

        // delete conversation
        $conversation->delete();

        expect($conversation->messages()->count())->toBe(0);
    });

    it('also deletes all messages with hidden scope when converstion is deleted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = Conversation::factory()->withParticipants([$receiver, $auth])->create(['type' => ConversationType::PRIVATE]);

        /* perfomr actions that hide some message from queries */

        // delete for
        $message = $auth->sendMessageTo($receiver, 'hello');
        $message->deleteFor($receiver);

        // soft delete
        $message = $auth->sendMessageTo($receiver, 'hello');
        $message->delete();

        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');

        $this->actingAs($receiver);

        // assert available
        expect($conversation->messages()->count())->toBe(4);

        // delete conversation
        $conversation->delete();

        expect($conversation->messages()->withoutGlobalScopes()->count())->toBe(0);

        expect(Message::withoutGlobalScopes()->count())->toBe(0);
    });
});

describe('recieverParticipant()', function () {

    it('it gets correct receiverParticipant in a private conversation ', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $receiver = $conversation->receiverParticipant;

        //   dd($receiver->participantable,$otherUser);

        expect($receiver->participantable->id)->toBe($otherUser->id);
        expect($receiver->participantable->getMorphClass())->toBe($otherUser->getMorphClass());
        expect($receiver->participantable->name)->toBe($otherUser->name);

    });

    it('it gets correct receiverParticipant in a private conversation of Mixed Participant Models  ', function () {

        $auth = User::factory()->create();
        $otherUser = Admin::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $receiver = $conversation->receiverParticipant;

        //   dd($receiver->participantable,$otherUser);

        expect($receiver->participantable->id)->toBe($otherUser->id);
        expect($receiver->participantable->getMorphClass())->toBe($otherUser->getMorphClass());
        expect($receiver->participantable->name)->toBe($otherUser->name);

    });

    it('returns NULL for self conversation ', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $receiver = $conversation->receiverParticipant;

        expect($receiver)->toBe(null);

    });

});

describe('authParticipant()', function () {

    it('it gets correct authParticipant in a private conversation ', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $authParticipant = $conversation->authParticipant;

        expect($authParticipant->participantable->id)->toBe($auth->id);
        expect($authParticipant->participantable->getMorphClass())->toBe($auth->getMorphClass());
        expect($authParticipant->participantable->name)->toBe($auth->name);

    });

    it('it gets correct receiverParticipant in a private conversation of Mixed Participant Models  ', function () {

        $auth = User::factory()->create();
        $otherUser = Admin::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $authParticipant = $conversation->authParticipant;

        expect($authParticipant->participantable->id)->toBe($auth->id);
        expect($authParticipant->participantable->getMorphClass())->toBe($auth->getMorphClass());
        expect($authParticipant->participantable->name)->toBe($auth->name);

    });

    it('it gets correct authParticipant in a Self conversation', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // log in as $auth
        $this->actingAs($auth);

        $authParticipant = $conversation->authParticipant;

        expect($authParticipant->participantable->id)->toBe($auth->id);
        expect($authParticipant->participantable->getMorphClass())->toBe($auth->getMorphClass());
        expect($authParticipant->participantable->name)->toBe($auth->name);

    });

});

describe('peerParticipant()', function () {

    it('it gets correct peer particiapnt in the conversaiton in a private conversation ', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        $conversation->load('participants.participantable');
        // get receiver
        $peerParticipant = $conversation->peerParticipant(reference: $auth);

        expect($peerParticipant->participantable->id)->toBe($otherUser->id);
        expect($peerParticipant->participantable->getMorphClass())->toBe($otherUser->getMorphClass());
        expect($peerParticipant->participantable->name)->toBe($otherUser->name);

    });

    it('it gets correct peer particiapnt in the conversaiton in a private conversation  of Mixed Models', function () {

        $auth = User::factory()->create();
        $otherUser = Admin::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        $conversation->load('participants.participantable');
        // get receiver
        $peerParticipant = $conversation->peerParticipant(reference: $auth);

        expect($peerParticipant->participantable->id)->toBe($otherUser->id);
        expect($peerParticipant->participantable->getMorphClass())->toBe($otherUser->getMorphClass());
        expect($peerParticipant->participantable->name)->toBe($otherUser->name);

    });

    it('it gets correct peer particiapnt in the conversaiton in a self conversation ', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);
        // get receiver
        $peerParticipant = $conversation->peerParticipant(reference: $auth);

        expect($peerParticipant->participantable->id)->toBe($auth->id);
        expect($peerParticipant->participantable->getMorphClass())->toBe($auth->getMorphClass());
        expect($peerParticipant->participantable->name)->toBe($auth->name);

    });

    it('it returns null if reference does not belong to conversation  ', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();
        $randomUser = User::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        $peerParticipant = $conversation->peerParticipant(reference: $randomUser);

        expect($peerParticipant)->toBe(null);

    });

});

describe('peerParticipants()', function () {

    it('gets correct peer participants in a group conversation', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create a group conversation
        $conversation = $auth->createGroup('test');

        // Add $otherUser to the conversation
        $conversation->addParticipant($otherUser);

        // Add 10 other participants
        $participants = collect();
        for ($i = 0; $i < 10; $i++) {
            $participants->push($conversation->addParticipant(User::factory()->create()));
        }

        $conversation->load('participants.participantable');

        // Get peer participants, excluding the authenticated user ($auth)
        $peerParticipants = $conversation->peerParticipants(reference: $auth);

        // Ensure that all retrieved peer participants are in the expected set
        expect($peerParticipants)->toHaveCount(11); // 10 random + 1 ($otherUser)

        foreach ($peerParticipants as $peerParticipant) {
            foreach ($participants as $key => $participant) {

                expect($peerParticipant->participantable)->not->toBe($auth); // Ensure $auth is excluded
                expect($participant->pluck('participantable_id'))->contains($peerParticipant->participantable->id)->toBeTrue(); // Ensure they are part of the added participants
                expect($participant->pluck('participantable_type'))->contains($peerParticipant->participantable->getMorphClass())->toBeTrue(); // Ensure they are part of the added participants
            }

        }
    });

    it('it gets correct peer particiapnt in the conversaiton in a group conversation  of Mixed Models', function () {

        $auth = User::factory()->create();
        $otherUser = Admin::factory()->create();

        // Create a group conversation
        $conversation = $auth->createGroup('test');

        // Add $otherUser to the conversation
        $conversation->addParticipant($otherUser);
        // Add 10 other participants
        $participants = collect();
        for ($i = 0; $i < 10; $i++) {
            $participants->push($conversation->addParticipant(User::factory()->create()));
        }

        $conversation->load('participants.participantable');
        // Get peer participants, excluding the authenticated user ($auth)
        $peerParticipants = $conversation->peerParticipants(reference: $auth);

        // Ensure that all retrieved peer participants are in the expected set
        expect($peerParticipants)->toHaveCount(11); // 10 random + 1 ($otherUser)

        foreach ($peerParticipants as $peerParticipant) {
            foreach ($participants as $key => $participant) {

                expect($peerParticipant->participantable)->not->toBe($auth); // Ensure $auth is excluded
                expect($participant->pluck('participantable_id'))->contains($peerParticipant->participantable->id)->toBeTrue(); // Ensure they are part of the added participants
                expect($participant->pluck('participantable_type'))->contains($peerParticipant->participantable->getMorphClass())->toBeTrue(); // Ensure they are part of the added participants

            }

        }
    });

    it('can return one user for private conversation', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // get receiver
        $peerParticipants = $conversation->peerParticipants(reference: $auth);

        expect($peerParticipants)->toHaveCount(1); // 1

        foreach ($peerParticipants as $peerParticipant) {
            expect($peerParticipant->pluck('participantable_id'))->contains($otherUser->id)->toBeTrue(); // Ensure they are part of the added participants
            expect($peerParticipant->pluck('participantable_type'))->contains($otherUser->getMorphClass())->toBeTrue(); // Ensure they are part of the added participants

        }

    });

    it('returns only one  peer particiapnt in the conversaiton in a self conversation ', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // get receiver
        $peerParticipants = $conversation->peerParticipants(reference: $auth);

        expect($peerParticipants)->toBeEmpty(); // 1
    });

});

describe('getReciever()', function () {

    it('it gets correct receiverParticipant in a private conversation ', function () {

        $auth = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $receiver = $conversation->getReceiver();

        //   dd($receiver->participantable,$otherUser);

        expect($receiver->id)->toBe($otherUser->id);
        expect($receiver->getMorphClass())->toBe($otherUser->getMorphClass());
        expect($receiver->name)->toBe($otherUser->name);

    });

    it('it gets correct receiverParticipant in a private conversation of Mixed Participant Models ', function () {

        $auth = User::factory()->create();
        $otherUser = Admin::factory()->create();

        $conversation = $auth->createConversationWith($otherUser);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $receiver = $conversation->getReceiver();

        //   dd($receiver->participantable,$otherUser);

        expect($receiver->id)->toBe($otherUser->id);
        expect($receiver->getMorphClass())->toBe($otherUser->getMorphClass());
        expect($receiver->name)->toBe($otherUser->name);

    });

    it('it gets correct receiverParticipant in a Self conversation', function () {
        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // log in as $auth
        $this->actingAs($auth);

        // get receiver
        $receiver = $conversation->getReceiver();

        //   dd($receiver,$otherUser);

        expect($receiver->id)->toBe($auth->id);
        expect($receiver->getMorphClass())->toBe($auth->getMorphClass());
        expect($receiver->name)->toBe($auth->name);

    });

});
