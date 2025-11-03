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
            // Thai ID Card Information
            $table->string('id_card_number', 13)->nullable()->after('kyc_verified_at');
            $table->string('thai_first_name')->nullable()->after('id_card_number');
            $table->string('thai_last_name')->nullable()->after('thai_first_name');
            $table->string('english_first_name')->nullable()->after('thai_last_name');
            $table->string('english_last_name')->nullable()->after('english_first_name');
            $table->date('id_card_birth_date')->nullable()->after('english_last_name');
            $table->string('id_card_religion')->nullable()->after('id_card_birth_date');
            $table->text('id_card_address')->nullable()->after('id_card_religion');
            $table->date('id_card_issue_date')->nullable()->after('id_card_address');
            $table->date('id_card_expiry_date')->nullable()->after('id_card_issue_date');

            // Add index for ID card number
            $table->index('id_card_number');
        });

        Schema::table('kyc_verifications', function (Blueprint $table) {
            // Store extracted data from OCR
            $table->json('extracted_data')->nullable()->after('selfie_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropColumn('extracted_data');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['id_card_number']);
            $table->dropColumn([
                'id_card_number',
                'thai_first_name',
                'thai_last_name',
                'english_first_name',
                'english_last_name',
                'id_card_birth_date',
                'id_card_religion',
                'id_card_address',
                'id_card_issue_date',
                'id_card_expiry_date',
            ]);
        });
    }
};
