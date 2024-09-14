<?php


return [
 
     /*
     * Tables names
     */
    'conversations_table' => 'wire_conversations',
    'messages_table' => 'wire_messages',
    'reads_table' => 'wirechat_reads',
    'participants_table' => 'wire_participants',
    'attachments_table' => 'wire_attachments',
    'actions_table' => 'wire_actions',


    'table_prefix' => 'wire_', //default wire_



    /* Model class name for Users */
    'user_model' => \App\Models\User::class,


    /**
     * Broadcasting:
     * define queue for broadcasting message events*/

    'queue'=>'default',

    /**
     * Color:
     * This is the theme color that will be used in the chat
     * Default : #3b82f6 //blue-500
     *  */
    'color'=>'#f43f5e',


    /**
     * Home route:
     * This is the route to redirect to when exit button is clicked in the chat
     * */
     'home_route'=>"/",


    /**
     * Search:
     * Show the bar to search for users 
     **/
    
     'user_search_allowed'=>true, //bool
     'user_searchable_fields'=>['name','email'],   //['email','profession']etc

    /**
     * Attachments
     **/
     'attachments' => [
        'storage_folder' => 'attachments', //folder name for attachments to be saved
        'storage_disk' => 'public',//The disk on which to store uploaded files
        'max_uploads' => 10,  // Maximum number of files to be uploaded for each request

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