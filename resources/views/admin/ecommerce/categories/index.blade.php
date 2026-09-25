@extends('layouts.admin-v4')

@section('title', 'หมวดหมู่สินค้า')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $allCategories = $allCategories ?? $categories->getCollection();
    $updateUrlTemplate = route('admin.ecommerce.categories.update', ['category' => '__ID__']);
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{
        open: false,
        busy: false,
        form: { id: null, name: '', slug: '', description: '', sort_order: 0, parent_id: '', is_active: true },
        storeUrl: @js(route('admin.ecommerce.categories.store')),
        updateUrl: @js($updateUrlTemplate),
        get action() { return this.form.id ? this.updateUrl.replace('__ID__', this.form.id) : this.storeUrl; },
        create() { this.form = { id: null, name: '', slug: '', description: '', sort_order: 0, parent_id: '', is_active: true }; this.open = true; },
        edit(cat) { this.form = Object.assign({}, cat, { parent_id: cat.parent_id ?? '' }); this.open = true; }
     }"
     @keydown.escape.window="open = false">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · หมวดหมู่</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">หมวดหมู่สินค้า 🏷️</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">จัดหมวดหมู่ที่ร้านค้าใช้ลงสินค้าและลูกค้าใช้ค้นหา</div>
        </div>
        <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="create()"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่</button>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $c['bad'] }};">
            <ul style="margin:0; padding-left:18px; font-size:13px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.ecommerce.categories.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
            <input type="text" name="search" value="{{ request('search') }}" class="tp-input" style="flex:1; min-width:200px;" placeholder="ค้นหาชื่อหมวดหมู่">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
            @if(request('search'))<a href="{{ route('admin.ecommerce.categories.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>@endif
        </form>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:760px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">หมวดหมู่</th>
                        <th style="{{ $th }}">Slug</th>
                        <th style="{{ $th }}">หมวดแม่</th>
                        <th style="{{ $th }} text-align:right;">สินค้า</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        @php
                            $payload = [
                                'id' => $category->id,
                                'name' => (string) $category->name,
                                'slug' => (string) $category->slug,
                                'description' => (string) $category->description,
                                'sort_order' => (int) $category->sort_order,
                                'parent_id' => $category->parent_id,
                                'is_active' => (bool) $category->is_active,
                            ];
                        @endphp
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    @if($category->image_url_full)
                                        <img src="{{ $category->image_url_full }}" alt="" loading="lazy" style="width:40px; height:40px; border-radius:11px; object-fit:cover; flex:none;">
                                    @else
                                        <span class="tp-tile" style="width:40px; height:40px; font-size:15px;"><i class="fas fa-tag"></i></span>
                                    @endif
                                    <div style="min-width:0;">
                                        <div style="font-weight:700;">{{ $category->name }}</div>
                                        @if($category->description)<div style="font-size:11.5px; color:var(--ink2); max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ \Illuminate\Support\Str::limit($category->description, 70) }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }} color:var(--ink2);" class="tp-num">{{ $category->slug }}</td>
                            <td style="{{ $td }} color:var(--ink2);">{{ $category->parent?->name ?? '—' }}</td>
                            <td style="{{ $td }} text-align:right;">
                                <a href="{{ route('admin.ecommerce.products.index', ['category' => $category->id]) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ number_format((int) ($category->products_count ?? 0)) }}</a>
                            </td>
                            <td style="{{ $td }}">
                                <span class="tp-pill" style="{{ $pill($category->is_active ? $c['ok'] : $c['mute']) }}">{{ $category->is_active ? 'ใช้งาน' : 'ปิด' }}</span>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    <button type="button" class="tp-icon-btn" style="width:34px; height:34px;" title="แก้ไข" @click="edit(@js($payload))"><i class="fas fa-pen"></i></button>
                                    <form method="POST" action="{{ route('admin.ecommerce.categories.delete', $category) }}"
                                          @submit="if (!confirm(@js('ลบหมวดหมู่ "'.$category->name.'" ? (ลบได้เฉพาะหมวดที่ไม่มีสินค้า)'))) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" style="width:34px; height:34px; color:{{ $c['bad'] }};" title="ลบ" @disabled(($category->products_count ?? 0) > 0)><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-tags" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ยังไม่มีหมวดหมู่
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($categories->hasPages())
        <div>{{ $categories->links() }}</div>
    @endif

    {{-- ===== โมดัลเพิ่ม/แก้ไข ===== --}}
    <div x-show="open" x-cloak x-transition.opacity class="flex"
         style="position:fixed; inset:0; z-index:80; background:rgba(0,0,0,.45); align-items:flex-start; justify-content:center; padding:24px 12px; overflow-y:auto;"
         @click.self="open = false">
        <div class="tp-card" style="width:100%; max-width:560px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <div class="tp-section-h" x-text="form.id ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่'"></div>
                <button type="button" class="tp-icon-btn" style="width:34px; height:34px;" @click="open = false" aria-label="ปิด"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" :action="action" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px;" @submit="busy = true">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!form.id">
                <div>
                    <label style="{{ $lbl }}">ชื่อหมวดหมู่ <span style="color:{{ $c['bad'] }};">*</span></label>
                    <input type="text" name="name" x-model="form.name" required maxlength="255" class="tp-input">
                </div>
                <div>
                    <label style="{{ $lbl }}">Slug (เว้นว่างให้สร้างอัตโนมัติ)</label>
                    <input type="text" name="slug" x-model="form.slug" maxlength="255" class="tp-input tp-num">
                </div>
                <div>
                    <label style="{{ $lbl }}">คำอธิบาย</label>
                    <textarea name="description" x-model="form.description" rows="3" class="tp-input"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px;">
                    <div>
                        <label style="{{ $lbl }}">หมวดแม่</label>
                        <select name="parent_id" x-model="form.parent_id" class="tp-input">
                            <option value="">— ไม่มี (หมวดหลัก) —</option>
                            @foreach($allCategories as $cat)
                                <option value="{{ $cat->id }}" :disabled="String(form.id) === '{{ $cat->id }}'">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ลำดับ</label>
                        <input type="number" name="sort_order" x-model="form.sort_order" class="tp-input tp-num">
                    </div>
                </div>
                <div>
                    <label style="{{ $lbl }}">รูปหมวดหมู่ (ไม่เกิน 5MB)</label>
                    <input type="file" name="category_image" accept="image/*" class="tp-input" style="padding:9px 12px;">
                </div>
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" style="accent-color:var(--accent1); width:17px; height:17px;"> เปิดใช้งาน
                </label>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="tp-btn" @click="open = false">ยกเลิก</button>
                    <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy"><i class="fas fa-floppy-disk"></i> บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
