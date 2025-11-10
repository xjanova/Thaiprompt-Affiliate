<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ReleaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'release:create
                            {version : Version number (e.g., 1.0.0 or v1.0.0)}
                            {--type=patch : Release type: major, minor, or patch}
                            {--changelog= : Changelog message (required)}
                            {--push : Automatically push to GitHub}
                            {--draft : Create as draft release}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new release with changelog';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $version = $this->argument('version');
        $type = $this->option('type');
        $changelog = $this->option('changelog');
        $isDraft = $this->option('draft');

        // Validate version format
        if (!preg_match('/^v?\d+\.\d+\.\d+$/', $version)) {
            $this->error('Invalid version format. Use format: 1.0.0 or v1.0.0');
            return self::FAILURE;
        }

        // Ensure version starts with 'v'
        if (!str_starts_with($version, 'v')) {
            $version = 'v' . $version;
        }

        // Check if changelog is provided
        if (empty($changelog)) {
            $this->error('Changelog is required. Use --changelog="Your changelog message"');
            $this->line('');
            $this->line('Example:');
            $this->line('  php artisan release:create v1.2.0 --changelog="เพิ่มระบบ affiliate tracking แบบ real-time"');
            return self::FAILURE;
        }

        $this->info("╔═══════════════════════════════════════════════════════════╗");
        $this->info("║            TP-Affiliate Release Creator                  ║");
        $this->info("╚═══════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Pre-flight checks
        if (!$this->preFlightChecks()) {
            return self::FAILURE;
        }

        // Show release info
        $this->table(
            ['Field', 'Value'],
            [
                ['Version', $version],
                ['Type', ucfirst($type)],
                ['Draft', $isDraft ? 'Yes' : 'No'],
                ['Changelog', $changelog],
            ]
        );

        if (!$this->confirm('Create this release?', true)) {
            $this->warn('Release cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();

        // Step 1: Update CHANGELOG.md
        if (!$this->updateChangelog($version, $type, $changelog)) {
            return self::FAILURE;
        }

        // Step 2: Update VERSION file
        if (!$this->updateVersionFile($version)) {
            return self::FAILURE;
        }

        // Step 3: Commit changes
        if (!$this->commitChanges($version, $changelog)) {
            return self::FAILURE;
        }

        // Step 4: Create Git tag
        if (!$this->createGitTag($version, $changelog)) {
            return self::FAILURE;
        }

        // Step 5: Push to GitHub (if --push flag)
        if ($this->option('push')) {
            if (!$this->pushToGitHub($version)) {
                return self::FAILURE;
            }

            // Step 6: Create GitHub Release
            if (!$this->createGitHubRelease($version, $changelog, $isDraft)) {
                return self::FAILURE;
            }
        } else {
            $this->newLine();
            $this->warn('⚠️  Release created locally. To publish to GitHub, run:');
            $this->line('  git push origin ' . $version);
            $this->line('  gh release create ' . $version . ' --title "' . $version . '" --notes "' . $changelog . '"');
        }

        // Success message
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          Release created successfully! 🎉                 ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if (!$isDraft && $this->option('push')) {
            $this->info('✅ Release ' . $version . ' is now available for updates!');
            $this->info('   Users can now update via Admin Panel > Updates');
        } elseif ($isDraft) {
            $this->warn('📝 Release created as DRAFT. It will NOT appear in update checks.');
            $this->info('   Publish it on GitHub when ready.');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Pre-flight checks
     */
    protected function preFlightChecks(): bool
    {
        $this->info('[1/6] Running pre-flight checks...');

        // Check if Git is available
        $result = Process::run('git --version');
        if (!$result->successful()) {
            $this->error('✗ Git is not installed or not available in PATH');
            return false;
        }
        $this->info('✓ Git is available');

        // Check if we're in a Git repository
        $result = Process::run('git rev-parse --git-dir');
        if (!$result->successful()) {
            $this->error('✗ Not a Git repository');
            return false;
        }
        $this->info('✓ Git repository detected');

        // Check for uncommitted changes
        $result = Process::run('git status --porcelain');
        if (!empty(trim($result->output()))) {
            $this->warn('⚠️  You have uncommitted changes');
            if (!$this->confirm('Continue anyway? (changes will be included in release commit)', false)) {
                $this->warn('Release cancelled. Please commit or stash your changes first.');
                return false;
            }
        } else {
            $this->info('✓ Working directory is clean');
        }

        // Check if on main branch
        $result = Process::run('git rev-parse --abbrev-ref HEAD');
        $currentBranch = trim($result->output());
        if ($currentBranch !== 'main' && $currentBranch !== 'master') {
            $this->warn("⚠️  You are on branch: {$currentBranch}");
            if (!$this->confirm('Releases should be created from main/master branch. Continue anyway?', false)) {
                return false;
            }
        } else {
            $this->info("✓ On {$currentBranch} branch");
        }

        $this->newLine();
        return true;
    }

    /**
     * Update CHANGELOG.md
     */
    protected function updateChangelog(string $version, string $type, string $changelog): bool
    {
        $this->info('[2/6] Updating CHANGELOG.md...');

        $changelogFile = base_path('CHANGELOG.md');

        // Create CHANGELOG.md if it doesn't exist
        if (!File::exists($changelogFile)) {
            $content = "# Changelog\n\n";
            $content .= "รายการการเปลี่ยนแปลงที่สำคัญของ TP-Affiliate\n\n";
            $content .= "## รูปแบบ\n";
            $content .= "- **[เวอร์ชัน]** - วันที่ - ประเภท\n";
            $content .= "- สรุปการเปลี่ยนแปลงที่สำคัญ (ไม่ต้องระบุรายละเอียดการแก้บัค)\n\n";
            $content .= "---\n\n";
        } else {
            $content = File::get($changelogFile);
        }

        // Add new version entry
        $date = now()->format('Y-m-d');
        $typeLabel = match($type) {
            'major' => 'Major Release',
            'minor' => 'Minor Release',
            'patch' => 'Patch Release',
            default => 'Release'
        };

        $entry = "## [{$version}] - {$date} - {$typeLabel}\n\n";
        $entry .= "{$changelog}\n\n";
        $entry .= "---\n\n";

        // Insert after the header
        $headerEnd = strpos($content, "---\n\n") + 6;
        $content = substr_replace($content, $entry, $headerEnd, 0);

        File::put($changelogFile, $content);

        $this->info('✓ CHANGELOG.md updated');
        $this->newLine();

        return true;
    }

    /**
     * Update VERSION file
     */
    protected function updateVersionFile(string $version): bool
    {
        $this->info('[3/6] Updating VERSION file...');

        $versionFile = base_path('VERSION');
        $cleanVersion = ltrim($version, 'v');

        File::put($versionFile, $cleanVersion);

        $this->info('✓ VERSION file updated to ' . $cleanVersion);
        $this->newLine();

        return true;
    }

    /**
     * Commit changes
     */
    protected function commitChanges(string $version, string $changelog): bool
    {
        $this->info('[4/6] Committing changes...');

        // Add files
        Process::run('git add CHANGELOG.md VERSION');

        // Check if there are other changes
        $result = Process::run('git status --porcelain');
        $hasOtherChanges = !empty(trim($result->output()));

        if ($hasOtherChanges) {
            if ($this->confirm('There are other uncommitted changes. Include them in the release commit?', false)) {
                Process::run('git add -A');
            }
        }

        // Commit
        $commitMessage = "release: {$version}\n\n{$changelog}";
        $result = Process::run(['git', 'commit', '-m', $commitMessage]);

        if (!$result->successful()) {
            $this->error('✗ Failed to commit changes');
            $this->error($result->errorOutput());
            return false;
        }

        $this->info('✓ Changes committed');
        $this->newLine();

        return true;
    }

    /**
     * Create Git tag
     */
    protected function createGitTag(string $version, string $changelog): bool
    {
        $this->info('[5/6] Creating Git tag...');

        $tagMessage = "{$version}\n\n{$changelog}";
        $result = Process::run(['git', 'tag', '-a', $version, '-m', $tagMessage]);

        if (!$result->successful()) {
            $this->error('✗ Failed to create Git tag');
            $this->error($result->errorOutput());
            return false;
        }

        $this->info("✓ Git tag {$version} created");
        $this->newLine();

        return true;
    }

    /**
     * Push to GitHub
     */
    protected function pushToGitHub(string $version): bool
    {
        $this->info('[6/6] Pushing to GitHub...');

        // Push commits
        $result = Process::timeout(120)->run('git push origin HEAD');
        if (!$result->successful()) {
            $this->error('✗ Failed to push commits');
            $this->error($result->errorOutput());
            return false;
        }
        $this->info('✓ Commits pushed');

        // Push tag
        $result = Process::timeout(120)->run("git push origin {$version}");
        if (!$result->successful()) {
            $this->error('✗ Failed to push tag');
            $this->error($result->errorOutput());
            return false;
        }
        $this->info('✓ Tag pushed');

        $this->newLine();

        return true;
    }

    /**
     * Create GitHub Release
     */
    protected function createGitHubRelease(string $version, string $changelog, bool $isDraft): bool
    {
        $this->info('Creating GitHub Release...');

        // Check if gh CLI is available
        $result = Process::run('gh --version');
        if (!$result->successful()) {
            $this->warn('✗ GitHub CLI (gh) not installed');
            $this->line('  Install: https://cli.github.com/');
            $this->line('  Or create release manually on GitHub');
            return true; // Not a failure, just skip
        }

        // Create release
        $args = ['gh', 'release', 'create', $version, '--title', $version, '--notes', $changelog];
        if ($isDraft) {
            $args[] = '--draft';
        }

        $result = Process::timeout(60)->run($args);

        if (!$result->successful()) {
            $this->warn('✗ Failed to create GitHub Release');
            $this->warn($result->errorOutput());
            $this->line('  You can create it manually on GitHub');
            return true; // Not a failure
        }

        $this->info('✓ GitHub Release created');
        $this->newLine();

        return true;
    }
}
