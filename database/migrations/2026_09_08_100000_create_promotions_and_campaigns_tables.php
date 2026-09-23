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
        // 1. Create campaigns table
        if (!Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('banner_image')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['active', 'inactive', 'scheduled', 'expired'])->default('active')->index();
                $table->integer('priority')->default(0)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Create promotions table
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('campaign_id')->nullable()->index();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('banner_image')->nullable();
                $table->enum('promotion_type', ['flash_sale', 'clearance_sale', 'deal_of_the_day', 'custom'])->default('flash_sale')->index();
                $table->date('start_date');
                $table->date('end_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->enum('frequency', ['daily', 'weekly', 'custom'])->default('custom');
                $table->decimal('discount', 8, 2)->nullable();
                $table->enum('discount_type', ['percentage', 'flat'])->default('percentage');
                $table->enum('status', ['active', 'inactive', 'scheduled', 'expired'])->default('active')->index();
                $table->integer('priority')->default(0)->index();
                $table->boolean('is_countdown_enabled')->default(true);
                $table->unsignedInteger('max_items_per_user')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            });
        }

        // 3. Create promotion_items table
        if (!Schema::hasTable('promotion_items')) {
            Schema::create('promotion_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('promotion_id')->index();
                $table->unsignedBigInteger('item_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('user_purchased_package_id')->nullable()->index();
                $table->decimal('promotional_price', 12, 2);
                $table->decimal('discount_value', 8, 2)->default(0);
                $table->enum('discount_type', ['percentage', 'flat'])->default('percentage');
                $table->unsignedInteger('stock_quantity')->default(1);
                $table->unsignedInteger('remaining_stock_quantity')->default(1);
                $table->dateTime('valid_until')->nullable()->index();
                $table->enum('status', ['active', 'inactive', 'sold_out', 'expired', 'pending', 'rejected'])->default('active')->index();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('promotion_id')->references('id')->on('promotions')->onDelete('cascade');
                $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('user_purchased_package_id')->references('id')->on('user_purchased_packages')->onDelete('set null');
            });
        }

        // 4. Create item_ad_promotions table (Promote this Ad: Daily Bump Up, Top Ad, Spotlight)
        if (!Schema::hasTable('item_ad_promotions')) {
            Schema::create('item_ad_promotions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('item_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('user_purchased_package_id')->nullable()->index();
                $table->enum('promotion_type', ['daily_bump_up', 'top_ad', 'spotlight'])->index();
                $table->dateTime('start_date');
                $table->dateTime('end_date')->nullable();
                $table->enum('bump_frequency', ['once', 'daily'])->nullable();
                $table->dateTime('last_bumped_at')->nullable();
                $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('user_purchased_package_id')->references('id')->on('user_purchased_packages')->onDelete('set null');
            });
        }

        // 5. Add promotional entitlement columns to packages table
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'allows_promotions')) {
                $table->boolean('allows_promotions')->default(false)->after('is_reel_allowed');
            }
            if (!Schema::hasColumn('packages', 'promotion_item_limit')) {
                $table->unsignedInteger('promotion_item_limit')->nullable()->after('allows_promotions');
            }
            if (!Schema::hasColumn('packages', 'allowed_promotion_types')) {
                $table->json('allowed_promotion_types')->nullable()->after('promotion_item_limit');
            }
            if (!Schema::hasColumn('packages', 'allows_daily_bump_up')) {
                $table->boolean('allows_daily_bump_up')->default(false)->after('allowed_promotion_types');
            }
            if (!Schema::hasColumn('packages', 'daily_bump_up_limit')) {
                $table->unsignedInteger('daily_bump_up_limit')->nullable()->after('allows_daily_bump_up');
            }
            if (!Schema::hasColumn('packages', 'allows_top_ad')) {
                $table->boolean('allows_top_ad')->default(false)->after('daily_bump_up_limit');
            }
            if (!Schema::hasColumn('packages', 'top_ad_limit')) {
                $table->unsignedInteger('top_ad_limit')->nullable()->after('allows_top_ad');
            }
            if (!Schema::hasColumn('packages', 'allows_spotlight')) {
                $table->boolean('allows_spotlight')->default(false)->after('top_ad_limit');
            }
            if (!Schema::hasColumn('packages', 'spotlight_limit')) {
                $table->unsignedInteger('spotlight_limit')->nullable()->after('allows_spotlight');
            }
        });

        // 6. Add promotional tracking columns to user_purchased_packages table
        Schema::table('user_purchased_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('user_purchased_packages', 'used_promotions_limit')) {
                $table->unsignedInteger('used_promotions_limit')->default(0)->after('used_limit');
            }
            if (!Schema::hasColumn('user_purchased_packages', 'used_daily_bump_up_limit')) {
                $table->unsignedInteger('used_daily_bump_up_limit')->default(0)->after('used_promotions_limit');
            }
            if (!Schema::hasColumn('user_purchased_packages', 'used_top_ad_limit')) {
                $table->unsignedInteger('used_top_ad_limit')->default(0)->after('used_daily_bump_up_limit');
            }
            if (!Schema::hasColumn('user_purchased_packages', 'used_spotlight_limit')) {
                $table->unsignedInteger('used_spotlight_limit')->default(0)->after('used_top_ad_limit');
            }
        });

        // 7. Register Spatie permissions
        $permissions = [
            'campaign-list',
            'campaign-create',
            'campaign-update',
            'campaign-delete',
            'promotion-list',
            'promotion-create',
            'promotion-update',
            'promotion-delete',
            'promotion-item-list',
            'promotion-item-update',
            'promotion-item-delete',
            'ad-promotion-list',
            'ad-promotion-update',
            'ad-promotion-delete',
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
        Schema::dropIfExists('item_ad_promotions');
        Schema::dropIfExists('promotion_items');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('campaigns');

        Schema::table('packages', function (Blueprint $table) {
            $cols = [
                'allows_promotions',
                'promotion_item_limit',
                'allowed_promotion_types',
                'allows_daily_bump_up',
                'daily_bump_up_limit',
                'allows_top_ad',
                'top_ad_limit',
                'allows_spotlight',
                'spotlight_limit',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('packages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('user_purchased_packages', function (Blueprint $table) {
            $cols = [
                'used_promotions_limit',
                'used_daily_bump_up_limit',
                'used_top_ad_limit',
                'used_spotlight_limit',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('user_purchased_packages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
