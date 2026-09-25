@extends('layouts.seller-v4')

@section('title', 'เครื่อง POS - '.($terminal->terminal_name ?? $terminal->device_name ?? 'POS Terminal'))

@php
    $posCtl = \App\Http\Controllers\Seller\SellerPosController::class;
    $tActive = $posCtl::terminalIsActive($terminal);
    $tOnline = $posCtl::terminalIsOnline($terminal);
    $tName = $terminal->terminal_name ?? $terminal->device_name ?? 'POS Terminal';
    $info = is_array($terminal->device_info ?? null) ? $terminal->device_info : [];
    $rows = [
        ['Product Key', $terminal->product_key],
        ['รุ่นเครื่อง', $terminal->device_model ?: '—'],
        ['ระบบปฏิบัติการ', $terminal->platform ?: '—'],
        ['เวอร์ชันแอป', $terminal->app_version ?: '—'],
        ['ลงทะเบียนเมื่อ', ($terminal->registered_at ? \Illuminate\Support\Carbon::parse($terminal->registered_at) : $terminal->created_at)?->format('d/m/Y H:i') ?? '—'],
        ['เห็นเครื่องล่าสุด', $terminal->last_seen_at ? \Illuminate\Support\Carbon::parse($terminal->last_seen_at)->diffForHumans() : '—'],
        ['ซิงก์ล่าสุด', $terminal->last_sync_at?->diffForHumans() ?? 'ยังไม่เคย'],
        ['IP ล่าสุด', $terminal->last_ip_address ?: '—'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="$tName" icon="🖥️" crumb="ร้านค้า · POS · เครื่อง POS" :subtitle="$terminal->apiKey ? 'ใช้ API Key: '.($terminal->apiKey->name ?: '#'.$terminal->apiKey->id) : null">
        <x-seller-kit.pill :tone="$tOnline ? 'ok' : 'muted'">{{ $tOnline ? '● ออนไลน์' : '○ ออฟไลน์' }}</x-seller-kit.pill>
        <x-seller-kit.pill :tone="$tActive ? 'ok' : 'muted'">{{ $tActive ? 'เปิดใช้' : 'ปิดอยู่' }}</x-seller-kit.pill>
        <a href="{{ route('seller.pos.terminals') }}" class="tp-btn tp-btn-sm">← เครื่อง POS ทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">ข้อมูลเครื่อง</div>
            @foreach($rows as [$rLabel, $rValue])
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px;">
                    <span style="color:var(--ink2);">{{ $rLabel }}</span>
                    <span class="tp-num" style="text-align:right; overflow-wrap:anywhere;">{{ $rValue }}</span>
                </div>
            @endforeach
            @if($terminal->notes)
                <div style="font-size:12.5px; line-height:1.6; margin-top:4px;"><span style="color:var(--ink2);">หมายเหตุ:</span> {{ $terminal->notes }}</div>
            @endif
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">จัดการเครื่อง</div>
            <div style="font-size:12.5px; color:var(--ink2); line-height:1.6;">ปิดเครื่องชั่วคราวเมื่อไม่ได้ใช้ หรือลบเครื่องที่เลิกใช้แล้วออกจากร้าน</div>
            <form method="POST" action="{{ route('seller.pos.terminals.toggle-status', $terminal) }}">
                @csrf
                <button type="submit" class="tp-btn" style="width:100%;">{{ $tActive ? '⏸️ ปิดใช้งานเครื่องนี้' : '▶️ เปิดใช้งานเครื่องนี้' }}</button>
            </form>
            <form method="POST" action="{{ route('seller.pos.terminals.destroy', $terminal) }}" onsubmit="return confirm('ลบเครื่อง POS นี้ออกจากร้าน? เครื่องต้องลงทะเบียนใหม่ด้วย API Key ถึงจะใช้ได้อีก');">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn" style="width:100%; color:var(--tp-bad, #d9534f);">🗑️ ลบเครื่องนี้</button>
            </form>
            @if($terminal->apiKey)
                <a href="{{ route('seller.pos.api-keys.show', $terminal->apiKey) }}" class="tp-btn tp-btn-sm">🔑 ดู API Key ที่ใช้</a>
            @endif
        </div>
    </div>

    @if(count($info) > 0)
        <div class="tp-card">
            <div class="tp-section-h">ข้อมูลฮาร์ดแวร์ที่เครื่องส่งมา</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:8px; margin-top:10px;">
                @foreach($info as $iKey => $iVal)
                    <div class="tp-inset-sm" style="padding:8px 12px; border-radius:12px; font-size:12px;">
                        <div style="color:var(--ink2);">{{ is_string($iKey) ? $iKey : '#'.$iKey }}</div>
                        <div class="tp-num" style="overflow-wrap:anywhere;">{{ is_scalar($iVal) ? $iVal : json_encode($iVal, JSON_UNESCAPED_UNICODE) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
