@extends('layouts.admin-v4')

@section('title', 'จัดการการยืนยันตัวตน (KYC)')

@section('content')
{{-- 🪪 จัดการการยืนยันตัวตน KYC (ธีม V4 นวลทองคำ) — คงตัวกรอง/ตาราง/ลิงก์เดิม 100% --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · สมาชิก · ยืนยันตัวตน</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">การยืนยันตัวตน (KYC) 🪪</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตรวจสอบเอกสารและอนุมัติการยืนยันตัวตนของสมาชิก</div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(($stats['ai_review'] ?? 0) > 0)
                <a href="{{ route('admin.kyc.index', ['ai' => 'review']) }}" class="tp-btn tp-btn-sm tp-btn-primary">
                    <i class="fas fa-robot"></i> AI ส่งตรวจ {{ number_format($stats['ai_review']) }} รายการ
                </a>
            @endif
            @if($stats['pending'] > 0)
                <a href="{{ route('admin.kyc.index', ['status' => 'pending']) }}" class="tp-btn tp-btn-sm" style="color:#a87d1e;">
                    <i class="fas fa-clock"></i> รอตรวจสอบ {{ number_format($stats['pending']) }} รายการ
                </a>
            @endif
        </div>
    </div>

    {{-- ===== Flash messages ===== --}}
    @if(session('success'))
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #5aa07e;">
            <div style="display:flex; align-items:center; gap:10px; font-size:13.5px; color:var(--ink);">
                <i class="fas fa-circle-check" style="color:#5aa07e;"></i>{{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:center; gap:10px; font-size:13.5px; color:var(--ink);">
                <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i>{{ session('error') }}
            </div>
        </div>
    @endif

    {{-- ===== KPI grid ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        {{-- ทั้งหมด (ไทล์ทองคำ default) --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ทั้งหมด</div>
                </div>
            </div>
        </div>

        {{-- รอตรวจสอบ --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#e0a52e;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['pending']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">รอตรวจสอบ</div>
                </div>
            </div>
        </div>

        {{-- อนุมัติแล้ว --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5aa07e;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['approved']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">อนุมัติแล้ว</div>
                </div>
            </div>
        </div>

        {{-- 🪪 AI eKYC: ส่งให้คนตรวจ / อนุมัติเองวันนี้ --}}
        <a href="{{ route('admin.kyc.index', ['ai' => 'review']) }}" class="tp-card" style="padding:18px; text-decoration:none; color:inherit;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#c9a24a;">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['ai_review'] ?? 0) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">AI ส่งตรวจ · อนุมัติเองวันนี้ {{ number_format($stats['ai_approved_today'] ?? 0) }}</div>
                </div>
            </div>
        </a>

        {{-- ปฏิเสธ --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#d9534f;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['rejected']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ปฏิเสธ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Filters ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</div>
        <form method="GET" action="{{ route('admin.kyc.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
            {{-- ค้นหา (ชื่อ / อีเมล) --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    🔍 ค้นหา (ชื่อ / อีเมล)
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ชื่อ หรือ อีเมลสมาชิก"
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            {{-- สถานะ --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    สถานะ
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="status" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ รอตรวจสอบ</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>✅ อนุมัติแล้ว</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>❌ ปฏิเสธ</option>
                        <option value="retake" {{ request('status') === 'retake' ? 'selected' : '' }}>📸 ให้ถ่ายใหม่ (eKYC)</option>
                    </select>
                </div>
            </div>

            {{-- 🪪 ช่องทาง: AI ส่งตรวจ / AI eKYC / แบบเดิม --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    ช่องทาง
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="ai" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="review" {{ request('ai') === 'review' ? 'selected' : '' }}>🤖 AI ส่งตรวจ</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    วิธียืนยัน
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="method" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="ekyc" {{ request('method') === 'ekyc' ? 'selected' : '' }}>AI eKYC (บัตร + ใบหน้า)</option>
                        <option value="manual" {{ request('method') === 'manual' ? 'selected' : '' }}>แบบเดิม (อัปโหลดรูป)</option>
                    </select>
                </div>
            </div>

            {{-- จำนวนต่อหน้า --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    จำนวนต่อหน้า
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="per_page" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่ม action --}}
            <div style="grid-column:1 / -1; display:flex; flex-wrap:wrap; gap:10px; padding-top:4px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> กรองข้อมูล
                </button>
                <a href="{{ route('admin.kyc.index') }}" class="tp-btn">
                    <i class="fas fa-rotate-left"></i> ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    {{-- ===== KYC Table ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ผู้ใช้</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">วันที่ส่ง</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">เอกสาร</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ผู้ตรวจสอบ</th>
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kycVerifications as $kyc)
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- ผู้ใช้ --}}
                            <td style="padding:14px 16px; font-size:13.5px; color:var(--ink);">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="tp-tile" style="width:34px; height:34px; border-radius:50%; font-size:13px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        {{ mb_strtoupper(mb_substr($kyc->user?->name ?: '?', 0, 1)) }}
                                    </span>
                                    <div style="min-width:0;">
                                        <div style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:200px;">{{ $kyc->user?->name ?: 'ไม่ระบุชื่อ' }}</div>
                                        <div style="font-size:11.5px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:200px;">{{ $kyc->user?->email ?: '-' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- วันที่ส่ง --}}
                            <td style="padding:14px 16px; font-size:13.5px; color:var(--ink); white-space:nowrap;">
                                @if($kyc->submitted_at)
                                    <div>{{ $kyc->submitted_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $kyc->submitted_at->format('H:i') }}</div>
                                @else
                                    <span style="color:var(--ink2);">-</span>
                                @endif
                            </td>

                            {{-- เอกสาร — เตือนตั้งแต่หน้ารายการว่าไฟล์เปิดไม่ขึ้น --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @php
                                    $isEkycRow = $kyc->isEkyc();
                                    $hasIdCard = $isEkycRow ? false : $kyc->hasIdCardImage();
                                    $hasSelfie = $isEkycRow ? false : $kyc->hasSelfieImage();
                                    $ekycCos = $kyc->ai_face_match !== null ? number_format($kyc->ai_face_match, 2) : '-';
                                    $ekycDecision = ['approved' => 'AI อนุมัติ', 'review' => 'AI ส่งตรวจ', 'retake' => 'AI ให้ถ่ายใหม่'][$kyc->ai_decision] ?? 'AI';
                                @endphp
                                @if($isEkycRow)
                                    <span class="tp-pill" style="background:rgba(201,162,74,.18); color:#8a6514;" title="ใบหน้าตรงกับบัตร (cosine)">
                                        <i class="fas fa-robot"></i> {{ $ekycDecision }} · cos {{ $ekycCos }}
                                    </span>
                                @elseif($hasIdCard && $hasSelfie)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">
                                        <i class="fas fa-images"></i> ครบ 2 รูป
                                    </span>
                                @elseif($hasIdCard || $hasSelfie)
                                    <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;" title="มีรูปเดียว อีกรูปไม่พบไฟล์">
                                        <i class="fas fa-triangle-exclamation"></i> มี 1 รูป
                                    </span>
                                @else
                                    <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;" title="ไม่พบไฟล์รูปภาพบนเซิร์ฟเวอร์">
                                        <i class="fas fa-image"></i> ไม่พบไฟล์
                                    </span>
                                @endif
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($kyc->status === 'pending')
                                    <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;">⏳ รอตรวจสอบ</span>
                                @elseif($kyc->status === 'approved')
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">✅ อนุมัติแล้ว</span>
                                @elseif($kyc->status === 'rejected')
                                    <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;">❌ ปฏิเสธ</span>
                                @elseif($kyc->status === 'retake')
                                    <span class="tp-pill" style="background:rgba(86,137,184,.18); color:#3f6a96;">📸 ให้ถ่ายใหม่</span>
                                @elseif($kyc->status === 'superseded')
                                    <span class="tp-pill tp-pill-soft">ยืนยันด้วย eKYC แล้ว</span>
                                @else
                                    <span class="tp-pill tp-pill-soft">{{ $kyc->status }}</span>
                                @endif
                            </td>

                            {{-- ผู้ตรวจสอบ --}}
                            <td style="padding:14px 16px; font-size:13.5px; color:var(--ink2); white-space:nowrap;">
                                {{ $kyc->reviewer->name ?? '-' }}
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.kyc.show', $kyc) }}" class="tp-btn tp-btn-sm tp-btn-primary">
                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:0;">
                                {{-- Empty state --}}
                                <div style="text-align:center; color:var(--ink2); padding:40px 0;">
                                    <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                    ไม่พบข้อมูลการยืนยันตัวตน
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($kycVerifications->hasPages())
        <div>
            {{ $kycVerifications->appends(request()->query())->links() }}
        </div>
    @endif

</div>
@endsection
