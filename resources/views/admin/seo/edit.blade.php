@extends('layouts.admin-v4')

@section('title', 'แก้ไข SEO Meta')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: แก้ไข SEO Meta (ธีม V4 "นวลทองคำ")
     ── page_type/language แก้ไม่ได้ (แสดงเป็นข้อมูล) — แก้ได้เฉพาะเนื้อหา meta
     ════════════════════════════════════════════════════════════ --}}
<div x-data="seoForm({ title: @js(old('meta_title', $seo->meta_title)), desc: @js(old('meta_description', $seo->meta_description)) })"
     style="max-width:820px; margin:0 auto; display:flex; flex-direction:column; gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex; align-items:center; gap:14px;">
        <a href="{{ route('admin.seo.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1; min-width:0;">
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">
                หลังบ้าน · SEO · แก้ไข
            </div>
            <h1 class="tp-num" style="font-size:1.6rem; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                {{ $seo->page_type }}
                <span class="tp-pill tp-pill-soft" style="font-size:12px; padding:2px 10px; text-transform:uppercase;">{{ $seo->language }}</span>
            </h1>
        </div>
    </div>

    @if($errors->any())
        <div class="tp-card" style="border-left:3px solid #d9534f;">
            <div style="font-weight:700; color:#d9534f; font-size:13px; margin-bottom:6px;"><i class="fas fa-triangle-exclamation"></i> ตรวจสอบข้อมูลอีกครั้ง</div>
            <ul class="tp-muted" style="font-size:12.5px; margin:0; padding-left:18px; line-height:1.7;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.seo.update', $seo) }}" method="POST" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ═══ Meta Tags พื้นฐาน ═══ --}}
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
                    <input type="text" name="meta_title" x-model="title" value="{{ old('meta_title', $seo->meta_title) }}"
                           placeholder="หัวข้อหน้า (แนะนำ 50-60 ตัวอักษร)" class="tp-input" style="width:100%; margin-top:6px;">
                </div>
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <label class="tp-muted" style="font-weight:700; font-size:.82rem; color:var(--ink);">Meta Description</label>
                        <span class="tp-muted" style="font-size:11px;" :style="descColor"><span x-text="desc.length"></span>/160</span>
                    </div>
                    <textarea name="meta_description" x-model="desc" rows="3"
                              placeholder="คำอธิบายหน้า (แนะนำ 150-160 ตัวอักษร)" class="tp-input" style="width:100%; margin-top:6px; resize:vertical;">{{ old('meta_description', $seo->meta_description) }}</textarea>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Meta Keywords</label>
                    <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $seo->meta_keywords) }}"
                           placeholder="คำสำคัญ คั่นด้วยจุลภาค" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Canonical URL</label>
                    <input type="url" name="canonical_url" value="{{ old('canonical_url', $seo->canonical_url) }}"
                           placeholder="https://main.thaiprompt.online/..." class="tp-input" style="width:100%; font-family:monospace; font-size:.82rem;">
                </div>
            </div>
        </div>

        {{-- ═══ Open Graph ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fab fa-facebook" style="color:var(--accent1);"></i> Open Graph (Facebook / LINE)
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Title</label>
                    <input type="text" name="og_title" value="{{ old('og_title', $seo->og_title) }}" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Description</label>
                    <textarea name="og_description" rows="2" class="tp-input" style="width:100%; resize:vertical;">{{ old('og_description', $seo->og_description) }}</textarea>
                </div>
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:16px;">
                    <div>
                        <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Image URL</label>
                        <input type="url" name="og_image" value="{{ old('og_image', $seo->og_image) }}" placeholder="https://.../image.jpg" class="tp-input" style="width:100%; font-family:monospace; font-size:.8rem;">
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">OG Type</label>
                        <input type="text" name="og_type" value="{{ old('og_type', $seo->og_type) }}" class="tp-input" style="width:100%;">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Twitter Card ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fab fa-x-twitter" style="color:var(--accent1);"></i> Twitter Card
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="max-width:280px;">
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Card Type</label>
                    <select name="twitter_card" class="tp-input" style="width:100%;">
                        <option value="summary_large_image" @selected(old('twitter_card', $seo->twitter_card)=='summary_large_image')>Summary Large Image</option>
                        <option value="summary" @selected(old('twitter_card', $seo->twitter_card)=='summary')>Summary</option>
                    </select>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Title</label>
                    <input type="text" name="twitter_title" value="{{ old('twitter_title', $seo->twitter_title) }}" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Description</label>
                    <textarea name="twitter_description" rows="2" class="tp-input" style="width:100%; resize:vertical;">{{ old('twitter_description', $seo->twitter_description) }}</textarea>
                </div>
                <div>
                    <label class="tp-muted" style="display:block; font-weight:700; font-size:.82rem; margin-bottom:6px; color:var(--ink);">Twitter Image URL</label>
                    <input type="url" name="twitter_image" value="{{ old('twitter_image', $seo->twitter_image) }}" class="tp-input" style="width:100%; font-family:monospace; font-size:.8rem;">
                </div>
            </div>
        </div>

        {{-- ═══ Structured Data ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:6px;">
                <i class="fas fa-diagram-project" style="color:var(--accent1);"></i> Structured Data (Schema.org JSON-LD)
            </div>
            <p class="tp-muted" style="margin:0 0 12px; font-size:12.5px; line-height:1.6;">
                ข้อมูลโครงสร้างเฉพาะหน้านี้ (JSON) เช่น FAQPage, Article, Product — ช่วยให้ Google AI / Gemini เข้าใจเนื้อหา (เว้นว่างได้)
            </p>
            <textarea name="structured_data" rows="8" class="tp-input" spellcheck="false"
                      style="width:100%; font-family:ui-monospace,monospace; font-size:12.5px; resize:vertical;"
                      placeholder="วางโค้ด JSON-LD ที่นี่ (เช่น FAQPage, Article, Product) — เว้นว่างได้">{{ old('structured_data', $seo->structured_data ? json_encode($seo->structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
        </div>

        {{-- ═══ Robots ═══ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:16px;">
                <i class="fas fa-robot" style="color:var(--accent1);"></i> การตั้งค่า Robots
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                <label class="tp-well" style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:14px; cursor:pointer;">
                    <input type="checkbox" name="index" value="1" @checked(old('index', $seo->index))
                           style="accent-color:var(--accent1); width:18px; height:18px; flex:0 0 auto;">
                    <span>
                        <span style="display:block; font-weight:700; font-size:13px; color:var(--ink);">Index</span>
                        <span class="tp-muted" style="font-size:11.5px;">อนุญาตให้ search engine จัดทำดัชนี</span>
                    </span>
                </label>
                <label class="tp-well" style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:14px; cursor:pointer;">
                    <input type="checkbox" name="follow" value="1" @checked(old('follow', $seo->follow))
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
                <i class="fas fa-floppy-disk"></i> บันทึกการแก้ไข
            </button>
            <a href="{{ route('admin.seo.index') }}" class="tp-btn"><i class="fas fa-xmark"></i> ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
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
