@extends('layouts.admin-v4')

@section('title', 'การตลาดดูดวงอัตโนมัติ')

@section('content')
{{-- ธีม V4 "นวลทองคำ" — หน้ารายการแคมเปญการตลาดอัตโนมัติ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · การตลาดอัตโนมัติ
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-bullhorn" style="color:var(--accent2);"></i>
                การตลาดดูดวงอัตโนมัติ
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13.5px;">
                ตั้งแคมเปญ AI สร้างข้อความ + ส่งหาผู้ใช้เก่าอัตโนมัติตามเวลา
            </p>
        </div>
        <div>
            @if(Route::has('admin.fortune.marketing.create'))
            <a href="{{ route('admin.fortune.marketing.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i>
                สร้างแคมเปญใหม่
            </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
        {{-- แคมเปญทั้งหมด --}}
        <div class="tp-card tp-card-hover" style="padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span class="tp-muted" style="font-size:12.5px;">แคมเปญทั้งหมด</span>
                <span class="tp-icon-btn" style="background:var(--a2soft);color:var(--accent2);">
                    <i class="fas fa-layer-group"></i>
                </span>
            </div>
            <div class="tp-num" style="font-size:28px;font-weight:800;">{{ number_format($stats['total']) }}</div>
        </div>
        {{-- กำลังทำงาน --}}
        <div class="tp-card tp-card-hover" style="padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span class="tp-muted" style="font-size:12.5px;">กำลังทำงาน</span>
                <span class="tp-icon-btn" style="background:rgba(90,160,126,.16);color:#5aa07e;">
                    <i class="fas fa-rocket"></i>
                </span>
            </div>
            <div class="tp-num" style="font-size:28px;font-weight:800;color:#5aa07e;">{{ number_format($stats['active']) }}</div>
        </div>
        {{-- ส่งเสร็จแล้ว --}}
        <div class="tp-card tp-card-hover" style="padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span class="tp-muted" style="font-size:12.5px;">ส่งเสร็จแล้ว</span>
                <span class="tp-icon-btn" style="background:rgba(183,154,232,.16);color:#b79ae8;">
                    <i class="fas fa-circle-check"></i>
                </span>
            </div>
            <div class="tp-num" style="font-size:28px;font-weight:800;color:#b79ae8;">{{ number_format($stats['completed']) }}</div>
        </div>
        {{-- ส่งทั้งหมด --}}
        <div class="tp-card tp-card-hover" style="padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span class="tp-muted" style="font-size:12.5px;">ส่งทั้งหมด</span>
                <span class="tp-icon-btn" style="background:rgba(214,130,74,.16);color:#d6824a;">
                    <i class="fas fa-paper-plane"></i>
                </span>
            </div>
            <div class="tp-num" style="font-size:28px;font-weight:800;color:#d6824a;">{{ number_format($stats['total_sent']) }}</div>
        </div>
        {{-- แบบร่าง --}}
        <div class="tp-card tp-card-hover" style="padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span class="tp-muted" style="font-size:12.5px;">แบบร่าง</span>
                <span class="tp-icon-btn" style="background:rgba(154,143,124,.16);color:#9a8f7c;">
                    <i class="fas fa-pen-ruler"></i>
                </span>
            </div>
            <div class="tp-num" style="font-size:28px;font-weight:800;color:#9a8f7c;">{{ number_format($stats['draft']) }}</div>
        </div>
    </div>

    {{-- ===== ฟิลเตอร์ค้นหา ===== --}}
    <div class="tp-card" style="padding:16px;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <div style="flex:1;min-width:220px;">
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาชื่อแคมเปญ หรือหัวข้อ..."
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>
            <div style="min-width:180px;">
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="status" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="">ทุกสถานะ</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>ตั้งเวลาแล้ว</option>
                        <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>กำลังส่ง</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>ส่งเสร็จ</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>หยุดชั่วคราว</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="tp-btn">
                <i class="fas fa-magnifying-glass"></i>
                ค้นหา
            </button>
        </form>
    </div>

    {{-- ===== ตารางแคมเปญ ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:880px;">
                <thead>
                    <tr style="background:var(--inset-sm);">
                        <th style="text-align:left;padding:14px 16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ink2);">แคมเปญ</th>
                        <th style="text-align:center;padding:14px 16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ink2);">เป้าหมาย</th>
                        <th style="text-align:center;padding:14px 16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ink2);">ตารางส่ง</th>
                        <th style="text-align:center;padding:14px 16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ink2);">สถานะ</th>
                        <th style="text-align:center;padding:14px 16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ink2);">ส่งแล้ว</th>
                        <th style="text-align:right;padding:14px 16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ink2);">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr style="border-top:1px solid var(--sd);">
                            {{-- แคมเปญ --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:14px;font-weight:700;color:var(--ink);">{{ $campaign->name }}</div>
                                @if($campaign->topic)
                                    <div style="font-size:12px;color:#b79ae8;margin-top:2px;">หัวข้อ: {{ $campaign->topic }}</div>
                                @endif
                                <div class="tp-muted" style="font-size:12px;margin-top:2px;">
                                    @if($campaign->use_ai_generate)
                                        <i class="fas fa-robot" style="color:var(--accent2);"></i> AI สร้างข้อความ
                                    @else
                                        <i class="fas fa-pen"></i> กำหนดเอง
                                    @endif
                                </div>
                            </td>

                            {{-- เป้าหมาย --}}
                            <td style="padding:14px 16px;text-align:center;">
                                <div style="font-size:14px;color:var(--ink);">{{ $campaign->target_label }}</div>
                                <div class="tp-muted" style="font-size:12px;">
                                    {{ $campaign->target_platform === 'all' ? 'ทุก Platform' : strtoupper($campaign->target_platform) }}
                                    @if($campaign->target_limit)
                                        | สูงสุด {{ $campaign->target_limit }} คน
                                    @endif
                                </div>
                            </td>

                            {{-- ตารางส่ง --}}
                            <td style="padding:14px 16px;text-align:center;">
                                @if($campaign->schedule_type === 'once')
                                    <span class="tp-pill tp-pill-soft" style="font-size:12px;">ส่งครั้งเดียว</span>
                                @elseif($campaign->schedule_type === 'daily')
                                    <span class="tp-pill" style="font-size:12px;background:rgba(86,137,184,.16);color:#5689b8;">ทุกวัน</span>
                                @elseif($campaign->schedule_type === 'weekly')
                                    <span class="tp-pill" style="font-size:12px;background:rgba(183,154,232,.16);color:#b79ae8;">ทุกสัปดาห์</span>
                                @endif
                                @if($campaign->scheduled_at)
                                    <div class="tp-muted" style="font-size:12px;margin-top:4px;">
                                        {{ $campaign->scheduled_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($campaign->next_run_at)
                                    <div style="font-size:12px;color:#5aa07e;">
                                        ถัดไป: {{ $campaign->next_run_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 16px;text-align:center;">
                                @switch($campaign->status)
                                    @case('draft')
                                        <span class="tp-pill" style="font-size:12px;background:rgba(154,143,124,.16);color:#9a8f7c;"><i class="fas fa-pen-ruler"></i> แบบร่าง</span>
                                        @break
                                    @case('scheduled')
                                        <span class="tp-pill" style="font-size:12px;background:rgba(86,137,184,.16);color:#5689b8;"><i class="fas fa-clock"></i> ตั้งเวลา</span>
                                        @break
                                    @case('sending')
                                        <span class="tp-pill" style="font-size:12px;background:rgba(224,165,46,.16);color:#e0a52e;"><i class="fas fa-rocket"></i> กำลังส่ง</span>
                                        @break
                                    @case('completed')
                                        <span class="tp-pill" style="font-size:12px;background:rgba(90,160,126,.16);color:#5aa07e;"><i class="fas fa-circle-check"></i> ส่งเสร็จ</span>
                                        @break
                                    @case('paused')
                                        <span class="tp-pill" style="font-size:12px;background:rgba(214,130,74,.16);color:#d6824a;"><i class="fas fa-pause"></i> หยุดชั่วคราว</span>
                                        @break
                                    @case('cancelled')
                                        <span class="tp-pill" style="font-size:12px;background:rgba(217,83,79,.16);color:#d9534f;"><i class="fas fa-xmark"></i> ยกเลิก</span>
                                        @break
                                @endswitch
                                @if($campaign->last_error)
                                    <div style="font-size:12px;color:#d9534f;margin-top:4px;" title="{{ $campaign->last_error }}">
                                        <i class="fas fa-triangle-exclamation"></i> error
                                    </div>
                                @endif
                            </td>

                            {{-- ส่งแล้ว --}}
                            <td style="padding:14px 16px;text-align:center;">
                                <div class="tp-num" style="font-size:18px;font-weight:800;color:var(--ink);">{{ $campaign->sent_count }}</div>
                                @if($campaign->failed_count > 0)
                                    <div style="font-size:12px;color:#d9534f;">ล้มเหลว {{ $campaign->failed_count }}</div>
                                @endif
                                @if($campaign->last_run_at)
                                    <div class="tp-muted" style="font-size:12px;">{{ $campaign->last_run_at->diffForHumans() }}</div>
                                @endif
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:14px 16px;text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:6px;flex-wrap:wrap;">
                                    {{-- แก้ไข --}}
                                    @if(Route::has('admin.fortune.marketing.edit'))
                                    <a href="{{ route('admin.fortune.marketing.edit', $campaign) }}"
                                       class="tp-icon-btn" title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @endif

                                    {{-- AI สร้างข้อความใหม่ --}}
                                    @if($campaign->use_ai_generate && Route::has('admin.fortune.marketing.generate-message'))
                                        <form action="{{ route('admin.fortune.marketing.generate-message', $campaign) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" title="AI สร้างข้อความใหม่"
                                                    class="tp-icon-btn" style="color:var(--accent2);">
                                                <i class="fas fa-robot"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- เปิดใช้งาน --}}
                                    @if(in_array($campaign->status, ['draft', 'paused']) && Route::has('admin.fortune.marketing.activate'))
                                        <form action="{{ route('admin.fortune.marketing.activate', $campaign) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" title="เปิดใช้งาน"
                                                    class="tp-icon-btn" style="color:#5aa07e;">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- ส่งทันที + หยุดชั่วคราว (เฉพาะตั้งเวลาแล้ว) --}}
                                    @if($campaign->status === 'scheduled')
                                        @if(Route::has('admin.fortune.marketing.send-now'))
                                        <form action="{{ route('admin.fortune.marketing.send-now', $campaign) }}" method="POST" style="display:inline;"
                                              onsubmit="return confirm('ยืนยันส่งทันที?')">
                                            @csrf
                                            <button type="submit" title="ส่งทันที"
                                                    class="tp-icon-btn" style="color:#5689b8;">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @if(Route::has('admin.fortune.marketing.pause'))
                                        <form action="{{ route('admin.fortune.marketing.pause', $campaign) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" title="หยุดชั่วคราว"
                                                    class="tp-icon-btn" style="color:#d6824a;">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                        @endif
                                    @endif

                                    {{-- ลบ --}}
                                    @if(Route::has('admin.fortune.marketing.destroy'))
                                    <form action="{{ route('admin.fortune.marketing.destroy', $campaign) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ยืนยันลบแคมเปญนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="ลบ"
                                                class="tp-icon-btn" style="color:#d9534f;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:56px 24px;text-align:center;">
                                <div style="font-size:42px;color:var(--ink2);opacity:.5;margin-bottom:12px;">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <p style="font-size:16px;font-weight:700;color:var(--ink);margin:0;">ยังไม่มีแคมเปญการตลาด</p>
                                <p class="tp-muted" style="font-size:13px;margin:4px 0 0;">คลิก "สร้างแคมเปญใหม่" เพื่อเริ่มต้น</p>
                                @if(Route::has('admin.fortune.marketing.create'))
                                <a href="{{ route('admin.fortune.marketing.create') }}" class="tp-btn tp-btn-primary" style="margin-top:16px;">
                                    <i class="fas fa-plus"></i> สร้างแคมเปญแรก
                                </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($campaigns->hasPages())
        <div>{{ $campaigns->links() }}</div>
    @endif

</div>
@endsection
