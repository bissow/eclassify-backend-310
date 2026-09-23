<?php

namespace App\Console\Commands;

use App\Services\License\LicenseRechecker;
use Illuminate\Console\Command;

class PluginLicenseRecheckCommand extends Command
{
    protected $signature = 'plugin:license:recheck';

    protected $description = 'Re-verify plugin licenses whose cached token is past its expires_hint';

    public function handle(LicenseRechecker $rechecker): int
    {
        $due = $rechecker->due();

        $ok = 0;
        $failed = 0;

        foreach ($due as $license) {
            if ($rechecker->recheck($license)) {
                $ok++;
            } else {
                $failed++;
            }
        }

        $this->info("Rechecked {$due->count()} license(s): {$ok} refreshed, {$failed} failed.");

        return 0;
    }
}
