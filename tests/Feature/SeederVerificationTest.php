<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeederVerificationTest extends TestCase
{
    /**
     * Test that all seeder files are properly included in DatabaseSeeder.php
     *
     * This test ensures that whenever a new seeder is created, it must be
     * added to DatabaseSeeder.php to be included in the seeding process.
     *
     * @return void
     */
    public function test_all_seeders_are_included_in_database_seeder(): void
    {
        $seederPath = database_path('seeders');
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

        // Get all seeder files
        $seederFiles = File::glob($seederPath . '/*Seeder.php');
        $allSeeders = [];

        foreach ($seederFiles as $file) {
            $className = basename($file, '.php');

            // Skip DatabaseSeeder itself
            if ($className === 'DatabaseSeeder') {
                continue;
            }

            $allSeeders[] = $className;
        }

        sort($allSeeders);

        // Read DatabaseSeeder.php content
        $databaseSeederContent = File::get($databaseSeederPath);

        // Extract seeder class names from DatabaseSeeder.php
        preg_match_all('/(\w+Seeder)::class/', $databaseSeederContent, $matches);
        $includedSeeders = $matches[1] ?? [];
        $includedSeeders = array_unique($includedSeeders);
        sort($includedSeeders);

        // Find missing seeders
        $missingSeeders = array_diff($allSeeders, $includedSeeders);

        // Create helpful error message
        $errorMessage = '';
        if (count($missingSeeders) > 0) {
            $errorMessage = sprintf(
                "\n\n" .
                "❌ Found %d seeder(s) NOT included in DatabaseSeeder.php:\n\n" .
                "%s\n\n" .
                "Please add these seeders to database/seeders/DatabaseSeeder.php:\n\n" .
                "\$this->call([\n%s\n]);\n\n" .
                "Or run: php scripts/verify-seeders.php for more details\n",
                count($missingSeeders),
                $this->formatList($missingSeeders, '  ❌ '),
                $this->formatSeederCalls($missingSeeders)
            );
        }

        $this->assertEmpty(
            $missingSeeders,
            $errorMessage ?: 'All seeders should be included in DatabaseSeeder.php'
        );

        // Also check for referenced but missing files
        $extraSeeders = array_diff($includedSeeders, $allSeeders);

        $extraErrorMessage = '';
        if (count($extraSeeders) > 0) {
            $extraErrorMessage = sprintf(
                "\n\n" .
                "⚠️  Found %d seeder(s) referenced in DatabaseSeeder.php but file not found:\n\n" .
                "%s\n\n" .
                "Please either:\n" .
                "  1. Create the missing seeder files, or\n" .
                "  2. Remove them from DatabaseSeeder.php\n",
                count($extraSeeders),
                $this->formatList($extraSeeders, '  ⚠️  ')
            );
        }

        $this->assertEmpty(
            $extraSeeders,
            $extraErrorMessage ?: 'No seeder should be referenced without existing file'
        );
    }

    /**
     * Format array as a bulleted list
     *
     * @param array $items
     * @param string $bullet
     * @return string
     */
    private function formatList(array $items, string $bullet = '  • '): string
    {
        return implode("\n", array_map(function ($item) use ($bullet) {
            return $bullet . $item;
        }, $items));
    }

    /**
     * Format seeders as $this->call() statements
     *
     * @param array $seeders
     * @return string
     */
    private function formatSeederCalls(array $seeders): string
    {
        return implode("\n", array_map(function ($seeder) {
            return "    {$seeder}::class,  // TODO: Add description and proper order";
        }, $seeders));
    }

    /**
     * Test that DatabaseSeeder.php file exists and is valid
     *
     * @return void
     */
    public function test_database_seeder_exists_and_is_valid(): void
    {
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

        $this->assertFileExists(
            $databaseSeederPath,
            'DatabaseSeeder.php must exist in database/seeders/'
        );

        $content = File::get($databaseSeederPath);

        // Check for run() method
        $this->assertStringContainsString(
            'public function run()',
            $content,
            'DatabaseSeeder must have a run() method'
        );

        // Check for $this->call() usage
        $this->assertStringContainsString(
            '$this->call(',
            $content,
            'DatabaseSeeder should use $this->call() to run seeders'
        );
    }

    /**
     * Test that seeder verification script exists and is executable
     *
     * @return void
     */
    public function test_seeder_verification_script_exists(): void
    {
        $scriptPath = base_path('scripts/verify-seeders.php');

        $this->assertFileExists(
            $scriptPath,
            'Seeder verification script must exist at scripts/verify-seeders.php'
        );

        $this->assertTrue(
            is_executable($scriptPath),
            'Seeder verification script must be executable. Run: chmod +x scripts/verify-seeders.php'
        );
    }
}
