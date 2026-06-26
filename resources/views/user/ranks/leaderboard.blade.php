@extends('layouts.user-v4')

@section('title', 'กระดานผู้นำ')

@php
    // ── ข้อมูลผู้ใช้ปัจจุบัน + ค้นหาอันดับของตัวเอง ─────────────
    $currentUser  = auth()->user();
    $userPosition = collect($leaderboard)->search(function ($item) use ($currentUser) {
        return $item['user']->id === $currentUser->id;
    });
    $userRank = $userPosition !== false ? $userPosition + 1 : null;

    // ── สีเหรียญ Top 3 (gold/silver/bronze) ───────────────────
    $medalColors = ['#e0a52e', '#9aa4ad', '#c08a4a'];
    $medalEmoji  = ['🥇', '🥈', '🥉'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <a href="{{ route('user.ranks.dashboard') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-trophy" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">กระดานผู้นำ</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">Top Performers Leaderboard — แสดง {{ count($leaderboard) }} อันดับแรก</div>
            </div>
        </div>
    </div>

    {{-- ── อันดับของฉัน ──────────────────────────────────────── --}}
    @if($userRank)
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile tp-num" style="width:56px; height:56px; border-radius:18px; font-size:22px; font-weight:800;">{{ $userRank }}</span>
                <div style="min-width:0;">
                    <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">อันดับของคุณ</div>
                    <div style="font-size:18px; font-weight:800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $currentUser->name }}</div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">แต้มรวม</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);">{{ number_format($currentUser->rank_points ?? 0) }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── โพเดียม Top 3 ─────────────────────────────────────── --}}
    @if(count($leaderboard) >= 3)
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; align-items:end;">
        @php $podiumOrder = [1 => 'translateY(18px)', 0 => 'translateY(0)', 2 => 'translateY(18px)']; @endphp
        @foreach([1, 0, 2] as $slot)
            @php
                $p     = $leaderboard[$slot];
                $pUser = $p['user'];
                $pRank = $p['rank'];
                $pPts  = $p['points'] ?? 0;
                $isFirst = $slot === 0;
            @endphp
            <div class="tp-card tp-card-hover" style="padding:{{ $isFirst ? '22px 14px' : '18px 12px' }}; text-align:center; transform:{{ $podiumOrder[$slot] }};
                        {{ $isFirst ? 'box-shadow:var(--inset-sm), 0 0 0 2px color-mix(in srgb, '.$medalColors[0].' 50%, transparent);' : '' }}">
                @if($isFirst)
                    <div style="margin-bottom:6px;"><i class="fas fa-crown" style="font-size:22px; color:{{ $medalColors[0] }};"></i></div>
                @endif
                <span class="tp-tile" style="width:{{ $isFirst ? '72px' : '58px' }}; height:{{ $isFirst ? '72px' : '58px' }}; border-radius:50%; font-size:{{ $isFirst ? '36px' : '28px' }}; margin:0 auto; background:{{ $medalColors[$slot] }};">{{ $medalEmoji[$slot] }}</span>
                <div style="font-size:10.5px; color:var(--ink2); font-weight:700; margin-top:10px; letter-spacing:.4px;">#{{ $slot + 1 }}{{ $isFirst ? ' · CHAMPION' : '' }}</div>
                <div style="font-weight:800; font-size:{{ $isFirst ? '18px' : '14px' }}; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $pUser->name }}</div>
                <div style="margin-top:10px; padding:8px 10px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <div style="font-size:10px; color:var(--ink2);">แต้ม</div>
                    <div class="tp-num" style="font-size:{{ $isFirst ? '22px' : '18px' }}; font-weight:800; color:{{ $medalColors[$slot] }};">{{ number_format($pPts) }}</div>
                </div>
                @if($pRank)
                <div style="margin-top:10px; display:flex; align-items:center; justify-content:center; gap:5px; font-size:11px; color:var(--ink2);">
                    <x-rank-icon :rank="$pRank" size="xs" />
                    <span>{{ $pRank->name_th ?? $pRank->name }}</span>
                </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── ตารางผู้นำทั้งหมด ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:18px 22px; box-shadow:var(--inset-sm);">
            <div style="font-weight:800; font-size:16px;">🏆 ผู้นำทั้งหมด</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:2px;">แสดง {{ count($leaderboard) }} อันดับแรก</div>
        </div>

        @if(count($leaderboard) > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">อันดับ</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">ผู้ใช้</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">ระดับ</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">แต้ม</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaderboard as $index => $item)
                            @php
                                $user   = $item['user'];
                                $rank   = $item['rank'];
                                $points = $item['points'];
                                $isMe   = $user->id === auth()->id();
                                $isTop3 = $index < 3;
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);
                                       {{ $isMe ? 'background:color-mix(in srgb, var(--accent1) 12%, transparent);' : '' }}">
                                {{-- อันดับ --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <span class="tp-tile tp-num" style="width:38px; height:38px; border-radius:12px; font-size:14px; font-weight:800;
                                        {{ $isTop3 ? 'background:'.$medalColors[$index].';' : 'background:color-mix(in srgb, var(--ink2) 16%, transparent); color:var(--ink);' }}">{{ $index + 1 }}</span>
                                </td>

                                {{-- ผู้ใช้ --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <span class="tp-tile" style="width:38px; height:38px; border-radius:50%; font-size:15px; font-weight:800;">{{ mb_substr($user->name, 0, 1) }}</span>
                                        <div style="min-width:0;">
                                            <div style="font-size:13px; font-weight:700; color:var(--ink);">
                                                {{ $user->name }}
                                                @if($isMe)
                                                    <span class="tp-pill tp-pill-gold" style="font-size:9.5px; margin-left:4px;">คุณ</span>
                                                @endif
                                            </div>
                                            <div style="font-size:11px; color:var(--ink2);">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- ระดับ --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    @if($rank)
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <x-rank-icon :rank="$rank" size="sm" />
                                            <div>
                                                <div style="font-size:13px; font-weight:700; color:var(--ink);">{{ app()->getLocale() === 'th' ? ($rank->name_th ?? $rank->name) : $rank->name }}</div>
                                                <div style="font-size:11px; color:var(--ink2);">Level {{ $rank->level }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span style="font-size:13px; color:var(--ink2);">-</span>
                                    @endif
                                </td>

                                {{-- แต้ม --}}
                                <td style="padding:12px 16px; white-space:nowrap; text-align:right;">
                                    <div class="tp-num" style="font-size:16px; font-weight:800; color:var(--ink);">{{ number_format($points ?? 0) }}</div>
                                    <div style="font-size:10.5px; color:var(--ink2);">แต้ม</div>
                                </td>

                                {{-- สถานะ --}}
                                <td style="padding:12px 16px; white-space:nowrap; text-align:center;">
                                    @if($index === 0)
                                        <span class="tp-pill" style="color:#fff; background:#e0a52e;"><i class="fas fa-crown" style="font-size:10px;"></i> ผู้นำ</span>
                                    @elseif($index < 10)
                                        <span class="tp-pill" style="color:#fff; background:#5aa07e;"><i class="fas fa-star" style="font-size:10px;"></i> Top 10</span>
                                    @elseif($index < 50)
                                        <span class="tp-pill" style="color:#fff; background:#5689b8;">Top 50</span>
                                    @else
                                        <span class="tp-pill tp-pill-soft">Top 100</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">🏆</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีข้อมูลกระดานผู้นำ</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เริ่มสะสมแต้มเพื่อขึ้นอันดับกระดานผู้นำ</div>
            </div>
        @endif
    </div>

    {{-- ── กลับไปแดชบอร์ด ────────────────────────────────────── --}}
    <div style="text-align:center;">
        <a href="{{ route('user.ranks.dashboard') }}" class="tp-btn tp-btn-primary"><i class="fas fa-arrow-left"></i> กลับไปที่แดชบอร์ด</a>
    </div>

</div>
@endsection
