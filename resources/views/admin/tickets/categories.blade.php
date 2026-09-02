@extends('layouts.admin-v4')

@section('title', 'หมวดหมู่ Ticket')

@section('content')
{{--
    📂 หมวดหมู่ Ticket (ธีม V4 นวลทองคำ)

    ปรับปรุงเพิ่มจากของเดิม:
    - ปุ่มแก้ไขเดิมแค่ alert("will be implemented") ทั้งที่ route PUT มีอยู่จริง
      → ต่อสายให้ใช้งานได้จริง (เติมค่าเดิมลงฟอร์ม + ส่ง PUT)
    - เดิมไม่มีปุ่มลบเลย ทั้งที่ route DELETE มีอยู่จริง → เพิ่มให้ พร้อม confirm
    - เดิม modal ใช้ raw JS + <script> ลอยใน section → ย้ายมาใช้ Alpine + @push('scripts')
--}}
@php
    $catList = collect($categories ?? []);
    // ส่งข้อมูลหมวดหมู่ให้ Alpine ใช้เติมฟอร์มตอนกดแก้ไข
    $catPayload = $catList->map(fn ($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'description' => $c->description,
        'icon' => $c->icon,
        'color' => $c->color ?: '#e0a52e',
        'sort_order' => $c->sort_order ?? 0,
        'is_active' => (bool) ($c->is_active ?? true),
    ])->values();
    $mostUsed = $catList->sortByDesc('tickets_count')->first();
@endphp

<div x-data="ticketCategories()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · หมวดหมู่</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">หมวดหมู่ Ticket 📂</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">จัดการหมวดหมู่สำหรับระบบ Ticket Support</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
            </a>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="openCreate()">
                <i class="fas fa-plus"></i> เพิ่มหมวดหมู่
            </button>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-folder"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($catList->count()) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">หมวดหมู่ทั้งหมด</div>
                </div>
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5aa07e;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($catList->where('is_active', true)->count()) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เปิดใช้งาน</div>
                </div>
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5689b8;">
                    <i class="fas fa-ticket"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($catList->sum('tickets_count')) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Ticket ทั้งหมด</div>
                </div>
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#b79ae8;">
                    <i class="fas fa-star"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:15px; font-weight:800; line-height:1.3; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $mostUsed->name ?? '—' }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ใช้มากที่สุด</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        @foreach(['ID','ชื่อหมวดหมู่','คำอธิบาย','ไอคอน','จำนวน Ticket','สถานะ'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($catList as $category)
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- ID --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-tile" style="width:30px; height:30px; border-radius:50%; font-size:12px; font-weight:800; display:inline-flex; align-items:center; justify-content:center;">{{ $category->id }}</span>
                            </td>
                            {{-- ชื่อ --}}
                            <td style="padding:14px 16px;">
                                <div style="display:flex; align-items:center; gap:9px;">
                                    @if($category->color ?? null)
                                        <span style="width:11px; height:11px; border-radius:50%; background:{{ $category->color }}; flex-shrink:0;"></span>
                                    @endif
                                    <span style="font-size:13.5px; font-weight:700; color:var(--ink);">{{ $category->name ?? '—' }}</span>
                                </div>
                            </td>
                            {{-- คำอธิบาย --}}
                            <td style="padding:14px 16px; font-size:13px; color:var(--ink2);">
                                {{ Str::limit($category->description ?: 'ไม่มีคำอธิบาย', 50) }}
                            </td>
                            {{-- ไอคอน --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($category->icon ?? null)
                                    <span class="tp-tile" style="width:36px; height:36px; border-radius:10px; font-size:14px; display:inline-flex; align-items:center; justify-content:center; background:{{ $category->color ?: '#e0a52e' }};">
                                        <i class="{{ $category->icon }}" style="color:#fff;"></i>
                                    </span>
                                @else
                                    <span style="color:var(--ink2);">—</span>
                                @endif
                            </td>
                            {{-- จำนวน Ticket --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill" style="background:rgba(86,137,184,.18); color:#3f6a96;">
                                    <i class="fas fa-ticket"></i> {{ number_format($category->tickets_count ?? 0) }}
                                </span>
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($category->is_active ?? true)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">● เปิดใช้งาน</span>
                                @else
                                    <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 18%, transparent); color:var(--ink2);">● ปิดใช้งาน</span>
                                @endif
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:7px;">
                                    <button type="button" class="tp-btn tp-btn-sm" title="แก้ไข"
                                            @click="openEdit({{ $category->id }})">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.tickets.categories.destroy', $category->id) }}" style="display:inline;"
                                          onsubmit="return confirm('ลบหมวดหมู่ &quot;{{ $category->name }}&quot; ใช่หรือไม่? Ticket ที่อยู่ในหมวดนี้จะไม่มีหมวดหมู่')">
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
                                    <i class="fas fa-folder-open" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    <div style="font-size:14px; font-weight:600;">ยังไม่มีหมวดหมู่</div>
                                    <div style="font-size:12px; margin-top:4px;">สร้างหมวดหมู่แรกเพื่อจัดระเบียบ Ticket</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== โมดัลเพิ่ม/แก้ไข ===== --}}
    <div x-show="open" x-cloak style="position:fixed; inset:0; z-index:50; overflow-y:auto;">
        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
            <div style="position:fixed; inset:0; background:rgba(0,0,0,.55);" @click="open = false"></div>

            <div class="tp-card" style="position:relative; max-width:560px; width:100%; padding:0; overflow:hidden;">
                <form :action="formAction" method="POST">
                    @csrf
                    {{-- ตอนแก้ไขต้องส่ง PUT ตาม route --}}
                    <template x-if="editingId">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    {{-- หัวโมดัล --}}
                    <div style="padding:18px 20px; background:linear-gradient(120deg, color-mix(in srgb, #e0a52e 26%, transparent), transparent 75%); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div class="tp-section-h" style="margin:0;">
                            <i class="fas fa-folder-open"></i>
                            <span x-text="editingId ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่'"></span>
                        </div>
                        <button type="button" class="tp-icon-btn" @click="open = false" title="ปิด"><i class="fas fa-times"></i></button>
                    </div>

                    <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                        {{-- ชื่อ --}}
                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                ชื่อหมวดหมู่ <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" name="name" x-model="form.name" required placeholder="เช่น ปัญหาทางเทคนิค"
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            </div>
                        </div>

                        {{-- คำอธิบาย --}}
                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">คำอธิบาย</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <textarea name="description" x-model="form.description" rows="3" placeholder="คำอธิบายสั้นๆ ของหมวดหมู่นี้"
                                          style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
                            </div>
                        </div>

                        {{-- ไอคอน + สี --}}
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                    <i class="fas fa-icons"></i> คลาสไอคอน
                                </label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" name="icon" x-model="form.icon" placeholder="fas fa-wrench"
                                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                </div>
                                <div style="font-size:11px; color:var(--ink2); margin-top:5px;">คลาสของ FontAwesome</div>
                            </div>

                            <div>
                                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                    <i class="fas fa-palette"></i> สี
                                </label>
                                <div class="tp-well" style="padding:6px;">
                                    <input type="color" name="color" x-model="form.color"
                                           style="width:100%; height:34px; background:transparent; border:none; outline:none; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        {{-- ลำดับ --}}
                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                <i class="fas fa-sort"></i> ลำดับการแสดง
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            </div>
                            <div style="font-size:11px; color:var(--ink2); margin-top:5px;">เลขน้อยแสดงก่อน</div>
                        </div>

                        {{-- เปิดใช้งาน --}}
                        <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:12px 14px; cursor:pointer;">
                            {{-- hidden 0 นำหน้า: ถ้าไม่ติ๊ก เบราว์เซอร์จะไม่ส่ง is_active มาเลย
                                 controller ใช้ $request->only([...]) → ค่าเดิมจะค้าง ปิดไม่ลง --}}
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                   style="accent-color:#e0a52e; width:16px; height:16px; cursor:pointer;">
                            <span style="font-size:13px; font-weight:600; color:var(--ink);">
                                <i class="fas fa-circle-check" style="color:#5aa07e; margin-right:5px;"></i>เปิดใช้งาน (ผู้ใช้มองเห็น)
                            </span>
                        </label>
                    </div>

                    {{-- ท้ายโมดัล --}}
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
function ticketCategories() {
    return {
        open: false,
        editingId: null,
        categories: @js($catPayload),
        storeUrl: @js(route('admin.tickets.categories.store')),
        // route แก้ไขใช้ id ต่อท้าย — สร้าง URL จาก store แล้วเติม id
        form: { name: '', description: '', icon: '', color: '#e0a52e', sort_order: 0, is_active: true },

        get formAction() {
            return this.editingId ? (this.storeUrl + '/' + this.editingId) : this.storeUrl;
        },

        openCreate() {
            this.editingId = null;
            this.form = { name: '', description: '', icon: '', color: '#e0a52e', sort_order: 0, is_active: true };
            this.open = true;
        },

        openEdit(id) {
            const c = this.categories.find(x => x.id === id);
            if (!c) return;
            this.editingId = id;
            this.form = {
                name: c.name || '',
                description: c.description || '',
                icon: c.icon || '',
                color: c.color || '#e0a52e',
                sort_order: c.sort_order || 0,
                is_active: !!c.is_active,
            };
            this.open = true;
        },

        init() {
            // ปิดโมดัลด้วยปุ่ม Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.open = false;
            });
        },
    };
}
</script>
@endpush
@endsection
