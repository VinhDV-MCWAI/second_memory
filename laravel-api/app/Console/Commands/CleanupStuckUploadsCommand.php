<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Media\CleanupStuckUploads;
use Illuminate\Console\Command;

class CleanupStuckUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup-stuck-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up media files stuck in PROCESSING status for more than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cleanup of stuck uploads...');

        try {
            CleanupStuckUploads::dispatch();
            $this->info('Cleanup job dispatched successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to dispatch cleanup job: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
