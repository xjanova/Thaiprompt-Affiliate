@extends('layouts.user-v4')

@section('title', 'หน้าหลัก')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    /**
     * User Home - Hub Navigation (App-Like Interface) — Theme V4 "นวลทองคำ"
     *
     * หน้าหลักหลังล็อกอินแบบ mobile app
     * - การ์ดต้อนรับ (Hero) + Avatar + แจ้งเตือน
     * - การ์ดยอดเงินคงเหลือ + ปุ่มเติมเงิน
     * - Hub Navigation Cards (8 รายการ)
     * - เมนูด่วน (Quick Actions)
     * - การ์ดสรุปสถิติ + ลิงก์แนะนำ
     * - คอมมิชชั่นล่าสุด
     */

    // ── เมนูด่วน (กรองเฉพาะ route ที่มีจริง) ─────────────────────
    // [emoji, label, route-or-null, comingSoonFeature]
    $quickRaw = [
        ['📊', 'แดชบอร์ด', 'user.dashboard',           null],
        ['💸', 'โอนเงิน',   'user.wallet.transfer',     null],
        ['➕', 'เติมเงิน',  'user.wallet.deposit',      null],
        ['📋', 'ประวัติ',   'user.wallet.transactions', null],
        ['📷', 'สแกน QR',   null,                       'สแกน QR'],
        ['💵', 'ถอนเงิน',   'user.wallet.withdraw',     null],
        ['👥', 'เชิญเพื่อน','user.mlm.referral',        null],
        ['👤', 'โปรไฟล์',   'user.profile',             null],
    ];
    $quick = [];
    foreach ($quickRaw as $q) {
        if ($q[2] === null) {
            // ฟีเจอร์ที่กำลังพัฒนา (ไม่มี route)
            $quick[] = [$q[0], $q[1], null, $q[3]];
        } elseif (RouteFacade::has($q[2])) {
            $quick[] = [$q[0], $q[1], route($q[2]), null];
        }
    }
@endphp

@section('content')
<div x-data="hubNavigation()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── การ์ดต้อนรับ (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; padding:22px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            {{-- Avatar + Notification badge --}}
            <a href="{{ route('user.profile') }}" style="position:relative; flex-shrink:0; text-decoration:none;">
                <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; padding:0; overflow:hidden;">
                    {{-- ใช้ profile_picture_url accessor ที่รองรับ fallback อัตโนมัติ --}}
                    <img src="{{ $user->profile_picture_url }}"
                         alt="{{ $user->name }}"
                         style="width:100%; height:100%; object-fit:cover;"
                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
                </span>
                @if($unreadNotifications > 0)
                    <span class="tp-num" style="position:absolute; top:-5px; right:-5px; min-width:20px; height:20px; padding:0 5px; border-radius:10px; background:#d9534f; color:#fff; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center;">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                @endif
            </a>

            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;" x-text="greeting"></div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">{{ $user->name }} <span style="color:var(--deep1);">✳</span></h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เลือกบริการที่คุณต้องการ ✨</div>
            </div>

            <a href="{{ route('user.wallet.deposit') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เติมเงิน</a>
        </div>
    </div>

    {{-- ── ยอดเงินคงเหลือ ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 18%, transparent), transparent 70%);">
            <div style="min-width:0;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12.5px; color:var(--ink2); font-weight:600;">💳 ยอดเงินคงเหลือ</span>
                    @if($walletGrowth != 0)
                        <span class="tp-pill" style="white-space:nowrap; {{ $walletGrowth > 0 ? 'color:#4f9e7e; background:rgba(79,158,126,.14);' : 'color:#d9534f; background:rgba(217,83,79,.14);' }}">
                            <i class="fas {{ $walletGrowth > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}" style="font-size:9px;"></i> {{ number_format(abs($walletGrowth), 0) }}%
                        </span>
                    @endif
                </div>
                <div class="tp-num" style="font-size:clamp(26px,5vw,36px); font-weight:800; margin-top:6px; color:#5aa07e;">฿{{ number_format($walletBalance, 2) }}</div>
            </div>
            <a href="{{ route('user.wallet.deposit') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เติมเงิน</a>
        </div>
    </div>

    {{-- ── Hub Navigation Cards (8 รายการ) ──────────────────── --}}
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">🧭 บริการทั้งหมด</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
            @foreach($hubs as $hub)
                <a href="{{ $hub['route'] }}" class="tp-card tp-card-hover" style="position:relative; display:block; padding:16px; text-decoration:none; color:inherit; overflow:hidden;">
                    {{-- ป้าย (ถ้ามี) --}}
                    @if(!empty($hub['badge']))
                        <span class="tp-pill" style="position:absolute; top:12px; right:12px; color:#fff; background:#d9534f; font-size:10px; padding:2px 8px;">{{ $hub['badge'] }}</span>
                    @endif

                    {{-- ไอคอน (ใช้สี gradient ของ hub เป็นพื้นกระเบื้อง) --}}
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px; margin-bottom:12px; background:linear-gradient(135deg, {{ $hub['gradient_start'] }}, {{ $hub['gradient_end'] }});">{{ $hub['icon'] }}</span>

                    <div style="font-size:14px; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $hub['title'] }}</div>
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:3px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $hub['subtitle'] }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── เมนูด่วน ──────────────────────────────────────────── --}}
    @if(count($quick) > 0)
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">⚡ การดำเนินการด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:12px;">
            @foreach($quick as [$emoji, $label, $href, $comingSoon])
                @if($href)
                    <a href="{{ $href }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:16px 10px; text-decoration:none; color:inherit;">
                        <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:20px;">{{ $emoji }}</span>
                        <span style="font-size:12.5px; font-weight:600; text-align:center;">{{ $label }}</span>
                    </a>
                @else
                    <a href="#" @click.prevent="showComingSoon('{{ $comingSoon }}')" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:16px 10px; text-decoration:none; color:inherit; cursor:pointer;">
                        <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:20px;">{{ $emoji }}</span>
                        <span style="font-size:12.5px; font-weight:600; text-align:center;">{{ $label }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── การ์ดสรุปสถิติ ───────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        {{-- คอมมิชชั่นรอดำเนินการ --}}
        <a href="{{ route('user.commissions') }}" class="tp-card tp-card-hover" style="display:block; padding:18px; text-decoration:none; color:inherit;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div style="min-width:0;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">รอดำเนินการ</div>
                    <div style="font-size:10px; color:var(--ink2); opacity:.8;">Pending Commission</div>
                </div>
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:rgba(86,137,184,.16);"><i class="fas fa-money-bill-wave" style="color:#5689b8;"></i></span>
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:10px; color:var(--deep1);">฿{{ number_format($pendingCommission, 2) }}</div>
        </a>

        {{-- ผู้ใช้ที่แนะนำ --}}
        <a href="{{ route('user.mlm.team') }}" class="tp-card tp-card-hover" style="display:block; padding:18px; text-decoration:none; color:inherit;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div style="min-width:0;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">ผู้ใช้ที่แนะนำ</div>
                    <div style="font-size:10px; color:var(--ink2); opacity:.8;">Total Referrals</div>
                </div>
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-users" style="color:var(--deep1);"></i></span>
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:10px;">{{ number_format($totalReferrals) }} คน</div>
        </a>
    </div>

    {{-- ── แชร์ลิงก์แนะนำ ───────────────────────────────────── --}}
    <a href="{{ route('user.mlm.referral') }}" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; padding:14px;">
        <i class="fas fa-share-alt"></i> แชร์ลิงก์แนะนำ
    </a>

    {{-- ── คอมมิชชั่นล่าสุด ─────────────────────────────────── --}}
    @if(count($recentActivities) > 0)
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h">📊 คอมมิชชั่นล่าสุด</div>
            <a href="{{ route('user.commissions') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด</a>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach($recentActivities as $activity)
                @php
                    $st = $activity['status'] ?? 'pending';
                    $stColor = $st === 'approved' ? '#5aa07e' : ($st === 'paid' ? 'var(--accent1)' : ($st === 'rejected' ? '#d9534f' : '#e0a52e'));
                    $stIcon  = $st === 'approved' ? 'fa-circle-check' : ($st === 'paid' ? 'fa-money-bill-wave' : ($st === 'rejected' ? 'fa-circle-xmark' : 'fa-clock'));
                    $stLabel = match($st) { 'approved' => 'อนุมัติแล้ว', 'paid' => 'จ่ายแล้ว', 'rejected' => 'ปฏิเสธ', default => 'รอดำเนินการ' };
                    $amt     = $activity['amount'] ?? 0;
                @endphp
                <div style="display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <span class="tp-tile" style="width:34px; height:34px; border-radius:10px; font-size:14px; background:{{ $stColor }};"><i class="fas {{ $stIcon }}" style="color:#fff;"></i></span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $activity['description'] ?? 'คอมมิชชั่น' }}</div>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:2px;">
                            <span class="tp-pill" style="color:#fff; background:{{ $stColor }}; font-size:10px; padding:1px 7px;">{{ $stLabel }}</span>
                            <span style="font-size:10.5px; color:var(--ink2); white-space:nowrap;">{{ optional($activity['created_at'])->diffForHumans() }}</span>
                        </div>
                    </div>
                    <span class="tp-num" style="font-size:13px; font-weight:800; white-space:nowrap; color:{{ $amt >= 0 ? '#5aa07e' : '#d9534f' }};">{{ $amt >= 0 ? '+' : '' }}฿{{ number_format(abs($amt), 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
/**
 * Hub Navigation Alpine.js Component
 *
 * จัดการ greeting ตามเวลาและ interactions
 */
function hubNavigation() {
    return {
        greeting: '',

        init() {
            this.setGreeting();
        },

        // ตั้งค่าข้อความทักทายตามเวลา
        setGreeting() {
            const hour = new Date().getHours();

            if (hour >= 5 && hour < 12) {
                this.greeting = 'สวัสดีตอนเช้า ☀️';
            } else if (hour >= 12 && hour < 17) {
                this.greeting = 'สวัสดีตอนบ่าย 🌤️';
            } else if (hour >= 17 && hour < 21) {
                this.greeting = 'สวัสดีตอนเย็น 🌅';
            } else {
                this.greeting = 'สวัสดีตอนกลางคืน 🌙';
            }
        },

        // แสดง toast สำหรับฟีเจอร์ที่กำลังพัฒนา
        showComingSoon(feature) {
            if (window.showNotification) {
                window.showNotification(feature + ' กำลังพัฒนา', 'info');
            } else if (window.toast) {
                window.toast.show('info', feature + ' กำลังพัฒนา');
            } else {
                alert(feature + ' กำลังพัฒนา');
            }
        }
    }
}
</script>
@endpush
