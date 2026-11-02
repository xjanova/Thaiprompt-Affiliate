{{--
    ทีมแนะนำตรงของฉัน (ธีมโนวาเต็ม) — MlmDashboardController::team
    ตัวแปร: $member|null, $directReferrals (paginator · with user.currentRank), $teamStats|null, $notMlmMember?
    🐛 หน้าเดิม: โชว์อีเมลของลูกทีมทุกคน (ข้อมูลส่วนตัวของคนอื่น — เอาออกแล้ว), สถิตินับจากแค่หน้าปัจจุบัน
       และนับสถานะ "pending" ที่ไม่มีใน mlm_members.status, อ่าน $referral->rank ที่ไม่มีอยู่จริง (ยศว่างตลอด)
--}}
@extends('layouts.user-nova')

@section('title', 'ทีมของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $notMember = ! empty($notMlmMember);
    $statusChip = [
        'active' => ['ok', 'ใช้งาน'],
        'inactive' => ['', 'ไม่ใช้งาน'],
        'suspended' => ['bad', 'ระงับ'],
    ];
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page">

<section class="nvm-ph" aria-labelledby="nvm-mlt-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if(! $notMember && $nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">My team</div>
            <h1 id="nvm-mlt-h">ทีมของฉัน</h1>
            <p class="nvm-ph__lead">สมาชิกที่สมัครผ่านการแนะนำของคุณโดยตรง</p>
            @if(! $notMember)
                <div class="nvm-pills">
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>ทีมทั้งหมด <span class="nvm-num">{{ number_format((int) $member->total_team_members) }}</span> คน</span>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/mascot/welcome.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($notMember)
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>คุณยังไม่ได้เป็นสมาชิก MLM</b>
            เมื่อเข้าร่วมแผน MLM แล้ว รายชื่อทีมที่คุณแนะนำจะแสดงที่นี่
            @if($nvR('user.dashboard'))
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>กลับหน้าหลัก</a></div>
            @endif
        </div>
    </section>
@else
    <section class="nvm-kpis" aria-label="สรุปทีม">
        @foreach([
            ['แนะนำตรง', 'Direct', $teamStats['total'], 'images/nova/mascot/welcome.webp'],
            ['ใช้งานอยู่', 'Active', $teamStats['active'], 'images/nova/deco/lotus.webp'],
            ['ไม่ใช้งาน/ระงับ', 'Inactive', $teamStats['inactive'], 'images/nova/wallet/hourglass.webp'],
            ['สมัครเดือนนี้', 'This month', $teamStats['this_month'], 'images/nova/deco/garland.webp'],
        ] as [$label, $en, $value, $img])
            <div class="nvm-card nvm-kpi nvm-rv">
                <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
                <div class="nvm-kpi__lbl">{{ $label }}</div>
                <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
                <div class="nvm-kpi__val nvm-num">{{ number_format((int) $value) }}</div>
                <div class="nvm-kpi__foot"><small>คน</small></div>
            </div>
        @endforeach
    </section>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-mlt-l-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Direct referrals</small><h2 id="nvm-mlt-l-h">รายชื่อสมาชิกในทีม</h2></div>
            @if($nvR('user.mlm.referral'))
                <a href="{{ route('user.mlm.referral') }}">ชวนเพิ่ม<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>

        @if($directReferrals->count() > 0)
            <div class="nvm-table-wrap">
                <table class="nvm-table">
                    <thead>
                        <tr><th>#</th><th>สมาชิก</th><th>วันที่เข้าร่วม</th><th>สถานะ</th><th>ยศ</th></tr>
                    </thead>
                    <tbody>
                        @foreach($directReferrals as $index => $referral)
                            @php
                                $name = $referral->user?->name ?? 'สมาชิก';
                                [$tone, $label] = $statusChip[$referral->status] ?? ['', $referral->status];
                                $refRank = $referral->user?->currentRank;
                                $refRetention = $referral->status === 'active'
                                    ? \App\Helpers\MlmRetentionHelper::getRetentionStatus($referral)
                                    : null;
                            @endphp
                            <tr>
                                <td class="nvm-num nvm-ml-mute">{{ $directReferrals->firstItem() + $index }}</td>
                                <td>
                                    <div class="nvm-ml-person">
                                        <span class="nvm-avatar">{{ mb_strtoupper(mb_substr(trim($name) ?: 'U', 0, 1)) }}</span>
                                        <span><b>{{ $name }}</b><small class="nvm-num">{{ $referral->member_code }}</small></span>
                                    </div>
                                </td>
                                <td class="nvm-num nvm-ml-nowrap">{{ $referral->created_at?->format('d/m/Y') }}<small class="nvm-ml-sub">{{ $referral->created_at?->locale('th')->diffForHumans() }}</small></td>
                                <td class="nvm-ml-nowrap">
                                    <span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $label }}</span>
                                    @if($refRetention && ($refRetention['retention_enabled'] ?? false) && ($refRetention['status'] ?? 'active') !== 'active')
                                        @if($refRetention['status'] === 'grace_period')
                                            <span class="nvm-chip nvm-chip--warn">ผ่อนผัน</span>
                                        @else
                                            <span class="nvm-chip nvm-chip--bad">PV ไม่ถึง</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($refRank)
                                        <span class="nvm-chip nvm-chip--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ $refRank->name_th ?: $refRank->name }}</span>
                                    @else
                                        <span class="nvm-ml-mute">ยังไม่มียศ</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($directReferrals->hasPages())
                <div class="nvm-pager nvm-gap-t">{{ $directReferrals->links() }}</div>
            @endif
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีสมาชิกในทีม</b>
                แชร์ลิงก์แนะนำให้เพื่อน แล้วรายชื่อจะแสดงที่นี่
                @if($nvR('user.mlm.referral'))
                    <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.mlm.referral') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ดูลิงก์แนะนำ</a></div>
                @endif
            </div>
        @endif
    </section>

    @php
        $quick = array_filter([
            ['send', 'ลิงก์แนะนำ', 'แชร์ลิงก์เชิญเพื่อน', 'user.mlm.referral'],
            ['grid', 'ผังสายงาน', 'ดูโครงสร้างทีมทั้งหมด', 'user.mlm.genealogy'],
            ['home', 'แดชบอร์ด MLM', 'กลับหน้าหลัก MLM', 'user.mlm.dashboard'],
        ], fn ($q) => $nvR($q[3]));
    @endphp
    @if(count($quick))
        <nav class="nvm-ml-quick" aria-label="ลิงก์ด่วน">
            @foreach($quick as [$icon, $label, $sub, $route])
                <a class="nvm-card nvm-tx nvm-ml-go" href="{{ route($route) }}">
                    <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                    <span class="nvm-tx__t"><b>{{ $label }}</b><small>{{ $sub }}</small></span>
                    <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                </a>
            @endforeach
        </nav>
    @endif
@endif

</div>
@endsection
