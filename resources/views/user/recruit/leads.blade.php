{{--
    ผู้มุ่งหวังจากหน้า Recruit (ธีมโนวาเต็ม)
    - RecruitPageController::leads ดึงเฉพาะ lead ของแม่ทีมคนนี้ (team_leader_id) + กรองสถานะ
    - 🔒 (2026-11-02) IP ของผู้เข้าชม (คนนอกที่ยังไม่ได้เป็นสมาชิก) เดิมโชว์เต็ม → ปิดท้ายไว้ เหลือพอแยกแยะได้
--}}
@extends('layouts.user-nova')

@section('title', 'ผู้มุ่งหวัง (Leads)')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';

    // ปิดท้าย IP: IPv4 เหลือ 2 ส่วนแรก · IPv6 เหลือ 2 กลุ่มแรก
    $maskIp = function (?string $ip): string {
        if (! $ip) {
            return '-';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $p = explode('.', $ip);

            return $p[0].'.'.$p[1].'.*.*';
        }
        $p = explode(':', $ip);

        return ($p[0] ?? '').':'.($p[1] ?? '').':****';
    };

    $tabs = [
        'all' => ['ทั้งหมด', $stats['total']],
        'active' => ['กำลังติดตาม', $stats['active']],
        'converted' => ['สมัครแล้ว', $stats['converted']],
        'expired' => ['หมดอายุ', $stats['expired']],
    ];
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-rl-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.marketing.recruit.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>หน้าแนะนำสมาชิก</a>
            <div class="nvm-eyebrow nvm-latin">Leads</div>
            <h1 id="nvm-rl-h">ผู้มุ่งหวัง (Leads)</h1>
            <p class="nvm-ph__lead">คนที่เข้ามาที่หน้า Recruit ของคุณ ระบบล็อกให้เป็นของคุณ 48 ชั่วโมงเพื่อให้ติดตามได้ทัน</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/garland.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-tr-stats" aria-label="สรุปผู้มุ่งหวัง">
    <div class="nvm-mini"><small>ผู้มุ่งหวังทั้งหมด</small><b class="nvm-num">{{ number_format($stats['total']) }}</b></div>
    <div class="nvm-mini"><small>กำลังติดตาม</small><b class="nvm-num is-in">{{ number_format($stats['active']) }}</b></div>
    <div class="nvm-mini"><small>สมัครแล้ว</small><b class="nvm-num is-gold">{{ number_format($stats['converted']) }}</b></div>
    <div class="nvm-mini"><small>หมดอายุ</small><b class="nvm-num is-mute">{{ number_format($stats['expired']) }}</b></div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-rl-list-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Lead list</small><h2 id="nvm-rl-list-h">รายชื่อผู้มุ่งหวัง</h2></div>
        <nav class="nvm-tabs" aria-label="กรองตามสถานะ">
            @foreach($tabs as $key => [$label, $count])
                <a href="{{ route('user.marketing.recruit.leads', ['status' => $key]) }}" class="{{ $currentStatus === $key ? 'is-on' : '' }}" @if($currentStatus === $key) aria-current="page" @endif>{{ $label }} ({{ number_format($count) }})</a>
            @endforeach
        </nav>
    </div>

    @if($leads->count() > 0)
        <div class="nvm-table-wrap">
            <table class="nvm-table">
                <thead>
                    <tr><th>ผู้เข้าชม</th><th>แหล่งที่มา</th><th>เวลา</th><th>สถานะ</th></tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                        @php
                            $isActive = $lead->status === 'locked' && $lead->expires_at && $lead->expires_at->isFuture();
                            $utm = is_array($lead->utm_params) ? $lead->utm_params : (is_string($lead->utm_params) ? (json_decode($lead->utm_params, true) ?: []) : []);
                        @endphp
                        <tr>
                            <td>
                                <div class="nvm-tr-who">
                                    <span class="nvm-avatar"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg></span>
                                    <div>
                                        @if($lead->status === 'converted' && $lead->mlmMember)
                                            <b>{{ $lead->mlmMember->user?->name ?? 'สมาชิกใหม่' }}</b>
                                            <small>สมัครสมาชิกแล้ว</small>
                                        @else
                                            <b>ผู้เข้าชม #{{ $lead->id }}</b>
                                            <small class="nvm-num">IP {{ $maskIp($lead->ip_address) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="nvm-tr-cell">
                                @if(! empty($utm))
                                    <span class="nvm-chip nvm-chip--info">{{ $utm['utm_source'] ?? 'ไม่ระบุ' }}</span>
                                    @if(! empty($utm['utm_campaign']))<small>แคมเปญ: {{ $utm['utm_campaign'] }}</small>@endif
                                @elseif($lead->referrer_url)
                                    {{ \Illuminate\Support\Str::limit(parse_url($lead->referrer_url, PHP_URL_HOST) ?: 'เข้าตรง', 30) }}
                                @else
                                    เข้าตรง
                                @endif
                            </td>
                            <td class="nvm-tr-cell nvm-num">
                                {{ $thDate($lead->locked_at) }}
                                @if($isActive)
                                    <small>เหลือเวลา {{ $lead->getTimeRemaining() }}</small>
                                @elseif($lead->status === 'converted')
                                    <small>สมัครเมื่อ {{ $thDate($lead->converted_at) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($lead->status === 'converted')
                                    <span class="nvm-chip nvm-chip--gold">สมัครแล้ว</span>
                                @elseif($isActive)
                                    <span class="nvm-chip nvm-chip--ok">กำลังติดตาม</span>
                                @else
                                    <span class="nvm-chip">หมดอายุ</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $leads->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีผู้มุ่งหวัง{{ $currentStatus !== 'all' ? 'ในสถานะนี้' : '' }}</b>
            แชร์ลิงก์หน้า Recruit ของคุณเพื่อเริ่มสร้างรายชื่อผู้มุ่งหวัง
            <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.marketing.recruit.index') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>ดูลิงก์และ QR Code</a></div>
        </div>
    @endif
</section>

</div>
@endsection
