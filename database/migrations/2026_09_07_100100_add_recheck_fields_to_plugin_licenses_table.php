<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            // Which pinned key/algorithm signed this row's payload_b64 —
            // metadata about the crypto operation, not a security decision,
            // so it's fine as a plain column (like signature/payload_b64).
            $table->string('key_id')->nullable()->after('payload_b64');
            $table->string('alg')->default('ed25519')->after('key_id');

            // From the verified payload, kept as columns only so the daily
            // recheck sweep can query "who's stale" without decoding every
            // row's JSON. Never used as the source of truth for authorization
            // — that's always the verified payload itself.
            $table->timestamp('issued_at')->nullable()->after('verified_at');
            $table->timestamp('expires_hint_at')->nullable()->after('issued_at');

            // 14-day offline grace tracking: set on the first failed
            // recheck attempt, cleared on the next success.
            $table->timestamp('recheck_failing_since')->nullable()->after('expires_hint_at');
            $table->boolean('grace_expired')->default(false)->after('recheck_failing_since');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_licenses', function (Blueprint $table) {
            $table->dropColumn(['key_id', 'alg', 'issued_at', 'expires_hint_at', 'recheck_failing_since', 'grace_expired']);
        });
    }
};
