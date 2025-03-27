<?php

// /Presence test

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\New\Group as NewGroup;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User as ModelsUser;

beforeEach(function () {

    Storage::fake(WireChat::storageDisk());
});

it('user must be authenticated', function () {
    $auth = ModelsUser::factory()->create();
    $request = Livewire::test(NewGroup::class);

    $request->assertStatus(401);

});

describe('Initial page', function () {

    test('Name label is set and property is wired', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->assertSee('Group Name')
            ->assertPropertyWired('name');

    });

    test('Name can be set', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->set('name', 'Test')
            ->assertSet('name', 'Test');

    });

    test('Description label is set and property is wired', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->assertPropertyWired('description')
            ->assertSee('Description');

    });

    test('Description can be set', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->set('description', 'Test Description')
            ->assertSet('description', 'Test Description');
    });

    test('Cancel button is set', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->assertSee('Cancel');
        $request->assertSeeHtml('dusk="cancel_create_new_group_button"');
        $request->assertContainsBladeComponent('wirechat::actions.close-modal');

    });

    test('cancel_create_new_group_button_is_set_correctly', function () {

        $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->assertSeeHtml('dusk="cancel_create_new_group_button"');
        $request->assertContainsBladeComponent('wirechat::actions.close-modal');

    });

    test('Next button is set', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->assertSee('Next');

    });

    test('it aborts if user canCreateNewGroups==FALSE (email  NOT is verified) in our case', function () {

        $auth = ModelsUser::factory()->create(['email_verified_at' => null]);
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->assertStatus(403, 'You do not have permission to create groups.');

    });

    test('it does not abort if canCreateNewGroups==TRUE (email is verified) in our case', function () {

        $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->assertStatus(200);

    });

});

describe('Validations', function () {

    // validations

    test('Photo must be an image', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);

        $file = UploadedFile::fake()->create('photo.mp3', '400');
        $request->set('photo', $file)
            ->call('validateDetails')
            ->assertHasErrors('photo');

    });

    test('A group name is required', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->set('name', null)
            ->call('validateDetails')
            ->assertHasErrors('name', 'required');
    });

    test('Name is max 120', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->set('name', str()->random(150))
            ->call('validateDetails')
            ->assertHasErrors('name', 'max');
    });

    test('Description is max 500', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->set('description', str()->random(501))
            ->call('validateDetails')
            ->assertHasErrors('description');
    });

    test('it sets add members to true after validations passed', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request->set('name', 'test')
            ->call('validateDetails')
            ->assertHasNoErrors()
            ->assertSet('showAddMembers', true);
    });

});

describe('Add members page', function () {

    test('Add Members title is set', function () {

        Config::set('wirechat.max_group_members', 1000);

        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);

        $maxGroupMembers = WireChat::maxGroupMembers();

        $request
            ->set('showAddMembers', true)
            ->assertSee('Add Members')
            ->assertSee('0 / 1000');

    });

    test('Create button is set and wired', function () {
        $auth = ModelsUser::factory()->create();
        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->set('showAddMembers', true)
            ->assertSee('Create');
    });

    test('Search can be filtered', function () {
        $auth = ModelsUser::factory()->create();
        // create another user
        ModelsUser::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->set('search', 'Mic')
            ->assertSee('Micheal');

    });

    test('calling addMember()  can add selected members', function () {
        $auth = ModelsUser::factory()->create();
        // create another user
        $user = ModelsUser::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
            ->call('addMember', $user->id, $user->getMorphClass())
            ->assertSee('Micheal');

    });

    test('calling removeMember() can remove selected members', function () {
        $auth = ModelsUser::factory()->create();
        // create another user
        $user = ModelsUser::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
                // first add member
            ->call('addMember', $user->id, $user->getMorphClass())
            ->assertSee('Micheal')
                // then remove memener
            ->call('removeMember', $user->id, $user->getMorphClass())
            ->assertDontSee('Micheal');
    });

    test('show error if member limit is exceeded', function () {

        Config::set('wirechat.max_group_members', 2);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $request
                // first add member
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())
            ->assertSee(__('wirechat::new.group.messages.members_limit_error', ['count' => 2]));
    });

});

describe('Creteing group', function () {

    it('can create conversation  is validations pass', function () {
        Config::set('wirechat.max_group_members', 3);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $conversation = Conversation::withoutGlobalScopes()->first();

        expect($conversation->type)->toBe(ConversationType::GROUP);
        expect($conversation)->not->toBe(null);
    });

    it('Creates group model', function () {
        Config::set('wirechat.max_group_members', 3);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $conversation = Conversation::withoutGlobalScopes()->first();

        expect($conversation->group)->not->toBe(null);
    });

    it('creates attachment images if uploaded', function () {
        Config::set('wirechat.max_group_members', 3);

        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $conversation = Conversation::withoutGlobalScopes()->first();

        $attachment = Attachment::first();
        expect($attachment)->not->toBe(null);
        Storage::disk(WireChat::storageDisk())->assertExists($attachment->file_path);

        expect($conversation->group->cover)->not->toBe(null);

    });

    it('creates participants', function () {
        Config::set('wirechat.max_group_members', 3);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $conversation = Conversation::withoutGlobalScopes()->first();

        expect($conversation->participants->count())->toBe(4);
    });

    it('dispataches Livewire events "closeWireChatModal" event after creating Group', function () {

        Config::set('wirechat.max_group_members', 3);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $request->assertDispatched('closeWireChatModal');

    });

    it('it redirects and does not dispatach Livewire events "open-chat" events after creating Group if is not Widget', function () {

        Config::set('wirechat.max_group_members', 3);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $conversation = Conversation::withoutGlobalScopes()->first();

        $request->assertRedirect(route(WireChat::viewRouteName(), $conversation->id))
            ->assertNotDispatched('open-chat');

    });

    it('it does not redirects but  dispataches Livewire events "open-chat" events after creating group if IS Widget', function () {

        Config::set('wirechat.max_group_members', 3);
        $auth = ModelsUser::factory()->create();
        // create another user
        $member1 = ModelsUser::factory()->create(['name' => 'Micheal']);
        $member2 = ModelsUser::factory()->create(['name' => 'Boost']);
        $member3 = ModelsUser::factory()->create(['name' => 'Ultra']);

        $request = Livewire::actingAs($auth)->test(NewGroup::class, ['widget' => true]);
        $file = UploadedFile::fake()->create('photo.png');

        $request
                // add details
            ->set('name', 'Test Group')
            ->set('description', 'Description Testing')
            ->set('photo', $file)
                // add members
            ->call('addMember', $member1->id, $member1->getMorphClass())
            ->call('addMember', $member2->id, $member2->getMorphClass())
            ->call('addMember', $member3->id, $member3->getMorphClass())

                // create group
            ->call('create');

        $conversation = Conversation::withoutGlobalScopes()->first();

        $request->assertNoRedirect(route(WireChat::viewRouteName(), $conversation->id))
            ->assertDispatched('open-chat');

    });

});
