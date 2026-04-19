<?php

namespace App\Console\Commands;

use App\Services\AppReleaseSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-shot ingestion of GitHub Releases into the `app_releases` table.
 *
 * Use cases:
 *   - First-time setup before GitHub webhook is wired up
 *   - Recovery after a DB wipe or migration reset
 *   - Re-sync if a release was edited (notes, asset re-upload, etc.)
 *
 * The underlying upsert is idempotent — safe to re-run any time.
 *
 *   php artisan releases:backfill
 *   php artisan releases:backfill --repo=xjanova/thaipromptapp --limit=50
 *   php artisan releases:backfill --dry-run
 */
class BackfillAppReleases extends Command
{
    protected $signature = 'releases:backfill
        {--repo=xjanova/thaipromptapp : owner/repo to fetch releases from}
        {--limit=30 : max releases to fetch (1..100)}
        {--dry-run : show what would be done without writing}';

    protected $description = 'Fetch published releases from GitHub and populate app_releases (idempotent).';

    public function handle(AppReleaseSync $sync): int
    {
        $repo = (string) $this->option('repo');
        $limit = max(1, min(100, (int) $this->option('limit')));
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Fetching up to {$limit} releases from github.com/{$repo} ...");

        $req = Http::timeout(15)->withHeaders([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'thaiprompt-backfill',
        ]);

        // Use a GitHub token if one is in env (raises 60/hr → 5000/hr rate limit
        // and lets us read private-repo releases). Public repos work fine without.
        if ($token = env('GITHUB_TOKEN')) {
            $req = $req->withToken($token);
        }

        $resp = $req->get("https://api.github.com/repos/{$repo}/releases", [
            'per_page' => $limit,
        ]);

        if (!$resp->successful()) {
            $this->error("GitHub API failed: HTTP {$resp->status()}");
            $this->line(substr((string) $resp->body(), 0, 500));
            return self::FAILURE;
        }

        $releases = $resp->json();
        if (!is_array($releases)) {
            $this->error('GitHub returned non-array body — aborting.');
            return self::FAILURE;
        }

        $this->info('Found ' . count($releases) . ' releases on GitHub.');
        $this->newLine();

        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errored' => 0];

        foreach ($releases as $r) {
            $tag = $r['tag_name'] ?? '?';
            $isDraft = $r['draft'] ?? false;
            $isPrerel = $r['prerelease'] ?? false;
            $assetCount = count($r['assets'] ?? []);
            $marker = $isDraft ? 'draft' : ($isPrerel ? 'beta' : 'stable');

            if ($dryRun) {
                $this->line(sprintf('  [dry-run] %-12s %-8s assets=%d', $tag, $marker, $assetCount));
                continue;
            }

            try {
                $result = $sync->upsertFromGitHubRelease($r);
                $key = $result ?? 'skipped';
                $stats[$key]++;
                $color = match ($key) {
                    'inserted' => 'green',
                    'updated' => 'yellow',
                    default => 'comment',
                };
                $this->line(sprintf(
                    '  <fg=%s>%-10s</> %-12s %-8s assets=%d',
                    $color, $key, $tag, $marker, $assetCount
                ));
            } catch (\Throwable $e) {
                $stats['errored']++;
                $this->warn("  errored   {$tag} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done — inserted: %d, updated: %d, skipped: %d, errored: %d',
            $stats['inserted'],
            $stats['updated'],
            $stats['skipped'],
            $stats['errored']
        ));

        return $stats['errored'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
