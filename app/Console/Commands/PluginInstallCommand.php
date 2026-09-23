<?php

namespace App\Console\Commands;

use App\Services\License\LicenseInstaller;
use Illuminate\Console\Command;

class PluginInstallCommand extends Command
{
    protected $signature = 'plugin:install {slug} {--code=} {--domain=}';

    protected $description = 'Activate a plugin license via purchase code (step 1 of plugin install)';

    public function handle(LicenseInstaller $installer): int
    {
        $slug = $this->argument('slug');
        $code = $this->option('code');

        if (!$code) {
            $this->error('--code is required');

            return 1;
        }

        $license = $installer->install($slug, $code, $this->option('domain'));

        $this->info("Plugin [{$slug}] license activated for domain [{$license->domain}], version {$license->version}.");

        return 0;
    }
}
