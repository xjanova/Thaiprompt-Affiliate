<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * เพิ่ม "สล็อตเสียงอัปโหลด" แยกจาก "สล็อตเสียง TTS" + ตัวเลือกว่าจะใช้เสียงไหน
     *
     * เดิม: คลิปมีไฟล์เสียงได้ทีละ 1 (generate กับ upload เขียนทับคอลัมน์ audio_* เดียวกัน)
     * ใหม่: เก็บได้ทั้ง 2 พร้อมกัน — audio_* = สล็อต TTS, upload_audio_* = สล็อตอัปโหลด
     *       active_audio_source = เลือกว่าจะส่งอันไหนให้ลูกค้า (tts | upload)
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable()+return (คอลัมน์ใหม่จะไม่ถูกสร้าง)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_system_voice_clips')) {
            return; // ตารางหลักยังไม่มี — ปล่อยให้ migration สร้างตารางทำงานก่อน
        }

        Schema::table('fortune_system_voice_clips', function (Blueprint $table) {
            // ── สล็อตเสียงอัปโหลด (แยกจาก TTS) ──
            if (! Schema::hasColumn('fortune_system_voice_clips', 'upload_audio_path')) {
                $table->string('upload_audio_path')->nullable()->after('generated_at');
            }
            if (! Schema::hasColumn('fortune_system_voice_clips', 'upload_audio_url')) {
                $table->string('upload_audio_url')->nullable()->after('upload_audio_path');
            }
            if (! Schema::hasColumn('fortune_system_voice_clips', 'upload_audio_duration_ms')) {
                $table->integer('upload_audio_duration_ms')->nullable()->after('upload_audio_url');
            }
            if (! Schema::hasColumn('fortune_system_voice_clips', 'upload_original_name')) {
                $table->string('upload_original_name')->nullable()->after('upload_audio_duration_ms');
            }
            if (! Schema::hasColumn('fortune_system_voice_clips', 'upload_audio_at')) {
                $table->timestamp('upload_audio_at')->nullable()->after('upload_original_name');
            }

            // ── ตัวเลือกว่าจะใช้เสียงไหน (tts | upload) ──
            if (! Schema::hasColumn('fortune_system_voice_clips', 'active_audio_source')) {
                $table->string('active_audio_source', 10)->default('tts')->after('audio_source');
            }
        });

        // ── ย้ายข้อมูลเดิม: แถวที่ปัจจุบันเก็บ "ไฟล์อัปโหลด" ในสล็อต audio_* → ย้ายไปสล็อต upload_* ──
        //   (ตารางเล็ก ~11 แถว ไม่ต้อง chunk)
        //   ⚠️ ต้องย้าย "ไฟล์จริง" บนดิสก์ด้วย: เดิม upload ใช้ path `{key}.mp3` ซึ่งชนกับ
        //      path ที่ generate TTS จะเขียน → เปลี่ยนเป็น `{key}-upload.ext` กันเขียนทับกันภายหลัง
        $rows = DB::table('fortune_system_voice_clips')
            ->where('audio_source', 'upload')
            ->whereNotNull('audio_path')
            ->get();

        foreach ($rows as $r) {
            $oldPath = (string) $r->audio_path;
            $dir = trim(dirname($oldPath), '.');
            $ext = pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'mp3';
            $candidate = ($dir !== '' ? $dir.'/' : '').$r->clip_key.'-upload.'.$ext;

            // ⚠️ upload_audio_path ต้อง "ไม่เท่ากับ {key}.mp3 (path ของ TTS)" เด็ดขาด
            //    → adopt path `-upload` เฉพาะเมื่อไฟล์อยู่จริงเท่านั้น ไม่งั้น clear slot (กัน alias/เขียนทับ)
            $newUploadPath = null;
            $newUploadUrl = null;

            try {
                if (Storage::disk('public')->exists($oldPath)) {
                    if (Storage::disk('public')->exists($candidate) && $candidate !== $oldPath) {
                        Storage::disk('public')->delete($candidate);
                    }
                    if ($candidate !== $oldPath) {
                        Storage::disk('public')->move($oldPath, $candidate);
                    }
                }

                if (Storage::disk('public')->exists($candidate)) {
                    $newUploadPath = $candidate;
                    $newUploadUrl = Storage::disk('public')->url($candidate);
                } else {
                    Log::warning('SystemVoice migration: ย้ายไฟล์อัปโหลดเดิมไม่สำเร็จ — เคลียร์ upload slot (ให้แอดมินอัปโหลดใหม่)', [
                        'clip_key' => $r->clip_key,
                        'old_path' => $oldPath,
                        'candidate' => $candidate,
                    ]);
                }
            } catch (\Throwable $e) {
                // ย้ายไฟล์ throw → ไม่ adopt path เก่า (กัน alias กับ TTS) เคลียร์ slot + log
                $newUploadPath = null;
                $newUploadUrl = null;
                Log::warning('SystemVoice migration: ย้ายไฟล์อัปโหลดเดิม exception — เคลียร์ upload slot', [
                    'clip_key' => $r->clip_key,
                    'old_path' => $oldPath,
                    'error' => $e->getMessage(),
                ]);
            }

            $hasUpload = $newUploadPath !== null;

            DB::table('fortune_system_voice_clips')->where('id', $r->id)->update([
                // ย้าย audio_* → upload_* (เฉพาะเมื่อไฟล์อยู่จริงที่ path `-upload`)
                'upload_audio_path' => $newUploadPath,
                'upload_audio_url' => $newUploadUrl,
                'upload_audio_duration_ms' => $hasUpload ? $r->audio_duration_ms : null,
                'upload_original_name' => $hasUpload ? $r->audio_original_name : null,
                'upload_audio_at' => $hasUpload ? $r->generated_at : null,
                // ไฟล์อัปโหลดยังอยู่ → active=upload / ย้ายไม่สำเร็จ → fallback tts (กัน active ชี้สล็อตว่าง)
                'active_audio_source' => $hasUpload ? 'upload' : 'tts',
                // เคลียร์สล็อต TTS (ข้อมูลเดิมเป็นไฟล์อัปโหลด ไม่ใช่ TTS)
                'audio_path' => null,
                'audio_url' => null,
                'audio_duration_ms' => null,
                'audio_provider' => null,
                'audio_voice_id' => null,
                'audio_chars' => null,
                'generated_at' => null,
            ]);
        }

        // แถวที่เหลือ (tts/null) → active_audio_source = 'tts' (default อยู่แล้ว)
        DB::table('fortune_system_voice_clips')
            ->where(function ($q) {
                $q->where('audio_source', '!=', 'upload')->orWhereNull('audio_source');
            })
            ->whereNull('active_audio_source')
            ->update(['active_audio_source' => 'tts']);
    }

    /**
     * ลบสล็อตอัปโหลด + ตัวเลือก active (ย้อนกลับโครงสร้างเดิม)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_system_voice_clips')) {
            return;
        }

        Schema::table('fortune_system_voice_clips', function (Blueprint $table) {
            foreach ([
                'upload_audio_path',
                'upload_audio_url',
                'upload_audio_duration_ms',
                'upload_original_name',
                'upload_audio_at',
                'active_audio_source',
            ] as $col) {
                if (Schema::hasColumn('fortune_system_voice_clips', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
