
This is a Laravel Private & Group  messaging application made with Livewire v3, it is used to provide a convinient way to allows users to communicate with eachother in the application 

## Prerequisites

Before we start, make sure you have the following installed:

* PHP version 8.1 or later
* Laravel version 10.47 or later
* Livewire version 3.2.3 or later


## Installing

Install package via composer 

```shell
composer require namu/wirechat
```


1. publish the Migration file 
```
php artisan vendor:publish --tag=wirechat-migration
```
2. Run the migrations:
```
php artisan migrate
```

4. Create storage symlink
```
 php artisan storage:link
```


### Building Tailwind CSS for production
To purge the classes used by the package, add the following lines to your purge array in tailwind.config.js:

```
   './wirechat/resources/views/**/*.blade.php',
   './wirechat/src/Livewire/*.php',
```

## WebSockets and Queue Setup

Wirechat leverages WebSockets to broadcast messages in real-time to conversations and their participants.

#### Step 1: Enable Broadcasting

By default, broadcasting is disabled in new Laravel installations. To enable it, run the following Artisan command:

```bash
php artisan install:broadcasting
```

This command sets up the necessary broadcasting configurations for your application.

#### Step 2: Set Up Broadcasting and Queues
Ensure both broadcasting and your queue workers are properly configured. You can follow the [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting) for detailed setup instructions, including integrating Laravel Echo for real-time updates.

#### Step 3: Start Your Queue Worker
Once broadcasting is configured, you'll need to start your queue worker to handle message broadcasting and other queued tasks. Run the following command:

```bash
php artisan queue:work
```

Make sure your queue workers are running continuously to handle queued events, ensuring proper message broadcasting in real-time.

## Usage

### Traits

#### `Namu\WireChat\Traits\Chatable`

```php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Namu\WireChat\Traits\Chatable;

class User extends Authenticatable
{
    use Chatable;

    ...
}


```

 

### API


```php

$user = User::find(1);
$auth = auth()->user();
$conversation =Conversation::first();

//get conversations 
$conversations = $auth->conversations()->get();

///send a message to another user ,Note: if no conversation is available yet between the two users , one will be created 
$auth->sendMessageTo($user,'message'); //returns created $message model


//Create a conversation :Note if a conversation already exists , it will return the existing conversation
$auth->createConversationWith($user,'message');// returns $conversation model 


//Check if user belongs to a conversation
$auth->belongsToConversation($conversation);//bool


//Check if the user has a conversation with another user.
$auth->hasConversationWith($user); // bool


///Deleting conversation :note this will only delete or hide messages from the user who deleted the conversation 
//messages will be retained with the other user , the conversation will only permanenlty be deleted if the other 
//user also deleted the conversation 
$auth->deleteConversationWith($user);


```

### Aggregations
Here you can retrieve items from the pivot table to we can get the count()

```php
//Get All unread messages count (This includes all user conversations)
$user->getUnreadCount(); //int

//Get count for specific conversation
$conversation = $user->conversations()->first();
$user->getUnreadCount($conversation); //int


```

Using withCount() attribute:

```php
//Conversations
$conversations = Conversation::withCount('messages')->get();


foreach($conversations as $conversation) {
    echo $conversation->messages_count;
}


```



### N+1 Issue


To optimize query performance and avoid N+1 issues, use eager loading. Specify the desired relationships to be loaded with the with method.


```php

//get conversations with messages
$user = auth()->user();
$conversations = $user->conversations()
                      ->with('messages')->get();


```


### Events

The MessageCreated Event is only broadcasted to others i.e The other user in the conversation. which means the user who created the message will not receive the broadcasted event

| **Event**                               | **Description**                             |
| ----------------------------------------| ------------------------------------------- |
| `Namu\WireChat\Events\MessageCreated`   | Triggered when a message is created/sent |





## Configurations
The Wirechat configuration file is located at `config/wirechat.php`, where you can modify its properties.

To publish the config run the vendor:publish command:
```
 php artisan vendor:publish --tag=wirechat-config
```

#### Queue
Defines the queue for broadcasting message events.

```php
'queue' => 'default',
```

#### Color
The theme color used in the chat interface. 

- **Default:** `#3b82f6` (blue-500)

```php
'color' => '#f43f5e',
```

#### Home Route
The route to redirect users when the exit button is clicked in the chat.

```php
'home_route' => '/',
```

#### Search
Controls the user search functionality within the chat.

- **user_search_allowed**: Boolean to enable or disable the search bar.
- **user_searchable_fields**: Fields that can be searched (e.g., name, email).

```php
'user_search_allowed' => true,
'user_searchable_fields' => ['name', 'email'],
```

#### Attachments
Configuration for handling attachments in the chat.

- **storage_folder**: Folder where attachments will be saved.
- **storage_disk**: The disk where uploaded files are stored (e.g., `public`).
- **max_uploads**: Maximum number of files allowed per request.

```php
'attachments' => [
    'storage_folder' => 'attachments',
    'storage_disk' => 'public',
    'max_uploads' => 10,
    
    // Media Configuration
    'allow_media_attachments' => true,
    'media_mimes' => ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'],
    'media_max_upload_size' => 12288, // 12MB

    // File Configuration
    'allow_file_attachments' => true,
    'file_mimes' => ['zip', 'rar', 'txt', 'pdf'],
    'file_max_upload_size' => 12288, // 12MB
],
```

**Note:** For the `media_mimes`, `file_mimes`, and `max_upload_size` settings, ensure you also update the corresponding validations in the Livewire configuration file to match the settings in Wirechat.

Additionally, the `post_max_size` setting in `php.ini` overrides any configuration in both the Livewire and Wirechat config files. Be sure to adjust `post_max_size` accordingly to support larger file uploads.

