<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SetUserTheme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:set {user_id} {theme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set theme preference for a specific user (millennium or classic_x)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $theme = $this->argument('theme');

        // Validate theme
        if (!in_array($theme, ['millennium', 'classic_x'])) {
            $this->error('Invalid theme! Use: millennium or classic_x');
            return 1;
        }

        // Find user
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        // Save theme
        $oldTheme = $user->menu_theme_preference ?? 'millennium';
        $user->menu_theme_preference = $theme;
        $user->save();
        $user->refresh();

        $this->info("✅ Theme updated for {$user->name} (ID: {$user->id})");
        $this->info("   Old theme: {$oldTheme}");
        $this->info("   New theme: {$user->menu_theme_preference}");

        return 0;
    }
}
