<?php

namespace Namu\WireChat\Services;

use Illuminate\Support\Facades\Schema;

class WireChatService
{   
    /**
     * Get the color used to be used in as themse
    */
    public function getColor()
    {
        return config('wirechat.color',"#3b82f6");
    }



     /**
     * Retrieve the searchable fields defined in configuration
     * and check if they exist in the database table schema.
     *
     * @return array|null The array of searchable fields or null if none found.
     */
    public function searchableFields(): ?array
    {
         // Define the fields specified as searchable in the configuration
         $fieldsToCheck = config('wirechat.user_searchable_fields');

        //  // Get the table name associated with the model
        //  $tableName = $this->getTable();
 
        //  // Get the list of columns in the database table
        //  $tableColumns = Schema::getColumnListing($tableName);
 
        //  // Filter the fields to include only those that exist in the table schema
        //  $searchableFields = array_intersect($fieldsToCheck, $tableColumns);
 
         return $fieldsToCheck ?: null;
    }
}
