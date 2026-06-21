@extends('layouts.admin-v4')

@section('title', 'SlipOK Account Pool')

@section('content')
{{-- 🪪 หน้า SlipOK Account Pool — ธีม V4 "นวลทองคำ"
     หมุนเวียนหลายบัญชี SlipOK กันโควต้าฟรี (~100/เดือน) ตันทั้งระบบ
     คงฟอร์ม/toggle/AJAX/field เดิม 100% — Alpine slipokPool() อยู่ใน @push('scripts') ตามเดิม --}}
<div style="display:flex;flex-direction:column;gap:18px;" x-data="slipokPool()">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · SlipOK Account Pool
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-id-card" style="color:var(--accent1);"></i> SlipOK Account Pool
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                หมุนเวียนหลายบัญชี SlipOK กันโควต้าฟรี (~100/เดือน) ตันทั้งระบบ
            </p>
        </div>
        <button @click="openAdd()" class="tp-btn tp-btn-primary">
            <i class="fas fa-plus"></i> เพิ่มบัญชี
        </button>
    </div>

    {{-- ===== Validation errors ===== --}}
    {{-- 🔴 layout admin-v4 แปลง session flash → toast ให้อัตโนมัติ แต่ไม่ครอบ $errors (validation)
         จึงคงการแสดง error ของฟอร์ม store/update ไว้เป็น card V4 เพื่อไม่ให้ฟังก์ชันหาย --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:3px solid #d9534f;background:rgba(217,83,79,.06);">
            <div style="display:flex;align-items:center;gap:8px;color:#d9534f;font-weight:600;font-size:14px;margin-bottom:6px;">
                <i class="fas fa-triangle-exclamation"></i> ตรวจสอบข้อมูลอีกครั้ง
            </div>
            @foreach($errors->all() as $e)
                <div style="font-size:13px;color:var(--ink);">• {{ $e }}</div>
            @endforeach
        </div>
    @endif

    {{-- ===== KPI สรุป pool ===== --}}
    @php
        // 🧮 สรุปตัวเลขรวมของ pool (คำนวณฝั่ง server ไม่ใช้ chart lib)
        $totalAccounts = $accounts->count();
        $activeAccounts = $accounts->where('is_active', true)->count();
        $threshold = (int) ($settings->slipok_pool_threshold ?? 10);
        $totalRemaining = $accounts->sum(fn ($a) => $a->remainingQuota());
        $totalQuota = $accounts->sum('monthly_quota');
        $totalUsed = $accounts->sum('used_this_month');
        $usedPct = $totalQuota > 0 ? min(100, round($totalUsed / $totalQuota * 100)) : 0;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        <div class="tp-card tp-tile">
            <div class="tp-muted" style="font-size:12px;">บัญชีทั้งหมด</div>
            <div class="tp-num" style="font-size:30px;line-height:1.1;margin-top:4px;">{{ number_format($totalAccounts) }}</div>
            <div class="tp-muted" style="font-size:11px;margin-top:2px;">
                <i class="fas fa-circle" style="font-size:7px;color:#5aa07e;vertical-align:middle;"></i>
                เปิดใช้ {{ number_format($activeAccounts) }} บัญชี
            </div>
        </div>
        <div class="tp-card tp-tile">
            <div class="tp-muted" style="font-size:12px;">โควต้าคงเหลือรวม</div>
            <div class="tp-num" style="font-size:30px;line-height:1.1;margin-top:4px;color:{{ $totalRemaining <= $threshold ? '#d6824a' : '#5aa07e' }};">
                {{ number_format($totalRemaining) }}
            </div>
            <div class="tp-muted" style="font-size:11px;margin-top:2px;">จาก {{ number_format($totalQuota) }} โควต้า/เดือน</div>
        </div>
        <div class="tp-card tp-tile">
            <div class="tp-muted" style="font-size:12px;">ใช้ไปเดือนนี้</div>
            <div class="tp-num" style="font-size:30px;line-height:1.1;margin-top:4px;">{{ number_format($totalUsed) }}</div>
            {{-- 📊 progress bar = CSS ล้วน (ห้าม Chart.js) --}}
            <div class="tp-inset-sm" style="height:8px;border-radius:99px;margin-top:8px;overflow:hidden;">
                <div style="height:100%;width:{{ $usedPct }}%;border-radius:99px;background:linear-gradient(90deg,var(--accent1),var(--accent2));"></div>
            </div>
            <div class="tp-muted" style="font-size:11px;margin-top:4px;">ใช้ไป {{ $usedPct }}% ของโควต้ารวม</div>
        </div>
        <div class="tp-card tp-tile">
            <div class="tp-muted" style="font-size:12px;">โหมดปัจจุบัน</div>
            <div class="tp-num" style="font-size:18px;line-height:1.25;margin-top:6px;">
                {{ $modes[$settings->slipok_pool_mode ?? 'near_empty'] ?? ($settings->slipok_pool_mode ?? '—') }}
            </div>
            <div style="margin-top:8px;">
                @if($settings->slipok_pool_enabled ?? false)
                    <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;">
                        <i class="fas fa-circle-check"></i> เปิดใช้ Pool
                    </span>
                @else
                    <span class="tp-pill tp-pill-soft" style="color:var(--ink2);">
                        <i class="fas fa-circle-pause"></i> ปิด Pool
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== Pool mode settings ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <i class="fas fa-sliders" style="color:var(--accent1);"></i>
            <span>โหมดการหมุนบัญชี</span>
        </div>

        <form method="POST" action="{{ route('admin.fortune.slipok-accounts.update-mode') }}"
              style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;align-items:end;">
            @csrf
            @method('PUT')

            {{-- เปิด/ปิด Pool --}}
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="slipok_pool_enabled" value="1"
                       {{ ($settings->slipok_pool_enabled ?? false) ? 'checked' : '' }}
                       style="width:20px;height:20px;accent-color:var(--accent1);cursor:pointer;">
                <span style="font-size:14px;font-weight:600;color:var(--ink);">เปิดใช้ Pool</span>
            </label>

            {{-- โหมด --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">โหมด</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="slipok_pool_mode"
                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        @foreach($modes as $key => $label)
                            <option value="{{ $key }}" {{ ($settings->slipok_pool_mode ?? 'near_empty') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- เกณฑ์ใกล้หมด --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">เกณฑ์ใกล้หมด (near-empty)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="number" name="slipok_pool_threshold" min="1"
                           value="{{ $settings->slipok_pool_threshold ?? 10 }}"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>

            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึกโหมด
            </button>
        </form>

        <div class="tp-divider" style="margin:16px 0;"></div>

        {{-- คำอธิบายโหมด --}}
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <span class="tp-pill tp-pill-soft" style="font-size:12px;">
                <i class="fas fa-rotate" style="color:var(--accent1);"></i>
                <b>near-empty</b> · เหลือ &lt; เกณฑ์ → สลับถัดไป
            </span>
            <span class="tp-pill tp-pill-soft" style="font-size:12px;">
                <i class="fas fa-life-ring" style="color:#5689b8;"></i>
                <b>failover</b> · ใช้จนหมด/พังค่อยสลับ
            </span>
            <span class="tp-pill tp-pill-soft" style="font-size:12px;">
                <i class="fas fa-scale-balanced" style="color:#5aa07e;"></i>
                <b>balance</b> · เฉลี่ย เลือกตัวเหลือมากสุด
            </span>
        </div>
        <p class="tp-muted" style="font-size:12px;margin:10px 0 0;">
            ทุกโหมดมี <b style="color:var(--ink);">auto-failover</b> เมื่อ API หมดโควต้า/คีย์พังในระหว่างยิง
        </p>
    </div>

    {{-- ===== Accounts table ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="padding:18px 20px;" class="tp-section-h">
            <i class="fas fa-layer-group" style="color:var(--accent1);"></i>
            <span style="margin-left:8px;">บัญชีใน Pool</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:separate;border-spacing:0 8px;font-size:13px;min-width:760px;padding:0 12px;">
                <thead>
                    <tr style="color:var(--ink2);text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">
                        <th style="padding:6px 12px;">#</th>
                        <th style="padding:6px 12px;">บัญชี</th>
                        <th style="padding:6px 12px;">Branch ID</th>
                        <th style="padding:6px 12px;">API Key</th>
                        <th style="padding:6px 12px;text-align:center;">ใช้/โควต้า</th>
                        <th style="padding:6px 12px;text-align:center;">คงเหลือ</th>
                        <th style="padding:6px 12px;text-align:center;">สถานะ</th>
                        <th style="padding:6px 12px;text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $a)
                        @php
                            $remaining = $a->remainingQuota();
                            $isExhausted = $a->isExhausted();
                        @endphp
                        {{-- 🟡 แต่ละแถวมีปุ่ม @click → ต้องมี x-data เอง (Alpine v3 init เฉพาะ subtree) — ใช้ root x-data ผ่าน slipokPool() ที่ครอบทั้งหน้าอยู่แล้ว --}}
                        <tr style="box-shadow:var(--inset-sm);border-radius:14px;">
                            <td style="padding:12px;color:var(--ink2);border-radius:14px 0 0 14px;">{{ $a->priority }}</td>
                            <td style="padding:12px;">
                                <div style="font-weight:600;color:var(--ink);">{{ $a->label ?: '—' }}</div>
                                @if($a->last_error)
                                    <div style="font-size:11px;color:#d9534f;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $a->last_error }}">
                                        <i class="fas fa-triangle-exclamation"></i> {{ $a->last_error }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding:12px;font-family:monospace;font-size:12px;color:var(--ink);">{{ $a->branch_id }}</td>
                            <td style="padding:12px;font-family:monospace;font-size:12px;color:var(--ink2);">{{ $a->masked_key }}</td>
                            <td style="padding:12px;text-align:center;color:var(--ink);" class="tp-num">
                                {{ $a->used_this_month }} / {{ $a->monthly_quota }}
                            </td>
                            <td style="padding:12px;text-align:center;">
                                <span class="tp-num" style="font-weight:700;color:{{ $remaining <= $threshold ? '#d6824a' : '#5aa07e' }};">
                                    {{ $remaining }}
                                </span>
                            </td>
                            <td style="padding:12px;text-align:center;">
                                @if(!$a->is_active)
                                    <span class="tp-pill tp-pill-soft" style="color:var(--ink2);">
                                        <i class="fas fa-circle-stop"></i> ปิด
                                    </span>
                                @elseif($isExhausted)
                                    <span class="tp-pill" style="background:rgba(214,130,74,.16);color:#d6824a;">
                                        <i class="fas fa-hourglass-half"></i> พัก
                                    </span>
                                @else
                                    <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;">
                                        <i class="fas fa-circle-check"></i> พร้อม
                                    </span>
                                @endif
                            </td>
                            <td style="padding:12px;border-radius:0 14px 14px 0;">
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap;">
                                    {{-- เทสการเชื่อมต่อ (AJAX เดิม) --}}
                                    <button type="button" @click='testAccount({{ $a->id }})'
                                            class="tp-icon-btn" title="ทดสอบการเชื่อมต่อ"
                                            style="color:#5689b8;">
                                        <span x-show="testing !== {{ $a->id }}"><i class="fas fa-plug-circle-bolt"></i></span>
                                        <span x-show="testing === {{ $a->id }}"><i class="fas fa-spinner fa-spin"></i></span>
                                    </button>

                                    {{-- 🔧 (2026-06-09) ใช้ double-quote ครอบ @click — Js::from() output = JSON.parse('...')
                                         มี single-quote ข้างใน ถ้าครอบด้วย single-quote attribute จะตัดจบเร็ว → @click พัง → modal ไม่เปิด --}}
                                    <button type="button"
                                            @click="openEdit({{ Js::from([
                                                'id' => $a->id,
                                                'label' => $a->label,
                                                'branch_id' => $a->branch_id,
                                                'priority' => $a->priority,
                                                'monthly_quota' => $a->monthly_quota,
                                                'notes' => $a->notes,
                                            ]) }})"
                                            class="tp-icon-btn" title="แก้ไข"
                                            style="color:var(--accent2);">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    {{-- เปิด/ปิดบัญชี --}}
                                    <form method="POST" action="{{ route('admin.fortune.slipok-accounts.toggle', $a->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tp-icon-btn" style="color:var(--ink2);"
                                                title="{{ $a->is_active ? 'ปิดบัญชี' : 'เปิดบัญชี' }}">
                                            <i class="fas {{ $a->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                        </button>
                                    </form>

                                    {{-- ลบบัญชี --}}
                                    <form method="POST" action="{{ route('admin.fortune.slipok-accounts.destroy', $a->id) }}" style="display:inline;"
                                          onsubmit="return confirm('ลบบัญชีนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" style="color:#d9534f;" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px 16px;text-align:center;">
                                <i class="fas fa-inbox" style="font-size:34px;color:var(--ink2);opacity:.5;"></i>
                                <div class="tp-muted" style="margin-top:10px;font-size:13px;">
                                    ยังไม่มีบัญชีใน pool — กด "เพิ่มบัญชี" เพื่อเริ่ม
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Add / Edit modal ===== --}}
    <div x-show="showModal" x-cloak
         style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(20,16,10,.55);backdrop-filter:blur(3px);padding:16px;"
         @click.self="showModal = false">
        <div class="tp-card tp-raise" style="width:100%;max-width:460px;">
            <h3 class="tp-num" style="font-size:18px;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-id-card" style="color:var(--accent1);"></i>
                <span x-text="form.id ? 'แก้ไขบัญชี SlipOK' : 'เพิ่มบัญชี SlipOK'"></span>
            </h3>

            <form :action="form.id
                    ? '{{ url('admin/fortune/slipok-accounts') }}/' + form.id
                    : '{{ route('admin.fortune.slipok-accounts.store') }}'"
                  method="POST" style="display:flex;flex-direction:column;gap:14px;">
                @csrf
                {{-- 🔧 (2026-06-09) method spoof แบบ always-present + bound — เชื่อถือได้กว่า <template x-if>
                     (x-if บางครั้ง inject ไม่ทัน → POST /{account} ไม่มี route → 405 → แก้ไขไม่บันทึก) --}}
                <input type="hidden" name="_method" x-bind:value="form.id ? 'PUT' : 'POST'">

                {{-- ชื่อเรียก --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">ชื่อเรียก (label)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="label" x-model="form.label"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                {{-- Branch ID --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">
                        Branch ID <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="branch_id" x-model="form.branch_id" required
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;font-family:monospace;">
                    </div>
                </div>

                {{-- API Key --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">
                        API Key <span x-show="!form.id" style="color:#d9534f;">*</span>
                        <span x-show="form.id" style="color:var(--ink2);">(เว้นว่าง = ไม่เปลี่ยน)</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="api_key" :required="!form.id" autocomplete="off"
                               placeholder="SLIPOKXXXXXXX"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;font-family:monospace;">
                    </div>
                </div>

                {{-- priority + quota --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">ลำดับ (priority)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="number" name="priority" x-model="form.priority" min="0"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">โควต้า/เดือน</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="number" name="monthly_quota" x-model="form.monthly_quota" min="1"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                    </div>
                </div>

                {{-- หมายเหตุ --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">หมายเหตุ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="notes" x-model="form.notes"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:6px;">
                    <button type="button" @click="showModal = false" class="tp-btn">ยกเลิก</button>
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="fas fa-floppy-disk"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- 🔧 (2026-06-09) Alpine component อยู่ใน @push('scripts') (โหลดก่อน Alpine init)
     — ตรงกับ pattern ที่ใช้ได้จริงในหน้าอื่น (saved-questions ฯลฯ) --}}
@push('scripts')
<script>
function slipokPool() {
    return {
        showModal: false,
        testing: null,
        form: { id: null, label: '', branch_id: '', priority: 100, monthly_quota: 100, notes: '' },
        openAdd() {
            this.form = { id: null, label: '', branch_id: '', priority: 100, monthly_quota: 100, notes: '' };
            this.showModal = true;
        },
        openEdit(data) {
            this.form = {
                id: data.id,
                label: data.label || '',
                branch_id: data.branch_id || '',
                priority: data.priority ?? 100,
                monthly_quota: data.monthly_quota ?? 100,
                notes: data.notes || '',
            };
            this.showModal = true;
        },
        async testAccount(id) {
            this.testing = id;
            try {
                const res = await fetch('{{ url('admin/fortune/slipok-accounts') }}/' + id + '/test', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ เชื่อมต่อสำเร็จ\nโควต้าคงเหลือ: ' + (data.quota ?? '?') +
                          (data.specialQuota ? ' (+พิเศษ ' + data.specialQuota + ')' : '') +
                          (data.endDate ? '\nหมดอายุ: ' + data.endDate : ''));
                    window.location.reload();
                } else {
                    alert('❌ ' + (data.message || 'เชื่อมต่อไม่สำเร็จ'));
                }
            } catch (e) {
                alert('❌ ทดสอบไม่สำเร็จ: ' + e.message);
            } finally {
                this.testing = null;
            }
        },
    };
}
</script>
@endpush
