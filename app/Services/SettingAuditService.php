<?php

namespace App\Services;

use App\Models\AccountingActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * บันทึกประวัติการแก้ค่าตั้ง (ตาราง settings) ว่าใคร/เมื่อไหร่/ค่าเดิม → ค่าใหม่
 *
 * ใช้ตาราง accounting_activity_logs ที่มีอยู่แล้ว (loggable = แถว Setting) ไม่สร้างตารางใหม่
 * action = 'setting.updated' · old_values/new_values = {key, value}
 */
class SettingAuditService
{
    public const ACTION = 'setting.updated';

    /**
     * บันทึกการเปลี่ยนค่า 1 key
     */
    public function record(User $admin, Setting $setting, mixed $oldValue, mixed $newValue, string $label, ?string $ip = null): void
    {
        AccountingActivityLog::create([
            'user_id' => $admin->id,
            'loggable_type' => Setting::class,
            'loggable_id' => $setting->id,
            'action' => self::ACTION,
            'description' => 'แก้ค่าตั้ง "'.$label.'": '.$this->display($oldValue).' → '.$this->display($newValue),
            'old_values' => ['key' => $setting->key, 'value' => $oldValue],
            'new_values' => ['key' => $setting->key, 'value' => $newValue, 'group' => $setting->group],
            'ip_address' => $ip,
        ]);
    }

    /**
     * ประวัติล่าสุดของ key ที่กำหนด
     *
     * @param  array<int, string>  $keys
     * @param  array<string, string>  $labels  key => ชื่อไทย
     * @return Collection<int, array{at: ?string, at_human: ?string, user: string, key: string, label: string, old: string, new: string}>
     */
    public function recent(array $keys, array $labels = [], int $limit = 20): Collection
    {
        try {
            $settingIds = Setting::whereIn('key', $keys)->pluck('id');

            if ($settingIds->isEmpty()) {
                return collect();
            }

            return AccountingActivityLog::query()
                ->with('user:id,name')
                ->where('loggable_type', Setting::class)
                ->where('action', self::ACTION)
                ->whereIn('loggable_id', $settingIds)
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(function (AccountingActivityLog $log) use ($labels) {
                    $key = (string) ($log->new_values['key'] ?? $log->old_values['key'] ?? '');

                    return [
                        'at' => $log->created_at?->format('d/m/Y H:i'),
                        'at_human' => $log->created_at?->diffForHumans(),
                        'user' => $log->user?->name ?? 'ไม่ทราบชื่อ',
                        'key' => $key,
                        'label' => $labels[$key] ?? $key,
                        'old' => $this->display($log->old_values['value'] ?? null),
                        'new' => $this->display($log->new_values['value'] ?? null),
                    ];
                });
        } catch (\Throwable $e) {
            Log::warning('SettingAuditService: read history failed', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * แปลงค่าให้อ่านง่ายในประวัติ
     */
    public function display(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => 'ว่าง',
            is_bool($value) => $value ? 'เปิด' : 'ปิด',
            is_float($value), is_int($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0',
            default => (string) $value,
        };
    }
}
