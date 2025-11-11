<?php

namespace App\Console\Commands;

use App\Models\WindowsUiSetting;
use Illuminate\Console\Command;

class ResetStartMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:reset {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Start Menu to use hard-coded menus (fixes missing menu items)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        if ($type) {
            // Reset specific menu type
            $this->resetMenuType($type);
        } else {
            // Reset all menus
            if ($this->confirm('ต้องการ reset เมนูทั้งหมด (admin, user, seller)?', true)) {
                $this->resetMenuType('admin');
                $this->resetMenuType('user');
                $this->resetMenuType('seller');

                $this->info('✅ Reset เมนูทั้งหมดเรียบร้อยแล้ว!');
            }
        }

        $this->newLine();
        $this->info('💡 เมนูจะใช้ hard-coded version ที่มีเมนูครบถ้วนแล้ว');
        $this->info('🔄 กรุณา refresh หน้าเว็บเพื่อดูเมนูใหม่');

        return Command::SUCCESS;
    }

    /**
     * Reset menu for specific type
     *
     * @param string $type
     * @return void
     */
    protected function resetMenuType(string $type)
    {
        $key = "windows_start_menu_items_{$type}";

        // Delete the database entry to force fallback to hard-coded menu
        WindowsUiSetting::where('key', $key)->delete();

        $this->info("✅ Reset {$type} menu เรียบร้อย");
    }
}
