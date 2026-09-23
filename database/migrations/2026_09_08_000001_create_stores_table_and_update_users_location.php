<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add location & store fields to users table if they don't exist
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('country');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('state');
            }
            if (!Schema::hasColumn('users', 'area_id')) {
                $table->unsignedBigInteger('area_id')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'has_store')) {
                $table->boolean('has_store')->default(false)->after('area_id');
            }
        });

        // 2. Create stores table
        if (!Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->string('banner')->nullable();
                $table->string('email')->nullable();
                $table->string('contact')->nullable();
                $table->string('country_code', 10)->nullable();
                $table->text('address')->nullable();
                $table->decimal('latitude', 10, 8)->nullable()->index();
                $table->decimal('longitude', 11, 8)->nullable()->index();
                $table->string('country')->nullable()->index();
                $table->string('state')->nullable()->index();
                $table->string('city')->nullable()->index();
                $table->unsignedBigInteger('area_id')->nullable()->index();
                $table->string('website')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('opening_time', 20)->nullable();
                $table->string('closing_time', 20)->nullable();
                $table->json('working_days')->nullable();
                $table->json('social_links')->nullable();
                $table->enum('status', ['active', 'inactive', 'pending'])->default('active')->index();
                $table->boolean('is_verified')->default(false)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 3. Register Spatie permissions for Store Management
        $permissions = [
            'store-list',
            'store-create',
            'store-update',
            'store-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');

        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['latitude', 'longitude', 'country', 'state', 'city', 'area_id', 'has_store'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        $permissions = [
            'store-list',
            'store-create',
            'store-update',
            'store-delete',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
