<?php


use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Participant;
use Workbench\App\Models\User;

describe('Delete Permanently', function () {


    it('deletes actions when message is deleted ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user);



        //remove by admin 

        Action::create([
            'actionable_id' => $participant->id,
            'actionable_type' => Participant::class,
            'actor_id' => $auth->id,  // The admin who performed the action
            'actor_type' => get_class($auth),  // Assuming 'User' is the actor model
            'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
        ]);

        #assert removed
        expect($participant->actions()->count())->toBe(1);


        #now forcifully delete

        $participant->delete();

        expect($participant->actions()->count())->toBe(0);
    });
});


describe('exitingConversation()', function () {




    it(' updates exited_at when participant exits conversation', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user);

        #assert
        expect($participant->exited_at)->toBe(null);


        #action
        $participant->exitConversation();

        $participant = $participant->refresh();

        #assert
        expect($participant->exited_at)->not->toBe(null);
    });


    it(' does not update conversation_deleted_at or conversation_cleared_at when participant exits conversation', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user);

        #assert
        expect($participant->conversation_deleted_at)->toBe(null);
        expect($participant->conversation_cleared_at)->toBe(null);


        #action
        $participant->exitConversation();

        $participant = $participant->refresh();

        #assert
        expect($participant->conversation_deleted_at)->toBe(null);
        expect($participant->conversation_cleared_at)->toBe(null);

    });


    it('$user -> belongs to conversation should return false after exiting', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user);


        #assert
        expect($user->belongsToConversation($conversation))->toBe(true);


        #action
        $participant->exitConversation();

        $participant = $participant->refresh();

        #assert
        expect($user->belongsToConversation($conversation))->toBe(false);
    });


    it('removes Admin role and add Participant role when if user is admin ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user, role: ParticipantRole::ADMIN);

        #assert 
        expect($participant->role)->toBe(ParticipantRole::ADMIN);

        #exit
        $participant->exitConversation();

        $participant->refresh();

        #assert 
        expect($participant->role)->toBe(ParticipantRole::PARTICIPANT);
    });


});



describe('removeByAdmin()', function () {




    it('creates an action model relationship with type REMOVED_BY_ADMIN ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user);

        #assert
        expect($participant->isRemovedByAdmin())->toBe(false);

        #action
        $participant->removeByAdmin($auth);

        $participant = $participant->refresh();

        #assert
        expect($participant->isRemovedByAdmin())->toBe(true);

        $actionsCount= Action::where('type',Actions::REMOVED_BY_ADMIN)
                        ->where('actionable_id',$participant->id)
                        ->where('actionable_type',Participant::class)
                        ->count();

        expect($actionsCount)->toBe(1);
    });


    it('it only create one REMOVED_BY_ADMIN action no matter how many times it is called ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user);


        #action - call 3 times 
        $participant->removeByAdmin($auth);
        $participant->removeByAdmin($auth);
        $participant->removeByAdmin($auth);


        $participant = $participant->refresh();

        $actionsCount= Action::where('type',Actions::REMOVED_BY_ADMIN)
                        ->where('actionable_id',$participant->id)
                        ->where('actionable_type',Participant::class)
                        ->count();

        expect($actionsCount)->toBe(1);
    });


    it('removes Admin role and adds Participant role REMOVED_BY_ADMIN  ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant =  $conversation->addParticipant($user, role: ParticipantRole::ADMIN);

        #assert
        expect($participant->role)->toBe(ParticipantRole::ADMIN);

        #action
        $participant->removeByAdmin($auth);

        $participant = $participant->refresh();

        #assert
        expect($participant->role)->toBe(ParticipantRole::PARTICIPANT);

    });


});
