@extends('layouts.admin-v4')

@section('title', 'ภาพรวมระบบดูดวง')

@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;

    // กราฟแท่ง 7 วัน
    $rMax = max(1, (int) (count($chartReadings) ? max($chartReadings) : 0));

    // โดนัทตามหมวดหมู่ (conic-gradient)
    $catTotal = $categoryStats->sum();
    $catPalette = ['var(--accent1)', '#d6824a', '#5aa07e', '#5689b8', '#b79ae8', '#e0a52e'];
    $catLegend = [];
    if ($catTotal > 0) {
        $acc = 0; $stops = []; $ci = 0;
        foreach ($categoryStats as $name => $cnt) {
            $start = $acc / $catTotal * 360; $acc += $cnt; $end = $acc / $catTotal * 360;
            $col = $catPalette[$ci % count($catPalette)];
            $stops[] = $col . ' ' . round($start, 2) . 'deg ' . round($end, 2) . 'deg';
            $catLegend[] = ['name' => $name, 'cnt' => $cnt, 'col' => $col, 'pct' => round($cnt / $catTotal * 100)];
            $ci++;
        }
        $catConic = 'conic-gradient(' . implode(', ', $stops) . ')';
    } else {
        $catConic = 'conic-gradient(color-mix(in srgb, var(--ink2) 22%, transparent) 0deg 360deg)';
    }
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- หัวข้อ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · DASHBOARD</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ภาพรวมระบบดูดวง 🔮</h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.readings.index') }}" class="tp-btn"><i class="fas fa-list"></i> คำทำนายทั้งหมด</a>
            <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-primary"><i class="fas fa-gear"></i> ตั้งค่า</a>
        </div>
    </div>

    {{-- แถบสถานะ AI --}}
    <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:13px; flex-wrap:wrap;">
        <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:17px;"><i class="fas fa-robot"></i></span>
        <div style="flex:1; min-width:160px;">
            <div style="font-size:12px; color:var(--ink2); font-weight:600;">สถานะ AI แม่หมอ</div>
            <div class="tp-num" style="font-size:14px; font-weight:700;">{{ $aiStatus['provider'] ?? '—' }} · {{ $aiStatus['model'] ?? '—' }}</div>
        </div>
        @unless($aiStatus['has_key'] ?? false)
            <span class="tp-pill" style="color:#fff; background:#e0a52e;"><i class="fas fa-triangle-exclamation"></i> ยังไม่ตั้ง API Key</span>
        @endunless
        <span class="tp-pill" style="color:#fff; background:{{ ($aiStatus['enabled'] ?? false) ? '#5aa07e' : '#d9534f' }};">
            <i class="fas fa-{{ ($aiStatus['enabled'] ?? false) ? 'circle-check' : 'circle-xmark' }}"></i>
            {{ ($aiStatus['enabled'] ?? false) ? 'เปิดใช้งาน' : 'ปิดอยู่' }}
        </span>
    </div>

    {{-- KPI --}}
    @php
        $kpis = [
            ['คำทำนายวันนี้', 'Readings today', number_format($stats['today']), 'ทั้งหมด ' . number_format($stats['total']), 'fa-wand-magic-sparkles'],
            ['ผู้ใช้ดูดวง', 'Unique users', number_format($stats['unique_users']), 'สัปดาห์นี้ ' . number_format($stats['this_week']), 'fa-users'],
            ['รายได้เดือนนี้', 'Revenue (month)', '฿' . number_format($stats['month_revenue'], 0), 'วันนี้ ฿' . number_format($stats['today_revenue'], 0), 'fa-coins'],
            ['อัตราแปลง Deep', 'Deep conversion', $stats['conversion_rate'] . '%', 'Deep ' . number_format($stats['deep_count']), 'fa-gem'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px;">
        @foreach($kpis as [$label, $en, $val, $sub, $icon])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between;">
                    <div>
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $en }}</div>
                    </div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px;"><i class="fas {{ $icon }}"></i></span>
                </div>
                <div class="tp-num" style="font-size:28px; font-weight:800; margin:10px 0 4px;">{{ $val }}</div>
                <div style="font-size:11px; color:var(--ink2);">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    {{-- กราฟ: คำทำนาย 7 วัน + โดนัทหมวดหมู่ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <div>
                    <div style="font-weight:700; font-size:15px;">คำทำนายรายวัน</div>
                    <div style="font-size:11px; color:var(--ink2);">Readings — 7 วันล่าสุด</div>
                </div>
                <span class="tp-pill tp-pill-soft tp-num">{{ number_format(array_sum($chartReadings)) }} ครั้ง</span>
            </div>
            <div class="tp-bars">
                @foreach($chartLabels as $idx => $lbl)
                    <div class="col" title="{{ $lbl }}: {{ $chartReadings[$idx] }} ครั้ง">
                        <div style="display:flex; align-items:flex-end; justify-content:center; height:100%; width:100%;">
                            <span class="bar a" style="height:{{ max(3, round(($chartReadings[$idx] / $rMax) * 100)) }}%;"></span>
                        </div>
                        <span class="lbl">{{ Str::limit($lbl, 6, '') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="tp-card" style="padding:20px;">
            <div style="margin-bottom:16px;">
                <div style="font-weight:700; font-size:15px;">สัดส่วนตามหมวดหมู่</div>
                <div style="font-size:11px; color:var(--ink2);">By category</div>
            </div>
            <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
                <div style="position:relative; width:150px; height:150px; flex:none;">
                    <div style="width:100%; height:100%; border-radius:50%; background:{{ $catConic }}; -webkit-mask:radial-gradient(circle 46px at center, transparent 98%, #000 100%); mask:radial-gradient(circle 46px at center, transparent 98%, #000 100%);"></div>
                    <div style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                        <span class="tp-num" style="font-size:22px; font-weight:800;">{{ number_format($stats['today']) }}</span>
                        <span style="font-size:10px; color:var(--ink2);">วันนี้</span>
                    </div>
                </div>
                <div style="flex:1; min-width:140px; display:flex; flex-direction:column; gap:9px;">
                    @forelse($catLegend as $lg)
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="width:11px; height:11px; border-radius:4px; background:{{ $lg['col'] }}; flex:none;"></span>
                            <span style="font-size:12.5px; color:var(--ink2); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $lg['name'] }}</span>
                            <span class="tp-num" style="font-weight:700; font-size:12.5px;">{{ $lg['pct'] }}%</span>
                        </div>
                    @empty
                        <div style="font-size:12.5px; color:var(--ink2);">ยังไม่มีข้อมูลหมวดหมู่</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- รายได้ วันนี้ / สัปดาห์ / เดือน --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
        @php
            $rev = [
                ['รายได้วันนี้', $stats['today_revenue'], 'fa-calendar-day'],
                ['รายได้สัปดาห์นี้', $stats['week_revenue'], 'fa-calendar-week'],
                ['รายได้เดือนนี้', $stats['month_revenue'], 'fa-calendar'],
                ['รายได้ทั้งหมด', $stats['total_revenue'], 'fa-sack-dollar'],
            ];
        @endphp
        @foreach($rev as [$label, $amount, $icon])
            <div class="tp-card" style="padding:16px; display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:15px;"><i class="fas {{ $icon }}"></i></span>
                <div>
                    <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1);">฿{{ number_format($amount, 0) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- เมนูลัด --}}
    @php
        $nav = [
            ['fa-flask', 'AI Playground', 'admin.fortune.playground'],
            ['fa-tower-broadcast', 'ช่องทาง', 'admin.fortune.channels.index'],
            ['fa-layer-group', 'หมวดหมู่', 'admin.fortune.categories.index'],
            ['fa-users', 'ผู้ใช้ดูดวง', 'admin.fortune.users.index'],
            ['fa-file-invoice-dollar', 'บิล/รายได้', 'admin.fortune.billing.index'],
            ['fa-bullhorn', 'การตลาด', 'admin.fortune.marketing.index'],
        ];
    @endphp
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">⚡ เมนูลัด</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
            @foreach($nav as [$ic, $lb, $rt])
                @if(Route::has($rt))
                    <a href="{{ route($rt) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:16px 10px; text-decoration:none; color:inherit;">
                        <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:18px;"><i class="fas {{ $ic }}"></i></span>
                        <span style="font-size:12.5px; font-weight:600; text-align:center;">{{ $lb }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Top users + คำทำนายล่าสุด --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:12px;">🏆 Top 5 ผู้ใช้ดูดวงบ่อย</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @forelse($topUsers as $i => $u)
                    <div style="display:flex; align-items:center; gap:11px; padding:9px 11px; border-radius:13px; box-shadow:var(--inset-sm);">
                        <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:13px;">{{ [0=>'🥇',1=>'🥈',2=>'🥉'][$i] ?? ($i + 1) }}</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $u->facebook_user_name ?: 'ไม่ทราบชื่อ' }}</div>
                            <div style="font-size:11px; color:var(--ink2);">ล่าสุด {{ \Carbon\Carbon::parse($u->last_reading)->diffForHumans() }}</div>
                        </div>
                        <span class="tp-num" style="font-weight:700; font-size:13px; color:var(--deep1);">{{ number_format($u->reading_count) }} ครั้ง</span>
                    </div>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:24px 0; font-size:13px;">ยังไม่มีข้อมูล</div>
                @endforelse
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <div class="tp-section-h">🕐 คำทำนายล่าสุด</div>
                <a href="{{ route('admin.fortune.readings.index') }}" style="font-size:12px; color:var(--deep1); text-decoration:none; font-weight:600;">ดูทั้งหมด →</a>
            </div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:340px; overflow-y:auto;">
                @forelse($recentReadings as $r)
                    <a href="{{ route('admin.fortune.readings.show', $r->id) }}" style="display:flex; align-items:center; gap:11px; padding:9px 11px; border-radius:13px; box-shadow:var(--inset-sm); text-decoration:none; color:inherit;">
                        <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:13px;">{{ mb_substr($r->facebook_user_name ?: '?', 0, 1) }}</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $r->facebook_user_name ?: 'ไม่ทราบชื่อ' }}</div>
                            <div style="font-size:11px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Str::limit($r->questions[0] ?? 'ไม่มีคำถาม', 38) }}</div>
                        </div>
                        <div style="text-align:right; flex:none;">
                            <span class="tp-pill" style="font-size:9.5px; {{ $r->reading_type === 'deep' ? 'color:#fff; background:linear-gradient(135deg,var(--accent1),var(--accent2));' : 'color:var(--deep1); background:color-mix(in srgb, var(--accent1) 16%, transparent);' }}">{{ $r->reading_type === 'deep' ? 'Deep' : 'Basic' }}</span>
                            <div style="font-size:10px; color:var(--ink2); margin-top:4px;">{{ $r->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:24px 0; font-size:13px;">ยังไม่มีคำทำนาย</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- สถิติเพิ่มเติม --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        @php
            $foot = [
                ['Avg Tokens/Reading', number_format($stats['avg_tokens']), 'fa-microchip'],
                ['Basic Readings', number_format($stats['basic_count']), 'fa-wand-magic'],
                ['Deep Readings', number_format($stats['deep_count']), 'fa-gem'],
                ['Paid Readings', number_format($stats['paid_count']), 'fa-circle-check'],
            ];
        @endphp
        @foreach($foot as [$label, $val, $icon])
            <div class="tp-card" style="padding:14px 16px; display:flex; align-items:center; gap:11px;">
                <span style="width:34px; height:34px; flex:none; border-radius:10px; display:grid; place-items:center; background:color-mix(in srgb, var(--accent1) 14%, transparent); color:var(--deep1); font-size:14px;"><i class="fas {{ $icon }}"></i></span>
                <div>
                    <div style="font-size:11px; color:var(--ink2);">{{ $label }}</div>
                    <div class="tp-num" style="font-size:18px; font-weight:800;">{{ $val }}</div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
