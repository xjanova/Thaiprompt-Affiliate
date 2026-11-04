<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🧹 (2026-11-04, owner) "ลบรุ่นเก่าให้หมด เหลือทั้งซีรีส์ของรุ่นล่าสุดในแต่ละเจ้า"
 *
 * คู่กับ AiApiKey::MODELS_BY_PROVIDER ที่ตัดใน commit เดียวกัน — ย้าย key/setting ที่ค้างรุ่นที่ลบ
 * ไปรุ่นใหม่ "ระดับเดียวกัน" (ถูก→ถูก แรง→แรง) ไม่ให้ dropdown หน้าแอดมินเลือกค่าเดิมไม่เจอ
 *
 *   - OpenAI → GPT-6 (prod ใช้ 6-luna / 6.1-sol อยู่แล้ว) · TTS gpt-4o-mini-tts ไม่แตะ
 *   - Gemini → 3.8-flash / 3.5-flash-lite (ตามที่ Google route 2.5 ไปเองตั้งแต่ 2026-12-01)
 *   - Claude → 5.5 · Grok → 4.7 / 4.3 · Qwen → 3.7-plus / 3.8 · Typhoon → v2.5 · MiniMax → 2.8
 *
 * ตารางที่แตะ: ai_api_keys, fortune_telling_settings, ai_content_settings (*_model), line_bot_ai_settings
 * key ที่ model ว่าง → resolveModel() ใช้ default ใหม่ของ provider เองอยู่แล้ว ไม่ต้องแตะ
 */
return new class extends Migration
{
    /**
     * ชื่อโมเดลที่ลบ → รุ่นที่ใช้แทน (ตรงตัว)
     */
    private const EXACT = [
        'openai' => [
            // ระดับถูก → gpt-6-luna
            'gpt-5.6-luna' => 'gpt-6-luna',
            'gpt-5.4-mini' => 'gpt-6-luna',
            'gpt-5.4-nano' => 'gpt-6-luna',
            'gpt-5-mini' => 'gpt-6-luna',
            'gpt-4o-mini' => 'gpt-6-luna',
            'gpt-3.5-turbo' => 'gpt-6-luna',
            'gpt-4' => 'gpt-6-luna',
            'gpt-4-turbo' => 'gpt-6-luna',
            'o1-mini' => 'gpt-6-luna',
            'o3-mini' => 'gpt-6-luna',
            // ระดับกลาง → gpt-6.1-terra
            'gpt-5.6-terra' => 'gpt-6.1-terra',
            'gpt-5.4' => 'gpt-6.1-terra',
            // ระดับแรง → gpt-6.1-sol
            'gpt-5.6-sol' => 'gpt-6.1-sol',
            'gpt-5.5' => 'gpt-6.1-sol',
            'gpt-5.4-pro' => 'gpt-6.1-sol',
            'gpt-5' => 'gpt-6.1-sol',
            'gpt-4o' => 'gpt-6.1-sol',
            'o1-preview' => 'gpt-6.1-sol',
            // ตัวแพงสุด → gpt-6-astra
            'gpt-5.5-pro' => 'gpt-6-astra',
        ],
        'gemini' => [
            'gemini-3.7-flash' => 'gemini-3.8-flash',
            'gemini-3.6-flash' => 'gemini-3.8-flash',
            'gemini-3.5-flash' => 'gemini-3.8-flash',
            'gemini-2.5-pro' => 'gemini-3.8-flash',
            'gemini-1.5-pro' => 'gemini-3.8-flash',
            'gemini-pro' => 'gemini-3.8-flash',
            'gemini-3.1-flash-lite' => 'gemini-3.5-flash-lite',
            'gemini-2.5-flash' => 'gemini-3.5-flash-lite',
            'gemini-2.5-flash-lite' => 'gemini-3.5-flash-lite',
            'gemini-1.5-flash' => 'gemini-3.5-flash-lite',
        ],
        'anthropic' => [
            'claude-opus-5' => 'claude-opus-5-5',
            'claude-sonnet-5' => 'claude-sonnet-5-5',
            'claude-opus-4-8' => 'claude-opus-5-5',
            'claude-opus-4-7' => 'claude-opus-5-5',
            'claude-opus-4-6' => 'claude-opus-5-5',
            'claude-sonnet-4-6' => 'claude-sonnet-5-5',
            'claude-haiku-4-5' => 'claude-haiku-5-5',
        ],
        'grok' => [
            'grok-4.6' => 'grok-4.7',
            'grok-4.5' => 'grok-4.7',
            'grok-4.20-0309-non-reasoning' => 'grok-4.3',
            'grok-4.20-0309-reasoning' => 'grok-4.3',
            'grok-4.20-multi-agent-0309' => 'grok-4.3',
        ],
        'qwen' => [
            'qwen-plus' => 'qwen3.7-plus',
            'qwen-max' => 'qwen3.8-max',
            'qwen-flash' => 'qwen3.8-flash',
            'qwen-turbo' => 'qwen3.8-flash',
            'qwen3-coder-flash' => 'qwen3.8-flash',
        ],
        'typhoon' => [
            'typhoon-v2.1-12b-instruct' => 'typhoon-v2.5-30b-a3b-instruct',
        ],
        'deepseek' => [
            'deepseek-chat' => 'deepseek-flash',
            'deepseek-coder' => 'deepseek-flash',
            'deepseek-reasoner' => 'deepseek-v4-pro',
        ],
        'minimax' => [
            'speech-2.6-hd' => 'speech-2.8-hd',
            'speech-02-hd' => 'speech-2.8-hd',
            'speech-01-hd' => 'speech-2.8-hd',
            'speech-2.6-turbo' => 'speech-2.8-turbo',
            'speech-02-turbo' => 'speech-2.8-turbo',
            'speech-01-turbo' => 'speech-2.8-turbo',
        ],
    ];

    /**
     * ตระกูลที่ลบทั้งตระกูล (ขึ้นต้นด้วย) — ใช้กับ id ที่มีวันที่ต่อท้าย
     * ⚠️ ห้ามใส่ 'claude-opus-5' ที่นี่ — จะไปจับ 'claude-opus-5-5' ด้วย
     */
    private const PREFIX = [
        'anthropic' => [
            'claude-haiku-4-5' => 'claude-haiku-5-5',
            'claude-sonnet-4-5' => 'claude-sonnet-5-5',
            'claude-opus-4-5' => 'claude-opus-5-5',
            'claude-opus-4-1' => 'claude-opus-5-5',
            'claude-3-opus' => 'claude-opus-5-5',
            'claude-3-sonnet' => 'claude-sonnet-5-5',
            'claude-3-5-sonnet' => 'claude-sonnet-5-5',
            'claude-3-7-sonnet' => 'claude-sonnet-5-5',
            'claude-3-5-haiku' => 'claude-haiku-5-5',
            'claude-3-haiku' => 'claude-haiku-5-5',
        ],
    ];

    /**
     * key purpose=tts ที่ค้างรุ่นที่ลบ → รุ่น TTS ของค่ายเดียวกัน
     */
    private const TTS_REPLACEMENT = [
        'gemini' => 'gemini-3.8-flash-tts',
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
     * คอลัมน์ที่ผูก provider ตายตัว (fortune_telling_settings)
     */
    private const SETTING_FIXED = [
        'gemini_model' => 'gemini',
        'claude_model' => 'anthropic',
        'minimax_model' => 'minimax',
    ];

    /**
     * ai_content_settings (key/value) — key → provider
     */
    private const CONTENT_KEYS = [
        'openai_default_model' => 'openai',
        'claude_default_model' => 'anthropic',
        'gemini_default_model' => 'gemini',
        'gemini_model' => 'gemini',
    ];

    public function up(): void
    {
        $this->remapProviderModelTable('ai_api_keys');
        $this->remapProviderModelTable('line_bot_ai_settings');
        $this->remapSettings();
        $this->remapContentSettings();
    }

    /**
     * Rollback: ไม่ revert — รุ่นเดิมถูกลบจาก dropdown แล้ว ย้อนกลับไปจะเลือกไม่เจอ
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — pruned AI models are not restored');
    }

    /**
     * ตารางที่มีคอลัมน์ provider + model คู่กันในแถวเดียว
     */
    private function remapProviderModelTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumns($table, ['provider', 'model'])) {
            return;
        }

        $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');
        $hasPurpose = Schema::hasColumn($table, 'purpose');
        $changes = [];
        $rows = DB::table($table)
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->get($hasPurpose ? ['id', 'provider', 'model', 'purpose'] : ['id', 'provider', 'model']);

        foreach ($rows as $row) {
            $target = $this->replacementFor((string) $row->provider, (string) $row->model);
            if ($target === null) {
                continue;
            }

            // key เสียง (purpose=tts) ต้องได้รุ่น TTS — ไม่ใช่รุ่นข้อความ (prod key 31: gemini-2.5-flash ที่ใช้เป็นป้ายชื่อ)
            if (($row->purpose ?? null) === 'tts' && isset(self::TTS_REPLACEMENT[$row->provider])) {
                $target = self::TTS_REPLACEMENT[$row->provider];
            }

            $update = ['model' => $target];
            if ($hasUpdatedAt) {
                $update['updated_at'] = now();
            }
            DB::table($table)->where('id', $row->id)->update($update);
            $changes[] = ['id' => $row->id, 'from' => $row->model, 'to' => $target];
        }

        if ($changes !== []) {
            Log::warning("Migration: ย้าย {$table} ".count($changes).' แถวออกจากรุ่นที่ลบ', ['changes' => $changes]);
        }
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
            Log::warning('Migration: ย้าย fortune_telling_settings ออกจากรุ่นที่ลบ', ['changes' => $changes]);
        }
    }

    private function remapContentSettings(): void
    {
        if (! Schema::hasTable('ai_content_settings') || ! Schema::hasColumns('ai_content_settings', ['key', 'value'])) {
            return;
        }

        $changes = [];
        foreach (self::CONTENT_KEYS as $key => $provider) {
            $value = DB::table('ai_content_settings')->where('key', $key)->value('value');
            if (! is_string($value) || $value === '') {
                continue;
            }

            $target = $this->replacementFor($provider, $value);
            if ($target === null) {
                continue;
            }

            DB::table('ai_content_settings')->where('key', $key)->update(['value' => $target]);
            $changes[] = ['key' => $key, 'from' => $value, 'to' => $target];
        }

        if ($changes !== []) {
            // ค่าเก่าอาจถูก cache ไว้ใน AiContentSetting::getValue — ล้างเฉพาะ key ที่เปลี่ยน
            foreach ($changes as $change) {
                try {
                    cache()->forget('ai_content_setting_'.$change['key']);
                } catch (\Throwable) {
                    // cache ล่ม ไม่ขวาง migration
                }
            }
            Log::warning('Migration: ย้าย ai_content_settings ออกจากรุ่นที่ลบ', ['changes' => $changes]);
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
