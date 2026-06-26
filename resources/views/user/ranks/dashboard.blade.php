@extends('layouts.user-v4')

@section('title', 'ระดับของฉัน')

{{--
/**
 * User Rank Dashboard — Theme V4 "นวลทองคำ"
 *
 * แสดงข้อมูล Rank ของผู้ใช้พร้อมเงื่อนไขและรางวัลอย่างครบถ้วน
 * - Rank ปัจจุบันและ Rank ถัดไป
 * - เงื่อนไขในการเลื่อนระดับ (Requirements)
 * - รางวัลและสิทธิพิเศษ (Bonuses & Privileges)
 * - ความคืบหน้า + เส้นทาง Roadmap
 *
 * ตัวแปรจาก RankController::dashboard():
 *   $user, $currentRank, $nextRank, $nextRankProgress, $leaderboardPosition,
 *   $currentRankRequirements, $currentRankBonuses, $currentRankPrivileges,
 *   $nextRankRequirements, $nextRankRequirementsProgress, $nextRankBonuses,
 *   $nextRankPrivileges, $allRanks
 *
 * @version 4.0.0
 * @date 2026-06-26
 */
--}}

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ป้ายเหรียญตาม level ────────────────────────────────────
    $rankBadges = [
        1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎',
        5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐',
    ];
    $currentLevel = $currentRank?->level ?? 1;
    $currentBadge = $rankBadges[$currentLevel] ?? '🏅';
    $nextLevel    = $nextRank?->level ?? null;
    $nextBadge    = $nextLevel ? ($rankBadges[$nextLevel] ?? '🏅') : null;

    // ── ลิงก์บัตร VIP (เผื่อชื่อ route ต่างกันระหว่างเวอร์ชัน) ──
    $idCardUrl = RouteFacade::has('user.ranks.id-card') ? route('user.ranks.id-card')
               : (RouteFacade::has('user.virtual-id-card') ? route('user.virtual-id-card') : null);

    // ── สีไอคอนสถานะ (hex semantic) ────────────────────────────
    $bonusTypeIcons = [
        'one_time'   => '💰',
        'monthly'    => '📆',
        'commission' => '📈',
        'multiplier' => '✖️',
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero: Rank ปัจจุบัน ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            {{-- ปุ่มย้อนกลับ --}}
            <div style="margin-bottom:14px;">
                <a href="{{ route('user.dashboard') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            </div>

            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px;">
                {{-- Avatar พร้อมกรอบ Rank (KEEP partial component) --}}
                <div style="flex-shrink:0;">
                    <x-rank-avatar
                        :user="$user"
                        :rank="$currentRank"
                        size="2xl"
                        :show-badge="true"
                    />
                </div>

                {{-- ข้อมูล Rank --}}
                <div style="flex:1; min-width:220px;">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px; text-transform:uppercase;">
                        ระดับปัจจุบันของคุณ · MY RANK
                    </div>
                    <h1 class="tp-num" style="font-size:clamp(22px,4vw,30px); font-weight:800; margin:6px 0 0; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <span style="font-size:1.4em;">{{ $currentBadge }}</span>
                        <span>{{ $currentRank?->name_th ?? $currentRank?->name ?? 'ไม่มีระดับ' }}</span>
                    </h1>
                    <div style="font-size:14px; color:var(--ink2); margin-top:4px;">Level {{ $currentLevel }}</div>

                    @if($currentRank?->description_th ?? $currentRank?->description)
                        <p style="font-size:13px; color:var(--ink2); margin:8px 0 0; max-width:640px;">
                            {{ $currentRank?->description_th ?? $currentRank?->description }}
                        </p>
                    @endif

                    {{-- สถิติย่อ --}}
                    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:16px;">
                        <div style="padding:8px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                            <div class="tp-num" style="font-size:18px; font-weight:800;">{{ number_format($user->rank_points ?? 0) }}</div>
                            <div style="font-size:11px; color:var(--ink2);">คะแนนสะสม</div>
                        </div>
                        <div style="padding:8px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                            <div class="tp-num" style="font-size:18px; font-weight:800;">#{{ $leaderboardPosition }}</div>
                            <div style="font-size:11px; color:var(--ink2);">อันดับในกระดาน</div>
                        </div>
                        @if($currentRank?->commission_rate)
                        <div style="padding:8px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                            <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1);">{{ $currentRank->commission_rate }}%</div>
                            <div style="font-size:11px; color:var(--ink2);">ค่าคอมมิชชั่น</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สิทธิพิเศษของ Rank ปัจจุบัน ─────────────────────── --}}
    @if($currentRankBonuses->count() > 0 || count($currentRankPrivileges) > 0)
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">🎁 สิทธิพิเศษของคุณในระดับปัจจุบัน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">
            {{-- โบนัส --}}
            @foreach($currentRankBonuses as $bonus)
            <div class="tp-card" style="padding:16px; display:flex; align-items:flex-start; gap:13px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; flex-shrink:0; background:#5aa07e;">
                    {{ $bonusTypeIcons[$bonus->bonus_type] ?? '🎁' }}
                </span>
                <div style="min-width:0;">
                    <div style="font-weight:700; font-size:14px;">{{ $bonus->name_th ?? $bonus->name }}</div>
                    @if($bonus->amount > 0)
                        <div class="tp-num" style="font-size:13px; font-weight:700; color:#5aa07e;">฿{{ number_format($bonus->amount, 2) }}</div>
                    @elseif($bonus->percentage > 0)
                        <div class="tp-num" style="font-size:13px; font-weight:700; color:#5aa07e;">{{ $bonus->percentage }}%</div>
                    @endif
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $bonus->description_th ?? $bonus->description }}</div>
                </div>
            </div>
            @endforeach

            {{-- สิทธิพิเศษ --}}
            @foreach($currentRankPrivileges as $key => $privilege)
            <div class="tp-card" style="padding:16px; display:flex; align-items:flex-start; gap:13px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:22px; flex-shrink:0; background:color-mix(in srgb, var(--accent1) 18%, transparent);">
                    {{ $privilege['icon'] ?? '⭐' }}
                </span>
                <div style="min-width:0;">
                    <div style="font-weight:700; font-size:14px;">{{ $privilege['name_th'] ?? $privilege['name'] }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $privilege['description_th'] ?? $privilege['description'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── ความคืบหน้าสู่ระดับถัดไป ──────────────────────── --}}
    @if($nextRank)
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:18px 22px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin:0;">🎯 ความคืบหน้าสู่ระดับถัดไป</div>
            <span class="tp-pill tp-pill-gold" style="font-size:13px;">{{ $nextBadge }} {{ $nextRank->name_th ?? $nextRank->name }}</span>
        </div>

        <div style="padding:22px;">
            {{-- แถบความคืบหน้ารวม --}}
            @if($nextRankProgress)
            <div style="margin-bottom:26px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink2);">ความคืบหน้ารวม</span>
                    <span class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1);">
                        {{ number_format($nextRankProgress->progress_percentage ?? 0, 1) }}%
                    </span>
                </div>
                <div style="height:18px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ min(100, $nextRankProgress->progress_percentage ?? 0) }}%; border-radius:20px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); transition:width .5s ease;"></div>
                </div>
                <div style="text-align:center; font-size:12.5px; color:var(--ink2); margin-top:9px;">
                    อีก
                    <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ number_format(max(0, ($nextRankProgress->target_points ?? 0) - ($user->rank_points ?? 0))) }}</span>
                    คะแนนจะถึงระดับ
                    <strong>{{ $nextRank->name_th ?? $nextRank->name }}</strong>
                </div>
            </div>
            @endif

            {{-- เงื่อนไขที่ต้องทำให้ครบ --}}
            <div class="tp-section-h" style="margin-bottom:14px;">📋 เงื่อนไขที่ต้องทำให้ครบ</div>

            @if($nextRankRequirements->count() > 0)
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach($nextRankRequirements as $requirement)
                    @php
                        $progress   = $nextRankRequirementsProgress[$requirement->id] ?? null;
                        $isMet      = $progress['met'] ?? false;
                        $percentage = $progress['percentage'] ?? 0;
                        $current    = $progress['current'] ?? 0;
                        $target     = $progress['target'] ?? $requirement->target_value;

                        // ไอคอนตามประเภทเงื่อนไข
                        $typeIcons = [
                            'points'              => '🎯',
                            'referrals'           => '👥',
                            'sales'               => '💰',
                            'active_referrals'    => '✅',
                            'team_sales'          => '📊',
                            'consecutive_months'  => '📅',
                            'diamond_legs'        => '💎',
                            'crown_legs'          => '👑',
                            'royal_legs'          => '🏆',
                        ];
                        $reqIcon = $typeIcons[$requirement->requirement_type] ?? '📌';
                    @endphp

                    <div class="tp-card" style="padding:16px; {{ $isMet ? 'border-left:4px solid #5aa07e;' : 'box-shadow:var(--inset-sm);' }}">
                        <div style="display:flex; align-items:flex-start; gap:13px;">
                            {{-- ไอคอนสถานะ --}}
                            <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; flex-shrink:0; background:{{ $isMet ? '#5aa07e' : 'color-mix(in srgb, var(--ink2) 18%, transparent)' }};">
                                @if($isMet)
                                    <span style="color:#fff;">✓</span>
                                @else
                                    {{ $reqIcon }}
                                @endif
                            </span>

                            {{-- เนื้อหา --}}
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                                    <div style="font-weight:700; font-size:14px;">
                                        {{ $requirement->name_th ?? $requirement->name }}
                                        @if($requirement->is_required)
                                            <span style="color:#d9534f;">*</span>
                                        @endif
                                    </div>
                                    @if($isMet)
                                        <span class="tp-pill" style="color:#fff; background:#5aa07e;">✓ ผ่านแล้ว</span>
                                    @else
                                        <span class="tp-num" style="font-size:12.5px; color:var(--ink2);">
                                            {{ number_format($current, 0) }} / {{ number_format($target, 0) }} {{ $requirement->unit }}
                                        </span>
                                    @endif
                                </div>

                                @if($requirement->description_th ?? $requirement->description)
                                <div style="font-size:12.5px; color:var(--ink2); margin-bottom:8px;">
                                    {{ $requirement->description_th ?? $requirement->description }}
                                </div>
                                @endif

                                {{-- แถบความคืบหน้า --}}
                                @if(!$isMet)
                                <div style="height:10px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                                    <div style="height:100%; width:{{ min(100, $percentage) }}%; border-radius:20px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); transition:width .5s ease;"></div>
                                </div>
                                <div class="tp-num" style="font-size:11px; color:var(--ink2); margin-top:5px; text-align:right;">
                                    {{ number_format($percentage, 1) }}%
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center; padding:40px 20px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:40px; opacity:.6;">📋</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:8px;">ยังไม่มีเงื่อนไขที่กำหนดไว้สำหรับระดับนี้</div>
            </div>
            @endif

            {{-- สิ่งที่จะได้รับเมื่อเลื่อนระดับ --}}
            @if($nextRankBonuses->count() > 0 || count($nextRankPrivileges) > 0)
            <div style="margin-top:26px;">
                <div class="tp-section-h" style="margin-bottom:14px;">🎁 สิ่งที่คุณจะได้รับเมื่อเลื่อนระดับ</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:12px;">
                    {{-- โบนัสของ Rank ถัดไป --}}
                    @foreach($nextRankBonuses as $bonus)
                    <div style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <span style="font-size:26px;">{{ $bonusTypeIcons[$bonus->bonus_type] ?? '🎁' }}</span>
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:13.5px;">{{ $bonus->name_th ?? $bonus->name }}</div>
                            @if($bonus->amount > 0)
                                <div class="tp-num" style="font-size:13px; font-weight:700; color:#e0a52e;">฿{{ number_format($bonus->amount, 2) }}</div>
                            @elseif($bonus->percentage > 0)
                                <div class="tp-num" style="font-size:13px; font-weight:700; color:#e0a52e;">{{ $bonus->percentage }}%</div>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    {{-- สิทธิพิเศษของ Rank ถัดไป --}}
                    @foreach($nextRankPrivileges as $key => $privilege)
                    <div style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <span style="font-size:26px;">{{ $privilege['icon'] ?? '⭐' }}</span>
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:13.5px;">{{ $privilege['name_th'] ?? $privilege['name'] }}</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $privilege['description_th'] ?? $privilege['description'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    {{-- ── Rank สูงสุดแล้ว ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="text-align:center; padding:36px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 22%, transparent), transparent 75%);">
            <div style="font-size:56px;">🏆</div>
            <h2 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:10px 0 4px;">ยินดีด้วย!</h2>
            <div style="font-size:15px; color:var(--ink2);">คุณอยู่ในระดับสูงสุดแล้ว</div>
            <div style="display:inline-flex; align-items:center; gap:10px; margin-top:18px; padding:10px 20px; border-radius:16px; box-shadow:var(--inset-sm);">
                <span style="font-size:34px;">{{ $currentBadge }}</span>
                <span class="tp-num" style="font-size:20px; font-weight:800;">{{ $currentRank?->name_th ?? $currentRank?->name ?? 'ไม่มีระดับ' }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- ── เส้นทางสู่ความสำเร็จ (Roadmap) ─────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:18px 22px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin:0;">🗺️ เส้นทางสู่ความสำเร็จ</div>
        </div>

        <div style="padding:22px;">
            <div style="position:relative;">
                {{-- เส้นแนวตั้ง --}}
                <div style="position:absolute; left:31px; top:0; bottom:0; width:2px; background:color-mix(in srgb, var(--ink2) 18%, transparent);"></div>

                <div style="display:flex; flex-direction:column; gap:18px;">
                    @foreach($allRanks as $rank)
                        @php
                            $isCurrentRank = $currentRank && $currentRank->id === $rank->id;
                            $isAchieved    = $currentRank && $currentRank->level >= $rank->level;
                            $badge         = $rankBadges[$rank->level] ?? '🏅';
                            $nodeBg = $isCurrentRank
                                ? 'linear-gradient(135deg, var(--accent1), var(--accent2))'
                                : ($isAchieved ? '#5aa07e' : 'color-mix(in srgb, var(--ink2) 18%, transparent)');
                        @endphp

                        <div style="position:relative; display:flex; align-items:flex-start; gap:18px;">
                            {{-- วงไอคอน --}}
                            <span style="position:relative; z-index:1; flex-shrink:0; width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:26px; box-shadow:var(--inset-sm); background:{{ $nodeBg }}; {{ $isCurrentRank ? 'outline:3px solid color-mix(in srgb, var(--accent1) 45%, transparent); outline-offset:2px;' : '' }}">
                                {{ $badge }}
                            </span>

                            {{-- การ์ดเนื้อหา --}}
                            <div style="flex:1; min-width:0; padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); {{ $isCurrentRank ? 'border-left:4px solid var(--accent1);' : ($isAchieved ? 'border-left:4px solid #5aa07e;' : '') }}">
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                    <div style="min-width:0;">
                                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <div style="font-weight:700; font-size:15px;">{{ $rank->name_th ?? $rank->name }}</div>
                                            @if($isCurrentRank)
                                                <span class="tp-pill" style="color:#fff; background:var(--accent1);">คุณอยู่ที่นี่</span>
                                            @elseif($isAchieved)
                                                <span class="tp-pill" style="color:#fff; background:#5aa07e;">✓ ผ่านแล้ว</span>
                                            @endif
                                        </div>
                                        <div style="font-size:12px; color:var(--ink2); margin-top:2px;">Level {{ $rank->level }}</div>
                                    </div>

                                    @if($rank->commission_rate)
                                    <div style="text-align:right;">
                                        <div class="tp-num" style="font-size:15px; font-weight:800; color:var(--deep1);">{{ $rank->commission_rate }}%</div>
                                        <div style="font-size:11px; color:var(--ink2);">ค่าคอมมิชชั่น</div>
                                    </div>
                                    @endif
                                </div>

                                {{-- ตัวอย่างรางวัล --}}
                                @if($rank->activeBonuses->count() > 0)
                                <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:7px;">
                                    @foreach($rank->activeBonuses->take(3) as $bonus)
                                        <span class="tp-pill tp-pill-soft" style="font-size:11px;">🎁 {{ $bonus->name_th ?? $bonus->name }}</span>
                                    @endforeach
                                    @if($rank->activeBonuses->count() > 3)
                                        <span style="font-size:11px; color:var(--ink2); align-self:center;">+{{ $rank->activeBonuses->count() - 3 }} อื่นๆ</span>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── เมนูด่วน ──────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px;">
        @if(RouteFacade::has('user.ranks.progress'))
        <a href="{{ route('user.ranks.progress') }}" class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px; padding:18px; text-decoration:none; color:inherit;">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px; flex-shrink:0;">📊</span>
            <div>
                <div style="font-weight:700; font-size:15px;">ดูความคืบหน้า</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:2px;">ติดตามความก้าวหน้าทุกระดับ</div>
            </div>
        </a>
        @endif

        @if(RouteFacade::has('user.ranks.leaderboard'))
        <a href="{{ route('user.ranks.leaderboard') }}" class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px; padding:18px; text-decoration:none; color:inherit;">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px; flex-shrink:0; background:color-mix(in srgb, var(--accent1) 18%, transparent);">🏆</span>
            <div>
                <div style="font-weight:700; font-size:15px;">กระดานผู้นำ</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:2px;">เปรียบเทียบกับสมาชิกอื่น</div>
            </div>
        </a>
        @endif

        @if($idCardUrl)
        <a href="{{ $idCardUrl }}" class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px; padding:18px; text-decoration:none; color:inherit;">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px; flex-shrink:0; background:#e0a52e;">🪪</span>
            <div>
                <div style="font-weight:700; font-size:15px;">บัตรสมาชิก</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:2px;">ดูบัตรประจำตัวเสมือนของคุณ</div>
            </div>
        </a>
        @endif
    </div>

    {{-- ── วิธีสะสมคะแนนและเลื่อนระดับ ─────────────────────── --}}
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">💡 วิธีสะสมคะแนนและเลื่อนระดับ</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#5aa07e;">👥</span>
                <div style="font-weight:700; font-size:14px; margin-top:12px;">แนะนำสมาชิกใหม่</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เชิญเพื่อนมาสมัครสมาชิกเพื่อรับคะแนนโบนัส</div>
            </div>

            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#5689b8;">💰</span>
                <div style="font-weight:700; font-size:14px; margin-top:12px;">สร้างยอดขาย</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ทำยอดขายเพื่อสะสม PV และคะแนน</div>
            </div>

            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:color-mix(in srgb, var(--accent1) 22%, transparent);">📈</span>
                <div style="font-weight:700; font-size:14px; margin-top:12px;">พัฒนาทีม</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ช่วยทีมให้เติบโตเพื่อรับโบนัสทีม</div>
            </div>

            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#e0a52e;">🎯</span>
                <div style="font-weight:700; font-size:14px; margin-top:12px;">รักษาความต่อเนื่อง</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ทำยอดอย่างสม่ำเสมอทุกเดือน</div>
            </div>
        </div>
    </div>

</div>
@endsection
