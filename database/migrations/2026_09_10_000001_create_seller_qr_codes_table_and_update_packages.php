<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add allows_seller_qr_code column to packages table
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'allows_seller_qr_code')) {
                $table->boolean('allows_seller_qr_code')->default(false)->after('allows_spotlight');
            }
        });

        // 2. Create seller_qr_codes table
        if (!Schema::hasTable('seller_qr_codes')) {
            Schema::create('seller_qr_codes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->string('qr_code_token', 64)->unique()->index();
                $table->string('title')->nullable();
                $table->text('tagline')->nullable();
                $table->string('qr_style', 50)->default('standee')->index();
                $table->string('primary_color', 10)->default('#00B2CA');
                $table->string('secondary_color', 10)->default('#0F172A');
                $table->string('center_logo_type', 50)->default('store_logo');
                $table->string('center_logo')->nullable();
                $table->unsignedBigInteger('scans_count')->default(0);
                $table->timestamp('last_scanned_at')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            });
        }

        // 3. Register Spatie permissions
        $permissions = [
            'seller-qr-list',
            'seller-qr-manage',
            'seller-qr-setting',
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
        Schema::dropIfExists('seller_qr_codes');

        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'allows_seller_qr_code')) {
                $table->dropColumn('allows_seller_qr_code');
            }
        });

        $permissions = [
            'seller-qr-list',
            'seller-qr-manage',
            'seller-qr-setting',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::where('name', $permission)->where('guard_name', 'web')->first();
            $perm?->delete();
        }
    }
};
