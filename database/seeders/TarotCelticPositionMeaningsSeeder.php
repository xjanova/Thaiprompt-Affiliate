<?php

namespace Database\Seeders;

use App\Models\TarotCard;
use App\Models\TarotCelticPositionMeaning;
use App\Models\TarotReadingCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * TarotCelticPositionMeaningsSeeder
 *
 * Seeder สำหรับโหลดคำทำนายไพ่ยิปซีในรูปแบบ Celtic Cross
 *
 * โหลดข้อมูลจาก JSON files ในโฟลเดอร์ database/data/tarot/celtic-cross/
 * แล้ว insert ลงตาราง tarot_celtic_position_meanings
 *
 * รองรับการรันซ้ำ (idempotent) - ใช้ updateOrCreate
 */
class TarotCelticPositionMeaningsSeeder extends Seeder
{
    /**
     * โฟลเดอร์เก็บไฟล์ JSON
     */
    protected string $dataPath;

    /**
     * แคชหมวดหมู่ทั้งหมด (slug => id)
     *
     * @var array<string, int|null>
     */
    protected array $categoryMap = [];

    /**
     * สถิติการ seed
     */
    protected int $totalInserted = 0;
    protected int $totalUpdated = 0;
    protected int $totalFiles = 0;
    protected int $errorCount = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dataPath = database_path('data/tarot/celtic-cross');
    }

    /**
     * รัน seeder
     */
    public function run(): void
    {
        $this->command->info('🔮 เริ่ม seed ข้อมูล Tarot Celtic Cross Position Meanings...');

        // เช็คว่าตาราง tarot_cards มีข้อมูลครบ 78 ใบหรือยัง
        if (TarotCard::count() < 78) {
            $this->command->warn('⚠️  ตาราง tarot_cards ยังไม่มีไพ่ครบ 78 ใบ กรุณารัน TarotSystemSeeder ก่อน');
            return;
        }

        // โหลดแคชหมวดหมู่
        $this->loadCategoryMap();

        // เช็คว่าโฟลเดอร์ JSON มีอยู่หรือไม่
        if (!File::isDirectory($this->dataPath)) {
            $this->command->error("❌ ไม่พบโฟลเดอร์: {$this->dataPath}");
            return;
        }

        // หาไฟล์ JSON ทั้งหมด
        $jsonFiles = collect(File::files($this->dataPath))
            ->filter(fn($file) => $file->getExtension() === 'json')
            ->sortBy(fn($file) => $file->getFilename())
            ->values();

        if ($jsonFiles->isEmpty()) {
            $this->command->warn('⚠️  ไม่พบไฟล์ JSON ใน ' . $this->dataPath);
            return;
        }

        $this->command->info("📁 พบไฟล์ JSON ทั้งหมด {$jsonFiles->count()} ไฟล์");

        // วนลูปประมวลผลแต่ละไฟล์
        $jsonFiles->each(function ($file) {
            $this->processJsonFile($file->getPathname());
            $this->totalFiles++;
        });

        // แสดงผลสรุป
        $this->command->newLine();
        $this->command->info('✅ Seed เสร็จสมบูรณ์!');
        $this->command->info("   📊 ไฟล์ที่ประมวลผล: {$this->totalFiles}");
        $this->command->info("   ➕ เพิ่มใหม่: {$this->totalInserted}");
        $this->command->info("   ✏️  อัพเดท: {$this->totalUpdated}");

        if ($this->errorCount > 0) {
            $this->command->warn("   ⚠️  ข้อผิดพลาด: {$this->errorCount}");
        }
    }

    /**
     * โหลดแคชหมวดหมู่จากฐานข้อมูล
     */
    protected function loadCategoryMap(): void
    {
        $categories = TarotReadingCategory::pluck('id', 'slug')->toArray();

        // เพิ่ม general เป็น null (ไม่ผูกกับหมวดใด)
        $this->categoryMap = array_merge(['general' => null], $categories);

        $this->command->info('📋 หมวดหมู่ที่พบ: ' . implode(', ', array_keys($this->categoryMap)));
    }

    /**
     * ประมวลผลไฟล์ JSON 1 ไฟล์
     */
    protected function processJsonFile(string $filePath): void
    {
        $filename = basename($filePath);
        $this->command->info("🃏 กำลังประมวลผล: {$filename}");

        try {
            $content = File::get($filePath);
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->command->error("   ❌ Parse JSON ไม่ได้: {$e->getMessage()}");
            $this->errorCount++;
            return;
        }

        // ดึงข้อมูลไพ่
        $cardInfo = $data['card_identifier'] ?? null;
        if (!$cardInfo) {
            $this->command->error("   ❌ ไม่พบ card_identifier ในไฟล์");
            $this->errorCount++;
            return;
        }

        // หา card_id จากชื่อภาษาอังกฤษ
        $card = TarotCard::where('name_en', $cardInfo['name_en'])->first();
        if (!$card) {
            $this->command->error("   ❌ ไม่พบไพ่: {$cardInfo['name_en']}");
            $this->errorCount++;
            return;
        }

        // วนลูปประมวลผลแต่ละตำแหน่ง
        $positions = $data['positions'] ?? [];
        $entryCount = 0;

        foreach ($positions as $positionNum => $positionData) {
            $entryCount += $this->processPosition(
                cardId: $card->id,
                positionNumber: (int) $positionNum,
                positionData: $positionData
            );
        }

        $this->command->info("   ✅ ประมวลผล {$entryCount} entries สำเร็จ");
    }

    /**
     * ประมวลผลตำแหน่ง 1 ตำแหน่ง (ครอบคลุม upright + reversed × 5 categories)
     *
     * @return int จำนวน entries ที่ประมวลผล
     */
    protected function processPosition(int $cardId, int $positionNumber, array $positionData): int
    {
        $positionSlug = $positionData['position_slug'] ?? null;
        $positionNameTh = $positionData['position_name_th'] ?? null;

        if (!$positionSlug || !$positionNameTh) {
            $this->command->warn("   ⚠️  ตำแหน่ง {$positionNumber} ขาด position_slug หรือ position_name_th");
            return 0;
        }

        $count = 0;

        // วนลูป upright + reversed
        foreach (['upright' => false, 'reversed' => true] as $direction => $isReversed) {
            $directionData = $positionData[$direction] ?? [];

            // วนลูป 5 หมวดหมู่
            foreach (TarotCelticPositionMeaning::CATEGORIES as $categorySlug => $categoryName) {
                $categoryData = $directionData[$categorySlug] ?? null;

                if (!$categoryData) {
                    continue;
                }

                $this->saveMeaning(
                    cardId: $cardId,
                    positionNumber: $positionNumber,
                    positionSlug: $positionSlug,
                    positionNameTh: $positionNameTh,
                    isReversed: $isReversed,
                    categorySlug: $categorySlug,
                    data: $categoryData
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * บันทึก 1 entry ลงตาราง
     */
    protected function saveMeaning(
        int $cardId,
        int $positionNumber,
        string $positionSlug,
        string $positionNameTh,
        bool $isReversed,
        string $categorySlug,
        array $data
    ): void {
        $categoryId = $this->categoryMap[$categorySlug] ?? null;

        $payload = [
            'card_id' => $cardId,
            'position_number' => $positionNumber,
            'position_slug' => $positionSlug,
            'position_name_th' => $positionNameTh,
            'is_reversed' => $isReversed,
            'category_id' => $categoryId,
            'category_slug' => $categorySlug,
            'core_meaning_th' => $data['core_meaning_th'] ?? '',
            'detailed_interpretation_th' => $data['detailed_interpretation_th'] ?? '',
            'adjacent_influences' => $data['adjacent_influences'] ?? null,
            'secret_keys_th' => $data['secret_keys_th'] ?? null,
            'advice_th' => $data['advice_th'] ?? '',
            'keywords_th' => $data['keywords_th'] ?? null,
            'intensity_level' => $data['intensity_level'] ?? 3,
            'emotional_tone' => $data['emotional_tone'] ?? 'neutral',
            'is_active' => true,
        ];

        $existing = TarotCelticPositionMeaning::where([
            'card_id' => $cardId,
            'position_number' => $positionNumber,
            'is_reversed' => $isReversed,
            'category_slug' => $categorySlug,
        ])->first();

        if ($existing) {
            $existing->update($payload);
            $this->totalUpdated++;
        } else {
            TarotCelticPositionMeaning::create($payload);
            $this->totalInserted++;
        }
    }
}
