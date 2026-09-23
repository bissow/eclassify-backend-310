<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            // 'payment', 'sms', 'plugin' (default/unknown). Read from the
            // plugin's module.json at install time — see PluginManagerController.
            $table->string('type')->default('plugin')->after('plugin_slug');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->dropColumn(['type', 'settings_fields']);
        });
    }
};
