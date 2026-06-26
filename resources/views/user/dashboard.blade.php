@extends('layouts.user-v4')

@section('title', 'แดชบอร์ด')

@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ระดับ Rank + ป้ายเหรียญ ────────────────────────────────
    $rankLevel  = $currentRank?->level ?? 1;
    $rankBadges = [1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎', 5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐'];
    $rankBadge  = $rankBadges[$rankLevel] ?? '🥉';

    $thMonth = now()->locale('th')->translatedFormat('F');
    $thYear  = now()->year + 543;

    // ── ลิงก์บัตร VIP (เผื่อชื่อ route ต่างกันระหว่างเวอร์ชัน) ──
    $idCardUrl = RouteFacade::has('user.id-card') ? route('user.id-card')
               : (RouteFacade::has('user.ranks.id-card') ? route('user.ranks.id-card') : null);

    // ── สถิติหลัก 4 ใบ (ตรงกับ DashboardController::index) ──────
    $kpis = [
        [
            'label' => 'ยอดเงินในกระเป๋า', 'en' => 'Wallet Balance',
            'value' => '฿' . number_format($stats['wallet_balance'] ?? 0, 2),
            'delta' => $stats['wallet_change'] ?? 0,
            'href'  => route('user.wallet.index'),
            'spark' => [28, 40, 34, 52, 46, 64, 58, 80, 100],
        ],
        [
            'label' => 'คอมมิชชั่นรอรับ', 'en' => 'Pending Commission',
            'value' => '฿' . number_format($stats['pending_commission'] ?? 0, 2),
            'delta' => $stats['commission_change'] ?? 0,
            'href'  => route('user.commissions'),
            'spark' => [22, 30, 44, 40, 58, 52, 70, 76, 92],
        ],
        [
            'label' => 'ทีมทั้งหมด', 'en' => 'Total Referrals',
            'value' => number_format($stats['total_referrals'] ?? 0),
            'badge' => number_format($stats['active_referrals'] ?? 0) . ' ใช้งาน',
            'href'  => route('user.mlm.team'),
            'spark' => [40, 32, 46, 38, 52, 44, 58, 50, 64],
        ],
        [
            'label' => 'คะแนน Rank', 'en' => 'Rank Points',
            'value' => number_format($stats['rank_points'] ?? 0),
            'href'  => route('user.ranks.progress'),
            'spark' => [18, 26, 30, 42, 48, 60, 72, 84, 100],
        ],
    ];

    // ── กราฟแท่งรายได้ 12 เดือน (CSS — ไม่ใช้ Chart.js) ────────
    $chartLabels = $chartData['labels'] ?? [];
    $chartValues = $chartData['values'] ?? [];
    $chartMax    = max(1, (float) (count($chartValues) ? max($chartValues) : 1));
    $chartSum    = array_sum($chartValues);

    // ── เมนูด่วน (กรองเฉพาะ route ที่มีจริง) ────────────────────
    $quickRaw = [
        ['💰', 'เติมเงิน',   'user.wallet.deposit'],
        ['🏧', 'ถอนเงิน',    'user.wallet.withdraw'],
        ['🤝', 'เชิญเพื่อน', 'user.mlm.referral'],
        ['📊', 'คอมมิชชั่น', 'user.commissions'],
        ['🌳', 'ผังทีม',     'user.mlm.genealogy'],
        ['👤', 'โปรไฟล์',    'user.profile'],
    ];
    $quick = [];
    foreach ($quickRaw as $q) {
        if (RouteFacade::has($q[2])) {
            $quick[] = [$q[0], $q[1], route($q[2])];
        }
    }
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── การ์ดต้อนรับ (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; padding:22px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:28px;">{{ $rankBadge }}</span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">บัญชีของฉัน · MY ACCOUNT · {{ $thMonth }} {{ $thYear }}</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">สวัสดี, {{ $user->name }} <span style="color:var(--deep1);">✳</span></h1>
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:9px;">
                    @if($currentRank)
                        <span class="tp-pill tp-pill-gold">{{ $rankBadge }} {{ $currentRank->name_th ?? $currentRank->name }}</span>
                    @else
                        <span class="tp-pill tp-pill-soft">สมาชิกใหม่</span>
                    @endif
                    @if(($kycStatus ?? null) === 'approved')
                        <span class="tp-pill" style="color:#fff; background:#5aa07e;"><i class="fas fa-circle-check" style="font-size:10px;"></i> ยืนยันตัวตนแล้ว</span>
                    @elseif(($kycStatus ?? null) === 'pending')
                        <span class="tp-pill" style="color:#fff; background:#e0a52e;"><i class="fas fa-hourglass-half" style="font-size:10px;"></i> รอตรวจ KYC</span>
                    @else
                        <span class="tp-pill" style="color:#fff; background:#d9534f;"><i class="fas fa-id-card" style="font-size:10px;"></i> ยังไม่ยืนยันตัวตน</span>
                    @endif
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:9px;">
                @if($idCardUrl && $rankLevel >= 2)
                    <a href="{{ $idCardUrl }}" class="tp-btn"><i class="fas fa-id-card"></i> บัตร VIP</a>
                @endif
                <a href="{{ route('user.wallet.index') }}" class="tp-btn tp-btn-primary"><i class="fas fa-wallet"></i> กระเป๋าเงิน</a>
            </div>
        </div>
    </div>

    {{-- ── แจ้งเตือน KYC (ถ้ายังไม่ยืนยัน/ถูกปฏิเสธ) ─────────── --}}
    @if($showKycAlert)
    <div class="tp-card" style="padding:16px 18px; display:flex; flex-wrap:wrap; align-items:center; gap:14px;
                box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:18px; background:#e0a52e;"><i class="fas fa-id-card" style="color:#fff;"></i></span>
        <div style="flex:1; min-width:200px;">
            <div style="font-weight:700; font-size:14px;">ยืนยันตัวตนเพื่อปลดล็อกฟีเจอร์ทั้งหมด</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">ทำ KYC เพื่อถอนเงินและใช้งานได้เต็มรูปแบบ</div>
        </div>
        <a href="{{ route('user.kyc.index') }}" class="tp-btn tp-btn-primary">ยืนยันตัวตนตอนนี้</a>
    </div>
    @endif

    {{-- ── KPI 4 ใบ ─────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px;">
        @foreach($kpis as $k)
            <a href="{{ $k['href'] }}" class="tp-card tp-card-hover" style="display:block; padding:18px; text-decoration:none; color:inherit;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $k['label'] }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $k['en'] }}</div>
                    </div>
                    @if(!empty($k['badge']))
                        <span class="tp-pill tp-pill-soft" style="white-space:nowrap;">{{ $k['badge'] }}</span>
                    @elseif(isset($k['delta']) && $k['delta'] != 0)
                        <span class="tp-pill" style="white-space:nowrap; {{ $k['delta'] > 0 ? 'color:#4f9e7e; background:rgba(79,158,126,.14);' : 'color:#d9534f; background:rgba(217,83,79,.14);' }}">
                            {{ $k['delta'] > 0 ? '↑' : '↓' }} {{ number_format(abs($k['delta']), 1) }}%
                        </span>
                    @endif
                </div>
                <div class="tp-num" style="font-size:28px; font-weight:800; margin:10px 0 12px; letter-spacing:.4px;">{{ $k['value'] }}</div>
                <div class="tp-spark">
                    @foreach($k['spark'] as $i => $h)
                        <i style="height:{{ $h }}%; animation-delay:{{ $i * 0.05 }}s;"></i>
                    @endforeach
                </div>
            </a>
        @endforeach
    </div>

    {{-- ── กราฟรายได้ 12 เดือน (CSS bars) ───────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;">
            <div>
                <div style="font-weight:700; font-size:15px;">รายได้ · รายเดือน</div>
                <div style="font-size:11px; color:var(--ink2);">Earnings — 12 เดือนล่าสุด</div>
            </div>
            <span class="tp-pill tp-pill-gold tp-num">฿{{ number_format($chartSum, 0) }}</span>
        </div>
        @if(count($chartValues) > 0 && $chartSum > 0)
        <div class="tp-bars">
            @foreach($chartValues as $i => $val)
                <div class="col" title="{{ $chartLabels[$i] ?? '' }}: ฿{{ number_format($val, 2) }}">
                    <div class="stack" style="height:100%;">
                        <span class="bar a" style="height:{{ max(3, round(($val / $chartMax) * 100)) }}%;"></span>
                    </div>
                    <span class="lbl">{{ Str::limit($chartLabels[$i] ?? '', 6, '') }}</span>
                </div>
            @endforeach
        </div>
        @else
            <div style="text-align:center; color:var(--ink2); padding:46px 0; font-size:13px;">ยังไม่มีรายได้ในช่วง 12 เดือนที่ผ่านมา</div>
        @endif
    </div>

    {{-- ── เมนูด่วน ──────────────────────────────────────────── --}}
    @if(count($quick) > 0)
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">⚡ เมนูด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
            @foreach($quick as [$emoji, $label, $href])
                <a href="{{ $href }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:16px 10px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:20px;">{{ $emoji }}</span>
                    <span style="font-size:12.5px; font-weight:600; text-align:center;">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── 3 คอลัมน์: รักษายอด / ความคืบหน้า Rank / กิจกรรม ─── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">

        {{-- สถานะรักษายอด (Volume Retention) --}}
        @if($retentionStatus && $mlmMember)
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">💓 สถานะรักษายอด</div>
            @if(!($retentionStatus['retention_enabled'] ?? true))
                <div style="text-align:center; padding:18px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="font-size:13px; color:var(--ink2);">ระบบรักษายอดปิดอยู่</div>
                    <div style="color:#4f9e7e; font-weight:700; margin-top:4px;">Active ทุกคน</div>
                </div>
            @else
                @php
                    $rtStatus = $retentionStatus['status'] ?? 'inactive';
                    $rtText   = match($rtStatus) { 'active' => 'รักษายอดสำเร็จ', 'grace_period' => 'อยู่ในช่วงผ่อนผัน', default => 'ยังไม่ถึงเกณฑ์' };
                    $rtEmoji  = match($rtStatus) { 'active' => '✅', 'grace_period' => '⏳', default => '⚠️' };
                    $rtColor  = match($retentionStatus['color'] ?? 'gray') { 'green' => '#5aa07e', 'yellow' => '#e0a52e', 'red' => '#d9534f', default => 'var(--ink2)' };
                    $pvPct    = min(100, (float) ($retentionStatus['pv_percentage'] ?? 0));
                @endphp
                <div style="text-align:center; margin-bottom:14px;">
                    <span class="tp-pill" style="color:#fff; background:{{ $rtColor }}; font-size:12px; padding:5px 12px;">{{ $rtEmoji }} {{ $rtText }}</span>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px; margin-bottom:7px;">
                    <span style="color:var(--ink2);">PV เดือนนี้</span>
                    <span class="tp-num" style="font-weight:700;">{{ number_format($retentionStatus['monthly_pv'] ?? 0, 0) }} / {{ number_format($retentionStatus['required_pv'] ?? 100, 0) }}</span>
                </div>
                <div style="height:12px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ $pvPct }}%; border-radius:20px; background:linear-gradient(90deg, {{ $rtColor }}, color-mix(in srgb, {{ $rtColor }} 60%, #fff)); transition:width .5s ease;"></div>
                </div>
                @if(($retentionStatus['remaining_pv'] ?? 0) > 0)
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px; margin-top:9px;">
                        <span style="color:var(--ink2);">ต้องการเพิ่ม</span>
                        <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ number_format($retentionStatus['remaining_pv'], 0) }} PV</span>
                    </div>
                @endif
                @if($retentionStatus['in_grace_period'] ?? false)
                    <div style="margin-top:12px; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm); font-size:12px; color:#e0a52e;">
                        <i class="fas fa-clock"></i> ช่วงผ่อนผัน: เหลืออีก {{ max(0, ($retentionStatus['grace_days'] ?? 7) - ($retentionStatus['days_since_last_purchase'] ?? 0)) }} วัน
                    </div>
                @elseif($rtStatus === 'inactive')
                    <div style="margin-top:12px; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm); font-size:12px; color:#d9534f;">
                        <i class="fas fa-triangle-exclamation"></i> <strong>PV ไม่ถึงเกณฑ์!</strong> คอมมิชชันจะถูกระงับจนกว่าจะซื้อสินค้าเพิ่ม
                    </div>
                @endif
            @endif
        </div>
        @endif

        {{-- ความคืบหน้า Rank --}}
        @if($currentRank || $nextRank)
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">{{ $rankBadge }} ความคืบหน้า Rank</div>
            @if($currentRank)
                <div style="padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm); margin-bottom:14px;">
                    <div style="font-size:11.5px; color:var(--ink2);">Rank ปัจจุบัน</div>
                    <div style="font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px; margin-top:3px;">
                        <span style="font-size:20px;">{{ $rankBadge }}</span> {{ $currentRank->name_th ?? $currentRank->name }}
                        @if($rankLevel >= 8)<span class="tp-pill tp-pill-soft" style="font-size:9px;">ตำนาน</span>@endif
                    </div>
                </div>
            @endif
            @if($nextRank)
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px; margin-bottom:7px;">
                    <span style="color:var(--ink2);"><i class="fas fa-arrow-up" style="color:#4f9e7e;"></i> ถัดไป: {{ $nextRank->name_th ?? $nextRank->name }}</span>
                    <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ number_format($rankProgress, 1) }}%</span>
                </div>
                <div style="height:14px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ min(100, $rankProgress) }}%; border-radius:20px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); transition:width .5s ease;"></div>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;"><i class="fas fa-circle-info"></i> ต้องการอีก <span class="tp-num" style="font-weight:700;">{{ number_format($pointsNeeded) }}</span> คะแนน</div>
            @else
                <div style="text-align:center; padding:24px 0;">
                    <div style="font-size:46px;">👑</div>
                    <div style="font-weight:700; margin-top:6px;">คุณอยู่ที่ Rank สูงสุดแล้ว!</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">ยินดีด้วย คุณคือสุดยอดสมาชิก</div>
                </div>
            @endif
            @if($idCardUrl && $rankLevel >= 2)
                <a href="{{ $idCardUrl }}" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; margin-top:14px;"><i class="fas fa-id-card"></i> ดูบัตรประจำตัว VIP</a>
            @endif
        </div>
        @endif

        {{-- กิจกรรมล่าสุด --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">🔔 กิจกรรมล่าสุด</div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:340px; overflow-y:auto;">
                @forelse($recentActivities as $activity)
                    @php
                        $st = $activity['status'] ?? 'pending';
                        $stColor = $st === 'approved' ? '#5aa07e' : ($st === 'paid' ? 'var(--accent1)' : ($st === 'rejected' ? '#d9534f' : '#e0a52e'));
                        $stIcon  = $st === 'approved' ? 'fa-circle-check' : ($st === 'paid' ? 'fa-money-bill-wave' : ($st === 'rejected' ? 'fa-circle-xmark' : 'fa-clock'));
                        $stTitle = match($st) { 'approved' => 'คอมมิชชั่นได้รับอนุมัติ', 'paid' => 'คอมมิชชั่นจ่ายแล้ว', 'rejected' => 'คอมมิชชั่นถูกปฏิเสธ', default => 'คอมมิชชั่นรอดำเนินการ' };
                    @endphp
                    <div style="display:flex; align-items:center; gap:11px; padding:9px 11px; border-radius:13px; box-shadow:var(--inset-sm);">
                        <span class="tp-tile" style="width:34px; height:34px; border-radius:10px; font-size:14px; background:{{ $stColor }};"><i class="fas {{ $stIcon }}" style="color:#fff;"></i></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $stTitle }}</div>
                            <div class="tp-num" style="font-size:12px; color:var(--deep1); font-weight:700;">฿{{ number_format($activity['amount'] ?? 0, 2) }}</div>
                        </div>
                        <span style="font-size:10.5px; color:var(--ink2); white-space:nowrap;">{{ optional($activity['created_at'])->diffForHumans() }}</span>
                    </div>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:30px 0; font-size:13px;">
                        <div style="font-size:38px; opacity:.5;">📭</div>
                        ยังไม่มีกิจกรรม
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
