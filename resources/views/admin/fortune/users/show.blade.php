@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ───────────────────────────────────────────────────────────
     ผู้ใช้ดูดวง — รายละเอียด (ธีม V4 "นวลทองคำ")
     ประวัติผู้ใช้ + การทำนายที่ผ่านมา + ฟอร์มส่งข้อความ/เพิ่มเครดิต
     คงฟังก์ชันเดิม 100%: send-message / quick-add-credits / Alpine toggle
     route('admin.fortune.users.show', [$platform, $userId]) ส่ง 2 พารามิเตอร์
─────────────────────────────────────────────────────────────── --}}
<div x-data="userDetail()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · รายละเอียดผู้ใช้
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                {{ $userInfo['facebook_user_name'] }}
                @if($userInfo['platform'] === 'line')
                    <span class="tp-pill" style="color:#fff; background:#5aa07e; font-size:11px;">🟢 LINE</span>
                @else
                    <span class="tp-pill" style="color:#fff; background:#5689b8; font-size:11px;">📘 Facebook</span>
                @endif
            </h1>
            <div class="tp-num" style="margin-top:6px; font-size:12px; color:var(--ink2); word-break:break-all;">
                <i class="fas fa-fingerprint" style="margin-right:5px;"></i>{{ $userInfo['facebook_user_id'] }}
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.fortune.users.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i> กลับรายการ
            </a>
            <button type="button" @click="showSendMessage = !showSendMessage" class="tp-btn tp-btn-sm tp-btn-primary">
                <i class="fas fa-comment-dots"></i> ส่งข้อความ
            </button>
            {{-- ฟอร์มเพิ่ม 5 เครดิตฟรี — คง action/field เดิมเป๊ะ --}}
            <form action="{{ route('admin.fortune.users.quick-add-credits') }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="facebook_user_id" value="{{ $userInfo['facebook_user_id'] }}">
                <input type="hidden" name="platform" value="{{ $userInfo['platform'] }}">
                <input type="hidden" name="facebook_user_name" value="{{ $userInfo['facebook_user_name'] }}">
                <input type="hidden" name="amount" value="5">
                <button type="submit" class="tp-btn tp-btn-sm" style="color:#fff; background:#5aa07e; border:0;">
                    <i class="fas fa-gift"></i> +5 เครดิต
                </button>
            </form>
        </div>
    </div>

    {{-- ===== KPI grid: สถิติผู้ใช้ ===== --}}
    @php
        // การ์ด KPI 5 ใบ — ใช้ field จาก $userInfo (controller) เป๊ะ
        $kpis = [
            ['ดูดวงทั้งหมด', number_format($userInfo['total_readings']), 'fa-wand-magic-sparkles', 'var(--deep1)'],
            ['ดูดวงละเอียด', number_format($userInfo['deep_readings']), 'fa-gem', '#b79ae8'],
            ['จ่ายรวม', '฿' . number_format($userInfo['total_spent'], 0), 'fa-coins', '#5aa07e'],
            ['ดูครั้งแรก', $userInfo['first_reading'] ? \Carbon\Carbon::parse($userInfo['first_reading'])->format('d/m/Y') : '—', 'fa-flag-checkered', '#5689b8'],
            ['ดูล่าสุด', $userInfo['last_reading'] ? \Carbon\Carbon::parse($userInfo['last_reading'])->diffForHumans() : '—', 'fa-clock-rotate-left', '#d6824a'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:14px;">
        @foreach($kpis as [$label, $val, $icon, $color])
            <div class="tp-card tp-card-hover">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px;">
                        <i class="fas {{ $icon }}"></i>
                    </span>
                </div>
                <div class="tp-num" style="font-size:clamp(18px,2.4vw,26px); font-weight:800; margin-top:10px; color:{{ $color }};">
                    {{ $val }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Credit Info (ถ้ามี credit) ===== --}}
    @if($credit)
        <div class="tp-card" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:17px;">
                <i class="fas fa-gift"></i>
            </span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600;">เครดิตพิเศษ</div>
                @if($credit->isCurrentlyUnlimited())
                    <div class="tp-num" style="font-size:15px; font-weight:800; color:#b79ae8; margin-top:3px;">
                        🌟 ดูฟรีไม่จำกัด
                    </div>
                @else
                    <div class="tp-num" style="font-size:15px; font-weight:800; color:var(--deep1); margin-top:3px;">
                        เหลือ {{ $credit->getRemainingCredits() }} ครั้ง
                    </div>
                    <div class="tp-num" style="font-size:11.5px; color:var(--ink2); margin-top:2px;">
                        ใช้ไป {{ $credit->credits_used }} / {{ $credit->bonus_credits }}
                    </div>
                @endif
            </div>
            <a href="{{ route('admin.fortune.credits.index', ['search' => $userInfo['facebook_user_id']]) }}"
               class="tp-btn tp-btn-sm">
                จัดการ <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    @endif

    {{-- ===== Send Message Form (Alpine toggle เดิม) ===== --}}
    <div x-show="showSendMessage" x-cloak class="tp-card" style="border:1.5px solid var(--a1soft);">
        <div class="tp-section-h" style="margin-bottom:14px;">
            <i class="fas fa-comment-dots" style="margin-right:6px; color:var(--deep1);"></i>
            ส่งข้อความถึง {{ $userInfo['facebook_user_name'] }}
        </div>
        {{-- คง action/method/@csrf/field hidden เดิมทั้งหมด --}}
        <form action="{{ route('admin.fortune.users.send-message') }}" method="POST">
            @csrf
            <input type="hidden" name="platform" value="{{ $userInfo['platform'] }}">
            <input type="hidden" name="facebook_user_id" value="{{ $userInfo['facebook_user_id'] }}">
            <div class="tp-well tp-input" style="padding:0; margin-bottom:14px;">
                <textarea name="message" rows="4" required maxlength="2000"
                          placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
                          style="width:100%; background:transparent; border:0; outline:0; padding:12px 14px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-paper-plane"></i> ส่งข้อความ
                </button>
                <button type="button" @click="showSendMessage = false" class="tp-btn">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>

    {{-- ===== ประวัติการดูดวง ===== --}}
    <div class="tp-divider"></div>
    <div class="tp-section-h" style="display:flex; align-items:center; gap:8px;">
        <i class="fas fa-scroll" style="color:var(--deep1);"></i> ประวัติการดูดวง
        <span class="tp-pill tp-pill-soft tp-num" style="font-size:11px;">{{ $readings->total() }} รายการ</span>
    </div>

    <div style="display:flex; flex-direction:column; gap:14px;">
        @forelse($readings as $reading)
            @php
                // สีสถานะ conversation_status — map ตามค่าจริงในระบบ
                $statusColor = match($reading->conversation_status) {
                    'completed' => '#5aa07e',
                    'paid' => '#5689b8',
                    'pending_payment' => '#e0a52e',
                    default => '#9a8f7c',
                };
            @endphp
            <div class="tp-card tp-card-hover">
                {{-- หัวการ์ด: ชนิด + สถานะ + ลิงก์ --}}
                <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:20px;">
                            {{ $reading->reading_type === 'deep' ? '💎' : '🔮' }}
                        </span>
                        <div>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span class="tp-num" style="font-size:14px; font-weight:700; color:var(--ink);">
                                    {{ $reading->reading_type === 'deep' ? 'ดูดวงละเอียด' : 'ดูดวงพื้นฐาน' }}
                                </span>
                                @if($reading->is_paid)
                                    <span class="tp-pill" style="color:#fff; background:#5aa07e; font-size:10.5px;">
                                        ฿{{ number_format($reading->amount_paid, 0) }}
                                    </span>
                                @endif
                            </div>
                            <div class="tp-num" style="font-size:11.5px; color:var(--ink2); margin-top:3px;">
                                {{ $reading->created_at->format('d/m/Y H:i') }}
                                · {{ $reading->created_at->diffForHumans() }}
                                @if($reading->ai_provider)
                                    · {{ $reading->ai_provider }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
                        @if($reading->conversation_status)
                            <span class="tp-pill" style="color:#fff; background:{{ $statusColor }}; font-size:10.5px;">
                                {{ $reading->conversation_status }}
                            </span>
                        @endif
                        <a href="{{ route('admin.fortune.readings.show', $reading) }}" class="tp-btn tp-btn-sm">
                            ดูเพิ่มเติม <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                {{-- คำถาม --}}
                @if($reading->questions && is_array($reading->questions))
                    <div style="margin-top:14px;">
                        <div style="font-size:11px; color:var(--ink2); font-weight:600; margin-bottom:6px;">คำถาม</div>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            @foreach($reading->questions as $q)
                                <div style="font-size:13px; color:var(--ink); padding-left:12px; border-left:3px solid var(--a1soft);">
                                    {{ $q }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- คำตอบ (แสดงบางส่วน) --}}
                @if($reading->ai_response)
                    <div class="tp-inset" style="margin-top:14px; padding:13px 15px; border-radius:13px;">
                        <div style="font-size:11px; color:var(--ink2); font-weight:600; margin-bottom:5px;">คำทำนาย</div>
                        <div style="font-size:13px; color:var(--ink); line-height:1.65;">
                            {{ Str::limit(strip_tags($reading->ai_response), 300) }}
                        </div>
                    </div>
                @endif
            </div>
        @empty
            {{-- Empty state --}}
            <div class="tp-card" style="text-align:center; padding:48px 20px;">
                <i class="fas fa-inbox" style="font-size:42px; color:var(--ink2); opacity:.55;"></i>
                <div class="tp-num" style="font-size:15px; font-weight:700; color:var(--ink); margin-top:14px;">
                    ยังไม่มีประวัติการดูดวง
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    เมื่อผู้ใช้เริ่มดูดวง รายการจะแสดงที่นี่
                </div>
            </div>
        @endforelse
    </div>

    {{-- ===== Pagination ===== --}}
    @if($readings->hasPages())
        <div style="margin-top:4px;">
            {{ $readings->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Alpine component เดิม — ย้ายมา @push('scripts') ตาม layout contract V4
    function userDetail() {
        return {
            showSendMessage: false,
        };
    }
</script>
@endpush
