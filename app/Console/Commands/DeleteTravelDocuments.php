<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteTravelDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-travel-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command deletes travel documents from the s3 bucket.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $disk = Storage::disk('s3');
        $files = $disk->files('pdfs');

        if (!empty($files)) {
            foreach ($files as $file) {
                $disk->delete($file);
            }
        }
    }
}
