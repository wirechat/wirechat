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


     'theme'=>'red', // can be from any colors : gree, red, 

     'user_search_allowed'=>false, //bool
     'user_searchable_fields'=>['name'],   //['email','profession']etc

     'attachments' => [
        'storage_folder' => 'attachments',
        'storage_disk' => 'public',
        'max_uploads' => 10,  

        //Media config
        'allow_media_attachments'=>true,
        'media_mimes' => (array) ['png','jpg','jpeg','gif','mov','mp4'],
        'media_max_upload_size' => 12288, // 12MB

        //Files config
        'allow_file_attachments'=>true,
        'file_mimes' => (array) ['zip','rar','txt','pdf'],
        'file_max_upload_size' => 12288, //12 MB
    ],

];