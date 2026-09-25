@extends('layouts.seller-v4')

@section('title', 'หมวดหมู่ POS')

@php
    // สีหมวดที่ผู้ขายตั้งเอง — ใช้ได้เฉพาะรหัส hex ที่ถูกต้อง (กันค่าแปลก ๆ หลุดเข้า style)
    $safeColor = fn ($c) => (is_string($c) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $c)) ? $c : 'var(--accent1)';

    $catRows = $categories->getCollection()->map(fn ($c) => [
        'id' => (int) $c->id,
        'name' => (string) $c->name,
        'description' => (string) ($c->description ?? ''),
        'icon' => (string) ($c->icon ?? ''),
        'color' => (is_string($c->color) && preg_match('/^#[0-9a-fA-F]{6}$/', $c->color)) ? $c->color : null,
        'order' => (int) ($c->order ?? 0),
        'is_active' => (bool) $c->is_active,
        'show_in_pos' => (bool) $c->show_in_pos,
        'update_url' => route('seller.pos.categories.update', $c->id),
    ])->keyBy('id');
@endphp

@section('content')
<div x-data="posCategories(@js($catRows), @js(route('seller.pos.categories.store')))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="หมวดหมู่ POS" icon="🗂️" crumb="ร้านค้า · POS"
                         subtitle="จัดกลุ่มสินค้าเพื่อให้พนักงานหาได้เร็วบนหน้าจอขาย">
        <button type="button" class="tp-btn tp-btn-primary" @click="openCreate()">＋ เพิ่มหมวดหมู่</button>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <x-seller-kit.errors />

    @if($categories->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px;">
            @foreach($categories as $category)
                @php
                    $cColor = $safeColor($category->color);
                @endphp
                <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="tp-inset-sm" style="width:48px; height:48px; border-radius:14px; display:grid; place-items:center; font-size:22px; background:color-mix(in srgb, {{ $cColor }} 22%, var(--surf));" aria-hidden="true">{{ $category->icon ?: '📦' }}</span>
                        <div style="min-width:0; flex:1;">
                            <div style="font-weight:800; font-size:14.5px; overflow-wrap:anywhere;">{{ $category->name }}</div>
                            <div style="font-size:12px; color:var(--ink2);">{{ number_format($category->products->count()) }} สินค้า · ลำดับ {{ (int) ($category->order ?? 0) }}</div>
                        </div>
                    </div>
                    @if($category->description)
                        <div style="font-size:12.5px; color:var(--ink2); line-height:1.6;">{{ $category->description }}</div>
                    @endif
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <x-seller-kit.pill :tone="$category->is_active ? 'ok' : 'muted'">{{ $category->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</x-seller-kit.pill>
                        @if($category->show_in_pos)<x-seller-kit.pill tone="info">แสดงในหน้าขาย</x-seller-kit.pill>@endif
                    </div>
                    <div style="display:flex; gap:8px; margin-top:auto;">
                        <button type="button" class="tp-btn tp-btn-sm" style="flex:1;" @click="openEdit({{ (int) $category->id }})">✏️ แก้ไข</button>
                        <form method="POST" action="{{ route('seller.pos.categories.destroy', $category->id) }}" style="flex:1;"
                              onsubmit="return confirm('ต้องการลบหมวดหมู่นี้ใช่หรือไม่? สินค้าในหมวดจะไม่ถูกลบ');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tp-btn tp-btn-sm" style="width:100%; color:var(--tp-bad, #d9534f);">🗑️ ลบ</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        @if($categories->hasPages())
            <div>{{ $categories->links() }}</div>
        @endif
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="🗂️" title="ยังไม่มีหมวดหมู่" text="สร้างหมวด เช่น เครื่องดื่ม อาหาร ของหวาน เพื่อให้กดขายได้เร็วขึ้น">
                <button type="button" class="tp-btn tp-btn-primary tp-btn-sm" @click="openCreate()">＋ เพิ่มหมวดหมู่แรก</button>
            </x-seller-kit.empty>
        </div>
    @endif

    {{-- หน้าต่างเพิ่ม/แก้ไขหมวดหมู่ --}}
    <div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false">
      <div style="position:fixed; inset:0; z-index:90; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; padding:16px;"
           @click.self="open = false" role="dialog" aria-modal="true" aria-labelledby="cat-modal-title">
        <form method="POST" :action="action" class="tp-card" @submit="saving = true"
              style="width:100%; max-width:520px; max-height:92vh; overflow-y:auto; padding:22px; background:var(--surf); display:flex; flex-direction:column; gap:14px;">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <div id="cat-modal-title" style="font-size:18px; font-weight:800;" x-text="editing ? '✏️ แก้ไขหมวดหมู่' : '＋ เพิ่มหมวดหมู่ใหม่'"></div>

            <div>
                <label for="cat-name" style="font-size:12.5px; font-weight:700;">ชื่อหมวดหมู่ <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="cat-name" type="text" name="name" x-model="form.name" required maxlength="255" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="cat-desc" style="font-size:12.5px; font-weight:700;">คำอธิบาย</label>
                <textarea id="cat-desc" name="description" x-model="form.description" rows="2" class="tp-input" style="margin-top:6px; resize:vertical;"></textarea>
            </div>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px;">
                <div>
                    <label for="cat-icon" style="font-size:12.5px; font-weight:700;">ไอคอน</label>
                    <input id="cat-icon" type="text" name="icon" x-model="form.icon" maxlength="100" placeholder="📦" class="tp-input" style="margin-top:6px; text-align:center;">
                </div>
                <div>
                    <label for="cat-color" style="font-size:12.5px; font-weight:700;">สี</label>
                    <input id="cat-color" type="color" name="color" x-model="form.color" class="tp-input" style="margin-top:6px; height:44px; padding:4px;">
                </div>
                <div>
                    <label for="cat-order" style="font-size:12.5px; font-weight:700;">ลำดับ</label>
                    <input id="cat-order" type="number" name="order" x-model.number="form.order" min="0" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:16px;">
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" style="width:18px; height:18px; accent-color:var(--accent1);"> เปิดใช้งาน
                </label>
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; cursor:pointer;">
                    <input type="hidden" name="show_in_pos" value="0">
                    <input type="checkbox" name="show_in_pos" value="1" x-model="form.show_in_pos" style="width:18px; height:18px; accent-color:var(--accent1);"> แสดงในหน้าขาย
                </label>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1.4fr; gap:10px;">
                <button type="button" class="tp-btn" @click="open = false">ยกเลิก</button>
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }"><span x-text="saving ? 'กำลังบันทึก…' : 'บันทึก'"></span></button>
            </div>
        </form>
      </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // จัดการหน้าต่างเพิ่ม/แก้ไขหมวดหมู่ POS (สีเริ่มต้นอ่านจากตัวแปรธีม --accent1)
    function posCategories(rows, storeUrl) {
        const themeColor = () => {
            const v = getComputedStyle(document.documentElement).getPropertyValue('--accent1').trim();
            return /^#[0-9a-f]{6}$/i.test(v) ? v : '#e6b347';
        };
        const blank = () => ({ name: '', description: '', icon: '', color: themeColor(), order: 0, is_active: true, show_in_pos: true });
        return {
            rows: rows,
            open: false,
            editing: false,
            saving: false,
            action: storeUrl,
            form: blank(),
            openCreate() { this.editing = false; this.action = storeUrl; this.form = blank(); this.saving = false; this.open = true; },
            openEdit(id) {
                const row = this.rows[id];
                if (!row) return;
                this.editing = true;
                this.action = row.update_url;
                this.form = Object.assign(blank(), row, { color: row.color || themeColor() });
                this.saving = false;
                this.open = true;
            },
        };
    }
</script>
@endpush
