@extends('layouts.admin-v4')

@section('title', 'กฎการมอบหมาย Ticket')

@section('content')
{{--
    🎯 กฎการมอบหมาย Ticket อัตโนมัติ (ธีม V4 นวลทองคำ)

    ปรับปรุงเพิ่มจากของเดิม:
    - ปุ่มแก้ไขเดิมแค่ alert("will be implemented") ทั้งที่ route PUT มีอยู่จริง
      → ต่อสายให้ใช้งานได้จริง (เติมค่าเดิมลงฟอร์ม + ส่ง PUT ไป /{id})
    - เพิ่มปุ่มเปิด/ปิดกฎ ต่อกับ route toggle ที่มีอยู่แล้วแต่ไม่เคยถูกเรียกจากหน้าไหน
--}}
@php
    $ruleList = collect($rules ?? []);
    $rulePayload = $ruleList->map(fn ($r) => [
        'id' => $r->id,
        'name' => $r->name,
        'category_id' => $r->category_id,
        'priority_filter' => $r->priority_filter,
        'assign_to_user_id' => $r->assign_to_user_id,
        'priority' => $r->priority,
    ])->values();

    $prioColors = ['critical' => '#d9534f', 'high' => '#d6824a', 'medium' => '#e0a52e', 'low' => '#5689b8'];
    $prioLabels = ['critical' => 'วิกฤต', 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ'];
@endphp

<div x-data="assignmentRules()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · กฎการมอบหมาย</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">กฎการมอบหมาย 🎯</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">มอบหมาย Ticket ให้เจ้าหน้าที่โดยอัตโนมัติตามเงื่อนไข</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="openCreate()">
                <i class="fas fa-plus"></i> เพิ่มกฎ
            </button>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    @php
        $kpis = [
            [$ruleList->count(),                           'กฎทั้งหมด',    'fa-list-check',    null],
            [$ruleList->where('is_active', true)->count(), 'เปิดใช้งาน',    'fa-circle-check',  '#5aa07e'],
            [$ruleList->where('is_active', false)->count(),'ปิดใช้งาน',     'fa-circle-pause',  '#9a8f7c'],
            [$ruleList->unique('assign_to_user_id')->count(), 'เจ้าหน้าที่ที่ถูกมอบหมาย', 'fa-user-check', '#5689b8'],
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
                        @foreach(['ชื่อกฎ','เงื่อนไข: หมวดหมู่','เงื่อนไข: ความสำคัญ','มอบหมายให้','ลำดับ','สถานะ'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ruleList as $rule)
                        @php $pc = $prioColors[$rule->priority_filter] ?? '#9a8f7c'; @endphp
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- ชื่อกฎ --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:13.5px; font-weight:700; color:var(--ink);">{{ $rule->name }}</div>
                            </td>
                            {{-- หมวดหมู่ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill tp-pill-soft"><i class="fas fa-folder"></i> {{ $rule->category->name ?? 'ทุกหมวดหมู่' }}</span>
                            </td>
                            {{-- ความสำคัญ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($rule->priority_filter)
                                    <span class="tp-pill" style="background:color-mix(in srgb, {{ $pc }} 18%, transparent); color:{{ $pc }}; font-weight:700;">
                                        <i class="fas fa-flag"></i> {{ $prioLabels[$rule->priority_filter] ?? ucfirst($rule->priority_filter) }}
                                    </span>
                                @else
                                    <span class="tp-pill tp-pill-soft">ทุกระดับ</span>
                                @endif
                            </td>
                            {{-- มอบหมายให้ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($rule->assignTo)
                                    <div style="display:flex; align-items:center; gap:9px;">
                                        <span class="tp-tile" style="width:28px; height:28px; border-radius:50%; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:#5689b8;">
                                            {{ mb_strtoupper(mb_substr($rule->assignTo->name ?: '?', 0, 1)) }}
                                        </span>
                                        <span style="font-size:13px; color:var(--ink);">{{ $rule->assignTo->name }}</span>
                                    </div>
                                @else
                                    <span style="color:var(--ink2); font-size:12px; font-style:italic;">ไม่ระบุ</span>
                                @endif
                            </td>
                            {{-- ลำดับ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill tp-pill-gold">{{ $rule->priority ?? 0 }}</span>
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($rule->is_active)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">● เปิดใช้งาน</span>
                                @else
                                    <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 18%, transparent); color:var(--ink2);">● ปิดใช้งาน</span>
                                @endif
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:7px;">
                                    @if(Route::has('admin.tickets.assignment-rules.toggle'))
                                        <form action="{{ route('admin.tickets.assignment-rules.toggle', $rule->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tp-btn tp-btn-sm" title="{{ $rule->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}"
                                                    style="color:{{ $rule->is_active ? '#9a8f7c' : '#5aa07e' }};">
                                                <i class="fas {{ $rule->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button" class="tp-btn tp-btn-sm" title="แก้ไข" @click="openEdit({{ $rule->id }})">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.tickets.assignment-rules.destroy', $rule->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ลบกฎ &quot;{{ $rule->name }}&quot; ใช่หรือไม่?')">
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
                            <td colspan="7" style="padding:0;">
                                <div style="text-align:center; color:var(--ink2); padding:44px 0;">
                                    <i class="fas fa-user-check" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    <div style="font-size:14px; font-weight:600;">ยังไม่มีกฎการมอบหมาย</div>
                                    <div style="font-size:12px; margin-top:4px;">สร้างกฎแรกเพื่อให้ระบบมอบหมาย Ticket อัตโนมัติ</div>
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

            <div class="tp-card" style="position:relative; max-width:600px; width:100%; padding:0; overflow:hidden;">
                <form :action="formAction" method="POST">
                    @csrf
                    <template x-if="editingId">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div style="padding:18px 20px; background:linear-gradient(120deg, color-mix(in srgb, #4fa3a3 24%, transparent), transparent 75%); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div class="tp-section-h" style="margin:0;">
                            <i class="fas fa-user-check"></i>
                            <span x-text="editingId ? 'แก้ไขกฎการมอบหมาย' : 'เพิ่มกฎการมอบหมาย'"></span>
                        </div>
                        <button type="button" class="tp-icon-btn" @click="open = false" title="ปิด"><i class="fas fa-times"></i></button>
                    </div>

                    <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                ชื่อกฎ <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" name="name" x-model="form.name" required placeholder="เช่น ปัญหาการชำระเงิน → ทีมการเงิน"
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เงื่อนไข: หมวดหมู่</label>
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
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เงื่อนไข: ความสำคัญ</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <select name="priority_filter" x-model="form.priority_filter"
                                            style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                        <option value="">ทุกระดับ</option>
                                        @foreach($prioLabels as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                มอบหมายให้ <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="assign_to_user_id" x-model="form.assign_to_user_id" required
                                        style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                    <option value="">เลือกเจ้าหน้าที่</option>
                                    @foreach($staffUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                <i class="fas fa-sort"></i> ลำดับการตรวจ
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                {{-- min=1 ตาม validate ของ controller ('required|integer|min:1') — ใส่ 0 จะไม่ผ่าน --}}
                                <input type="number" name="priority" x-model="form.priority" min="1" required
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            </div>
                            <div style="font-size:11px; color:var(--ink2); margin-top:5px;">เลขน้อยถูกตรวจก่อน — กฎแรกที่ตรงเงื่อนไขจะถูกใช้ (เริ่มที่ 1)</div>
                        </div>
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
function assignmentRules() {
    return {
        open: false,
        editingId: null,
        rules: @js($rulePayload),
        storeUrl: @js(route('admin.tickets.assignment-rules.store')),
        form: { name: '', category_id: '', priority_filter: '', assign_to_user_id: '', priority: 1 },

        get formAction() {
            return this.editingId ? (this.storeUrl + '/' + this.editingId) : this.storeUrl;
        },

        openCreate() {
            this.editingId = null;
            this.form = { name: '', category_id: '', priority_filter: '', assign_to_user_id: '', priority: 1 };
            this.open = true;
        },

        openEdit(id) {
            const r = this.rules.find(x => x.id === id);
            if (!r) return;
            this.editingId = id;
            this.form = {
                name: r.name || '',
                category_id: r.category_id || '',
                priority_filter: r.priority_filter || '',
                assign_to_user_id: r.assign_to_user_id || '',
                priority: r.priority || 1,
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
