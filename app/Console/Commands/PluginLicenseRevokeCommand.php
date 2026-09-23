<?php

namespace App\Console\Commands;

use App\Models\PluginLicense;
use Illuminate\Console\Command;

// Local kill-switch: flips revoked=true without re-signing or contacting
// the validator, effective immediately.
class PluginLicenseRevokeCommand extends Command
{
    protected $signature = 'plugin:license:revoke {slug} {--domain=}';

    protected $description = 'Immediately revoke a plugin license locally (kill switch)';

    public function handle(): int
    {
        $query = PluginLicense::where('plugin_slug', $this->argument('slug'));

        if ($domain = $this->option('domain')) {
            $query->where('domain', $domain);
        }

        $updated = $query->update(['revoked' => true]);

        $this->info("Revoked {$updated} license(s) for slug [{$this->argument('slug')}].");

        return 0;
    }
}
