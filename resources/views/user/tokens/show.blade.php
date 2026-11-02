{{--
    รายละเอียดเหรียญ (ธีมโนวาเต็ม)
    - ข้อมูลทั้งหมด render จากฝั่งเซิร์ฟเวอร์ ($myBalance · $recentTransfers · $topHolders)
      เดิมหน้านี้ fetch API ที่ไม่ตรงกับ route จริง ยอดจึงขึ้น 0 และตารางค้าง "กำลังโหลด"
    - เหรียญที่ยังไม่ขึ้นเชนเห็นเฉพาะผู้สร้าง (TokenController::show ตรวจให้)
    - ที่อยู่กระเป๋าของสมาชิกคนอื่นแสดงแบบย่อ (0x1234…abcd) ไม่ผูกกับชื่อ/อีเมล
    - เดิมมีปุ่มซื้อ/ขาย/โอนที่เปิดกล่อง "กำลังพัฒนา" — ไม่มีระบบรองรับ จึงเอาออก
--}}
@extends('layouts.user-nova')

@section('title', $token->name.' ('.$token->symbol.')')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    $me = auth()->id();
    $isCreator = (int) $token->creator_id === (int) $me;
    $statusChip = [
        'draft' => ['warn', 'ร่าง · รอทีมงานตรวจสอบ'],
        'deploying' => ['warn', 'กำลังขึ้นเครือข่าย'],
        'deployed' => ['ok', 'ขึ้นเครือข่ายแล้ว'],
        'verified' => ['ok', 'ตรวจสอบสัญญาแล้ว'],
        'active' => ['ok', 'เปิดใช้งาน'],
        'paused' => ['warn', 'หยุดการโอนชั่วคราว'],
        'suspended' => ['bad', 'ถูกระงับ'],
        'failed' => ['bad', 'ขึ้นเครือข่ายไม่สำเร็จ'],
    ];
    [$statusTone, $statusLabel] = $statusChip[$token->status] ?? ['', $token->status];
    $mask = fn ($addr) => strlen((string) $addr) > 12 ? substr((string) $addr, 0, 6).'…'.substr((string) $addr, -4) : (string) $addr;
    // ลิงก์ภายนอกรับเฉพาะ http(s) — กัน javascript: ที่อาจหลุดมาจากข้อมูลเก่า
    $safeUrl = fn ($url) => is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
    $twitter = (string) ($token->twitter ?? '');
    $twitterUrl = $safeUrl($twitter) ?? (preg_match('/^@?([A-Za-z0-9_]{1,30})$/', $twitter, $m) ? 'https://x.com/'.$m[1] : null);
    $explorer = rtrim((string) config('tpix_pay.explorer_url'), '/');
    $links = array_filter([
        ['เว็บไซต์', $safeUrl($token->website)],
        ['Whitepaper', $safeUrl($token->whitepaper_url)],
        ['GitHub', $safeUrl($token->github)],
        ['Telegram', $safeUrl($token->telegram)],
        ['X (Twitter)', $twitterUrl],
        ['ดูบน Explorer', $token->contract_address && $explorer ? $explorer.'/token/'.$token->contract_address : null],
    ], fn ($l) => $l[1]);
    $features = [
        ['Mintable', 'สร้างเหรียญเพิ่มได้', $token->is_mintable],
        ['Burnable', 'เผาเหรียญลด supply ได้', $token->is_burnable],
        ['Pausable', 'หยุดการโอนชั่วคราวได้', $token->is_pausable],
        ['Freezable', 'ระงับบางที่อยู่ได้', $token->is_freezable],
    ];
    $typeLabels = ['transfer' => 'โอน', 'mint' => 'สร้างเพิ่ม', 'burn' => 'เผา', 'airdrop' => 'แจก', 'reward' => 'รางวัล', 'swap' => 'แลกเปลี่ยน', 'liquidity_add' => 'เพิ่มสภาพคล่อง', 'liquidity_remove' => 'ถอนสภาพคล่อง'];
    $num = fn ($n, $d = 4) => number_format((float) ($n ?? 0), $d);
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tks-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tokens.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตลาดเหรียญ</a>
            <div class="nvm-eyebrow nvm-latin">{{ $token->symbol }}</div>
            <h1 id="nvm-tks-h">{{ $token->name }}</h1>
            <p class="nvm-ph__lead">ผู้สร้าง: {{ $isCreator ? 'คุณ' : ($token->creator->name ?? 'ไม่ทราบ') }} · <span class="nvm-num">{{ $token->created_at?->format('d/m/Y') }}</span></p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $statusTone === 'ok' ? 'ok' : ($statusTone === 'bad' ? 'bad' : 'warn') }}">{{ $statusLabel }}</span>
                @if($token->is_verified)<span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ตรวจสอบแล้ว</span>@endif
                @if($token->is_featured)<span class="nvm-pill nvm-pill--gold">แนะนำ</span>@endif
            </div>
        </div>
        <div class="nvm-ph__art nvm-tx-phcoin">@include('user.tokens.partials.coin', ['token' => $token, 'size' => 'lg'])</div>
    </div>
</section>

<section class="nvm-kpis" aria-label="ข้อมูลตลาด">
    @foreach([
        ['ราคาปัจจุบัน', 'Price', $num($token->current_price_tpix).' TPIX'],
        ['มูลค่าตลาด', 'Market cap', $num($token->market_cap_tpix, 0).' TPIX'],
        ['ปริมาณ 24 ชม.', 'Volume 24h', $num($token->volume_24h_tpix, 0).' TPIX'],
        ['ผู้ถือ', 'Holders', number_format((int) ($token->holders_count ?? 0))],
    ] as [$label, $en, $value])
        <div class="nvm-card nvm-kpi">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-kpi__val--txt nvm-num">{{ $value }}</div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        @if($token->description)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-tks-ab-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">About</small><h2 id="nvm-tks-ab-h">เกี่ยวกับเหรียญ</h2></div></div>
                <p class="nvm-desc">{{ $token->description }}</p>
            </section>
        @endif

        <section class="nvm-card nvm-box" aria-labelledby="nvm-tks-d-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Details</small><h2 id="nvm-tks-d-h">รายละเอียดเหรียญ</h2></div></div>
            <dl class="nvm-kv">
                <dt>ที่อยู่สัญญา</dt>
                <dd class="nvm-tx-mono">{{ $token->contract_address ?: 'ยังไม่ขึ้นเครือข่าย' }}</dd>
                <dt>จำนวนทั้งหมด</dt><dd class="nvm-num">{{ $num($token->total_supply, 0) }} {{ $token->symbol }}</dd>
                <dt>หมุนเวียนอยู่</dt><dd class="nvm-num">{{ $num($token->circulating_supply ?? $token->total_supply, 0) }} {{ $token->symbol }}</dd>
                <dt>ทศนิยม</dt><dd class="nvm-num">{{ (int) $token->decimals }}</dd>
            </dl>
            <h3 class="nvm-label nvm-gap-t">คุณสมบัติ</h3>
            <ul class="nvm-tx-feats nvm-gap-t">
                @foreach($features as [$name, $hint, $on])
                    <li class="{{ $on ? 'is-on' : '' }}" title="{{ $hint }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $on ? 'check' : 'x' }}"/></svg>{{ $name }}<span class="nvm-sr">{{ $on ? 'เปิด' : 'ปิด' }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="nvm-card nvm-box" id="transfers" aria-labelledby="nvm-tks-tx-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Transfers</small><h2 id="nvm-tks-tx-h">ธุรกรรมล่าสุด</h2></div></div>
            @if($recentTransfers->isNotEmpty())
                <div class="nvm-table-wrap">
                    <table class="nvm-table">
                        <thead>
                            <tr><th>ประเภท</th><th>จาก</th><th>ถึง</th><th class="r">จำนวน</th><th class="r">เวลา</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransfers as $transfer)
                                <tr>
                                    <td><span class="nvm-chip">{{ $typeLabels[$transfer->transfer_type] ?? $transfer->transfer_type }}</span></td>
                                    <td class="nvm-tx-mono">{{ (int) $transfer->from_user_id === (int) $me ? 'คุณ' : $mask($transfer->from_address) }}</td>
                                    <td class="nvm-tx-mono">{{ (int) $transfer->to_user_id === (int) $me ? 'คุณ' : $mask($transfer->to_address) }}</td>
                                    <td class="r nvm-num">{{ $num($transfer->amount) }}</td>
                                    <td class="r nvm-num">{{ $transfer->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="nvm-empty"><b>ยังไม่มีธุรกรรม</b></div>
            @endif
        </section>
    </div>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-tks-b-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Your balance</small><h2 id="nvm-tks-b-h">ยอดของคุณ</h2></div></div>
            <div class="nvm-tx-big nvm-num">{{ $num($myBalance?->balance) }}<span>{{ $token->symbol }}</span></div>
            @if($myBalance && (float) $myBalance->locked_balance > 0)
                <p class="nvm-help nvm-gap-t">ล็อกไว้ <span class="nvm-num">{{ $num($myBalance->locked_balance) }}</span> {{ $token->symbol }}</p>
            @endif
            @if($myBalance?->is_frozen)
                <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ยอดของคุณถูกระงับการโอนโดยเจ้าของเหรียญ</p>
            @endif
        </aside>

        @if($isCreator && $token->status === 'draft')
            <aside class="nvm-card nvm-box" aria-labelledby="nvm-tks-dr-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Draft</small><h2 id="nvm-tks-dr-h">เหรียญของคุณอยู่ในสถานะร่าง</h2></div></div>
                <p class="nvm-desc">ทีมงานจะตรวจสอบข้อมูลก่อนนำเหรียญขึ้นเครือข่าย ระบบจะแจ้งเตือนเมื่ออนุมัติหรือมีข้อแก้ไข เหรียญที่ยังเป็นร่างจะไม่แสดงในตลาด</p>
            </aside>
        @endif

        <aside class="nvm-card nvm-box" aria-labelledby="nvm-tks-h-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Top holders</small><h2 id="nvm-tks-h-h">ผู้ถือสูงสุด</h2></div></div>
            @if($topHolders->isNotEmpty())
                <ol class="nvm-tx-list">
                    @foreach($topHolders as $holder)
                        <li>
                            <span class="nvm-tx-mono">{{ $loop->iteration }}. {{ (int) $holder->user_id === (int) $me ? 'คุณ' : $mask($holder->wallet_address) }}</span>
                            <span class="nvm-tx-amt nvm-num">{{ $num($holder->balance) }}</span>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="nvm-empty"><b>ยังไม่มีผู้ถือ</b></div>
            @endif
        </aside>

        @if(count($links))
            <aside class="nvm-card nvm-box" aria-labelledby="nvm-tks-l-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Links</small><h2 id="nvm-tks-l-h">ลิงก์</h2></div></div>
                <div class="nvm-acts">
                    @foreach($links as [$label, $url])
                        <a class="nvm-act" href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow">
                            <span class="nvm-act__ic ok"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg></span>
                            <span class="nvm-act__t"><b>{{ $label }}</b></span>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif
    </div>
</div>

</div>
@endsection
