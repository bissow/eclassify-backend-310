<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            // Verbatim base64 of the exact JSON bytes the validator signed.
            // Signature checks must verify against this, never against a
            // payload rebuilt from plugin_slug/scope/domain columns — those
            // columns are only for querying and can be edited independently.
            $table->text('payload_b64')->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->dropColumn('payload_b64');
        });
    }
};
