<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('edited_images')) {
            Schema::create('edited_images', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('item_id')->nullable()->index();
                $table->string('original_path', 2048);
                $table->string('edited_path', 2048);
                $table->json('transformations')->nullable()->comment('Recorded operations: crop, rotate, filters, text overlay, etc.');
                $table->string('disk', 64)->default('public');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('mime_type', 128)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edited_images');
    }
};
