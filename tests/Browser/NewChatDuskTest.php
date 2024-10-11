<?php

namespace Namu\WireChat\Tests\Browser;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Livewire\Components\NewChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Tests\DuskTestCase;
use Workbench\App\Models\User;

class NewChatDuskTest extends DuskTestCase
{




    /** @test */
    public function title_is_set_correctly()
    {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)
                ->visit(NewChat::class)
                ->assertSee('New Chat');
    }

     /** @test */
     public function close_modal_button_is_set_correctly()
     {
 
         $auth = User::factory()->create();
         $receiver = User::factory()->create(['name' => 'John']);
 
         $auth->createConversationWith($receiver);
 
         Livewire::actingAs($auth)
                 ->visit(NewChat::class)
                 ->assertPresent('@close_modal_button');
     }


       /** @test */
       public function search_users_field_is_set_correctly()
       {
   
           $auth = User::factory()->create();
           $receiver = User::factory()->create(['name' => 'John']);
   
           $auth->createConversationWith($receiver);
   
           Livewire::actingAs($auth)
                   ->visit(NewChat::class)
                   ->assertPresent('@search_users_field');
       }


         /** @test */
         public function new_group_button_is_show_if_allowed()
         {

             Config::set('wirechat.allow_new_group_modal',true);
             $auth = User::factory()->create();
             $receiver = User::factory()->create(['name' => 'John']);
     
             $auth->createConversationWith($receiver);
     
             Livewire::actingAs($auth)
                     ->visit(NewChat::class)
                     ->assertSee('New group')
                     ->assertPresent('@open_new_group_modal_button');
         }


             
            //  public function clicking_new_group_button_opens_new_group_modal()
            //  {

            //      Config::set('wirechat.allow_new_group_modal',true);
            //      $auth = User::factory()->create();
            //      $receiver = User::factory()->create(['name' => 'John']);
         
            //      $auth->createConversationWith($receiver);
         
            //      Livewire::actingAs($auth)
            //              ->visit(NewChat::class)
            //             //  ->waitForLivewire()
            //              ->click('@open_new_group_modal_button')
            //              ->refresh()
            //              ->assertPresent('@new_group_modal')
            //              ;
            //  }
        
    
  

    /**
     * ---
     * Testing Footer
     */




}
