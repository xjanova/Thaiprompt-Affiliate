@extends('layouts.user-v4')

@section('title', 'MLM Dashboard')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ตรวจชนิดแผน MLM (binary/hybrid แสดง Binary Legs) ──────────
    $planType  = $member->plan?->type ?? null;
    $showBinary = in_array($planType, ['binary', 'hybrid'], true);

    // ── ลิงก์แนะนำเพื่อน (เผื่อชื่อ route ต่างเวอร์ชัน) ──────────────
    $referralUrl = RouteFacade::has('register')
        ? route('register', ['ref' => $member->member_code])
        : (RouteFacade::has('line.registration.register')
            ? route('line.registration.register', ['ref' => $member->member_code])
            : url('/register?ref=' . $member->member_code));

    // ── สถานะรักษายอด (Volume Retention) ───────────────────────────
    $rtEnabled = $retentionStatus['retention_enabled'] ?? true;
    $rtStatus  = $retentionStatus['status'] ?? 'inactive';
    $rtText    = match ($rtStatus) {
        'active' => 'รักษายอดสำเร็จ',
        'grace_period' => 'อยู่ในช่วงผ่อนผัน',
        default => 'ยังไม่ถึงเกณฑ์',
    };
    $rtEmoji   = match ($rtStatus) {
        'active' => '✅',
        'grace_period' => '⏳',
        default => '⚠️',
    };
    $rtColor   = match ($retentionStatus['color'] ?? 'gray') {
        'green' => '#5aa07e',
        'yellow' => '#e0a52e',
        'red' => '#d9534f',
        default => 'var(--ink2)',
    };
    $pvPct     = (float) ($retentionStatus['pv_percentage'] ?? 0);

    // ── ค่าคอมมิชชัน Binary Pair (จาก Global Setting) ──────────────
    $weakLegPv  = min($member->left_leg_pv ?? 0, $member->right_leg_pv ?? 0);
    $pairComm   = \App\Models\MlmGlobalSetting::get('binary_pair_commission', 100);
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── การ์ดต้อนรับ (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; padding:22px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:26px;"><i class="fas fa-network-wired" style="color:#fff;"></i></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สวัสดี, {{ auth()->user()->name }}!</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">
                        รหัสสมาชิก: <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ $member->member_code }}</span>
                    </div>
                </div>
            </div>
            <div style="padding:12px 18px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:11px; color:var(--ink2);">แผน MLM ของคุณ</div>
                <div style="font-size:18px; font-weight:800; color:var(--deep1); margin-top:2px;">{{ $member->plan?->display_name ?? 'ไม่มีแผน' }}</div>
            </div>
        </div>
    </div>

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    @php
        $kpis = [
            [
                'emoji' => '⭐', 'label' => 'PV รวม',
                'value' => number_format($member->total_pv ?? 0),
                'color' => 'var(--deep1)',
                'progress' => min(($member->total_pv ?? 0) / 10000 * 100, 100),
                'note' => 'เป้า: 10,000 PV',
            ],
            [
                'emoji' => '👥', 'label' => 'สมาชิกโดยตรง',
                'value' => number_format($member->total_direct_referrals ?? 0),
                'color' => '#5689b8',
                'note' => 'ทีมทั้งหมด: ' . number_format($member->total_team_members ?? 0) . ' คน',
            ],
            [
                'emoji' => '💰', 'label' => 'รายได้รวม',
                'value' => '฿' . number_format($member->total_earnings ?? 0, 2),
                'color' => '#5aa07e',
                'note' => 'รายได้สะสมทั้งหมด',
            ],
            [
                'emoji' => '🏆', 'label' => 'ระดับปัจจุบัน',
                'value' => $member->rank?->name ?? 'Starter',
                'color' => '#e0a52e',
                'note' => 'ระดับถัดไป: Silver',
            ],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px;">
        @foreach($kpis as $k)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                    <span style="font-size:30px;">{{ $k['emoji'] }}</span>
                    <div style="text-align:right; min-width:0;">
                        <div style="font-size:12px; color:var(--ink2); font-weight:600;">{{ $k['label'] }}</div>
                        <div class="tp-num" style="font-size:24px; font-weight:800; color:{{ $k['color'] }}; margin-top:2px; overflow:hidden; text-overflow:ellipsis;">{{ $k['value'] }}</div>
                    </div>
                </div>
                @if(isset($k['progress']))
                    <div style="height:8px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden; margin-top:12px;">
                        <div style="height:100%; width:{{ $k['progress'] }}%; border-radius:20px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                    </div>
                @endif
                <div style="font-size:11px; color:var(--ink2); margin-top:8px;">{{ $k['note'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── เลย์เอาต์ 2 คอลัมน์ ───────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:18px;" class="tp-mlm-grid">

        {{-- ── คอลัมน์ซ้าย ──────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:18px; min-width:0;">

            {{-- Binary Legs (เฉพาะแผน binary / hybrid) --}}
            @if($showBinary)
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:16px;">🔀 Binary Legs</div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    {{-- Left Leg --}}
                    <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
                        <div style="font-size:14px; font-weight:700; color:#5aa07e; margin-bottom:12px;">⬅️ Left Leg</div>
                        <div style="display:flex; flex-direction:column; gap:9px; font-size:12.5px;">
                            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">PV:</span><span class="tp-num" style="font-weight:700; color:#5aa07e;">{{ number_format($member->left_leg_pv ?? 0) }}</span></div>
                            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Sales:</span><span class="tp-num" style="font-weight:700; color:#5aa07e;">฿{{ number_format($member->left_leg_sales ?? 0, 2) }}</span></div>
                            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Members:</span><span class="tp-num" style="font-weight:700; color:#5aa07e;">{{ number_format($member->left_leg_members ?? 0) }}</span></div>
                        </div>
                    </div>

                    {{-- Right Leg --}}
                    <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
                        <div style="font-size:14px; font-weight:700; color:#d9534f; margin-bottom:12px;">➡️ Right Leg</div>
                        <div style="display:flex; flex-direction:column; gap:9px; font-size:12.5px;">
                            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">PV:</span><span class="tp-num" style="font-weight:700; color:#d9534f;">{{ number_format($member->right_leg_pv ?? 0) }}</span></div>
                            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Sales:</span><span class="tp-num" style="font-weight:700; color:#d9534f;">฿{{ number_format($member->right_leg_sales ?? 0, 2) }}</span></div>
                            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Members:</span><span class="tp-num" style="font-weight:700; color:#d9534f;">{{ number_format($member->right_leg_members ?? 0) }}</span></div>
                        </div>
                    </div>
                </div>

                {{-- Pair Matching Today --}}
                <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="font-size:13px; font-weight:700; color:var(--deep1); margin-bottom:14px;">📊 Pair Matching Today</div>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; text-align:center;">
                        <div>
                            <div style="font-size:11px; color:var(--ink2);">Weak Leg PV</div>
                            <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1); margin-top:3px;">{{ number_format($weakLegPv) }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--ink2);">Pairs</div>
                            <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1); margin-top:3px;">{{ number_format(floor($weakLegPv)) }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--ink2);">Est. Commission</div>
                            <div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e; margin-top:3px;">฿{{ number_format(floor($weakLegPv) * $pairComm, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Recent Commissions --}}
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;">
                    <div class="tp-section-h">💸 คอมมิชชันล่าสุด</div>
                    @if(RouteFacade::has('user.mlm.commissions'))
                        <a href="{{ route('user.mlm.commissions') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
                    @endif
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    @forelse($recentCommissions ?? [] as $commission)
                        @php
                            $cStatus = $commission->status;
                            $cColor  = $cStatus === 'paid' ? '#5aa07e' : ($cStatus === 'rejected' ? '#d9534f' : '#e0a52e');
                        @endphp
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                            <div style="min-width:0;">
                                <div style="font-size:13px; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $commission->type_display }}</div>
                                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $commission->created_at->diffForHumans() }}</div>
                            </div>
                            <div style="text-align:right; white-space:nowrap;">
                                <div class="tp-num" style="font-size:16px; font-weight:800; color:#5aa07e;">฿{{ number_format($commission->commission_amount, 2) }}</div>
                                <span class="tp-pill" style="margin-top:3px; color:{{ $cColor }}; background:color-mix(in srgb, {{ $cColor }} 16%, transparent);">{{ ucfirst($commission->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center; color:var(--ink2); padding:34px 0;">
                            <div style="font-size:44px; opacity:.5;">💰</div>
                            <div style="margin-top:8px; font-weight:600;">ยังไม่มีคอมมิชชัน</div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:16px;">🚀 Quick Actions</div>
                @php
                    $quickActions = [
                        ['🌳', 'ดูผังสายงาน', 'user.mlm.genealogy'],
                        ['🔗', 'Referral Link', 'user.mlm.referral'],
                        ['💸', 'ประวัติคอมมิชชัน', 'user.mlm.commissions'],
                        ['👥', 'ทีมของฉัน', 'user.mlm.team'],
                    ];
                @endphp
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    @foreach($quickActions as [$emoji, $label, $route])
                        @if(RouteFacade::has($route))
                            <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:9px; padding:18px 10px; text-decoration:none; color:inherit;">
                                <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:24px;">{{ $emoji }}</span>
                                <span style="font-size:13px; font-weight:700; text-align:center;">{{ $label }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── คอลัมน์ขวา ──────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:18px; min-width:0;">

            {{-- สถานะรักษายอด (Volume Retention) --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:16px;">💓 สถานะรักษายอด</div>

                @if(!$rtEnabled)
                    {{-- ระบบรักษายอดปิดอยู่ --}}
                    <div style="text-align:center; padding:18px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="font-size:13px; color:var(--ink2);">ระบบรักษายอดปิดอยู่</div>
                        <div style="color:#5aa07e; font-weight:700; margin-top:4px;">Active ทุกคน</div>
                    </div>
                @else
                    {{-- ป้ายสถานะ --}}
                    <div style="text-align:center; margin-bottom:16px;">
                        <span class="tp-pill" style="color:#fff; background:{{ $rtColor }}; font-size:12px; padding:6px 14px;">{{ $rtEmoji }} {{ $rtText }}</span>
                    </div>

                    {{-- วงแหวนความคืบหน้า PV (SVG) --}}
                    @php
                        $ringPct    = min($pvPct, 100);
                        $circ       = 2 * 3.14159 * 55;
                        $dashOffset = $circ - ($circ * $ringPct / 100);
                        $ringColor  = $pvPct >= 100 ? '#5aa07e' : ($pvPct >= 50 ? '#e0a52e' : '#d9534f');
                    @endphp
                    <div style="display:flex; justify-content:center; margin-bottom:16px;">
                        <svg width="140" height="140" viewBox="0 0 140 140">
                            <circle cx="70" cy="70" r="55" fill="none" stroke="color-mix(in srgb, var(--ink2) 22%, transparent)" stroke-width="10"/>
                            <circle cx="70" cy="70" r="55" fill="none" stroke="{{ $ringColor }}" stroke-width="10"
                                    stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $dashOffset }}"
                                    stroke-linecap="round"
                                    style="transform:rotate(-90deg); transform-origin:center; transition:stroke-dashoffset .5s;"/>
                            <text x="70" y="64" text-anchor="middle" dominant-baseline="middle" font-size="26" font-weight="800" fill="{{ $ringColor }}">{{ round($pvPct) }}%</text>
                            <text x="70" y="86" text-anchor="middle" dominant-baseline="middle" font-size="11" fill="var(--ink2)">PV เดือนนี้</text>
                        </svg>
                    </div>

                    {{-- รายละเอียด PV --}}
                    <div style="display:flex; flex-direction:column; gap:10px; font-size:12.5px;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--ink2);">PV เดือนนี้:</span>
                            <span class="tp-num" style="font-weight:700; color:{{ ($retentionStatus['monthly_pv'] ?? 0) >= ($retentionStatus['required_pv'] ?? 100) ? '#5aa07e' : 'var(--ink)' }};">
                                {{ number_format($retentionStatus['monthly_pv'] ?? 0, 0) }} / {{ number_format($retentionStatus['required_pv'] ?? 100, 0) }}
                            </span>
                        </div>

                        @if(($retentionStatus['remaining_pv'] ?? 0) > 0)
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--ink2);">PV ที่ต้องการเพิ่ม:</span>
                            <span class="tp-num" style="font-weight:700; color:#e0a52e;">{{ number_format($retentionStatus['remaining_pv'] ?? 0, 0) }} PV</span>
                        </div>
                        @endif

                        @if(($retentionStatus['days_since_last_purchase'] ?? null) !== null)
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--ink2);">ซื้อล่าสุดเมื่อ:</span>
                            <span class="tp-num" style="font-weight:700;">{{ $retentionStatus['days_since_last_purchase'] }} วันที่แล้ว</span>
                        </div>
                        @endif
                    </div>

                    {{-- Progress Bar --}}
                    <div style="height:12px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden; margin-top:14px;">
                        <div style="height:100%; width:{{ $ringPct }}%; border-radius:20px; background:linear-gradient(90deg, {{ $ringColor }}, color-mix(in srgb, {{ $ringColor }} 60%, #fff)); transition:width .5s ease;"></div>
                    </div>

                    {{-- ช่วงผ่อนผัน --}}
                    @if($retentionStatus['in_grace_period'] ?? false)
                    <div style="margin-top:14px; padding:11px 13px; border-radius:12px; box-shadow:var(--inset-sm); font-size:12px; color:#e0a52e;">
                        <i class="fas fa-clock"></i> ช่วงผ่อนผัน: เหลืออีก {{ max(0, ($retentionStatus['grace_days'] ?? 7) - ($retentionStatus['days_since_last_purchase'] ?? 0)) }} วัน
                    </div>
                    @endif

                    {{-- แจ้งเตือน PV ไม่ถึงเกณฑ์ --}}
                    @if($rtStatus === 'inactive')
                    <div style="margin-top:14px; padding:11px 13px; border-radius:12px; box-shadow:var(--inset-sm); font-size:12px; color:#d9534f;">
                        <i class="fas fa-triangle-exclamation"></i> <strong>PV ไม่ถึงเกณฑ์!</strong> คอมมิชชันจะถูกระงับจนกว่าจะซื้อสินค้าเพิ่ม
                    </div>
                    @endif
                @endif
            </div>

            {{-- Rank Progress --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:16px;">🎯 Rank Progress</div>

                @php
                    $rankPctVal   = 65;
                    $rankCirc     = 2 * 3.14159 * 65;
                    $rankOffset   = $rankCirc - ($rankCirc * $rankPctVal / 100);
                @endphp
                <div style="display:flex; justify-content:center; margin-bottom:16px;">
                    <svg width="150" height="150" viewBox="0 0 150 150">
                        <circle cx="75" cy="75" r="65" fill="none" stroke="color-mix(in srgb, var(--ink2) 22%, transparent)" stroke-width="10"/>
                        <circle cx="75" cy="75" r="65" fill="none" stroke="url(#rankGradient)" stroke-width="10"
                                stroke-dasharray="{{ $rankCirc }}" stroke-dashoffset="{{ $rankOffset }}"
                                stroke-linecap="round"
                                style="transform:rotate(-90deg); transform-origin:center; transition:stroke-dashoffset .5s;"/>
                        <defs>
                            <linearGradient id="rankGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="var(--accent1)"/>
                                <stop offset="100%" stop-color="var(--accent2)"/>
                            </linearGradient>
                        </defs>
                        <text x="75" y="75" text-anchor="middle" dominant-baseline="middle" font-size="30" font-weight="800" fill="var(--deep1)">{{ $rankPctVal }}%</text>
                    </svg>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px; font-size:12.5px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--ink2);">✓ PV Requirement:</span>
                        <span class="tp-num" style="font-weight:700; color:#5aa07e;">5,000 / 5,000</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--ink2);">○ Team PV:</span>
                        <span class="tp-num" style="font-weight:700;">12,000 / 20,000</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--ink2);">✓ Direct Referrals:</span>
                        <span class="tp-num" style="font-weight:700; color:#5aa07e;">3 / 3</span>
                    </div>
                </div>

                <div style="margin-top:16px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; margin-bottom:2px;">Next Rank:</div>
                    <div style="font-size:17px; font-weight:800; color:var(--deep1);">Silver</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">8,000 Team PV needed</div>
                </div>
            </div>

            {{-- Referral Link --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">🔗 Referral Link</div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="referral-link" value="{{ $referralUrl }}" readonly
                           class="tp-input" style="width:100%; font-size:12.5px;">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <button type="button" onclick="copyReferralLink()" class="tp-btn tp-btn-primary" style="justify-content:center;">📋 Copy</button>
                    <button type="button" onclick="shareReferralLink()" class="tp-btn" style="justify-content:center;">📤 Share</button>
                </div>

                {{-- QR Code placeholder --}}
                <div style="margin-top:18px; text-align:center;">
                    <div style="display:inline-block; padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div id="qr-code"></div>
                    </div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:8px;">Scan QR Code to Register</div>
                </div>
            </div>

            {{-- Tips --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;">💡 Tips</div>
                <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:9px; font-size:12.5px; color:var(--ink2);">
                    <li style="display:flex; gap:8px;"><span style="color:var(--deep1);">•</span><span>สร้างสมดุลระหว่างขาซ้ายและขวาเพื่อเพิ่มคอมมิชชัน Binary</span></li>
                    <li style="display:flex; gap:8px;"><span style="color:var(--deep1);">•</span><span>แนะนำทีมให้ซื้อสินค้าต่อเนื่องเพื่อสะสม PV</span></li>
                    <li style="display:flex; gap:8px;"><span style="color:var(--deep1);">•</span><span>ติดตามผังสายงานเพื่อดูการเติบโตของทีม</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    /* คอลัมน์เดียวบนจอเล็ก (responsive) */
    @media (max-width: 900px) {
        .tp-mlm-grid { grid-template-columns: 1fr !important; }
    }
</style>
<script>
// คัดลอกลิงก์แนะนำเพื่อน
function copyReferralLink() {
    var input = document.getElementById('referral-link');
    if (!input) return;
    var link = input.value;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(function () {
            if (window.showNotification) window.showNotification('คัดลอกลิงก์แนะนำสำเร็จ!', 'success');
        }).catch(function () {
            fallbackCopy(input);
        });
    } else {
        fallbackCopy(input);
    }
}

// วิธีคัดลอกสำรอง (กรณี clipboard API ใช้ไม่ได้)
function fallbackCopy(input) {
    input.select();
    try {
        document.execCommand('copy');
        if (window.showNotification) window.showNotification('คัดลอกลิงก์แนะนำสำเร็จ!', 'success');
    } catch (e) {
        if (window.showNotification) window.showNotification('คัดลอกไม่สำเร็จ', 'error');
    }
}

// แชร์ลิงก์แนะนำเพื่อน (Web Share API + fallback)
function shareReferralLink() {
    var input = document.getElementById('referral-link');
    if (!input) return;
    var link = input.value;
    if (navigator.share) {
        navigator.share({
            title: 'Join my MLM team!',
            text: 'Join my MLM team and start earning today!',
            url: link
        }).catch(function () { /* ผู้ใช้ยกเลิกการแชร์ */ });
    } else {
        copyReferralLink();
    }
}
</script>
@endpush
@endsection
