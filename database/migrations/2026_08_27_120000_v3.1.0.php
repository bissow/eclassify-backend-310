<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('chat_templates')) {
            Schema::create('chat_templates', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->string('name');
                $blueprint->boolean('is_global')->default(false);
                $blueprint->boolean('status')->default(true);
                $blueprint->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $blueprint->timestamps();
            });
        }

        if (!Schema::hasTable('chat_template_questions')) {
            Schema::create('chat_template_questions', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('chat_template_id')->constrained('chat_templates')->cascadeOnDelete();
                $blueprint->enum('role', ['customer', 'seller']);
                $blueprint->text('question');
                $blueprint->integer('sequence')->default(0);
                $blueprint->timestamps();
            });
        }

        if (!Schema::hasTable('chat_template_categories')) {
            Schema::create('chat_template_categories', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('chat_template_id')->constrained('chat_templates')->cascadeOnDelete();
                $blueprint->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('chats', 'is_offer')) {
            Schema::table('chats', function (Blueprint $blueprint) {
                $blueprint->boolean('is_offer')->default(false)->after('audio');
                $blueprint->double('amount')->nullable()->after('is_offer');
            });
        }

        foreach (['countries', 'states', 'cities', 'areas'] as $table) {
            if (!Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('status')->default(true);
                });
            }
        }

        $currentMaintenanceMode = DB::table('settings')->where('name', 'maintenance_mode')->value('value') ?? '0';

        foreach (['android_maintenance_mode', 'ios_maintenance_mode'] as $name) {
            if (!DB::table('settings')->where('name', $name)->exists()) {
                DB::table('settings')->insert([
                    'name' => $name,
                    'value' => $currentMaintenanceMode,
                    'type' => 'string',
                ]);
            }
        }

        DB::table('settings')->whereIn('name', ['maintenance_mode', 'web_maintenance_mode'])->delete();

        if (Schema::hasTable('featured_items')) {
            Schema::table('featured_items', function (Blueprint $blueprint) {
                $blueprint->unique(['item_id', 'package_id', 'user_purchased_package_id'], 'featured_items_item_package_purchase_unique');
            });
            Schema::table('featured_items', function (Blueprint $blueprint) {
                $blueprint->dropUnique('featured_items_item_id_package_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('featured_items')) {
            Schema::table('featured_items', function (Blueprint $blueprint) {
                $blueprint->unique(['item_id', 'package_id'], 'featured_items_item_id_package_id_unique');
            });
            Schema::table('featured_items', function (Blueprint $blueprint) {
                $blueprint->dropUnique('featured_items_item_package_purchase_unique');
            });
        }

        DB::table('settings')->whereIn('name', ['android_maintenance_mode', 'ios_maintenance_mode'])->delete();

        foreach (['maintenance_mode' => '0', 'web_maintenance_mode' => '0'] as $name => $value) {
            if (!DB::table('settings')->where('name', $name)->exists()) {
                DB::table('settings')->insert([
                    'name' => $name,
                    'value' => $value,
                    'type' => 'string',
                ]);
            }
        }

        if (Schema::hasColumn('chats', 'is_offer')) {
            Schema::table('chats', function (Blueprint $blueprint) {
                $blueprint->dropColumn(['is_offer', 'amount']);
            });
        }

        foreach (['countries', 'states', 'cities', 'areas'] as $table) {
            if (Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('status');
                });
            }
        }

        Schema::dropIfExists('chat_template_category');
        Schema::dropIfExists('chat_template_questions');
        Schema::dropIfExists('chat_templates');
    }
};
