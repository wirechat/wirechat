<?php

use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Workbench\App\Models\User;

it('it saves action to database when created', function () {

    $auth = User::factory()->create();
    $conversation = $auth->createGroup('My Group');

    // add participant
    $user = User::factory()->create(['name' => 'Micheal']);
    $participant = $conversation->addParticipant($user);

    // remove by admin

    $action = Action::create([
        'actionable_id' => $participant->id,
        'actionable_type' => Participant::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
    ]);

    // data verification
    expect(Action::find($action->id))->not->toBe(null);
});

it('it saved correct data when action is created', function () {

    $auth = User::factory()->create();
    $conversation = $auth->createGroup('My Group');

    // add participant
    $user = User::factory()->create(['name' => 'Micheal']);
    $participant = $conversation->addParticipant($user);

    // remove by admin

    $action = Action::create([
        'actionable_id' => $participant->id,
        'actionable_type' => Participant::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
    ]);

    // data verification
    expect($action->actionable_id)->toBe($participant->id);
    expect($action->actionable_type)->toBe(Participant::class);
    expect($action->actor_id)->toBe($auth->id);
    expect($action->actor_type)->toBe(get_class($auth));
    expect($action->type)->toBe(Actions::REMOVED_BY_ADMIN);
});

it('it retrives correct actionable model (The model the action was performed on)', function () {

    $auth = User::factory()->create();
    $conversation = $auth->createGroup('My Group');

    // add participant
    $user = User::factory()->create(['name' => 'Micheal']);
    $participant = $conversation->addParticipant($user);

    // remove by admin

    $action = Action::create([
        'actionable_id' => $participant->id,
        'actionable_type' => Participant::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
    ]);

    // data verification

    $actionable = $actionable = $action->actionable()->withoutGlobalScopes()->first();

    expect($actionable->id)->toBe($actionable->id);
    expect(get_class($actionable))->toBe(get_class($participant));

});

it('it retrives correct actor model (The model performed the action)', function () {

    $auth = User::factory()->create();
    $conversation = $auth->createGroup('My Group');

    // add participant
    $user = User::factory()->create(['name' => 'Micheal']);
    $participant = $conversation->addParticipant($user);

    // remove by admin

    $action = Action::create([
        'actionable_id' => $participant->id,
        'actionable_type' => Participant::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
    ]);

    // data verification
    expect($action->id)->toBe($auth->id);
    expect(get_class($action->actor))->toBe(get_class($auth));

});

test('A model returns actions when ->actions is called()', function () {

    $auth = User::factory()->create();
    $conversation = $auth->createGroup('My Group');

    // add participant
    $otherUser = $conversation->addParticipant(User::factory()->create(['name' => 'Micheal']));

    // create message to be delete
    $message = $auth->sendMessageTo($conversation, 'Hello');

    // Create delete action by auth
    Action::create([
        'actionable_id' => $message->id,
        'actionable_type' => Message::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::DELETE,  // Type of action
    ]);

    // Create delete action by auth
    Action::create([
        'actionable_id' => $message->id,
        'actionable_type' => Message::class,
        'actor_id' => $otherUser->id,  // The admin who performed the action
        'actor_type' => get_class($otherUser),  // Assuming 'User' is the actor model
        'type' => Actions::DELETE,  // Type of action
    ]);

    // get message actions
    expect($message->actions()->count())->toBe(2);

});

test('A model returns performed actions when ->performedActions is called() on actor', function () {

    $auth = User::factory()->create();
    $conversation = $auth->createGroup('My Group');

    // add participant
    $otherUser = $conversation->addParticipant(User::factory()->create(['name' => 'Micheal']));

    // create message to be delete
    $message = $auth->sendMessageTo($conversation, 'Hello');
    $message2 = $auth->sendMessageTo($conversation, 'Hello');

    // Create delete action by auth
    Action::create([
        'actionable_id' => $message->id,
        'actionable_type' => Message::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::DELETE,  // Type of action
    ]);

    // Create delete action by auth
    Action::create([
        'actionable_id' => $message2->id,
        'actionable_type' => Message::class,
        'actor_id' => $auth->id,  // The admin who performed the action
        'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
        'type' => Actions::DELETE,  // Type of action
    ]);

    // get message actions
    expect($auth->performedActions()->count())->toBe(2);

});
