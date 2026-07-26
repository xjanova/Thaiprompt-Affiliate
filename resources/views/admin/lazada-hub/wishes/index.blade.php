@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'ของที่ลูกค้าอยากได้')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: ของที่ลูกค้าอยากได้ (คิวคำขอจากน้อง Eve)
     Eve ค้น Lazada สดไม่ได้ (ต้องใช้คุกกี้เบราว์เซอร์) จึง "จดไว้" แทน
     หน้านี้ให้เจ้าของระบบเลือกว่าจะเติมอะไรเข้าคลัง ตอนที่พร้อม
     ════════════════════════════════════════════════════════════ --}}
<div class="space-y-5">

    {{-- หัวเรื่อง --}}
    <div class="tp-card" style="padding:18px 20px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;margin:0;">🎯 ของที่ลูกค้าอยากได้</h1>
                <p class="tp-muted" style="margin:6px 0 0;font-size:.85rem;line-height:1.6;">
                    น้อง Eve จดคำขอไว้เมื่อค้นในคลังเราแล้วไม่เจอ —
                    เลือกคำที่ถูกขอบ่อย แล้วค่อยเติมสินค้าเข้าคลังรอบถัดไป
                </p>
            </div>
            <a href="{{ route('admin.lazada-hub.wishes.export') }}" class="tp-btn">
                <i class="fas fa-file-csv"></i> ดาวน์โหลด CSV
            </a>
        </div>
    </div>

    {{-- ตัวเลขสรุป --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
        @foreach([
            ['คำขอทั้งหมด', $stats['total'], 'fa-inbox', 'var(--ink)'],
            ['คำไม่ซ้ำ', $stats['unique_queries'], 'fa-fingerprint', 'var(--ink)'],
            ['รอดำเนินการ', $stats['pending'], 'fa-hourglass-half', '#d98e3f'],
            ['หาไม่เจอเลย', $stats['not_found'], 'fa-magnifying-glass-minus', '#c0392b'],
            ['เติมให้แล้ว', $stats['fulfilled'], 'fa-circle-check', '#2e8b57'],
            ['7 วันล่าสุด', $stats['last_7d'], 'fa-calendar-week', 'var(--ink)'],
        ] as [$label, $val, $icon, $color])
        <div class="tp-card" style="padding:14px 16px;">
            <div class="tp-muted" style="font-size:.75rem;display:flex;align-items:center;gap:6px;">
                <i class="fas {{ $icon }}"></i> {{ $label }}
            </div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;margin-top:4px;color:{{ $color }};">
                {{ number_format($val) }}
            </div>
        </div>
        @endforeach
    </div>

    {{-- ตัวกรอง --}}
    <div class="tp-card" style="padding:14px 16px;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="ค้นคำที่ลูกค้าขอ..."
                   class="tp-input" style="flex:1;min-width:200px;">
            <select name="status" class="tp-input" style="min-width:150px;">
                <option value="">ทุกสถานะ</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>
                        {{ ['pending'=>'รอดำเนินการ','searching'=>'กำลังหา','fulfilled'=>'เติมให้แล้ว','none'=>'ไม่เติม'][$s] ?? $s }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="tp-btn tp-btn--primary"><i class="fas fa-filter"></i> กรอง</button>
            <a href="{{ route('admin.lazada-hub.wishes.index', ['mode' => $mode === 'grouped' ? 'raw' : 'grouped']) }}"
               class="tp-btn">
                {{ $mode === 'grouped' ? '📋 ดูรายครั้ง' : '🧮 ดูแบบจัดกลุ่ม' }}
            </a>
        </form>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:12px 16px;border-left:4px solid #2e8b57;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:12px 16px;border-left:4px solid #c0392b;">{{ session('error') }}</div>
    @endif

    {{-- ตาราง --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        @if($rows->count() === 0)
            <div style="text-align:center;padding:44px 20px;">
                <i class="fas fa-comments" style="font-size:2.2rem;opacity:.3;"></i>
                <p style="margin:14px 0 0;font-weight:600;">ยังไม่มีคำขอจากลูกค้า</p>
                <p class="tp-muted" style="margin:6px 0 0;font-size:.85rem;">
                    เมื่อลูกค้าถามหาสินค้าที่คลังเรายังไม่มี น้อง Eve จะจดไว้ที่นี่อัตโนมัติ
                </p>
            </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
                <thead>
                    <tr class="tp-inset">
                        @if($mode === 'grouped')
                            <th style="text-align:left;padding:12px 14px;">คำที่ลูกค้าขอ</th>
                            <th style="text-align:center;padding:12px 10px;">ถูกขอ</th>
                            <th style="text-align:center;padding:12px 10px;">จำนวนคน</th>
                            <th style="text-align:center;padding:12px 10px;">หาไม่เจอ</th>
                            <th style="text-align:right;padding:12px 10px;">งบ</th>
                            <th style="text-align:left;padding:12px 10px;">ขอล่าสุด</th>
                            <th style="text-align:center;padding:12px 14px;">จัดการ</th>
                        @else
                            <th style="text-align:left;padding:12px 14px;">คำที่ลูกค้าขอ</th>
                            <th style="text-align:left;padding:12px 10px;">ลูกค้า</th>
                            <th style="text-align:center;padding:12px 10px;">ผลค้น</th>
                            <th style="text-align:right;padding:12px 10px;">งบ</th>
                            <th style="text-align:center;padding:12px 10px;">สถานะ</th>
                            <th style="text-align:left;padding:12px 10px;">เมื่อ</th>
                            <th style="text-align:center;padding:12px 14px;">จัดการ</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                    <tr style="border-top:1px solid var(--line);">
                        @if($mode === 'grouped')
                            <td style="padding:12px 14px;font-weight:600;">{{ $r->sample_query }}</td>
                            <td style="text-align:center;padding:12px 10px;">
                                <span class="tp-pill">{{ number_format($r->times_asked) }} ครั้ง</span>
                            </td>
                            <td style="text-align:center;padding:12px 10px;">{{ number_format($r->distinct_users) }}</td>
                            <td style="text-align:center;padding:12px 10px;">
                                @if($r->times_empty > 0)
                                    <span style="color:#c0392b;font-weight:700;">{{ $r->times_empty }}</span>
                                @else
                                    <span class="tp-muted">—</span>
                                @endif
                            </td>
                            <td style="text-align:right;padding:12px 10px;" class="tp-num">
                                {{ $r->budget_max ? '≤ '.number_format((float) $r->budget_max).'฿' : '—' }}
                            </td>
                            <td style="padding:12px 10px;" class="tp-muted">
                                {{ \Illuminate\Support\Carbon::parse($r->last_asked_at)->diffForHumans() }}
                            </td>
                            <td style="text-align:center;padding:12px 14px;">
                                <form method="POST" action="{{ route('admin.lazada-hub.wishes.update') }}"
                                      style="display:inline-flex;gap:6px;">
                                    @csrf
                                    <input type="hidden" name="query_key" value="{{ $r->sample_query }}">
                                    <select name="status" class="tp-input" style="padding:4px 8px;font-size:.8rem;">
                                        <option value="fulfilled">เติมให้แล้ว</option>
                                        <option value="searching">กำลังหา</option>
                                        <option value="pending">รอดำเนินการ</option>
                                        <option value="none">ไม่เติม</option>
                                    </select>
                                    <button class="tp-btn" style="padding:4px 10px;font-size:.8rem;">บันทึก</button>
                                </form>
                            </td>
                        @else
                            <td style="padding:12px 14px;font-weight:600;">{{ $r->query }}</td>
                            <td style="padding:12px 10px;">{{ $r->user?->name ?? 'ผู้เยี่ยมชม' }}</td>
                            <td style="text-align:center;padding:12px 10px;">
                                @if((int) $r->results_found === 0)
                                    <span style="color:#c0392b;font-weight:700;">ไม่เจอ</span>
                                @else
                                    {{ $r->results_found }}
                                @endif
                            </td>
                            <td style="text-align:right;padding:12px 10px;" class="tp-num">
                                {{ $r->budget ? '≤ '.number_format((float) $r->budget).'฿' : '—' }}
                            </td>
                            <td style="text-align:center;padding:12px 10px;">
                                <span class="tp-pill">{{ $r->status }}</span>
                            </td>
                            <td style="padding:12px 10px;" class="tp-muted">{{ $r->created_at?->diffForHumans() }}</td>
                            <td style="text-align:center;padding:12px 14px;">
                                <form method="POST" action="{{ route('admin.lazada-hub.wishes.update') }}"
                                      style="display:inline-flex;gap:6px;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $r->id }}">
                                    <select name="status" class="tp-input" style="padding:4px 8px;font-size:.8rem;">
                                        <option value="fulfilled">เติมให้แล้ว</option>
                                        <option value="searching">กำลังหา</option>
                                        <option value="pending">รอดำเนินการ</option>
                                        <option value="none">ไม่เติม</option>
                                    </select>
                                    <button class="tp-btn" style="padding:4px 10px;font-size:.8rem;">บันทึก</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">{{ $rows->links() }}</div>
        @endif
    </div>

    {{-- คำอธิบายวิธีใช้ --}}
    <div class="tp-card" style="padding:16px 18px;">
        <h3 style="font-size:.95rem;font-weight:700;margin:0 0 8px;">📌 เอาไปใช้ต่อยังไง</h3>
        <ol class="tp-muted" style="font-size:.85rem;line-height:1.9;margin:0;padding-left:20px;">
            <li>ดูคำที่ <strong>ถูกขอบ่อย</strong> และ <strong>หาไม่เจอ</strong> — นั่นคือช่องว่างในคลังที่ชัดที่สุด</li>
            <li>กด "ดาวน์โหลด CSV" เอาคำเหล่านั้นไปใช้เป็นคำค้นตอนเก็บสินค้าเข้าคลังรอบถัดไป</li>
            <li>เติมเข้าไฟล์ <code>database/data/lazada-catalog-products.json</code> แล้วรัน
                <code>php artisan lazada:mu-import 2 --file=...</code></li>
            <li>กลับมาเปลี่ยนสถานะเป็น "เติมให้แล้ว" เพื่อไม่ให้ค้างในคิว</li>
        </ol>
        <p class="tp-muted" style="font-size:.8rem;margin:10px 0 0;line-height:1.7;">
            ⚠️ น้อง Eve ค้นสินค้าจาก Lazada สดๆ เองไม่ได้ (ต้องใช้คุกกี้ในเบราว์เซอร์ที่ล็อกอินค้าง
            เซิร์ฟเวอร์ยิงเองไม่ได้) จึงจดคำขอไว้ที่นี่แทน แล้วให้คนเติมเข้าคลังเมื่อพร้อม
        </p>
    </div>

</div>
@endsection
