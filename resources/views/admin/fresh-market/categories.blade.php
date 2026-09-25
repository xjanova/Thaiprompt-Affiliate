{{--
 | หมวดหมู่สินค้าตลาดสด (admin.fresh-market.categories) — ธีม V4 + SortableJS
 | ตัวแปรจาก Admin\FreshMarketController@categories: $categories (withCount listings, เรียง sort_order)
 | ฟอร์ม: POST categories.store / PUT categories.update {name*, icon, description, is_active (hidden 0 + checkbox 1)}
 |        PATCH categories.toggle/{category} · DELETE categories.destroy/{category} (มีสินค้า/หมวดย่อยอยู่ = ลบไม่ได้)
 | ลากเรียง: POST categories.reorder JSON {order: [id, id, ...]} (ส่งเป็นอาร์เรย์ของ id ตามลำดับใหม่)
--}}
@extends('layouts.admin-v4')

@section('title', 'หมวดหมู่ตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
<div x-data="w1CategoryManager()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.dashboard') }}" class="tp-icon-btn" title="กลับแดชบอร์ดตลาดสด"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · หมวดหมู่</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">หมวดหมู่สินค้า 📂</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ลากที่ <i class="fas fa-grip-vertical"></i> เพื่อเรียงลำดับที่ผู้ซื้อเห็น</div>
            </div>
        </div>
        <button type="button" class="tp-btn tp-btn-primary" @click="openCreate()"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่</button>
    </div>

    @include('admin.riders.partials.flash')

    <div x-show="orderMessage" x-cloak class="tp-card" style="padding:10px 16px; font-size:13px;" :style="{ borderLeft: '4px solid var(' + (orderOk ? '--w-ok' : '--w-bad') + ')' }">
        <i class="fas" :class="orderOk ? 'fa-circle-check' : 'fa-circle-exclamation'" :style="{ color: 'var(' + (orderOk ? '--w-ok' : '--w-bad') + ')' }"></i>
        <span x-text="orderMessage"></span>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:720px; border-collapse:collapse; font-size:13.5px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:12px 8px 12px 18px; width:36px;"></th>
                        <th style="padding:12px;">หมวดหมู่</th>
                        <th style="padding:12px;">คำอธิบาย</th>
                        <th style="padding:12px; text-align:center;">สินค้า</th>
                        <th style="padding:12px; text-align:center;">สถานะ</th>
                        <th style="padding:12px 18px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody x-ref="list">
                    @forelse ($categories as $category)
                        <tr class="w1-row" data-id="{{ $category->id }}" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:12px 8px 12px 18px; color:var(--ink2); cursor:grab;" class="w1-handle" title="ลากเพื่อเรียงลำดับ"><i class="fas fa-grip-vertical"></i></td>
                            <td style="padding:12px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="tp-well" style="width:40px; height:40px; border-radius:12px; display:grid; place-items:center; font-size:20px; flex:none;">{{ $category->icon ?: '🏷️' }}</span>
                                    <div>
                                        <div style="font-weight:700;">{{ $category->name }}</div>
                                        @if ($category->slug)<div style="font-size:11.5px; color:var(--ink2);">{{ $category->slug }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px; color:var(--ink2); max-width:320px;">{{ \Illuminate\Support\Str::limit((string) $category->description, 80) ?: '-' }}</td>
                            <td style="padding:12px; text-align:center;">
                                <a href="{{ route('admin.fresh-market.listings', ['category_id' => $category->id]) }}" class="w1-link tp-num">{{ number_format((int) $category->listings_count) }}</a>
                            </td>
                            <td style="padding:12px; text-align:center;">
                                <form method="POST" action="{{ route('admin.fresh-market.categories.toggle', $category) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" style="border:0; background:none; cursor:pointer; padding:0;" title="กดเพื่อสลับเปิด/ปิด">
                                        @include('admin.riders.partials.pill', ['pillTone' => $category->is_active ? 'ok' : 'mute', 'pillText' => $category->is_active ? 'เปิดใช้งาน' : 'ปิดอยู่', 'pillIcon' => $category->is_active ? 'fa-toggle-on' : 'fa-toggle-off', 'pillTitle' => null])
                                    </button>
                                </form>
                            </td>
                            <td style="padding:12px 18px;">
                                <div style="display:flex; justify-content:flex-end; gap:7px;">
                                    <button type="button" class="tp-btn tp-btn-sm" @click="openEdit(@js(['id' => $category->id, 'name' => $category->name, 'icon' => $category->icon, 'description' => $category->description, 'is_active' => (bool) $category->is_active]))">
                                        <i class="fas fa-pen"></i> แก้ไข
                                    </button>
                                    <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ลบ"
                                            @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.categories.destroy', $category), 'title' => 'ลบหมวดหมู่ '.$category->name, 'message' => (int) $category->listings_count > 0 ? 'หมวดนี้มีสินค้า '.(int) $category->listings_count.' รายการ ระบบจะไม่ยอมให้ลบ — ย้ายสินค้าออกก่อน' : 'ลบแล้วกู้คืนไม่ได้', 'reason' => 'none', 'confirm' => 'ลบหมวดหมู่', 'tone' => 'bad', 'icon' => 'fa-trash', 'fields' => ['_method' => 'DELETE']]))">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:44px 18px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-folder-open" style="font-size:30px; opacity:.5; display:block; margin-bottom:10px;"></i>
                                ยังไม่มีหมวดหมู่ กด "เพิ่มหมวดหมู่" เพื่อเริ่มต้น
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== โมดัลเพิ่ม/แก้ไข ===== --}}
    <div x-show="modal" x-cloak @keydown.escape.window="modal = false">
        <div style="position:fixed; inset:0; z-index:85; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,.5);" @click.self="modal = false">
            <form method="POST" :action="editingId ? updateUrl.replace('__ID__', editingId) : storeUrl" class="tp-card"
                  style="width:100%; max-width:480px; padding:22px; max-height:calc(100vh - 32px); overflow-y:auto;"
                  @submit="saving = true">
                @csrf
                <template x-if="editingId">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:16px;">
                    <div class="tp-section-h" style="font-size:16px;" x-text="editingId ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่'"></div>
                    <button type="button" class="tp-icon-btn" style="width:34px; height:34px;" @click="modal = false" title="ปิด"><i class="fas fa-xmark"></i></button>
                </div>
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="display:grid; grid-template-columns:90px 1fr; gap:12px;">
                        <label>
                            <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ไอคอน</span>
                            <input type="text" name="icon" x-model="form.icon" maxlength="10" class="tp-input" style="text-align:center; font-size:20px; padding:8px;" placeholder="🥬">
                        </label>
                        <label>
                            <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชื่อหมวดหมู่ <span style="color:var(--w-bad);">*</span></span>
                            <input type="text" name="name" x-model="form.name" required maxlength="100" class="tp-input" placeholder="เช่น ผักสด ผลไม้ อาหารปรุงสำเร็จ">
                        </label>
                    </div>
                    <label>
                        <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">คำอธิบาย</span>
                        <textarea name="description" x-model="form.description" rows="3" maxlength="500" class="tp-input" style="resize:vertical;" placeholder="คำอธิบายสั้นๆ ของหมวดนี้"></textarea>
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" style="width:18px; height:18px; accent-color:var(--accent1);">
                        เปิดให้ผู้ซื้อเห็นหมวดนี้
                    </label>
                </div>
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="button" class="tp-btn" style="flex:1;" @click="modal = false">ยกเลิก</button>
                    <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;" :disabled="saving">
                        <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="editingId ? 'บันทึกการแก้ไข' : 'สร้างหมวดหมู่'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    /* จัดการหมวดหมู่ตลาดสด: โมดัลเพิ่ม/แก้ไข + ลากเรียงลำดับด้วย SortableJS */
    function w1CategoryManager() {
        return {
            storeUrl: @js(route('admin.fresh-market.categories.store')),
            updateUrl: @js(route('admin.fresh-market.categories.update', ['category' => '__ID__'])),
            reorderUrl: @js(route('admin.fresh-market.categories.reorder')),
            modal: false,
            saving: false,
            editingId: null,
            form: { icon: '', name: '', description: '', is_active: true },
            orderMessage: '',
            orderOk: true,
            init() {
                this.$nextTick(() => {
                    if (!window.Sortable || !this.$refs.list || !this.$refs.list.querySelector('tr[data-id]')) return;
                    Sortable.create(this.$refs.list, {
                        animation: 160,
                        handle: '.w1-handle',
                        draggable: 'tr[data-id]',
                        onEnd: () => this.saveOrder(),
                    });
                });
            },
            openCreate() {
                this.editingId = null;
                this.form = { icon: '', name: '', description: '', is_active: true };
                this.saving = false;
                this.modal = true;
            },
            openEdit(category) {
                this.editingId = category.id;
                this.form = { icon: category.icon || '', name: category.name || '', description: category.description || '', is_active: !!category.is_active };
                this.saving = false;
                this.modal = true;
            },
            async saveOrder() {
                const order = Array.from(this.$refs.list.querySelectorAll('tr[data-id]')).map((row) => parseInt(row.dataset.id, 10));
                try {
                    const res = await fetch(this.reorderUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ order: order }),
                    });
                    const json = await res.json().catch(() => ({}));
                    this.orderOk = res.ok && json.success !== false;
                    this.orderMessage = this.orderOk ? (json.message || 'บันทึกลำดับหมวดหมู่แล้ว') : 'บันทึกลำดับไม่สำเร็จ กรุณารีเฟรชหน้าแล้วลองใหม่';
                } catch (e) {
                    this.orderOk = false;
                    this.orderMessage = 'บันทึกลำดับไม่สำเร็จ ตรวจการเชื่อมต่อแล้วลองใหม่';
                }
                setTimeout(() => { this.orderMessage = ''; }, 3500);
            },
        };
    }
</script>
@endpush
