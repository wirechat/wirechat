

This is a highly customizable one - one Laravel messaging application made with Livewire v3, it is used to provide a convinient way to allows users to communicate with eachother in the application 


## Installing

Intall package via composer 

```shell
composer require namu/wirechat
```

### Configuration & Migrations

1. publish the Migration file 
```
php artisan vendor:publish --tag=wirechat-migration
```
2. Optionally, publish the configuration file if you want to change any defaults:
```
 php artisan vendor:publish --tag=wirechat-config
```



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



$auth->sendMessageTo($user,'message');

$auth->createConversationWith($user,'message');

$auth->deleteConversationWith($user);

$auth->hasConversationWith($user); // bool


```


### Retrieving Data

#### `\Namu\LaravelFavoriteable\Traits\Favoriter`

```php
$user->getFavorites(Listings::class);

//if you're using other guards too you can retrieve as follows
$user = User::find(1);

//To get user conversations
$user->conversations()->get();

$admin->getFavorites(Conversation::class);

//you can also perform additional queries 
$favoritedPosts= $user->getFavorites(Post::class)->get();
$favoritedPosts= $user->getFavorites(Post::class)->paginate(10);
$favoritedPosts= $user->getFavorites(Post::class)->where('title','your post title')->get();

```
 

### Aggregations
Here you can retrieve items from the pivot table to we can get the count()

```php
// retrieve all Favorites
$user->unReadMessagesCount(); //

// Filter by type
$admin->favoriteObjects()->whereType(Post::class)->count();


```

Using withCount() attribute:

```php
//For Favoriter
$users = User::withCount('favorites')->get();

foreach($users as $user) {
    echo $user->favorites_count;
}


// For Favoriteable
$posts = Post::withCount('favoriters')->get();

foreach($posts as $post) {
    echo $post->favoriters_count;
}
```


### N+1 Issue


To optimize query performance and avoid N+1 issues, use eager loading. Specify the desired relationships to be loaded with the with method.


```php

// Favoriter
$users = User::with('favorites')->get();

foreach($users as $user) {
    $user->hasFavorited($post);
}


// Favoriteable
$posts = Post::with('favorites')->get();

foreach($posts as $post) {
    $post->isFavoritedBy($user);
}

```