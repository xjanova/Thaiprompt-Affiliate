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
        Schema::create('system_analytics', function (Blueprint $table) {
            $table->id();

            // Timestamp for this metric snapshot
            $table->timestamp('recorded_at')->index();

            // System Performance Metrics
            $table->decimal('cpu_usage', 5, 2)->nullable()->comment('CPU usage percentage');
            $table->decimal('memory_usage', 5, 2)->nullable()->comment('Memory usage percentage');
            $table->decimal('disk_usage', 5, 2)->nullable()->comment('Disk usage percentage');
            $table->bigInteger('disk_free')->nullable()->comment('Free disk space in bytes');
            $table->bigInteger('disk_total')->nullable()->comment('Total disk space in bytes');

            // Application Metrics
            $table->integer('active_users')->default(0)->comment('Current active users');
            $table->integer('active_sessions')->default(0)->comment('Active sessions count');
            $table->decimal('request_rate', 10, 2)->default(0)->comment('Requests per second');
            $table->decimal('avg_response_time', 10, 2)->nullable()->comment('Average response time in ms');
            $table->decimal('p50_response_time', 10, 2)->nullable()->comment('50th percentile response time');
            $table->decimal('p95_response_time', 10, 2)->nullable()->comment('95th percentile response time');
            $table->decimal('p99_response_time', 10, 2)->nullable()->comment('99th percentile response time');
            $table->integer('error_count')->default(0)->comment('Error count in this period');
            $table->decimal('error_rate', 5, 2)->default(0)->comment('Error rate percentage');

            // Database Metrics
            $table->integer('db_connections_active')->nullable()->comment('Active DB connections');
            $table->integer('db_connections_total')->nullable()->comment('Total DB connections available');
            $table->decimal('db_avg_query_time', 10, 2)->nullable()->comment('Average query time in ms');
            $table->integer('db_slow_queries')->default(0)->comment('Slow queries count (>1s)');
            $table->integer('db_deadlocks')->default(0)->comment('Deadlocks count');

            // Cache Metrics
            $table->integer('cache_hits')->default(0)->comment('Cache hits in this period');
            $table->integer('cache_misses')->default(0)->comment('Cache misses in this period');
            $table->decimal('cache_hit_rate', 5, 2)->nullable()->comment('Cache hit rate percentage');
            $table->bigInteger('cache_memory_used')->nullable()->comment('Cache memory used in bytes');
            $table->bigInteger('cache_memory_total')->nullable()->comment('Total cache memory in bytes');
            $table->integer('cache_keys_count')->nullable()->comment('Total number of cache keys');

            // Queue Metrics
            $table->integer('queue_pending')->default(0)->comment('Pending queue jobs');
            $table->integer('queue_processing')->default(0)->comment('Processing queue jobs');
            $table->integer('queue_failed')->default(0)->comment('Failed queue jobs');
            $table->decimal('queue_avg_wait_time', 10, 2)->nullable()->comment('Average queue wait time in seconds');

            // Business Metrics (realtime snapshot)
            $table->integer('users_online')->default(0)->comment('Users currently online');
            $table->integer('new_users_today')->default(0)->comment('New user registrations today');
            $table->decimal('revenue_today', 15, 2)->default(0)->comment('Revenue generated today');
            $table->integer('transactions_today')->default(0)->comment('Transactions processed today');
            $table->integer('active_affiliates')->default(0)->comment('Currently active affiliates');

            // HTTP Metrics
            $table->integer('http_2xx')->default(0)->comment('Successful responses (2xx)');
            $table->integer('http_3xx')->default(0)->comment('Redirect responses (3xx)');
            $table->integer('http_4xx')->default(0)->comment('Client error responses (4xx)');
            $table->integer('http_5xx')->default(0)->comment('Server error responses (5xx)');

            // Security Metrics
            $table->integer('failed_logins')->default(0)->comment('Failed login attempts in period');
            $table->integer('blocked_ips')->default(0)->comment('Currently blocked IP addresses');
            $table->integer('auto_bans')->default(0)->comment('Auto-bans triggered in period');
            $table->integer('captcha_failures')->default(0)->comment('CAPTCHA failures in period');

            $table->timestamps();

            // Indexes for performance
            $table->index('created_at');
            $table->index(['recorded_at', 'active_users']);
            $table->index(['recorded_at', 'request_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_analytics');
    }
};
