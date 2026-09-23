<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugin_catalog_items', function (Blueprint $table) {
            $table->string('scope')->default('universal')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_catalog_items', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
