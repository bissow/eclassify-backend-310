<?php

namespace App\Console\Commands;

use App\Models\PluginLicense;
use Illuminate\Console\Command;
use ZipArchive;

// Step 2 of plugin install (only runs after step 1's license activation succeeds):
// extracts an uploaded plugin package zip into app/Modules/{slug}/.
class PluginUploadCommand extends Command
{
    protected $signature = 'plugin:upload {slug} {--zip=} {--domain=}';

    protected $description = 'Extract an uploaded plugin package zip into app/Modules/{slug} (requires an active license)';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $zipPath = $this->option('zip');
        $domain = $this->option('domain') ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if (!$zipPath || !file_exists($zipPath)) {
            $this->error('--zip must point to an existing plugin package file.');

            return 1;
        }

        $license = PluginLicense::where('plugin_slug', $slug)
            ->where('domain', $domain)
            ->first();

        if (!$license) {
            $this->error('No active license for this plugin/domain. Run plugin:install first.');

            return 1;
        }

        $destination = app_path('Modules/'.$slug);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Unable to open plugin package zip.');

            return 1;
        }

        $zip->extractTo($destination);
        $zip->close();

        $this->info("Plugin [{$slug}] package extracted to {$destination}.");

        return 0;
    }
}
