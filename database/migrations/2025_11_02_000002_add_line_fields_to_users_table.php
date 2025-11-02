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
        Schema::table('users', function (Blueprint $table) {
            $table->string('line_user_id')->nullable()->unique()->after('email');
            $table->string('line_display_name')->nullable()->after('line_user_id');
            $table->string('line_picture_url')->nullable()->after('line_display_name');
            $table->text('line_access_token')->nullable()->after('line_picture_url');
            $table->timestamp('line_linked_at')->nullable()->after('line_access_token');
            $table->boolean('line_verified')->default(false)->after('line_linked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'line_user_id',
                'line_display_name',
                'line_picture_url',
                'line_access_token',
                'line_linked_at',
                'line_verified',
            ]);
        });
    }
};
