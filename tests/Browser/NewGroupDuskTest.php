<?php

namespace Namu\WireChat\Tests\Browser;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Livewire\Components\NewGroup;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Tests\DuskTestCase;
use Workbench\App\Models\User;

class NewGroupDuskTest extends DuskTestCase
{


    /** @test */
    public function group_name_label_and_input_is_set_correctly()
    {

        $auth = User::factory()->create();
        Livewire::actingAs($auth)
            ->visit(NewGroup::class)
            ->assertSee('Group Name')
            ->assertPresent('#name');
    }

    /** @test */
    public function add_photo_button_is_set_correctly()
    {

        $auth = User::factory()->create();

        Livewire::actingAs($auth)
            ->visit(NewGroup::class)
            ->assertPresent('@add_photo_field');
    }


    /** @test */
    public function group_description_label_and_input_is_set_correctly()
    {

        $auth = User::factory()->create();
        Livewire::actingAs($auth)
            ->visit(NewGroup::class)
            ->assertSee('Description')
            ->assertPresent('#description');
    }


    /** @test */
    public function Cancel_and_next_button_is_set()
    {

        Config::set('wirechat.allow_new_group_modal', true);
        $auth = User::factory()->create();
        Livewire::actingAs($auth)
            ->visit(NewGroup::class)
            ->assertPresent('@cancel_create_new_group_button')
            ->assertSee('Cancel')
            ->assertPresent('@next_button')
            ->assertSee('Next');
    }



    /** @test */
    public function next_button_is_disabled_if_name_input_empty()
    {

        Config::set('wirechat.allow_new_group_modal', true);
        $auth = User::factory()->create();
        Livewire::actingAs($auth)
            ->visit(NewGroup::class)
            ->assertButtonDisabled('@next_button');
    }



      /** @test */
      public function next_button_is_enabled_if_name_input_not_empty()
      {
  
          Config::set('wirechat.allow_new_group_modal', true);
          $auth = User::factory()->create();
          Livewire::actingAs($auth)
              ->visit(NewGroup::class)
              ->waitForLivewire()
              ->typeSlowly('#name','Test')
              ->refresh()
              ->assertButtonEnabled('@next_button');
      }


    //    /** @test */
    //    public function it_shows_add_members_section_if_next_is_clicked()
    //    {
   
    //        Config::set('wirechat.allow_new_group_modal', true);
    //        $auth = User::factory()->create();
    //        Livewire::actingAs($auth)
    //            ->visit(NewGroup::class)
    //            ->assertNotVisible('@add_members_section')//assert not present before
    //            ->typeSlowly('#name','Test')
    //            ->refresh()
    //            ->click('@next_button')
    //            ->assertVisible('@add_members_section');
    //    }
}
