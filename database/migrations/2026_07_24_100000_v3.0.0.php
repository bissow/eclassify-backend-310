<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $blueprint) {
            $blueprint->text('message')->nullable()->change();
        });


        DB::table('packages')->where('duration', 'unlimited')->update(['duration' => null]);
        Schema::table('packages', function (Blueprint $blueprint) {
            $blueprint->integer('duration')->nullable()->change();
        });
        DB::table('packages')->where('duration', 0)->update(['duration' => null]);

        if (!DB::table('settings')->where('name', 'feature_image_resizing')->exists()) {
            DB::table('settings')->insert([
                'name' => 'feature_image_resizing',
                'value' => '1',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!DB::table('settings')->where('name', 'web_maintenance_mode')->exists()) {
            $currentMaintenanceMode = DB::table('settings')->where('name', 'maintenance_mode')->value('value') ?? '0';
            DB::table('settings')->insert([
                'name' => 'web_maintenance_mode',
                'value' => (string)$currentMaintenanceMode,
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Approved items need to be updated for publish date if null
        DB::table('items')
            ->where('status', 'approved')
            ->whereNull('published_at')
            ->update(['published_at' => DB::raw('created_at')]);

        if (! Schema::hasColumn('languages', 'status')) {
            Schema::table('languages', function (Blueprint $blueprint) {
                $blueprint->boolean('status')->default(true)->after('country_code');
            });

            // Existing languages stay active after upgrade
            DB::table('languages')->update(['status' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $blueprint) {
            $blueprint->string('message')->nullable()->change();
        });

        Schema::table('packages', function (Blueprint $blueprint) {
            $blueprint->string('duration')->nullable()->change();
        });

        if (Schema::hasColumn('languages', 'status')) {
            Schema::table('languages', function (Blueprint $blueprint) {
                $blueprint->dropColumn('status');
            });
        }
    }
};
