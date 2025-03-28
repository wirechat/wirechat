<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\DeleteConversationJob;
use Namu\WireChat\Livewire\Chat\Group\Info;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\Admin;
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
    $auth = User::factory()->create();

    $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(200);
});

test('aborts if conversation is NOT group ', function () {

    $auth = User::factory()->create(['id' => '34567833']);
    $receiver = User::factory()->create(['name' => 'Musa']);

    $conversation = $auth->createConversationWith($receiver, 'hello');

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(403, __('wirechat::chat.group.info.messages.invalid_conversation_type_error'));
});

describe('presence test', function () {

    test('it shows heading', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create(['name' => 'Musa']);

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($receiver);
        $conversation->addParticipant(User::factory()->create());

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee(__('wirechat::chat.group.info.heading.label'));
    });

    test('it shows group name if conversaton is group', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create(['name' => 'Musa']);

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($receiver);
        $conversation->addParticipant(User::factory()->create());

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('dusk="form_group_name_when_not_editing"')
            ->assertSee('My Group');
    });

    test('group description property is wired', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyWired('description');
    });

    test('it doent show photo property wired if auth is not admin', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyNotWired('photo');
    });

    test('it doent show name property wired or edit_group_name_form if auth is not admin', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyNotWired('groupName')
            ->assertDontSeeHtml('@dusk="edit_group_name_form"');
    });

    test('it show name property wired and edit_group_name_form if auth is  admin', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyWired('groupName')
            ->assertSeeHtml('@dusk="edit_group_name_form"');
    });

    test('it doent show description property wired if auth is not admin', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertPropertyNotWired('description');
    });

    test('it shows group description if conversaton is group', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('This is a good group');
    });

    test('it will show add a description if group description is null', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Add a group description');
    });

    test('it shows group members count if conversaton is group', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Members 3');
    });

    test('it shows "add member" if is group', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Add Members');
    });

    test('it shows "Exit Group" and method wired if is group', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($receiver);

        Livewire::actingAs($receiver)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Exit Group')
            ->assertMethodWired('exitConversation');
    });

    test('it doesnt shows "Exit Group" if is not group', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee('Exit Group');
    });

    test('it doesnt shows "Exit Group" if auth is owner', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($receiver);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee('Exit Group')
            ->assertMethodNotWired('exitConversation');
    });

    test('it shows "Delete Group" and method wired if is group and auth is Owner', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Delete Group')
            ->assertMethodWired('deleteGroup')
            ->assertSee('Before you can delete the group, you need to remove all group members');
    });

    test('it shows "Group Permissions" and method wired if is group and auth is Owner', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Group Permissions');
    });

    test('it doenst shows "Delete Group" and method wired if is group and auth is NOT Owner', function () {

        $auth = User::factory()->create();
        $participant = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($participant)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee('Delete Group')
            ->assertMethodNotWired('deleteChat');
    });

    test('it doenst shows "Group Permissions" and method wired if is group and auth is NOT Owner', function () {

        $auth = User::factory()->create();
        $participant = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($participant)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSee('Group Permissions');
    });

});

describe('allowsMembersToEditGroupInfo() Permission accesibility ', function () {

    /**
     * Turning feature ON
     */
    test('when "allow_members_to_edit_group_info" is ON &&  auth is OWNER, it still  shows "edit_group_information_section" & not "non_editable_group_information_section" ', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_edit_group_info = true;
        $group->save();

        $user = User::factory()->create();

        $conversation->addParticipant($user);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="edit_group_information_section"')
            ->assertDontSeeHtml('@dusk="non_editable_group_information_section"');

    });

    test('when "allow_members_to_edit_group_info" is ON &&  auth is ADMIN, it still  shows "edit_group_information_section" & not "non_editable_group_information_section" ', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_edit_group_info = true;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="edit_group_information_section"')
            ->assertDontSeeHtml('@dusk="non_editable_group_information_section"');

    });

    test('when "allow_members_to_edit_group_info" is ON &&  auth is PARTICIPANT, it still show "edit_group_information_section" but shows "non_editable_group_information_section" instead ', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_edit_group_info = true;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="edit_group_information_section"')
            ->assertDontSeeHtml('@dusk="non_editable_group_information_section"');

    });

    /**
     * Turning feature OFF
     */
    test('when "allow_members_to_edit_group_info" is OFF &&  auth is OWNER, it  shows "edit_group_information_section" & not "non_editable_group_information_section" ', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_edit_group_info = false;
        $group->save();

        $user = User::factory()->create();

        $conversation->addParticipant($user);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="edit_group_information_section"')
            ->assertDontSeeHtml('@dusk="non_editable_group_information_section"');

    });

    test('when "allow_members_to_edit_group_info" is OFF &&  auth is ADMIN, it  shows "edit_group_information_section" & not "non_editable_group_information_section" ', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_edit_group_info = false;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="edit_group_information_section"')
            ->assertDontSeeHtml('@dusk="non_editable_group_information_section"');

    });

    test('when "allow_members_to_edit_group_info" is OFF &&  auth is PARTICIPANT, it DOESNT show "edit_group_information_section" but shows "non_editable_group_information_section" instead ', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_edit_group_info = false;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSeeHtml('@dusk="edit_group_information_section"')
            ->assertSeeHtml('@dusk="non_editable_group_information_section"');

    });

});

describe('allowsMembersToAddOthers() Permission accesibility ', function () {

    /**
     * Turning feature ON
     */
    test('when ON it shows "open_add_members_modal_button" if auth is OWNER,', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_add_others = true;
        $group->save();

        $user = User::factory()->create();

        $conversation->addParticipant($user);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="open_add_members_modal_button"');

    });

    test('when ON it shows "open_add_members_modal_button" if auth is ADMIN,', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_add_others = true;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="open_add_members_modal_button"');

    });

    test('when ON it shows "open_add_members_modal_button" if auth is PARTICIPANT,', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_add_others = true;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="open_add_members_modal_button"');

    });

    /**
     * Turning feature OFF
     */
    test('when OFF it shows "open_add_members_modal_button" if auth is OWNER,', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_add_others = false;
        $group->save();

        $user = User::factory()->create();

        $conversation->addParticipant($user);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="open_add_members_modal_button"');

    });

    test('when OFF it shows "open_add_members_modal_button" if auth is ADMIN,', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_add_others = false;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertSeeHtml('@dusk="open_add_members_modal_button"');

    });

    test('when OFF it shows "open_add_members_modal_button" if auth is PARTICIPANT,', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // turn off feature
        $group = $conversation->group;
        $group->allow_members_to_add_others = false;
        $group->save();

        $user = User::factory()->create();
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation])
            ->assertDontSeeHtml('@dusk="open_add_members_modal_button"');

    });

});

describe('updating group name and description', function () {

    // Group name

    test('group name is required', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $request->set('groupName', null)
            ->call('updateGroupName')
            ->assertHasErrors('groupName')
            ->assertSee(__('wirechat::validation.required', ['attribute' => __('wirechat::chat.group.info.inputs.name.label')]));
    });

    test('group name cannot exceed 120 chars', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $text = str()->random(150);
        $request->set('groupName', $text)
            ->call('updateGroupName')
            ->assertHasErrors('groupName')
            ->assertSee(__('wirechat::validation.max.string', ['attribute' => __('wirechat::chat.group.info.inputs.name.label'), 'max' => 120]));

    });

    test('it udpates group name in blade', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $request->set('groupName', 'New Name')
            ->call('updateGroupName')
            ->assertSee('New Name');
    });

    test('it saved the group name to database', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $request->set('groupName', 'New Name')
            ->call('updateGroupName');

        expect($conversation->group()->first()->name)->toBe('New Name');
    });

    test('it dispactches refersh event after upating name', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $request->set('groupName', 'New Name')
            ->call('updateGroupName')
            ->assertDispatched('refresh');
    });

    // Description

    test('description cannot exceed 500 characters', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $text = str()->random(501);
        $request->set('description', $text)
            ->assertHasErrors('description')
            ->assertSee(__('wirechat::validation.max.string', ['attribute' => __('wirechat::chat.group.info.inputs.description.label'), 'max' => 500]));
    });

    test('it udpates description in blade', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $request->set('description', 'New description')
            ->assertSee('New description');
    });

    test('it saved updated description to database if no errors', function () {

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        // update
        $request->set('description', 'New description');

        expect($conversation->group()->first()->description)->toBe('New description');
    });

    // Photo

    test('it can save photo to database', function () {
        UploadedFile::fake();

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        // update
        $request->set('photo', $file);

        expect($conversation->group()->first()->cover_url)->not->toBe(null);
    });

    test('it deletes previous photo/attachment before saving the new one', function () {
        UploadedFile::fake();

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        // upload
        $request->set('photo', $file);

        $previousAttachment = $conversation->group()->first()->cover;

        // upload  again
        $request->set('photo', UploadedFile::fake()->create('new.png'));

        // expect previuus photo no onger available
        expect(Attachment::find($previousAttachment->id))->toBe(null);

        // assert new photo available

        expect($conversation->group()->first()->cover_url)->not->toBe(null);
        expect($conversation->group()->count())->toBe(1);
    });

    test('it saves save photo to storage', function () {
        UploadedFile::fake();

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        // update
        $request->set('photo', $file);

        $attachment = $conversation->group()->first()->cover;

        Storage::disk(WireChat::storageDisk())->assertExists($attachment->file_path);
    });

    test('it dispaches event after saving photo', function () {
        UploadedFile::fake();

        $auth = User::factory()->create(['id' => '345678']);

        $conversation = $auth->createGroup(name: 'My Group');

        $request = Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation]);

        $file = UploadedFile::fake()->create('photo.png');

        // update
        $request->set('photo', $file)
            ->assertDispatched('refresh');
    });
});

describe('Deleting Group', function () {

    test('it deletes group from database after delete is successful', function () {
        // Queue::fake();
        Carbon::setTestNow(now()->subMinutes(4));

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');
        Carbon::setTestNow(now()->addMinutes(1));

        expect(Conversation::withoutGlobalScopes()->count())->toBe(1);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        expect(Conversation::withoutGlobalScopes()->count())->toBe(0);
    });

    test('it pushes DeleteConversationJob', function () {
        Queue::fake();
        $auth = Admin::factory()->create();

        Carbon::setTestNow(now()->subMinutes(4));

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // add members
        $conversation->addParticipant(Admin::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->removeByAdmin($auth);

        Carbon::setTestNow();

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        Queue::assertPushed(DeleteConversationJob::class, function ($job) use ($conversation) {

            return $job->conversation->id == $conversation->id;
        });

    });

    test('it updates participant\'s conversation_deleted_at or soft deletes conversation in general ', function () {
        Queue::fake();
        $auth = Admin::factory()->create();

        Carbon::setTestNow(now()->subMinutes(4));

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $authParticipant = $conversation->participant($auth);

        // assert false
        expect($authParticipant->hasDeletedConversation())->toBe(false);

        // add members & remove members
        $conversation->addParticipant(Admin::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->removeByAdmin($auth);

        Carbon::setTestNow();

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        //   dd($authParticipant);
        // now assert true
        expect($auth->hasDeletedConversation($conversation))->toBe(true);

    });

    test('it does not immediately delete conversation  from database after delete is successful', function () {
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        expect(Conversation::withoutGlobalScopes()->count())->toBe(1);
    });

    test('it redirects to index route and Does NOT dispatch  "close-chat"  & "chat-deleted" events after deleting Group conversation when isNotWidget', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200)
            ->assertRedirect(route(WireChat::indexRouteName()))
            ->assertNotDispatched('close-chat')
            ->assertNotDispatched('chat-deleted');
    });

    test('when isWidget  it dispatches  "close-chat" & "chat-deleted" events  and Does NOT redirects to index route after deleting Group conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation, 'widget' => true])
            ->call('deleteGroup')
            ->assertStatus(200)
            ->assertNoRedirect(route(WireChat::indexRouteName()))
            ->assertDispatched('close-chat')
            ->assertDispatched('chat-deleted');
    });

    test('it removes group from chats list when group is deleted when Info is NOT widget', function () {

        Queue::fake();

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $auth->sendMessageTo($conversation, 'hello');

        // Load Chats component
        $CHATLIST = Livewire::actingAs($auth)->test(Chats::class, ['widget' => true]);

        // Assert conversation is visible
        $CHATLIST->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 1;
        });

        // Load Info component
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        // Assert conversation no longer visible
        $CHATLIST->dispatch('chat-deleted', $conversation->id)->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 0;
        });
    });

    test('it removes group from chats list when group is deleted when Info is Widget', function () {

        Queue::fake();

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $auth->sendMessageTo($conversation, 'hello');

        // Load Chats component
        $CHATLIST = Livewire::actingAs($auth)->test(Chats::class, ['widget' => true]);

        // Assert conversation is visible
        $CHATLIST->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 1;
        });

        // Load Info component
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation, 'widget' => true])
            ->call('deleteGroup')
            ->assertDispatched('chat-deleted')
            ->assertStatus(200);

        // Assert conversation no longer visible
        $CHATLIST->dispatch('chat-deleted', $conversation->id)->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 0;
        });
    });

    test('it aborts if group members is not 0 excluding owner', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // add members
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(403, 'Cannot delete group: Please remove all members before attempting to delete the group.');
    });

    test('it aborts if group of Mixed Model members is not 0 excluding owner', function () {

        $auth = Admin::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // add members
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(403, 'Cannot delete group: Please remove all members before attempting to delete the group.');
    });

    test('group can be deleted after removing all members or when if they all remove themselves', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // add members
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->removeByAdmin($auth);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        expect(Conversation::withoutGlobalScopes()->count())->toBe(0);
    });

    test('group can be deleted after removing all members of Mixed Models or when if they all remove themselves', function () {

        $auth = Admin::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // add members
        $conversation->addParticipant(Admin::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->exitConversation();
        $conversation->addParticipant(User::factory()->create())->removeByAdmin($auth);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(200);

        expect(Conversation::withoutGlobalScopes()->count())->toBe(0);
    });

    test('it aborts if auth is not owner of group', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // add members
        $nonOwner = User::factory()->create();
        $conversation->addParticipant($nonOwner);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        Livewire::actingAs($nonOwner)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteGroup')
            ->assertStatus(403, 'Forbidden: You do not have permission to delete this group.');
    });

});

describe('Exiting Chat', function () {

    test('it redirects to index route and Does NOT dispatch  "close-chat"  & "chat-exited" events after exiting Group conversation when isNotWidget', function () {

        $auth = User::factory()->create();

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);
        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation, 'widget' => false])
            ->call('exitConversation')
            ->assertStatus(200)
            ->assertRedirect(route(WireChat::indexRouteName()))
            ->assertNotDispatched('close-chat')
            ->assertNotDispatched('chat-exited');
    });

    test('when isWidget  it dispatches  "close-chat" & "chat-exited" events  and Does NOT redirects to index route after exiting Group conversation', function () {

        $auth = User::factory()->create();

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);
        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation, 'widget' => true])
            ->call('exitConversation')
            ->assertStatus(200)
            ->assertNoRedirect(route(WireChat::indexRouteName()))
            ->assertDispatched('close-chat')
            ->assertDispatched('chat-exited');
    });

    test('it removes conversatoin from chats list when group is exited', function () {

        Queue::fake();

        $auth = User::factory()->create();
        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        // Add particitpant
        $conversation->addParticipant($user);
        // add message
        $user->sendMessageTo($conversation, 'Never let go ');

        // Load Chats component
        $CHATLIST = Livewire::actingAs($user)->test(Chats::class);

        // Assert conversation is visible
        $CHATLIST->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 1;
        });

        // Load Info component
        Livewire::actingAs($user)->test(Info::class, ['conversation' => $conversation, 'widget' => true])
            ->call('exitConversation')
            ->assertStatus(200)
            ->assertDispatched('chat-exited');

        // Assert conversation no longer visible
        $CHATLIST->dispatch('chat-exited', $conversation->id)->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 0;
        });
    });

    test('owner cannot exit conversation', function () {

        $auth = User::factory()->create();

        $user = User::factory()->create();

        $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

        $conversation->addParticipant($user);
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('exitConversation')
            ->assertStatus(403, 'Owner cannot exit conversation');

        expect($auth->belongsToConversation($conversation))->toBe(true);
    });

});
