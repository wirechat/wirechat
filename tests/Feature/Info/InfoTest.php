<?php

use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Info\Info;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation= Conversation::factory()->create();
    Livewire::test(Info::class,['conversation'=>$conversation])
        ->assertStatus(401);
});

test('authenticaed user can access info ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->withParticipants([$auth])->create();

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(200);
});


describe('presence test',function(){


test('it shows receiver name if conversaton is private', function () {
    $auth = User::factory()->create(['id' => '345678']);
    $receiver=User::factory()->create(['name' => 'Musa']);

    $conversation =  $auth->createConversationWith($receiver,'hello');

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                         ->assertSee("Musa");
});


test('it shows group name if conversaton is group', function () {

    $auth = User::factory()->create(['id' => '345678']);
    $receiver=User::factory()->create(['name' => 'Musa']);

    $conversation =  $auth->createGroup(name:'My Group',description:'This is a good group');

    $conversation->addParticipant($receiver);
    $conversation->addParticipant(User::factory()->create());


    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                         ->assertSee("My Group");
});


test('group description property is wired', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation =  $auth->createGroup(name:'My Group',description:'This is a good group');


    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                         ->assertPropertyWired("description");
});

test('it shows group description if conversaton is group', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation =  $auth->createGroup(name:'My Group',description:'This is a good group');
    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                         ->assertSee("This is a good group");
});


test('it will show add a description if group description is null', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation =  $auth->createGroup(name:'My Group');


    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                         ->assertSee("Add a group description");
});


test('it shows group members count if conversaton is group', function () {

    $auth = User::factory()->create(['id' => '345678']);


    $conversation =  $auth->createGroup(name:'My Group',description:'This is a good group');

    $conversation->addParticipant(User::factory()->create());
    $conversation->addParticipant(User::factory()->create());


    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
                         ->assertSee("Members 3");
});


});



describe('updating group name and description',function(){


    //Group name 

    test('group name is required', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation =  $auth->createGroup(name:'My Group');

        $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $request->set('groupName',null)
                ->call('updateGroupName')
                ->assertHasErrors('groupName')
                ->assertSee('The Group name cannot be empty');
    });


    test('group name cannot exceed 500 chars', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation =  $auth->createGroup(name:'My Group');

        $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $text = str()->random(150);
       $request->set('groupName',$text)
                ->call('updateGroupName')
               ->assertHasErrors('groupName')
                ->assertSee('Group name cannot exceed 120 characters.');
    });


    test('it udpates group name in blade', function () {

        $auth = User::factory()->create(['id' => '345678']);
    
        $conversation =  $auth->createGroup(name:'My Group');

       $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $request->set('groupName','New Name')
                ->call('updateGroupName')
                ->assertSee('New Name');
    });


    test('it saved ne group name to database', function () {

        $auth = User::factory()->create(['id' => '345678']);
    
        $conversation =  $auth->createGroup(name:'My Group');

       $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $request->set('groupName','New Name')
                ->call('updateGroupName');

        expect($conversation->group()->first()->name)->toBe('New Name');
    });


    test('it dispactches refersh event after upating name', function () {

        $auth = User::factory()->create(['id' => '345678']);
    
        $conversation =  $auth->createGroup(name:'My Group');

       $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $request->set('groupName','New Name')
                ->call('updateGroupName')
                ->assertDispatched('refresh');

    });


    //Description 
    
    test('description cannot exceed 500 chars', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation =  $auth->createGroup(name:'My Group');

        $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $text = str()->random(501);
       $request->set('description',$text)
                ->refresh()
                ->assertHasErrors('description')
                ->assertSee('Description cannot exceed 500 characters.');
    });

    test('it udpates description in blade', function () {

        $auth = User::factory()->create(['id' => '345678']);
    
        $conversation =  $auth->createGroup(name:'My Group');

       $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $request->set('description','New description')
                ->assertSee('New description');
    });



    test('it saved updated description to database if no errors', function () {

        $auth = User::factory()->create(['id' => '345678']);
    
        $conversation =  $auth->createGroup(name:'My Group');

       $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


       #update 
       $request->set('description','New description');

        expect($conversation->group()->first()->description)->toBe('New description');
    });

    // test('it dispactches refersh event after description', function () {

    //     $auth = User::factory()->create(['id' => '345678']);
    
    //     $conversation =  $auth->createGroup(name:'My Group');

    //    $request=  Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);


    //    #update 
    //    $request->set('description','New description')->assertDispatched('refresh');;

    // });




});
