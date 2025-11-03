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
        Schema::table('products', function (Blueprint $table) {
            // Add store_id after seller_id
            $table->foreignId('store_id')->nullable()->after('seller_id')
                ->constrained('vendor_stores')->onDelete('cascade');

            // Add public approval fields
            $table->boolean('is_public_approved')->default(false)->after('is_featured');
            $table->timestamp('public_approved_at')->nullable()->after('is_public_approved');
            $table->foreignId('public_approved_by')->nullable()->after('public_approved_at')
                ->constrained('users')->onDelete('set null');

            // Add indexes
            $table->index(['store_id', 'is_active'], 'idx_store_active');
            $table->index(['is_public_approved', 'is_active'], 'idx_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropForeign(['public_approved_by']);
            $table->dropIndex('idx_store_active');
            $table->dropIndex('idx_public');
            $table->dropColumn([
                'store_id',
                'is_public_approved',
                'public_approved_at',
                'public_approved_by'
            ]);
        });
    }
};
