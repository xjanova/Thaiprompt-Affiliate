@extends('layouts.user-v4')

@section('title', 'ผู้มุ่งหวังของฉัน')

@php
    use Illuminate\Support\Str;

    // สถานะ → [ป้าย, สี] (รองรับ fortuneReferral)
    $statusInfo = function ($p) {
        if ($p->fortuneReferral) {
            return match ($p->fortuneReferral->status) {
                'pending' => ['รอกดลิงก์', '#e0a52e'],
                'followed' => ['add LINE แล้ว', '#5689b8'],
                'converted' => ['สมัครสมาชิกแล้ว', '#5aa07e'],
                'expired' => ['หมดอายุ', '#d9534f'],
                default => [$p->fortuneReferral->status, 'var(--ink2)'],
            };
        }
        return match ($p->status) {
            'pending' => ['รอดำเนินการ', '#e0a52e'],
            'in_progress' => ['กำลังดำเนินการ', '#5689b8'],
            'completed' => ['สำเร็จ', '#5aa07e'],
            'expired' => ['หมดอายุ', '#d9534f'],
            default => [$p->status, 'var(--ink2)'],
        };
    };

    $statCards = [
        ['ทั้งหมด', $stats['total'] ?? 0, 'var(--deep1)'],
        ['รอดำเนินการ', $stats['pending'] ?? 0, '#e0a52e'],
        ['กำลังดำเนินการ', $stats['in_progress'] ?? 0, '#5689b8'],
        ['สำเร็จ', $stats['completed'] ?? 0, '#5aa07e'],
        ['หมดอายุ', $stats['expired'] ?? 0, '#d9534f'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ไม่ได้เป็นสมาชิก MLM --}}
    @if(isset($notMlmMember) && $notMlmMember)
    <div class="tp-card" style="padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; background:#e0a52e;"><i class="fas fa-triangle-exclamation" style="color:#fff;"></i></span>
        <div style="flex:1; min-width:200px;">
            <div style="font-weight:700;">คุณยังไม่ได้เป็นสมาชิก MLM</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">สมัครเป็นสมาชิก MLM ก่อนเพื่อใช้ฟีเจอร์ผู้มุ่งหวังและสร้างทีม</div>
        </div>
        <a href="{{ route('user.mlm.dashboard') }}" class="tp-btn tp-btn-primary">ไปหน้า MLM Dashboard</a>
    </div>
    @endif

    {{-- หัวข้อ --}}
    <div class="tp-card" style="padding:0; overflow:hidden; position:relative;">
        {{-- ภาพประกอบหัวเรื่อง (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.hero-art image="usr-team" />
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-user-plus" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:180px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ผู้มุ่งหวังของฉัน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามและจัดการผู้ที่คุณเชิญให้สมัครผ่าน LINE</div>
            </div>
            <a href="{{ route('user.prospects.create') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> สร้างลิงก์เชิญใหม่</a>
        </div>
    </div>

    {{-- สถิติ 5 ใบ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        @foreach($statCards as [$label, $val, $color])
            <div class="tp-card" style="padding:16px;">
                <div style="font-size:12px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px; color:{{ $color }};">{{ number_format($val) }}</div>
            </div>
        @endforeach
    </div>

    {{-- อัตราการแปลง --}}
    @if(($stats['total'] ?? 0) > 0)
    <div class="tp-card" style="padding:18px; display:flex; flex-wrap:wrap; align-items:center; gap:16px;">
        <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px;">📈</span>
        <div>
            <div style="font-size:12px; color:var(--ink2);">อัตราการแปลง (Conversion Rate)</div>
            <div class="tp-num" style="font-size:28px; font-weight:800; color:#5aa07e;">{{ number_format((($stats['completed'] ?? 0) / $stats['total']) * 100, 1) }}%</div>
        </div>
        <div style="margin-left:auto; display:flex; align-items:center; gap:14px;">
            <div style="text-align:center;"><div style="font-size:11px; color:var(--ink2);">เชิญแล้ว</div><div class="tp-num" style="font-weight:700;">{{ number_format($stats['total']) }}</div></div>
            <span style="color:var(--ink2);">→</span>
            <div style="text-align:center;"><div style="font-size:11px; color:var(--ink2);">สมัครแล้ว</div><div class="tp-num" style="font-weight:700; color:#5aa07e;">{{ number_format($stats['completed'] ?? 0) }}</div></div>
        </div>
    </div>
    @endif

    {{-- ตัวกรอง --}}
    <form method="GET" action="{{ route('user.prospects.index') }}" class="tp-card" style="padding:16px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
        <div style="flex:1; min-width:180px;">
            <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:5px;">ค้นหา</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อ LINE หรือ Token…" class="tp-input">
        </div>
        <div style="min-width:130px;">
            <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:5px;">ประเภท</label>
            <select name="type" class="tp-input">
                <option value="">ทั้งหมด</option>
                <option value="fortune" {{ request('type') === 'fortune' ? 'selected' : '' }}>ดูดวง</option>
            </select>
        </div>
        <div style="min-width:150px;">
            <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:5px;">สถานะ</label>
            <select name="status" class="tp-input">
                <option value="">ทั้งหมด</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
            </select>
        </div>
        <div style="min-width:140px;">
            <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:5px;">จากวันที่</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input">
        </div>
        <div style="min-width:140px;">
            <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:5px;">ถึงวันที่</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
            <a href="{{ route('user.prospects.index') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    {{-- รายการ --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($prospects->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            @foreach(['วันที่สร้าง','ข้อมูล LINE','สถานะ','ผู้ใช้ที่สมัครแล้ว','จัดการ'] as $h)
                                <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prospects as $prospect)
                            @php [$stLabel, $stColor] = $statusInfo($prospect); @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap;">
                                    <div>{{ $prospect->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $prospect->created_at->format('H:i น.') }}</div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        @if($prospect->line_profile_picture)
                                            <img src="{{ $prospect->line_profile_picture }}" alt="" style="width:38px; height:38px; border-radius:50%; object-fit:cover; flex:none;">
                                        @else
                                            <span class="tp-tile" style="width:38px; height:38px; border-radius:50%; font-size:14px; background:color-mix(in srgb, var(--ink2) 16%, transparent);"><i class="fab fa-line" style="color:#06c755;"></i></span>
                                        @endif
                                        <div style="min-width:0;">
                                            <div style="font-size:12.5px; font-weight:600; display:flex; align-items:center; gap:6px;">
                                                {{ $prospect->line_display_name ?? 'รอการคลิกลิงก์' }}
                                                @if($prospect->fortuneReferral)<span class="tp-pill tp-pill-soft" style="font-size:9px;">ดูดวง</span>@endif
                                            </div>
                                            <div class="tp-num" style="font-size:10.5px; color:var(--ink2);">{{ $prospect->line_user_id ? Str::limit($prospect->line_user_id, 20) : 'ยังไม่ได้เริ่มสมัคร' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <span class="tp-pill" style="color:#fff; background:{{ $stColor }};">{{ $stLabel }}</span>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    @if($prospect->registeredUser)
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span class="tp-tile" style="width:30px; height:30px; border-radius:50%; font-size:13px; background:#5aa07e; color:#fff;">{{ mb_substr($prospect->registeredUser->name, 0, 1) }}</span>
                                            <span style="font-size:12.5px; font-weight:600;">{{ $prospect->registeredUser->name }}</span>
                                        </div>
                                    @else
                                        <span style="font-size:12px; color:var(--ink2); font-style:italic;">ยังไม่สมัคร</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div style="display:flex; gap:7px;">
                                        <a href="{{ route('user.prospects.show', $prospect->id) }}" class="tp-btn tp-btn-sm">ดู</a>
                                        @if(in_array($prospect->status, ['pending', 'expired']))
                                            <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" onsubmit="return confirm('ลบลิงก์เชิญนี้?')" style="display:inline;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;">ลบ</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($prospects->hasPages())
                <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $prospects->links() }}</div>
            @endif
        @else
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">👥</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีผู้มุ่งหวัง</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เริ่มสร้างเครือข่ายโดยสร้างลิงก์เชิญ แล้วแชร์ให้เพื่อน!</div>
                <a href="{{ route('user.prospects.create') }}" class="tp-btn tp-btn-primary" style="margin-top:16px;"><i class="fas fa-plus"></i> สร้างลิงก์เชิญคนแรก</a>
            </div>
        @endif
    </div>

    {{-- เคล็ดลับ --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💡 เคล็ดลับเพิ่ม Conversion Rate</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
            @php
                $tips = [
                    ['1', 'ส่งลิงก์พร้อมข้อความส่วนตัว', 'เพิ่มโอกาสในการตอบรับถึง 40%'],
                    ['2', 'ติดตามผู้มุ่งหวังที่ยังไม่ลงทะเบียน', 'ส่งข้อความเตือนภายใน 24-48 ชม.'],
                    ['3', 'ต่ออายุลิงก์ที่หมดอายุ', 'อย่าปล่อยให้ผู้มุ่งหวังหลุดไป'],
                ];
            @endphp
            @foreach($tips as [$n, $head, $desc])
                <div style="display:flex; gap:11px; align-items:flex-start;">
                    <span class="tp-tile" style="width:30px; height:30px; border-radius:9px; font-size:13px; font-weight:800;">{{ $n }}</span>
                    <div>
                        <div style="font-weight:700; font-size:13px;">{{ $head }}</div>
                        <div style="font-size:12px; color:var(--ink2);">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
