<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ShowUserThemes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:show {--all : Show all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show theme preferences for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== User Theme Preferences ===');
        $this->newLine();

        if ($this->option('all')) {
            $users = User::all();
            $this->info("Showing all {$users->count()} users:");
        } else {
            $users = User::take(10)->get();
            $this->info('Showing first 10 users (use --all to show all):');
        }

        $this->newLine();

        $data = $users->map(function($user) {
            $theme = $user->menu_theme_preference ?? 'millennium';
            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Theme' => $theme,
                'Icon' => $theme === 'classic_x' ? '📐' : '⚡'
            ];
        });

        $this->table(
            ['ID', 'Name', 'Email', 'Theme', 'Icon'],
            $data
        );

        // Show statistics
        $this->newLine();
        $millenniumCount = $users->where('menu_theme_preference', 'millennium')->count();
        $classicXCount = $users->where('menu_theme_preference', 'classic_x')->count();
        $nullCount = $users->whereNull('menu_theme_preference')->count();

        $this->info('Statistics:');
        $this->info("  ⚡ Millennium: {$millenniumCount} users");
        $this->info("  📐 Classic X: {$classicXCount} users");
        $this->info("  ❓ NULL/Default: {$nullCount} users");

        return 0;
    }
}
