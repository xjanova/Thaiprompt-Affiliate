{{-- resources/views/admin/fortune/horoscope/index.blade.php --}}
{{-- รายการแคมเปญโพสดวงรายวันอัตโนมัติ — ธีม V4 นวลทองคำ --}}

@extends('layouts.admin-v4')

@section('title', 'ดวงรายวันอัตโนมัติ')

@php
    use Illuminate\Support\Str;

    // สีสถานะแคมเปญ (semantic palette V4)
    $statusMeta = [
        'draft'     => ['color' => '#9a8f7c', 'icon' => '📝', 'label' => 'แบบร่าง'],
        'active'    => ['color' => '#5aa07e', 'icon' => '✅', 'label' => 'กำลังทำงาน'],
        'paused'    => ['color' => '#e0a52e', 'icon' => '⏸', 'label' => 'หยุดชั่วคราว'],
        'cancelled' => ['color' => '#d9534f', 'icon' => '❌', 'label' => 'ยกเลิก'],
    ];
    $curStatus = request('status', '');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- หัวข้อ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ดวงรายวันอัตโนมัติ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ดวงรายวันอัตโนมัติ 🔮</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">AI สร้างดวงรายวัน + โพสอัตโนมัติลง Facebook / LINE</p>
        </div>
        <a href="{{ route('admin.fortune.horoscope.create') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-plus"></i> สร้างแคมเปญใหม่
        </a>
    </div>

    {{-- KPI --}}
    @php
        $kpis = [
            ['แคมเปญทั้งหมด', 'Total', number_format($stats['total']), 'fa-list-check', 'var(--deep1)'],
            ['กำลังทำงาน', 'Active', number_format($stats['active']), 'fa-circle-check', '#5aa07e'],
            ['เนื้อหาวันนี้', 'Contents today', number_format($stats['contents_today']), 'fa-robot', '#b79ae8'],
            ['โพสวันนี้', 'Posts today', number_format($stats['posts_today']), 'fa-paper-plane', '#d6824a'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @foreach($kpis as [$label, $en, $val, $icon, $col])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between;">
                    <div>
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $en }}</div>
                    </div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:linear-gradient(135deg, {{ $col }}, color-mix(in srgb, {{ $col }} 60%, #fff));">
                        <i class="fas {{ $icon }}"></i>
                    </span>
                </div>
                <div class="tp-num" style="font-size:26px; font-weight:800; margin:10px 0 2px; color:{{ $col }};">{{ $val }}</div>
            </div>
        @endforeach
    </div>

    {{-- ค้นหา / กรอง (GET form — คงชื่อ field เดิม) --}}
    <div class="tp-card" style="padding:16px;">
        <form method="GET" action="{{ route('admin.fortune.horoscope.index') }}" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
            <div class="tp-well" style="flex:1; min-width:220px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาแคมเปญ..." class="tp-input">
            </div>
            <div class="tp-well tp-input" style="padding:0; min-width:180px;">
                <select name="status" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                    <option value="">— สถานะทั้งหมด —</option>
                    <option value="draft" @selected($curStatus === 'draft')>📝 แบบร่าง</option>
                    <option value="active" @selected($curStatus === 'active')>✅ กำลังทำงาน</option>
                    <option value="paused" @selected($curStatus === 'paused')>⏸ หยุดชั่วคราว</option>
                    <option value="cancelled" @selected($curStatus === 'cancelled')>❌ ยกเลิก</option>
                </select>
            </div>
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.fortune.horoscope.index') }}" class="tp-btn"><i class="fas fa-eraser"></i> ล้าง</a>
            @endif
        </form>
    </div>

    {{-- ตารางแคมเปญ --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:900px;">
                <thead>
                    <tr style="box-shadow:var(--inset-sm);">
                        @foreach(['แคมเปญ' => 'left', 'แพลตฟอร์ม' => 'center', 'เวลาโพส' => 'center', 'สถานะ' => 'center', 'เนื้อหา/โพส' => 'center', 'โพสล่าสุด' => 'center', 'จัดการ' => 'right'] as $th => $align)
                            <th style="padding:13px 16px; text-align:{{ $align }}; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        @php $sm = $statusMeta[$campaign->status] ?? ['color' => '#9a8f7c', 'icon' => '•', 'label' => $campaign->status_label]; @endphp
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- ชื่อแคมเปญ --}}
                            <td style="padding:14px 16px; vertical-align:top;">
                                <div class="tp-num" style="font-size:13.5px; font-weight:700;">{{ $campaign->name }}</div>
                                @if($campaign->description)
                                    <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">{{ Str::limit($campaign->description, 60) }}</div>
                                @endif
                                @if($campaign->last_error)
                                    <div style="font-size:11px; color:#d9534f; margin-top:3px;">⚠️ {{ Str::limit($campaign->last_error, 40) }}</div>
                                @endif
                            </td>

                            {{-- แพลตฟอร์ม --}}
                            <td style="padding:14px 16px; text-align:center;">
                                <div style="display:flex; justify-content:center; gap:5px; flex-wrap:wrap;">
                                    @if($campaign->post_to_facebook)
                                        <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8;">📘 FB</span>
                                    @endif
                                    @if($campaign->post_to_line)
                                        <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">💚 LINE</span>
                                    @endif
                                    @if(!$campaign->post_to_facebook && !$campaign->post_to_line)
                                        <span style="font-size:11px; color:var(--ink2); opacity:.7;">ยังไม่ตั้ง</span>
                                    @endif
                                </div>
                            </td>

                            {{-- เวลาโพส --}}
                            <td style="padding:14px 16px; text-align:center;">
                                <span class="tp-num" style="font-size:13.5px; font-weight:600;">{{ $campaign->schedule_time ?? '06:00' }}</span>
                                <div style="font-size:10.5px; color:var(--ink2);">{{ $campaign->timezone ?? 'Asia/Bangkok' }}</div>
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; text-align:center;">
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $sm['color'] }} 16%, transparent); color:{{ $sm['color'] }}; font-weight:700;">{{ $sm['icon'] }} {{ $campaign->status_label }}</span>
                            </td>

                            {{-- เนื้อหา / โพส --}}
                            <td style="padding:14px 16px; text-align:center;">
                                <span class="tp-num" style="font-size:13.5px; font-weight:600;">{{ number_format($campaign->contents_count) }} / {{ number_format($campaign->posts_count) }}</span>
                            </td>

                            {{-- โพสล่าสุด --}}
                            <td style="padding:14px 16px; text-align:center;">
                                @if($campaign->last_posted_at)
                                    <span class="tp-num" style="font-size:13px; font-weight:600;">{{ $campaign->last_posted_at->format('d/m/Y') }}</span>
                                    <div style="font-size:10.5px; color:var(--ink2);">{{ $campaign->last_posted_at->format('H:i') }}</div>
                                @else
                                    <span style="font-size:12px; color:var(--ink2); opacity:.7;">ยังไม่เคยโพส</span>
                                @endif
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right;">
                                <div style="display:flex; justify-content:flex-end; gap:5px; flex-wrap:wrap;">
                                    <a href="{{ route('admin.fortune.horoscope.edit', $campaign) }}" class="tp-icon-btn tp-btn-sm" title="แก้ไข" style="width:32px; height:32px;"><i class="fas fa-pen" style="color:#5689b8;"></i></a>
                                    <a href="{{ route('admin.fortune.horoscope.content-history', $campaign) }}" class="tp-icon-btn tp-btn-sm" title="ดูเนื้อหา" style="width:32px; height:32px;"><i class="fas fa-robot" style="color:#b79ae8;"></i></a>
                                    <a href="{{ route('admin.fortune.horoscope.post-history', $campaign) }}" class="tp-icon-btn tp-btn-sm" title="ประวัติโพส" style="width:32px; height:32px;"><i class="fas fa-paper-plane" style="color:#5689b8;"></i></a>

                                    @if($campaign->status === 'active')
                                        <form action="{{ route('admin.fortune.horoscope.pause', $campaign) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tp-icon-btn tp-btn-sm" title="หยุดชั่วคราว" style="width:32px; height:32px;"><i class="fas fa-pause" style="color:#e0a52e;"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.fortune.horoscope.activate', $campaign) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tp-icon-btn tp-btn-sm" title="เปิดใช้งาน" style="width:32px; height:32px;"><i class="fas fa-play" style="color:#5aa07e;"></i></button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.fortune.horoscope.generate-now', $campaign) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ต้องการสร้างเนื้อหา AI ทันทีหรือไม่? อาจใช้เวลา 2-5 นาที')">
                                        @csrf
                                        <button type="submit" class="tp-icon-btn tp-btn-sm" title="สร้างเนื้อหา AI ทันที" style="width:32px; height:32px;"><i class="fas fa-bolt" style="color:#d6824a;"></i></button>
                                    </form>

                                    <form action="{{ route('admin.fortune.horoscope.publish-now', $campaign) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ต้องการโพสเนื้อหาทันทีหรือไม่?')">
                                        @csrf
                                        <button type="submit" class="tp-icon-btn tp-btn-sm" title="โพสทันที" style="width:32px; height:32px;"><i class="fas fa-rocket" style="color:#d6824a;"></i></button>
                                    </form>

                                    <form action="{{ route('admin.fortune.horoscope.destroy', $campaign) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ยืนยันลบแคมเปญ {{ $campaign->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn tp-btn-sm" title="ลบ" style="width:32px; height:32px;"><i class="fas fa-trash" style="color:#d9534f;"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 20px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                <div class="tp-num" style="font-size:15px; font-weight:700;">ยังไม่มีแคมเปญ</div>
                                <div style="font-size:12.5px; margin:3px 0 14px;">สร้างแคมเปญใหม่เพื่อเริ่มโพสดวงรายวันอัตโนมัติ</div>
                                <a href="{{ route('admin.fortune.horoscope.create') }}" class="tp-btn tp-btn-primary" style="display:inline-flex;"><i class="fas fa-plus"></i> สร้างแคมเปญแรก</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- แบ่งหน้า --}}
    @if($campaigns->hasPages())
        <div class="tp-num" style="display:flex; justify-content:center;">{{ $campaigns->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
