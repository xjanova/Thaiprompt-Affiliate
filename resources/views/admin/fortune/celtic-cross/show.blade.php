{{-- Celtic Cross Reading Detail (admin view) — ธีม V4 นวลทองคำ --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ===== หน้า "Celtic Cross — รายละเอียด reading" ธีม V4 นวลทองคำ ===== --}}
{{-- container หลัก: เว้นระยะแนวตั้งสม่ำเสมอ --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header: ชื่อหน้า + ปุ่ม action ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:14px;">
        <div>
            @if (Route::has('admin.fortune.celtic-cross.index'))
                <a href="{{ route('admin.fortune.celtic-cross.index') }}"
                   style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--accent1); text-decoration:none;">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้ารวม
                </a>
            @endif
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px; margin-top:8px;">
                หลังบ้าน · ระบบดูดวง · Celtic Cross 99฿
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                🔮 Celtic Cross #{{ $reading->id }}
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px; line-height:1.55;">
                Bill:
                <code style="padding:1px 7px; border-radius:6px; background:var(--inset-sm); box-shadow:var(--inset-sm); color:var(--ink);">{{ $reading->bill_reference ?? '-' }}</code>
                &nbsp;•&nbsp; {{ $reading->facebook_user_name ?? '-' }}
                &nbsp;•&nbsp; {{ $reading->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        {{-- ===== กลุ่มปุ่ม action ด้านขวา ===== --}}
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-start;">
            @if ($reading->celtic_summary_image_path)
                <a href="{{ asset('storage/' . $reading->celtic_summary_image_path) }}" target="_blank"
                   class="tp-btn tp-btn-sm">
                    <i class="fas fa-image"></i> เปิดภาพ Spread
                </a>
            @endif

            @if ($reading->is_paid)
                {{-- ⏰ (2026-05-22) เพิ่มเวลา Pro Session — admin ให้ลูกค้าคุยต่อได้นานขึ้น --}}
                @if ($reading->getCelticPickedCount() >= 10)
                    @php
                        // คำนวณเวลาคงเหลือของ Pro Session ปัจจุบัน
                        $_proActive = (bool) $reading->getConversationState('pro_session_active', false);
                        $_proStartedAt = $reading->getConversationState('pro_session_started_at');
                        $_proWindowMin = (int) $reading->getConversationState('pro_session_window_minutes', $settings->celtic_cross_qa_window_minutes ?? 15);
                        $_proRemainingMin = 0;
                        if ($_proActive && ! empty($_proStartedAt)) {
                            try {
                                $_elapsed = (int) \Carbon\Carbon::parse($_proStartedAt)->diffInMinutes(now(), true);
                                $_proRemainingMin = max(0, $_proWindowMin - $_elapsed);
                            } catch (\Throwable $e) {
                                $_proRemainingMin = 0;
                            }
                        }
                        $_proLive = $_proActive && $_proRemainingMin > 0;
                    @endphp
                    <div x-data="{ open: false, minutes: 30, mode: 'add', notify: true, custom: false }" style="display:inline-block;">
                        <button @click="open = true" type="button" class="tp-btn tp-btn-sm"
                                style="color:#5689b8;">
                            <i class="fas fa-clock"></i> เพิ่มเวลา
                            @if ($_proLive)
                                <span style="margin-left:2px; font-size:11px; opacity:.75;">(เหลือ {{ $_proRemainingMin }}m)</span>
                            @else
                                <span style="margin-left:2px; font-size:11px; opacity:.75;">(session ปิด)</span>
                            @endif
                        </button>

                        {{-- Modal: เพิ่มเวลา --}}
                        <div x-show="open" x-cloak
                             style="position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:50; display:flex; align-items:center; justify-content:center; padding:16px;"
                             @click.self="open = false"
                             @keydown.escape.window="open = false">
                            <div class="tp-card" style="max-width:440px; width:100%; padding:24px;">
                                <h3 class="tp-section-h" style="font-size:17px; font-weight:800; margin:0 0 8px;">
                                    <i class="fas fa-clock" style="color:#5689b8;"></i> เพิ่มเวลาคุยกับแม่หมอ
                                </h3>

                                {{-- สถานะ session ปัจจุบัน --}}
                                <div class="tp-inset" style="padding:12px; border-radius:12px; margin-bottom:16px;
                                    @if ($_proLive) box-shadow:inset 0 0 0 1px #5aa07e; @endif">
                                    @if ($_proLive)
                                        <p style="font-size:13px; color:#5aa07e; margin:0; line-height:1.5;">
                                            🟢 <strong>Session กำลังใช้งาน</strong> — เหลือ <strong>{{ $_proRemainingMin }} นาที</strong>
                                            <br><span style="font-size:11px; opacity:.75; color:var(--ink2);">window {{ $_proWindowMin }}m | started {{ \Carbon\Carbon::parse($_proStartedAt)->format('H:i:s') }}</span>
                                        </p>
                                    @else
                                        <p style="font-size:13px; color:var(--ink); margin:0; line-height:1.5;">
                                            ⚫ <strong>Session ปิด/หมดเวลา</strong> — กดเพิ่มเวลาเพื่อ <strong>เปิด session ใหม่</strong>
                                            <br><span style="font-size:11px; opacity:.75; color:var(--ink2);">status: {{ $reading->conversation_status }}</span>
                                        </p>
                                    @endif
                                </div>

                                <form action="{{ route('admin.fortune.celtic-cross.extend', $reading) }}" method="POST">
                                    @csrf

                                    {{-- Preset minutes --}}
                                    <label style="display:block; font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:8px;">
                                        จำนวนนาทีที่จะเพิ่ม
                                    </label>
                                    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:8px;">
                                        @foreach ([10, 30, 60, 120] as $preset)
                                            <button type="button" @click="minutes = {{ $preset }}; custom = false"
                                                    class="tp-btn tp-btn-sm"
                                                    :style="!custom && minutes === {{ $preset }} ? 'background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;' : ''"
                                                    style="justify-content:center;">
                                                +{{ $preset }}m
                                            </button>
                                        @endforeach
                                    </div>
                                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--ink2); cursor:pointer;">
                                            <input type="checkbox" x-model="custom">
                                            <span>กำหนดเอง:</span>
                                        </label>
                                        <div class="tp-well tp-input" style="padding:0; flex:1;">
                                            <input type="number" name="minutes" x-model.number="minutes"
                                                   :disabled="!custom" min="1" max="300" required
                                                   style="width:100%; background:transparent; border:0; outline:0; padding:9px 12px; color:var(--ink); font-size:14px;">
                                        </div>
                                        <span style="font-size:11px; color:var(--ink2);">นาที (1-300)</span>
                                    </div>

                                    {{-- Mode --}}
                                    @if ($_proLive)
                                        <label style="display:block; font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:8px;">
                                            โหมด
                                        </label>
                                        <div style="display:flex; flex-direction:column; gap:4px; margin-bottom:16px;">
                                            <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:8px; padding:8px; border-radius:10px; cursor:pointer;">
                                                <input type="radio" name="mode" value="add" x-model="mode" style="margin-top:2px;">
                                                <div style="font-size:13px;">
                                                    <strong style="color:var(--ink);">เพิ่มต่อเวลาเดิม</strong>
                                                    <p style="font-size:11px; color:var(--ink2); margin:2px 0 0;">เวลาเหลือ {{ $_proRemainingMin }}m → <span x-text="{{ $_proRemainingMin }} + minutes"></span>m</p>
                                                </div>
                                            </label>
                                            <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:8px; padding:8px; border-radius:10px; cursor:pointer;">
                                                <input type="radio" name="mode" value="reset" x-model="mode" style="margin-top:2px;">
                                                <div style="font-size:13px;">
                                                    <strong style="color:var(--ink);">เริ่มเวลาใหม่</strong>
                                                    <p style="font-size:11px; color:var(--ink2); margin:2px 0 0;">รีเซ็ตเวลาเหลือเป็น <span x-text="minutes"></span>m (started=now)</p>
                                                </div>
                                            </label>
                                        </div>
                                    @else
                                        <input type="hidden" name="mode" value="add">
                                        <div class="tp-inset-sm" style="margin-bottom:16px; padding:9px; border-radius:10px; font-size:12px; color:#5689b8;">
                                            🔄 จะ <strong>เปิด Pro Session ใหม่</strong> ให้ลูกค้าคุยกับแม่หมอ <span x-text="minutes"></span> นาที
                                        </div>
                                    @endif

                                    {{-- Notify --}}
                                    <label style="display:flex; align-items:center; gap:8px; margin-bottom:20px; cursor:pointer;">
                                        <input type="checkbox" name="notify" value="1" x-model="notify">
                                        <span style="font-size:13px; color:var(--ink2);">
                                            📤 แจ้งลูกค้าทันที (แม่หมอจะส่งข้อความ "ได้เวลาเพิ่ม")
                                        </span>
                                    </label>

                                    <div style="display:flex; gap:8px;">
                                        <button @click="open = false" type="button" class="tp-btn" style="flex:1; justify-content:center;">
                                            ยกเลิก
                                        </button>
                                        <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center;">
                                            <i class="fas fa-clock"></i> ยืนยัน +<span x-text="minutes"></span>m
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 🔄 (2026-05-16) Restore active chat — เปิด Pro Session กลับให้ลูกค้าคุยต่อ --}}
                @if ($reading->getCelticPickedCount() >= 10)
                    <form action="{{ route('admin.fortune.celtic-cross.restore', $reading) }}" method="POST"
                          onsubmit="return confirm('ยืนยันคืนสถานะ &quot;กำลังดูอยู่&quot; ให้ #{{ $reading->id }}?\n\nลูกค้าจะกลับมาคุยกับแม่หมอต่อได้ตามเวลาที่เหลือ\n(ถ้าหมดเวลาแล้ว ระบบจะ reset window ใหม่)');"
                          style="display:inline;">
                        @csrf
                        <input type="hidden" name="notify" value="1">
                        <button type="submit" class="tp-btn tp-btn-sm" style="color:#5aa07e;">
                            <i class="fas fa-rotate"></i> คืนสถานะกำลังดู + แจ้งลูกค้า
                        </button>
                    </form>
                @endif

                {{-- 🔄 (2026-05-03) Admin reset — ใช้เมื่อ flow ไม่สมบูรณ์ --}}
                <form action="{{ route('admin.fortune.celtic-cross.reset', $reading) }}" method="POST"
                      onsubmit="return confirm('ยืนยัน reset reading #{{ $reading->id }}?\n\nจะล้างไพ่ + Q&A ทั้งหมด แล้วให้ลูกค้าเริ่มเปิดไพ่ใหม่ (ไม่ต้องจ่ายซ้ำ)');"
                      style="display:inline;">
                    @csrf
                    <input type="hidden" name="notify" value="1">
                    <button type="submit" class="tp-btn tp-btn-sm" style="color:#e0a52e;">
                        <i class="fas fa-rotate"></i> Reset + แจ้งลูกค้า
                    </button>
                </form>
            @else
                {{-- 🚀 (2026-05-08) Force Approve — โอนยอดไม่ตรง → admin มาร์คจ่าย + push เริ่มเปิดไพ่ --}}
                <div x-data="{ open: false, amount: '{{ $reading->amount_paid ?? 99 }}' }" style="display:inline-block;">
                    <button @click="open = true" type="button" class="tp-btn tp-btn-sm" style="color:#5aa07e;">
                        <i class="fas fa-rocket"></i> Force Approve (โอนไม่ตรงยอด)
                    </button>

                    {{-- Modal: Force Approve --}}
                    <div x-show="open" x-cloak
                         style="position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:50; display:flex; align-items:center; justify-content:center; padding:16px;"
                         @click.self="open = false">
                        <div class="tp-card" style="max-width:440px; width:100%; padding:24px;">
                            <h3 class="tp-section-h" style="font-size:17px; font-weight:800; margin:0 0 8px;">
                                <i class="fas fa-rocket" style="color:#5aa07e;"></i> Force Approve บิล {{ $reading->bill_reference ?? '#' . $reading->id }}
                            </h3>
                            <p class="tp-muted" style="font-size:13px; margin:0 0 16px; line-height:1.55;">
                                ใช้กรณีลูกค้าโอนยอดไม่ตรง (เช่น 99 แทน 99.37) → SMS app จับคู่ไม่ได้<br>
                                ระบบจะ <strong>มาร์คบิลจ่ายแล้ว</strong> + <strong>ส่งให้ลูกค้าเริ่มเปิดไพ่ทันที</strong>
                            </p>

                            <form action="{{ route('admin.fortune.celtic-cross.force-approve', $reading) }}" method="POST">
                                @csrf
                                <label style="display:block; font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:6px;">
                                    จำนวนเงินที่ลูกค้าโอนจริง (บาท)
                                </label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="number" name="actual_amount" x-model="amount"
                                           step="0.01" min="0.01" max="9999" required
                                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                                </div>
                                <p class="tp-muted" style="font-size:11px; margin:6px 0 0;">
                                    ยอดบิลที่ตั้งไว้: ฿{{ number_format($reading->amount_paid ?? 99, 2) }}
                                </p>

                                <div style="display:flex; gap:8px; margin-top:20px;">
                                    <button @click="open = false" type="button" class="tp-btn" style="flex:1; justify-content:center;">
                                        ยกเลิก
                                    </button>
                                    <button type="submit"
                                            onclick="return confirm('ยืนยัน? ระบบจะมาร์คบิลจ่ายแล้ว + ส่งให้ลูกค้าเริ่มเปิดไพ่ — ย้อนกลับไม่ได้');"
                                            class="tp-btn tp-btn-primary" style="flex:1; justify-content:center;">
                                        <i class="fas fa-rocket"></i> ยืนยัน Force Approve
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- 🗑️ (2026-05-04) Admin cancel — ลบบิลที่ขัดกัน (pending payment ค้าง) — ปลอดภัยถ้ายังไม่จ่าย --}}
                @if ((int) ($reading->celtic_questions_used ?? 0) === 0)
                    <form action="{{ route('admin.fortune.celtic-cross.cancel', $reading) }}" method="POST"
                          onsubmit="return confirm('ยืนยันยกเลิกบิล {{ $reading->bill_reference ?? '#' . $reading->id }}?\n\nจะปลด UPA + ปิด conversation (ปลอดภัยเพราะยังไม่จ่าย)');"
                          style="display:inline;">
                        @csrf
                        <input type="hidden" name="notify" value="1">
                        <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;">
                            <i class="fas fa-trash"></i> ยกเลิกบิล + แจ้งลูกค้า
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- ===== KPI สถานะ reading ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        {{-- สถานะ --}}
        <div class="tp-card" style="padding:18px;">
            <div style="font-size:12px; color:var(--ink2);">สถานะ</div>
            <div class="tp-num" style="font-size:18px; font-weight:800; margin-top:4px;">{{ $reading->conversation_status }}</div>
        </div>
        {{-- ค่าครู --}}
        <div class="tp-card" style="padding:18px;">
            <div style="font-size:12px; color:var(--ink2);">ค่าครู (รับจริง)</div>
            @if ($reading->is_paid)
                @php $recv = $reading->amount_received; $billed = $reading->amount_paid; @endphp
                <div class="tp-num" style="font-size:18px; font-weight:800; margin-top:4px; color:#5aa07e;">
                    ฿{{ number_format((float) ($recv ?? $billed), 2) }} ✓
                </div>
                @if ($recv !== null && abs((float) $recv - (float) $billed) >= 0.01)
                    <div style="font-size:10px; color:var(--ink2); margin-top:2px;">
                        ตั้งบิล ฿{{ number_format((float) $billed, 2) }}
                        @if ((float) $recv > (float) $billed)
                            <span style="color:#e0a52e;">(เกิน ฿{{ number_format((float) $recv - (float) $billed, 2) }})</span>
                        @endif
                    </div>
                @endif
            @else
                <div class="tp-num" style="font-size:18px; font-weight:800; margin-top:4px; color:var(--ink2);">รอ</div>
            @endif
        </div>
        {{-- คำถามใช้ไป --}}
        <div class="tp-card" style="padding:18px;">
            <div style="font-size:12px; color:var(--ink2);">คำถามใช้ไป</div>
            <div class="tp-num" style="font-size:18px; font-weight:800; margin-top:4px; color:var(--accent1);">{{ $reading->celtic_questions_used }}/{{ $maxQuestions > 0 ? $maxQuestions : '∞' }}</div>
        </div>
        {{-- Q1 ตอบเมื่อ --}}
        <div class="tp-card" style="padding:18px;">
            <div style="font-size:12px; color:var(--ink2);">Q1 ตอบเมื่อ</div>
            <div class="tp-num" style="font-size:15px; font-weight:800; margin-top:4px;">
                {{ $reading->celtic_first_answered_at?->format('d/m H:i') ?? '—' }}
            </div>
        </div>
    </div>

    {{-- ===== 🤖 (2026-05-17 Phase 2) Admin Ask AI — AJAX sync (admin เห็นผลใน UI ทันที) ===== --}}
    @if ($reading->is_paid && $reading->getCelticPickedCount() >= 10)
        @php
            $askAiUrl = route('admin.fortune.celtic-cross.ask-ai', $reading);
            $debugToolsUrl = route('admin.fortune.debug-tools.index');
            $totalQuestionsAsked = $reading->celticQuestions()->count();
            $customerCanStillAsk = $reading->canAskMoreCeltic();
        @endphp
        <div x-data="adminAskAi()" class="tp-card" style="padding:24px;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h2 class="tp-section-h" style="font-size:18px; font-weight:800; display:flex; align-items:center; gap:9px; margin:0;">
                        🤖 Admin Ask AI
                        <span class="tp-pill tp-pill-soft" style="font-size:11px; font-weight:500;">
                            sync — เห็นผลใน UI ทันที
                        </span>
                    </h2>
                    <p class="tp-muted" style="font-size:13px; margin:6px 0 0;">
                        แอดมินพิมพ์คำถาม → AI ทำนาย (30-60s) → ส่งให้ลูกค้า + แสดงผลในหน้านี้
                    </p>
                </div>
                <div style="text-align:right; font-size:12px; color:var(--ink2); display:flex; flex-direction:column; gap:4px;">
                    <div>📝 ถามมาแล้ว <strong style="color:var(--accent1);">{{ $totalQuestionsAsked }}</strong> ครั้ง</div>
                    @if (! $customerCanStillAsk)
                        <div style="color:#e0a52e; font-weight:700;">⚠️ ลูกค้าใช้ quota หมด/เลยเวลา (admin ใช้ได้)</div>
                    @endif
                    <a href="{{ $debugToolsUrl }}" target="_blank" style="color:var(--accent1); text-decoration:none;">🐛 เปิด Debug Tools →</a>
                </div>
            </div>

            <label style="display:block; font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:8px;">
                📝 คำถามที่จะส่งให้ AI ทำนาย (สูงสุด 1000 ตัวอักษร)
            </label>
            <div class="tp-well tp-input" style="padding:0;">
                <textarea x-model="question"
                          :disabled="running"
                          rows="3"
                          maxlength="1000"
                          placeholder="เช่น: ความรักของฉันในเดือนนี้จะเป็นอย่างไร?"
                          style="width:100%; background:transparent; border:0; outline:0; padding:12px 14px; color:var(--ink); font-size:14px; resize:none; font-family:inherit;"></textarea>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:10px; flex-wrap:wrap; gap:10px;">
                <span style="font-size:12px; color:var(--ink2);">
                    <span x-text="question.length">0</span>/1000 ตัวอักษร
                </span>
                <button @click="run()" type="button"
                        :disabled="running || question.trim().length < 3"
                        class="tp-btn tp-btn-primary">
                    <span x-show="!running">🚀 ส่งให้ AI ทำนาย + แจ้งลูกค้า</span>
                    <span x-show="running" x-cloak>⏳ AI กำลังทำนาย... <span x-text="elapsedDisplay"></span></span>
                </button>
            </div>

            <p style="font-size:12px; color:#5aa07e; margin:14px 0 0; display:flex; align-items:flex-start; gap:6px; line-height:1.5;">
                <span>✨</span>
                <span>Admin proxy — <strong>ไม่ตัดโควต้าลูกค้า</strong> + AI ใช้บริบทไพ่ 10 ใบ + sync call (admin รอ 30-60s แต่เห็นผลทันที)</span>
            </p>

            {{-- Result panel --}}
            <div x-show="result" x-cloak style="margin-top:20px;">
                <div class="tp-inset"
                     :style="result?.success ? 'box-shadow:inset 0 0 0 1px #5aa07e, var(--inset);' : 'box-shadow:inset 0 0 0 1px #d9534f, var(--inset);'"
                     style="padding:16px; border-radius:12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                        <strong style="font-size:13px;">
                            <span x-show="result?.success" style="color:#5aa07e;">✅ AI ทำนายสำเร็จ</span>
                            <span x-show="result?.success === false" style="color:#d9534f;">❌ ล้มเหลว</span>
                        </strong>
                        <div style="font-size:11px; color:var(--ink2); display:flex; flex-wrap:wrap; gap:12px;">
                            <span x-show="result?.sequence">Q<span x-text="result?.sequence"></span></span>
                            <span x-show="result?.ai_provider"><span x-text="result?.ai_provider"></span> / <span x-text="result?.ai_model"></span></span>
                            <span x-show="result?.tokens_used"><span x-text="result?.tokens_used"></span> tokens</span>
                            <span x-show="result?.elapsed_ms"><span x-text="result?.elapsed_ms"></span>ms</span>
                            <span :style="result?.pushed ? 'color:#5aa07e;' : 'color:#d9534f;'">
                                <span x-text="result?.pushed ? '📤 push ✓' : '📤 push ✗'"></span>
                            </span>
                        </div>
                    </div>

                    <div x-show="result?.message" style="font-size:13px; color:#d9534f; margin-bottom:8px;">
                        <strong>Error:</strong> <span x-text="result?.message"></span>
                    </div>

                    <div x-show="result?.response_full" style="margin-top:8px;">
                        <p style="font-size:11px; font-weight:700; color:var(--ink2); margin:0 0 4px;">💬 คำตอบที่ส่งให้ลูกค้า:</p>
                        <pre class="tp-well" style="font-size:12px; padding:12px; border-radius:10px; white-space:pre-wrap; color:var(--ink); margin:0; font-family:inherit;" x-text="result?.response_full"></pre>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Spread Image ===== --}}
    @if ($reading->celtic_summary_image_path)
        <div class="tp-card" style="padding:24px;">
            <h2 class="tp-section-h" style="font-size:18px; font-weight:800; margin:0 0 16px;">🖼️ Celtic Cross Spread</h2>
            <img src="{{ asset('storage/' . $reading->celtic_summary_image_path) }}"
                 alt="Celtic Cross Spread"
                 style="max-width:100%; margin:0 auto; display:block; border-radius:12px; box-shadow:var(--raise);">
        </div>
    @endif

    {{-- ===== ไพ่ทั้ง 10 ใบ ===== --}}
    <div class="tp-card" style="padding:24px;">
        <h2 class="tp-section-h" style="font-size:18px; font-weight:800; margin:0 0 16px;">🃏 ไพ่ทั้ง 10 ใบ</h2>

        @if (empty($cards))
            <div style="text-align:center; padding:36px 0; color:var(--ink2);">
                <i class="fas fa-inbox" style="font-size:34px; opacity:.5;"></i>
                <p style="margin:10px 0 0; font-size:14px;">ยังไม่ได้เลือกไพ่</p>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
                @for ($pos = 1; $pos <= 10; $pos++)
                    @php $card = $cards[$pos] ?? null; @endphp
                    <div class="tp-inset" style="padding:12px; border-radius:12px;">
                        <p style="font-size:11px; color:var(--accent1); font-weight:700; margin:0 0 6px;">
                            [{{ $pos }}] {{ $positions[$pos]['name'] ?? '?' }}
                        </p>
                        @if ($card)
                            @if (! empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" alt="{{ $card['card_name_th'] ?? '' }}"
                                     style="width:100%; border-radius:8px; margin-bottom:8px; {{ ! empty($card['is_reversed']) ? 'transform:rotate(180deg);' : '' }}">
                            @endif
                            <p style="font-size:13px; font-weight:700; color:var(--ink); margin:0;">{{ $card['card_name_th'] ?? '?' }}</p>
                            <p style="font-size:11px; color:var(--ink2); margin:2px 0 0;">{{ $card['card_name_en'] ?? '' }}</p>
                            <p style="font-size:11px; margin:4px 0 0; color:{{ ! empty($card['is_reversed']) ? '#d9534f' : '#5aa07e' }};">
                                {{ ! empty($card['is_reversed']) ? '⤵ กลับหัว' : '↑ ตั้งตรง' }}
                            </p>
                        @else
                            <p style="font-size:11px; color:var(--ink2); font-style:italic; margin:8px 0 0;">ยังไม่เลือก</p>
                        @endif
                    </div>
                @endfor
            </div>
        @endif
    </div>

    {{-- ===== คำถาม & คำตอบ ===== --}}
    <div class="tp-card" style="padding:24px;">
        <h2 class="tp-section-h" style="font-size:18px; font-weight:800; margin:0 0 16px;">💬 คำถาม &amp; คำตอบ</h2>

        @if ($reading->celticQuestions->isEmpty())
            <div style="text-align:center; padding:36px 0; color:var(--ink2);">
                <i class="fas fa-inbox" style="font-size:34px; opacity:.5;"></i>
                <p style="margin:10px 0 0; font-size:14px;">ยังไม่มีคำถาม</p>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:14px;">
                @foreach ($reading->celticQuestions as $q)
                    <div class="tp-inset" style="padding:16px; border-radius:12px; border-left:4px solid {{ $q->sequence === 1 ? 'var(--accent1)' : '#5689b8' }};">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                            <span class="tp-pill {{ $q->sequence === 1 ? 'tp-pill-gold' : 'tp-pill-soft' }}" style="font-size:11px; font-weight:700;">
                                Q{{ $q->sequence }} {{ $q->sequence === 1 ? '(Main)' : '(Follow-up)' }}
                            </span>
                            <span style="font-size:11px; color:var(--ink2);">
                                {{ $q->answered_at?->format('d/m H:i:s') ?? 'รอ' }}
                                @if ($q->ai_response_time_ms) • {{ round($q->ai_response_time_ms / 1000, 1) }}s @endif
                                @if ($q->ai_tokens_used) • {{ number_format($q->ai_tokens_used) }} tokens @endif
                            </span>
                        </div>

                        <p style="font-size:13px; font-weight:700; color:var(--ink); margin:0 0 8px;">❓ {{ $q->question }}</p>

                        @if ($q->response)
                            <pre class="tp-well" style="font-size:13px; color:var(--ink); white-space:pre-wrap; font-family:inherit; padding:12px; border-radius:10px; margin:8px 0 0;">{{ $q->response }}</pre>
                        @else
                            <p style="font-size:11px; color:#d9534f; font-style:italic; margin:0;">ยังไม่มีคำตอบ (อาจ AI ล้มเหลว)</p>
                        @endif

                        @if ($q->ai_provider)
                            <p style="font-size:11px; color:var(--ink2); margin:8px 0 0;">via {{ $q->ai_provider }} • {{ $q->ai_model }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

{{-- scripts stack: Alpine component สำหรับ Admin Ask AI (คง endpoint/payload/logic เดิม 100%) --}}
@push('scripts')
<script>
function adminAskAi() {
    return {
        question: '',
        running: false,
        result: null,
        startedAt: null,
        elapsedDisplay: '0s',
        timerInterval: null,

        async run() {
            if (this.running || this.question.trim().length < 3) return;
            if (!confirm('ยืนยันส่งคำถามให้ AI ทำนาย?\n\nคำถาม: ' + this.question.substring(0, 200) +
                         '\n\nไม่ตัดโควต้าลูกค้า\nAI สำเร็จ ส่งให้ลูกค้าทาง LINE/FB อัตโนมัติ\nรอ 30-60 วินาที')) {
                return;
            }

            this.running = true;
            this.result = null;
            this.startedAt = Date.now();
            this.elapsedDisplay = '0s';
            this.timerInterval = setInterval(() => {
                const s = Math.floor((Date.now() - this.startedAt) / 1000);
                this.elapsedDisplay = s + 's';
            }, 1000);

            try {
                const res = await fetch('{{ $askAiUrl ?? '' }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ question: this.question }),
                });

                if (!res.ok) {
                    const errBody = await res.json().catch(() => ({ message: 'HTTP ' + res.status }));
                    this.result = {
                        success: false,
                        message: errBody.message || ('HTTP ' + res.status),
                    };
                } else {
                    this.result = await res.json();
                    if (this.result.success) {
                        this.question = '';
                    }
                }
            } catch (e) {
                this.result = {
                    success: false,
                    message: 'Network error: ' + e.message,
                };
            } finally {
                this.running = false;
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                    this.timerInterval = null;
                }
            }
        },
    };
}
</script>
@endpush
