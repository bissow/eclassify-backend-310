<?php

namespace App\Console\Commands;

use App\Services\License\MarketplaceCatalogService;
use Illuminate\Console\Command;
use Throwable;

class PluginCatalogRefreshCommand extends Command
{
    protected $signature = 'plugin:catalog:refresh';

    protected $description = 'Refresh the marketplace plugin catalog from the validator (ETag-conditional)';

    public function handle(MarketplaceCatalogService $service): int
    {
        try {
            $result = $service->refresh();

            $this->info($result['changed']
                ? "Catalog refreshed: {$result['count']} plugin(s)."
                : "Catalog unchanged (304) — {$result['count']} plugin(s) cached.");

            return 0;
        } catch (Throwable $e) {
            $this->error('Catalog refresh failed: '.$e->getMessage());

            return 1;
        }
    }
}
