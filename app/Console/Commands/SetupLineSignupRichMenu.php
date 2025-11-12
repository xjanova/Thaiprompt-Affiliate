<?php

namespace App\Console\Commands;

use App\Services\LineService;
use App\Services\LineSignupRichMenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupLineSignupRichMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:setup-signup-richmenu
                            {--force : Force recreate even if exists}
                            {--set-default : Set as default Rich Menu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup LINE Rich Menu for signup system';

    protected $richMenuService;
    protected $lineService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        LineSignupRichMenuService $richMenuService,
        LineService $lineService
    ) {
        parent::__construct();
        $this->richMenuService = $richMenuService;
        $this->lineService = $lineService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up LINE Signup Rich Menu...');
        $this->newLine();

        // Check if image exists
        $imagePath = $this->richMenuService->getRichMenuImagePath();

        if (!file_exists($imagePath)) {
            $this->warn('⚠️  Rich Menu image not found at: ' . $imagePath);
            $this->info('Creating placeholder image...');
            $this->createPlaceholderImage($imagePath);
        }

        // Create Rich Menu
        $this->info('📋 Creating Rich Menu...');
        $richMenuId = $this->richMenuService->createSignupRichMenu();

        if (!$richMenuId) {
            $this->error('❌ Failed to create Rich Menu');
            return 1;
        }

        $this->info("✅ Rich Menu created: {$richMenuId}");
        $this->newLine();

        // Upload image
        $this->info('📸 Uploading Rich Menu image...');

        try {
            $this->lineService->uploadRichMenuImage($richMenuId, $imagePath);
            $this->info('✅ Image uploaded successfully');
        } catch (\Exception $e) {
            $this->error('❌ Failed to upload image: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Set as default if option provided
        if ($this->option('set-default')) {
            $this->info('🎯 Setting as default Rich Menu...');

            if ($this->richMenuService->setDefaultRichMenu($richMenuId)) {
                $this->info('✅ Set as default successfully');
            } else {
                $this->error('❌ Failed to set as default');
                return 1;
            }
        }

        $this->newLine();
        $this->info('🎉 Rich Menu setup completed!');
        $this->newLine();

        // Show summary
        $this->showSummary($richMenuId);

        return 0;
    }

    /**
     * Create placeholder Rich Menu image
     */
    protected function createPlaceholderImage(string $path): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Create a simple placeholder image (2500x1686 px)
        $image = imagecreatetruecolor(2500, 1686);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $green = imagecolorallocate($image, 29, 180, 70); // #1DB446
        $gray = imagecolorallocate($image, 240, 240, 240);
        $darkGray = imagecolorallocate($image, 100, 100, 100);

        // Fill background
        imagefill($image, 0, 0, $white);

        // Draw grid
        // Row 1 - Left button (สมัครสมาชิก)
        imagefilledrectangle($image, 10, 10, 1240, 833, $green);
        imagettftext($image, 60, 0, 400, 450, $white, $this->getDefaultFont(), '🚀 สมัครสมาชิก');

        // Row 1 - Right button (เส้นทางเศรษฐี)
        imagefilledrectangle($image, 1260, 10, 2490, 833, $gray);
        imagettftext($image, 50, 0, 1400, 450, $darkGray, $this->getDefaultFont(), '💰 เส้นทางเศรษฐี');

        // Row 2 - 3 buttons
        $buttons = [
            ['x' => 10, 'text' => '📖 วิธีการทำงาน'],
            ['x' => 853, 'text' => '🌟 เรื่องสำเร็จ'],
            ['x' => 1677, 'text' => '💬 ติดต่อเรา'],
        ];

        foreach ($buttons as $i => $button) {
            imagefilledrectangle($image, $button['x'], 853, $button['x'] + 813, 1676, $gray);
            imagettftext($image, 40, 0, $button['x'] + 100, 1280, $darkGray, $this->getDefaultFont(), $button['text']);
        }

        // Save image
        imagepng($image, $path);
        imagedestroy($image);

        $this->info('✅ Placeholder image created');
    }

    /**
     * Get default font path
     */
    protected function getDefaultFont(): string
    {
        // Try to find a system font
        $fontPaths = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            '/Windows/Fonts/arial.ttf',
        ];

        foreach ($fontPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Return empty string if no font found (will use default)
        return '';
    }

    /**
     * Show summary
     */
    protected function showSummary(string $richMenuId): void
    {
        $this->table(
            ['Setting', 'Value'],
            [
                ['Rich Menu ID', $richMenuId],
                ['Size', '2500 x 1686 px'],
                ['Areas', '6 clickable areas'],
                ['Chat Bar Text', 'เมนู'],
            ]
        );

        $this->newLine();
        $this->info('📝 Rich Menu Actions:');
        $this->line('  • สมัครสมาชิก - Start signup flow');
        $this->line('  • เส้นทางเศรษฐี - Show wealth path');
        $this->line('  • วิธีการทำงาน - How it works');
        $this->line('  • เรื่องสำเร็จ - Success stories');
        $this->line('  • ติดต่อเรา - Contact support');

        $this->newLine();
        $this->info('💡 Tips:');
        $this->line('  • To set as default: php artisan line:setup-signup-richmenu --set-default');
        $this->line('  • To force recreate: php artisan line:setup-signup-richmenu --force');
        $this->line('  • Custom image location: storage/app/public/line/rich-menu-signup.png');
    }
}
