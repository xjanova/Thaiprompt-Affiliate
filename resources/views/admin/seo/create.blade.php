@extends('layouts.admin-v4')

@section('title', 'เพิ่ม SEO Meta')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: เพิ่ม SEO Meta (ธีม V4 "นวลทองคำ")
     ── ฟอร์มแบบแบ่งส่วน + ตัวนับตัวอักษร + แนะนำความยาวที่เหมาะสม
     ════════════════════════════════════════════════════════════ --}}
<div x-data="seoForm({ title: @js(old('meta_title', '')), desc: @js(old('meta_description', '')) })"
     style="max-width:820px; margin:0 auto; display:flex; flex-direction:column; gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex; align-items:center; gap:14px;">
        <a href="{{ route('admin.seo.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">
                หลังบ้าน · SEO · เพิ่มใหม่
            </div>
            <h1 class="tp-num" style="font-size:1.6rem; font-weight:800; color:var(--ink); margin:0;">เพิ่ม SEO Meta</h1>
        </div>
    </div>

    {{-- แจ้ง error รวม --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:3px solid #d9534f;">
            <div style="font-weight:700; color:#d9534f; font-size:13px; margin-bottom:6px;"><i class="fas fa-triangle-exclamation"></i> ตรวจสอบข้อมูลอีกครั้ง</div>
            <ul class="tp-muted" style="font-size:12.5px; margin:0; padding-left:18px; line-height:1.7;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.seo.store') }}" method="POST" style="display:flex; flex-direction:column; gap:18px;">
        @csrf

        {{-- ═══ ส่วนที่ 1: ระบุหน้า ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fas fa-file-lines" style="color:var(--accent1);"></i> ระบุหน้า
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">ประเภทหน้า <span style="color:#d46a6a;">*</span></label>
                    <select name="page_type" required class="tp-input" style="width:100%;">
                        <option value="">— เลือกประเภทหน้า —</option>
                        @foreach($pageTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('page_type') == $key)>{{ $label }} ({{ $key }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">ภาษา <span style="color:#d46a6a;">*</span></label>
                    <select name="language" required class="tp-input" style="width:100%;">
                        @foreach($languages as $key => $label)
                            <option value="{{ $key }}" @selected(old('language') == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ═══ ส่วนที่ 2: Meta Tags พื้นฐาน ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fas fa-tag" style="color:var(--accent1);"></i> Meta Tags พื้นฐาน
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <label class="tp-muted" style="font-weight:700; font-size:.82rem; color:var(--ink);">Meta Title</label>
                        <span class="tp-muted" style="font-size:11px;" :style="titleColor"><span x-text="title.length"></span>/60</span>
                    </div>
                    <input type="text" name="meta_title" x-model="title" value="{{ old('meta_title') }}"
                           placeholder="หัวข้อหน้า (แนะนำ 50-60 ตัวอักษร)" class="tp-input" style="width:100%; margin-top:6px;">
                </div>
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <label class="tp-muted" style="font-weight:700; font-size:.82rem; color:var(--ink);">Meta Description</label>
                        <span class="tp-muted" style="font-size:11px;" :style="descColor"><span x-text="desc.length"></span>/160</span>
                    </div>
                    <textarea name="meta_description" x-model="desc" rows="3"
                              placeholder="คำอธิบายหน้า (แนะนำ 150-160 ตัวอักษร)" class="tp-input" style="width:100%; margin-top:6px; resize:vertical;">{{ old('meta_description') }}</textarea>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Meta Keywords</label>
                    <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}"
                           placeholder="คำสำคัญ คั่นด้วยจุลภาค เช่น ดูดวง, ไพ่ทาโรต์" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Canonical URL</label>
                    <input type="url" name="canonical_url" value="{{ old('canonical_url') }}"
                           placeholder="https://main.thaiprompt.online/..." class="tp-input" style="width:100%; font-family:monospace; font-size:.82rem;">
                </div>
            </div>
        </div>

        {{-- ═══ ส่วนที่ 3: Open Graph (Facebook/LINE) ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fab fa-facebook" style="color:var(--accent1);"></i> Open Graph (Facebook / LINE)
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Title</label>
                    <input type="text" name="og_title" value="{{ old('og_title') }}" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Description</label>
                    <textarea name="og_description" rows="2" class="tp-input" style="width:100%; resize:vertical;">{{ old('og_description') }}</textarea>
                </div>
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:16px;">
                    <div>
                        <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Image URL</label>
                        <input type="url" name="og_image" value="{{ old('og_image') }}" placeholder="https://.../image.jpg" class="tp-input" style="width:100%; font-family:monospace; font-size:.8rem;">
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Type</label>
                        <input type="text" name="og_type" value="{{ old('og_type', 'website') }}" class="tp-input" style="width:100%;">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ ส่วนที่ 4: Twitter Card ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fab fa-x-twitter" style="color:var(--accent1);"></i> Twitter Card
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="max-width:280px;">
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Card Type</label>
                    <select name="twitter_card" class="tp-input" style="width:100%;">
                        <option value="summary_large_image" @selected(old('twitter_card')=='summary_large_image')>Summary Large Image</option>
                        <option value="summary" @selected(old('twitter_card')=='summary')>Summary</option>
                    </select>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Title</label>
                    <input type="text" name="twitter_title" value="{{ old('twitter_title') }}" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Description</label>
                    <textarea name="twitter_description" rows="2" class="tp-input" style="width:100%; resize:vertical;">{{ old('twitter_description') }}</textarea>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Image URL</label>
                    <input type="url" name="twitter_image" value="{{ old('twitter_image') }}" class="tp-input" style="width:100%; font-family:monospace; font-size:.8rem;">
                </div>
            </div>
        </div>

        {{-- ═══ ส่วนที่ 5: Structured Data (JSON-LD) ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:6px;">
                <i class="fas fa-diagram-project" style="color:var(--accent1);"></i> Structured Data (Schema.org JSON-LD)
            </div>
            <p class="tp-muted" style="margin:0 0 12px; font-size:12.5px; line-height:1.6;">
                ข้อมูลโครงสร้างเฉพาะหน้านี้ (JSON) เช่น FAQPage, Article, Product, Service —
                ช่วยให้ Google AI / Gemini เข้าใจเนื้อหา และมีโอกาสขึ้น Rich Results / AI Overviews (เว้นว่างได้)
            </p>
            <textarea name="structured_data" rows="8" class="tp-input" spellcheck="false"
                      style="width:100%; font-family:ui-monospace,monospace; font-size:12.5px; resize:vertical;"
                      placeholder="วางโค้ด JSON-LD ที่นี่ (เช่น FAQPage, Article, Product) — เว้นว่างได้">{{ old('structured_data') }}</textarea>
        </div>

        {{-- ═══ ส่วนที่ 6: Robots ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fas fa-robot" style="color:var(--accent1);"></i> การตั้งค่า Robots
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                <label class="tp-well" style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:14px; cursor:pointer;">
                    <input type="checkbox" name="index" value="1" @checked(old('index', true))
                           style="accent-color:var(--accent1); width:18px; height:18px; flex:0 0 auto;">
                    <span>
                        <span style="display:block; font-weight:700; font-size:13px; color:var(--ink);">Index</span>
                        <span class="tp-muted" style="font-size:11.5px;">อนุญาตให้ search engine จัดทำดัชนี</span>
                    </span>
                </label>
                <label class="tp-well" style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:14px; cursor:pointer;">
                    <input type="checkbox" name="follow" value="1" @checked(old('follow', true))
                           style="accent-color:var(--accent1); width:18px; height:18px; flex:0 0 auto;">
                    <span>
                        <span style="display:block; font-weight:700; font-size:13px; color:var(--ink);">Follow</span>
                        <span class="tp-muted" style="font-size:11.5px;">อนุญาตให้ติดตามลิงก์ในหน้า</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- ── ปุ่ม ── --}}
        <div style="display:flex; align-items:center; gap:12px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center;">
                <i class="fas fa-floppy-disk"></i> บันทึก SEO Meta
            </button>
            <a href="{{ route('admin.seo.index') }}" class="tp-btn"><i class="fas fa-xmark"></i> ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // ตัวนับตัวอักษร + แนะนำสีตามความยาวที่เหมาะสม (title 50-60, desc 150-160)
    function seoForm(initial) {
        return {
            title: initial.title || '',
            desc: initial.desc || '',
            get titleColor() {
                const n = this.title.length;
                if (n === 0) return 'color:var(--ink2);';
                return (n >= 50 && n <= 60) ? 'color:#5aa07e;font-weight:700;' : (n > 60 ? 'color:#d9534f;font-weight:700;' : 'color:#c98a3c;');
            },
            get descColor() {
                const n = this.desc.length;
                if (n === 0) return 'color:var(--ink2);';
                return (n >= 150 && n <= 160) ? 'color:#5aa07e;font-weight:700;' : (n > 160 ? 'color:#d9534f;font-weight:700;' : 'color:#c98a3c;');
            },
        };
    }
</script>
@endpush
