@extends('layouts.admin-v4')

@section('title', 'ตรวจสอบการยืนยันตัวตน')

@section('content')
{{-- 🪪 ตรวจสอบการยืนยันตัวตน KYC (ธีม V4 นวลทองคำ) — คง route/ฟอร์ม/โมดัล/JS เดิม 100% --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.kyc.index') }}" class="tp-icon-btn" title="กลับไปหน้ารายการ">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · สมาชิก · ยืนยันตัวตน</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ตรวจสอบการยืนยันตัวตน 🪪</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตรวจสอบเอกสารแล้วอนุมัติหรือปฏิเสธการยืนยันตัวตน</div>
            </div>
        </div>
        <div>
            @if($kycVerification->status === 'pending')
                <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;">⏳ รอตรวจสอบ</span>
            @elseif($kycVerification->status === 'approved')
                <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">✅ อนุมัติแล้ว</span>
            @elseif($kycVerification->status === 'rejected')
                <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;">❌ ปฏิเสธ</span>
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

    {{-- ===== ข้อมูลผู้ใช้ + สถานะ ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">

        {{-- ข้อมูลผู้ใช้ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-user"></i> ข้อมูลผู้ใช้</div>

            <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px;">
                <div class="tp-tile" style="width:56px; height:56px; border-radius:50%; font-size:22px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    {{ mb_strtoupper(mb_substr($kycVerification->user->name ?? '?', 0, 1)) }}
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:700; font-size:15px; color:var(--ink);">{{ $kycVerification->user->name ?? 'ไม่ระบุชื่อ' }}</div>
                    <div style="font-size:12.5px; color:var(--ink2);">{{ $kycVerification->user->email ?? '-' }}</div>
                </div>
            </div>

            <div class="tp-divider" style="margin:14px 0;"></div>

            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; gap:12px; font-size:13.5px;">
                    <span style="color:var(--ink2);">User ID</span>
                    <span style="font-weight:600; color:var(--ink);">{{ $kycVerification->user->id }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; gap:12px; font-size:13.5px;">
                    <span style="color:var(--ink2);">บทบาท</span>
                    <span style="font-weight:600; color:var(--ink);">{{ ucfirst($kycVerification->user->role) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; gap:12px; font-size:13.5px;">
                    <span style="color:var(--ink2);">วันที่สมัคร</span>
                    <span style="font-weight:600; color:var(--ink);">{{ $kycVerification->user->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        {{-- สถานะการยืนยัน --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-clipboard-check"></i> สถานะการยืนยัน</div>

            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; font-size:13.5px;">
                    <span style="color:var(--ink2);">สถานะ</span>
                    @if($kycVerification->status === 'pending')
                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;">⏳ รอตรวจสอบ</span>
                    @elseif($kycVerification->status === 'approved')
                        <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">✅ อนุมัติแล้ว</span>
                    @elseif($kycVerification->status === 'rejected')
                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;">❌ ปฏิเสธ</span>
                    @endif
                </div>

                <div style="display:flex; justify-content:space-between; gap:12px; font-size:13.5px;">
                    <span style="color:var(--ink2);">วันที่ส่ง</span>
                    <span style="font-weight:600; color:var(--ink);">
                        {{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}
                    </span>
                </div>

                @if($kycVerification->reviewed_at)
                    <div style="display:flex; justify-content:space-between; gap:12px; font-size:13.5px;">
                        <span style="color:var(--ink2);">วันที่ตรวจสอบ</span>
                        <span style="font-weight:600; color:var(--ink);">{{ $kycVerification->reviewed_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif

                @if($kycVerification->reviewer)
                    <div style="display:flex; justify-content:space-between; gap:12px; font-size:13.5px;">
                        <span style="color:var(--ink2);">ตรวจสอบโดย</span>
                        <span style="font-weight:600; color:var(--ink);">{{ $kycVerification->reviewer->name }}</span>
                    </div>
                @endif

                @if($kycVerification->status === 'rejected' && $kycVerification->rejection_reason)
                    <div class="tp-divider" style="margin:6px 0;"></div>
                    <div>
                        <div style="font-size:12.5px; color:var(--ink2); margin-bottom:8px;">เหตุผลในการปฏิเสธ</div>
                        <div class="tp-well" style="padding:12px; border-left:3px solid #d9534f;">
                            <p style="margin:0; font-size:13.5px; color:var(--ink);">{{ $kycVerification->rejection_reason }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== 🤖 ผลตรวจอัตโนมัติชั้นที่ 1 (KycAutoCheckService) ===== --}}
    @if(!empty($autoChecks))
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;">
                <div class="tp-section-h" style="margin:0;"><i class="fas fa-clipboard-check"></i> ผลตรวจอัตโนมัติ</div>
                @if($autoChecks['all_green'])
                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c; font-weight:700;">
                        ✅ ผ่านทุกข้อ — กดอนุมัติได้เลย
                    </span>
                @elseif($autoChecks['fail'] > 0)
                    <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; font-weight:700;">
                        ⛔ ไม่ผ่าน {{ $autoChecks['fail'] }} ข้อ — ตรวจละเอียดก่อน
                    </span>
                @else
                    <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e; font-weight:700;">
                        ⚠️ มีข้อที่ต้องตรวจเอง
                    </span>
                @endif
            </div>

            <div style="display:flex; flex-direction:column; gap:9px;">
                @foreach($autoChecks['checks'] as $check)
                    @php
                        // สีตามผลตรวจ — ธีม V4 ใช้ inline style ไม่ใช่คลาส utility
                        $style = match ($check['status']) {
                            'pass' => ['icon' => '✅', 'accent' => '#5aa07e'],
                            'warn' => ['icon' => '⚠️', 'accent' => '#e0a52e'],
                            'fail' => ['icon' => '⛔', 'accent' => '#d9534f'],
                            default => ['icon' => '❔', 'accent' => 'var(--ink2)'],
                        };
                    @endphp
                    <div class="tp-well" style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border-left:3px solid {{ $style['accent'] }};">
                        <span style="font-size:17px; line-height:1.2; flex-shrink:0;">{{ $style['icon'] }}</span>
                        <div style="min-width:0;">
                            <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--ink);">{{ $check['label'] }}</p>
                            <p style="margin:3px 0 0; font-size:12px; color:var(--ink2);">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p style="font-size:11.5px; color:var(--ink2); margin:14px 0 0;">
                ตัวช่วยตัดสินใจเท่านั้น — การอนุมัติสุดท้ายเป็นดุลยพินิจของแอดมิน
                (ข้อ "ผู้โอนในสลิป" คือชื่อจากธนาคารจริงตอนลูกค้าจ่ายค่าดูดวง ปลอมยากที่สุด)
            </p>
        </div>
    @endif

    {{-- ===== ข้อมูลที่ตรวจจับได้จากบัตร (OCR) ===== --}}
    @if(!empty($kycVerification->extracted_data))
        @php
            $ocr = $kycVerification->extracted_data;
            $isLicense = ($ocr['document_type'] ?? null) === 'driver_license';
            // ฟิลด์ที่จะโชว์: [key, label, เต็มแถวไหม]
            $ocrFields = [
                ['license_number',     'เลขที่ใบอนุญาต',   false],
                ['id_card_number',     'เลขบัตรประชาชน',   false],
                ['thai_first_name',    'ชื่อ (ไทย)',        false],
                ['thai_last_name',     'นามสกุล (ไทย)',    false],
                ['english_first_name', 'First Name',        false],
                ['english_last_name',  'Last Name',         false],
                ['birth_date',         'วันเกิด',           false],
                ['religion',           'ศาสนา',             false],
                ['issue_date',         'วันออกบัตร',        false],
                ['expiry_date',        'วันหมดอายุ',        false],
                ['address',            'ที่อยู่',            true],
                ['license_type',       'ประเภทรถที่สามารถขับได้', true],
            ];
        @endphp
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-robot"></i> ข้อมูลที่ตรวจจับได้จากบัตร (OCR)</div>

            {{-- ป้ายประเภทเอกสาร --}}
            @if(!empty($ocr['document_type']))
                <div style="margin-bottom:16px;">
                    @if($isLicense)
                        <span class="tp-pill" style="background:rgba(183,154,232,.18); color:#7a5db8;">
                            <i class="fas fa-id-card-alt"></i> ใบขับขี่
                        </span>
                    @else
                        <span class="tp-pill" style="background:rgba(86,137,184,.18); color:#3f6a96;">
                            <i class="fas fa-id-card"></i> บัตรประชาชน
                        </span>
                    @endif
                </div>
            @endif

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px;">
                @foreach($ocrFields as [$key, $label, $fullWidth])
                    @if(!empty($ocr[$key]))
                        <div class="tp-well" style="padding:12px 14px;{{ $fullWidth ? ' grid-column:1 / -1;' : '' }}">
                            <div style="font-size:11.5px; color:var(--ink2); margin-bottom:4px;">{{ $label }}</div>
                            <div style="font-weight:600; font-size:14px; color:var(--ink); word-break:break-word;">{{ $ocr[$key] }}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="tp-well" style="margin-top:16px; padding:12px 14px; border-left:3px solid #e0a52e;">
                <p style="margin:0; font-size:12px; color:var(--ink2);">
                    <i class="fas fa-circle-info" style="margin-right:5px; color:#e0a52e;"></i>
                    <strong style="color:var(--ink);">หมายเหตุ:</strong> ข้อมูลนี้ถูกตรวจจับโดยระบบ OCR อัตโนมัติ กรุณาตรวจสอบความถูกต้องก่อนอนุมัติ
                </p>
            </div>
        </div>
    @else
        <div class="tp-card" style="padding:28px 20px; text-align:center;">
            <i class="fas fa-robot" style="font-size:26px; color:var(--ink2); opacity:.6; display:block; margin-bottom:10px;"></i>
            <p style="margin:0; font-size:13.5px; color:var(--ink2);">
                ไม่สามารถตรวจจับข้อมูลจากบัตรประชาชนได้ กรุณาตรวจสอบข้อมูลจากภาพด้านล่าง
            </p>
        </div>
    @endif

    {{-- ===== เอกสารที่ส่ง ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-images"></i> เอกสารที่ส่ง</div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px;">

            {{-- รูปบัตรประชาชน / ใบขับขี่ --}}
            <div>
                <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:10px;">
                    @if(!empty($kycVerification->extracted_data['document_type']) && $kycVerification->extracted_data['document_type'] === 'driver_license')
                        <i class="fas fa-id-card-alt" style="margin-right:5px;"></i>รูปใบขับขี่
                    @else
                        <i class="fas fa-id-card" style="margin-right:5px;"></i>รูปบัตรประชาชน
                    @endif
                </div>

                @if($kycVerification->idCardImageUrl())
                    <div style="border-radius:12px; overflow:hidden; box-shadow:var(--inset-sm); cursor:pointer; background:var(--bg);"
                         onclick="openImageModal('{{ $kycVerification->idCardImageUrl() }}')">
                        <div style="aspect-ratio:4/3; display:flex; align-items:center; justify-content:center;">
                            <img src="{{ $kycVerification->idCardImageUrl() }}" alt="ID Document"
                                 style="max-width:100%; max-height:100%; object-fit:contain; display:block;">
                        </div>
                    </div>
                    <p style="font-size:11.5px; color:var(--ink2); margin:8px 0 0; text-align:center;">
                        <i class="fas fa-search-plus" style="margin-right:4px;"></i>คลิกเพื่อขยายดูภาพเต็ม
                    </p>
                @else
                    {{-- ไม่มีไฟล์จริงบนดิสก์ — บอกสาเหตุให้ชัด แทนไอคอนรูปแตกที่ไม่บอกอะไรเลย --}}
                    <x-kyc-missing-image :path="$kycVerification->id_card_image" />
                @endif
            </div>

            {{-- รูปถ่ายตัวเองพร้อมบัตร --}}
            <div>
                <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:10px;">
                    @if(!empty($kycVerification->extracted_data['document_type']) && $kycVerification->extracted_data['document_type'] === 'driver_license')
                        <i class="fas fa-user-circle" style="margin-right:5px;"></i>รูปถ่ายตัวเองพร้อมใบขับขี่
                    @else
                        <i class="fas fa-user-circle" style="margin-right:5px;"></i>รูปถ่ายตัวเองพร้อมบัตรประชาชน
                    @endif
                </div>

                @if($kycVerification->selfieImageUrl())
                    <div style="border-radius:12px; overflow:hidden; box-shadow:var(--inset-sm); cursor:pointer; background:var(--bg);"
                         onclick="openImageModal('{{ $kycVerification->selfieImageUrl() }}')">
                        <div style="aspect-ratio:4/3; display:flex; align-items:center; justify-content:center;">
                            <img src="{{ $kycVerification->selfieImageUrl() }}" alt="Selfie with ID Card"
                                 style="max-width:100%; max-height:100%; object-fit:contain; display:block;">
                        </div>
                    </div>
                    <p style="font-size:11.5px; color:var(--ink2); margin:8px 0 0; text-align:center;">
                        <i class="fas fa-search-plus" style="margin-right:4px;"></i>คลิกเพื่อขยายดูภาพเต็ม
                    </p>
                @else
                    {{-- ไม่มีไฟล์จริงบนดิสก์ — บอกสาเหตุให้ชัด แทนไอคอนรูปแตกที่ไม่บอกอะไรเลย --}}
                    <x-kyc-missing-image :path="$kycVerification->selfie_image" />
                @endif
            </div>
        </div>

        <div class="tp-well" style="margin-top:16px; padding:12px 14px;">
            <p style="margin:0; font-size:12px; color:var(--ink2);">
                <i class="fas fa-circle-info" style="margin-right:5px;"></i>
                <strong style="color:var(--ink);">รูปแบบไฟล์:</strong> รูปภาพถูกบันทึกในรูปแบบ WebP เพื่อประหยัดพื้นที่จัดเก็บและรักษาคุณภาพ
            </p>
        </div>
    </div>

    {{-- ===== นโยบายการจัดเก็บข้อมูล ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-shield-halved"></i> นโยบายการจัดเก็บข้อมูล</div>

        @php
            // แต่ละข้อ: [ไอคอน, สี, ข้อความ HTML ที่ปลอดภัย (แต่ง <strong> เอง)]
            $policyLines = [
                ['fa-check',      '#5aa07e', 'รูปภาพเอกสารถูกบันทึกในรูปแบบ <strong>WebP</strong> คุณภาพสูงเพื่อประหยัดพื้นที่จัดเก็บ'],
                ['fa-check',      '#5aa07e', 'ข้อมูล KYC จะถูก <strong>เก็บรักษาอย่างปลอดภัย</strong> ตามมาตรฐานความปลอดภัยของระบบ'],
                ['fa-trash-can',  '#d9534f', '<strong>สำคัญ:</strong> เมื่อผู้ใช้ลบบัญชี รูปภาพ KYC และข้อมูลที่เกี่ยวข้อง <strong>จะถูกลบออกโดยอัตโนมัติ</strong> เพื่อปกป้องความเป็นส่วนตัว'],
                ['fa-eye-slash',  '#5689b8', 'รูปภาพและข้อมูลส่วนตัวจะ <strong>ไม่ถูกเปิดเผย</strong> ต่อบุคคลภายนอกโดยไม่ได้รับอนุญาต'],
            ];
        @endphp

        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($policyLines as [$icon, $color, $text])
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="flex-shrink:0; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:color-mix(in srgb, {{ $color }} 18%, transparent);">
                        <i class="fas {{ $icon }}" style="font-size:11px; color:{{ $color }};"></i>
                    </div>
                    <p style="margin:0; font-size:13px; color:var(--ink2); line-height:1.65;">{!! $text !!}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== การดำเนินการ (เฉพาะรายการที่รอตรวจสอบ) ===== --}}
    @if($kycVerification->status === 'pending')
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-gavel"></i> การดำเนินการ</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                {{-- ฟอร์มอนุมัติ --}}
                <form action="{{ route('admin.kyc.approve', $kycVerification) }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการอนุมัติการยืนยันตัวตนนี้?')">
                    @csrf
                    <button type="submit" class="tp-btn"
                            style="width:100%; justify-content:center; padding:12px; background:#5aa07e; color:#fff; border-color:#5aa07e; font-weight:700;">
                        <i class="fas fa-circle-check"></i> อนุมัติการยืนยันตัวตน
                    </button>
                </form>

                {{-- ปุ่มปฏิเสธ (เปิดโมดัล) --}}
                <button type="button" onclick="openRejectModal()" class="tp-btn"
                        style="width:100%; justify-content:center; padding:12px; background:#d9534f; color:#fff; border-color:#d9534f; font-weight:700;">
                    <i class="fas fa-circle-xmark"></i> ปฏิเสธการยืนยันตัวตน
                </button>
            </div>
        </div>
    @endif

    {{-- ===== ลบข้อมูล (เฉพาะแอดมินที่มีสิทธิ์ manage_kyc) ===== --}}
    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_kyc'))
        <div class="tp-card" style="padding:20px; border-left:4px solid #d9534f;">
            <div class="tp-section-h" style="margin-bottom:12px; color:#d9534f;"><i class="fas fa-trash"></i> ลบข้อมูล</div>
            <p style="font-size:13px; color:var(--ink2); margin:0 0 14px;">
                การลบข้อมูลการยืนยันตัวตนนี้จะไม่สามารถกู้คืนได้ (ไฟล์รูปจะถูกลบออกจากเซิร์ฟเวอร์ด้วย)
            </p>
            <form action="{{ route('admin.kyc.destroy', $kycVerification) }}" method="POST"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลการยืนยันตัวตนนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn tp-btn-sm"
                        style="background:#d9534f; color:#fff; border-color:#d9534f; font-weight:700;">
                    <i class="fas fa-trash"></i> ลบข้อมูลการยืนยันตัวตน
                </button>
            </form>
        </div>
    @endif
</div>

{{-- ===== โมดัลปฏิเสธ (JS สลับคลาส hidden/flex เหมือนเดิม) ===== --}}
<div id="rejectModal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.55);"
     onclick="if(event.target === this) closeRejectModal()">
    <div class="tp-card" style="max-width:460px; width:100%; padding:22px;" onclick="event.stopPropagation()">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
            <div class="tp-section-h" style="margin:0;"><i class="fas fa-circle-xmark"></i> ปฏิเสธการยืนยันตัวตน</div>
            <button type="button" onclick="closeRejectModal()" class="tp-icon-btn" title="ปิด">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.kyc.reject', $kycVerification) }}" method="POST">
            @csrf
            <div style="margin-bottom:16px;">
                <label for="rejection_reason" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    เหตุผลในการปฏิเสธ <span style="color:#d9534f;">*</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required
                              placeholder="กรุณาระบุเหตุผลในการปฏิเสธ..."
                              style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
                </div>
                @error('rejection_reason')
                    <p style="font-size:12px; color:#d9534f; margin:6px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeRejectModal()" class="tp-btn" style="flex:1; justify-content:center;">
                    ยกเลิก
                </button>
                <button type="submit" class="tp-btn"
                        style="flex:1; justify-content:center; background:#d9534f; color:#fff; border-color:#d9534f; font-weight:700;">
                    ปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== โมดัลดูภาพเต็ม ===== --}}
<div id="imageModal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.9);"
     onclick="closeImageModal()">
    <div style="position:relative; max-width:1150px; max-height:100%;">
        <button type="button" onclick="closeImageModal()"
                style="position:absolute; top:14px; right:14px; z-index:10; width:40px; height:40px; border-radius:50%; border:none; cursor:pointer; background:rgba(0,0,0,.5); color:#fff; font-size:18px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="Full Size" style="max-width:100%; max-height:100vh; border-radius:12px; display:block;">
    </div>
</div>

@push('scripts')
<script>
function openRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    const modal = document.getElementById('imageModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush
@endsection
