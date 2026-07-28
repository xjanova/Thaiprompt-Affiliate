@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'ตั้งค่าน้อง Eve')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: ตั้งค่าน้อง Eve (ผู้ช่วย AI ประจำเว็บ)
     แอดมินปรับได้เองครบ: บุคลิก · ชื่อ · ทักทาย · เปิด/ปิดรายพื้นที่ ·
     โควตา · AI (provider/model/คีย์) + ปุ่มทดสอบคุยจริงก่อนบันทึก
     ════════════════════════════════════════════════════════════ --}}
<div class="space-y-5" x-data="eveSettings()">

    {{-- หัวเรื่อง --}}
    <div class="tp-card" style="padding:18px 20px;">
        <h1 style="font-size:1.35rem;font-weight:800;margin:0;">🤖 ตั้งค่าน้อง Eve</h1>
        <p class="tp-muted" style="margin:6px 0 0;font-size:.85rem;line-height:1.6;">
            ผู้ช่วย AI หน้าเว็บ — ปรับบุคลิก โมเดล AI และขอบเขตการทำงานได้จากหน้านี้
            การตั้งค่ามีผลภายใน ~1 นาที (ไม่ต้อง deploy ใหม่)
        </p>
    </div>

    @if (session('success'))
        <div class="tp-card" style="padding:12px 16px;border-left:4px solid #2e8b57;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="tp-card" style="padding:12px 16px;border-left:4px solid #c0392b;">
            ❌ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.eve.settings.update') }}" class="space-y-5">
        @csrf

        {{-- ── ตัวตนและบุคลิก ── --}}
        <div class="tp-card" style="padding:18px 20px;">
            <h2 style="font-size:1.05rem;font-weight:800;margin:0 0 14px;">🎭 ตัวตนและบุคลิก</h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
                <label style="display:block;">
                    <span class="tp-muted" style="font-size:.8rem;">ชื่อผู้ช่วย</span>
                    <input type="text" name="assistant_name" x-model="form.assistant_name" maxlength="40"
                           class="tp-input" style="width:100%;margin-top:4px;" required>
                </label>

                <label style="display:block;">
                    <span class="tp-muted" style="font-size:.8rem;">บุคลิก</span>
                    <select name="personality" x-model="form.personality" class="tp-input" style="width:100%;margin-top:4px;">
                        @foreach ($personalities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label style="display:block;margin-top:14px;">
                <span class="tp-muted" style="font-size:.8rem;">ข้อความทักทายตอนเปิดแชท (เว้นว่าง = ใช้ข้อความมาตรฐาน)</span>
                <input type="text" name="greeting" value="{{ $config['greeting'] }}" maxlength="300"
                       class="tp-input" style="width:100%;margin-top:4px;"
                       placeholder="เช่น สวัสดีค่า น้อง Eve เองน้า วันนี้อยากได้อะไรบอกได้เลยค่ะ 😉">
            </label>

            <label style="display:block;margin-top:14px;">
                <span class="tp-muted" style="font-size:.8rem;">
                    นโยบายเพิ่มเติม (ต่อท้าย system prompt — เช่น "ห้ามพูดถึงร้านคู่แข่ง", "เน้นเชียร์สินค้าสายมู")
                </span>
                <textarea name="extra_prompt" x-model="form.extra_prompt" rows="3" maxlength="1500"
                          class="tp-input" style="width:100%;margin-top:4px;">{{ $config['extra_prompt'] }}</textarea>
            </label>
        </div>

        {{-- ── AI ── --}}
        <div class="tp-card" style="padding:18px 20px;">
            <h2 style="font-size:1.05rem;font-weight:800;margin:0 0 6px;">🧠 โมเดล AI</h2>
            <p class="tp-muted" style="font-size:.8rem;margin:0 0 14px;line-height:1.6;">
                ค่าแนะนำ: Gemini + เว้นโมเดล/คีย์ว่างไว้ = ใช้คีย์ฟรีในพูลอัตโนมัติ
                (พูลตอนนี้:
                @forelse ($poolSummary as $prov => $cnt)
                    <strong>{{ $prov }}</strong> {{ $cnt }} คีย์@if(!$loop->last) · @endif
                @empty
                    ยังไม่มีคีย์ในพูล — ต้องใส่คีย์เองด้านล่าง
                @endforelse)
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
                <label style="display:block;">
                    <span class="tp-muted" style="font-size:.8rem;">ผู้ให้บริการ</span>
                    <select name="ai_provider" x-model="form.ai_provider" class="tp-input" style="width:100%;margin-top:4px;">
                        <option value="gemini">Gemini (แนะนำ — มีโควตาฟรี)</option>
                        <option value="openai">OpenAI</option>
                        <option value="groq">Groq</option>
                        <option value="anthropic">Anthropic</option>
                    </select>
                </label>

                <label style="display:block;">
                    <span class="tp-muted" style="font-size:.8rem;">โมเดล (เว้นว่าง = พูลเลือกอัตโนมัติ)</span>
                    <input type="text" name="ai_model" x-model="form.ai_model" maxlength="80"
                           class="tp-input" style="width:100%;margin-top:4px;" placeholder="เช่น gemini-3.1-flash-lite">
                </label>

                <label style="display:block;">
                    <span class="tp-muted" style="font-size:.8rem;">
                        API Key ส่วนตัว
                        @if ($config['ai_api_key'] !== '')
                            (ตั้งไว้แล้ว: {{ mb_substr($config['ai_api_key'], 0, 6) }}••••• — เว้นว่าง=คงเดิม, พิมพ์ "-"=ล้างทิ้ง)
                        @else
                            (เว้นว่าง = ใช้พูลอัตโนมัติ)
                        @endif
                    </span>
                    <input type="password" name="ai_api_key" x-model="form.ai_api_key" maxlength="200"
                           autocomplete="new-password" class="tp-input" style="width:100%;margin-top:4px;">
                </label>
            </div>

            {{-- 🧪 ทดสอบคุยด้วยค่าในฟอร์ม ก่อนบันทึก --}}
            <div style="margin-top:16px;padding:14px;border:1px dashed var(--line,#d9cfc0);border-radius:12px;">
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    <input type="text" x-model="testMessage" maxlength="300" class="tp-input" style="flex:1;min-width:220px;"
                           placeholder="พิมพ์ข้อความทดสอบ เช่น สวัสดี วันนี้มีโปรอะไรบ้าง">
                    <button type="button" class="tp-btn" @click="runTest()" :disabled="testing">
                        <i class="fas" :class="testing ? 'fa-spinner fa-spin' : 'fa-vial'"></i>
                        <span x-text="testing ? 'กำลังทดสอบ...' : 'ทดสอบคุยจริง'"></span>
                    </button>
                </div>
                <template x-if="testResult">
                    <div style="margin-top:10px;font-size:.85rem;line-height:1.7;">
                        <div :style="testResult.success ? 'color:#2e8b57' : 'color:#c0392b'" x-text="testResult.reply"></div>
                        <div class="tp-muted" style="font-size:.75rem;margin-top:4px;"
                             x-text="'ใช้ ' + testResult.provider + ' / ' + testResult.model + ' · ' + testResult.ms + 'ms'"></div>
                    </div>
                </template>
                <p class="tp-muted" style="font-size:.72rem;margin:8px 0 0;">
                    ⚠️ ถ้าเปลี่ยนโมเดล ต้องกดทดสอบให้ผ่านก่อนบันทึกเสมอ — โมเดลผิด = Eve ตอบไม่ได้ทั้งเว็บ
                </p>
            </div>
        </div>

        {{-- ── เปิด/ปิดรายพื้นที่ ── --}}
        <div class="tp-card" style="padding:18px 20px;">
            <h2 style="font-size:1.05rem;font-weight:800;margin:0 0 14px;">📍 เปิดใช้งานรายพื้นที่</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
                @foreach ([
                    'enabled_storefront' => '🏪 หน้าร้าน (ลูกค้า/ผู้เยี่ยมชม)',
                    'enabled_user' => '👤 หน้าสมาชิก',
                    'enabled_seller' => '👔 หน้าผู้ขาย',
                    'enabled_admin' => '🛠️ หลังบ้านแอดมิน',
                ] as $field => $label)
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked($config[$field])>
                        <span style="font-size:.9rem;">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ── โควตา ── --}}
        <div class="tp-card" style="padding:18px 20px;">
            <h2 style="font-size:1.05rem;font-weight:800;margin:0 0 6px;">🎟️ โควตาข้อความต่อวัน</h2>
            <p class="tp-muted" style="font-size:.8rem;margin:0 0 14px;">
                กันคีย์ AI ถูกเผา — Eve ใช้พูลก้อนเดียวกับระบบดูดวงที่ลูกค้าจ่ายเงิน
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;">
                @foreach ([
                    'quota_guest' => 'ผู้เยี่ยมชม (ต่อ IP)',
                    'quota_customer' => 'สมาชิก',
                    'quota_seller' => 'ผู้ขาย',
                    'quota_admin' => 'แอดมิน',
                ] as $field => $label)
                    <label style="display:block;">
                        <span class="tp-muted" style="font-size:.8rem;">{{ $label }}</span>
                        <input type="number" name="{{ $field }}" value="{{ $config[$field] }}" min="1" max="10000"
                               class="tp-input" style="width:100%;margin-top:4px;" required>
                    </label>
                @endforeach
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="submit" class="tp-btn" style="font-weight:700;">
                <i class="fas fa-save"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function eveSettings() {
    return {
        // ค่าที่ผูกกับฟอร์ม — ใช้ยิงทดสอบ "ก่อนบันทึก" ได้เลย
        form: {
            assistant_name: {{ Js::from($config['assistant_name']) }},
            personality: {{ Js::from($config['personality']) }},
            ai_provider: {{ Js::from($config['ai_provider']) }},
            ai_model: {{ Js::from($config['ai_model']) }},
            ai_api_key: '',
            extra_prompt: {{ Js::from($config['extra_prompt']) }},
        },
        testMessage: 'สวัสดีค่ะ แนะนำตัวหน่อย',
        testing: false,
        testResult: null,

        async runTest() {
            if (this.testing) return;
            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch({{ Js::from(route('admin.eve.settings.test')) }}, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ message: this.testMessage, ...this.form }),
                });
                this.testResult = await res.json();
            } catch (e) {
                this.testResult = { success: false, reply: '❌ เชื่อมต่อไม่ได้: ' + e.message, provider: '-', model: '-', ms: 0 };
            } finally {
                this.testing = false;
            }
        },
    };
}
</script>
@endpush
