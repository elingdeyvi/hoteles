<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateStorageDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:create-directories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create necessary storage directories for the application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $directories = [
            'public/logos',
            'public/favicons',
            'public/documents',
            'public/temp'
        ];

        $this->info('Creating storage directories...');

        foreach ($directories as $directory) {
            $path = storage_path('app/' . $directory);

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("Created: {$directory}");
            } else {
                $this->line("Already exists: {$directory}");
            }
        }

        $this->info('Storage directories created successfully!');

        return Command::SUCCESS;
    }
}
