<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🔄 (2026-10-30) ย้าย key/setting ที่ยังชี้โมเดลที่ค่ายปิดไปแล้ว → รุ่นปัจจุบัน
 *
 * คู่กับ AiApiKey::MODELS_BY_PROVIDER ที่อัปเดตใน commit เดียวกัน
 *   - Groq: Llama ทุกตัว + qwen3-32b ปิดสำหรับ free/dev tier (Llama 2026-08-16) → gpt-oss
 *   - OpenAI: gpt-4 / gpt-3.5 / o1 / o3-mini ปิดแล้ว → gpt-5.6-luna
 *   - Gemini: gemini-3.5-flash ถูกแทนด้วย 3.6-flash
 *   - xAI: grok-2/3/4 รุ่นแรก + grok-4-fast → grok-4.3
 *   - Anthropic: Claude 3.x + Sonnet/Opus 4 รุ่นแรก ปลดระวางแล้ว → Claude 5.5
 *   - DeepSeek: deepseek-chat/coder/reasoner → deepseek-flash / deepseek-v4-pro
 *   - Typhoon / Xiaomi / OpenRouter: slug เก่าที่หายไปจาก /models
 *
 * key ที่ model ว่าง → resolveModel() ใช้ default ใหม่ของ provider เองอยู่แล้ว ไม่ต้องแตะ
 */
return new class extends Migration
{
    /**
     * ชื่อโมเดลที่ปิดแล้ว → รุ่นที่ใช้แทน (ตรงตัว)
     */
    private const EXACT = [
        'groq' => [
            'llama-3.3-70b-versatile' => 'openai/gpt-oss-120b',
            'llama-3.1-8b-instant' => 'openai/gpt-oss-20b',
            'meta-llama/llama-4-scout-17b-16e-instruct' => 'openai/gpt-oss-120b',
            'qwen/qwen3-32b' => 'openai/gpt-oss-120b',
            'qwen/qwen3.6-27b' => 'qwen/qwen3.8-27b',
        ],
        'openai' => [
            'gpt-4' => 'gpt-5.6-luna',
            'gpt-4-turbo' => 'gpt-5.6-luna',
            'gpt-3.5-turbo' => 'gpt-5.6-luna',
            'o1-mini' => 'gpt-5.6-luna',
            'o1-preview' => 'gpt-5.6-luna',
            'o3-mini' => 'gpt-5.6-luna',
        ],
        'gemini' => [
            'gemini-3.5-flash' => 'gemini-3.6-flash',
        ],
        'grok' => [
            'grok-4-latest' => 'grok-4.3',
            'grok-4' => 'grok-4.3',
            'grok-4-0709' => 'grok-4.3',
            'grok-beta' => 'grok-4.3',
        ],
        'anthropic' => [
            'claude-opus-4' => 'claude-opus-5-5',
            'claude-opus-4-0' => 'claude-opus-5-5',
            'claude-opus-4-20250514' => 'claude-opus-5-5',
            'claude-sonnet-4' => 'claude-sonnet-5-5',
            'claude-sonnet-4-0' => 'claude-sonnet-5-5',
            'claude-sonnet-4-20250514' => 'claude-sonnet-5-5',
        ],
        'deepseek' => [
            'deepseek-chat' => 'deepseek-flash',
            'deepseek-coder' => 'deepseek-flash',
            'deepseek-reasoner' => 'deepseek-v4-pro',
        ],
        'typhoon' => [
            'typhoon-v2-70b-instruct' => 'typhoon-v2.5-30b-a3b-instruct',
            'typhoon-v2-8b-instruct' => 'typhoon-v2.5-30b-a3b-instruct',
            'typhoon-v1.5x-70b-instruct' => 'typhoon-v2.5-30b-a3b-instruct',
        ],
        'xiaomi' => [
            'mimo-v2.5-pro' => 'mimo-v2.6-pro',
            'mimo-v2-pro' => 'mimo-v2.6-pro',
            'mimo-v2.5' => 'mimo-v2.6-flash',
            'mimo-v2-flash' => 'mimo-v2.6-flash',
            'mimo-v2-omni' => 'mimo-v2.6-flash',
        ],
        'openrouter' => [
            'anthropic/claude-sonnet-4' => 'anthropic/claude-sonnet-5.5',
            'google/gemini-2.0-flash-001' => 'google/gemini-3.5-flash-lite',
            'x-ai/grok-2-1212' => 'x-ai/grok-4.3',
        ],
    ];

    /**
     * ตระกูลที่ปิดทั้งตระกูล (ขึ้นต้นด้วย) → รุ่นที่ใช้แทน
     */
    private const PREFIX = [
        'grok' => [
            'grok-2' => 'grok-4.3',
            'grok-3' => 'grok-4.3',
            'grok-4-fast' => 'grok-4.3',
            'grok-4-1-fast' => 'grok-4.3',
        ],
        'anthropic' => [
            'claude-3-opus' => 'claude-opus-5-5',
            'claude-3-sonnet' => 'claude-sonnet-5-5',
            'claude-3-5-sonnet' => 'claude-sonnet-5-5',
            'claude-3-7-sonnet' => 'claude-sonnet-5-5',
            'claude-3-5-haiku' => 'claude-haiku-5-5',
            'claude-3-haiku' => 'claude-haiku-5-5',
        ],
    ];

    /**
     * คอลัมน์คู่ provider → model ใน fortune_telling_settings
     */
    private const SETTING_PAIRS = [
        'ai_provider' => 'ai_model',
        'chat_ai_provider' => 'chat_ai_model',
        'sensitive_classifier_provider' => 'sensitive_classifier_model',
        'sensitive_provider' => 'sensitive_model',
    ];

    /**
     * คอลัมน์ที่ผูก provider ตายตัว
     */
    private const SETTING_FIXED = [
        'gemini_model' => 'gemini',
        'claude_model' => 'anthropic',
    ];

    public function up(): void
    {
        $this->remapKeys();
        $this->remapSettings();
    }

    /**
     * Rollback: ไม่ revert — โมเดลเดิมถูกค่ายปิดแล้ว revert กลับไปก็ error อีก
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — retired AI models cannot be restored');
    }

    private function remapKeys(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        $changes = [];
        $rows = DB::table('ai_api_keys')
            ->whereIn('provider', array_unique(array_merge(array_keys(self::EXACT), array_keys(self::PREFIX))))
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->get(['id', 'name', 'provider', 'model']);

        foreach ($rows as $row) {
            $target = $this->replacementFor($row->provider, $row->model);
            if ($target === null) {
                continue;
            }

            DB::table('ai_api_keys')->where('id', $row->id)->update([
                'model' => $target,
                'updated_at' => now(),
            ]);
            $changes[] = ['id' => $row->id, 'name' => $row->name, 'from' => $row->model, 'to' => $target];
        }

        if ($changes === []) {
            Log::info('Migration: ไม่มี AI key ที่ใช้โมเดลที่ปิดแล้ว — ข้าม');

            return;
        }

        Log::warning('Migration: ย้าย '.count($changes).' AI key(s) ออกจากโมเดลที่ปิดแล้ว', ['changes' => $changes]);
    }

    private function remapSettings(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        $columns = Schema::getColumnListing('fortune_telling_settings');
        $pairs = [];
        foreach (self::SETTING_PAIRS as $providerCol => $modelCol) {
            if (in_array($providerCol, $columns, true) && in_array($modelCol, $columns, true)) {
                $pairs[$modelCol] = $providerCol;
            }
        }
        foreach (self::SETTING_FIXED as $modelCol => $provider) {
            if (in_array($modelCol, $columns, true)) {
                $pairs[$modelCol] = null;
            }
        }

        if ($pairs === []) {
            return;
        }

        $changes = [];
        foreach (DB::table('fortune_telling_settings')->get() as $row) {
            $update = [];
            foreach ($pairs as $modelCol => $providerCol) {
                $model = $row->{$modelCol} ?? null;
                $provider = $providerCol !== null ? ($row->{$providerCol} ?? null) : self::SETTING_FIXED[$modelCol];
                if (! $model || ! $provider) {
                    continue;
                }

                $target = $this->replacementFor($provider, $model);
                if ($target !== null) {
                    $update[$modelCol] = $target;
                    $changes[] = ['id' => $row->id, 'column' => $modelCol, 'from' => $model, 'to' => $target];
                }
            }

            if ($update !== []) {
                if (in_array('updated_at', $columns, true)) {
                    $update['updated_at'] = now();
                }
                DB::table('fortune_telling_settings')->where('id', $row->id)->update($update);
            }
        }

        if ($changes !== []) {
            Log::warning('Migration: ย้าย fortune_telling_settings ออกจากโมเดลที่ปิดแล้ว', ['changes' => $changes]);
        }
    }

    private function replacementFor(string $provider, string $model): ?string
    {
        if (isset(self::EXACT[$provider][$model])) {
            return self::EXACT[$provider][$model];
        }

        foreach (self::PREFIX[$provider] ?? [] as $prefix => $target) {
            if (str_starts_with($model, $prefix)) {
                return $target;
            }
        }

        return null;
    }
};
