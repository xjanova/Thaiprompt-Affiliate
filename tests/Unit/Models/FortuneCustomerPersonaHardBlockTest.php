<?php

namespace Tests\Unit\Models;

use App\Models\FortuneCustomerPersona;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ทดสอบการยืดเงียบเป็น 7 วัน ต้องโดนเฉพาะคนปั่น free-chat ที่ไม่จ่ายเงิน
 */
class FortuneCustomerPersonaHardBlockTest extends TestCase
{
    private const USER_ID = '900000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Cache::flush();

        Schema::create('fortune_customer_personas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fortune_page_id')->nullable();
            $table->string('platform');
            $table->string('platform_user_id');
            $table->timestamp('chat_silenced_until')->nullable();
            $table->integer('time_waster_score')->default(0);
            $table->integer('silence_count')->default(0);
            $table->string('last_silence_reason')->nullable();
            $table->timestamp('admin_abuse_alerted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fortune_readings', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_user_id')->nullable();
            $table->string('platform_user_id')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    /** ผู้กระทำซ้ำที่ถึงเกณฑ์ hard block แล้ว และเคยแจ้งแอดมินไปแล้ว */
    private function repeatOffender(): FortuneCustomerPersona
    {
        $persona = new FortuneCustomerPersona;
        $persona->forceFill([
            'platform' => 'facebook',
            'platform_user_id' => self::USER_ID,
            'silence_count' => FortuneCustomerPersona::HARD_BLOCK_SILENCE_THRESHOLD,
            'admin_abuse_alerted_at' => now(),
        ])->save();

        return $persona;
    }

    private function paidReading(int $daysAgo): void
    {
        DB::table('fortune_readings')->insert([
            'facebook_user_id' => self::USER_ID,
            'is_paid' => true,
            'paid_at' => now()->subDays($daysAgo),
            'created_at' => now()->subDays($daysAgo),
        ]);
    }

    private function silenceHours(FortuneCustomerPersona $persona): int
    {
        return (int) round(now()->diffInHours($persona->fresh()->chat_silenced_until, true));
    }

    public function test_repeat_offender_who_never_paid_is_silenced_for_seven_days(): void
    {
        $persona = $this->repeatOffender();

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::HARD_BLOCK_DURATION_HOURS, $this->silenceHours($persona));
    }

    public function test_customer_who_just_paid_only_gets_the_normal_silence(): void
    {
        $persona = $this->repeatOffender();
        $this->paidReading(0);

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::SILENCE_DURATION_HOURS, $this->silenceHours($persona));
        $this->assertSame(FortuneCustomerPersona::HARD_BLOCK_SILENCE_THRESHOLD + 1, $persona->fresh()->silence_count);
    }

    public function test_payment_on_the_last_day_of_the_grace_period_still_counts(): void
    {
        $persona = $this->repeatOffender();
        $this->paidReading(FortuneCustomerPersona::HARD_BLOCK_PAID_GRACE_DAYS - 1);

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::SILENCE_DURATION_HOURS, $this->silenceHours($persona));
    }

    public function test_payment_older_than_the_grace_period_does_not_protect(): void
    {
        $persona = $this->repeatOffender();
        $this->paidReading(FortuneCustomerPersona::HARD_BLOCK_PAID_GRACE_DAYS + 1);

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::HARD_BLOCK_DURATION_HOURS, $this->silenceHours($persona));
    }

    public function test_unpaid_bill_does_not_protect(): void
    {
        $persona = $this->repeatOffender();
        DB::table('fortune_readings')->insert([
            'facebook_user_id' => self::USER_ID,
            'is_paid' => false,
            'created_at' => now(),
        ]);

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::HARD_BLOCK_DURATION_HOURS, $this->silenceHours($persona));
    }

    public function test_another_customers_payment_does_not_protect(): void
    {
        $persona = $this->repeatOffender();
        DB::table('fortune_readings')->insert([
            'facebook_user_id' => 'someone-else',
            'is_paid' => true,
            'paid_at' => now(),
            'created_at' => now(),
        ]);

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::HARD_BLOCK_DURATION_HOURS, $this->silenceHours($persona));
    }

    public function test_failed_payment_lookup_never_causes_a_seven_day_silence(): void
    {
        $persona = $this->repeatOffender();
        Schema::drop('fortune_readings');

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::SILENCE_DURATION_HOURS, $this->silenceHours($persona));
    }

    public function test_paying_customer_below_the_threshold_keeps_the_normal_silence(): void
    {
        $persona = $this->repeatOffender();
        $persona->forceFill(['silence_count' => 0])->save();
        $this->paidReading(0);

        $persona->triggerSilence('free-chat cap reached');

        $this->assertSame(FortuneCustomerPersona::SILENCE_DURATION_HOURS, $this->silenceHours($persona));
        $this->assertSame(1, $persona->fresh()->silence_count);
    }
}
