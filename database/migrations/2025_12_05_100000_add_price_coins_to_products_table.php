<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ price_coins สำหรับซื้อสินค้าด้วย Coins
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'price_coins')) {
                $table->decimal('price_coins', 12, 2)->nullable()->after('price')
                    ->comment('ราคาเป็น Coins (ถ้า null = ไม่รองรับการซื้อด้วย Coins)');
            }

            if (!Schema::hasColumn('products', 'allow_coin_purchase')) {
                $table->boolean('allow_coin_purchase')->default(false)->after('price_coins')
                    ->comment('อนุญาตให้ซื้อด้วย Coins หรือไม่');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มไว้
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'price_coins')) {
                $table->dropColumn('price_coins');
            }

            if (Schema::hasColumn('products', 'allow_coin_purchase')) {
                $table->dropColumn('allow_coin_purchase');
            }
        });
    }
};
