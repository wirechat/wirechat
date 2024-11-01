<?php


return [
 
     /*
     * Tables prefix
     */
    'table_prefix' => 'wire_',



    /* Model class name for Users */
    'user_model' => \App\Models\User::class,


    /**
     * Broadcasting:
     * define queue config*/
    'broadcasting' => [
        'messages_queue' => 'default',  // Prioritize for real-time messaging in chat rooms
        'message_notification_queue' => 'medium',  // Notifications for new messages in other areas like the navbar
    ],
    /**
     * Color:
     * This is the theme color that will be used in the chat
     * Default : #3b82f6 //blue-500
     *  */
    'color'=>'#3b82f6',


    /**
     * Home route:
     * This is the route to redirect to when exit button is clicked in the chat*/
     'redirect_route'=>"/users",
    

    'routes' => [
        'prefix' => '/chats',
        'middleware' => ['web', 'auth'],
    ],

     /**
     * Features:
     * You can configure the feature you want to allow for wirechat */
     'allow_new_chat_modal'=>true,     //bool -Show the modal to create create a new conversation inside
     'allow_new_group_modal'=>true,    //allow group creation
     'allow_chats_search'=>true,       //bool -Show the search bar to existing conversations
     'allow_media_attachments'=>true,  //bool -Allow participants to share media in conversatoin (images, vidoes, gifs, etc)
     'allow_file_attachments'=>true,   //bool -Allow participants to share files in conversatoin (documents, zip , pdf, etc)

     'user_searchable_fields'=>['name','email','username'],   //['email','profession']etc
     'max_group_members'=>3000,        //Maximum members allowed per group

    /**
     * Attachments:
     **/
     'attachments' => [
        'storage_folder' => 'attachments', //folder name for attachments to be saved
        'storage_disk' => 'public',//The disk on which to store uploaded files
        'max_uploads' => 10,  // Maximum number of files to be uploaded for each request

        //Media config
        'media_mimes' => (array) ['png','jpg','jpeg','gif','mov','mp4'],
        'media_max_upload_size' => 12288, // 12MB

        //Files config
        'file_mimes' => (array) ['zip','rar','txt','pdf'],
        'file_max_upload_size' => 12288, //12 MB
    ],

];