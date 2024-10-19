<?php

namespace Namu\WireChat\Tests\Browser;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Laravel\Dusk\Browser;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Livewire\info\Members;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Tests\DuskTestCase;
use Workbench\App\Models\User;

class MembersDuskTest extends DuskTestCase
{





    /**
     * ------------
     * Presence
     * ------------
     */

   
    /** @test */
    public function title_is_set_correctly()
    {

        $auth = User::factory()->create(['name'=>'John']);

        $conversation = $auth->createGroup('My Group');
        
        $auth->sendMessageTo($conversation,'Hello');
        
        $component = new class($conversation) extends Members {
            public Conversation $conversation;
    
            // Livewire will automatically call this with the parameters passed in `visit()`
            public function mount(Conversation $conversation)
            {
               // dd($conversation->id);
                $this->conversation = $conversation;
            }
        };


        $this->browse(function (Browser $browser)use($conversation) {
            Livewire::visit($browser, Members::class,['convesation'=>$conversation])
                ->pause(2000)
                ->assertSee('Members') ;
        });
    
        // Livewire::actingAs($auth)
        //         ->visit($component,$conversation)
        // ->pause(2000)

        //         ->assertSee('Members')  ;

        
    }

     /** @test */
     public function close_modal_button_is_set_correctly()
     {
 

         $auth = User::factory()->create();
         $receiver = User::factory()->create(['name' => 'John']);
 
         $conversation = $auth->createGroup('My Group');
         $auth->sendMessageTo($conversation,'Hello');

 
         $component = new class() extends Members {
            public Conversation $conversation;
    
            // Livewire will automatically call this with the parameters passed in `visit()`
            public function mount(Conversation $conversation)
            {
                $this->conversation =Conversation::withoutGlobalScopes()->first();
            }
        };
    
        Livewire::actingAs($auth)
                ->visit($component, ['conversation' => $conversation])
                 ->assertPresent('@close_modal_button');

     }



     /** @test */
     public function it_loads_members()
     {
 
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participants
        $conversation->addParticipant(User::factory()->create(["name"=>"John"]));
        $conversation->addParticipant(User::factory()->create(["name"=>"Lemon"]));
        $conversation->addParticipant(User::factory()->create(["name"=>"Cold"]));
        $auth->sendMessageTo($conversation,'Hello');

        $component = new class() extends Members {
            public Conversation $conversation;
    
            // Livewire will automatically call this with the parameters passed in `visit()`
            public function mount(Conversation $conversation)
            {
                $this->conversation =Conversation::withoutGlobalScopes()->first();
            }
        };
    
        Livewire::actingAs($auth)
                ->visit($component, ['conversation' => $conversation])
                ->assertSee('John')
                ->assertSee('Lemon')
                ->assertSee('Cold');



     }



     /** @test */
     public function it_shows_you_if_member_in_loop_is_auth()
     {
 
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');
        $auth->sendMessageTo($conversation,'Hello');

        #add participants
        $conversation->addParticipant(User::factory()->create(["name"=>"John"]));
        $conversation->addParticipant(User::factory()->create(["name"=>"Lemon"]));
        $conversation->addParticipant(User::factory()->create(["name"=>"Cold"]));

        $component = new class() extends Members {
            public Conversation $conversation;
    
            // Livewire will automatically call this with the parameters passed in `visit()`
            public function mount(Conversation $conversation)
            {
                $this->conversation =Conversation::withoutGlobalScopes()->first();
            }
        };
    
        Livewire::actingAs($auth)
                ->visit($component, ['conversation' => $conversation])
                 ->assertSee('You');

     }


     /** @test */
     public function it_shows_load_more_if_user_can_load_more_than_10 ()
     {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participants
        Participant::factory(20)->create(['conversation_id'=>$conversation->id]);

         Livewire::actingAs($auth)

                 ->visit(Members::class,$conversation)
                 ->assertSee('Load more');

     }


       /** @test */
       public function it_dosent_shows_load_more_if_user_cannot_load_more_than_10 ()
       {
          $auth = User::factory()->create();
          $conversation = $auth->createGroup('My Group');
  
          #add participants
          Participant::factory(4)->create(['conversation_id'=>$conversation->id]);
  

          $component = new class() extends Members {
            public Conversation $conversation;
    
            // Livewire will automatically call this with the parameters passed in `visit()`
            public function mount(Conversation $conversation)
            {
                $this->conversation =Conversation::withoutGlobalScopes()->first();
            }
        };
    
        Livewire::actingAs($auth)
                ->visit($component, ['conversation' => $conversation])
                   ->assertDontSee('Load more');
  
       }
  
    //    /** @test */
    //    public function search_users_field_is_set_correctly()
    //    {
   
    //        $auth = User::factory()->create();
    //        $receiver = User::factory()->create(['name' => 'John']);
   
    //        $auth->createConversationWith($receiver);
   
    //        Livewire::actingAs($auth)
    //                ->visit(Members::class)
    //                ->assertPresent('@search_users_field');
    //    }


    //      /** @test */
    //      public function new_group_button_is_show_if_allowed()
    //      {

    //          Config::set('wirechat.allow_new_group_modal',true);
    //          $auth = User::factory()->create();
    //          $receiver = User::factory()->create(['name' => 'John']);
     
    //          $auth->createConversationWith($receiver);
     
    //          Livewire::actingAs($auth)
    //                  ->visit(Members::class)
    //                  ->assertSee('New group')
    //                  ->assertPresent('@open_new_group_modal_button');
    //      }

 
 



}
