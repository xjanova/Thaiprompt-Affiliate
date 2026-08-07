{{-- 🐛 Fortune Debug Tools — เครื่องมือดีบักสำหรับแอดมิน (ธีม V4 นวลทองคำ) --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก — ครอบ Alpine x-data ทั้งหน้า --}}
<div x-data="debugTools()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัวหน้า (header) --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · เครื่องมือดีบัก
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-bug" style="color:var(--accent1);"></i>
                Fortune Debug Tools
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:6px;">
                Tail laravel.log + ทดสอบ AI sync — ไม่ต้อง SSH เข้าเซิร์ฟเวอร์
            </div>
        </div>
        <div class="tp-pill tp-pill-soft" style="font-family:ui-monospace,'SFMono-Regular',Menlo,monospace;font-size:12px;max-width:100%;overflow:hidden;text-overflow:ellipsis;">
            <i class="fas fa-folder-open" style="margin-right:6px;color:var(--accent2);"></i>{{ $logPath }}
        </div>
    </div>

    {{-- ═══════════════════════════════════
         ส่วนที่ 1 — Tail laravel.log
         ═══════════════════════════════════ --}}
    <div class="tp-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <div class="tp-section-h" style="margin:0;display:flex;align-items:center;gap:9px;">
                <i class="fas fa-scroll" style="color:var(--accent2);"></i>
                Tail laravel.log
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <label class="tp-muted" style="font-size:12px;display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" x-model="autoRefresh" style="accent-color:var(--accent1);">
                    auto-refresh 3 วินาที
                </label>
                <span class="tp-muted" style="font-size:12px;" x-text="log.fetched_at ? '⏱ ' + log.fetched_at.substring(11, 19) : ''"></span>
                <button @click="fetchLog()" type="button" :disabled="log.loading"
                        class="tp-btn tp-btn-sm tp-btn-primary">
                    <span x-show="!log.loading"><i class="fas fa-rotate" style="margin-right:5px;"></i>รีเฟรช</span>
                    <span x-show="log.loading" x-cloak><i class="fas fa-spinner fa-spin" style="margin-right:5px;"></i>กำลังโหลด...</span>
                </button>
            </div>
        </div>

        {{-- แถวควบคุม: filter + จำนวนบรรทัด --}}
        <div style="display:grid;grid-template-columns:1fr;gap:10px;margin-bottom:12px;" class="tp-debug-controls">
            <div class="tp-well tp-input" style="padding:0;">
                <input type="text" x-model="log.filter" @keydown.enter="fetchLog()"
                       placeholder="filter (regex หรือ keyword) — เช่น celtic|fortune ask"
                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
            </div>
            <div class="tp-well tp-input" style="padding:0;">
                <select x-model.number="log.lines" @change="fetchLog()"
                        style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                    <option value="50">50 บรรทัด</option>
                    <option value="100">100 บรรทัด</option>
                    <option value="200">200 บรรทัด</option>
                    <option value="500">500 บรรทัด</option>
                </select>
            </div>
        </div>

        {{-- Quick filters — ปุ่มกรองด่วน --}}
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;font-size:12px;">
            <span class="tp-muted">ตัวกรองด่วน:</span>
            <template x-for="preset in ['fortune:celtic-admin-ask', 'celtic admin', 'askQuestionAsAdmin', 'FortuneAIService', 'AiApiKeyPool', 'ERROR']" :key="preset">
                <button @click="log.filter = preset; fetchLog()" type="button"
                        class="tp-pill tp-pill-soft" style="cursor:pointer;border:0;font-size:12px;">
                    <span x-text="preset"></span>
                </button>
            </template>
            <button @click="log.filter = ''; fetchLog()" type="button"
                    class="tp-pill" style="cursor:pointer;border:0;font-size:12px;background:rgba(217,83,79,.14);color:#d9534f;">
                <i class="fas fa-xmark" style="margin-right:4px;"></i>ล้าง
            </button>
        </div>

        {{-- สรุปจำนวนบรรทัด/ขนาดไฟล์ --}}
        <div x-show="log.count !== null" class="tp-muted" style="font-size:12px;margin-bottom:10px;">
            แสดง <strong class="tp-num" x-text="log.count"></strong> บรรทัด •
            ขนาดไฟล์ <strong class="tp-num" x-text="formatBytes(log.size_bytes)"></strong>
        </div>

        {{-- log viewer (mono + scroll) — คงลอจิก highlight เดิม --}}
        <div class="tp-inset" x-show="!log.error" style="padding:0;border-radius:12px;overflow:hidden;">
            <pre style="background:var(--deep1);color:#7fd1a8;margin:0;padding:16px;overflow-x:auto;overflow-y:auto;max-height:24rem;font-size:12px;line-height:1.5;font-family:ui-monospace,'SFMono-Regular',Menlo,monospace;"><template x-for="(line, idx) in log.lines" :key="idx"><span :style="{
                color: (line.includes('ERROR') || line.includes('exception')) ? '#ff8a82'
                     : (line.includes('WARNING') || line.includes('warn')) ? '#ffe08a'
                     : (line.includes('INFO')) ? '#86dcff'
                     : '#7fd1a8'
             }" x-text="line + '\n'"></span></template></pre>
        </div>

        {{-- กล่อง error เมื่ออ่านไฟล์ไม่ได้ --}}
        <div x-show="log.error" x-cloak class="tp-inset-sm" style="margin-top:10px;padding:12px 14px;border-radius:10px;background:rgba(217,83,79,.12);color:#d9534f;font-size:13px;">
            <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i><span x-text="log.error"></span>
        </div>

        {{-- ไม่พบ log ตรง filter --}}
        <div x-show="!log.error && log.count === 0" x-cloak class="tp-inset-sm" style="margin-top:10px;padding:12px 14px;border-radius:10px;background:rgba(224,165,46,.12);color:#e0a52e;font-size:13px;">
            <i class="fas fa-magnifying-glass" style="margin-right:6px;"></i>ไม่เจอ log ที่ตรง filter — ลองเปลี่ยน keyword หรือเพิ่มจำนวนบรรทัด
        </div>
    </div>

    {{-- ═══════════════════════════════════
         ส่วนที่ 2 — ทดสอบ AI ทำนาย (sync)
         ═══════════════════════════════════ --}}
    <div class="tp-card" style="border:1px solid var(--a2soft);">
        <div class="tp-section-h" style="margin:0 0 4px;display:flex;align-items:center;gap:9px;">
            <i class="fas fa-flask" style="color:var(--accent1);"></i>
            ทดสอบ AI ทำนาย (sync)
        </div>
        <div class="tp-muted" style="font-size:13px;margin-bottom:18px;">
            เรียก AI ตรงๆ ไม่ผ่าน background — เห็น response/error ในหน้านี้ทันที + log แสดงด้านบน
        </div>

        <div style="display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:16px;" class="tp-debug-test-grid">
            {{-- เลือก reading ที่จะทดสอบ --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                    Reading (เลือก reading ที่จะทดสอบ)
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select x-model.number="test.reading_id"
                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="">— เลือก —</option>
                        @foreach($recentReadings as $r)
                            <option value="{{ $r->id }}">
                                #{{ $r->id }} • {{ $r->reading_type }} • {{ $r->facebook_user_name ?? '-' }} •
                                {{ $r->is_paid ? '✓paid' : 'unpaid' }} • {{ $r->conversation_status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- ตัวเลือกเสริม --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                    Options
                </label>
                <label class="tp-well tp-input" style="display:flex;align-items:center;gap:10px;padding:11px 14px;cursor:pointer;">
                    <input type="checkbox" x-model="test.push_to_customer" style="accent-color:var(--accent1);">
                    <span style="font-size:13px;color:var(--ink);">
                        🚨 ส่งคำตอบให้ลูกค้าจริง (มี prefix <code style="font-family:ui-monospace,monospace;">[DEBUG TEST]</code>)
                    </span>
                </label>
            </div>
        </div>

        {{-- คำถามทดสอบ --}}
        <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
            คำถามทดสอบ
        </label>
        <div class="tp-well tp-input" style="padding:0;margin-bottom:14px;">
            <textarea x-model="test.question" rows="2" maxlength="500"
                      placeholder="ทดสอบคำถาม ✨"
                      style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;resize:vertical;font-family:inherit;"></textarea>
        </div>

        <button @click="runTestAi()" type="button"
                :disabled="!test.reading_id || test.question.trim().length < 3 || test.running"
                class="tp-btn tp-btn-primary">
            <span x-show="!test.running"><i class="fas fa-flask" style="margin-right:6px;"></i>ทดสอบ AI</span>
            <span x-show="test.running" x-cloak><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>กำลังเรียก AI... (30-60s)</span>
        </button>

        {{-- ผลทดสอบ --}}
        <div x-show="test.result" x-cloak style="margin-top:20px;">
            <div class="tp-section-h" style="margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-chart-simple" style="color:var(--accent2);"></i>
                ผลทดสอบ
                <span x-show="test.result?.success" style="color:#5aa07e;"><i class="fas fa-circle-check"></i></span>
                <span x-show="test.result?.success === false" style="color:#d9534f;"><i class="fas fa-circle-xmark"></i></span>
            </div>

            {{-- รายการ step --}}
            <div style="display:flex;flex-direction:column;gap:10px;">
                <template x-for="(step, idx) in test.result?.steps || []" :key="idx">
                    <div class="tp-inset-sm" style="padding:12px 14px;border-radius:10px;border-left:4px solid;"
                         :style="step.success ? 'border-left-color:#5aa07e;background:rgba(90,160,126,.08);' : 'border-left-color:#d9534f;background:rgba(217,83,79,.08);'">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                            <strong style="font-size:13px;color:var(--ink);" x-text="step.name"></strong>
                            <div style="display:flex;align-items:center;gap:8px;font-size:12px;">
                                <span x-show="step.elapsed_ms !== undefined" class="tp-muted">
                                    <span x-text="step.elapsed_ms"></span>ms
                                </span>
                                <span x-show="step.skipped" class="tp-pill tp-pill-soft" style="font-size:11px;">skipped</span>
                            </div>
                        </div>
                        <pre x-show="step" x-text="JSON.stringify(stepDetails(step), null, 2)"
                             style="margin-top:8px;font-size:12px;background:var(--deep1);color:#7fd1a8;padding:10px;border-radius:8px;overflow-x:auto;font-family:ui-monospace,monospace;"></pre>
                    </div>
                </template>
            </div>

            {{-- AI response เต็ม --}}
            <div x-show="test.result?.ai_response_full" style="margin-top:16px;">
                <div style="font-weight:600;font-size:13px;color:var(--ink);margin-bottom:8px;">💬 AI Response (full):</div>
                <pre style="background:var(--deep1);color:#86dcff;padding:12px;border-radius:10px;font-size:12px;white-space:pre-wrap;font-family:ui-monospace,monospace;" x-text="test.result?.ai_response_full"></pre>
            </div>

            {{-- exception --}}
            <div x-show="test.result?.error" style="margin-top:16px;padding:12px 14px;border-radius:10px;background:rgba(217,83,79,.12);color:#d9534f;font-size:13px;">
                <strong><i class="fas fa-circle-xmark" style="margin-right:5px;"></i>Exception:</strong> <span x-text="test.result?.error"></span>
                <pre x-show="test.result?.trace" style="margin-top:8px;font-size:12px;background:var(--deep1);color:#ff8a82;padding:10px;border-radius:8px;overflow-x:auto;font-family:ui-monospace,monospace;" x-text="test.result?.trace"></pre>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════
         ส่วนที่ 3 — สลับแพ็กเกจบิล Deep 39 ↔ Celtic 99
         ═══════════════════════════════════ --}}
    <div class="tp-card" style="border:1px solid var(--a2soft);">
        <div class="tp-section-h" style="margin:0 0 4px;display:flex;align-items:center;gap:9px;">
            <i class="fas fa-right-left" style="color:var(--accent1);"></i>
            สลับแพ็กเกจบิล — Deep 39 ↔ Celtic 99
        </div>
        <div class="tp-muted" style="font-size:13px;margin-bottom:18px;">
            แก้เคสลูกค้าเปิดผิดแพ็กเกจ / จ่ายไม่ตรงราคา — เปลี่ยนใน DB อย่างเดียว
            <strong style="color:var(--accent2);">ไม่ส่งข้อความหาลูกค้า</strong> (แจ้งลูกค้าเอง)
        </div>

        {{-- ค้นหาบิล --}}
        <div style="display:grid;grid-template-columns:1fr auto;gap:10px;margin-bottom:14px;">
            <div class="tp-well tp-input" style="padding:0;">
                <input type="text" x-model="sw.bill" @keydown.enter="loadBill()"
                       placeholder="เลขบิล (เช่น FTU-260703-D2776) หรือ reading id"
                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
            </div>
            <button @click="loadBill()" type="button" :disabled="sw.loading || !sw.bill.trim()"
                    class="tp-btn tp-btn-sm tp-btn-primary">
                <span x-show="!sw.loading"><i class="fas fa-magnifying-glass" style="margin-right:5px;"></i>โหลดบิล</span>
                <span x-show="sw.loading" x-cloak><i class="fas fa-spinner fa-spin"></i></span>
            </button>
        </div>

        {{-- error โหลดบิล --}}
        <div x-show="sw.error" x-cloak class="tp-inset-sm" style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:rgba(217,83,79,.12);color:#d9534f;font-size:13px;">
            <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i><span x-text="sw.error"></span>
        </div>

        {{-- ข้อมูลบิลปัจจุบัน + ฟอร์มสลับ --}}
        <template x-if="sw.reading">
            <div>
                {{-- สถานะปัจจุบัน --}}
                <div class="tp-inset-sm" style="padding:14px;border-radius:10px;margin-bottom:16px;">
                    <div style="display:flex;flex-wrap:wrap;gap:10px 18px;font-size:13px;">
                        <span class="tp-muted">บิล: <strong class="tp-num" style="color:var(--ink);" x-text="sw.reading.bill_reference || ('#' + sw.reading.id)"></strong></span>
                        <span class="tp-muted">แพ็กเกจ: <strong :style="sw.reading.reading_type==='celtic_cross' ? 'color:#c9a227;' : 'color:#5aa07e;'" x-text="sw.reading.reading_type==='celtic_cross' ? 'Celtic 99' : (sw.reading.reading_type==='deep' ? 'Deep 39' : sw.reading.reading_type)"></strong></span>
                        <span class="tp-muted">จ่าย: <strong :style="sw.reading.is_paid ? 'color:#5aa07e;' : 'color:#d9534f;'" x-text="sw.reading.is_paid ? '✓ จ่ายแล้ว' : 'ยังไม่จ่าย'"></strong></span>
                        <span class="tp-muted">เงินรับจริง: <strong class="tp-num" style="color:var(--ink);" x-text="'฿' + Number(sw.reading.money_in).toFixed(2)"></strong></span>
                        <span class="tp-muted">สถานะ: <strong style="color:var(--ink);" x-text="sw.reading.conversation_status"></strong></span>
                        <span class="tp-muted" x-show="sw.reading.celtic_cards>0">ไพ่: <strong class="tp-num" style="color:var(--ink);" x-text="sw.reading.celtic_cards + '/10'"></strong></span>
                        <span x-show="sw.reading.has_deep_response" style="color:#e0a52e;">⚠ มีคำทำนาย Deep แล้ว</span>
                    </div>
                </div>

                {{-- เลือกแพ็กเกจปลายทาง --}}
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:8px;">สลับเป็น</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                    <label class="tp-well tp-input" style="flex:1;min-width:160px;display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;" :style="sw.target==='deep' ? 'border-color:var(--accent1);' : ''">
                        <input type="radio" value="deep" x-model="sw.target" style="accent-color:var(--accent1);">
                        <span style="font-size:14px;color:var(--ink);"><i class="fas fa-gem" style="margin-right:6px;color:#5aa07e;"></i>Deep เจาะลึก (39฿)</span>
                    </label>
                    <label class="tp-well tp-input" style="flex:1;min-width:160px;display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;" :style="sw.target==='celtic_cross' ? 'border-color:var(--accent1);' : ''">
                        <input type="radio" value="celtic_cross" x-model="sw.target" style="accent-color:var(--accent1);">
                        <span style="font-size:14px;color:var(--ink);"><i class="fas fa-wand-sparkles" style="margin-right:6px;color:#c9a227;"></i>Celtic ไพ่ยิปซี (99฿)</span>
                    </label>
                </div>

                {{-- โหมดเก็บเงิน (เมื่อราคาปลายทางแพงกว่ายอดที่จ่าย) --}}
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:8px;">ถ้าราคาปลายทาง "แพงกว่า" ยอดที่ลูกค้าจ่ายมาแล้ว</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
                    <label class="tp-well tp-input" style="flex:1;min-width:190px;display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;">
                        <input type="radio" value="charge" x-model="sw.pay_mode" style="accent-color:var(--accent1);">
                        <span style="font-size:13px;color:var(--ink);">💸 ออกบิลเก็บส่วนต่าง (ลูกค้าโอนเพิ่ม)</span>
                    </label>
                    <label class="tp-well tp-input" style="flex:1;min-width:190px;display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;">
                        <input type="radio" value="free" x-model="sw.pay_mode" style="accent-color:var(--accent1);">
                        <span style="font-size:13px;color:var(--ink);">🎁 อัปเกรดฟรี (ถือว่าจ่ายครบ)</span>
                    </label>
                </div>

                <button @click="doSwitch(false)" type="button"
                        :disabled="sw.running || !sw.target || sw.target===sw.reading.reading_type"
                        class="tp-btn tp-btn-primary">
                    <span x-show="!sw.running"><i class="fas fa-right-left" style="margin-right:6px;"></i>สลับแพ็กเกจ (ไม่แจ้งลูกค้า)</span>
                    <span x-show="sw.running" x-cloak><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>กำลังสลับ...</span>
                </button>
                <div x-show="sw.target && sw.target===sw.reading.reading_type" x-cloak class="tp-muted" style="font-size:12px;margin-top:8px;color:#e0a52e;">
                    <i class="fas fa-circle-info"></i> บิลนี้เป็นแพ็กเกจนี้อยู่แล้ว — เลือกอีกแพ็กเกจ
                </div>

                {{-- needs_confirm (บิลมีความคืบหน้า) --}}
                <div x-show="sw.needsConfirm" x-cloak style="margin-top:16px;padding:14px;border-radius:10px;background:rgba(224,165,46,.12);border:1px solid rgba(224,165,46,.4);">
                    <div style="font-size:13px;color:#b8860b;margin-bottom:10px;"><i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i><span x-text="sw.result?.message"></span></div>
                    <button @click="doSwitch(true)" type="button" :disabled="sw.running"
                            class="tp-btn tp-btn-sm" style="background:#d9534f;color:#fff;">
                        <i class="fas fa-check" style="margin-right:5px;"></i>ยืนยันสลับ (แม้มีความคืบหน้า)
                    </button>
                </div>

                {{-- ผลลัพธ์สำเร็จ --}}
                <div x-show="sw.result && sw.result.ok" x-cloak style="margin-top:18px;">
                    <div class="tp-inset-sm" style="padding:14px;border-radius:10px;border-left:4px solid #5aa07e;background:rgba(90,160,126,.08);">
                        <div style="font-weight:600;font-size:14px;color:var(--ink);margin-bottom:12px;">
                            <i class="fas fa-circle-check" style="color:#5aa07e;margin-right:6px;"></i><span x-text="sw.result.message"></span>
                        </div>
                        {{-- before → after --}}
                        <template x-if="sw.result.before && sw.result.after">
                            <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;font-size:12px;margin-bottom:12px;">
                                <div class="tp-well" style="padding:10px;border-radius:8px;">
                                    <div class="tp-muted" style="margin-bottom:4px;">ก่อน</div>
                                    <div style="color:var(--ink);" x-text="sw.result.before.reading_type + ' • ' + (sw.result.before.is_paid ? 'จ่ายแล้ว' : 'ยังไม่จ่าย')"></div>
                                    <div class="tp-muted" x-text="sw.result.before.conversation_status"></div>
                                </div>
                                <i class="fas fa-arrow-right" style="color:var(--accent1);"></i>
                                <div class="tp-well" style="padding:10px;border-radius:8px;">
                                    <div class="tp-muted" style="margin-bottom:4px;">หลัง</div>
                                    <div style="color:var(--ink);" x-text="sw.result.after.reading_type + ' • ' + (sw.result.after.is_paid ? 'จ่ายแล้ว' : 'ยังไม่จ่าย')"></div>
                                    <div class="tp-muted" x-text="sw.result.after.conversation_status"></div>
                                </div>
                            </div>
                        </template>
                        {{-- ข้อความให้แอดมิน copy ไปแจ้งลูกค้า --}}
                        <template x-if="sw.result.customer_hint">
                            <div>
                                <div class="tp-muted" style="font-size:12px;margin-bottom:6px;">📋 บอกลูกค้า (copy ไปส่งเอง):</div>
                                <div class="tp-well" style="padding:10px 12px;border-radius:8px;display:flex;gap:10px;align-items:flex-start;">
                                    <span style="flex:1;font-size:13px;color:var(--ink);" x-text="sw.result.customer_hint"></span>
                                    <button @click="copyHint(sw.result.customer_hint)" type="button" class="tp-pill tp-pill-soft" style="border:0;cursor:pointer;font-size:12px;white-space:nowrap;"><i class="fas fa-copy"></i> copy</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ผลลัพธ์ error (ไม่ ok และไม่ใช่ needs_confirm) --}}
                <div x-show="sw.result && !sw.result.ok && !sw.needsConfirm" x-cloak style="margin-top:16px;padding:12px 14px;border-radius:10px;background:rgba(217,83,79,.12);color:#d9534f;font-size:13px;">
                    <i class="fas fa-circle-xmark" style="margin-right:5px;"></i><span x-text="sw.result?.message"></span>
                </div>
            </div>
        </template>
    </div>

</div>
@endsection

@push('scripts')
{{-- responsive grid สำหรับแถวควบคุม log + ตารางทดสอบ (scripts stack แทน styles เพื่อกัน push หลุด) --}}
<style>
    @media (min-width: 768px) {
        .tp-debug-controls { grid-template-columns: 3fr 1fr !important; }
        .tp-debug-test-grid { grid-template-columns: 1fr 1fr !important; }
    }
</style>
<script>
/* Alpine component สำหรับ Debug Tools — คงลอจิก/endpoint/payload/interval เดิมเป๊ะ */
function debugTools() {
    return {
        autoRefresh: false,
        refreshTimer: null,
        log: {
            filter: '',
            lines: 100,
            loading: false,
            lines: [],
            count: null,
            size_bytes: 0,
            fetched_at: null,
            error: null,
        },
        test: {
            reading_id: '',
            question: 'ทดสอบ — ความรักของฉันเดือนนี้จะเป็นยังไง',
            push_to_customer: false,
            running: false,
            result: null,
        },
        // 🔀 สลับแพ็กเกจบิล 39 ↔ 99
        sw: {
            // 🔗 (2026-08-07) รับเลขบิลจาก ?bill= — ให้ลิงก์ "เปลี่ยนแพคเกจ" จากหน้าศูนย์รวมบิล
            //    เด้งมาแล้วโหลดบิลให้เลย ไม่ต้องพิมพ์เลขบิลซ้ำ
            bill: @json(request()->query('bill', '')),
            loading: false,
            error: null,
            reading: null,
            target: '',
            pay_mode: 'charge',
            running: false,
            result: null,
            needsConfirm: false,
        },

        init() {
            this.fetchLog();
            this.$watch('autoRefresh', (v) => {
                if (v) this.startAutoRefresh();
                else this.stopAutoRefresh();
            });
            // 🔗 มาจากลิงก์ที่แนบเลขบิลมา → โหลดบิลให้อัตโนมัติ
            if (this.sw.bill && this.sw.bill.trim()) {
                this.loadBill();
            }
        },

        startAutoRefresh() {
            this.refreshTimer = setInterval(() => this.fetchLog(), 3000);
        },
        stopAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        async fetchLog() {
            this.log.loading = true;
            this.log.error = null;
            try {
                const params = new URLSearchParams({
                    lines: this.log.lines,
                    filter: this.log.filter,
                });
                const res = await fetch('{{ route("admin.fortune.debug-tools.logs") }}?' + params, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.log.lines = data.lines;
                    this.log.count = data.count;
                    this.log.size_bytes = data.size_bytes;
                    this.log.fetched_at = data.fetched_at;
                } else {
                    this.log.error = data.message;
                }
            } catch (e) {
                this.log.error = e.message;
            } finally {
                this.log.loading = false;
            }
        },

        async runTestAi() {
            if (!this.test.reading_id || this.test.question.trim().length < 3) return;

            if (this.test.push_to_customer && !confirm('⚠️ ยืนยันส่งคำตอบ AI ให้ลูกค้าจริง?\nลูกค้าจะเห็น [DEBUG TEST] นำหน้า')) {
                return;
            }

            this.test.running = true;
            this.test.result = null;

            try {
                const res = await fetch('{{ route("admin.fortune.debug-tools.test-ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        reading_id: this.test.reading_id,
                        question: this.test.question,
                        push_to_customer: this.test.push_to_customer,
                    }),
                });
                this.test.result = await res.json();
                // Refresh log to show new entries
                setTimeout(() => this.fetchLog(), 500);
            } catch (e) {
                this.test.result = { success: false, error: e.message };
            } finally {
                this.test.running = false;
            }
        },

        stepDetails(step) {
            const { name, success, elapsed_ms, skipped, ...rest } = step;
            return rest;
        },

        /* 🔀 โหลดสถานะบิลก่อนสลับ */
        async loadBill() {
            if (!this.sw.bill.trim()) return;
            this.sw.loading = true;
            this.sw.error = null;
            this.sw.reading = null;
            this.sw.result = null;
            this.sw.needsConfirm = false;
            try {
                const params = new URLSearchParams({ bill: this.sw.bill.trim() });
                const res = await fetch('{{ route("admin.fortune.debug-tools.bill-info") }}?' + params, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.sw.reading = data.reading;
                    // default target = แพ็กเกจตรงข้าม
                    this.sw.target = data.reading.reading_type === 'celtic_cross' ? 'deep'
                        : (data.reading.reading_type === 'deep' ? 'celtic_cross' : '');
                } else {
                    this.sw.error = data.message;
                }
            } catch (e) {
                this.sw.error = e.message;
            } finally {
                this.sw.loading = false;
            }
        },

        /* 🔀 สลับแพ็กเกจ (force=true เมื่อยืนยันแม้บิลมีความคืบหน้า) */
        async doSwitch(force) {
            if (!this.sw.reading || !this.sw.target) return;
            if (this.sw.target === this.sw.reading.reading_type) return;
            if (!force && !confirm('ยืนยันสลับแพ็กเกจบิลนี้?\nระบบจะเปลี่ยนใน DB อย่างเดียว — ไม่ส่งข้อความหาลูกค้า')) return;
            this.sw.running = true;
            this.sw.result = null;
            this.sw.needsConfirm = false;
            try {
                const res = await fetch('{{ route("admin.fortune.debug-tools.switch-package") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        bill: this.sw.bill.trim(),
                        target: this.sw.target,
                        pay_mode: this.sw.pay_mode,
                        force: !!force,
                    }),
                });
                const data = await res.json();
                this.sw.result = data;
                if (data.needs_confirm) {
                    this.sw.needsConfirm = true;
                } else if (data.ok && data.after) {
                    // อัปเดตสถานะในหน้าให้ตรงผลลัพธ์ (ไม่ reload เพื่อคง result ที่แสดง)
                    this.sw.reading.reading_type = data.after.reading_type;
                    this.sw.reading.conversation_status = data.after.conversation_status;
                    this.sw.reading.is_paid = data.after.is_paid;
                    this.sw.reading.amount_paid = data.after.amount_paid;
                    this.sw.reading.partial_paid_total = data.after.partial_paid_total;
                }
            } catch (e) {
                this.sw.result = { ok: false, message: e.message };
            } finally {
                this.sw.running = false;
            }
        },

        /* 📋 copy ข้อความบอกลูกค้า */
        copyHint(text) {
            try { navigator.clipboard.writeText(text); } catch (e) {}
        },

        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
        },
    };
}
</script>
@endpush
