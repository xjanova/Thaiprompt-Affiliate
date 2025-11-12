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
        Schema::table('tickets', function (Blueprint $table) {
            // SLA tracking fields
            $table->timestamp('first_response_at')->nullable()->after('last_reply_at');
            $table->timestamp('due_at')->nullable()->after('first_response_at');
            $table->timestamp('sla_breached_at')->nullable()->after('due_at');
            $table->boolean('is_overdue')->default(false)->after('sla_breached_at');

            // Additional tracking
            $table->integer('response_time_minutes')->nullable()->after('is_overdue')->comment('Time to first response in minutes');
            $table->integer('resolution_time_minutes')->nullable()->after('response_time_minutes')->comment('Time to resolution in minutes');

            // Ticket linking/merging
            $table->unsignedBigInteger('merged_into_ticket_id')->nullable()->after('resolution_time_minutes');
            $table->foreign('merged_into_ticket_id')->references('id')->on('tickets')->onDelete('set null');

            // Tags for better organization
            $table->json('tags')->nullable()->after('merged_into_ticket_id');

            // Add index for performance
            $table->index('is_overdue');
            $table->index('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['merged_into_ticket_id']);
            $table->dropColumn([
                'first_response_at',
                'due_at',
                'sla_breached_at',
                'is_overdue',
                'response_time_minutes',
                'resolution_time_minutes',
                'merged_into_ticket_id',
                'tags'
            ]);
        });
    }
};
