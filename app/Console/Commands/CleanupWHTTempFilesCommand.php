<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupWHTTempFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wht:cleanup-temp {--days=2 : Delete files older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old WHT ZIP files from storage/temp/ (default: older than 2 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tempPath = storage_path('temp');
        $days = (int) $this->option('days');
        $deleted = 0;

        if (!is_dir($tempPath)) {
            $this->info('No temp directory found.');
            return 0;
        }

        $files = File::glob($tempPath . '/*.zip');
        $now = now();

        foreach ($files as $file) {
            $lastModified = \Carbon\Carbon::createFromTimestamp(File::lastModified($file));
            if ($lastModified->lt($now->copy()->subDays($days))) {
                File::delete($file);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} old WHT ZIP file(s) from storage/temp/.");
        return 0;
    }
}
