<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('facebook_oauth_settings', 'graph_version')) {
            return;
        }

        Schema::table('facebook_oauth_settings', function (Blueprint $table) {
            $table->string('graph_version', 10)->default('v27.0')
                ->comment('Facebook Graph API version')->change();
        });

        DB::table('facebook_oauth_settings')->update(['graph_version' => 'v27.0']);
        Cache::forget('facebook_oauth_settings');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('facebook_oauth_settings', 'graph_version')) {
            return;
        }

        Schema::table('facebook_oauth_settings', function (Blueprint $table) {
            $table->string('graph_version', 10)->default('v18.0')
                ->comment('Facebook Graph API version')->change();
        });

        // Keep upgraded rows: their previous individual versions cannot be recovered.
        Cache::forget('facebook_oauth_settings');
    }
};
