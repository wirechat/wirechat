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


    /**
     * FEATURES
     **/

     'allow_attachments'=>true,

     //This string is a comma-separated list of unique file type specifiers  https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file#unique_file_type_specifiers
     'allowed_attachments'=>['image/png','image/jpeg', 'image/jpg'],

     'allow_user_search'=>true
];