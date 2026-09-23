<?php

use App\Models\PluginCatalogItem;
use Illuminate\Database\Migrations\Migration;

// eClassify plugins ship as their own product line (Cashfree + PayU India
// payment gateways) instead of a pulled-from-validator marketplace catalog.
// Seed the two fixed entries and lock the "Available Plugin" tab to them.
return new class extends Migration {
    public function up(): void
    {
        PluginCatalogItem::updateOrCreate(
            ['slug' => 'cashfree-payment-gateway'],
            [
                'scope' => 'universal',
                'name' => 'Cashfree',
                'description' => 'Cashfree Payment Links payment gateway',
                'marketplace_url' => null,
                'latest_version' => '1.0.0',
            ]
        );

        PluginCatalogItem::updateOrCreate(
            ['slug' => 'payu-india-payment-gateway'],
            [
                'scope' => 'universal',
                'name' => 'PayU India',
                'description' => 'PayU Payment Links — direct hosted checkout URL, no signed-form bridge page',
                'marketplace_url' => null,
                'latest_version' => '1.0.0',
            ]
        );

        PluginCatalogItem::whereNotIn('slug', ['cashfree-payment-gateway', 'payu-india-payment-gateway'])->delete();
    }

    public function down(): void
    {
        PluginCatalogItem::whereIn('slug', ['cashfree-payment-gateway', 'payu-india-payment-gateway'])->delete();
    }
};
