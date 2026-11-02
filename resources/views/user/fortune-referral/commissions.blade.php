{{--
    คอมมิชชั่นดูดวง (ธีมโนวาเต็ม) — FortuneReferralDashboardController::commissions
    - $commissions = รายการของสมาชิกคนนี้เท่านั้น (forUser) แบ่งหน้า 15 แถว
    - $stats = ยอดรวม / L1 / L2 / เดือนนี้ · $levelFilter = all | 1 | 2
--}}
@extends('layouts.user-nova')

@section('title', 'คอมมิชชั่นดูดวง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/fortune-referral.css') }}?v={{ filemtime(public_path('theme-nova/member/fortune-referral.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);

    $kpis = [
        ['รวมทั้งหมด', 'Total', $baht($stats['total']), 'images/nova/deco/coins.webp', 'ทุกชั้นทุกสถานะ'],
        ['Level 1 สายตรง', 'Direct', $baht($stats['level1']), 'images/nova/deco/crystal.webp', 'เพื่อนที่คุณชวนเอง'],
        ['Level 2 ชั้นหลาน', 'Second level', $baht($stats['level2']), 'images/nova/deco/lotus.webp', 'เพื่อนของเพื่อน'],
        ['เดือนนี้', 'This month', $baht($stats['this_month']), 'images/nova/deco/scroll.webp', now()->locale('th')->translatedFormat('F Y')],
    ];

    $tabs = [
        'all' => 'ทั้งหมด',
        '1' => 'Level 1 สายตรง',
        '2' => 'Level 2 ชั้นหลาน',
    ];

    // สถานะ → [โทนชิป, ข้อความ]
    $statusChip = [
        'paid' => ['ok', 'จ่ายแล้ว'],
        'pending' => ['warn', 'รอดำเนินการ'],
        'approved' => ['info', 'อนุมัติ'],
        'rejected' => ['bad', 'ปฏิเสธ'],
    ];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-fr-c-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.genealogy'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.genealogy') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>สายงานของฉัน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Fortune referral</div>
            <h1 id="nvm-fr-c-h">คอมมิชชั่นดูดวง</h1>
            <p class="nvm-ph__lead">รายได้จากการแนะนำเพื่อนมาดูดวง — Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)</p>
            <div class="nvm-pills">
                <a class="nvm-pill nvm-pill--gold" href="{{ route('user.fortune-referral.recruit') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ชวนเพื่อนดูดวง</a>
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.fortune-referral.tree') }}"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>ผังสายงานดูดวง</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/tarot.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุปคอมมิชชั่นดูดวง">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi nvm-rv">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-fr-list-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Commissions</small><h2 id="nvm-fr-list-h">รายการคอมมิชชั่น</h2></div>
        <span class="nvm-help">{{ number_format($commissions->total()) }} รายการ</span>
    </div>

    <nav class="nvm-tabs nvm-fr-tabs" aria-label="กรองตามชั้น">
        @foreach($tabs as $key => $label)
            <a href="{{ route('user.fortune-referral.commissions', ['level' => $key]) }}"
               class="{{ $levelFilter === (string) $key ? 'is-on' : '' }}"
               @if($levelFilter === (string) $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @if($commissions->isNotEmpty())
        <ul class="nvm-txs">
            @foreach($commissions as $commission)
                @php
                    $from = $commission->fromUser;
                    $fromName = $from?->name ?? 'ไม่ทราบ';
                    $avatar = $from?->profile_picture_url;
                    [$tone, $statusText] = $statusChip[$commission->status] ?? ['bad', 'ปฏิเสธ'];
                @endphp
                <li class="nvm-tx nvm-fr-tx">
                    <span class="nvm-avatar nvm-fr-av" aria-hidden="true">
                        {{ mb_substr($fromName, 0, 1) }}
                        @if($avatar)<img src="{{ $avatar }}" alt="" loading="lazy" onerror="this.remove()">@endif
                    </span>
                    <div class="nvm-tx__t">
                        <b>{{ $fromName }}</b>
                        <div class="nvm-fr-meta">
                            @if($commission->level === 1)
                                <span class="nvm-chip nvm-chip--ok">L1 สายตรง</span>
                            @else
                                <span class="nvm-chip nvm-chip--warn">L2 หลาน</span>
                            @endif
                            <span>บิล #{{ $commission->fortune_reading_id }}</span>
                            <span>· ราคาดูดวง <span class="nvm-num">{{ number_format((float) $commission->reading_price, 2) }}</span> บาท</span>
                            <span>· {{ $commission->commission_type === 'fixed' ? 'คงที่' : $commission->commission_rate.'%' }}</span>
                        </div>
                    </div>
                    <div class="nvm-tx__r">
                        <span class="nvm-tx__amt nvm-num {{ $commission->status === 'rejected' ? 'is-void' : 'is-in' }}">+{{ number_format((float) $commission->amount, 2) }} บาท</span>
                        <span class="nvm-chip nvm-chip--{{ $tone }}">{{ $statusText }}</span>
                        <time class="nvm-num" datetime="{{ $commission->created_at?->toIso8601String() }}">{{ $commission->created_at?->format('d/m/Y H:i') }}</time>
                    </div>
                </li>
            @endforeach
        </ul>

        @if($commissions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $commissions->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีคอมมิชชั่นดูดวง</b>
            @if($levelFilter !== 'all')
                ไม่มีรายการในชั้นนี้ ลองดูแท็บ "ทั้งหมด"
            @else
                ชวนเพื่อนมาดูดวง แล้วค่าแนะนำจะแสดงที่นี่
            @endif
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.fortune-referral.recruit') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ชวนเพื่อนมาดูดวง</a></div>
        </div>
    @endif
</section>

</div>
@endsection
