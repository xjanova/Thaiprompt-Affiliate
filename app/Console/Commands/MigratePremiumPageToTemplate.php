<?php

namespace App\Console\Commands;

use App\Models\PageTemplate;
use App\Models\PageSection;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePremiumPageToTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'templates:migrate-premium-page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'แปลงหน้าแรกปัจจุบัน (Premium Page) เป็น Default Template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 เริ่มต้นแปลงหน้าแรกเป็น Template...');
        $this->newLine();

        DB::beginTransaction();
        try {
            // Check if default template already exists
            $existingDefault = PageTemplate::where('is_default', true)->first();
            if ($existingDefault) {
                if (!$this->confirm("มี Default Template อยู่แล้ว ({$existingDefault->name}). ต้องการแทนที่?")) {
                    $this->warn('ยกเลิกการแปลง');
                    return 0;
                }

                $this->info("กำลังยกเลิก Default Template เดิม...");
                $existingDefault->update(['is_default' => false]);
            }

            // Create new default template
            $this->info('📄 สร้าง Default Template...');
            $template = PageTemplate::create([
                'name' => 'Default Homepage',
                'slug' => 'default-homepage',
                'description' => 'เทมเพลตหน้าแรกที่แปลงมาจาก Premium Page',
                'is_default' => true,
                'is_active' => true,
                'created_by' => 1, // Admin user
            ]);

            $this->line("✅ สร้างเทมเพลต: {$template->name} (ID: {$template->id})");
            $this->newLine();

            // Define sections to migrate
            $sections = [
                'hero' => [
                    'component_type' => 'hero',
                    'name' => 'Hero Section',
                    'order' => 0,
                    'fields' => ['title', 'subtitle', 'description', 'cta_text'],
                ],
                'statistics' => [
                    'component_type' => 'stats',
                    'name' => 'Live Statistics',
                    'order' => 1,
                    'fields' => ['title', 'subtitle'],
                ],
                'features' => [
                    'component_type' => 'features',
                    'name' => 'Features',
                    'order' => 2,
                    'fields' => ['title', 'subtitle'],
                ],
                'leaderboard' => [
                    'component_type' => 'leaderboard',
                    'name' => 'Top Affiliates',
                    'order' => 3,
                    'fields' => ['title', 'subtitle', 'limit'],
                ],
                'how_it_works' => [
                    'component_type' => 'process',
                    'name' => 'How It Works',
                    'order' => 4,
                    'fields' => ['title', 'subtitle'],
                ],
                'faq' => [
                    'component_type' => 'faq',
                    'name' => 'FAQ',
                    'order' => 5,
                    'fields' => ['title', 'subtitle'],
                ],
                'cta' => [
                    'component_type' => 'cta',
                    'name' => 'Final CTA',
                    'order' => 6,
                    'fields' => ['title', 'subtitle', 'cta_text'],
                ],
            ];

            $this->info('📦 แปลงส่วนต่าง ๆ เป็น Sections...');
            $bar = $this->output->createProgressBar(count($sections));
            $bar->start();

            foreach ($sections as $sectionKey => $config) {
                // Get content from settings
                $content = [];
                foreach ($config['fields'] as $field) {
                    $settingKey = "premium_page_{$sectionKey}_{$field}";
                    $value = Setting::get($settingKey, '');
                    if ($value) {
                        $content[$field] = $value;
                    }
                }

                // Get enabled status
                $enabled = Setting::get("premium_page_{$sectionKey}_enabled", true);

                // Create section
                PageSection::create([
                    'template_id' => $template->id,
                    'component_type' => $config['component_type'],
                    'name' => $config['name'],
                    'order' => $config['order'],
                    'content' => $content,
                    'styles' => [
                        'background_color' => '#ffffff',
                        'text_color' => '#000000',
                        'padding_y' => '60',
                    ],
                    'settings' => [],
                    'is_active' => $enabled,
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Set home_template_id in settings
            $this->info('⚙️  ตั้งค่าเทมเพลตเป็นหน้าแรก...');
            Setting::set('home_template_id', $template->id, 'integer', 'system');

            DB::commit();

            $this->newLine();
            $this->info('✨ แปลงสำเร็จ!');
            $this->table(
                ['รายการ', 'ข้อมูล'],
                [
                    ['เทมเพลต', $template->name],
                    ['ID', $template->id],
                    ['จำนวน Sections', count($sections)],
                    ['สถานะ', 'Default & Active'],
                ]
            );

            $this->newLine();
            $this->info('💡 คุณสามารถแก้ไขเทมเพลตได้ที่: /admin/templates/' . $template->id);
            $this->info('💡 ดูรายการเทมเพลตทั้งหมด: /admin/templates');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ เกิดข้อผิดพลาด: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
