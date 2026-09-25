{{--
 | ฟอร์มที่ต้องยืนยันก่อนส่ง (ยกเลิก/ลบ/เรียกไรเดอร์ ฯลฯ) — กล่องยืนยันธีม V4 แทน window.confirm
 | กันกดซ้ำ: ส่งแล้วปุ่มยืนยันถูกล็อก
 | กล่องยืนยันถูกย้ายไปไว้ใต้ body (x-teleport) เพื่อไม่ให้ติดกรอบการ์ดที่มี backdrop-filter
 |
 | ใช้งาน:
 |   <x-seller-v4.confirm-form :action="route('...')" method="DELETE" title="ลบสินค้า?" message="..." confirm-label="ลบ" danger>
 |       ช่องกรอก + ปุ่ม submit
 |   </x-seller-v4.confirm-form>
 --}}
@props([
    'action',
    'method' => 'POST',
    'title' => 'ยืนยันการทำรายการ',
    'message' => 'ต้องการดำเนินการต่อหรือไม่?',
    'confirmLabel' => 'ยืนยัน',
    'danger' => false,
    'enctype' => null,
])

@php
    $httpMethod = strtoupper((string) $method);
    $formId = $attributes->get('id') ?: 'sv4f-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
@endphp

<form action="{{ $action }}" method="POST" id="{{ $formId }}" @if($enctype) enctype="{{ $enctype }}" @endif
      x-data="{ ask: false, busy: false }"
      @submit.prevent="if (!busy) ask = true"
      {{ $attributes->except('id') }}>
    @csrf
    @if($httpMethod !== 'POST')
        @method($httpMethod)
    @endif

    {{ $slot }}

    <template x-teleport="body">
        {{-- display อยู่ในคลาส (grid) ไม่ใช่ inline: x-show ลบ display ของ inline style ทิ้งตอนแสดง → กล่องจะไม่อยู่กลางจอ --}}
        <div x-show="ask" x-cloak x-transition.opacity
             @keydown.escape.window="ask = false"
             class="grid"
             style="position:fixed; inset:0; z-index:120; place-items:center; padding:16px; background:rgba(0,0,0,.45); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px);">
            <div class="tp-card" role="dialog" aria-modal="true" @click.outside="ask = false"
                 style="width:100%; max-width:420px; padding:22px;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <span style="font-size:26px; line-height:1;">{{ $danger ? '⚠️' : '❓' }}</span>
                    <div style="min-width:0;">
                        <div style="font-weight:800; font-size:16px; color:var(--ink);">{{ $title }}</div>
                        <div style="font-size:13px; color:var(--ink2); margin-top:6px; line-height:1.6;">{{ $message }}</div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px; flex-wrap:wrap;">
                    <button type="button" class="tp-btn" @click="ask = false">ไม่ใช่ กลับไป</button>
                    <button type="button" class="tp-btn {{ $danger ? '' : 'tp-btn-primary' }}"
                            @if($danger) style="color:var(--tp-on-accent, #fff); background:var(--tp-bad, #d9534f);" @endif
                            :disabled="busy"
                            @click="busy = true; ask = false; document.getElementById('{{ $formId }}').submit()">
                        <span x-show="!busy">{{ $confirmLabel }}</span>
                        <span x-show="busy" x-cloak>กำลังดำเนินการ…</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</form>
