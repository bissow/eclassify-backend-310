<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Licenses are valid-or-revoked, not time-limited — expires_at/grace-days
// checking has been removed everywhere it was read (PluginLicense::isUsable(),
// CheckPluginLicense middleware, PluginManagerController, GeneralApiController,
// LicenseInstaller/LicenseVerifier), so the column itself is dead weight.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->dropColumn('expires_at');
            $table->string('version')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
            $table->dropColumn('version');
        });
    }
};
