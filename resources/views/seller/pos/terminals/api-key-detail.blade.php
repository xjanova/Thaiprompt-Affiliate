@extends('layouts.seller-v4')

@section('title', 'API Key - '.($apiKey->name ?: 'POS'))

@php
    $posCtl = \App\Http\Controllers\Seller\SellerPosController::class;
    $keyTone = ! $apiKey->is_active ? 'muted' : ($apiKey->is_blocked ? 'bad' : (($apiKey->expires_at && $apiKey->expires_at->isPast()) ? 'warn' : 'ok'));
    // แสดง Key แบบปิดบางส่วนเท่านั้น (Key เต็มแสดงครั้งเดียวตอนสร้าง)
    $maskedKey = $apiKey->key ? substr($apiKey->key, 0, 6).'••••••••••'.substr($apiKey->key, -4) : '—';
    $terminals = $apiKey->terminals ?? collect();
    $rows = [
        ['สร้างเมื่อ', optional($apiKey->created_at)->format('d/m/Y H:i')],
        ['ใช้งานล่าสุด', $apiKey->last_used_at?->diffForHumans() ?? 'ยังไม่เคยใช้'],
        ['หมดอายุ', $apiKey->expires_at ? $apiKey->expires_at->format('d/m/Y') : 'ไม่มีวันหมดอายุ'],
        ['จำกัดการเรียก', $apiKey->rate_limit ? number_format($apiKey->rate_limit).' ครั้ง/นาที' : 'ค่าเริ่มต้นของระบบ'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="$apiKey->name ?: 'API Key'" icon="🔑" crumb="ร้านค้า · POS · เครื่อง POS" :subtitle="$apiKey->description">
        <x-seller-kit.pill :tone="$keyTone">{{ $apiKey->getStatusText() }}</x-seller-kit.pill>
        <a href="{{ route('seller.pos.terminals') }}" class="tp-btn tp-btn-sm">← เครื่อง POS ทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">รายละเอียด Key</div>
            <div class="tp-inset-sm" style="padding:10px 14px; border-radius:12px;">
                <div style="font-size:11px; color:var(--ink2);">API Key</div>
                <code class="tp-num" style="font-size:14px; font-weight:700;">{{ $maskedKey }}</code>
            </div>
            @foreach($rows as [$rLabel, $rValue])
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px;"><span style="color:var(--ink2);">{{ $rLabel }}</span><span style="text-align:right;">{{ $rValue }}</span></div>
            @endforeach
            @if($apiKey->is_blocked && $apiKey->blocked_reason)
                <div style="font-size:12.5px; color:var(--tp-bad, #d9534f);">เหตุผลที่บล็อก: {{ $apiKey->blocked_reason }}</div>
            @endif
            <div style="font-size:11.5px; color:var(--ink2); line-height:1.6;">ลืม Key? สร้าง Key ใหม่ที่หน้าเครื่อง POS แล้วบล็อก/ลบ Key เดิม เพื่อความปลอดภัย</div>
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">จัดการ</div>
            <form method="POST" action="{{ route('seller.pos.api-keys.toggle-block', $apiKey) }}" style="display:flex; flex-direction:column; gap:8px;"
                  onsubmit="return confirm('{{ $apiKey->is_blocked ? 'ปลดบล็อก API Key นี้?' : 'บล็อก API Key นี้? เครื่องที่ใช้ Key นี้จะซิงก์ไม่ได้ทันที' }}');">
                @csrf
                @unless($apiKey->is_blocked)
                    <label for="block-reason" style="font-size:12.5px; font-weight:700;">เหตุผลที่บล็อก (ไม่บังคับ)</label>
                    <input id="block-reason" type="text" name="reason" maxlength="255" placeholder="เช่น เครื่องหาย / พนักงานลาออก" class="tp-input">
                @endunless
                <button type="submit" class="tp-btn" style="color:{{ $apiKey->is_blocked ? 'var(--tp-ok, #4f9a74)' : 'var(--tp-warn, #c98a1b)' }};">{{ $apiKey->is_blocked ? '✅ ปลดบล็อก Key นี้' : '⛔ บล็อก Key นี้' }}</button>
            </form>
            <form method="POST" action="{{ route('seller.pos.api-keys.destroy', $apiKey) }}"
                  onsubmit="return confirm('ลบ API Key นี้ถาวร? เครื่อง POS ที่ผูกกับ Key นี้ ({{ $terminals->count() }} เครื่อง) จะถูกลบด้วย');">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn" style="width:100%; color:var(--tp-bad, #d9534f);">🗑️ ลบ API Key</button>
            </form>
        </div>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">🖥️ เครื่องที่ใช้ Key นี้ ({{ number_format($terminals->count()) }})</div>
        @forelse($terminals as $terminal)
            @php
                $tActive = $posCtl::terminalIsActive($terminal);
                $tOnline = $posCtl::terminalIsOnline($terminal);
            @endphp
            <a href="{{ route('seller.pos.terminals.show', $terminal) }}" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); text-decoration:none; color:var(--ink);">
                <span class="tp-tile" style="width:38px; height:38px; font-size:17px;" aria-hidden="true">🖥️</span>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; font-size:13.5px;">{{ $terminal->terminal_name ?? $terminal->device_name ?? 'POS Terminal' }}</div>
                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $terminal->product_key }}</div>
                </div>
                <x-seller-kit.pill :tone="$tOnline ? 'ok' : 'muted'">{{ $tOnline ? '● ออนไลน์' : '○ ออฟไลน์' }}</x-seller-kit.pill>
                <x-seller-kit.pill :tone="$tActive ? 'ok' : 'muted'">{{ $tActive ? 'เปิดใช้' : 'ปิดอยู่' }}</x-seller-kit.pill>
            </a>
        @empty
            <x-seller-kit.empty icon="🖥️" title="ยังไม่มีเครื่องใช้ Key นี้" text="วาง Key ในโปรแกรม POS แล้วกด Activate" />
        @endforelse
    </div>
</div>
@endsection
