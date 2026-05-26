#!/usr/bin/env php
<?php

/**
 * Seeder Verification Script
 *
 * This script verifies that all seeder files in database/seeders/
 * are properly included in DatabaseSeeder.php
 *
 * Usage: php scripts/verify-seeders.php
 * Exit Codes:
 *   0 - All seeders are included
 *   1 - Missing seeders found
 *   2 - Error occurred
 */
$scriptStart = microtime(true);

// Colors for terminal output
$colors = [
    'red' => "\033[0;31m",
    'green' => "\033[0;32m",
    'yellow' => "\033[1;33m",
    'blue' => "\033[0;34m",
    'magenta' => "\033[0;35m",
    'cyan' => "\033[0;36m",
    'white' => "\033[1;37m",
    'reset' => "\033[0m",
];

function colorize($text, $color)
{
    global $colors;

    return $colors[$color].$text.$colors['reset'];
}

function printHeader($text)
{
    echo "\n";
    echo colorize('═══════════════════════════════════════════════', 'cyan')."\n";
    echo colorize("  $text", 'cyan')."\n";
    echo colorize('═══════════════════════════════════════════════', 'cyan')."\n";
    echo "\n";
}

function printSuccess($text)
{
    echo colorize('✓ ', 'green').$text."\n";
}

function printError($text)
{
    echo colorize('✗ ', 'red').$text."\n";
}

function printWarning($text)
{
    echo colorize('⚠ ', 'yellow').$text."\n";
}

function printInfo($text)
{
    echo colorize('ℹ ', 'blue').$text."\n";
}

printHeader('🔍 Seeder Verification Tool');

// Define paths
$basePath = dirname(__DIR__);
$seederDir = $basePath.'/database/seeders';
$databaseSeederPath = $seederDir.'/DatabaseSeeder.php';

// Seeders ที่ถูกปิดใช้งานโดยตั้งใจ (ไม่ต้องรายงานเป็น error)
// เหตุผลดูได้จาก comment ใน DatabaseSeeder.php
$intentionallyExcludedSeeders = [
    'DemoUsersSeeder',          // ❌ ลบ - ใช้ ThaipromptMlmSeeder แทน
    'HomepageImportSeeder',     // SKIP: Already exists - ใช้ครั้งเดียว
    'MlmHierarchySeeder',       // ❌ ลบ - ใช้ ThaipromptMlmSeeder แทน
    'PageBuilderSeeder',        // SKIP: Already exists - ใช้ครั้งเดียว
    'ThaipromptMlmSeeder',      // ❌ ปิดไว้ - ทดสอบสมัครปกติ
    'WalletTopupPackagesSeeder', // ❌ ยกเลิก - ระบบเติมเงินไม่ใช้สินค้าแล้ว ใช้ PaymentTransaction โดยตรง
    'AppConfigSeeder',          // 🔧 รัน manual: php artisan db:seed --class=AppConfigSeeder (admin custom values อาจถูก upsert overwrite)
    'FeatureFlagSeeder',        // 🔧 รัน manual: php artisan db:seed --class=FeatureFlagSeeder (admin toggle อาจถูก reset)
];

// Check if directories exist
if (! is_dir($seederDir)) {
    printError("Seeder directory not found: $seederDir");
    exit(2);
}

if (! file_exists($databaseSeederPath)) {
    printError("DatabaseSeeder.php not found: $databaseSeederPath");
    exit(2);
}

printInfo("Scanning seeder directory: $seederDir");
printInfo("Reading DatabaseSeeder.php: $databaseSeederPath");
echo "\n";

// Find all seeder files
$seederFiles = glob($seederDir.'/*Seeder.php');
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

printInfo('Found '.count($allSeeders).' seeder files (excluding DatabaseSeeder)');

// Read DatabaseSeeder.php content
$databaseSeederContent = file_get_contents($databaseSeederPath);

// Remove comments before extracting seeder names
// This prevents commented-out seeders from being detected as "referenced but file not found"
// 1. Remove multi-line comments (/* ... */)
$contentWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $databaseSeederContent);
// 2. Remove single-line comments (// ...)
$contentWithoutComments = preg_replace('/\/\/.*$/m', '', $contentWithoutComments);

// Extract seeder class names from DatabaseSeeder.php (only from uncommented code)
// Look for patterns like: SeederName::class
preg_match_all('/(\w+Seeder)::class/', $contentWithoutComments, $matches);
$includedSeeders = $matches[1] ?? [];
$includedSeeders = array_unique($includedSeeders);
sort($includedSeeders);

printInfo('Found '.count($includedSeeders).' seeders included in DatabaseSeeder.php');
echo "\n";

// Find missing seeders (excluding intentionally disabled ones)
$missingSeeders = array_diff($allSeeders, $includedSeeders);
$actuallyMissing = array_diff($missingSeeders, $intentionallyExcludedSeeders);
$intentionallyDisabled = array_intersect($missingSeeders, $intentionallyExcludedSeeders);

// Find extra seeders (referenced but file doesn't exist)
$extraSeeders = array_diff($includedSeeders, $allSeeders);

// Check results (only actual missing seeders count as errors)
$hasIssues = false;

// Show intentionally disabled seeders (info only, not an error)
if (count($intentionallyDisabled) > 0) {
    printInfo('Found '.count($intentionallyDisabled).' seeder(s) intentionally disabled (OK):');
    echo "\n";
    foreach ($intentionallyDisabled as $seeder) {
        echo '  '.colorize("• $seeder", 'magenta')." (disabled)\n";
    }
    echo "\n";
}

// Check for actually missing seeders (this is an error)
if (count($actuallyMissing) > 0) {
    $hasIssues = true;
    printError('Found '.count($actuallyMissing).' seeder(s) NOT included in DatabaseSeeder.php:');
    echo "\n";
    foreach ($actuallyMissing as $seeder) {
        echo '  '.colorize("• $seeder", 'red')."\n";
    }
    echo "\n";

    printWarning('Please add these seeders to DatabaseSeeder.php in the run() method:');
    echo "\n";
    echo colorize('  $this->call([', 'yellow')."\n";
    foreach ($actuallyMissing as $seeder) {
        echo colorize("      {$seeder}::class,  // TODO: Add description", 'yellow')."\n";
    }
    echo colorize('  ]);', 'yellow')."\n";
    echo "\n";
}

if (count($extraSeeders) > 0) {
    $hasIssues = true;
    printWarning('Found '.count($extraSeeders).' seeder(s) referenced but file not found:');
    echo "\n";
    foreach ($extraSeeders as $seeder) {
        echo '  '.colorize("• $seeder", 'yellow')." (file does not exist)\n";
    }
    echo "\n";
}

if (! $hasIssues) {
    printSuccess('All seeders are properly included in DatabaseSeeder.php!');
    echo "\n";

    printInfo('Summary:');
    echo '  • Total seeder files: '.colorize(count($allSeeders), 'green')."\n";
    echo '  • Included in DatabaseSeeder: '.colorize(count($includedSeeders), 'green')."\n";
    echo '  • Intentionally disabled: '.colorize(count($intentionallyDisabled), 'magenta')."\n";
    echo '  • Missing seeders: '.colorize('0', 'green')."\n";
    echo "\n";

    $executionTime = round((microtime(true) - $scriptStart) * 1000, 2);
    printSuccess("Verification completed in {$executionTime}ms");
    echo "\n";

    exit(0);
} else {
    echo "\n";
    printError('Seeder verification FAILED!');
    echo "\n";

    printInfo('Summary:');
    echo '  • Total seeder files: '.colorize(count($allSeeders), 'blue')."\n";
    echo '  • Included in DatabaseSeeder: '.colorize(count($includedSeeders), 'blue')."\n";
    echo '  • Intentionally disabled: '.colorize(count($intentionallyDisabled), 'magenta')."\n";
    echo '  • Missing seeders: '.colorize(count($actuallyMissing), 'red')."\n";
    echo '  • Extra references: '.colorize(count($extraSeeders), 'yellow')."\n";
    echo "\n";

    printInfo('To fix this issue:');
    echo '  1. Add missing seeders to '.colorize('database/seeders/DatabaseSeeder.php', 'cyan')."\n";
    echo '  2. Or add to $intentionallyExcludedSeeders in '.colorize('scripts/verify-seeders.php', 'cyan')." if disabled on purpose\n";
    echo "  3. Ensure proper order (dependencies first)\n";
    echo "  4. Run this script again to verify\n";
    echo "\n";

    $executionTime = round((microtime(true) - $scriptStart) * 1000, 2);
    printError("Verification failed in {$executionTime}ms");
    echo "\n";

    exit(1);
}
