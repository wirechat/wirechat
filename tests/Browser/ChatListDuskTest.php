<?php
namespace Namu\WireChat\Tests\Browser;
use Illuminate\Support\Facades\Config;
use Laravel\Dusk\Browser;
use Livewire\Component;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chatlist;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Tests\DuskTestCase;
use Workbench\App\Models\User;

 
class ChatlistDuskTest extends DuskTestCase
{

/** @test */
public function it_shows_chats_title()
{
    Config::set("wirechat.allow_chats_search", true);
    $auth = User::factory()->create();

    $this->withoutExceptionHandling();

    Livewire::actingAs($auth)->visit(Chatlist::class)->assertSee('Chats');

}

/** @test */
public function it_shows_redirect_button()
{
    $auth = User::factory()->create();

    Livewire::actingAs($auth)->visit(Chatlist::class) ->assertVisible('#redirect-button');
}

/** @test */
public function it_shows_search_field_if_enabled_in_config()
{
    Config::set("wirechat.allow_chats_search", true);
    $auth = User::factory()->create();

    Livewire::actingAs($auth)
        ->visit(Chatlist::class)
        ->assertVisible('#chats-search-field');
}

 /** @test */
public function it_does_not_show_search_field_if_not_enabled_in_config()
{
    Config::set("wirechat.allow_chats_search", false);
    $auth = User::factory()->create();

    Livewire::actingAs($auth)
        ->visit(Chatlist::class)
        ->assertNotPresent('#chats-search-field');
}

 /** @test */
public function it_shows_new_chat_modal_button_if_enabled_in_config()
{
    Config::set("wirechat.allow_new_chat_modal", true);
    $auth = User::factory()->create();

    Livewire::actingAs($auth)
        ->visit(Chatlist::class)
        
        ->assertVisible('#open-new-chat-modal-button');
}


/** @test */
public function it_does_not_show_new_chat_modal_button_if_not_enabled_in_config()
{
    Config::set("wirechat.allow_new_chat_modal", false);
    $auth = User::factory()->create();

    Livewire::actingAs($auth)
        ->visit(Chatlist::class)
        ->assertNotPresent('#open-new-chat-modal-button');
        
}



/** @test */
public function it_shows_loadMoreButton_if_user_can_loadMore()
{
    Config::set("wirechat.allow_new_chat_modal", false);
    $auth = User::factory()->create();

    for ($i=0; $i < 12; $i++) { 

        $user= Conversation::factory()->create();

        $auth->createConversationWith($user,'hello');


    }

    Livewire::actingAs($auth)
        ->visit(Chatlist::class)
        ->assertPresent('@loadMoreButton')
        ->assertSee('Load more');
        
}


/** @test */
public function it_does_not_show_loadMoreButton_if_user_cannot_loadMore()
{

    $auth = User::factory()->create();

    for ($i=0; $i < 10; $i++) { 

        $user= Conversation::factory()->create();

        $auth->createConversationWith($user,'hello');


    }

    Livewire::actingAs($auth)
        ->visit(Chatlist::class)
        ->assertNotPresent('@loadMoreButton');
        
}


///Interation


 /** @test */
 public function it_can_filter_chats_by_entering_text_in_search()
 {

    Config::set("wirechat.allow_chats_search", true);

    $auth = User::factory()->create();

    $user1 = User::factory()->create(['name' => 'iam user 1']);
    $user2 = User::factory()->create(['name' => 'iam user 2']);


    //create conversation with user1
    $auth->createConversationWith($user1, 'hello');


    //create conversation with user2
    $auth->createConversationWith($user2, 'new message');

    $request= Livewire::actingAs($auth)
         ->visit(Chatlist::class);

    //Assert both conversations visible before typing
    $request->assertSee('iam user 1')->assertSee('iam user 2');

    //type
    $request->typeSlowly('#chats-search-field','iam user 1')
            ->refresh();

    //assert only one visible after typing
    $request->assertSee('iam user 1')->refresh()->assertDontSee('iam user 2');

         
 }


  /** @test */
  public function It_opens_modal_when_open_new_chat_modal_button_button_is_tapped()
  {
 
     Config::set("wirechat.allow_chats_search", true);
 
     $auth = User::factory()->create();
 
     $user1 = User::factory()->create(['name' => 'iam user 1']);
     $user2 = User::factory()->create(['name' => 'iam user 2']);
 
 
     //create conversation with user1
     $auth->createConversationWith($user1, 'hello');
 
 
     //create conversation with user2
     $auth->createConversationWith($user2, 'new message');
 
     $request= Livewire::actingAs($auth)
          ->visit(Chatlist::class);
 
     //Assert both conversations visible before typing
     $request->assertSee('iam user 1')->assertSee('iam user 2');

     //assert not visible 
     $request->assertNotVisible('#new-chat-modal');
 
     //Click and assert now visible
     $request->click("#open-new-chat-modal-button")
            ->assertVisible('#new-chat-modal')
            ->assertSee('Send message');
          
  }

    /** @test */
    public function assert_open_new_chat_modal_information_is_correct()
    {
   
       Config::set("wirechat.allow_chats_search", true);
   
       $auth = User::factory()->create();
   
       $user1 = User::factory()->create(['name' => 'iam user 1']);
       $user2 = User::factory()->create(['name' => 'iam user 2']);
   
   
       //create conversation with user1
       $auth->createConversationWith($user1, 'hello');
   
   
       //create conversation with user2
       $auth->createConversationWith($user2, 'new message');
   
       $request= Livewire::actingAs($auth)
            ->visit(Chatlist::class);
   
       //Assert both conversations visible before typing
       $request->assertSee('iam user 1')->assertSee('iam user 2');
  
       //assert not visible 
       $request->assertNotVisible('#new-chat-modal');
   
       //Click and assert now visible
       $request->click("#open-new-chat-modal-button")
              ->assertSee('Send message')
              ->assertSee('To:')
              ->assertVisible('#users-search-field')
              ->assertSee('No accounts found');
            
    }


        /** @test */
        public function it_filters_users_when_user_types()
        {
       
           Config::set("wirechat.allow_chats_search", true);
       
           $auth = User::factory()->create();
       
           $user1 = User::factory()->create(['name' => 'iam user 1']);
           $user2 = User::factory()->create(['name' => 'iam user 2']);
       

           User::factory()->create(['name'=> 'john']);
       
           //create conversation with user1
           $auth->createConversationWith($user1, 'hello');
       
       
           //create conversation with user2
           $auth->createConversationWith($user2, 'new message');
       
           $request= Livewire::actingAs($auth)
                ->visit(Chatlist::class);
       
           //Assert both conversations visible before typing
           $request->assertSee('iam user 1')->assertSee('iam user 2');
      
           //assert not visible 
           $request->assertNotVisible('#new-chat-modal');
       
           //Click and assert now visible
           $request->click("#open-new-chat-modal-button")
                    ->typeSlowly('#users-search-field','iam user 1')
                    ->assertSee('john')
                    ->assertDontSee('No accounts found');

                
        }


            /** @test */
            public function it_shows_suffix_you_if_user_has_self_conversation()
            {
           
                $auth = User::factory()->create(['name' => 'Test']);

                //create conversation with user1
                $auth->createConversationWith($auth,'hello');

               $request= Livewire::actingAs($auth)
                    ->visit(Chatlist::class);
           
               //Assert both conversations visible before typing
               $request
               ->assertSee('Test')
               ->assertSee('(You)');
    
                    
            }
    


}
// describe("Interaction", function () {


//     test('It can filter chats by entering text in search', function () {
//         Config::set("wirechat.allow_chats_search", true);

//         $auth = User::factory()->create();

//         $user1 = User::factory()->create(['name' => 'iam user 1']);
//         $user2 = User::factory()->create(['name' => 'iam user 2']);


//         //create conversation with user1
//         $auth->createConversationWith($user1, 'hello');


//         //create conversation with user2
//         $auth->createConversationWith($user2, 'new message');


//         $request =Livewire::actingAs($auth)->visit(Chatlist::class)
//                         ->visit('/chats');
        
//         //Assert both conversations visible before typing
//         $request->see('iam user 1')->see('iam user 2');

//         //type
//         $request->type('iam user 1',"#chats-search-field");

//         //assert only one visible after typing
//         $request->see('iam user 1')->dontSee('iam user 2');

//     });


//     test('It opens modal when open-new-chat-modal-button button is tapped', function () {
//         Config::set("wirechat.allow_chats_search", true);

//         $auth = User::factory()->create();

//         $user1 = User::factory()->create(['name' => 'iam user 1']);
//         $user2 = User::factory()->create(['name' => 'iam user 2']);


//         //create conversation with user1
//         $auth->createConversationWith($user1, 'hello');


//         //create conversation with user2
//         $auth->createConversationWith($user2, 'new message');


//         $request =Livewire::actingAs($auth)->visit(Chatlist::class)
//                         ->visit('/chats')->click("#chats-search-field");
        
//         //Assert both conversations visible before typing
//         $request->see('iam user 1')->see('iam user 2');

//         //type
//         $request->click("#chats-search-field")->type('iam user 1',"chats_search")->seeInField('chats_search','iam user 1');

//         //assert only one visible after typing
//         $request->see('iam user 1')->dontSee('iam user 2');

//     })->only();

//     test('It can redirect to Chats view page', function () {

//         $auth = User::factory()->create();

//         $user1 = User::factory()->create(['name' => 'iam user 1']);
//         $user2 = User::factory()->create(['name' => 'iam user 2']);


//         //create conversation with user1
//         $auth->createConversationWith($user1, 'hello');


//         //create conversation with user2
//         $auth->createConversationWith($user2, 'new message');


//         $this->withoutExceptionHandling();

//         $request =Livewire::actingAs($auth)->visit(Chatlist::class)
//             ->visit('/chats')
//             ->see('iam user 1')
//             ->click("iam user 1")
//             ->seePageIs('/chats/1');
//     });
// });
