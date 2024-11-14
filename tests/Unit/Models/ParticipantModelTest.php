<?php


use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Participant;
use Workbench\App\Models\User;

describe('Delete Permanently',function(){


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
})->only();