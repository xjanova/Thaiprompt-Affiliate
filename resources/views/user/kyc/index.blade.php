@extends('layouts.user-v4')

@section('title', 'ยืนยันตัวตน (KYC)')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden; position:relative;">
        {{-- ภาพประกอบหัวเรื่อง (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.hero-art image="usr-kyc" />
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:24px; background:#5689b8;"><i class="fas fa-user-shield" style="color:#fff;"></i></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ยืนยันตัวตน (KYC)</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ยืนยันตัวตนเพื่อความปลอดภัยและเพิ่มความน่าเชื่อถือของบัญชี</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ข้อความแจ้งเตือน ──────────────────────────────────── --}}
    @foreach(['success' => ['#5aa07e','fa-check-circle'], 'error' => ['#d9534f','fa-exclamation-circle'], 'warning' => ['#d9a441','fa-exclamation-triangle'], 'info' => ['#5689b8','fa-info-circle']] as $key => $meta)
        @if(session($key))
            <div class="tp-card" style="padding:14px 16px; border-left:4px solid {{ $meta[0] }};">
                <i class="fas {{ $meta[1] }}" style="color:{{ $meta[0] }}; margin-right:8px;"></i>{{ session($key) }}
            </div>
        @endif
    @endforeach

    {{-- OCR สำเร็จ --}}
    @if(session('ocr_success'))
        <div class="tp-card" style="padding:16px; border-left:4px solid #5aa07e;">
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <i class="fas fa-robot" style="font-size:22px; color:#5aa07e;"></i>
                <div>
                    <div style="font-weight:800; color:var(--ink);"><i class="fas fa-check-circle" style="margin-right:4px;"></i>{{ session('ocr_success') }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">ข้อมูลจากบัตรประชาชนถูกอ่านและบันทึกอัตโนมัติแล้ว แอดมินจะตรวจสอบความถูกต้องอีกครั้ง</div>
                </div>
            </div>
        </div>
    @endif

    {{-- OCR ผิดพลาด --}}
    @if(session('ocr_error'))
        <div class="tp-card" style="padding:16px; border-left:4px solid #d9a441;">
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <i class="fas fa-exclamation-triangle" style="font-size:22px; color:#d9a441;"></i>
                <div>
                    <div style="font-weight:800; color:var(--ink);"><i class="fas fa-robot" style="margin-right:4px;"></i>ระบบ OCR: {{ session('ocr_error') }}</div>
                    @if(session('ocr_suggestion'))
                        <div style="font-size:12px; color:var(--ink2); margin-top:8px;"><i class="fas fa-lightbulb" style="margin-right:4px;"></i><strong>คำแนะนำ:</strong> {{ session('ocr_suggestion') }}</div>
                    @endif
                    <div style="margin-top:12px; padding:10px; border-radius:10px; box-shadow:var(--inset-sm); font-size:12px; color:var(--ink2);">
                        <i class="fas fa-info-circle" style="margin-right:4px;"></i><strong>หมายเหตุ:</strong> คำขอของคุณถูกส่งเรียบร้อยแล้ว แต่ระบบไม่สามารถอ่านข้อมูลจากบัตรอัตโนมัติได้ แอดมินจะกรอกข้อมูลด้วยตนเองในขั้นตอนการตรวจสอบ
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── การ์ดสถานะ KYC ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:28px;">
        @php $st = auth()->user()->kyc_status; @endphp

        @if($st === 'pending')
            <div style="text-align:center;">
                <div style="margin-bottom:20px;">
                    <span style="display:inline-flex; align-items:center; gap:10px; padding:12px 24px; border-radius:999px; font-size:16px; font-weight:800; color:#fff; background:#d9a441;">
                        <i class="fas fa-hourglass-half"></i> กำลังรอตรวจสอบเอกสาร
                    </span>
                </div>
                <div style="width:96px; height:96px; margin:0 auto 16px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-clock" style="font-size:36px; color:#d9a441;"></i>
                </div>
                <h2 style="font-size:22px; font-weight:800; color:var(--ink); margin:0 0 8px;">เอกสารอยู่ระหว่างการตรวจสอบ</h2>
                <p style="color:var(--ink2); margin:0 0 20px;">ทีมงานกำลังตรวจสอบเอกสารของคุณ โปรดรอผลการตรวจสอบภายใน <strong style="color:#d9a441;">1-3 วันทำการ</strong></p>

                @if($kycVerification)
                    <div style="max-width:420px; margin:0 auto; border-radius:14px; box-shadow:var(--inset-sm); padding:20px; text-align:left;">
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:13px;">
                            <span style="color:var(--ink2);"><i class="fas fa-calendar-alt" style="margin-right:6px; color:#d9a441;"></i>วันที่ส่ง:</span>
                            <span style="font-weight:700; color:var(--ink);">{{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:13px;">
                            <span style="color:var(--ink2);"><i class="fas fa-info-circle" style="margin-right:6px; color:#d9a441;"></i>สถานะ:</span>
                            <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; color:#d9a441; background:color-mix(in srgb, #d9a441 16%, transparent);">รอการตรวจสอบ</span>
                        </div>
                    </div>
                    <a href="{{ route('user.kyc.show', $kycVerification) }}" class="tp-btn" style="margin-top:20px;"><i class="fas fa-eye"></i> ดูเอกสารที่ส่ง</a>
                @endif

                <div style="margin-top:20px; max-width:420px; margin-inline:auto; padding:14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <p style="font-size:13px; color:var(--ink2); margin:0;"><i class="fas fa-info-circle" style="margin-right:6px;"></i><strong>หมายเหตุ:</strong> คุณจะได้รับการแจ้งเตือนเมื่อการตรวจสอบเสร็จสิ้น</p>
                </div>
            </div>

        @elseif($st === 'approved')
            <div style="text-align:center;">
                <div style="margin-bottom:20px;">
                    <span style="display:inline-flex; align-items:center; gap:10px; padding:12px 24px; border-radius:999px; font-size:16px; font-weight:800; color:#fff; background:#5aa07e;">
                        <i class="fas fa-shield-alt"></i> ยืนยันตัวตนเรียบร้อยแล้ว
                    </span>
                </div>
                <div style="width:96px; height:96px; margin:0 auto 16px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-check-circle" style="font-size:36px; color:#5aa07e;"></i>
                </div>
                <h2 style="font-size:22px; font-weight:800; color:var(--ink); margin:0 0 8px;">บัญชีได้รับการยืนยันแล้ว</h2>
                <p style="color:var(--ink2); margin:0 0 20px;">คุณสามารถใช้งานฟีเจอร์ทั้งหมดได้อย่างเต็มรูปแบบ รวมถึงการถอนเงิน</p>

                @if($kycVerification)
                    <div style="max-width:420px; margin:0 auto; border-radius:14px; box-shadow:var(--inset-sm); padding:20px; text-align:left;">
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:13px;">
                            <span style="color:var(--ink2);"><i class="fas fa-calendar-check" style="margin-right:6px; color:#5aa07e;"></i>วันที่อนุมัติ:</span>
                            <span style="font-weight:700; color:var(--ink);">{{ auth()->user()->kyc_verified_at ? auth()->user()->kyc_verified_at->format('d/m/Y H:i') : '-' }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:13px;">
                            <span style="color:var(--ink2);"><i class="fas fa-award" style="margin-right:6px; color:#5aa07e;"></i>สถานะ:</span>
                            <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent);">อนุมัติแล้ว</span>
                        </div>
                    </div>
                    <a href="{{ route('user.kyc.show', $kycVerification) }}" class="tp-btn" style="margin-top:20px;"><i class="fas fa-eye"></i> ดูรายละเอียด</a>
                @endif

                <div style="margin-top:20px; display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; max-width:520px; margin-inline:auto;">
                    <div style="padding:14px; border-radius:12px; box-shadow:var(--inset-sm); text-align:center;">
                        <i class="fas fa-money-bill-wave" style="color:#5aa07e; font-size:18px;"></i>
                        <p style="font-size:11px; color:var(--ink2); font-weight:600; margin:4px 0 0;">ถอนเงินได้</p>
                    </div>
                    <div style="padding:14px; border-radius:12px; box-shadow:var(--inset-sm); text-align:center;">
                        <i class="fas fa-shield-alt" style="color:#5aa07e; font-size:18px;"></i>
                        <p style="font-size:11px; color:var(--ink2); font-weight:600; margin:4px 0 0;">บัญชีปลอดภัย</p>
                    </div>
                    <div style="padding:14px; border-radius:12px; box-shadow:var(--inset-sm); text-align:center;">
                        <i class="fas fa-star" style="color:#5aa07e; font-size:18px;"></i>
                        <p style="font-size:11px; color:var(--ink2); font-weight:600; margin:4px 0 0;">ฟีเจอร์ครบ</p>
                    </div>
                </div>
            </div>

        @elseif($st === 'rejected')
            <div style="text-align:center;">
                <div style="margin-bottom:20px;">
                    <span style="display:inline-flex; align-items:center; gap:10px; padding:12px 24px; border-radius:999px; font-size:16px; font-weight:800; color:#fff; background:#d9534f;">
                        <i class="fas fa-exclamation-triangle"></i> ไม่ผ่านการตรวจสอบ
                    </span>
                </div>
                <div style="width:96px; height:96px; margin:0 auto 16px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-times-circle" style="font-size:36px; color:#d9534f;"></i>
                </div>
                <h2 style="font-size:22px; font-weight:800; color:var(--ink); margin:0 0 8px;">เอกสารไม่ผ่านการตรวจสอบ</h2>
                <p style="color:var(--ink2); margin:0 0 16px;">กรุณาตรวจสอบเหตุผลด้านล่าง และส่งเอกสารใหม่</p>

                @if($kycVerification && $kycVerification->rejection_reason)
                    <div style="max-width:420px; margin:0 auto 20px; border-radius:14px; box-shadow:var(--inset-sm); padding:20px; text-align:left; border-left:4px solid #d9534f;">
                        <div style="font-weight:800; color:#d9534f; margin-bottom:10px;"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>เหตุผลในการปฏิเสธ</div>
                        <p style="color:var(--ink); margin:0;">{{ $kycVerification->rejection_reason }}</p>
                    </div>
                @endif

                <a href="{{ route('user.kyc.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fas fa-redo"></i> ส่งเอกสารใหม่</a>

                <div style="margin-top:20px; max-width:420px; margin-inline:auto; padding:14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <p style="font-size:13px; color:var(--ink2); margin:0;"><i class="fas fa-lightbulb" style="margin-right:6px;"></i><strong>คำแนะนำ:</strong> ถ่ายรูปบัตรให้ชัดเจน มีแสงสว่างเพียงพอ และเห็นข้อมูลครบถ้วน</p>
                </div>
            </div>

        @else
            {{-- not_submitted / null / ค่าอื่น --}}
            <div style="text-align:center; padding:12px 0;">
                <div style="width:96px; height:96px; margin:0 auto 16px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-id-card" style="font-size:36px; color:var(--ink2);"></i>
                </div>
                <h2 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 8px;">คุณยังไม่ได้ยืนยันตัวตน</h2>
                <p style="color:var(--ink2); margin:0 0 20px;">ยืนยันตัวตนเพื่อเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชีของคุณ</p>
                <a href="{{ route('user.kyc.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fas fa-upload"></i> เริ่มยืนยันตัวตน</a>
            </div>
        @endif
    </div>

    {{-- ── ข้อมูลเกี่ยวกับ KYC ────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px; border-left:4px solid #5689b8;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-info-circle" style="margin-right:6px;"></i>ข้อมูลเกี่ยวกับการยืนยันตัวตน</div>
        <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px; font-size:13px; color:var(--ink);">
            <li style="display:flex; gap:8px;"><i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i><span>การยืนยันตัวตนช่วยเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชี</span></li>
            <li style="display:flex; gap:8px;"><i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i><span>คุณจะต้องอัปโหลดรูปบัตรประชาชน หรือ ใบขับขี่ และรูปถ่ายตัวเองพร้อมบัตร</span></li>
            <li style="display:flex; gap:8px;"><i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i><span>ข้อมูลของคุณจะถูกเก็บเป็นความลับและปลอดภัย</span></li>
            <li style="display:flex; gap:8px;"><i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i><span>การตรวจสอบอาจใช้เวลา 1-3 วันทำการ</span></li>
        </ul>
    </div>
</div>
@endsection
