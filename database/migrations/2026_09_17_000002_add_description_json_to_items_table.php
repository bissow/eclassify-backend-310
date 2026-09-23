<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('items') && !Schema::hasColumn('items', 'description_json')) {
            Schema::table('items', function (Blueprint $table) {
                $table->longText('description_json')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'description_json')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('description_json');
            });
        }
    }
};
