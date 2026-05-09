<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupCachedTestPdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf-cache:cleanup-test-downloads {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old cached test PDF files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::info('PDF cleanup started');
        try {
            $days = (int) $this->option('days');

            $directory = storage_path('app/pdf-cache/test-downloads');

            if (! File::exists($directory)) {
                $this->info('PDF cache directory does not exist.');
                return self::SUCCESS;
            }

            $deleted = 0;
            $threshold = now()->subDays($days)->timestamp;

            foreach (File::files($directory) as $file) {
                if ($file->getMTime() < $threshold) {
                    File::delete($file->getPathname());
                    $deleted++;
                }
            }

            $this->info('PDF cleanup finished', [
                'deleted_files' => $deleted,
            ]);

            return self::SUCCESS;
        }catch (\Throwable $exception)
        {
            Log::channel('errors')->error('PDF cleanup failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }

    }
}
