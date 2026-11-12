<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('oauth_auth_codes')) {
            return;
        }
        foreach (['social_provider' => 16, 'social_nonce_hash' => 64, 'social_code_hash' => 64] as $column => $length) {
            if (! Schema::hasColumn('oauth_auth_codes', $column)) {
                Schema::table('oauth_auth_codes', fn (Blueprint $table) => $table->string($column, $length)->nullable());
            }
        }
    }

    public function down(): void
    {
        foreach (['social_provider', 'social_nonce_hash', 'social_code_hash'] as $column) {
            if (Schema::hasTable('oauth_auth_codes') && Schema::hasColumn('oauth_auth_codes', $column)) {
                Schema::table('oauth_auth_codes', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
