<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        
        // Blog Categories
        if (!Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->string('name');
                $blueprint->string('slug')->unique();
                $blueprint->boolean('is_active')->default(1)->index();
                $blueprint->timestamps();
                $blueprint->softDeletes();
            });
        }

        if(!Schema::hasColumn('blogs', 'category_id')){
            Schema::table('blogs', function (Blueprint $blueprint) {
                $blueprint->foreignId('category_id')->after('views')->nullable()->constrained('blog_categories')->nullOnDelete();
            });

            $this->migrateBlogsToOtherCategory();
        }

        // Blogs Useful or not per user
        if (!Schema::hasTable('blog_feedbacks')) {
            Schema::create('blog_feedbacks', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('blog_id')->constrained('blogs')->onDelete('cascade');
                $blueprint->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $blueprint->boolean('is_useful'); // 1 = useful, 0 = not useful
                $blueprint->timestamps();
                $blueprint->unique(['blog_id', 'user_id']);
            });
        }

        // Reels
        if (!Schema::hasTable('reels')) {
            Schema::create('reels', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('item_id')->unique()->constrained('items')->onDelete('cascade');
                $blueprint->string('video');
                $blueprint->string('thumbnail')->nullable();
                $blueprint->timestamps();
            });
        }

        // Reel Likes
        if (!Schema::hasTable('reel_likes')) {
            Schema::create('reel_likes', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('reel_id')->constrained('reels')->onDelete('cascade');
                $blueprint->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $blueprint->timestamps();
                $blueprint->unique(['reel_id', 'user_id']);
            });
        }

        // is_reel_allowed on packages
        if (!Schema::hasColumn('packages', 'is_reel_allowed')) {
            Schema::table('packages', function (Blueprint $blueprint) {
                $blueprint->boolean('is_reel_allowed')->default(0)->after('is_global');
            });
        }

        // Reel settings
        $reelSettings = [
            ['name' => 'reel_max_file_size_mb',  'value' => '50',  'type' => 'string'],
            ['name' => 'reel_max_duration_sec',   'value' => '60',  'type' => 'string'],
        ];
        foreach ($reelSettings as $setting) {
            DB::table('settings')->updateOrInsert(['name' => $setting['name']], $setting);
        }

        // Item Type
        if(!Schema::hasColumn('items','item_type')){
            Schema::table('items', function(Blueprint $blueprint){
                $blueprint->enum('item_type', ['normal','reel'])->default('normal')->after('status')->index();
            });
        }

        // Item Videos
        if (!Schema::hasTable('item_videos')) {
            Schema::create('item_videos', static function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('item_id')->unique()->constrained('items')->onDelete('cascade');
                $blueprint->enum('video_type', ['youtube_link', 'vimeo_link', 'other_link', 'file']);
                $blueprint->string('video_link')->nullable();
                $blueprint->string('video_file')->nullable();
                $blueprint->timestamps();
            });

            // Migrate existing video_link data → item_videos as other_link
            DB::table('items')
                ->whereNotNull('video_link')
                ->where('video_link', '!=', '')
                ->orderBy('id')
                ->each(function ($item) {
                    DB::table('item_videos')->insertOrIgnore([
                        'item_id'    => $item->id,
                        'video_type' => 'other_link',
                        'video_link' => $item->video_link,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        // Item video setting
        DB::table('settings')->updateOrInsert(
            ['name' => 'item_video_max_file_size_mb'],
            ['value' => '50', 'type' => 'string']
        );


        // Discontinue Packages
        if (!Schema::hasColumn('packages', 'is_discontinued')) {
            Schema::table('packages', function (Blueprint $blueprint) {
                $blueprint->boolean('is_discontinued')->default(0)->after('status');
                $blueprint->timestamp('discontinued_at')->nullable()->after('is_discontinued');
            });
        }

        // Published & Renewed dates on items
        if (!Schema::hasColumn('items', 'published_at') && !Schema::hasColumn('items', 'renewed_at')) {
            Schema::table('items', function (Blueprint $blueprint) {
                $blueprint->timestamp('published_at')->nullable()->after('expiry_date');
                $blueprint->timestamp('renewed_at')->nullable()->after('published_at');
            });

            // Backfill published_at from created_at for existing items
            DB::table('items')->whereNull('published_at')->update([
                'published_at' => DB::raw('created_at'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_videos');
        Schema::dropIfExists('reel_likes');
        Schema::dropIfExists('reels');

        if (Schema::hasColumn('packages', 'is_reel_allowed')) {
            Schema::table('packages', function (Blueprint $blueprint) {
                $blueprint->dropColumn('is_reel_allowed');
            });
        }

        DB::table('settings')->whereIn('name', ['reel_max_file_size_mb', 'reel_max_duration_sec'])->delete();

        Schema::dropIfExists('blog_feedbacks');

        if (Schema::hasColumn('blogs', 'category_id')) {
            Schema::table('blogs', function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('category_id');
            });
        }

        Schema::dropIfExists('blog_categories');

        if (Schema::hasColumn('packages', 'is_discontinued')) {
            Schema::table('packages', function (Blueprint $blueprint) {
                $blueprint->dropColumn(['is_discontinued', 'discontinued_at']);
            });
        }

        if (Schema::hasColumn('items', 'published_at')) {
            Schema::table('items', function (Blueprint $blueprint) {
                $blueprint->dropColumn(['published_at', 'renewed_at']);
            });
        }
    }

    private function migrateBlogsToOtherCategory(){
        // Create or Get Other Category
        $otherCategoryId = DB::table('blog_categories')->where('slug', 'other')->value('id');
        if (!$otherCategoryId) {
            $otherCategoryId = DB::table('blog_categories')->insertGetId([
                'name'       => 'Other',
                'slug'       => 'other',
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign existing blogs to Other category
        DB::table('blogs')->whereNull('category_id')->update(['category_id' => $otherCategoryId]);
    }
};
