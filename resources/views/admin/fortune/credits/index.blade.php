@extends('layouts.admin-v4')

@section('title', 'จัดการเครดิตดูดวงฟรีรายคน')

@section('content')
{{-- container หลัก: ผูก Alpine ที่ root เพื่อให้ showAddForm ใช้ได้ทั้งหน้า --}}
<div x-data="creditManager()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ข้อความ validation error → ส่งเข้า toast ของ layout (layout จับเฉพาะ session ไม่จับ $errors) --}}
    @if($errors->any())
        <div x-data x-init="$dispatch('notify', { type: 'error', message: @js($errors->first()) })"></div>
    @endif

    {{-- หัวข้อหน้า --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · เครดิตฟรีรายคน</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">เครดิตดูดวงฟรีรายคน 🎁</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เพิ่มเครดิต / รีเซ็ตสิทธิ์ฟรี / ให้ดูฟรีไม่จำกัดเป็นรายคน</div>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- ปุ่มเปิดฟอร์มเพิ่มเครดิต (toggle Alpine) --}}
            <button type="button" @click="showAddForm = !showAddForm" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i> เพิ่มเครดิตผู้ใช้
            </button>
        </div>
    </div>

    {{-- KPI สถิติ --}}
    @php
        $kpis = [
            ['ผู้ใช้ทั้งหมด', 'Total users', number_format($stats['total_users']), 'fa-users', 'var(--deep1)'],
            ['ดูฟรีไม่จำกัด', 'Unlimited', number_format($stats['unlimited_users']), 'fa-star', '#b79ae8'],
            ['มีเครดิตเหลือ', 'With credits', number_format($stats['users_with_credits']), 'fa-ticket', '#5aa07e'],
            ['เครดิตแจกทั้งหมด', 'Credits given', number_format($stats['total_credits_given']), 'fa-gift', '#d6824a'],
            ['เครดิตใช้ไปแล้ว', 'Credits used', number_format($stats['total_credits_used']), 'fa-chart-simple', '#5689b8'],
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

    {{-- ฟอร์มเพิ่มเครดิต (แสดงเมื่อกดปุ่ม) --}}
    <div x-show="showAddForm" x-cloak x-transition class="tp-card" style="padding:22px;">
        <div style="display:flex; align-items:center; gap:11px; margin-bottom:16px;">
            <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px;"><i class="fas fa-gift"></i></span>
            <div>
                <div class="tp-num" style="font-size:16px; font-weight:800;">เพิ่มเครดิตให้ผู้ใช้</div>
                <div style="font-size:12px; color:var(--ink2);">ระบุ Facebook User ID และจำนวนเครดิตที่ต้องการแจก</div>
            </div>
        </div>

        {{-- คงฟอร์มเดิม 100%: action/method/@csrf/ชื่อ field --}}
        <form action="{{ route('admin.fortune.credits.add-credits') }}" method="POST">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Facebook User ID <span style="color:#d9534f;">*</span></label>
                    <div class="tp-well">
                        <input type="text" name="facebook_user_id" required placeholder="เช่น 1234567890" class="tp-input">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชื่อผู้ใช้ (ถ้ามี)</label>
                    <div class="tp-well">
                        <input type="text" name="facebook_user_name" placeholder="ชื่อ Facebook" class="tp-input">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">จำนวนเครดิต <span style="color:#d9534f;">*</span></label>
                    <div class="tp-well">
                        <input type="number" name="amount" required min="1" max="999" value="5" class="tp-input">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมายเหตุ</label>
                    <div class="tp-well">
                        <input type="text" name="note" placeholder="เหตุผลที่เพิ่ม" class="tp-input">
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-check"></i> เพิ่มเครดิต</button>
                <button type="button" @click="showAddForm = false" class="tp-btn"><i class="fas fa-xmark"></i> ยกเลิก</button>
            </div>
        </form>
    </div>

    {{-- ค้นหา / กรอง (GET form — คงชื่อ field เดิม) --}}
    <div class="tp-card" style="padding:16px;">
        <form method="GET" action="{{ route('admin.fortune.credits.index') }}" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
            <div class="tp-well" style="flex:1; min-width:220px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา Facebook User ID หรือชื่อ..." class="tp-input">
            </div>
            <label style="display:flex; align-items:center; gap:7px; font-size:12.5px; color:var(--ink2); font-weight:600; cursor:pointer;">
                <input type="checkbox" name="has_credits" value="1" {{ request('has_credits') ? 'checked' : '' }} style="width:16px; height:16px; accent-color:var(--accent1);">
                มีเครดิตเหลือ
            </label>
            <label style="display:flex; align-items:center; gap:7px; font-size:12.5px; color:var(--ink2); font-weight:600; cursor:pointer;">
                <input type="checkbox" name="unlimited" value="1" {{ request('unlimited') ? 'checked' : '' }} style="width:16px; height:16px; accent-color:#b79ae8;">
                ไม่จำกัด
            </label>
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
            @if(request()->hasAny(['search', 'has_credits', 'unlimited']))
                <a href="{{ route('admin.fortune.credits.index') }}" class="tp-btn"><i class="fas fa-eraser"></i> ล้าง</a>
            @endif
        </form>
    </div>

    {{-- ตารางเครดิตรายคน --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:760px;">
                <thead>
                    <tr style="box-shadow:var(--inset-sm);">
                        <th style="padding:13px 16px; text-align:left; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase;">ผู้ใช้</th>
                        <th style="padding:13px 16px; text-align:center; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase;">เครดิต</th>
                        <th style="padding:13px 16px; text-align:center; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase;">สถานะพิเศษ</th>
                        <th style="padding:13px 16px; text-align:left; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase;">หมายเหตุ</th>
                        <th style="padding:13px 16px; text-align:left; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase;">อัปเดตล่าสุด</th>
                        <th style="padding:13px 16px; text-align:right; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($credits as $credit)
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- ผู้ใช้ --}}
                            <td style="padding:13px 16px;">
                                <div class="tp-num" style="font-size:13.5px; font-weight:700;">{{ $credit->facebook_user_name ?? '-' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2); font-family:monospace;">{{ $credit->facebook_user_id }}</div>
                                <div style="font-size:10.5px; color:var(--ink2); opacity:.75;">{{ $credit->platform }}</div>
                            </td>

                            {{-- เครดิต --}}
                            <td style="padding:13px 16px; text-align:center;">
                                <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1);">{{ $credit->getRemainingCredits() }}</div>
                                <div style="font-size:11px; color:var(--ink2);">ใช้ {{ $credit->credits_used }} / {{ $credit->bonus_credits }}</div>
                            </td>

                            {{-- สถานะพิเศษ --}}
                            <td style="padding:13px 16px; text-align:center;">
                                @if($credit->isCurrentlyUnlimited())
                                    <span class="tp-pill" style="color:#fff; background:#b79ae8;"><i class="fas fa-star"></i> ไม่จำกัด</span>
                                    @if($credit->unlimited_until)
                                        <div style="font-size:10.5px; color:var(--ink2); margin-top:5px;">ถึง {{ $credit->unlimited_until->format('d/m/Y') }}</div>
                                    @endif
                                @elseif($credit->isDailyResetActive())
                                    <span class="tp-pill" style="color:#fff; background:#5aa07e;"><i class="fas fa-rotate"></i> รีเซ็ตวันนี้</span>
                                @elseif($credit->getRemainingCredits() > 0)
                                    <span class="tp-pill tp-pill-soft"><i class="fas fa-ticket"></i> มีเครดิต</span>
                                @else
                                    <span style="font-size:12px; color:var(--ink2); opacity:.6;">-</span>
                                @endif
                            </td>

                            {{-- หมายเหตุ --}}
                            <td style="padding:13px 16px;">
                                <div style="font-size:12.5px; color:var(--ink2); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $credit->note ?? '-' }}</div>
                            </td>

                            {{-- อัปเดตล่าสุด --}}
                            <td style="padding:13px 16px;">
                                <div style="font-size:12.5px; color:var(--ink2);">{{ $credit->updated_at?->diffForHumans() ?? '-' }}</div>
                            </td>

                            {{-- จัดการ (เมนู dropdown ต่อแถว — มี x-data ของตัวเอง) --}}
                            <td style="padding:13px 16px; text-align:right;">
                                <div style="position:relative; display:inline-block;" x-data="{ openMenu: false }">
                                    <button type="button" @click="openMenu = !openMenu" class="tp-icon-btn" title="จัดการ">
                                        <i class="fas fa-ellipsis"></i>
                                    </button>
                                    <div x-show="openMenu" @click.away="openMenu = false" x-cloak x-transition
                                         class="tp-card"
                                         style="position:absolute; right:0; top:48px; width:210px; padding:6px; z-index:50; text-align:left;">

                                        {{-- รีเซ็ตสิทธิ์ฟรีวันนี้ --}}
                                        <form action="{{ route('admin.fortune.credits.reset-daily', $credit) }}" method="POST">
                                            @csrf
                                            <button type="submit" style="width:100%; text-align:left; padding:9px 12px; border:none; background:transparent; color:#5aa07e; font-size:12.5px; font-weight:600; border-radius:9px; cursor:pointer;"
                                                    onmouseover="this.style.boxShadow='var(--inset-sm)'" onmouseout="this.style.boxShadow='none'">
                                                <i class="fas fa-rotate" style="width:16px;"></i> รีเซ็ตสิทธิ์ฟรีวันนี้
                                            </button>
                                        </form>

                                        {{-- ตั้งค่าดูฟรีไม่จำกัด (hidden is_unlimited เดิม) --}}
                                        @if($credit->is_unlimited)
                                            <form action="{{ route('admin.fortune.credits.set-unlimited', $credit) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="is_unlimited" value="0">
                                                <button type="submit" style="width:100%; text-align:left; padding:9px 12px; border:none; background:transparent; color:#d6824a; font-size:12.5px; font-weight:600; border-radius:9px; cursor:pointer;"
                                                        onmouseover="this.style.boxShadow='var(--inset-sm)'" onmouseout="this.style.boxShadow='none'">
                                                    <i class="fas fa-stop" style="width:16px;"></i> ปิดดูฟรีไม่จำกัด
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.fortune.credits.set-unlimited', $credit) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="is_unlimited" value="1">
                                                <button type="submit" style="width:100%; text-align:left; padding:9px 12px; border:none; background:transparent; color:#b79ae8; font-size:12.5px; font-weight:600; border-radius:9px; cursor:pointer;"
                                                        onmouseover="this.style.boxShadow='var(--inset-sm)'" onmouseout="this.style.boxShadow='none'">
                                                    <i class="fas fa-star" style="width:16px;"></i> เปิดดูฟรีไม่จำกัด
                                                </button>
                                            </form>
                                        @endif

                                        <div class="tp-divider" style="margin:5px 0;"></div>

                                        {{-- รีเซ็ตเครดิตทั้งหมด --}}
                                        <form action="{{ route('admin.fortune.credits.reset-all', $credit) }}" method="POST"
                                              onsubmit="return confirm('ยืนยันรีเซ็ตเครดิตทั้งหมด?')">
                                            @csrf
                                            <button type="submit" style="width:100%; text-align:left; padding:9px 12px; border:none; background:transparent; color:#e0a52e; font-size:12.5px; font-weight:600; border-radius:9px; cursor:pointer;"
                                                    onmouseover="this.style.boxShadow='var(--inset-sm)'" onmouseout="this.style.boxShadow='none'">
                                                <i class="fas fa-arrows-rotate" style="width:16px;"></i> รีเซ็ตเครดิตทั้งหมด
                                            </button>
                                        </form>

                                        {{-- ลบ (DELETE) --}}
                                        <form action="{{ route('admin.fortune.credits.destroy', $credit) }}" method="POST"
                                              onsubmit="return confirm('ยืนยันลบข้อมูลเครดิตของผู้ใช้นี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="width:100%; text-align:left; padding:9px 12px; border:none; background:transparent; color:#d9534f; font-size:12.5px; font-weight:600; border-radius:9px; cursor:pointer;"
                                                    onmouseover="this.style.boxShadow='var(--inset-sm)'" onmouseout="this.style.boxShadow='none'">
                                                <i class="fas fa-trash" style="width:16px;"></i> ลบ
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:40px 0; text-align:center; color:var(--ink2);">
                                <i class="fas fa-gift" style="font-size:32px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                <div class="tp-num" style="font-size:15px; font-weight:700;">ยังไม่มีข้อมูลเครดิตพิเศษ</div>
                                <div style="font-size:12.5px; margin-top:3px;">คลิก "เพิ่มเครดิตผู้ใช้" เพื่อเริ่มต้น</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- แบ่งหน้า --}}
    @if($credits->hasPages())
        <div>
            {{ $credits->withQueryString()->links() }}
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Alpine component: คุมการเปิด/ปิดฟอร์มเพิ่มเครดิต (logic เดิม)
    function creditManager() {
        return {
            showAddForm: false,
        };
    }
</script>
@endpush
