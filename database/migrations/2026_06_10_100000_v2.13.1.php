<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add dummy columns for new logic
        foreach (['categories', 'custom_fields', 'items'] as $table) {
            if (!Schema::hasColumn($table, 'is_dummy')) {
                Schema::table($table, static function (Blueprint $blueprint) {
                    $blueprint->boolean('is_dummy')->default(0)->index();
                });
            }
        }
    }

    public function down(): void
    {
        // Remove dummy columns for new logic
        foreach (['categories', 'custom_fields', 'items'] as $table) {
            if (Schema::hasColumn($table, 'is_dummy')) {
                Schema::table($table, static function (Blueprint $blueprint) {
                    $blueprint->dropColumn('is_dummy');
                });
            }
        }
    }
};
