<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admin-controlled kill switch, separate from the license itself — a plugin
// can be perfectly licensed and still be turned off on purpose. Folded into
// PluginLicense::isUsable() so every existing check (PaymentService gateway
// resolution, the dynamic settings cards, the payment_method validation
// list) respects it automatically, with no extra call sites to update.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(1)->after('revoked');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
