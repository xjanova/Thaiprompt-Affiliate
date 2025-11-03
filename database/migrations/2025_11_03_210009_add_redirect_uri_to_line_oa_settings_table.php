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
        Schema::table('line_oa_settings', function (Blueprint $table) {
            $table->string('redirect_uri')->nullable()->after('channel_secret')
                ->comment('Custom redirect URI for LINE Login callback. If not set, will use route("line.callback")');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            $table->dropColumn('redirect_uri');
        });
    }
};
