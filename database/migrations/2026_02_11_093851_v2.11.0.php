<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Country Code Column to Items Table
        if(!Schema::hasColumn('items', 'country_code')){
            Schema::table('items', function (Blueprint $table) {
                $table->string('country_code')->nullable();
            });
        }

        // Add Is Featured Column to Categories Table
        if(!Schema::hasColumn('categories', 'is_featured')){
            Schema::table('categories', function (Blueprint $table) {
               $table->boolean('is_featured')->default(0);
            });
        }

        // Add User Follows Table
        if(!Schema::hasTable('user_follows')){
            Schema::create('user_follows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                
                // Prevent duplicate follows
                $table->unique(['follower_id', 'following_id']);
                
                // Index for better query performance
                $table->index('follower_id');
                $table->index('following_id');
            });
        }

        // Add Session ID Column to Number Otps Table
        if(!Schema::hasColumn('number_otps', 'session_id')){
            Schema::table('number_otps', function (Blueprint $table) {
                $table->string('session_id', 100)->nullable()->after('otp');
            });
        }
        if(Schema::hasColumn('number_otps', 'otp')){
            Schema::table('number_otps', function (Blueprint $table) {
                $table->string('otp')->nullable()->change();
            });

        if (!Schema::hasColumn('chats', 'deleted_at')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('item_offers', 'deleted_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        }

        // Make Password Column Nullable in Users Table
        if(Schema::hasColumn('users', 'password')){
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
            });
        }

        if (!Schema::hasColumn('chats', 'deleted_by_sender_at')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->timestamp('deleted_by_sender_at')->nullable()->after('is_read');
            });
        }

        // Add deleted_by_seller_at and deleted_by_buyer_at to item_offers table
        if (!Schema::hasColumn('item_offers', 'deleted_by_seller_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->timestamp('deleted_by_seller_at')->nullable()->after('amount');
            });
        }

        if (!Schema::hasColumn('item_offers', 'deleted_by_buyer_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->timestamp('deleted_by_buyer_at')->nullable()->after('deleted_by_seller_at');
            });
        }

        // Add cleared_by_seller_at and cleared_by_buyer_at to item_offers table
        // These track when a user last cleared the chat history (never reset on re-initiation)
        if (!Schema::hasColumn('item_offers', 'cleared_by_seller_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->timestamp('cleared_by_seller_at')->nullable()->after('deleted_by_buyer_at');
            });
        }

        if (!Schema::hasColumn('item_offers', 'cleared_by_buyer_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->timestamp('cleared_by_buyer_at')->nullable()->after('cleared_by_seller_at');
            });
        }

        // Add original_price and discount_price to payment_transactions
        if (!Schema::hasColumn('payment_transactions', 'original_price')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->double('original_price', 8, 2)->nullable()->after('amount');
                $table->double('discount_price', 8, 2)->nullable()->after('original_price');
            });

            // Backfill existing records from packages data
            DB::table('payment_transactions')
                ->join('packages', 'payment_transactions.package_id', '=', 'packages.id')
                ->update([
                    'payment_transactions.original_price' => DB::raw('packages.price'),
                    'payment_transactions.discount_price' => DB::raw('packages.price - packages.final_price'),
                ]);

            // Mark transactions with amount 0 as Free
            DB::table('payment_transactions')
                ->where('amount', 0)
                ->update(['payment_gateway' => 'Free']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Country Code Column from Items Table
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });

        // Drop Is Featured Column from Categories Table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });

        // Drop User Follows Table
        Schema::dropIfExists('user_follows');

        // Drop Session ID Column from Number Otps Table
        Schema::table('number_otps', function (Blueprint $table) {
            $table->dropColumn('session_id');
            $table->string('otp')->nullable(false)->change();
        });

         if (Schema::hasColumn('chats', 'deleted_at')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove deleted_at column from item_offers table
        if (Schema::hasColumn('item_offers', 'deleted_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Make Password Column Not Nullable in Users Table
        // No need to make passsword back to not nullable

         // Remove deleted_by_sender_at from chats table
         if (Schema::hasColumn('chats', 'deleted_by_sender_at')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->dropColumn('deleted_by_sender_at');
            });
        }

        // Remove deleted_by_seller_at and deleted_by_buyer_at from item_offers table
        if (Schema::hasColumn('item_offers', 'deleted_by_seller_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->dropColumn('deleted_by_seller_at');
            });
        }

        if (Schema::hasColumn('item_offers', 'deleted_by_buyer_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->dropColumn('deleted_by_buyer_at');
            });
        }

        if (Schema::hasColumn('item_offers', 'cleared_by_seller_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->dropColumn('cleared_by_seller_at');
            });
        }

        if (Schema::hasColumn('item_offers', 'cleared_by_buyer_at')) {
            Schema::table('item_offers', function (Blueprint $table) {
                $table->dropColumn('cleared_by_buyer_at');
            });
        }

        if (Schema::hasColumn('payment_transactions', 'original_price')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->dropColumn(['original_price', 'discount_price']);
            });
        }
    }
};
