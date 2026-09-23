<?php

use App\Models\BlockUser;
use App\Models\Category;
use App\Models\Chat;
use App\Models\Item;
use App\Models\ItemOffer;
use App\Models\SeoDetail;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFcmToken;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =============================================
        // Referral System Migration
        // =============================================

        // Add referral_code and refer_points columns to users table
        if (!Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_code', 10)->unique()->nullable()->after('region_code');
                $table->unsignedInteger('refer_points')->default(0)->after('referral_code');
            });
        }
        // Add used_referral_code columns to users table
        if (!Schema::hasColumn('users', 'used_referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('used_referral_code')->comment('false value only applicable to first time user, and not updated profile')->default(false)->after('refer_points');
            });
            User::where(['used_referral_code' => false])->update(['used_referral_code' => true]);
        }

        // Backfill existing users with unique referral codes
        $users = DB::table('users')->whereNull('referral_code')->orderBy('id')->get(['id']);
        foreach ($users as $user) {
            do {
                $code = strtoupper(Str::random(8));
            } while (DB::table('users')->where('referral_code', $code)->exists());

            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        }

        // Create referrals table
        if (!Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
                $table->boolean('is_rewarded')->default(false);
                $table->timestamp('rewarded_at')->nullable();
                $table->timestamps();

                // A user can only be referred once
                $table->unique('referred_id');
                $table->index('referrer_id');
            });
        }

        // Create refer_point_transactions table
        if (!Schema::hasTable('refer_point_transactions')) {
            Schema::create('refer_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedInteger('points');
                $table->enum('transaction_type', ['credit', 'debit']);
                $table->enum('type', ['earned_by_referral', 'earned_as_referred', 'used_for_purchase']);
                $table->string('remark')->nullable();

                // Snapshot fields - freeze values at time of transaction
                // Using same types as packages table: price=integer, final_price=float
                $table->integer('package_original_price')->nullable();
                $table->float('package_discounted_price')->nullable();
                $table->unsignedInteger('points_used')->nullable();
                $table->unsignedInteger('points_remaining_after');
                $table->double('final_payment_amount', 8, 2)->nullable();

                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_type')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('type');
            });
        }

        // Add per-package refer settings to packages table
        if (!Schema::hasColumn('packages', 'refer_max_points_usage_percentage')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->unsignedInteger('refer_max_points_usage_percentage')->nullable()->after('listing_duration_days');
                $table->unsignedInteger('refer_min_points_to_use')->nullable()->after('refer_max_points_usage_percentage');
                $table->unsignedInteger('refer_max_points_to_use')->nullable()->after('refer_min_points_to_use');
            });
        }

        // Add refer_points_used to payment_transactions table
        if (!Schema::hasColumn('payment_transactions', 'refer_points_used')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->unsignedInteger('refer_points_used')->default(0)->after('discount_price');
            });
        }
        /** End Referral System Migration */

        // Align item_offers.amount with items.price (double, no precision cap)
        if (Schema::hasColumn('item_offers', 'amount')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->double('amount')->nullable()->change();
            });
        }

        // =============================================
        // Unified Morph-Based Translations Table
        // =============================================
        if (!Schema::hasTable('translations')) {
            Schema::create('translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
                $table->string('key', 100);
                $table->text('value');
                $table->morphs('translatable');
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['language_id', 'key', 'translatable_id', 'translatable_type'], 'unique_translation');
            });
        }

        // Migrate data from old translation tables into unified translations table
        $this->migrateOldTranslations();

        // Drop old translation tables
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('item_translations');
        Schema::dropIfExists('blog_translations');
        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('state_translations');
        Schema::dropIfExists('city_translations');
        Schema::dropIfExists('area_translations');
        Schema::dropIfExists('package_translations');
        Schema::dropIfExists('custom_fields_translations');
        Schema::dropIfExists('setting_translations');
        Schema::dropIfExists('feature_section_translations');
        Schema::dropIfExists('faq_translations');
        Schema::dropIfExists('tip_translations');
        Schema::dropIfExists('report_reason_translations');
        Schema::dropIfExists('seo_settings_translations');
        Schema::dropIfExists('verification_fields_translations');

        // Drop translations column from countries table (now using morph translations table)
        if (Schema::hasColumn('countries', 'translations')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('translations');
            });
        }
        /** End Unified Morph-Based Translations Table */

        // =============================================
        // Update SEO related columns
        // =============================================

        // Seo Settings
        if (!Schema::hasColumn('seo_settings', 'schema')) {
            Schema::table('seo_settings', function (Blueprint $table) {
                $table->text('schema')->nullable();
            });
        }

        // Uniformed SEO Details Table
        if (!Schema::hasTable('seo_details')) {
            Schema::create('seo_details', function (Blueprint $table) {
                $table->id();
                $table->morphs('seoable');
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('meta_keywords')->nullable();
                $table->text('schema')->nullable();
                $table->timestamps();

                $table->unique(['seoable_id', 'seoable_type'], 'unique_seoable');
            });
            /** End Uniformed SEO Details Table */

            $this->migrateCategoryDescriptionToSeoMetaDetails();

        }

        // =============================================
        // Gemini AI Usage Tracking Table
        // =============================================
        if (!Schema::hasTable('gemini_usage')) {
            Schema::create('gemini_usage', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_type', 20)->nullable();
                $table->string('type', 30); // description, meta
                $table->string('entity_type', 30)->default('item');
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('prompt_hash', 64)->nullable();
                $table->unsignedInteger('tokens_used')->default(0);
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type', 'created_at']);
                $table->index(['type', 'created_at']);
            });
        }

        // Gemini AI default settings
        $geminiSettings = [
            ['name' => 'gemini_ai_enabled', 'value' => '0', 'type' => 'string'],
            ['name' => 'gemini_model', 'value' => 'gemini-2.5-flash-lite', 'type' => 'string'],
            ['name' => 'gemini_description_limit', 'value' => '10', 'type' => 'string'],
            ['name' => 'gemini_meta_limit', 'value' => '10', 'type' => 'string'],
            ['name' => 'gemini_description_limit_global', 'value' => '100', 'type' => 'string'],
            ['name' => 'gemini_meta_limit_global', 'value' => '100', 'type' => 'string'],
        ];
        foreach ($geminiSettings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['name' => $setting['name']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
        /** End Gemini AI */

        // =============================================
        // Migrate old admin user data to Super Admin
        // =============================================
        $this->migrateAdminUserDataToSuperAdmin();

        // Remove admin user settings (no longer needed)
        DB::table('settings')->whereIn('name', ['admin_user_email', 'admin_user_password'])->delete();

        // =============================================
        // Home Screen Sections
        // =============================================
        if (!Schema::hasTable('home_screen_sections')) {
            Schema::create('home_screen_sections', function (Blueprint $table) {
                $table->id();
                $table->string('section_type')->unique();
                $table->boolean('is_active')->default(true);
                $table->integer('sequence');
                $table->timestamps();
            });

            DB::table('home_screen_sections')->insert([
                ['section_type' => 'all_categories', 'is_active' => true, 'sequence' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['section_type' => 'slider', 'is_active' => true, 'sequence' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['section_type' => 'popular_categories', 'is_active' => true, 'sequence' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['section_type' => 'featured_section', 'is_active' => true, 'sequence' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (!Schema::hasTable('popular_categories')) {
            Schema::create('popular_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->unique()->constrained('categories')->cascadeOnDelete();
                $table->integer('sequence');
                $table->timestamps();
            });

            // Migrate existing featured categories
            $featuredCategories = DB::table('categories')
                ->where('is_featured', 1)
                ->orderBy('sequence')
                ->get(['id', 'sequence']);

            $seq = 1;
            foreach ($featuredCategories as $cat) {
                DB::table('popular_categories')->insert([
                    'category_id' => $cat->id,
                    'sequence' => $seq++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!Schema::hasColumn('item_images', 'is_default')) {
            Schema::table('item_images', function (Blueprint $table) {
                $table->boolean('is_default')->default(0)->after('image');
            });

            // Migrate existing item image data to item_images as default
            DB::table('items')->orderBy('id')->chunk(500, function ($items) {
                $images = [];
                foreach ($items as $item) {
                    if (!empty($item->image)) {
                        $images[] = [
                            'item_id' => $item->id,
                            'image' => $item->image,
                            'is_default' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (!empty($images)) {
                    foreach (array_chunk($images, 200) as $chunk) {
                        DB::table('item_images')->insert($chunk);
                    }
                }
            });
            
            // Drop the old image column from items as it is now merged
            if (Schema::hasColumn('items', 'image')) {
                Schema::table('items', function (Blueprint $table) {
                    $table->dropColumn('image');
                });
            }
        }
    }

    /**
     * Migrate data from all old translation tables into the new unified translations table.
     */
    private function migrateOldTranslations(): void
    {
        $mappings = [
            ['table' => 'category_translations', 'parent_key' => 'category_id', 'type' => 'App\\Models\\Category', 'keys' => ['name', 'description']],
            ['table' => 'item_translations', 'parent_key' => 'item_id', 'type' => 'App\\Models\\Item', 'keys' => ['name', 'description', 'address', 'rejected_reason', 'admin_edit_reason', 'slug']],
            ['table' => 'blog_translations', 'parent_key' => 'blog_id', 'type' => 'App\\Models\\Blog', 'keys' => ['title', 'description', 'tags']],
            ['table' => 'country_translations', 'parent_key' => 'country_id', 'type' => 'App\\Models\\Country', 'keys' => ['name']],
            ['table' => 'state_translations', 'parent_key' => 'state_id', 'type' => 'App\\Models\\State', 'keys' => ['name']],
            ['table' => 'city_translations', 'parent_key' => 'city_id', 'type' => 'App\\Models\\City', 'keys' => ['name']],
            ['table' => 'area_translations', 'parent_key' => 'area_id', 'type' => 'App\\Models\\Area', 'keys' => ['name']],
            ['table' => 'package_translations', 'parent_key' => 'package_id', 'type' => 'App\\Models\\Package', 'keys' => ['name', 'description']],
            ['table' => 'custom_fields_translations', 'parent_key' => 'custom_field_id', 'type' => 'App\\Models\\CustomField', 'keys' => ['name', 'value']],
            ['table' => 'setting_translations', 'parent_key' => 'setting_id', 'type' => 'App\\Models\\Setting', 'keys' => ['translated_value']],
            ['table' => 'feature_section_translations', 'parent_key' => 'feature_section_id', 'type' => 'App\\Models\\FeatureSection', 'keys' => ['name', 'description']],
            ['table' => 'faq_translations', 'parent_key' => 'faq_id', 'type' => 'App\\Models\\Faq', 'keys' => ['question', 'answer']],
            ['table' => 'tip_translations', 'parent_key' => 'tip_id', 'type' => 'App\\Models\\Tip', 'keys' => ['description']],
            ['table' => 'report_reason_translations', 'parent_key' => 'report_reason_id', 'type' => 'App\\Models\\ReportReason', 'keys' => ['reason']],
            ['table' => 'seo_settings_translations', 'parent_key' => 'seo_setting_id', 'type' => 'App\\Models\\SeoSetting', 'keys' => ['title', 'description', 'keywords']],
            ['table' => 'verification_fields_translations', 'parent_key' => 'verification_field_id', 'type' => 'App\\Models\\VerificationField', 'keys' => ['name', 'value']],
        ];

        foreach ($mappings as $mapping) {
            if (!Schema::hasTable($mapping['table'])) {
                continue;
            }

            DB::table($mapping['table'])->orderBy('id')->chunk(500, function ($rows) use ($mapping) {
                $inserts = [];
                $now = now();

                foreach ($rows as $row) {
                    foreach ($mapping['keys'] as $key) {
                        $value = $row->{$key} ?? null;
                        if (!empty($value)) {
                            $inserts[] = [
                                'language_id'       => $row->language_id,
                                'key'               => $key,
                                'value'             => $value,
                                'translatable_id'   => $row->{$mapping['parent_key']},
                                'translatable_type' => $mapping['type'],
                                'created_at'        => $row->created_at ?? $now,
                                'updated_at'        => $row->updated_at ?? $now,
                            ];
                        }
                    }
                }

                if (!empty($inserts)) {
                    foreach (array_chunk($inserts, 200) as $batch) {
                        DB::table('translations')->insert($batch);
                    }
                }
            });
        }
    }

    /**
     * Migrate data from old admin user (from settings) to the Super Admin role user.
     * If both are the same user, no changes are needed.
     */
    private function migrateAdminUserDataToSuperAdmin(): void
    {
        $adminEmail = Setting::where('name', 'admin_user_email')->value('value');

        if (empty($adminEmail)) {
            return;
        }

        $oldAdminUser = User::where('email', $adminEmail)->whereNull('deleted_at')->first();

        if (!$oldAdminUser) {
            return;
        }

        $superAdminUserId = User::role('Super Admin')->first()->id;

        if (!$superAdminUserId || $superAdminUserId == $oldAdminUser->id) {
            // Same user or no Super Admin found — nothing to migrate
            return;
        }

        // Migrate items created by old admin user
        Item::where('user_id', $oldAdminUser->id)->update(['user_id' => $superAdminUserId]);

        // Migrate item_offers where old admin is the seller
        ItemOffer::where('seller_id', $oldAdminUser->id)->update(['seller_id' => $superAdminUserId]);

        // Migrate item_offers where old admin is the buyer
        ItemOffer::where('buyer_id', $oldAdminUser->id)->update(['buyer_id' => $superAdminUserId]);

        // Migrate chat messages sent by old admin user
        Chat::where('sender_id', $oldAdminUser->id)->update(['sender_id' => $superAdminUserId]);

        // Delete FCM tokens
        UserFcmToken::where('user_id', $oldAdminUser->id)->delete();

        // Migrate block_users records
        if (Schema::hasTable('block_users')) {
            BlockUser::where('user_id', $oldAdminUser->id)->update(['user_id' => $superAdminUserId]);
            BlockUser::where('blocked_user_id', $oldAdminUser->id)->update(['blocked_user_id' => $superAdminUserId]);
        }
    }

    private function migrateCategoryDescriptionToSeoMetaDetails(): void
    {
        $categories = Category::all();
        $data = [];
        if(collect($categories)->isNotEmpty()){
            foreach ($categories as $category) {
                if(!empty($category->description)){
                    $data[] = [
                        'seoable_id' => $category->id,
                        'seoable_type' => Category::class,
                        'meta_description' => $category->description,
                    ];
                }
            }
            SeoDetail::insert($data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('refer_point_transactions');
        Schema::dropIfExists('referrals');

        if (Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['referral_code', 'refer_points']);
            });
        }

        if (Schema::hasColumn('users', 'used_referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['used_referral_code']);
            });
        }

        if (Schema::hasColumn('packages', 'refer_max_points_usage_percentage')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn(['refer_max_points_usage_percentage', 'refer_min_points_to_use', 'refer_max_points_to_use']);
            });
        }

        if (Schema::hasColumn('payment_transactions', 'refer_points_used')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->dropColumn('refer_points_used');
            });
        }

        // Remove schema column from seo_settings table
        if(Schema::hasColumn('seo_settings', 'schema')){
            Schema::table('seo_settings', function (Blueprint $table) {
                $table->dropColumn('schema');
            });
        }
        
        // Drop home screen tables
        Schema::dropIfExists('popular_categories');
        Schema::dropIfExists('home_screen_sections');

        // Drop seo_details table
        Schema::dropIfExists('seo_details');

        // Drop Gemini AI tables and settings
        Schema::dropIfExists('gemini_usage');
        DB::table('settings')->whereIn('name', [
            'gemini_ai_enabled',
            'gemini_model',
            'gemini_description_limit',
            'gemini_meta_limit',
            'gemini_description_limit_global',
            'gemini_meta_limit_global',
        ])->delete();

        if (Schema::hasColumn('item_images', 'is_default')) {
            Schema::table('item_images', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
        
        if (!Schema::hasColumn('items', 'image')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('image')->nullable()->after('user_id');
            });
        }
    }
};
