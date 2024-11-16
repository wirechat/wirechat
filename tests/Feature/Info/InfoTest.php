<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Info\Info;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(Info::class, ['conversation' => $conversation])
        ->assertStatus(401);
});

test('aborts if user doest not belog to conversation', function () {


    $auth = User::factory()->create(['id' => '345678']);


    $conversation = Conversation::factory()->create();
    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

test('authenticaed user can access info ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->withParticipants([$auth])->create();

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(200);
});


describe('presence test', function () {


    test('it shows receiver name if conversaton is private', function () {
        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create(['name' => 'Musa']);

        $conversation =  $auth->createConversationWith($receiver, 'hello');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("Musa");
    });


    test('it shows group name if conversaton is group', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create(['name' => 'Musa']);

        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($receiver);
        $conversation->addParticipant(User::factory()->create());


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("My Group");
    });


    test('group description property is wired', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyWired("description");
    });


    test('it doent show photo property wired if auth is not admin', function () {

        $auth = User::factory()->create(['id' => '345678']);



        $user = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyNotWired("photo");
    });

    test('it doent show name property wired if auth is not admin', function () {

        $auth = User::factory()->create(['id' => '345678']);



        $user = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyNotWired("groupName");
    });

    test('it doent show description property wired if auth is not admin', function () {

        $auth = User::factory()->create(['id' => '345678']);



        $user = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyNotWired("description");
    });

    test('it shows group description if conversaton is group', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("This is a good group");
    });


    test('it will show add a description if group description is null', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                       ->assertSee("Add a group description");
    });


    test('it shows group members count if conversaton is group', function () {

        $auth = User::factory()->create(['id' => '345678']);


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("Members 3");
    });


    test('it shows "add member" if is group', function () {

        $auth = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("Add Member");
    });

    test('it doesnt shows "add member" if is not group', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation =  $auth->createConversationWith($receiver);
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee("Add Members");
    });


    test('it shows "Exit Group" and method wired if is group', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        //create conversation with user1
        $conversation= $auth->createGroup('My Group');

        #add participant
        $conversation->addParticipant($receiver);

        Livewire::actingAs($receiver)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("Exit Group")
            ->assertMethodWired('exitConversation');
    });


    test('it doesnt shows "Exit Group" if is not group', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation =  $auth->createConversationWith($receiver);
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee("Exit Group");
    });


    test('it doesnt shows "Exit Group" if auth is owner', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //create conversation with user1
        $conversation= $auth->createGroup('My Group');

        #add participant
        $conversation->addParticipant($receiver);


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee("Exit Group")
            ->assertMethodNotWired('exitConversation')
        ;
    });



    



    test('it shows "Delete Chat" if is not group', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation =  $auth->createConversationWith($receiver);


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("Delete Chat")
            ->assertMethodWired('deleteChat');
    });


    test('it shows "Delete Group" and method wired if is group and auth is Owner', function () {

        $auth = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee("Delete Group")
            ->assertMethodWired('deleteGroup')
            ->assertSee("Before you can delete the group, you’ll need to remove all group members") ;
    });

    test('it doenst shows "Delete Group" and method wired if is group and auth is NOT Owner', function () {

        $auth = User::factory()->create();
        $participant = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        
        Livewire::actingAs($participant)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee("Delete Group")
            ->assertMethodNotWired('deleteChat');
    });


});



describe('updating group name and description', function () {


    //Group name 

    test('group name is required', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $request->set('groupName', null)
            ->call('updateGroupName')
            ->assertHasErrors('groupName')
            ->assertSee('The Group name cannot be empty');
    });


    test('group name cannot exceed 500 chars', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $text = str()->random(150);
        $request->set('groupName', $text)
            ->call('updateGroupName')
            ->assertHasErrors('groupName')
            ->assertSee('Group name cannot exceed 120 characters.');
    });


    test('it udpates group name in blade', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $request->set('groupName', 'New Name')
            ->call('updateGroupName')
            ->assertSee('New Name');
    });


    test('it saved ne group name to database', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $request->set('groupName', 'New Name')
            ->call('updateGroupName');

        expect($conversation->group()->first()->name)->toBe('New Name');
    });


    test('it dispactches refersh event after upating name', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $request->set('groupName', 'New Name')
            ->call('updateGroupName')
            ->assertDispatched('refresh');
    });


    //Description 

    test('description cannot exceed 500 chars', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $text = str()->random(501);
        $request->set('description', $text)
            ->refresh()
            ->assertHasErrors('description')
            ->assertSee('Description cannot exceed 500 characters.');
    });

    test('it udpates description in blade', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $request->set('description', 'New description')
            ->assertSee('New description');
    });


    test('it saved updated description to database if no errors', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


        #update 
        $request->set('description', 'New description');

        expect($conversation->group()->first()->description)->toBe('New description');
    });

    //Photo 


    test('it can save photo to database', function () {
        UploadedFile::fake();


        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        #update 
        $request->set('photo', $file);


        expect($conversation->group()->first()->cover_url)->not->toBe(null);
    });

    test('it deletes previous photo/attachment before saving the new one', function () {
        UploadedFile::fake();


        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        #upload 
        $request->set('photo', $file);

        $previousAttachment = $conversation->group()->first()->cover;



        #upload  again
        $request->set('photo', UploadedFile::fake()->create('new.png'));

        #expect previuus photo no onger available
        expect(Attachment::find($previousAttachment->id))->toBe(null);


        #assert new photo available 

        expect($conversation->group()->first()->cover_url)->not->toBe(null);
        expect($conversation->group()->count())->toBe(1);
    });



    test('it saves save photo to storage', function () {
        UploadedFile::fake();


        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        #update 
        $request->set('photo', $file);

        $attachment = $conversation->group()->first()->cover;


        Storage::disk(WireChat::storageDisk())->assertExists($attachment->file_path);
    });



    test('it dispaches event after saving photo', function () {
        UploadedFile::fake();


        $auth = User::factory()->create(['id' => '345678']);

        $conversation =  $auth->createGroup(name: 'My Group');

        $request =  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        #update 
        $request->set('photo', $file)
            ->assertDispatched('refresh');
    });
});



describe('Deleting Chat', function () {


    test('it redirects to wirechat route after deleting Private conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation =  $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteChat")
            ->assertStatus(200)
            ->assertRedirect(route("wirechat"));;
    });


    test('it redirects to wirechat route after deleting Self conversation', function () {

        $auth = User::factory()->create();

        $conversation =  $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteChat")
            ->assertStatus(200)
            ->assertRedirect(route("wirechat"));;
    });



    test('it aborts if conversation is Group', function () {

        $auth = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteChat")
            ->assertStatus(403,'This operation is not available for Groups.');
    });
});



describe('Deleting Group', function () {

    test('it deletes group from database after delete is successful', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteGroup")
            ->assertStatus(200);

        expect(Conversation::withoutGlobalScopes()->count())->toBe(0);
    });


    test('it redirects to wirechat route after deleting Group conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteGroup")
            ->assertStatus(200)
            ->assertRedirect(route("wirechat"));;
    });


    test('it aborts if conversation is private ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation =  $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteGroup")
            ->assertStatus(403,'Operation not allowed: Private chats cannot be deleted.')
            ->assertNoRedirect();
    });

    test('it aborts if group members is not 0 excluding owner', function () {

        $auth = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        #add members
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteGroup")
            ->assertStatus(403,'Cannot delete group: Please remove all members before attempting to delete the group.');
    });


    test('group can be deleted after removing all members or when if they all remove themselves', function () {

        $auth = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        #add members
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->remove($auth);


        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteGroup")
            ->assertStatus(200);
    
        expect(Conversation::withoutGlobalScopes()->count())->toBe(0);
    });

    test('it aborts if auth is not owner of group', function () {

        $auth = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        #add members
        $nonOwner= User::factory()->create();
        $conversation->addParticipant($nonOwner);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());


        Livewire::actingAs($nonOwner)->test(Info::class, ['conversation' => $conversation])
            ->call("deleteGroup")
            ->assertStatus(403,'Forbidden: You do not have permission to delete this group.');
    });

});

describe('Exiting Chat', function () {


    test('it redirects to wirechat route after exiting conversation', function () {

        $auth = User::factory()->create();


        $user = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);
        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->call("exitConversation")
            ->assertStatus(200)
            ->assertRedirect(route("wirechat"));;
    });




    test('owner cannot exit conversation', function () {

        $auth = User::factory()->create();


        $user = User::factory()->create();


        $conversation =  $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->call('exitConversation')
        ->assertStatus(403,'Owner cannot exit conversation');

        expect($auth->belongsToConversation($conversation))->toBe(true);
    });

});
