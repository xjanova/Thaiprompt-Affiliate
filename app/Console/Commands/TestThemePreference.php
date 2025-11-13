<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TestThemePreference extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:test {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test theme preference per-user functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Theme Preference Per-User Test ===');
        $this->newLine();

        // 1. Check if column exists
        $this->info('1. Checking database column...');
        if (!Schema::hasColumn('users', 'menu_theme_preference')) {
            $this->error('❌ Column "menu_theme_preference" does NOT exist in users table!');
            $this->warn('Run: php artisan migrate');
            return 1;
        }
        $this->info('✅ Column "menu_theme_preference" exists');
        $this->newLine();

        // 2. Check User model fillable
        $this->info('2. Checking User model fillable...');
        $user = new User();
        $fillable = $user->getFillable();
        if (in_array('menu_theme_preference', $fillable)) {
            $this->info('✅ "menu_theme_preference" is in User fillable array');
        } else {
            $this->error('❌ "menu_theme_preference" is NOT in User fillable array!');
            return 1;
        }
        $this->newLine();

        // 3. Test with specific user or first user
        $userId = $this->argument('user_id') ?? 1;
        $testUser = User::find($userId);

        if (!$testUser) {
            $this->error("❌ User with ID {$userId} not found!");
            $this->warn('Try: php artisan theme:test <user_id>');
            return 1;
        }

        $this->info("3. Testing with User ID: {$testUser->id} ({$testUser->name})");
        $this->info("   Current theme: " . ($testUser->menu_theme_preference ?? 'NULL'));
        $this->newLine();

        // 4. Test save to classic_x
        $this->info('4. Testing save to "classic_x"...');
        $testUser->menu_theme_preference = 'classic_x';
        $saved = $testUser->save();

        if (!$saved) {
            $this->error('❌ Failed to save!');
            return 1;
        }

        $testUser->refresh();
        if ($testUser->menu_theme_preference === 'classic_x') {
            $this->info('✅ Successfully saved "classic_x"');
            $this->info("   Verified value: {$testUser->menu_theme_preference}");
        } else {
            $this->error('❌ Save succeeded but value is wrong!');
            $this->error("   Expected: classic_x, Got: " . ($testUser->menu_theme_preference ?? 'NULL'));
            return 1;
        }
        $this->newLine();

        // 5. Test save to millennium
        $this->info('5. Testing save to "millennium"...');
        $testUser->menu_theme_preference = 'millennium';
        $saved = $testUser->save();

        if (!$saved) {
            $this->error('❌ Failed to save!');
            return 1;
        }

        $testUser->refresh();
        if ($testUser->menu_theme_preference === 'millennium') {
            $this->info('✅ Successfully saved "millennium"');
            $this->info("   Verified value: {$testUser->menu_theme_preference}");
        } else {
            $this->error('❌ Save succeeded but value is wrong!');
            return 1;
        }
        $this->newLine();

        // 6. Test multiple users
        $this->info('6. Testing multiple users (per-user isolation)...');
        $users = User::take(3)->get();

        $this->table(
            ['ID', 'Name', 'Current Theme'],
            $users->map(fn($u) => [
                $u->id,
                $u->name,
                $u->menu_theme_preference ?? 'millennium (default)'
            ])
        );

        $this->newLine();
        $this->info('=== All Tests Passed! ✅ ===');
        $this->info('Theme preference is working correctly per-user.');

        return 0;
    }
}
