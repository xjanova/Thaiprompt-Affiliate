@extends('layouts.admin-v4')

@section('title', 'จัดการบิลลอย')

@section('content')
{{-- ══════════════════════════════════════════════════════════════
     หน้าจัดการบิลลอย — ธีม V4 "นวลทองคำ"
     บิลที่รับเงินแล้วแต่ระบบยังจับคู่กับผู้ใช้ไม่ได้ → แอดมิน assign เอง
     ตัวแปรจาก controller: $stats, $floatingBills (paginate), $users
     ══════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ───── Header ───── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · จัดการบิลลอย
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);margin:0;">
                จัดการบิลลอย
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:6px;">
                บิลที่รับเงินแล้วแต่ยังไม่สามารถระบุตัวตนผู้ใช้ได้
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.billing.index'))
                <a href="{{ route('admin.fortune.billing.index') }}" class="tp-btn">
                    <i class="fas fa-arrow-left"></i> กลับหน้าจัดการบิล
                </a>
            @endif
        </div>
    </div>

    {{-- ───── KPI Grid ───── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">

        {{-- บิลลอยทั้งหมด --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">บิลลอยทั้งหมด</span>
                    <span class="tp-pill tp-pill-soft" style="color:#d6824a;">
                        <i class="fas fa-layer-group"></i>
                    </span>
                </div>
                <div class="tp-num" style="font-size:28px;font-weight:800;color:var(--ink);">
                    {{ number_format($stats['total_count']) }}
                </div>
                <div class="tp-muted" style="font-size:12px;">รายการ</div>
            </div>
        </div>

        {{-- ยอดเงินรวม --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">ยอดเงินรวม</span>
                    <span class="tp-pill tp-pill-soft" style="color:#d9534f;">
                        <i class="fas fa-coins"></i>
                    </span>
                </div>
                <div class="tp-num" style="font-size:28px;font-weight:800;color:var(--ink);">
                    ฿{{ number_format($stats['total_amount'], 2) }}
                </div>
                <div class="tp-muted" style="font-size:12px;">รอจับคู่</div>
            </div>
        </div>

        {{-- วันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">วันนี้</span>
                    <span class="tp-pill tp-pill-soft" style="color:#e0a52e;">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                </div>
                <div class="tp-num" style="font-size:28px;font-weight:800;color:var(--ink);">
                    {{ number_format($stats['today_count']) }}
                </div>
                <div class="tp-muted" style="font-size:12px;">รายการใหม่วันนี้</div>
            </div>
        </div>

        {{-- ยอดวันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">ยอดวันนี้</span>
                    <span class="tp-pill tp-pill-gold">
                        <i class="fas fa-sack-dollar"></i>
                    </span>
                </div>
                <div class="tp-num" style="font-size:28px;font-weight:800;color:var(--ink);">
                    ฿{{ number_format($stats['today_amount'], 2) }}
                </div>
                <div class="tp-muted" style="font-size:12px;">เงินเข้าวันนี้</div>
            </div>
        </div>
    </div>

    {{-- ───── Info: บิลลอยคืออะไร ───── --}}
    <div class="tp-card">
        <div class="tp-tile" style="display:flex;gap:14px;align-items:flex-start;">
            <span class="tp-pill tp-pill-soft" style="color:#d6824a;flex:0 0 auto;width:42px;height:42px;display:flex;align-items:center;justify-content:center;font-size:18px;border-radius:12px;">
                <i class="fas fa-circle-question"></i>
            </span>
            <div>
                <div style="font-weight:700;color:var(--ink);margin-bottom:4px;">บิลลอยคืออะไร?</div>
                <div class="tp-muted" style="font-size:13px;line-height:1.7;">
                    บิลลอยเกิดจากการที่ระบบได้รับแจ้งเตือนการโอนเงิน (SMS) แต่ไม่สามารถจับคู่กับผู้ใช้ที่รอชำระเงินได้
                    อาจเกิดจาก: ผู้ใช้โอนเงินผิดจำนวน, โอนก่อนสร้างคำสั่งซื้อ, หรือ SMS มาช้ากว่าที่ผู้ใช้รอ
                </div>
            </div>
        </div>
    </div>

    {{-- ───── Search ───── --}}
    <div class="tp-card">
        <div class="tp-tile">
            <div class="tp-section-h" style="margin-bottom:12px;">
                <i class="fas fa-magnifying-glass"></i> ค้นหาบิลลอย
            </div>
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <div class="tp-well tp-input" style="flex:1;min-width:240px;padding:0;display:flex;align-items:center;">
                    <i class="fas fa-magnifying-glass tp-muted" style="margin-left:14px;font-size:13px;"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาจากชื่อผู้โอน, ธนาคาร, หรือชื่อ Facebook..."
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
                @if(request('search') && Route::has('admin.fortune.billing.floating-bills'))
                    <a href="{{ route('admin.fortune.billing.floating-bills') }}" class="tp-btn">
                        <i class="fas fa-xmark"></i> ล้าง
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- ───── ตารางบิลลอย ───── --}}
    <div class="tp-card">
        <div class="tp-tile" style="padding-bottom:6px;">
            <div class="tp-section-h" style="margin-bottom:12px;">
                <i class="fas fa-receipt"></i> รายการบิลลอย
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0 10px;min-width:920px;">
                    <thead>
                        <tr style="text-align:left;">
                            <th style="padding:6px 14px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.05em;">วันที่ชำระ</th>
                            <th style="padding:6px 14px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.05em;">ข้อมูลผู้โอน</th>
                            <th style="padding:6px 14px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.05em;">จำนวนเงิน</th>
                            <th style="padding:6px 14px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.05em;">ธนาคาร</th>
                            <th style="padding:6px 14px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.05em;">ข้อมูล Facebook</th>
                            <th style="padding:6px 14px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.05em;text-align:right;">Assign ให้ผู้ใช้</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($floatingBills as $bill)
                            {{-- แต่ละแถวมี x-data เอง เพื่อให้ Alpine init subtree --}}
                            <tr x-data="{}" style="box-shadow:var(--inset-sm);border-radius:14px;">
                                {{-- วันที่ชำระ --}}
                                <td style="padding:14px;border-radius:14px 0 0 14px;">
                                    <div class="tp-num" style="font-size:14px;color:var(--ink);font-weight:600;">
                                        {{ $bill->paid_at?->format('d/m/Y') ?? '-' }}
                                    </div>
                                    <div class="tp-muted tp-num" style="font-size:12px;">
                                        {{ $bill->paid_at?->format('H:i:s') ?? '' }}
                                    </div>
                                </td>

                                {{-- ข้อมูลผู้โอน --}}
                                <td style="padding:14px;">
                                    <div style="font-weight:600;color:var(--ink);font-size:14px;">
                                        {{ $bill->sender_info ?? 'ไม่ระบุ' }}
                                    </div>
                                    @if($bill->smsNotification)
                                        <div class="tp-muted tp-num" style="font-size:11px;margin-top:2px;">
                                            SMS ID: {{ $bill->sms_notification_id }}
                                        </div>
                                    @endif
                                </td>

                                {{-- จำนวนเงิน --}}
                                <td style="padding:14px;">
                                    <span class="tp-pill tp-num" style="color:#5aa07e;font-weight:700;font-size:14px;">
                                        ฿{{ number_format($bill->amount_paid, 2) }}
                                    </span>
                                </td>

                                {{-- ธนาคาร --}}
                                <td style="padding:14px;">
                                    <span class="tp-muted" style="font-size:13px;">
                                        {{ $bill->sender_bank ?? '-' }}
                                    </span>
                                </td>

                                {{-- ข้อมูล Facebook --}}
                                <td style="padding:14px;">
                                    @if($bill->facebook_user_name)
                                        <div style="font-weight:600;color:var(--ink);font-size:14px;">
                                            {{ $bill->facebook_user_name }}
                                        </div>
                                        <div class="tp-muted tp-num" style="font-size:11px;">
                                            {{ $bill->facebook_user_id }}
                                        </div>
                                    @else
                                        <span class="tp-muted" style="font-size:13px;font-style:italic;">ไม่มีข้อมูล</span>
                                    @endif
                                </td>

                                {{-- Assign ให้ผู้ใช้ (คงฟอร์มเดิม: action/method/csrf/field user_id) --}}
                                <td style="padding:14px;border-radius:0 14px 14px 0;text-align:right;">
                                    <form action="{{ route('admin.fortune.billing.assign', $bill) }}" method="POST"
                                          style="display:flex;align-items:center;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                                        @csrf
                                        <div class="tp-well tp-input" style="padding:0;min-width:200px;">
                                            <select name="user_id" required
                                                    style="width:100%;background:transparent;border:0;outline:0;padding:9px 12px;color:var(--ink);font-size:13px;cursor:pointer;">
                                                <option value="">เลือกผู้ใช้...</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm">
                                            <i class="fas fa-user-check"></i> Assign
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:48px 14px;text-align:center;">
                                    <div style="font-size:40px;color:var(--accent1);margin-bottom:12px;">
                                        <i class="fas fa-inbox"></i>
                                    </div>
                                    <div class="tp-muted" style="font-size:14px;">
                                        ไม่มีบิลลอย — ระบบจับคู่การชำระเงินได้ครบทุกรายการ
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ───── Pagination ───── --}}
    @if($floatingBills->hasPages())
        <div class="tp-card">
            <div class="tp-tile">
                {{ $floatingBills->appends(request()->query())->links() }}
            </div>
        </div>
    @endif

    {{-- ───── เคล็ดลับการจัดการบิลลอย ───── --}}
    <div class="tp-card">
        <div class="tp-tile">
            <div class="tp-section-h" style="margin-bottom:12px;">
                <i class="fas fa-lightbulb"></i> เคล็ดลับการจัดการบิลลอย
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
                <div class="tp-well" style="padding:14px;display:flex;gap:10px;align-items:flex-start;">
                    <i class="fas fa-user-magnifying-glass" style="color:var(--accent1);margin-top:2px;"></i>
                    <span class="tp-muted" style="font-size:13px;line-height:1.6;">ตรวจสอบชื่อผู้โอนเทียบกับชื่อ Facebook ของผู้ใช้ที่รอชำระเงิน</span>
                </div>
                <div class="tp-well" style="padding:14px;display:flex;gap:10px;align-items:flex-start;">
                    <i class="fas fa-money-bill-wave" style="color:var(--accent1);margin-top:2px;"></i>
                    <span class="tp-muted" style="font-size:13px;line-height:1.6;">เช็คยอดเงินที่โอนเทียบกับราคาที่ตั้งไว้ (เช่น 49.xx บาท)</span>
                </div>
                <div class="tp-well" style="padding:14px;display:flex;gap:10px;align-items:flex-start;">
                    <i class="fab fa-facebook-messenger" style="color:var(--accent1);margin-top:2px;"></i>
                    <span class="tp-muted" style="font-size:13px;line-height:1.6;">ติดต่อผู้ใช้ผ่าน Facebook Messenger เพื่อยืนยันการโอนเงิน</span>
                </div>
                <div class="tp-well" style="padding:14px;display:flex;gap:10px;align-items:flex-start;">
                    <i class="fas fa-clock-rotate-left" style="color:var(--accent1);margin-top:2px;"></i>
                    <span class="tp-muted" style="font-size:13px;line-height:1.6;">หากไม่พบเจ้าของ อาจเป็นการโอนผิด ควรเก็บข้อมูลไว้อย่างน้อย 30 วัน</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
