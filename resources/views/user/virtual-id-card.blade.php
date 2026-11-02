{{--
    บัตรประจำตัวสมาชิก (ธีมโนวาเต็ม) — RankController::virtualIdCard ส่งมา: $user, $currentRank, $idCardSettings
    - ตัวบัตรวาดใหม่เป็นบัตรกรมท่า + ทอง (คลาส nvm-um-idc-*) — ระดับยิ่งสูงยิ่งมีลูกเล่น:
        ระดับ 4+ = แสงโฮโลแกรมวิ่ง · ระดับ 6+ = กรอบทองหนา · ระดับ 8 = เรืองแสง
      สีประจำยศ (ranks.color) ส่งเข้า CSS ผ่านตัวแปร --rk
    - ดาวน์โหลดเป็น PNG ด้วย html2canvas · พิมพ์ได้เฉพาะตัวบัตร (@media print ใน user-misc.css)
    - QR = เลขสมาชิกของตัวเอง (qrcodejs ชุดเดียวกับหน้า QR รับเงิน)
      🐛 เดิมส่ง <div> ให้ QRCode.toCanvas() ซึ่งต้องการ <canvas> → QR ไม่เคยขึ้น เหลือแต่ตัวเลขสำรอง
    - partial id-card-background / id-card-decorations (Tailwind) ยังใช้อยู่ที่หน้าแอดมิน admin.id-card.* — หน้านี้ไม่ใช้แล้ว
--}}
@extends('layouts.user-nova')

@section('title', 'บัตรประจำตัวสมาชิก')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    use Illuminate\Support\Str;

    $rankLevel = (int) ($currentRank?->level ?? 1);
    $rankName = $currentRank?->name ?? 'Bronze';
    $rankNameTh = $currentRank?->name_th ?? 'สำริด';
    $rankBadge = $currentRank?->badge_icon ?? '🥉';
    $rankStars = max(0, (int) ($currentRank?->stars ?? 1));
    // สีประจำยศต้องเป็น hex เท่านั้น (ค่าจากฐานข้อมูลเข้า style ตรงๆ)
    $rankColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $currentRank?->color) ? $currentRank->color : '#CD7F32';
    $memberNo = $user->member_number ?: (string) $user->id;
    $initial = mb_strtoupper(mb_substr((string) $user->name, 0, 1)) ?: '?';
    $tier = $rankLevel >= 8 ? 'is-legend' : ($rankLevel >= 6 ? 'is-elite' : ($rankLevel >= 4 ? 'is-holo' : ''));
    $privileges = $currentRank && $currentRank->privileges ? $currentRank->getPrivilegesWithDescriptions() : [];
@endphp

@section('content')

<div class="nvm-stack" x-data="nvmUmIdCard(@js($memberNo), @js('thaiprompt-id-card-'.(Str::slug($user->name) ?: $user->id).'.png'))">

<section class="nvm-ph no-print" aria-labelledby="nvm-um-idc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.ranks.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ระดับสมาชิก</a>
            <div class="nvm-eyebrow nvm-latin">Member ID card</div>
            <h1 id="nvm-um-idc-h">บัตรประจำตัวสมาชิก</h1>
            <p class="nvm-ph__lead">บัตรประจำตัวเสมือนของคุณ ดาวน์โหลดเก็บไว้หรือพิมพ์ได้</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ระดับ{{ $rankNameTh }}</span>
                <span class="nvm-pill nvm-pill--glass nvm-num"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>{{ $memberNo }}</span>
            </div>
            <div class="nvm-hero__actions">
                <button type="button" class="nvm-btn nvm-btn--gold" @click="download()" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg><span x-text="busy ? 'กำลังสร้างรูป…' : 'ดาวน์โหลด'">ดาวน์โหลด</span></button>
                <button type="button" class="nvm-btn nvm-btn--ghost" @click="window.print()"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>พิมพ์</button>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/account/idcard.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ── ตัวบัตร (หน้า + หลัง) ───────────────────────────────── --}}
<section class="nvm-um-idc-wrap" id="id-card-container" aria-label="บัตรประจำตัวสมาชิก">
    <div class="nvm-um-idc {{ $tier }}" id="virtual-id-card" x-ref="card" style="--rk: {{ $rankColor }}">
        <div class="nvm-um-idc__top">
            <span class="nvm-um-idc__brand"><img src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" width="30" height="30">
                <span><small class="nvm-latin">Member ID card</small><b>Thai Prompt</b></span>
            </span>
            <span class="nvm-um-idc__rank">
                <span class="nvm-um-idc__badge" aria-hidden="true">{{ $rankBadge }}</span>
                <b class="nvm-latin">{{ $rankName }}</b>
            </span>
        </div>

        <div class="nvm-um-idc__mid">
            <span class="nvm-um-idc__av">
                {{ $initial }}
                @if($user->profile_picture_url)
                    <img src="{{ $user->profile_picture_url }}" alt="" onerror="this.remove()">
                @endif
                @if($rankStars > 0)
                    <span class="nvm-um-idc__stars" aria-label="{{ $rankStars }} ดาว">{{ str_repeat('★', min($rankStars, 5)) }}@if($rankStars > 5)<small>+{{ $rankStars - 5 }}</small>@endif</span>
                @endif
            </span>
            <span class="nvm-um-idc__who">
                <b>{{ Str::limit($user->name, 24) }}</b>
                <small>ระดับ{{ $rankNameTh }}</small>
                <span class="nvm-um-idc__no nvm-num">ID {{ $memberNo }}</span>
            </span>
        </div>

        <div class="nvm-um-idc__bot">
            <span class="nvm-um-idc__chip" aria-hidden="true"></span>
            <span class="nvm-um-idc__since"><small>เป็นสมาชิกตั้งแต่</small><b>{{ $user->created_at?->locale('th')->translatedFormat('M Y') }}</b></span>
            <span class="nvm-um-idc__qr" x-ref="qr" aria-label="QR เลขสมาชิก"></span>
        </div>
    </div>

    <div class="nvm-um-idc nvm-um-idc--back" aria-label="บัตรด้านหลัง">
        <span class="nvm-um-idc__stripe" aria-hidden="true"></span>
        <span class="nvm-um-idc__sign">ลายเซ็นผู้ถือบัตร</span>
        <div class="nvm-um-idc__fine">
            <span>บัตรนี้เป็นสมบัติของ Thai Prompt<br>กรุณาคืนหากพบ · ติดต่อผ่านศูนย์ช่วยเหลือในระบบ</span>
            <span class="nvm-um-idc__valid"><small>Valid thru</small><b class="nvm-num">{{ now()->addYear()->format('m/Y') }}</b></span>
        </div>
    </div>
</section>

{{-- ── ข้อมูลระดับ ─────────────────────────────────────────── --}}
<section class="nvm-kpis nvm-kpis--3 no-print" aria-label="ข้อมูลสมาชิก">
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/brand/tabbar-kanok-medallion.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">ระดับปัจจุบัน</div>
        <div class="nvm-kpi__en nvm-latin">Current rank</div>
        <div class="nvm-kpi__val">{{ $rankNameTh }}</div>
        <div class="nvm-kpi__foot"><small>{{ $rankStars > 0 ? str_repeat('★', min($rankStars, 10)) : 'ระดับเริ่มต้น' }}</small></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">คะแนนสะสม</div>
        <div class="nvm-kpi__en nvm-latin">Rank points</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($user->rank_points ?? 0)) }}</div>
        <div class="nvm-kpi__foot"><small>สะสมเพื่อเลื่อนระดับ</small></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">วันที่เป็นสมาชิก</div>
        <div class="nvm-kpi__en nvm-latin">Days with us</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) $user->created_at?->diffInDays(now())) }}</div>
        <div class="nvm-kpi__foot"><small>ตั้งแต่ {{ $user->created_at?->locale('th')->translatedFormat('j M Y') }}</small></div>
    </div>
</section>

@if(count($privileges))
    <section class="nvm-card nvm-box no-print" aria-labelledby="nvm-um-idc-perk">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Privileges</small><h2 id="nvm-um-idc-perk">สิทธิพิเศษของคุณ</h2></div></div>
        <ul class="nvm-um-feats nvm-um-feats--grid">
            @foreach($privileges as $privilege)
                <li>
                    <span class="nvm-um-ic nvm-um-ic--txt" aria-hidden="true">{{ $privilege['icon'] ?? '★' }}</span>
                    <span><b>{{ $privilege['name_th'] ?? ($privilege['name'] ?? '') }}</b></span>
                </li>
            @endforeach
        </ul>
    </section>
@endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
/**
 * บัตรประจำตัวสมาชิก — วาด QR เลขสมาชิก และดาวน์โหลดบัตรเป็น PNG
 */
function nvmUmIdCard(memberNo, fileName) {
    return {
        busy: false,

        init() {
            const box = this.$refs.qr;
            if (typeof QRCode === 'undefined') { box.textContent = memberNo; box.classList.add('is-txt'); return; }
            // เลขสมาชิกเป็นอักษรละตินล้วน — qrcodejs นับความยาวถูก
            new QRCode(box, { text: String(memberNo), width: 128, height: 128, colorDark: '#0b1633', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        },

        async download() {
            if (this.busy) return;
            if (typeof html2canvas === 'undefined') { window.showNotification('โหลดเครื่องมือสร้างรูปไม่สำเร็จ ลองรีเฟรชหน้า', 'error'); return; }
            this.busy = true;
            try {
                const canvas = await html2canvas(this.$refs.card, { scale: 2, useCORS: true, backgroundColor: null, logging: false });
                const link = document.createElement('a');
                link.download = fileName;
                link.href = canvas.toDataURL('image/png');
                link.click();
                window.showNotification('ดาวน์โหลดบัตรสำเร็จ', 'success');
            } catch (e) {
                window.showNotification('ดาวน์โหลดไม่สำเร็จ กรุณาลองใหม่', 'error');
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
