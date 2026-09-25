@extends('layouts.seller-v4')

@section('title', 'โฆษณาจอลูกค้า (POS)')

@php
    $typeLabels = ['image' => ['รูปภาพ', 'info'], 'video' => ['วิดีโอ', 'violet'], 'promotion' => ['โปรโมชั่น', 'warn'], 'html' => ['HTML', 'muted']];

    // ข้อมูลโฆษณาสำหรับเติมฟอร์มแก้ไข (ส่งเข้า Alpine ผ่าน @js — ปลอดภัยจาก XSS)
    $adRows = $advertisements->getCollection()->map(fn ($ad) => [
        'id' => (int) $ad->id,
        'title' => (string) $ad->title,
        'description' => (string) ($ad->description ?? ''),
        'type' => (string) ($ad->type ?? 'image'),
        'duration_seconds' => (int) ($ad->duration_seconds ?? 10),
        'order' => (int) ($ad->order ?? 0),
        'is_active' => (bool) $ad->is_active,
        'update_url' => route('seller.pos.advertisements.update', $ad->id),
    ])->keyBy('id');

    $adImage = function ($ad) {
        $path = $ad->image_url;
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::url($path);
    };
@endphp

@section('content')
<div x-data="posAds(@js($adRows), @js(route('seller.pos.advertisements.store')))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="โฆษณาจอลูกค้า" icon="📺" crumb="ร้านค้า · POS"
                         subtitle="รูปภาพหรือโปรโมชั่นที่หมุนแสดงบนจอฝั่งลูกค้าของเครื่อง POS ระหว่างรอชำระเงิน">
        <button type="button" class="tp-btn tp-btn-primary" @click="openCreate()">＋ เพิ่มโฆษณา</button>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <x-seller-kit.errors />

    @if($advertisements->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px;">
            @foreach($advertisements as $ad)
                @php
                    [$typeLabel, $typeTone] = $typeLabels[$ad->type] ?? [$ad->type, 'muted'];
                    $img = $adImage($ad);
                @endphp
                <div class="tp-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
                    <div class="tp-inset-sm" style="aspect-ratio:16/9; display:grid; place-items:center; font-size:44px; overflow:hidden; border-radius:0;">
                        @if($img)
                            <img src="{{ $img }}" alt="{{ $ad->title }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <span aria-hidden="true">📺</span>
                        @endif
                    </div>
                    <div style="padding:14px 16px; display:flex; flex-direction:column; gap:8px; flex:1;">
                        <div style="font-weight:800; font-size:14.5px; overflow-wrap:anywhere;">{{ $ad->title }}</div>
                        @if($ad->description)
                            <div style="font-size:12.5px; color:var(--ink2); line-height:1.6;">{{ \Illuminate\Support\Str::limit($ad->description, 120) }}</div>
                        @endif
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <x-seller-kit.pill :tone="$typeTone">{{ $typeLabel }}</x-seller-kit.pill>
                            <x-seller-kit.pill :tone="$ad->is_active ? 'ok' : 'muted'">{{ $ad->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</x-seller-kit.pill>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--ink2); margin-top:auto; padding-top:8px; border-top:1px solid color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <span>⏱️ แสดง {{ (int) $ad->duration_seconds }} วินาที</span>
                            <span>ลำดับ {{ (int) ($ad->order ?? 0) }}</span>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="tp-btn tp-btn-sm" style="flex:1;" @click="openEdit({{ (int) $ad->id }})">✏️ แก้ไข</button>
                            <form method="POST" action="{{ route('seller.pos.advertisements.destroy', $ad->id) }}" style="flex:1;"
                                  onsubmit="return confirm('ต้องการลบโฆษณา “{{ addslashes($ad->title) }}” ใช่หรือไม่?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tp-btn tp-btn-sm" style="width:100%; color:var(--tp-bad, #d9534f);">🗑️ ลบ</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($advertisements->hasPages())
            <div>{{ $advertisements->links() }}</div>
        @endif
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="📺" title="ยังไม่มีโฆษณา" text="เพิ่มรูปโปรโมชั่นเพื่อแสดงบนจอลูกค้าของเครื่อง POS ช่วยเพิ่มยอดขายสินค้าที่อยากดัน">
                <button type="button" class="tp-btn tp-btn-primary tp-btn-sm" @click="openCreate()">＋ เพิ่มโฆษณาแรก</button>
            </x-seller-kit.empty>
        </div>
    @endif

    {{-- หน้าต่างเพิ่ม/แก้ไขโฆษณา — ตัวนอกมีแค่ x-show, ตัวในเป็น flex จัดกลาง --}}
    <div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false">
      <div style="position:fixed; inset:0; z-index:90; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; padding:16px;"
           @click.self="open = false" role="dialog" aria-modal="true" aria-labelledby="ad-modal-title">
        <form method="POST" :action="action" enctype="multipart/form-data" class="tp-card"
              style="width:100%; max-width:560px; max-height:92vh; overflow-y:auto; padding:22px; background:var(--surf); display:flex; flex-direction:column; gap:14px;"
              @submit="saving = true">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

            <div id="ad-modal-title" style="font-size:18px; font-weight:800;" x-text="editing ? '✏️ แก้ไขโฆษณา' : '＋ เพิ่มโฆษณาใหม่'"></div>

            <div>
                <label for="ad-title" style="font-size:12.5px; font-weight:700;">ชื่อโฆษณา <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="ad-title" type="text" name="title" x-model="form.title" required maxlength="255" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="ad-desc" style="font-size:12.5px; font-weight:700;">คำอธิบาย</label>
                <textarea id="ad-desc" name="description" x-model="form.description" rows="3" class="tp-input" style="margin-top:6px; resize:vertical;"></textarea>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
                <div>
                    <label for="ad-type" style="font-size:12.5px; font-weight:700;">ประเภท</label>
                    <select id="ad-type" name="type" x-model="form.type" required class="tp-input" style="margin-top:6px;">
                        <option value="image">รูปภาพ</option>
                        <option value="video">วิดีโอ</option>
                        <option value="promotion">โปรโมชั่น</option>
                        <option value="html">HTML</option>
                    </select>
                </div>
                <div>
                    <label for="ad-duration" style="font-size:12.5px; font-weight:700;">แสดงนาน (วินาที)</label>
                    <input id="ad-duration" type="number" name="duration_seconds" x-model.number="form.duration_seconds" min="1" max="300" required class="tp-input tp-num" style="margin-top:6px;">
                </div>
                <div>
                    <label for="ad-order" style="font-size:12.5px; font-weight:700;">ลำดับ</label>
                    <input id="ad-order" type="number" name="order" x-model.number="form.order" min="0" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <div>
                <label for="ad-image" style="font-size:12.5px; font-weight:700;">รูปภาพ <span style="font-weight:500; color:var(--ink2);" x-text="editing ? '(เว้นว่าง = ใช้รูปเดิม)' : ''"></span></label>
                <input id="ad-image" type="file" name="image" accept="image/*" class="tp-input" style="margin-top:6px;">
                <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">ไฟล์ภาพไม่เกิน 5MB แนะนำอัตราส่วน 16:9</div>
            </div>
            <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" style="width:18px; height:18px; accent-color:var(--accent1);">
                เปิดใช้งานโฆษณานี้
            </label>

            <div style="display:grid; grid-template-columns:1fr 1.4fr; gap:10px; margin-top:4px;">
                <button type="button" class="tp-btn" @click="open = false">ยกเลิก</button>
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">
                    <span x-text="saving ? 'กำลังบันทึก…' : 'บันทึก'"></span>
                </button>
            </div>
        </form>
      </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // จัดการหน้าต่างเพิ่ม/แก้ไขโฆษณาจอลูกค้า
    function posAds(rows, storeUrl) {
        const blank = { title: '', description: '', type: 'image', duration_seconds: 10, order: 0, is_active: true };
        return {
            rows: rows,
            open: false,
            editing: false,
            saving: false,
            action: storeUrl,
            form: Object.assign({}, blank),
            openCreate() {
                this.editing = false;
                this.action = storeUrl;
                this.form = Object.assign({}, blank);
                this.saving = false;
                this.open = true;
            },
            openEdit(id) {
                const row = this.rows[id];
                if (!row) return;
                this.editing = true;
                this.action = row.update_url;
                this.form = Object.assign({}, blank, row);
                this.saving = false;
                this.open = true;
            },
        };
    }
</script>
@endpush
