@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'Lazada Hub')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — แดชบอร์ดภาพรวม (ธีม V4 "นวลทองคำ")
     สรุปการเชื่อมต่อ + สินค้า (affiliate/ขายเอง) + ออเดอร์ + ค่าคอม
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                หลังบ้าน · Lazada Hub
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-store" style="color:var(--accent1);"></i>
                ศูนย์เชื่อมต่อ Lazada
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">จัดการการเชื่อมต่อ · สินค้า · ราคา/กำไร · ออเดอร์ · ค่าคอมมิชชั่น แบบครบวงจร</p>
        </div>
        <a href="{{ route('admin.lazada-hub.connections.create') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-plug"></i>
            <span>เพิ่มการเชื่อมต่อ</span>
        </a>
    </div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="tp-card" style="border-left:4px solid #5aa07e;color:var(--ink);font-size:.88rem;">
            <i class="fas fa-circle-check" style="color:#5aa07e;"></i> {{ session('success') }}
        </div>
    @endif

    {{-- ── สถานะการเชื่อมต่อ ── --}}
    @if($stats['connections_total'] === 0)
        <div class="tp-card" style="border-left:4px solid #e0a52e;">
            <div style="font-weight:700;color:var(--ink);margin-bottom:6px;"><i class="fas fa-triangle-exclamation" style="color:#e0a52e;"></i> ยังไม่ได้เชื่อมต่อ Lazada</div>
            <p class="tp-muted" style="margin:0 0 10px;font-size:.86rem;line-height:1.7;">
                Lazada มี 2 โปรแกรมแยกกัน — เลือกเชื่อมต่อได้ทั้งคู่ ได้อันไหนมาก่อนกรอกอันนั้น:
            </p>
            <ul style="margin:0 0 12px;padding-left:20px;color:var(--ink2);font-size:.84rem;line-height:1.8;">
                <li><b>Seller (Open Platform)</b> — ดึงสินค้า/ราคา/สต็อก/ออเดอร์ของร้านตัวเอง (เหมาะกับโหมด <b>ขายเองบวกกำไร</b>)</li>
                <li><b>Affiliate (Involve Asia)</b> — สร้างลิงก์ได้ค่าคอม ~6.5% (เหมาะกับโหมด <b>affiliate</b>)</li>
            </ul>
            <p class="tp-muted" style="margin:0;font-size:.8rem;">* ระหว่างที่ยังไม่มี API ใช้ <a href="{{ route('admin.ecommerce.lazada-import.form') }}" style="color:var(--accent1);">นำเข้าด้วยการวางลิงก์ (scrape)</a> ไปก่อนได้</p>
        </div>
    @else
        <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;">
            <div style="display:flex;align-items:center;gap:8px;font-size:.88rem;color:var(--ink);">
                <i class="fas fa-plug" style="color:var(--accent1);"></i>
                <b>{{ $stats['connections_active'] }}</b>/{{ $stats['connections_total'] }} การเชื่อมต่อพร้อมใช้งาน
            </div>
            <span class="tp-pill" style="background:{{ $stats['has_seller'] ? '#5aa07e' : 'var(--ink2)' }};color:#fff;font-size:11px;">Seller {{ $stats['has_seller'] ? '✓' : '—' }}</span>
            <span class="tp-pill" style="background:{{ $stats['has_affiliate'] ? '#5aa07e' : 'var(--ink2)' }};color:#fff;font-size:11px;">Affiliate {{ $stats['has_affiliate'] ? '✓' : '—' }}</span>
            <a href="{{ route('admin.lazada-hub.connections.index') }}" class="tp-btn tp-btn-sm" style="margin-left:auto;">
                <i class="fas fa-gear"></i> <span>จัดการการเชื่อมต่อ</span>
            </a>
        </div>
    @endif

    {{-- ── การ์ดสถิติ ── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;">
        @php
            $cards = [
                ['label' => 'สินค้าทั้งหมด', 'value' => number_format($stats['products_total']), 'icon' => 'fa-box', 'color' => 'var(--accent1)'],
                ['label' => 'โหมด Affiliate', 'value' => number_format($stats['products_affiliate']), 'icon' => 'fa-link', 'color' => '#b79ae8'],
                ['label' => 'โหมดขายเอง', 'value' => number_format($stats['products_resell']), 'icon' => 'fa-tags', 'color' => '#5aa07e'],
                ['label' => 'เผยแพร่ขึ้นร้าน', 'value' => number_format($stats['products_published']), 'icon' => 'fa-store', 'color' => 'var(--accent2)'],
                ['label' => 'ออเดอร์', 'value' => number_format($stats['orders_total']), 'icon' => 'fa-shopping-cart', 'color' => '#e0a52e'],
                ['label' => 'รอคำนวณค่าคอม', 'value' => number_format($stats['orders_pending']), 'icon' => 'fa-hourglass-half', 'color' => '#d9849a'],
                ['label' => 'ค่าคอมรออนุมัติ (฿)', 'value' => number_format((float) $stats['commission_pending'], 2), 'icon' => 'fa-coins', 'color' => '#e0a52e'],
                ['label' => 'ค่าคอมจ่ายแล้ว (฿)', 'value' => number_format((float) $stats['commission_paid'], 2), 'icon' => 'fa-circle-check', 'color' => '#5aa07e'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="tp-card" style="display:flex;flex-direction:column;gap:6px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:.74rem;">{{ $c['label'] }}</span>
                    <i class="fas {{ $c['icon'] }}" style="color:{{ $c['color'] }};"></i>
                </div>
                <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ $c['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── ทางลัด ── --}}
    <div>
        <div class="tp-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">ทางลัด</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
            @php
                $tiles = [
                    ['label' => 'การเชื่อมต่อ', 'desc' => 'กรอก/ทดสอบ API', 'icon' => 'fa-plug', 'route' => route('admin.lazada-hub.connections.index'), 'ready' => true],
                    ['label' => 'นำเข้าสินค้า (วางลิงก์)', 'desc' => 'scrape PDP → ร้านเรา', 'icon' => 'fa-cloud-download-alt', 'route' => route('admin.ecommerce.lazada-import.form'), 'ready' => true],
                    ['label' => 'แคตตาล็อก + ราคา/กำไร', 'desc' => 'ต้นทุน·markup·ราคาเรา', 'icon' => 'fa-layer-group', 'route' => route('admin.lazada-hub.catalog.index'), 'ready' => true],
                    ['label' => 'ค่าคอมมิชชั่น → MLM', 'desc' => 'เร็วๆ นี้ (Phase 4)', 'icon' => 'fa-coins', 'route' => null, 'ready' => false],
                ];
            @endphp
            @foreach($tiles as $t)
                @if($t['ready'])
                    <a href="{{ $t['route'] }}" class="tp-card" style="text-decoration:none;display:flex;align-items:center;gap:12px;transition:.15s;">
                        <i class="fas {{ $t['icon'] }}" style="color:var(--accent1);font-size:1.3rem;width:28px;text-align:center;"></i>
                        <div>
                            <div style="font-weight:700;color:var(--ink);font-size:.9rem;">{{ $t['label'] }}</div>
                            <div class="tp-muted" style="font-size:.74rem;">{{ $t['desc'] }}</div>
                        </div>
                    </a>
                @else
                    <div class="tp-card" style="display:flex;align-items:center;gap:12px;opacity:.55;">
                        <i class="fas {{ $t['icon'] }}" style="color:var(--ink2);font-size:1.3rem;width:28px;text-align:center;"></i>
                        <div>
                            <div style="font-weight:700;color:var(--ink);font-size:.9rem;">{{ $t['label'] }}</div>
                            <div class="tp-muted" style="font-size:.74rem;">{{ $t['desc'] }}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection
