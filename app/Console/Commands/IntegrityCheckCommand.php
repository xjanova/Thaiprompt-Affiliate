<?php

namespace App\Console\Commands;

use App\Services\IntegrityService;
use Illuminate\Console\Command;

class IntegrityCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:integrity-check
                            {--clear-cache : Clear integrity cache before checking}
                            {--generate-checksums : Generate checksums for current files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check file integrity to detect unauthorized modifications';

    /**
     * Execute the console command.
     */
    public function handle(IntegrityService $integrityService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Integrity Check                     ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Generate checksums
        if ($this->option('generate-checksums')) {
            return $this->generateChecksums($integrityService);
        }

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->info('Clearing integrity cache...');
            $integrityService->clearCache();
            $this->newLine();
        }

        // Verify integrity
        $this->info('Checking file integrity...');
        $result = $integrityService->verify();

        $this->newLine();

        if ($result['valid']) {
            $this->info('✓ File integrity check passed!');
            $this->newLine();
            $this->line('All critical files are intact.');
            $this->line('Last checked: ' . $result['checked_at']);
            return self::SUCCESS;
        }

        $this->error('✗ File integrity check FAILED!');
        $this->newLine();

        $this->warn('The following violations were detected:');
        $this->newLine();

        foreach ($result['violations'] as $violation) {
            $this->line('File: ' . $violation['file']);
            $this->error('  Issue: ' . $violation['issue']);
            $this->line('  Message: ' . $violation['message']);
            $this->newLine();
        }

        $this->comment('⚠️  WARNING: Unauthorized file modifications detected!');
        $this->newLine();
        $this->line('This could indicate:');
        $this->line('  • Tampering with license validation');
        $this->line('  • Manual code modifications');
        $this->line('  • Corrupted installation');
        $this->newLine();
        $this->comment('Action required:');
        $this->line('  1. Reinstall the application from official source');
        $this->line('  2. Run: git reset --hard <latest-version>');
        $this->line('  3. Contact support if issue persists');

        return self::FAILURE;
    }

    /**
     * Generate checksums for current files
     */
    protected function generateChecksums(IntegrityService $integrityService): int
    {
        $this->info('Generating checksums for critical files...');
        $this->newLine();

        $checksums = $integrityService->generateChecksums();

        $this->table(
            ['File', 'SHA-256 Checksum'],
            collect($checksums)->map(function ($checksum, $file) {
                return [$file, $checksum];
            })->values()->toArray()
        );

        $this->newLine();
        $this->info('✓ Checksums generated successfully!');
        $this->newLine();
        $this->comment('Save these checksums for distribution:');
        $this->line(json_encode($checksums, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
