@extends('layouts.admin-v4')

@section('title', 'ร้านแนะนำหน้าแรก')

@php
    $bad = 'var(--tp-bad,#d9534f)';
    $warn = 'var(--tp-warn,#e0a52e)';
@endphp

@section('content')
{{-- ⭐ ร้านแนะนำหน้าแรก (GAP-20) — เดิมใช้ Bootstrap + ปลั๊กอินลากวางรุ่นเก่าที่ layout ไม่ได้โหลด → ย้ายเป็น V4 + SortableJS --}}
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{
        saving: false,
        dirty: false,
        orderUrl: @js(route('admin.featured-stores.update-order')),
        init() {
            const el = this.$refs.list;
            if (!el || typeof Sortable === 'undefined') return;
            Sortable.create(el, {
                animation: 160,
                handle: '[data-drag]',
                ghostClass: 'tp-drag-ghost',
                onEnd: () => { this.renumber(); this.save(); },
            });
        },
        renumber() {
            this.$refs.list.querySelectorAll('[data-id]').forEach((node, i) => {
                const badge = node.querySelector('[data-rank]');
                if (badge) badge.textContent = i + 1;
            });
        },
        async save() {
            const order = {};
            this.$refs.list.querySelectorAll('[data-id]').forEach((node, i) => { order[node.dataset.id] = i + 1; });
            this.saving = true;
            try {
                const res = await fetch(this.orderUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ order }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error('fail');
                this.$dispatch('notify', { type: 'success', message: data.message || 'บันทึกลำดับแล้ว' });
            } catch (e) {
                this.$dispatch('notify', { type: 'error', message: 'บันทึกลำดับไม่สำเร็จ กรุณาลองใหม่' });
            } finally {
                this.saving = false;
            }
        }
     }">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า · หน้าแรก</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ร้านแนะนำหน้าแรก ⭐</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เลือกร้านที่จะแสดงบนหน้าแรก แล้วลาก <i class="fas fa-grip-vertical"></i> เพื่อเรียงลำดับ (บันทึกอัตโนมัติ)</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap; align-items:center;">
            <span x-show="saving" x-cloak style="font-size:12px; color:var(--ink2);"><i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...</span>
            <a href="{{ route('admin.storefront.vendor-stores.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-store"></i> ร้านค้าทั้งหมด</a>
            <a href="{{ route('admin.storefront.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-gear"></i> ตั้งค่าหน้าร้าน</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr)); gap:16px; align-items:start;">

        {{-- ===== ร้านที่แสดงอยู่ ===== --}}
        <div class="tp-card">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px;">
                <div class="tp-section-h">แสดงบนหน้าแรก <span class="tp-pill tp-pill-gold tp-num">{{ $featuredStores->count() }}</span></div>
                @if($featuredStores->count() > 1)
                    <button type="button" class="tp-btn tp-btn-sm" @click="save()" :disabled="saving"><i class="fas fa-floppy-disk"></i> บันทึกลำดับ</button>
                @endif
            </div>

            @if($featuredStores->count() > 0)
                <div x-ref="list" style="display:flex; flex-direction:column; gap:10px;">
                    @foreach($featuredStores as $store)
                        <div data-id="{{ $store->id }}" class="tp-well" style="padding:10px 12px; border-radius:15px; display:flex; align-items:center; gap:10px;">
                            <span data-drag class="tp-icon-btn" style="width:34px; height:34px; cursor:grab;" title="ลากเพื่อเรียงลำดับ"><i class="fas fa-grip-vertical"></i></span>
                            <span data-rank class="tp-num" style="width:22px; text-align:center; font-weight:800; color:var(--deep1);">{{ $loop->iteration }}</span>
                            @if($store->logo_url)
                                <img src="{{ $store->logo_url }}" alt="" loading="lazy" style="width:42px; height:42px; border-radius:12px; object-fit:cover; flex:none;">
                            @else
                                <span class="tp-tile" style="width:42px; height:42px; font-weight:800;">{{ mb_strtoupper(mb_substr($store->store_name ?: '?', 0, 1)) }}</span>
                            @endif
                            <div style="flex:1; min-width:0;">
                                <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" style="font-weight:700; color:var(--ink); text-decoration:none; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $store->store_name }}</a>
                                <div style="font-size:11.5px; color:var(--ink2);">
                                    <span style="color:{{ $warn }};">★</span> <span class="tp-num">{{ number_format((float) $store->rating_average, 1) }}</span>
                                    · <span class="tp-num">{{ number_format((int) ($store->products_count ?? $store->total_products)) }}</span> สินค้า
                                    @if(! $store->is_active || in_array($store->status, ['suspended', 'closed'], true))
                                        · <span style="color:{{ $bad }};">ร้านปิดอยู่ (ไม่แสดงบนหน้าแรก)</span>
                                    @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.featured-stores.remove', $store->id) }}"
                                  @submit="if (!confirm(@js('นำร้าน '.$store->store_name.' ออกจากหน้าแรก?'))) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tp-icon-btn" style="width:34px; height:34px; color:{{ $bad }};" title="นำออก"><i class="fas fa-xmark"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; color:var(--ink2); padding:36px 0; font-size:13px;">
                    <i class="fas fa-store" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                    ยังไม่มีร้านแนะนำ — เลือกจากรายการด้านขวา
                </div>
            @endif
        </div>

        {{-- ===== ร้านที่เลือกได้ ===== --}}
        <div class="tp-card" x-data="{ q: '' }">
            <div class="tp-section-h" style="margin-bottom:10px;">ร้านที่เลือกได้ <span class="tp-pill tp-pill-soft tp-num">{{ $availableStores->count() }}</span></div>
            <div style="font-size:11.5px; color:var(--ink2); margin-bottom:10px;">แสดงเฉพาะร้านที่เปิดขายและยืนยันแล้ว</div>
            @if($availableStores->count() > 5)
                <input type="text" x-model="q" class="tp-input" placeholder="ค้นหาชื่อร้าน" style="margin-bottom:10px;">
            @endif
            @if($availableStores->count() > 0)
                <div style="display:flex; flex-direction:column; gap:8px; max-height:620px; overflow-y:auto; padding:2px;">
                    @foreach($availableStores as $store)
                        <div class="tp-well flex" style="padding:10px 12px; border-radius:15px; align-items:center; gap:10px;"
                             x-show="q === '' || @js(mb_strtolower($store->store_name)).includes(q.toLowerCase())">
                            @if($store->logo_url)
                                <img src="{{ $store->logo_url }}" alt="" loading="lazy" style="width:40px; height:40px; border-radius:12px; object-fit:cover; flex:none;">
                            @else
                                <span class="tp-tile" style="width:40px; height:40px; font-weight:800;">{{ mb_strtoupper(mb_substr($store->store_name ?: '?', 0, 1)) }}</span>
                            @endif
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $store->store_name }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">
                                    <span style="color:{{ $warn }};">★</span> <span class="tp-num">{{ number_format((float) $store->rating_average, 1) }}</span>
                                    · <span class="tp-num">{{ number_format((int) ($store->products_count ?? $store->total_products)) }}</span> สินค้า
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.featured-stores.add', $store->id) }}" x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary" :disabled="busy"><i class="fas fa-plus"></i> เพิ่ม</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; color:var(--ink2); padding:36px 0; font-size:13px;">
                    <i class="fas fa-circle-check" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                    ไม่มีร้านอื่นที่เลือกได้ในตอนนี้
                </div>
            @endif
        </div>
    </div>
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
