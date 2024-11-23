<?php

namespace Namu\WireChat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Namu\WireChat\Jobs\DeleteExpiredMessagesJob;

class DeleteExpiredMessage extends Command
{
    protected $signature = 'wirechat:delete-expired';
    protected $description = 'Deletes expired disappearing messages from conversations';

    public function handle()
    {
        // Run the job that deletes expired messages
        DeleteExpiredMessagesJob::dispatch();

        $this->info('Expired messages have been deleted successfully!');
    }


}
