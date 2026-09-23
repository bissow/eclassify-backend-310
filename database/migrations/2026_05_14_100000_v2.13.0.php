<?php

use App\Models\HomeScreenSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('banner_ads')) {
            Schema::create('banner_ads', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->enum('platform', ['web', 'app']);
                $table->enum('page', ['home', 'detail', 'listing']);
                $table->enum('layout', ['single', 'dual', 'single_side', 'dual_side', 'large']);
                $table->uuid('group_id')->index()->nullable();
                $table->unsignedTinyInteger('position')->default(1);
                $table->string('image');
                $table->enum('ad_type', ['only_banner', 'category', 'advertisement', 'external_link']);
                $table->text('link')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('advertisement_id')->nullable();
                $table->unsignedBigInteger('home_screen_section_id')->nullable();
                $table->unsignedBigInteger('feature_section_id')->nullable();
                $table->enum('listing_page_section',['category_list', 'listing_data'])->nullable();
                $table->enum('detail_page_section',['image', 'ad_info', 'custom_fields', 'about_ad', 'location', 'similar_ads'])->nullable();
                $table->enum('placement', ['above', 'below'])->nullable();
                $table->boolean('status')->default(1);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['platform', 'page', 'status']);
                $table->foreign('home_screen_section_id')->references('id')->on('home_screen_sections')->nullOnDelete();
                $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                $table->foreign('advertisement_id')->references('id')->on('items')->nullOnDelete();
                $table->foreign('feature_section_id')->references('id')->on('feature_sections')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('categories', 'path')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->text('path')->nullable()->after('slug');
            });
            DB::statement('CREATE INDEX categories_path_index ON categories (path(255))');

            // Add Backfill Command
            Artisan::call('categories:backfill-paths');
        }

        HomeScreenSection::updateOrCreate(['section_type' => 'all_ads'], ['is_active' => 1, 'sequence' => 5]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'path')) {
            DB::statement('DROP INDEX categories_path_index ON categories');
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('path');
            });
        }

        Schema::dropIfExists('banner_ads');
    }
};
