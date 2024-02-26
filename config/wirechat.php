<?php


return [
 
    /*
     * Table name for conversations.
     */
    'conversations_table' => 'wire_conversations',


     /*
     * Table name for messages.
     */
    'messages_table' => 'wire_messages',


    /* Table name for attachments */
    'attachments_table' => 'wire_attachments',

    /* Model class name for Users */
    'user_model' => \App\Models\User::class,


    /* Home route */
     'home_route'=>"/",


    /**
     * FEATURES
     **/

    

     //This string is a comma-separated list of unique file type specifiers  https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file#unique_file_type_specifiers
     'allowed_attachments'=>['image/png','image/jpeg', 'image/jpg'],

     'allow_user_search'=>true,

     'theme'=>'red', // can be from any colors : gree, red, 


     'attachments' => [
        'allow_attachments'=>true,
        'storage_folder' => 'attachments',
        'storage_disk' => 'public',
        'image_mimes' => (array) ['png','jpg','jpeg','gif'],
        'file_mimes' => (array) ['zip','rar','txt'],
        'max_upload_size' => 150, // MB
    ],

];