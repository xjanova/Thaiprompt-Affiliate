@extends('layouts.admin-v4')

@section('title', 'นโยบาย SLA')

@section('content')
{{--
    ⏱️ นโยบาย SLA (ธีม V4 นวลทองคำ)

    ปรับปรุงเพิ่มจากของเดิม:
    - ปุ่มแก้ไขเดิมแค่ alert("will be implemented") ทั้งที่ route PUT มีอยู่จริง
      → ต่อสายให้ใช้งานได้จริง (เติมค่าเดิมลงฟอร์ม + ส่ง PUT ไป /{id})
--}}
@php
    $polList = collect($policies ?? []);
    $polPayload = $polList->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'description' => $p->description,
        'category_id' => $p->category_id,
        'priority' => $p->priority,
        'first_response_time' => $p->first_response_time,
        'resolution_time' => $p->resolution_time,
        'business_hours_only' => (bool) $p->business_hours_only,
    ])->values();

    $prioColors = ['critical' => '#d9534f', 'high' => '#d6824a', 'medium' => '#e0a52e', 'low' => '#5689b8'];
    $prioLabels = ['critical' => 'วิกฤต', 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ'];
@endphp

<div x-data="slaPolicies()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · SLA</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">นโยบาย SLA ⏱️</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">กำหนดเวลาตอบกลับและเวลาปิดงานที่รับประกัน</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="openCreate()">
                <i class="fas fa-plus"></i> เพิ่มนโยบาย
            </button>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    @php
        $kpis = [
            [$polList->count(),                            'นโยบายทั้งหมด',       'fa-file-contract', null],
            [$polList->where('is_active', true)->count(),  'เปิดใช้งาน',           'fa-circle-check',  '#5aa07e'],
            [$polList->where('business_hours_only', true)->count(), 'เฉพาะเวลาทำการ', 'fa-business-time', '#e0a52e'],
            [$polList->avg('first_response_time') ? round($polList->avg('first_response_time')) : 0, 'ตอบกลับเฉลี่ย (นาที)', 'fa-hourglass-start', '#5689b8'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($kpis as [$value, $label, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;{{ $color ? ' background:'.$color.';' : '' }}">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        @foreach(['ชื่อนโยบาย','หมวดหมู่','ความสำคัญ','ตอบกลับแรก','ปิดงาน','เวลาทำการ','สถานะ'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($polList as $policy)
                        @php $pc = $prioColors[$policy->priority] ?? '#9a8f7c'; @endphp
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- ชื่อ --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:13.5px; font-weight:700; color:var(--ink);">{{ $policy->name }}</div>
                                @if($policy->description)
                                    <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ Str::limit($policy->description, 50) }}</div>
                                @endif
                            </td>
                            {{-- หมวดหมู่ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill tp-pill-soft"><i class="fas fa-folder"></i> {{ $policy->category->name ?? 'ทุกหมวดหมู่' }}</span>
                            </td>
                            {{-- ความสำคัญ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($policy->priority)
                                    <span class="tp-pill" style="background:color-mix(in srgb, {{ $pc }} 18%, transparent); color:{{ $pc }}; font-weight:700;">
                                        <i class="fas fa-flag"></i> {{ $prioLabels[$policy->priority] ?? ucfirst($policy->priority) }}
                                    </span>
                                @else
                                    <span class="tp-pill tp-pill-soft">ทุกระดับ</span>
                                @endif
                            </td>
                            {{-- ตอบกลับแรก --}}
                            <td style="padding:14px 16px; white-space:nowrap; font-size:13.5px;">
                                <i class="fas fa-hourglass-start" style="color:#5689b8; margin-right:6px;"></i>
                                <strong style="color:var(--ink);">{{ number_format($policy->first_response_time) }}</strong>
                                <span style="color:var(--ink2); font-size:12px;"> นาที</span>
                            </td>
                            {{-- ปิดงาน --}}
                            <td style="padding:14px 16px; white-space:nowrap; font-size:13.5px;">
                                <i class="fas fa-hourglass-end" style="color:#5aa07e; margin-right:6px;"></i>
                                <strong style="color:var(--ink);">{{ number_format($policy->resolution_time) }}</strong>
                                <span style="color:var(--ink2); font-size:12px;"> นาที</span>
                            </td>
                            {{-- เวลาทำการ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($policy->business_hours_only)
                                    <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;"><i class="fas fa-business-time"></i> เฉพาะเวลาทำการ</span>
                                @else
                                    <span class="tp-pill tp-pill-soft">ตลอด 24 ชม.</span>
                                @endif
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($policy->is_active ?? true)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">● เปิดใช้งาน</span>
                                @else
                                    <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 18%, transparent); color:var(--ink2);">● ปิดใช้งาน</span>
                                @endif
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:7px;">
                                    <button type="button" class="tp-btn tp-btn-sm" title="แก้ไข" @click="openEdit({{ $policy->id }})">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.tickets.sla-policies.destroy', $policy->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ลบนโยบาย &quot;{{ $policy->name }}&quot; ใช่หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:0;">
                                <div style="text-align:center; color:var(--ink2); padding:44px 0;">
                                    <i class="fas fa-file-contract" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    <div style="font-size:14px; font-weight:600;">ยังไม่มีนโยบาย SLA</div>
                                    <div style="font-size:12px; margin-top:4px;">สร้างนโยบายแรกเพื่อกำหนดเวลาตอบกลับที่รับประกัน</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== โมดัลสร้าง/แก้ไข ===== --}}
    <div x-show="open" x-cloak style="position:fixed; inset:0; z-index:50; overflow-y:auto;">
        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
            <div style="position:fixed; inset:0; background:rgba(0,0,0,.55);" @click="open = false"></div>

            <div class="tp-card" style="position:relative; max-width:620px; width:100%; padding:0; overflow:hidden;">
                <form :action="formAction" method="POST">
                    @csrf
                    <template x-if="editingId">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div style="padding:18px 20px; background:linear-gradient(120deg, color-mix(in srgb, #d9534f 22%, transparent), transparent 75%); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div class="tp-section-h" style="margin:0;">
                            <i class="fas fa-clock"></i>
                            <span x-text="editingId ? 'แก้ไขนโยบาย SLA' : 'เพิ่มนโยบาย SLA'"></span>
                        </div>
                        <button type="button" class="tp-icon-btn" @click="open = false" title="ปิด"><i class="fas fa-times"></i></button>
                    </div>

                    <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                ชื่อนโยบาย <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" name="name" x-model="form.name" required
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            </div>
                        </div>

                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">คำอธิบาย</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <textarea name="description" x-model="form.description" rows="3"
                                          style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมวดหมู่</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <select name="category_id" x-model="form.category_id"
                                            style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                        <option value="">ทุกหมวดหมู่</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ความสำคัญ</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <select name="priority" x-model="form.priority"
                                            style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                        <option value="">ทุกระดับ</option>
                                        @foreach($prioLabels as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                    <i class="fas fa-hourglass-start"></i> เวลาตอบกลับแรก (นาที) <span style="color:#d9534f;">*</span>
                                </label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="number" name="first_response_time" x-model="form.first_response_time" min="1" required
                                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                </div>
                            </div>

                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                    <i class="fas fa-hourglass-end"></i> เวลาปิดงาน (นาที) <span style="color:#d9534f;">*</span>
                                </label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="number" name="resolution_time" x-model="form.resolution_time" min="1" required
                                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                </div>
                            </div>
                        </div>

                        <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:12px 14px; cursor:pointer;">
                            <input type="checkbox" name="business_hours_only" value="1" x-model="form.business_hours_only"
                                   style="accent-color:#e0a52e; width:16px; height:16px; cursor:pointer;">
                            <span style="font-size:13px; font-weight:600; color:var(--ink);">
                                <i class="fas fa-business-time" style="color:#e0a52e; margin-right:5px;"></i>นับเฉพาะเวลาทำการเท่านั้น
                            </span>
                        </label>
                    </div>

                    <div style="padding:16px 20px; display:flex; justify-content:flex-end; gap:10px; box-shadow:var(--inset-sm);">
                        <button type="button" class="tp-btn" @click="open = false">ยกเลิก</button>
                        <button type="submit" class="tp-btn tp-btn-primary" style="font-weight:700;">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function slaPolicies() {
    return {
        open: false,
        editingId: null,
        policies: @js($polPayload),
        storeUrl: @js(route('admin.tickets.sla-policies.store')),
        form: { name: '', description: '', category_id: '', priority: '', first_response_time: 60, resolution_time: 1440, business_hours_only: false },

        get formAction() {
            return this.editingId ? (this.storeUrl + '/' + this.editingId) : this.storeUrl;
        },

        openCreate() {
            this.editingId = null;
            this.form = { name: '', description: '', category_id: '', priority: '', first_response_time: 60, resolution_time: 1440, business_hours_only: false };
            this.open = true;
        },

        openEdit(id) {
            const p = this.policies.find(x => x.id === id);
            if (!p) return;
            this.editingId = id;
            this.form = {
                name: p.name || '',
                description: p.description || '',
                category_id: p.category_id || '',
                priority: p.priority || '',
                first_response_time: p.first_response_time || 60,
                resolution_time: p.resolution_time || 1440,
                business_hours_only: !!p.business_hours_only,
            };
            this.open = true;
        },

        init() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.open = false;
            });
        },
    };
}
</script>
@endpush
@endsection
