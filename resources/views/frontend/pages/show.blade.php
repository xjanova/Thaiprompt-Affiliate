@extends('layouts.frontend-v4')

@section('title', $page->title . ' · ไทยพร๊อมท์')
@section('meta_description', $page->meta_data['description'] ?? ($page->title . ' — ไทยพร๊อมท์ ThaiPrompt'))

@push('styles')
<style>
    .legal-doc { color: var(--ink); line-height: 1.85; font-size: 15px; }
    .legal-doc h1 { font-size: 1.6rem; font-weight: 800; color: var(--ink); margin: 0 0 14px; }
    .legal-doc h2 { font-size: 1.22rem; font-weight: 700; color: var(--ink); margin: 28px 0 12px; }
    .legal-doc h3 { font-size: 1.04rem; font-weight: 700; color: var(--deep1); margin: 18px 0 8px; }
    .legal-doc p { margin: 0 0 12px; color: var(--ink2); }
    .legal-doc ul, .legal-doc ol { margin: 0 0 14px; padding-left: 22px; color: var(--ink2); }
    .legal-doc li { margin: 6px 0; }
    .legal-doc a { color: var(--deep1); text-decoration: none; }
    .legal-doc a:hover { text-decoration: underline; }
    .legal-doc strong, .legal-doc b { color: var(--ink); }
    .legal-doc table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    .legal-doc th, .legal-doc td { border: 1px solid color-mix(in srgb, var(--ink2) 25%, transparent); padding: 8px 11px; text-align: left; font-size: 13.5px; }
    .legal-doc th { background: var(--surf); font-weight: 700; color: var(--ink); }
    .legal-doc img { max-width: 100%; height: auto; border-radius: 12px; }
</style>
@endpush

@section('content')
<div style="max-width:920px; margin:0 auto; padding:40px clamp(16px,3vw,40px) 60px;">
    {{-- หัวเรื่อง V4 บนแถบลายกนกทอง (เจนเอง เก็บที่ public/images/art) --}}
    <div style="position:relative; overflow:hidden; border-radius:22px; text-align:center; margin-bottom:28px; padding:clamp(26px,4vw,42px) 20px;">
        <x-art.backdrop image="pattern-kanok" tone="light" :opacity="0.75" mask="fade-bottom" />
        <div style="position:relative;">
            <h1 style="font-size:clamp(26px,4vw,34px); font-weight:800; color:var(--ink); margin:0;">{{ $page->title }}</h1>
            @if($page->updated_at)
                <p class="tp-muted" style="margin:8px 0 0; font-size:13px;">อัปเดตล่าสุด: {{ $page->updated_at->format('d F Y') }}</p>
            @endif
        </div>
    </div>

    {{-- เนื้อหาหน้า (จาก CMS) ห่อด้วยกรอบ V4 --}}
    <div class="tp-card legal-doc" style="padding:clamp(20px,4vw,40px);">
        {!! strip_tags($page->content, '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
    </div>

    <div style="text-align:center; margin-top:28px;">
        <a href="{{ url('/') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> <span>กลับหน้าแรก</span></a>
    </div>
</div>
@endsection
