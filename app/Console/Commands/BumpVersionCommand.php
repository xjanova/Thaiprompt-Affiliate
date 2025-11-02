<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BumpVersionCommand extends Command
{
    protected $signature = 'version:bump {type=patch : The version bump type (major, minor, patch)}';
    protected $description = 'Bump application version';

    public function handle(): int
    {
        $type = $this->argument('type');
        
        if (!in_array($type, ['major', 'minor', 'patch'])) {
            $this->error('Invalid bump type. Use: major, minor, or patch');
            return self::FAILURE;
        }

        // Read current version
        $currentVersion = $this->getCurrentVersion();
        $this->info("Current version: $currentVersion");

        // Calculate new version
        $newVersion = $this->calculateNewVersion($currentVersion, $type);
        $this->info("New version: $newVersion");

        // Update files
        $this->updateVersionFile($newVersion);
        $this->updatePackageJson($newVersion);
        $this->updateChangelog($currentVersion, $newVersion);

        $this->newLine();
        $this->info("✅ Version bumped successfully!");
        $this->info("📦 Old: $currentVersion → New: $newVersion");
        $this->newLine();
        $this->warn("Don't forget to:");
        $this->warn("1. git add VERSION package.json CHANGELOG.md");
        $this->warn("2. git commit -m \"chore: bump version to $newVersion\"");
        $this->warn("3. git tag v$newVersion");
        $this->warn("4. git push && git push --tags");

        return self::SUCCESS;
    }

    protected function getCurrentVersion(): string
    {
        // Try VERSION file first
        $versionFile = base_path('VERSION');
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }

        // Fallback to package.json
        $packageFile = base_path('package.json');
        if (file_exists($packageFile)) {
            $package = json_decode(file_get_contents($packageFile), true);
            return $package['version'] ?? '1.0.0';
        }

        return '1.0.0';
    }

    protected function calculateNewVersion(string $current, string $type): string
    {
        $parts = explode('.', $current);
        $major = (int)($parts[0] ?? 0);
        $minor = (int)($parts[1] ?? 0);
        $patch = (int)($parts[2] ?? 0);

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }

        return "$major.$minor.$patch";
    }

    protected function updateVersionFile(string $version): void
    {
        $file = base_path('VERSION');
        file_put_contents($file, $version);
        $this->line("✓ Updated VERSION file");
    }

    protected function updatePackageJson(string $version): void
    {
        $file = base_path('package.json');
        if (!file_exists($file)) {
            return;
        }

        $package = json_decode(file_get_contents($file), true);
        $package['version'] = $version;
        file_put_contents($file, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $this->line("✓ Updated package.json");
    }

    protected function updateChangelog(string $oldVersion, string $newVersion): void
    {
        $changelogFile = base_path('CHANGELOG.md');
        if (!file_exists($changelogFile)) {
            return;
        }

        $date = date('Y-m-d');
        $newEntry = "\n## [v{$newVersion}] - {$date}\n\n";
        $newEntry .= "### ✨ Features\n";
        $newEntry .= "- Manual version bump from v{$oldVersion} to v{$newVersion}\n\n";
        $newEntry .= "### 🐛 Bug Fixes\n";
        $newEntry .= "- ไม่มีการแก้ไขบัก\n\n";
        $newEntry .= "### 🔧 Other Changes\n";
        $newEntry .= "- ไม่มีการเปลี่ยนแปลงอื่นๆ\n\n";

        $content = file_get_contents($changelogFile);
        
        // Insert after the header (after line 3)
        $lines = explode("\n", $content);
        $header = array_slice($lines, 0, 3);
        $rest = array_slice($lines, 3);
        
        $newContent = implode("\n", $header) . "\n" . $newEntry . implode("\n", $rest);
        file_put_contents($changelogFile, $newContent);
        
        $this->line("✓ Updated CHANGELOG.md");
    }
}
