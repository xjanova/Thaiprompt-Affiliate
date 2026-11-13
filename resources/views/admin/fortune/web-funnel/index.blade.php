@extends('layouts.admin-v4')

@section('title', 'ยอดเข้าห้องแม่หมอ (FB → เว็บ)')

@php
    // 📈 (2026-11-14) ตัวนับเส้นทางลูกค้า แชทเพจ FB → ห้องแม่หมอบนเว็บจันทรา
    $funnel = $report['funnel'];
    $top = max(1, (int) ($funnel[0]['people'] ?? 0));
    $hasData = $report['ready'] && ($report['totals']['boxes'] > 0 || collect($funnel)->sum('people') > 0 || $report['totals']['dead'] > 0);

    // % เทียบขั้นหลักก่อนหน้า (ขั้นย่อย "กดปุ่มเอง" เทียบกับขั้นได้กล่อง)
    $prevMain = null;
    foreach ($funnel as $i => $step) {
        $base = ! empty($step['sub']) ? ($funnel[0]['people'] ?? 0) : $prevMain;
        $funnel[$i]['pct'] = ($base !== null && $base > 0) ? round($step['people'] * 100 / $base) : null;
        if (empty($step['sub'])) {
            $prevMain = $step['people'];
        }
    }

    $query = fn (array $extra) => array_filter(array_merge(['page' => $pageId], $extra), fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ยอดเข้าห้องแม่หมอ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ยอดเข้าห้องแม่หมอ 🌙</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ลูกค้าจากแชทเพจ FB เดินไปถึงขั้นไหน · {{ \Carbon\Carbon::parse($report['from'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($report['to'])->format('d/m/Y') }}</div>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            @foreach($presets as $n => $label)
                <a href="{{ route('admin.fortune.web-funnel.index', $query(['days' => $n])) }}"
                   class="tp-btn tp-btn-sm {{ $days === $n ? 'tp-btn-primary' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- ===== ตัวกรอง: ช่วงวันที่เอง + สาขา ===== --}}
    <form method="GET" action="{{ route('admin.fortune.web-funnel.index') }}" class="tp-card"
          style="padding:14px 16px; display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px;">
        <label style="display:flex; flex-direction:column; gap:4px; font-size:11.5px; color:var(--ink2); font-weight:600; flex:1 1 140px;">
            ตั้งแต่วันที่
            <input type="date" name="from" value="{{ $days === null ? $report['from'] : '' }}" max="{{ now()->toDateString() }}" class="tp-input" style="padding:9px 12px;">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:11.5px; color:var(--ink2); font-weight:600; flex:1 1 140px;">
            ถึงวันที่
            <input type="date" name="to" value="{{ $days === null ? $report['to'] : '' }}" max="{{ now()->toDateString() }}" class="tp-input" style="padding:9px 12px;">
        </label>
        @if($pages->isNotEmpty())
            <label style="display:flex; flex-direction:column; gap:4px; font-size:11.5px; color:var(--ink2); font-weight:600; flex:1 1 180px;">
                สาขา (เพจ)
                <select name="page" class="tp-input" style="padding:9px 12px;">
                    <option value="">ทุกสาขา</option>
                    @foreach($pages as $p)
                        <option value="{{ $p->id }}" @selected($pageId === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @if($days !== null)
            <input type="hidden" name="days" value="{{ $days }}">
        @endif
        <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="min-height:40px;">
            <i class="fas fa-filter"></i> ดูยอด
        </button>
    </form>

    @if(! $report['ready'])
        <div class="tp-card" style="padding:18px; border-left:4px solid #d9534f;">
            <div style="font-weight:700;">ยังไม่มีตารางตัวนับ</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">รัน <code>php artisan migrate</code> บนเซิร์ฟเวอร์ก่อน (ตาราง fortune_web_funnel_events)</div>
        </div>
    @elseif(! $hasData)
        <div class="tp-card" style="padding:18px; border-left:4px solid #e0a52e;">
            <div style="font-weight:700;">ช่วงนี้ยังไม่มียอด</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตัวนับเริ่มเก็บ{{ $report['since'] ? 'ตั้งแต่ '.\Carbon\Carbon::parse($report['since'])->format('d/m/Y H:i') : 'ตั้งแต่วันที่ติดตั้ง' }} — ก่อนหน้านั้นไม่มีข้อมูลในหน้านี้ · ลองเลือกช่วงวันอื่นหรือทุกสาขา</div>
        </div>
    @endif

    {{-- ===== ขั้นตอน (funnel) ===== --}}
    <div class="tp-card" style="padding:18px;">
        <div style="font-weight:800; font-size:15px; margin-bottom:12px;">เดินไปถึงขั้นไหน (นับเป็นคน)</div>
        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($funnel as $step)
                @php $w = max(2, (int) round($step['people'] * 100 / $top)); @endphp
                <div style="{{ ! empty($step['sub']) ? 'padding-left:22px;' : '' }}">
                    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:baseline; gap:6px;">
                        <div style="font-weight:700; font-size:{{ ! empty($step['sub']) ? '12.5px' : '13.5px' }};">
                            {{ ! empty($step['sub']) ? '└ ' : '' }}{{ $step['label'] }}
                        </div>
                        <div style="display:flex; align-items:baseline; gap:8px;">
                            <span class="tp-num" style="font-size:{{ ! empty($step['sub']) ? '18px' : '22px' }}; font-weight:800;">{{ number_format($step['people']) }}</span>
                            <span style="font-size:11.5px; color:var(--ink2);">คน</span>
                            @if($step['pct'] !== null)
                                <span class="tp-pill tp-pill-soft">{{ $step['pct'] }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="tp-inset-sm" style="height:10px; border-radius:20px; margin-top:6px; overflow:hidden;">
                        <div style="height:100%; width:{{ $w }}%; border-radius:20px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); {{ ! empty($step['sub']) ? 'opacity:.6;' : '' }}"></div>
                    </div>
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">{{ $step['hint'] }}</div>
                </div>
            @endforeach
        </div>
        <div style="font-size:11.5px; color:var(--ink2); margin-top:14px; line-height:1.6;">
            % = เทียบกับขั้นก่อนหน้า (ขั้นย่อยเทียบกับคนที่ได้กล่อง) · แต่ละขั้นนับคนที่ทำขั้นนั้นภายในช่วงวันที่เลือก
            · ขั้นบนเว็บนับเฉพาะลูกค้าที่เคยแตะลิงก์จากแชทเพจ
        </div>
    </div>

    {{-- ===== ตัวเลขรวม ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px;">
        @foreach([
            ['กล่องลิงก์ที่ส่ง', number_format($report['totals']['boxes']), 'ครั้ง'],
            ['บิลคำทำนาย', number_format($report['totals']['bills']), 'บิล'],
            ['ยอดซื้อคำทำนาย', number_format($report['totals']['paid_amount'], 2), 'บาท'],
            ['แตะลิงก์แต่เข้าไม่ได้', number_format($report['totals']['dead']), 'ครั้ง'],
        ] as [$label, $value, $unit])
            <div class="tp-card" style="padding:14px 16px;">
                <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                <div style="display:flex; align-items:baseline; gap:6px; margin-top:4px;">
                    <span class="tp-num" style="font-size:22px; font-weight:800;">{{ $value }}</span>
                    <span style="font-size:11.5px; color:var(--ink2);">{{ $unit }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== รายวัน ===== --}}
    <div class="tp-card" style="padding:18px;">
        <div style="font-weight:800; font-size:15px; margin-bottom:10px;">รายวัน</div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:12.5px; min-width:640px;">
                <thead>
                    <tr style="color:var(--ink2); text-align:right;">
                        <th style="text-align:left; padding:8px 6px; font-weight:700;">วันที่</th>
                        <th style="padding:8px 6px; font-weight:700;">กล่อง (ครั้ง)</th>
                        <th style="padding:8px 6px; font-weight:700;">ได้กล่อง (คน)</th>
                        <th style="padding:8px 6px; font-weight:700;">กดปุ่มเอง</th>
                        <th style="padding:8px 6px; font-weight:700;">เข้าเว็บได้</th>
                        <th style="padding:8px 6px; font-weight:700;">ลิงก์ตัน</th>
                        <th style="padding:8px 6px; font-weight:700;">คุยกับแม่หมอ</th>
                        <th style="padding:8px 6px; font-weight:700;">ซื้อ (คน)</th>
                        <th style="padding:8px 6px; font-weight:700;">ยอด (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['daily'] as $d)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 18%, transparent); text-align:right;">
                            <td style="text-align:left; padding:8px 6px; font-weight:600;">{{ \Carbon\Carbon::parse($d['date'])->format('d/m') }}</td>
                            <td class="tp-num" style="padding:8px 6px;">{{ $d['boxes'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px;">{{ $d['box_people'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px;">{{ $d['pressed'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px; font-weight:700;">{{ $d['opened'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px; color:var(--ink2);">{{ $d['dead'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px;">{{ $d['chatted'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px;">{{ $d['paid'] ?: '–' }}</td>
                            <td class="tp-num" style="padding:8px 6px;">{{ $d['amount'] > 0 ? number_format($d['amount'], 2) : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:18px;">
        {{-- ===== กล่องมาจากไหน ===== --}}
        <div class="tp-card" style="padding:18px;">
            <div style="font-weight:800; font-size:15px;">กล่องลิงก์มาจากจุดไหน</div>
            <div style="font-size:11.5px; color:var(--ink2); margin:2px 0 10px;">"เข้าเว็บได้" = คนที่ได้กล่องจากจุดนี้ แล้วแตะลิงก์เข้าเว็บได้ในช่วงเดียวกัน</div>
            @if(empty($report['sources']))
                <div style="font-size:12.5px; color:var(--ink2); padding:10px 0;">ยังไม่มีข้อมูล</div>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                        <thead>
                            <tr style="color:var(--ink2); text-align:right;">
                                <th style="text-align:left; padding:6px; font-weight:700;">จุดที่ส่ง</th>
                                <th style="padding:6px; font-weight:700;">ครั้ง</th>
                                <th style="padding:6px; font-weight:700;">คน</th>
                                <th style="padding:6px; font-weight:700;">เข้าเว็บได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['sources'] as $s)
                                <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 18%, transparent); text-align:right;">
                                    <td style="text-align:left; padding:6px;" title="{{ $s['source'] }}">{{ $s['label'] }}</td>
                                    <td class="tp-num" style="padding:6px;">{{ $s['boxes'] }}</td>
                                    <td class="tp-num" style="padding:6px;">{{ $s['people'] }}</td>
                                    <td class="tp-num" style="padding:6px; font-weight:700;">
                                        {{ $s['opened'] }}
                                        @if($s['people'] > 0)
                                            <span style="font-size:11px; color:var(--ink2); font-weight:600;">({{ round($s['opened'] * 100 / $s['people']) }}%)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ===== ลิงก์ที่เข้าไม่ได้ ===== --}}
        <div class="tp-card" style="padding:18px;">
            <div style="font-weight:800; font-size:15px;">แตะลิงก์แต่เข้าไม่ได้ เพราะอะไร</div>
            <div style="font-size:11.5px; color:var(--ink2); margin:2px 0 10px;">ลูกค้าเจอหน้า "ลิงก์ใช้ไม่ได้" ซึ่งมีปุ่มขอลิงก์ใหม่เข้าแชท</div>
            @if(empty($report['dead']))
                <div style="font-size:12.5px; color:var(--ink2); padding:10px 0;">ไม่มี 🎉</div>
            @else
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($report['dead'] as $r)
                        <div style="display:flex; justify-content:space-between; gap:10px; font-size:12.5px; padding:8px 10px; border-radius:10px;" class="tp-inset-sm">
                            <span>{{ $r['label'] }}</span>
                            <span class="tp-num" style="font-weight:700;">{{ $r['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
