<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\LineAvatar;
use App\Models\LineRichMenu;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebPDatabaseUpdateService
{
    /**
     * Update all image paths in database to use WebP
     *
     * @param array $updatedRecords Array of ['old_path' => '', 'new_path' => '', 'directory' => '']
     * @return array Results summary
     */
    public function updateAllImagePaths(array $updatedRecords): array
    {
        $results = [
            'settings' => 0,
            'line_avatars' => 0,
            'line_rich_menus' => 0,
            'withdrawal_requests' => 0,
            'total_updated' => 0,
            'errors' => [],
        ];

        // Group by directory for efficient processing
        $byDirectory = [];
        foreach ($updatedRecords as $record) {
            $dir = $record['directory'];
            if (!isset($byDirectory[$dir])) {
                $byDirectory[$dir] = [];
            }
            $byDirectory[$dir][] = $record;
        }

        // Update each table based on directory
        DB::beginTransaction();

        try {
            // Update Settings (branding, page-loaders)
            if (isset($byDirectory['branding']) || isset($byDirectory['page-loaders'])) {
                $settingsUpdated = $this->updateSettings(
                    array_merge(
                        $byDirectory['branding'] ?? [],
                        $byDirectory['page-loaders'] ?? []
                    )
                );
                $results['settings'] = $settingsUpdated;
            }

            // Update LINE Avatars
            if (isset($byDirectory['avatars'])) {
                $avatarsUpdated = $this->updateLineAvatars($byDirectory['avatars']);
                $results['line_avatars'] = $avatarsUpdated;
            }

            // Update LINE Rich Menus
            if (isset($byDirectory['rich-menus'])) {
                $richMenusUpdated = $this->updateLineRichMenus($byDirectory['rich-menus']);
                $results['line_rich_menus'] = $richMenusUpdated;
            }

            // Update Withdrawal Requests (transfer slips)
            if (isset($byDirectory['withdrawal-slips'])) {
                $withdrawalsUpdated = $this->updateWithdrawalRequests($byDirectory['withdrawal-slips']);
                $results['withdrawal_requests'] = $withdrawalsUpdated;
            }

            $results['total_updated'] = array_sum([
                $results['settings'],
                $results['line_avatars'],
                $results['line_rich_menus'],
                $results['withdrawal_requests'],
            ]);

            DB::commit();

            Log::info('WebP database update completed', $results);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            Log::error('WebP database update failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Update Settings table (logo, favicon, page_loader_gif)
     */
    protected function updateSettings(array $records): int
    {
        $updated = 0;

        foreach ($records as $record) {
            $oldPath = '/storage/' . $record['old_path'];
            $newPath = '/storage/' . $record['new_path'];

            try {
                // Update logo
                $logoSetting = Setting::where('key', 'logo')
                    ->where('value', $oldPath)
                    ->first();

                if ($logoSetting) {
                    $logoSetting->update(['value' => $newPath]);
                    $updated++;
                }

                // Update favicon
                $faviconSetting = Setting::where('key', 'favicon')
                    ->where('value', $oldPath)
                    ->first();

                if ($faviconSetting) {
                    $faviconSetting->update(['value' => $newPath]);
                    $updated++;
                }

                // Update page_loader_gif
                $gifSetting = Setting::where('key', 'page_loader_gif')
                    ->where('value', $oldPath)
                    ->first();

                if ($gifSetting) {
                    $gifSetting->update(['value' => $newPath]);
                    $updated++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to update setting for {$oldPath}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Update LineAvatar table
     */
    protected function updateLineAvatars(array $records): int
    {
        $updated = 0;

        foreach ($records as $record) {
            $oldPath = $record['old_path'];
            $newPath = $record['new_path'];

            try {
                // Update avatars with file_path
                $avatars = LineAvatar::where('file_path', $oldPath)
                    ->where('source_type', 'upload')
                    ->get();

                foreach ($avatars as $avatar) {
                    $avatar->update([
                        'file_path' => $newPath,
                        'file_url' => '/storage/' . $newPath,
                    ]);
                    $updated++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to update LINE avatar for {$oldPath}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Update LineRichMenu table
     */
    protected function updateLineRichMenus(array $records): int
    {
        $updated = 0;

        foreach ($records as $record) {
            $oldPath = $record['old_path'];
            $newPath = $record['new_path'];

            try {
                $richMenus = LineRichMenu::where('image_path', $oldPath)->get();

                foreach ($richMenus as $menu) {
                    $menu->update(['image_path' => $newPath]);
                    $updated++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to update rich menu for {$oldPath}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Update WithdrawalRequest table (transfer slips)
     */
    protected function updateWithdrawalRequests(array $records): int
    {
        $updated = 0;

        foreach ($records as $record) {
            $oldPath = $record['old_path'];
            $newPath = $record['new_path'];

            try {
                $withdrawals = WithdrawalRequest::where('transfer_slip', $oldPath)->get();

                foreach ($withdrawals as $withdrawal) {
                    $withdrawal->update(['transfer_slip' => $newPath]);
                    $updated++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to update withdrawal request for {$oldPath}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Rollback database changes (if needed)
     */
    public function rollbackChanges(array $updatedRecords): array
    {
        $results = [
            'rolled_back' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($updatedRecords as $record) {
                $oldPath = '/storage/' . $record['old_path'];
                $newPath = '/storage/' . $record['new_path'];

                // Rollback Settings
                Setting::where('value', $newPath)->update(['value' => $oldPath]);

                // Rollback LINE Avatars
                LineAvatar::where('file_path', $record['new_path'])
                    ->update([
                        'file_path' => $record['old_path'],
                        'file_url' => $oldPath,
                    ]);

                // Rollback Rich Menus
                LineRichMenu::where('image_path', $record['new_path'])
                    ->update(['image_path' => $record['old_path']]);

                // Rollback Withdrawals
                WithdrawalRequest::where('transfer_slip', $record['new_path'])
                    ->update(['transfer_slip' => $record['old_path']]);

                $results['rolled_back']++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }
}
