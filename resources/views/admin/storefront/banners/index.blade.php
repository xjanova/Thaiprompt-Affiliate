@extends('layouts.admin-v4')

@section('title', 'แบนเนอร์หน้าร้าน')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{
        saving: false,
        reorderUrl: @js(route('admin.storefront.banners.reorder')),
        toggleUrl: @js(route('admin.storefront.banners.toggle', ['banner' => '__ID__'])),
        csrf() { return document.querySelector('meta[name=csrf-token]').content; },
        init() {
            const el = this.$refs.list;
            if (!el || typeof Sortable === 'undefined') return;
            Sortable.create(el, {
                animation: 160,
                handle: '[data-drag]',
                ghostClass: 'tp-drag-ghost',
                onEnd: () => this.saveOrder(),
            });
        },
        async saveOrder() {
            const items = [...this.$refs.list.querySelectorAll('[data-id]')].map((node, index) => ({ id: parseInt(node.dataset.id, 10), sort_order: index }));
            this.saving = true;
            try {
                const res = await fetch(this.reorderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ banners: items }),
                });
                const data = await res.json().catch(() => ({}));
                this.$dispatch('notify', { type: res.ok && data.success ? 'success' : 'error', message: res.ok && data.success ? 'บันทึกลำดับแบนเนอร์แล้ว' : 'บันทึกลำดับไม่สำเร็จ กรุณาลองใหม่' });
            } catch (e) {
                this.$dispatch('notify', { type: 'error', message: 'เชื่อมต่อไม่ได้ กรุณาลองใหม่' });
            } finally {
                this.saving = false;
            }
        },
        async toggle(id, el) {
            try {
                const res = await fetch(this.toggleUrl.replace('__ID__', id), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error('fail');
                el.checked = !!data.is_active;
                this.$dispatch('notify', { type: 'success', message: data.is_active ? 'เปิดแสดงแบนเนอร์แล้ว' : 'ปิดแบนเนอร์แล้ว' });
            } catch (e) {
                el.checked = !el.checked;
                this.$dispatch('notify', { type: 'error', message: 'เปลี่ยนสถานะไม่สำเร็จ กรุณาลองใหม่' });
            }
        }
     }">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า · หน้าร้าน</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">แบนเนอร์หน้าร้าน 🖼️</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ลาก <i class="fas fa-grip-vertical"></i> เพื่อเรียงลำดับ ระบบบันทึกให้อัตโนมัติ</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap; align-items:center;">
            <span x-show="saving" x-cloak style="font-size:12px; color:var(--ink2);"><i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...</span>
            <a href="{{ route('admin.storefront.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-gear"></i> ตั้งค่าหน้าร้าน</a>
            <a href="{{ route('admin.storefront.banners.create') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มแบนเนอร์</a>
        </div>
    </div>

    @if($banners->count() > 0)
        <div x-ref="list" style="display:flex; flex-direction:column; gap:12px;">
            @foreach($banners as $banner)
                <div class="tp-card" data-id="{{ $banner->id }}" style="padding:12px; display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
                    <span data-drag class="tp-icon-btn" style="width:36px; height:36px; cursor:grab;" title="ลากเพื่อเรียงลำดับ"><i class="fas fa-grip-vertical"></i></span>
                    <div style="width:200px; max-width:100%; aspect-ratio:16/7; border-radius:14px; overflow:hidden; flex:none; background:linear-gradient(120deg,var(--accent1),var(--accent2)); display:grid; place-items:center;">
                        @if($banner->image_url)
                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <span style="color:var(--tp-on-accent,#fff); font-weight:800; font-size:13px; padding:8px; text-align:center;">{{ $banner->title ?: 'ไม่มีรูป' }}</span>
                        @endif
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                            <span class="tp-pill tp-pill-soft">{{ $banner->location === 'homepage' ? 'หน้าแรก' : 'หมวด: '.$banner->category_slug }}</span>
                            @if($banner->badge)<span class="tp-pill tp-pill-gold">{{ $banner->badge }}</span>@endif
                            @if($banner->trashed())<span class="tp-pill" style="{{ $pill($c['bad']) }}">ถูกลบ</span>@endif
                        </div>
                        <div style="font-weight:700; font-size:14px;">{{ $banner->title ?: 'ไม่มีหัวข้อ' }}</div>
                        @if($banner->subtitle)<div style="font-size:12.5px; color:var(--ink2);">{{ $banner->subtitle }}</div>@endif
                        <div style="display:flex; gap:12px; flex-wrap:wrap; font-size:11.5px; color:var(--ink2); margin-top:5px;">
                            <span><i class="fas fa-eye"></i> <span class="tp-num">{{ number_format((int) $banner->view_count) }}</span></span>
                            <span><i class="fas fa-hand-pointer"></i> <span class="tp-num">{{ number_format((int) $banner->click_count) }}</span></span>
                            <span>CTR <span class="tp-num">{{ $banner->ctr }}%</span></span>
                            @if($banner->start_at || $banner->end_at)
                                <span><i class="fas fa-calendar"></i> {{ $banner->start_at?->format('d/m/Y') ?? '...' }} – {{ $banner->end_at?->format('d/m/Y') ?? '...' }}</span>
                            @endif
                        </div>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <label style="display:flex; align-items:center; gap:7px; font-size:12.5px; cursor:pointer;" title="เปิด/ปิดการแสดง">
                            <input type="checkbox" @checked($banner->is_active) @change="toggle({{ $banner->id }}, $event.target)" style="accent-color:var(--accent1); width:18px; height:18px;"> แสดง
                        </label>
                        <a href="{{ route('admin.storefront.banners.edit', $banner->id) }}" class="tp-icon-btn" style="width:36px; height:36px;" title="แก้ไข"><i class="fas fa-pen"></i></a>
                        <form method="POST" action="{{ route('admin.storefront.banners.destroy', $banner->id) }}"
                              @submit="if (!confirm('ลบแบนเนอร์นี้ถาวร (รวมไฟล์รูป)?')) $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tp-icon-btn" style="width:36px; height:36px; color:{{ $c['bad'] }};" title="ลบ"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="tp-card" style="text-align:center; padding:44px 16px; color:var(--ink2);">
            <i class="fas fa-images" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
            ยังไม่มีแบนเนอร์
            <div style="margin-top:12px;"><a href="{{ route('admin.storefront.banners.create') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> สร้างแบนเนอร์แรก</a></div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .tp-drag-ghost { opacity: .45; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush
