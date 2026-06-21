@extends('layouts.admin-v4')

@section('title', 'เทมเพลตตอบกลับคำทำนาย')

@section('content')
{{-- ───────────────────────────────────────────────────────────────
     หน้า "เทมเพลตตอบกลับคำทำนาย" — ธีม V4 นวลทองคำ
     จัดการเทมเพลตข้อความที่ใช้ตอบกลับผ่าน Facebook Messenger / LINE
     ฟังก์ชันคงเดิม 100%:
       - ลิงก์สร้าง/แก้ไข (GET create, edit)
       - ตั้งเป็นเริ่มต้น (POST set-default + csrf)
       - ลบเทมเพลต (POST destroy + csrf + method DELETE + confirm)
     ตัวแปร/ฟิลด์จาก controller@index:
       $stats[total|active|types], $groupedTemplates (groupBy type)
       template: name, slug, is_default, is_active, body, available_placeholders
                 + method getTypeIcon()/getTypeLabel()/hasHeaderImage()/hasFooterImage()
     ──────────────────────────────────────────────────────────── --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ───── Header ───── --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · เทมเพลตตอบกลับ
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                เทมเพลตตอบกลับคำทำนาย 📝
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px; max-width:560px;">
                จัดการเทมเพลตข้อความที่ใช้ตอบกลับผ่าน Facebook Messenger และ LINE — ตั้งเทมเพลตเริ่มต้นของแต่ละประเภทได้
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            @if(Route::has('admin.fortune.response-templates.create'))
                <a href="{{ route('admin.fortune.response-templates.create') }}"
                   class="tp-btn tp-btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <i class="fas fa-plus"></i>
                    สร้างเทมเพลตใหม่
                </a>
            @endif
        </div>
    </div>

    {{-- ───── KPI grid ───── --}}
    @php
        // ไทล์สถิติ — icon + สี status ตาม DESIGN KIT
        $kpis = [
            ['label' => 'เทมเพลตทั้งหมด', 'value' => $stats['total'],  'icon' => 'fa-layer-group',  'color' => 'var(--accent1)'],
            ['label' => 'เปิดใช้งาน',     'value' => $stats['active'], 'icon' => 'fa-circle-check',  'color' => '#5aa07e'],
            ['label' => 'จำนวนประเภท',    'value' => $stats['types'],  'icon' => 'fa-shapes',       'color' => '#5689b8'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        @foreach($kpis as $kpi)
            <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
                <div class="tp-tile" style="flex-shrink:0;">
                    <i class="fas {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:12px; color:var(--ink2); font-weight:600;">{{ $kpi['label'] }}</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1.1; color:{{ $kpi['color'] }};">
                        {{ number_format($kpi['value']) }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ───── เทมเพลตจัดกลุ่มตามประเภท ───── --}}
    @forelse($groupedTemplates as $type => $typeTemplates)
        <div class="tp-card" style="display:flex; flex-direction:column; gap:16px;">

            {{-- หัวข้อกลุ่ม --}}
            <div class="tp-section-h" style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:20px; line-height:1;">{{ $typeTemplates->first()->getTypeIcon() }}</span>
                <span style="font-weight:800; font-size:16px; color:var(--ink);">{{ $typeTemplates->first()->getTypeLabel() }}</span>
                <span class="tp-pill tp-pill-soft tp-num" style="margin-left:2px;">{{ $typeTemplates->count() }}</span>
            </div>

            <div class="tp-divider"></div>

            {{-- การ์ดเทมเพลตในกลุ่ม --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:14px;">
                @foreach($typeTemplates as $template)
                    <div class="tp-inset"
                         style="display:flex; flex-direction:column; gap:12px; padding:16px;
                                border-left:3px solid {{ $template->is_default ? 'var(--accent1)' : 'var(--sd)' }};">

                        {{-- หัวการ์ด: ชื่อ + slug + ป้ายสถานะ --}}
                        <div style="display:flex; flex-direction:column; gap:7px;">
                            <h3 style="font-weight:800; font-size:15px; color:var(--ink); margin:0; word-break:break-word;">
                                {{ $template->name }}
                            </h3>
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
                                <code class="tp-inset-sm tp-num"
                                      style="font-size:11px; color:var(--ink2); padding:2px 8px; border-radius:8px;">
                                    {{ $template->slug }}
                                </code>
                                @if($template->is_default)
                                    <span class="tp-pill tp-pill-gold" style="display:inline-flex; align-items:center; gap:5px;">
                                        <i class="fas fa-star" style="font-size:10px;"></i> เริ่มต้น
                                    </span>
                                @endif
                                @if($template->is_active)
                                    <span class="tp-pill" style="display:inline-flex; align-items:center; gap:5px; color:#5aa07e;">
                                        <i class="fas fa-circle" style="font-size:8px;"></i> เปิดใช้
                                    </span>
                                @else
                                    <span class="tp-pill" style="display:inline-flex; align-items:center; gap:5px; color:#d9534f;">
                                        <i class="fas fa-circle" style="font-size:8px;"></i> ปิดใช้
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- ป้ายรูปภาพ (ส่วนหัว / ส่วนท้าย QR) --}}
                        @if($template->hasHeaderImage() || $template->hasFooterImage())
                            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                @if($template->hasHeaderImage())
                                    <span class="tp-pill" style="display:inline-flex; align-items:center; gap:5px; color:#5689b8; font-size:11px;">
                                        <i class="fas fa-image"></i> รูปส่วนหัว
                                    </span>
                                @endif
                                @if($template->hasFooterImage())
                                    <span class="tp-pill" style="display:inline-flex; align-items:center; gap:5px; color:#d6824a; font-size:11px;">
                                        <i class="fas fa-qrcode"></i> รูปส่วนท้าย (QR/บัญชี)
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- ตัวอย่างเนื้อหาเทมเพลต --}}
                        <div class="tp-well"
                             style="max-height:160px; overflow-y:auto; padding:12px 14px;">
                            <pre style="margin:0; font-size:12px; line-height:1.6; color:var(--ink2);
                                        white-space:pre-wrap; word-break:break-word; font-family:inherit;">{{ Str::limit($template->body, 300) }}</pre>
                        </div>

                        {{-- placeholders ที่ใช้ได้ --}}
                        @if(!empty($template->available_placeholders))
                            <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                @foreach($template->available_placeholders as $placeholder)
                                    <code class="tp-inset-sm tp-num"
                                          style="font-size:10.5px; color:var(--accent2); padding:2px 7px; border-radius:7px;">
                                        {{ $placeholder }}
                                    </code>
                                @endforeach
                            </div>
                        @endif

                        {{-- ปุ่ม action --}}
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding-top:12px;
                                    border-top:1px solid var(--sd);">
                            @if(Route::has('admin.fortune.response-templates.edit'))
                                <a href="{{ route('admin.fortune.response-templates.edit', $template) }}"
                                   class="tp-btn tp-btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                                    <i class="fas fa-pen"></i> แก้ไข
                                </a>
                            @endif

                            @if(!$template->is_default && Route::has('admin.fortune.response-templates.set-default'))
                                <form method="POST" action="{{ route('admin.fortune.response-templates.set-default', $template) }}" style="margin:0;">
                                    @csrf
                                    <button type="submit"
                                            class="tp-btn tp-btn-sm tp-btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                                        <i class="fas fa-star"></i> ตั้งเป็นเริ่มต้น
                                    </button>
                                </form>
                            @endif

                            @if(Route::has('admin.fortune.response-templates.destroy'))
                                <form method="POST" action="{{ route('admin.fortune.response-templates.destroy', $template) }}"
                                      onsubmit="return confirm('ยืนยันลบเทมเพลต &quot;{{ $template->name }}&quot;?')" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="tp-btn tp-btn-sm" style="display:inline-flex; align-items:center; gap:6px; color:#d9534f;">
                                        <i class="fas fa-trash-can"></i> ลบ
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        {{-- สถานะว่าง --}}
        <div class="tp-card" style="text-align:center; padding:48px 24px;">
            <div class="tp-tile" style="margin:0 auto 16px; width:64px; height:64px;">
                <i class="fas fa-inbox" style="font-size:26px; color:var(--ink2);"></i>
            </div>
            <p style="font-size:16px; font-weight:700; color:var(--ink); margin:0 0 6px;">ยังไม่มีเทมเพลต</p>
            <p class="tp-muted" style="font-size:13px; margin:0 0 18px;">สร้างเทมเพลตแรกเพื่อเริ่มตอบกลับคำทำนายอัตโนมัติ</p>
            @if(Route::has('admin.fortune.response-templates.create'))
                <a href="{{ route('admin.fortune.response-templates.create') }}"
                   class="tp-btn tp-btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <i class="fas fa-plus"></i> สร้างเทมเพลตแรก
                </a>
            @endif
        </div>
    @endforelse
</div>
@endsection
