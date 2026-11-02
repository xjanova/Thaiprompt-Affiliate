{{--
    ตลาดเหรียญลูกบน TPIX (ธีมโนวาเต็ม)
    - ค้นหา/กรอง/เรียงด้วยฟอร์ม GET ธรรมดา (search · verified · sort) ตรงกับ TokenController::index
    - "เหรียญของฉัน" แสดงเหรียญที่สมาชิกสร้างเองทุกสถานะ (ร่างที่รอตรวจไม่ขึ้นในตลาด)
    - เดิมมีปุ่มหมวดหมู่ DeFi/GameFi ฯลฯ แต่ตารางไม่มีคอลัมน์หมวดหมู่และ controller ไม่กรองให้ จึงเอาออก
--}}
@extends('layouts.user-nova')

@section('title', 'ตลาดเหรียญ TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    $sortOptions = [
        'market_cap' => 'มูลค่าตลาดสูงสุด',
        'volume' => 'ปริมาณซื้อขาย 24 ชม. สูงสุด',
        'price' => 'ราคาสูงสุด',
        'holders' => 'ผู้ถือมากที่สุด',
    ];
    $sort = array_key_exists(request('sort'), $sortOptions) ? request('sort') : 'market_cap';
    $hasFilter = request()->filled('search') || request()->filled('verified');
    $statusChip = [
        'draft' => ['', 'ร่าง · รอตรวจสอบ'],
        'deploying' => ['warn', 'กำลังขึ้นเครือข่าย'],
        'deployed' => ['ok', 'ขึ้นเครือข่ายแล้ว'],
        'verified' => ['ok', 'ตรวจสอบสัญญาแล้ว'],
        'active' => ['ok', 'เปิดใช้งาน'],
        'paused' => ['warn', 'หยุดชั่วคราว'],
        'suspended' => ['bad', 'ถูกระงับ'],
        'failed' => ['bad', 'ขึ้นเครือข่ายไม่สำเร็จ'],
    ];
    $tpixNum = fn ($n, $d = 4) => number_format((float) ($n ?? 0), $d);
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tki-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Token market</div>
            <h1 id="nvm-tki-h">ตลาดเหรียญ TPIX</h1>
            <p class="nvm-ph__lead">ค้นหาเหรียญลูกบน TPIX Blockchain หรือสร้างเหรียญของคุณเอง</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.tokens.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างเหรียญของคุณ</a>
                <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.tokens.tutorial') }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>คู่มือสร้างเหรียญ</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<form class="nvm-card nvm-box" method="GET" action="{{ route('user.tokens.index') }}" aria-label="ค้นหาเหรียญ">
    <div class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="tki-search">ค้นหา</label>
            <input id="tki-search" class="nvm-input" type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="ชื่อหรือสัญลักษณ์เหรียญ">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="tki-sort">เรียงตาม</label>
            <select id="tki-sort" class="nvm-input" name="sort">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="nvm-check">
            <input type="checkbox" name="verified" value="1" @checked(request()->filled('verified'))>
            <span>เฉพาะเหรียญที่ตรวจสอบสัญญาแล้ว</span>
        </label>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ค้นหา</button>
            @if($hasFilter)
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.tokens.index') }}">ล้าง</a>
            @endif
        </div>
    </div>
</form>

@if($myTokens->isNotEmpty())
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tki-my-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">My tokens</small><h2 id="nvm-tki-my-h">เหรียญที่ฉันสร้าง</h2></div>
            <span class="nvm-chip nvm-num">{{ number_format($myTokens->count()) }} เหรียญ</span>
        </div>
        <ul class="nvm-tx-list">
            @foreach($myTokens as $token)
                @php [$tone, $label] = $statusChip[$token->status] ?? ['', $token->status]; @endphp
                <li>
                    <a class="nvm-tx-head" href="{{ route('user.tokens.show', $token->id) }}">
                        <span class="nvm-tx-coin">{{ mb_substr((string) $token->symbol, 0, 1) }}</span>
                        <div><b>{{ $token->name }}</b><small>{{ $token->symbol }} · <span class="nvm-num">{{ $token->created_at?->format('d/m/Y') }}</span></small></div>
                    </a>
                    <span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif

@if($featuredTokens->isNotEmpty())
    <section aria-labelledby="nvm-tki-ft-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Featured</small><h2 id="nvm-tki-ft-h">เหรียญแนะนำ</h2></div></div>
        <div class="nvm-tx-grid">
            @foreach($featuredTokens as $token)
                @include('user.tokens.partials.card', ['token' => $token, 'featured' => true])
            @endforeach
        </div>
    </section>
@endif

<section aria-labelledby="nvm-tki-all-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">All tokens</small><h2 id="nvm-tki-all-h">เหรียญทั้งหมด</h2></div>
        <span class="nvm-chip nvm-num">{{ number_format($tokens->total()) }} เหรียญ</span>
    </div>
    @if($tokens->isNotEmpty())
        <div class="nvm-tx-grid">
            @foreach($tokens as $token)
                @include('user.tokens.partials.card', ['token' => $token, 'featured' => false])
            @endforeach
        </div>
        @if($tokens->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $tokens->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-card nvm-box">
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>{{ $hasFilter ? 'ไม่พบเหรียญตามคำค้นนี้' : 'ยังไม่มีเหรียญในตลาด' }}</b>
                {{ $hasFilter ? 'ลองเปลี่ยนคำค้นหาหรือตัวกรอง' : 'เป็นคนแรกที่สร้างเหรียญบน TPIX' }}
                <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.tokens.create') }}">สร้างเหรียญ</a></div>
            </div>
        </div>
    @endif
</section>

</div>
@endsection
