@extends('layouts.admin-v4')

@section('title', 'จัดการ SEO')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: จัดการ SEO (ธีม V4 "นวลทองคำ")
     ── รายการ Meta Tags ต่อหน้า + สถิติสรุป + ทางลัดไปตั้งค่า/วิเคราะห์
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Header: eyebrow + h1 + ปุ่มการทำงาน ── --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">
                หลังบ้าน · การตลาด · SEO
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-magnifying-glass-chart" style="color:var(--accent1);"></i> จัดการ SEO
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px; max-width:640px; line-height:1.55;">
                ตั้งค่า Meta Tags, Open Graph, Twitter Card, Structured Data และ robots ของแต่ละหน้า —
                รองรับ Google AI Overviews / Gemini
            </p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <a href="{{ route('admin.seo.analysis') }}" class="tp-btn">
                <i class="fas fa-chart-pie"></i> วิเคราะห์
            </a>
            <a href="{{ route('admin.seo.settings') }}" class="tp-btn">
                <i class="fas fa-sliders"></i> ตั้งค่าทั่วเว็บ
            </a>
            <a href="{{ route('admin.seo.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i> เพิ่ม SEO Meta
            </a>
        </div>
    </div>

    {{-- ── KPI สรุป ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        {{-- ทั้งหมด --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:19px; flex:0 0 auto;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total']) }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:3px;">หน้าที่ตั้งค่าไว้ทั้งหมด</div>
            </div>
        </div>
        {{-- Indexed --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:19px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5aa07e; box-shadow:var(--raise);">
                <i class="fas fa-eye"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['indexed']) }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:3px;">อนุญาตให้ index</div>
            </div>
        </div>
        {{-- Noindex --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:19px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#c98a3c; box-shadow:var(--raise);">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['noindex']) }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:3px;">ซ่อนจาก search (noindex)</div>
            </div>
        </div>
        {{-- Structured Data --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:19px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5689b8; box-shadow:var(--raise);">
                <i class="fas fa-diagram-project"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['with_structured']) }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:3px;">มี Structured Data (JSON-LD)</div>
            </div>
        </div>
    </div>

    {{-- ── ตารางรายการ SEO Meta ── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 18px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700;">
                <i class="fas fa-table-list" style="color:var(--accent1);"></i> รายการ Meta Tags ต่อหน้า
            </div>
            <span class="tp-muted" style="font-size:12px;">ทั้งหมด {{ number_format($stats['total']) }} รายการ</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:11px 18px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ประเภทหน้า</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ภาษา</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Meta Title</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Robots</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">JSON-LD</th>
                        <th style="padding:11px 18px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($seoMetas as $seo)
                        <tr style="box-shadow:var(--inset-sm);">
                            <td style="padding:12px 18px; font-size:13px; font-weight:700; color:var(--ink);">
                                {{ $seo->page_type }}
                            </td>
                            <td style="padding:12px 16px;">
                                <span class="tp-pill tp-pill-soft" style="font-size:11px; padding:2px 9px; text-transform:uppercase;">{{ $seo->language }}</span>
                            </td>
                            <td style="padding:12px 16px; font-size:13px; color:var(--ink2); max-width:360px;">
                                {{ Str::limit($seo->meta_title, 64) ?: '—' }}
                            </td>
                            <td style="padding:12px 16px;">
                                <span class="tp-pill" style="font-size:11px; padding:2px 9px; {{ $seo->index ? 'background:rgba(90,160,126,.16); color:#4f9e7e;' : 'background:rgba(217,83,79,.14); color:#d9534f;' }}">
                                    {{ $seo->robots }}
                                </span>
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if(filled($seo->structured_data))
                                    <i class="fas fa-circle-check" style="color:#5aa07e;" title="มี Structured Data"></i>
                                @else
                                    <i class="fas fa-minus" style="color:var(--ink2); opacity:.5;" title="ยังไม่มี"></i>
                                @endif
                            </td>
                            <td style="padding:12px 18px; text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.seo.edit', $seo) }}" class="tp-icon-btn" title="แก้ไข" style="margin-left:auto;">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.seo.destroy', $seo) }}" method="POST" style="display:inline-block; margin-left:6px;"
                                      onsubmit="return confirm('ยืนยันการลบ SEO Meta ของหน้า {{ $seo->page_type }} ({{ $seo->language }})?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tp-icon-btn" title="ลบ" style="color:#d9534f;">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:48px 18px; text-align:center;">
                                <div style="color:var(--ink2); font-size:14px;">
                                    <i class="fas fa-inbox" style="font-size:26px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    ยังไม่มีข้อมูล SEO Meta
                                </div>
                                <a href="{{ route('admin.seo.create') }}" class="tp-btn tp-btn-primary" style="margin-top:14px;">
                                    <i class="fas fa-plus"></i> เพิ่ม SEO Meta แรก
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($seoMetas->hasPages())
            <div style="padding:14px 18px; box-shadow:var(--inset-sm);">
                {{ $seoMetas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
