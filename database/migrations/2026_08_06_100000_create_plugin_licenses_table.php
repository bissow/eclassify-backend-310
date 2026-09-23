<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug')->index();
            $table->string('purchase_code_hash');
            $table->text('purchase_code_encrypted');
            $table->string('domain');
            $table->string('scope')->default('universal');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->text('signature');
            $table->timestamps();
            $table->unique(['plugin_slug', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_licenses');
    }
};
