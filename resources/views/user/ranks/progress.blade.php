@extends('layouts.user-v4')

@section('title', 'ความคืบหน้ายศ')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero: ความคืบหน้ายศ ──────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.ranks.dashboard'))
                <a href="{{ route('user.ranks.dashboard') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-chart-line" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ความคืบหน้ายศ</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามความคืบหน้าสู่ยศที่สูงขึ้น</div>
            </div>
        </div>
    </div>

    {{-- ── ยศปัจจุบัน ────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div style="text-align:center; margin-bottom:22px;">
            <div class="tp-section-h" style="justify-content:center; margin-bottom:16px;">🏅 ยศปัจจุบันของคุณ</div>
            @if($user->currentRank)
                <div style="display:inline-block;">
                    <div style="display:flex; justify-content:center; margin-bottom:14px;">
                        <x-rank-icon :rank="$user->currentRank" size="2xl" />
                    </div>
                    <h3 class="tp-num" style="font-size:26px; font-weight:800; margin:0 0 6px;">{{ $user->currentRank->name }}</h3>
                    <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $user->currentRank->description ?? '' }}</p>
                    <div style="margin-top:14px;">
                        <span class="tp-pill tp-pill-gold" style="font-size:14px; padding:7px 18px;">ระดับ {{ $user->currentRank->level }}</span>
                    </div>
                </div>
            @else
                <div style="text-align:center; padding:24px 0;">
                    <div style="font-size:54px;">🎯</div>
                    <div style="font-weight:700; font-size:16px; margin-top:8px;">ยังไม่มียศ</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เริ่มต้นสร้างทีมและสะสมคะแนนเพื่อรับยศแรกของคุณ</div>
                </div>
            @endif
        </div>

        {{-- สถิติปัจจุบัน 3 ใบ --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm); text-align:center;">
                <div style="font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:8px;">คะแนนปัจจุบัน</div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:#5689b8;">{{ number_format($user->rank_points ?? 0, 0) }}</div>
            </div>
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm); text-align:center;">
                <div style="font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:8px;">จำนวนทีม</div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--deep1);">{{ $user->team_count ?? 0 }}</div>
            </div>
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm); text-align:center;">
                <div style="font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:8px;">รายได้รวม</div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:#5aa07e;">฿{{ number_format($user->total_commissions ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── เส้นทางความก้าวหน้า ───────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:18px;">📊 เส้นทางความก้าวหน้า</div>

        <div style="display:flex; flex-direction:column; gap:18px;">
            @foreach($allRanks as $index => $rank)
                @php
                    $isCurrentRank = $user->currentRank && $user->currentRank->id === $rank->id;
                    $isAchieved = $user->currentRank && $user->currentRank->level >= $rank->level;
                    $progress = $userProgress->firstWhere('target_rank_id', $rank->id);
                    $progressPercentage = $progress->progress_percentage ?? 0;

                    // สีกรอบตามสถานะยศ (ปัจจุบัน / ผ่านแล้ว / ยังไม่ถึง)
                    if ($isCurrentRank) {
                        $borderColor = 'var(--accent1)';
                        $borderWidth = '2px';
                    } elseif ($isAchieved) {
                        $borderColor = '#5aa07e';
                        $borderWidth = '1px';
                    } else {
                        $borderColor = 'color-mix(in srgb, var(--ink2) 22%, transparent)';
                        $borderWidth = '1px';
                    }
                @endphp

                <div style="position:relative;">
                    {{-- เส้นเชื่อมไทม์ไลน์ --}}
                    @if(!$loop->last)
                        <div style="position:absolute; left:30px; top:78px; width:3px; height:100%;
                                    background:{{ $isAchieved ? '#5aa07e' : 'color-mix(in srgb, var(--ink2) 22%, transparent)' }}; border-radius:3px;"></div>
                    @endif

                    {{-- การ์ดยศ --}}
                    <div style="position:relative; padding:18px; border-radius:16px; box-shadow:var(--inset-sm);
                                border:{{ $borderWidth }} solid {{ $borderColor }};">
                        <div style="display:flex; align-items:flex-start; gap:18px;">
                            {{-- ไอคอนยศ --}}
                            <div style="flex-shrink:0; position:relative; z-index:1;">
                                <x-rank-icon :rank="$rank" size="lg" />
                            </div>

                            {{-- เนื้อหา --}}
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px;">
                                    <div style="min-width:0;">
                                        <h3 style="font-size:17px; font-weight:700; margin:0; display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
                                            {{ $rank->name }}
                                            @if($isCurrentRank)
                                                <span class="tp-pill tp-pill-gold" style="font-size:10.5px;">ยศปัจจุบัน</span>
                                            @elseif($isAchieved)
                                                <span class="tp-pill" style="font-size:10.5px; color:#fff; background:#5aa07e;">✓ ผ่านแล้ว</span>
                                            @endif
                                        </h3>
                                        <p style="font-size:12.5px; color:var(--ink2); margin:4px 0 0;">{{ $rank->description ?? '' }}</p>
                                    </div>
                                    <div style="text-align:right; flex-shrink:0;">
                                        <div style="font-size:11px; color:var(--ink2);">ระดับ</div>
                                        <div class="tp-num" style="font-size:22px; font-weight:800;">{{ $rank->level }}</div>
                                    </div>
                                </div>

                                {{-- เงื่อนไข --}}
                                @if($rank->requirements)
                                    <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm); margin-bottom:10px;">
                                        <div style="font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:8px;">เงื่อนไข:</div>
                                        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:5px;">
                                            @foreach($rank->requirements as $requirement)
                                                <li style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--ink2);">
                                                    <span style="color:{{ $isAchieved ? '#5aa07e' : 'var(--ink2)' }};">{{ $isAchieved ? '✅' : '○' }}</span>
                                                    <span>{{ $requirement }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- แถบความคืบหน้า (เฉพาะยศที่ยังไม่ผ่าน) --}}
                                @if(!$isAchieved && $progress)
                                    <div>
                                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px; color:var(--ink2); margin-bottom:6px;">
                                            <span>ความคืบหน้า</span>
                                            <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ number_format($progressPercentage, 1) }}%</span>
                                        </div>
                                        <div style="height:12px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                                            <div style="height:100%; width:{{ min($progressPercentage, 100) }}%; border-radius:20px;
                                                        background:linear-gradient(90deg, var(--accent1), var(--accent2)); transition:width .5s ease;"></div>
                                        </div>
                                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--ink2); margin-top:6px;">
                                            <span class="tp-num">{{ number_format($progress->current_points ?? 0, 0) }} คะแนน</span>
                                            <span class="tp-num">{{ number_format($progress->target_points ?? 0, 0) }} คะแนน</span>
                                        </div>
                                    </div>
                                @endif

                                {{-- สิทธิพิเศษ --}}
                                @if($rank->benefits)
                                    <div style="margin-top:10px; padding:11px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                                        <div style="font-size:12.5px; font-weight:700; color:#5689b8; margin-bottom:4px;">สิทธิพิเศษ:</div>
                                        <div style="font-size:12.5px; color:var(--ink2);">{{ implode(', ', $rank->benefits) }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── เคล็ดลับเพื่อเลื่อนยศ ──────────────────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💡 เคล็ดลับเพื่อเลื่อนยศ</div>
        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--ink2);">
            <li style="display:flex; gap:10px; align-items:flex-start;">
                <span>✅</span>
                <span><strong style="color:var(--ink);">สร้างทีมใหม่:</strong> เชิญสมาชิกใหม่เพื่อเพิ่มทีมและสร้างรายได้</span>
            </li>
            <li style="display:flex; gap:10px; align-items:flex-start;">
                <span>✅</span>
                <span><strong style="color:var(--ink);">รักษาทีมที่มีอยู่:</strong> ช่วยเหลือทีมให้ประสบความสำเร็จ</span>
            </li>
            <li style="display:flex; gap:10px; align-items:flex-start;">
                <span>✅</span>
                <span><strong style="color:var(--ink);">สะสม PV:</strong> ซื้อสินค้าและบริการเพื่อสะสมคะแนน</span>
            </li>
            <li style="display:flex; gap:10px; align-items:flex-start;">
                <span>✅</span>
                <span><strong style="color:var(--ink);">เรียนรู้ตลอดเวลา:</strong> พัฒนาทักษะการขายและการสร้างทีม</span>
            </li>
        </ul>
    </div>
</div>
@endsection
