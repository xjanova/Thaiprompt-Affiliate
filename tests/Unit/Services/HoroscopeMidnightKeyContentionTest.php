<?php

namespace Tests\Unit\Services;

use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Models\HoroscopeDailyPrediction;
use App\Services\FortuneAIService;
use App\Services\FortuneHoroscopeService;
use App\Services\HoroscopeDailyService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🌙 เที่ยงคืนเลนโพสกับเลนแชทแย่ง key เดียวกันแล้วดวงรายวันต้องไม่หาย (2026-10-31)
 *
 * เหตุจริง: เลนโพสเริ่ม 00:00:05 เลนแชทเริ่ม 00:01:03 ทั้งคู่ใช้ openai key เดียว โมเดลตอบ 9-17 วิ
 * แต่รอคิว key ได้แค่ 12 วิ → ข้าม key ทั้งที่ไม่มีตัวอื่น → "ลองแล้ว 0 keys" 10 จาก 14 คืน
 *   - เลนแชท: เขียนทับแถวที่ mirror เสร็จแล้วเป็น failed ลูกค้าวันนั้นได้ดวงเก่าจนรอบกู้ 00:20
 *   - เลนโพส: ใบที่ล้มหายจากโพส 00:05 ถาวร (10-21 เสาร์ · 10-22 ศุกร์ · 10-31 พุธกลางคืน)
 *
 * ใช้ sqlite ในหน่วยความจำ — รันบนเครื่อง dev ที่ไม่มี MySQL ได้
 */
class HoroscopeMidnightKeyContentionTest extends TestCase
{
    /** โมเดลที่ prod ใช้คืนนั้นตอบช้าสุด 17 วิ — นานกว่าคิว 12 วิเดิม */
    private const BUSY_SEC = 17;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Cache::flush();
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 0, 1, 0, 'Asia/Bangkok'));

        Schema::create('horoscope_daily_predictions', function (Blueprint $table) {
            $table->id();
            $table->date('target_date');
            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->unsignedBigInteger('zodiac_sign_id')->nullable();
            $table->string('prediction_type');
            $table->text('overall_prediction_th')->nullable();
            $table->text('love_prediction_th')->nullable();
            $table->text('career_prediction_th')->nullable();
            $table->text('finance_prediction_th')->nullable();
            $table->text('health_prediction_th')->nullable();
            $table->text('time_prediction_th')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('love_score')->nullable();
            $table->unsignedTinyInteger('career_score')->nullable();
            $table->unsignedTinyInteger('finance_score')->nullable();
            $table->unsignedTinyInteger('health_score')->nullable();
            $table->string('lucky_number')->nullable();
            $table->string('lucky_color_th')->nullable();
            $table->string('lucky_direction_th')->nullable();
            $table->string('ai_provider_used')->nullable();
            $table->string('ai_model_used')->nullable();
            $table->string('status')->default('generating');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        // คอลัมน์ DATE ของ MySQL ตัดเวลาทิ้งเอง แต่ sqlite เก็บตามที่ cast ส่งมา
        // ไม่ตัดให้เหมือนกัน updateOrCreate ตาม target_date จะหาแถวเดิมไม่เจอและงอกแถวใหม่
        DB::statement('CREATE TRIGGER horoscope_date_only AFTER INSERT ON horoscope_daily_predictions BEGIN '
            .'UPDATE horoscope_daily_predictions SET target_date = substr(NEW.target_date, 1, 10) WHERE id = NEW.id; END');

        Schema::create('fortune_horoscope_contents', function (Blueprint $table) {
            $table->id();
            $table->date('target_date');
            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->text('ai_prediction')->nullable();
            $table->string('ai_text_provider_used')->nullable();
            $table->string('ai_text_model_used')->nullable();
            $table->string('lucky_number')->nullable();
            $table->string('lucky_direction')->nullable();
            $table->string('status')->default('generating');
            $table->timestamps();
        });

        Schema::create('ai_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->string('model')->nullable();
            $table->string('base_url')->nullable();
            $table->text('api_key');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_critical')->default(false);
            $table->integer('priority')->default(50);
            $table->string('purpose')->nullable();
            $table->timestamp('disabled_until')->nullable();
            $table->timestamp('last_test_passed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // 1. คิวของ key
    // ─────────────────────────────────────────────────────────────

    public function test_งานหลังบ้านรอคิว_key_เดียวจนได้คิว_ไม่ยอมแพ้ที่_12_วิ(): void
    {
        $lock = $this->lockBusyFor(self::BUSY_SEC);
        $ai = $this->aiWithKeys(['key-เดียว']);

        $result = $ai->withConfigOverride(['key_wait_sec' => FortuneAIService::BATCH_KEY_WAIT_SEC])
            ->generateWithRetryAndFallback(questions: ['ดวงวันศุกร์'], readingType: 'basic');

        $this->assertSame('คำทำนายจาก key-เดียว', $result['response']);
        $this->assertSame([FortuneAIService::BATCH_KEY_WAIT_SEC], $lock->waits);
    }

    public function test_งานที่มีลูกค้ารอ_ยังรอคิวแค่_12_วิเท่าเดิม(): void
    {
        // ProcessFortuneTelling มี timeout 120 วิ — ใครเผลอยืดคิวให้ทุกงาน job จะถูกฆ่ากลางทาง
        $lock = $this->lockBusyFor(self::BUSY_SEC);
        $ai = $this->aiWithKeys(['key-เดียว']);

        try {
            $ai->generateWithRetryAndFallback(questions: ['ดูดวง'], readingType: 'basic');
            $this->fail('key ไม่ว่างและไม่มีตัวสำรอง ต้องโยน exception');
        } catch (Exception $e) {
            $this->assertStringContainsString('ไม่สามารถเชื่อมต่อ AI ได้', $e->getMessage());
        }

        $this->assertSame([12], $lock->waits);
    }

    public function test_คิวยาวใช้กับ_key_ตัวสุดท้ายเท่านั้น_ตัวก่อนหน้าต้องข้ามเร็ว(): void
    {
        // ถ้า key แรกรอ 120 วิ งานจะค้างอยู่หลัง key ที่ไม่ว่างทั้งที่ key ถัดไปว่างอยู่
        $lock = $this->lockBusyFor(self::BUSY_SEC);
        $ai = $this->aiWithKeys(['key-1', 'key-2']);

        $result = $ai->withConfigOverride(['key_wait_sec' => FortuneAIService::BATCH_KEY_WAIT_SEC])
            ->generateWithRetryAndFallback(questions: ['ดวงวันศุกร์'], readingType: 'basic');

        $this->assertSame('คำทำนายจาก key-2', $result['response']);
        $this->assertSame([12, FortuneAIService::BATCH_KEY_WAIT_SEC], $lock->waits);
    }

    public function test_key_ไม่ว่างต้องไม่รายงานว่าลอง_0_keys(): void
    {
        // ข้อความเดิมพาคนไล่ผิดทางมาหลายรอบ (อ่านเหมือนไม่มี key) ทั้งที่แค่รอคิวไม่ทัน
        $this->lockBusyFor(self::BUSY_SEC);
        $ai = $this->aiWithKeys(['key-เดียว']);

        try {
            $ai->generateWithRetryAndFallback(questions: ['ดูดวง'], readingType: 'basic');
            $this->fail('key ไม่ว่างและไม่มีตัวสำรอง ต้องโยน exception');
        } catch (Exception $e) {
            $this->assertStringNotContainsString('0 keys', $e->getMessage());
            $this->assertStringContainsString('key ไม่ว่าง', $e->getMessage());
        }
    }

    public function test_key_ไม่ว่างต้องเปิดทาง_emergency_ให้ลอง_key_อื่น(): void
    {
        // $errors ว่างเคยปิดทางนี้ไปด้วย ⇒ มี key สำรองก็ไม่ถูกลองเลย
        $this->lockBusyFor(self::BUSY_SEC);
        $ai = $this->aiWithKeys(['key-เดียว']);

        DB::table('ai_api_keys')->insert([
            'name' => 'สำรอง',
            'provider' => 'gemini',
            'model' => 'gemini-test',
            'api_key' => 'key-สำรอง',
            'is_active' => true,
            'purpose' => 'chat',
            'last_test_passed_at' => now(),
        ]);

        $result = $ai->generateWithRetryAndFallback(questions: ['ดูดวง'], readingType: 'basic');

        $this->assertSame('คำทำนายจาก key-สำรอง', $result['response']);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. ต่อสายครบทั้งสองเลน
    // ─────────────────────────────────────────────────────────────

    public function test_เลนแชทขอรอคิวแบบงานหลังบ้าน(): void
    {
        $ai = $this->aiThatAnswers();

        (new HoroscopeDailyService($ai))->generateBirthDayPrediction(5, $this->today());

        $this->assertSame(FortuneAIService::BATCH_KEY_WAIT_SEC, $ai->seenKeyWaitSec);
    }

    public function test_เลนโพสขอรอคิวแบบงานหลังบ้าน(): void
    {
        // ใบที่ล้มในเลนนี้หายจากโพส 00:05 ถาวร เลนนี้จึงพลาดการต่อสายไม่ได้
        $ai = $this->aiThatAnswers();

        $service = new class($ai) extends FortuneHoroscopeService
        {
            public function __construct(FortuneAIService $ai)
            {
                $this->aiService = $ai;
            }

            protected function buildTextPrompt(FortuneHoroscopeCampaign $campaign, Carbon $targetDate, int $birthDay, array $astrologyData): string
            {
                return 'prompt';
            }
        };

        (new ReflectionMethod($service, 'generatePrediction'))
            ->invoke($service, new FortuneHoroscopeCampaign, $this->today(), 5, []);

        $this->assertSame(FortuneAIService::BATCH_KEY_WAIT_SEC, $ai->seenKeyWaitSec);
    }

    // ─────────────────────────────────────────────────────────────
    // 3. เลนแชทล้มต้องไม่ทับของเลนโพส
    // ─────────────────────────────────────────────────────────────

    public function test_ai_ล้มแต่เลนโพสเจนเสร็จระหว่างรอ_ต้องไม่ทับเป็น_failed(): void
    {
        $service = new HoroscopeDailyService($this->aiThatFails(whilePostLaneFinishes: true));

        $prediction = $service->generateBirthDayPrediction(5, $this->today());

        $this->assertSame('generated', $prediction->status);
        $this->assertStringContainsString('บทความจากเลนโพส', (string) $prediction->overall_prediction_th);
        $this->assertSame('generated', HoroscopeDailyPrediction::where('birth_day', 5)->value('status'));
    }

    public function test_ai_ล้มและเลนโพสไม่มีของ_ยังต้องนับว่าล้มให้รอบกู้เห็น(): void
    {
        $service = new HoroscopeDailyService($this->aiThatFails(whilePostLaneFinishes: false));

        try {
            $service->generateBirthDayPrediction(5, $this->today());
            $this->fail('ไม่มีบทความเลย ต้องโยน exception ต่อ');
        } catch (Exception $e) {
            $this->assertStringContainsString('ไม่สามารถเชื่อมต่อ AI ได้', $e->getMessage());
        }

        $this->assertSame('failed', HoroscopeDailyPrediction::where('birth_day', 5)->value('status'));
    }

    // ─────────────────────────────────────────────────────────────

    private function today(): Carbon
    {
        return Carbon::create(2026, 10, 31, 0, 0, 0, 'Asia/Bangkok');
    }

    /** lock ปลอม — ได้คิวเมื่อยอมรอนานกว่าเวลาที่ key ไม่ว่าง ไม่ต้องรอจริงให้เทสต์ช้า */
    private function lockBusyFor(int $busySec): Lock
    {
        $lock = new class($busySec) implements Lock
        {
            public array $waits = [];

            public function __construct(private int $busySec) {}

            public function get($callback = null)
            {
                return true;
            }

            public function block($seconds, $callback = null)
            {
                $this->waits[] = $seconds;

                if ($seconds < $this->busySec) {
                    throw new LockTimeoutException;
                }

                return true;
            }

            public function release()
            {
                return true;
            }

            public function owner()
            {
                return 'test';
            }

            public function forceRelease() {}
        };

        Cache::shouldReceive('lock')->andReturn($lock);

        return $lock;
    }

    /**
     * FortuneAIService ที่ข้ามการอ่าน settings และไม่ยิง provider จริง
     *
     * @param  array<int,string>  $apiKeys
     */
    private function aiWithKeys(array $apiKeys): FortuneAIService
    {
        $ai = new class extends FortuneAIService
        {
            public array $keys = [];

            public function __construct()
            {
                $this->provider = 'openai';
            }

            protected function buildPrompt(array $questions, ?array $userProfile, ?array $userPosts, ?string $promptTemplate = null, ?string $birthDate = null): string
            {
                return 'prompt';
            }

            protected function getAllAvailableKeys(?string $userContext = null, string $purpose = 'prediction', ?string $preferredProvider = null): array
            {
                return $this->keys;
            }

            protected function callProviderDirect(string $provider, string $apiKey, string $model, string $prompt, array $config, ?string $baseUrl = null): array
            {
                return ['response' => "คำทำนายจาก {$apiKey}", 'provider' => $provider, 'model' => $model, 'tokens_used' => 0];
            }

            protected function recordUsageForKey(\App\Models\AiApiKey $key, int $tokensUsed, string $model, int $responseTime, string $requestType = 'fortune'): void {}
        };

        $ai->keys = array_map(fn (string $key) => [
            'provider' => 'openai',
            'api_key' => $key,
            'model' => 'gpt-test',
            'pool_key' => null,
            'name' => $key,
            'score' => 0,
        ], $apiKeys);

        return $ai;
    }

    private function aiThatAnswers(): FortuneAIService
    {
        return new class extends FortuneAIService
        {
            public ?int $seenKeyWaitSec = null;

            public function __construct() {}

            public function generateWithRetryAndFallback(
                array $questions,
                ?array $userProfile = null,
                ?array $userPosts = null,
                ?string $promptTemplate = null,
                string $readingType = 'basic',
                ?string $birthDate = null,
                ?string $userContext = null,
                string $purpose = 'prediction',
                ?array $modelOverrides = null
            ): array {
                $this->seenKeyWaitSec = $this->configOverride['key_wait_sec'] ?? null;

                return ['response' => 'คำทำนาย', 'provider' => 'openai', 'model' => 'gpt-test'];
            }
        };
    }

    /** AI ล้มแบบเดียวกับคืนนั้น — เลือกได้ว่าเลนโพสเจนเสร็จระหว่างรอหรือไม่ */
    private function aiThatFails(bool $whilePostLaneFinishes): FortuneAIService
    {
        return new class($whilePostLaneFinishes) extends FortuneAIService
        {
            public function __construct(private bool $postLaneFinishes) {}

            public function generateWithRetryAndFallback(
                array $questions,
                ?array $userProfile = null,
                ?array $userPosts = null,
                ?string $promptTemplate = null,
                string $readingType = 'basic',
                ?string $birthDate = null,
                ?string $userContext = null,
                string $purpose = 'prediction',
                ?array $modelOverrides = null
            ): array {
                if ($this->postLaneFinishes) {
                    DB::table('fortune_horoscope_contents')->insert([
                        'target_date' => '2026-10-31',
                        'birth_day' => 5,
                        'ai_prediction' => 'บทความจากเลนโพส',
                        'status' => FortuneHoroscopeContent::STATUS_GENERATED,
                    ]);
                }

                throw new Exception('ไม่สามารถเชื่อมต่อ AI ได้ (ลองแล้ว 1 keys): openai/2: key ไม่ว่าง');
            }
        };
    }
}
