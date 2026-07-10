@extends('layouts.admin-v4')

@section('title', 'ตั้งค่า SEO ทั่วเว็บไซต์')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: ตั้งค่า SEO ทั่วเว็บไซต์ (ธีม V4 "นวลทองคำ")
     ── ค่า default ที่ SeoService ใช้ + ป้อน Structured Data (Organization/WebSite)
     ════════════════════════════════════════════════════════════ --}}
<div style="max-width:820px; margin:0 auto; display:flex; flex-direction:column; gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex; align-items:center; gap:14px;">
        <a href="{{ route('admin.seo.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">
                หลังบ้าน · SEO · ตั้งค่าทั่วเว็บไซต์
            </div>
            <h1 class="tp-num" style="font-size:1.6rem; font-weight:800; color:var(--ink); margin:0;">ตั้งค่า SEO ทั่วเว็บไซต์</h1>
        </div>
    </div>

    {{-- คำอธิบาย --}}
    <div class="tp-card" style="display:flex; gap:12px; align-items:flex-start;">
        <i class="fas fa-circle-info" style="color:var(--accent1); font-size:18px; margin-top:2px;"></i>
        <p class="tp-muted" style="margin:0; font-size:12.5px; line-height:1.7;">
            ค่าเหล่านี้ใช้เป็น <strong style="color:var(--ink);">ค่าเริ่มต้น</strong> เมื่อหน้าใดไม่ได้ตั้ง Meta เฉพาะ
            และถูกใช้ป้อน <strong style="color:var(--ink);">Structured Data (Organization + WebSite)</strong>
            ที่ฝังในทุกหน้าสาธารณะ — เป็นสัญญาณสำคัญให้ Google AI Overviews / Gemini เข้าใจแบรนด์
        </p>
    </div>

    @if($errors->any())
        <div class="tp-card" style="border-left:3px solid #d9534f;">
            <ul class="tp-muted" style="font-size:12.5px; margin:0; padding-left:18px; line-height:1.7;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.seo.settings.update') }}" method="POST" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ═══ ข้อมูลแบรนด์ (Organization schema) ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fas fa-building" style="color:var(--accent1);"></i> ข้อมูลแบรนด์ (Organization / WebSite)
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">ชื่อเว็บไซต์ / แบรนด์</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}"
                           placeholder="{{ config('app.name') }}" class="tp-input" style="width:100%;">
                    <p class="tp-muted" style="margin:6px 0 0; font-size:.74rem;">ใช้ใน &lt;title&gt;, og:site_name และชื่อ Organization ใน JSON-LD</p>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">คำอธิบายเว็บไซต์ (Default Description)</label>
                    <textarea name="site_description" rows="3" class="tp-input" style="width:100%; resize:vertical;"
                              placeholder="ระบบ Affiliate Marketing MLM อันดับ 1 ของไทย...">{{ old('site_description', $settings['site_description']) }}</textarea>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">อีเมลติดต่อ (contactPoint)</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}"
                           placeholder="support@thaiprompt.online" class="tp-input" style="width:100%;">
                    <p class="tp-muted" style="margin:6px 0 0; font-size:.74rem;">แสดงใน Organization → contactPoint (customer service)</p>
                </div>
            </div>
        </div>

        {{-- ═══ รูปภาพสำหรับแชร์โซเชียล ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fas fa-image" style="color:var(--accent1);"></i> รูปภาพเริ่มต้นสำหรับแชร์
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Image เริ่มต้น (Facebook / LINE)</label>
                    <input type="text" name="og_default_image" value="{{ old('og_default_image', $settings['og_default_image']) }}"
                           placeholder="images/og-default.jpg หรือ URL เต็ม" class="tp-input" style="width:100%; font-family:monospace; font-size:.8rem;">
                    <p class="tp-muted" style="margin:6px 0 0; font-size:.74rem;">แนะนำ 1200×630px — ใช้เมื่อหน้าไม่ได้กำหนด og:image เอง</p>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Image เริ่มต้น</label>
                    <input type="text" name="twitter_default_image" value="{{ old('twitter_default_image', $settings['twitter_default_image']) }}"
                           placeholder="images/twitter-default.jpg หรือ URL เต็ม" class="tp-input" style="width:100%; font-family:monospace; font-size:.8rem;">
                </div>
            </div>
        </div>

        {{-- ═══ สถานะระบบ SEO (read-only) ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:14px;">
                <i class="fas fa-heart-pulse" style="color:var(--accent1);"></i> สถานะระบบ (อ่านอย่างเดียว)
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
                <div class="tp-well" style="padding:12px 14px; border-radius:12px;">
                    <div class="tp-muted" style="font-size:11.5px;">robots.txt</div>
                    <div style="font-weight:700; font-size:13px; color:var(--ink); margin-top:3px;">
                        <i class="fas fa-circle-check" style="color:#5aa07e;"></i> Dynamic (เปิด AI ทุกเจ้า)
                    </div>
                    <a href="{{ url('/robots.txt') }}" target="_blank" class="tp-muted" style="font-size:11px; text-decoration:underline;">เปิดดู →</a>
                </div>
                <div class="tp-well" style="padding:12px 14px; border-radius:12px;">
                    <div class="tp-muted" style="font-size:11.5px;">sitemap.xml</div>
                    <div style="font-weight:700; font-size:13px; color:var(--ink); margin-top:3px;">
                        <i class="fas fa-circle-check" style="color:#5aa07e;"></i> เปิดใช้งาน
                    </div>
                    <a href="{{ url('/sitemap.xml') }}" target="_blank" class="tp-muted" style="font-size:11px; text-decoration:underline;">เปิดดู →</a>
                </div>
                <div class="tp-well" style="padding:12px 14px; border-radius:12px;">
                    <div class="tp-muted" style="font-size:11.5px;">Structured Data</div>
                    <div style="font-weight:700; font-size:13px; color:var(--ink); margin-top:3px;">
                        <i class="fas fa-circle-check" style="color:#5aa07e;"></i> Organization + WebSite
                    </div>
                    <span class="tp-muted" style="font-size:11px;">ฝังทุกหน้าสาธารณะ</span>
                </div>
            </div>
        </div>

        {{-- ── ปุ่ม ── --}}
        <div style="display:flex; align-items:center; gap:12px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center;">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
            <a href="{{ route('admin.seo.index') }}" class="tp-btn"><i class="fas fa-xmark"></i> กลับ</a>
        </div>
    </form>
</div>
@endsection
