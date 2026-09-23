<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearImageCache extends Command
{
    protected $signature = 'image-cache:clear {--days= : Only delete cache files older than N days}';
    protected $description = 'Clear cached resized image variants';

    const CACHE_DIR = 'image-cache';

    public function handle()
    {
        $disk = Storage::disk('public');
        $days = $this->option('days');

        if (!$disk->exists(self::CACHE_DIR)) {
            $this->info('No image cache found.');
            return;
        }

        $files = $disk->files(self::CACHE_DIR);
        $deleted = 0;

        foreach ($files as $file) {
            if ($days !== null) {
                $age = now()->timestamp - $disk->lastModified($file);
                if ($age < ((int) $days * 86400)) {
                    continue;
                }
            }

            $disk->delete($file);
            $deleted++;
        }

        $this->info("Deleted {$deleted} cached image variant(s).");
    }
}
