{{-- รายละเอียดผลทำนายฝัน — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก จัดเรียงแนวตั้งเว้นระยะ --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ส่วนหัว: eyebrow + ชื่อเรื่อง ด้านซ้าย / ปุ่มกลับ ด้านขวา --}}
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · ทำนายฝัน
            </div>
            <h1 class="tp-num" style="font-size:1.5rem; font-weight:800; margin:0; color:var(--ink); display:flex; align-items:center; gap:10px;">
                <i class="fas fa-moon" style="color:var(--accent1);"></i>
                ผลทำนายฝัน #{{ $reading->id }}
            </h1>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            {{-- ปุ่มกลับไปรายการผลทำนาย (ลิงก์เดิม) --}}
            <a href="{{ route('admin.fortune.horoscope-public.dream.readings') }}" class="tp-btn">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- เลย์เอาต์ 2 คอลัมน์: เนื้อหาหลัก (กว้าง) + เมตาดาต้า (แคบ) --}}
    <div class="tp-dream-grid">

        {{-- ============ คอลัมน์ซ้าย: ข้อมูลหลัก ============ --}}
        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- เรื่องฝันที่เล่า --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <i class="fas fa-comment-dots" style="color:var(--accent1);"></i>
                    <span>เรื่องฝันที่เล่า</span>
                </div>
                <div class="tp-inset" style="padding:16px; white-space:pre-line; line-height:1.7; font-size:.9rem; color:var(--ink);">{{ $reading->dream_description }}</div>
            </div>

            {{-- ผลทำนาย AI (แสดงเฉพาะเมื่อมีข้อมูล) --}}
            @if($reading->ai_interpretation)
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <i class="fas fa-robot" style="color:#b79ae8;"></i>
                    <span>ผลทำนาย AI</span>
                </div>
                {{-- กล่องผล AI ใช้โทนม่วงอ่อนล้อม border --}}
                <div class="tp-inset" style="padding:16px; white-space:pre-line; line-height:1.75; font-size:.9rem; color:var(--ink); border-left:3px solid #b79ae8;">{{ $reading->ai_interpretation }}</div>
            </div>
            @endif

            {{-- สัญลักษณ์ที่ตรงกัน (แสดงเฉพาะเมื่อมีอย่างน้อย 1 รายการ) --}}
            @if($matchedSymbols->isNotEmpty())
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                    <i class="fas fa-key" style="color:var(--accent2);"></i>
                    <span>สัญลักษณ์ที่ตรงกัน</span>
                    <span class="tp-pill tp-pill-soft" style="margin-left:auto;">{{ $matchedSymbols->count() }} รายการ</span>
                </div>
                <div class="tp-symbol-grid">
                    @foreach($matchedSymbols as $symbol)
                        {{-- การ์ดสัญลักษณ์ — สีขอบซ้ายบอกฤกษ์ดี (เขียว) / เตือน (เหลือง) --}}
                        <div class="tp-tile" style="padding:14px; border-left:4px solid {{ $symbol->is_good_omen ? '#5aa07e' : '#e0a52e' }};">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                <span style="font-size:1.05rem;">{{ $symbol->category?->icon ?? '💭' }}</span>
                                <span style="font-weight:700; font-size:.9rem; color:var(--ink);">{{ $symbol->keyword_th }}</span>
                                @if($symbol->is_good_omen)
                                    <i class="fas fa-circle-check" style="color:#5aa07e; font-size:.8rem; margin-left:auto;"></i>
                                @else
                                    <i class="fas fa-triangle-exclamation" style="color:#e0a52e; font-size:.8rem; margin-left:auto;"></i>
                                @endif
                            </div>
                            <p class="tp-muted" style="font-size:.78rem; line-height:1.5; margin:0 0 6px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $symbol->meaning_th }}</p>
                            @if(!empty($symbol->lucky_numbers))
                                <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:4px;">
                                    @foreach($symbol->lucky_numbers as $num)
                                        <span class="tp-pill tp-pill-gold" style="font-family:monospace; font-weight:700; font-size:.72rem; padding:2px 8px;">{{ $num }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ============ คอลัมน์ขวา: เมตาดาต้า ============ --}}
        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- เลขเด็ด (แสดงเฉพาะเมื่อมีข้อมูล) --}}
            @if(!empty($reading->lucky_numbers))
            <div class="tp-card" style="text-align:center; background:linear-gradient(135deg, var(--a2soft), var(--a1soft));">
                <div class="tp-section-h" style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:14px;">
                    <i class="fas fa-bullseye" style="color:var(--accent2);"></i>
                    <span>เลขเด็ด</span>
                </div>
                <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:12px;">
                    @foreach($reading->lucky_numbers as $num)
                        {{-- ลูกเล่นเลขเด็ดแบบลูกบอลทอง --}}
                        <span class="tp-num" style="width:52px; height:52px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; border-radius:14px; font-family:monospace; font-weight:900; font-size:1.4rem; box-shadow:var(--raise);">{{ $num }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ข้อมูลรายละเอียด --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                    <i class="fas fa-circle-info" style="color:var(--accent1);"></i>
                    <span>ข้อมูล</span>
                </div>
                <dl style="margin:0; display:flex; flex-direction:column; gap:0;">

                    {{-- ID --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">ID</dt>
                        <dd class="tp-num" style="font-family:monospace; color:var(--ink); margin:0;">#{{ $reading->id }}</dd>
                    </div>

                    {{-- Session --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">Session</dt>
                        <dd style="font-family:monospace; font-size:.78rem; color:var(--ink); margin:0;">{{ Str::limit($reading->session_id, 15) }}</dd>
                    </div>

                    {{-- ผู้ใช้ --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">ผู้ใช้</dt>
                        <dd style="color:var(--ink); margin:0;">{{ $reading->user?->name ?? 'Guest' }}</dd>
                    </div>

                    {{-- AI Provider --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">AI Provider</dt>
                        <dd style="color:var(--ink); margin:0;">{{ $reading->ai_provider_used ?? '—' }}</dd>
                    </div>

                    {{-- ฟรี/เครดิต --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">ฟรี/เครดิต</dt>
                        <dd style="margin:0;">
                            @if($reading->is_free)
                                <span class="tp-pill" style="color:#5aa07e;"><i class="fas fa-gift"></i> ฟรี</span>
                            @else
                                <span class="tp-pill" style="color:#5689b8;"><i class="fas fa-gem"></i> เครดิต</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Views --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">Views</dt>
                        <dd class="tp-num" style="color:var(--ink); margin:0;">{{ number_format($reading->view_count) }}</dd>
                    </div>

                    {{-- IP --}}
                    <div class="tp-meta-row">
                        <dt class="tp-muted">IP</dt>
                        <dd style="font-family:monospace; font-size:.78rem; color:var(--ink); margin:0;">{{ $reading->ip_address ?? '—' }}</dd>
                    </div>

                    {{-- สร้างเมื่อ --}}
                    <div class="tp-meta-row" style="border-bottom:none;">
                        <dt class="tp-muted">สร้างเมื่อ</dt>
                        <dd style="color:var(--ink); margin:0;">{{ $reading->created_at->locale('th')->translatedFormat('j M Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- ลิงก์ Frontend (ลิงก์เดิม เปิดแท็บใหม่) --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                    <i class="fas fa-link" style="color:var(--accent1);"></i>
                    <span>ลิงก์ Frontend</span>
                </div>
                <a href="{{ route('horoscope.dream.result', $reading->id) }}" target="_blank"
                   class="tp-well" style="display:block; padding:12px; font-size:.82rem; color:var(--accent1); word-break:break-all; text-decoration:none;">
                    {{ route('horoscope.dream.result', $reading->id) }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- สไตล์เฉพาะหน้านี้ ฝังใน scripts stack เพื่อเลี่ยงปัญหา styles stack หลุด head --}}
<style>
    /* เลย์เอาต์ 2 คอลัมน์: เนื้อหาหลัก 2 ส่วน + เมตาดาต้า 1 ส่วน */
    .tp-dream-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 18px;
        align-items: start;
    }
    /* กริดการ์ดสัญลักษณ์ — ปรับขนาดอัตโนมัติ */
    .tp-symbol-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }
    /* แถวข้อมูลเมตา — เส้นคั่นบางด้วย inset เงา */
    .tp-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 2px;
        font-size: .85rem;
        border-bottom: 1px solid var(--sd);
    }
    .tp-meta-row dt { margin: 0; }
    /* จอแคบ: ยุบเป็นคอลัมน์เดียว */
    @media (max-width: 900px) {
        .tp-dream-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush
