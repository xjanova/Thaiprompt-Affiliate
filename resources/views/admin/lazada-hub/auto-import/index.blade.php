@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'นำเข้าอัตโนมัติ')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     Lazada Hub — นำเข้าอัตโนมัติ (คิว URL) + คำขอจากลูกค้า (Eve wishes)
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> · นำเข้าอัตโนมัติ
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-robot" style="color:var(--accent1);"></i> นำเข้าอัตโนมัติ
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">วางลิงก์สินค้า → ระบบค่อยๆ ดึงเข้า catalog ทีละน้อยทุก 5 นาที จนครบ</p>
        </div>
        {{-- สวิตช์เปิด/ปิด --}}
        <form method="POST" action="{{ route('admin.lazada-hub.auto-import.toggle') }}">
            @csrf
            <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
            <button class="tp-btn {{ $enabled ? '' : 'tp-btn-primary' }}" style="{{ $enabled ? 'background:#5aa07e;color:#fff;' : '' }}">
                <i class="fas {{ $enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                <span>{{ $enabled ? 'เปิดอยู่ (กดเพื่อปิด)' : 'ปิดอยู่ (กดเพื่อเปิด)' }}</span>
            </button>
        </form>
    </div>

    @if(session('success'))<div class="tp-card" style="border-left:4px solid #5aa07e;font-size:.88rem;color:var(--ink);"><i class="fas fa-circle-check" style="color:#5aa07e;"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="tp-card" style="border-left:4px solid #d9534f;font-size:.88rem;color:var(--ink);"><i class="fas fa-circle-exclamation" style="color:#d9534f;"></i> {{ session('error') }}</div>@endif

    {{-- สถิติคิว --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;">
        @php $stat = [['รอนำเข้า','pending','#e0a52e','fa-hourglass-half'],['สำเร็จ','done','#5aa07e','fa-circle-check'],['ข้าม','skipped','var(--ink2)','fa-forward'],['ผิดพลาด','failed','#d9534f','fa-triangle-exclamation']]; @endphp
        @foreach($stat as [$lbl,$k,$col,$ic])
            <div class="tp-card" style="display:flex;flex-direction:column;gap:4px;">
                <span class="tp-muted" style="font-size:.74rem;"><i class="fas {{ $ic }}" style="color:{{ $col }};"></i> {{ $lbl }}</span>
                <span class="tp-num" style="font-size:1.4rem;font-weight:800;color:var(--ink);">{{ number_format($counts[$k] ?? 0) }}</span>
            </div>
        @endforeach
    </div>

    {{-- ฟอร์มเพิ่มลิงก์ --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
        <div style="font-weight:700;color:var(--ink);"><i class="fas fa-plus" style="color:var(--accent1);"></i> เพิ่มลิงก์เข้าคิว</div>
        <form method="POST" action="{{ route('admin.lazada-hub.auto-import.enqueue') }}" style="display:flex;flex-direction:column;gap:12px;">
            @csrf
            <textarea name="urls" rows="4" class="tp-input tp-num" style="font-size:12.5px;min-height:96px;" placeholder="วางลิงก์สินค้า Lazada บรรทัดละ 1 ลิงก์ (สูงสุด 200/ครั้ง)&#10;https://www.lazada.co.th/products/...-i123.html"></textarea>
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end;">
                <div><label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ราคาต่ำสุด (฿)</label><input type="number" name="price_min" min="0" class="tp-input tp-num" style="width:120px;"></div>
                <div><label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ราคาสูงสุด (฿)</label><input type="number" name="price_max" min="0" class="tp-input tp-num" style="width:120px;"></div>
                <div><label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">โหมด</label>
                    <select name="fulfillment_mode" class="tp-input" style="width:auto;">
                        <option value="">ค่าเริ่มต้น ({{ $defaultMode==='affiliate' ? 'Affiliate' : 'ขายเอง' }})</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="resell">ขายเอง</option>
                    </select>
                </div>
                <div><label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ป้ายล็อต (ไม่บังคับ)</label><input type="text" name="batch_label" maxlength="100" class="tp-input" style="width:160px;"></div>
                <button class="tp-btn tp-btn-primary" style="height:42px;"><i class="fas fa-layer-group"></i> <span>เข้าคิว</span></button>
            </div>
            <p class="tp-muted" style="font-size:.74rem;margin:0;">* กรองช่วงราคา: ถ้าราคาจริงไม่อยู่ในช่วง ระบบจะข้าม &nbsp;|&nbsp; รองรับเฉพาะลิงก์หน้าสินค้า lazada.co.th</p>
        </form>
    </div>

    {{-- รายการคิว --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;">
            <div style="font-weight:700;color:var(--ink);">คิวล่าสุด</div>
            <form method="POST" action="{{ route('admin.lazada-hub.auto-import.clear') }}" onsubmit="return confirm('ล้างรายการที่จบแล้ว (สำเร็จ/ข้าม/ผิดพลาด)?');">
                @csrf
                <button class="tp-btn tp-btn-sm"><i class="fas fa-broom"></i> <span>ล้างที่จบแล้ว</span></button>
            </form>
        </div>
        @if($items->isEmpty())
            <div style="padding:28px;text-align:center;color:var(--ink2);">ยังไม่มีในคิว — วางลิงก์ด้านบนเพื่อเริ่ม</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:680px;">
                    <thead><tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                        <th style="padding:9px 12px;">ลิงก์</th><th style="padding:9px 12px;">ช่วงราคา</th><th style="padding:9px 12px;text-align:center;">โหมด</th><th style="padding:9px 12px;text-align:center;">สถานะ</th><th style="padding:9px 12px;text-align:right;">จัดการ</th>
                    </tr></thead>
                    <tbody>
                    @foreach($items as $it)
                        @php $sm = ['pending'=>['รอ','#e0a52e'],'done'=>['สำเร็จ','#5aa07e'],'skipped'=>['ข้าม','var(--ink2)'],'failed'=>['ผิดพลาด','#d9534f']][$it->status] ?? [$it->status,'var(--ink2)']; @endphp
                        <tr style="border-top:1px solid color-mix(in srgb,var(--ink2) 14%,transparent);">
                            <td style="padding:8px 12px;max-width:320px;">
                                <a href="{{ $it->url }}" target="_blank" rel="noopener" class="tp-num" style="color:var(--accent2);text-decoration:none;font-size:.74rem;word-break:break-all;">{{ \Illuminate\Support\Str::limit($it->url, 70) }}</a>
                                @if($it->error)<div style="color:#d9534f;font-size:.7rem;">{{ \Illuminate\Support\Str::limit($it->error, 80) }}</div>@endif
                                @if($it->batch_label)<span class="tp-pill" style="background:var(--surf);color:var(--ink2);font-size:9px;">{{ $it->batch_label }}</span>@endif
                            </td>
                            <td class="tp-num" style="padding:8px 12px;color:var(--ink2);font-size:.74rem;">{{ $it->price_min ? number_format($it->price_min) : '–' }} - {{ $it->price_max ? number_format($it->price_max) : '∞' }}</td>
                            <td style="padding:8px 12px;text-align:center;color:var(--ink2);font-size:.74rem;">{{ $it->fulfillment_mode ?: '—' }}</td>
                            <td style="padding:8px 12px;text-align:center;"><span class="tp-pill" style="background:{{ $sm[1] }};color:#fff;font-size:10px;font-weight:700;">{{ $sm[0] }}</span></td>
                            <td style="padding:8px 12px;text-align:right;white-space:nowrap;">
                                @if($it->status === 'failed')
                                    <form method="POST" action="{{ route('admin.lazada-hub.auto-import.retry', $it) }}" style="display:inline;">@csrf<button class="tp-btn tp-btn-sm" title="ลองใหม่"><i class="fas fa-rotate"></i></button></form>
                                @endif
                                @if($it->marketplace_product_id)
                                    <a href="{{ route('admin.lazada-hub.catalog.index') }}" class="tp-btn tp-btn-sm" title="ดูในแคตตาล็อก"><i class="fas fa-layer-group"></i></a>
                                @endif
                                <form method="POST" action="{{ route('admin.lazada-hub.auto-import.destroy', $it) }}" style="display:inline;" onsubmit="return confirm('ลบรายการนี้?');">@csrf @method('DELETE')<button class="tp-btn tp-btn-sm" style="color:#d9534f;"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── คำขอจากลูกค้า (Eve wishes) ── --}}
    <div class="tp-card" style="border-left:4px solid #b79ae8;">
        <div style="font-weight:700;color:var(--ink);margin-bottom:4px;"><i class="fas fa-lightbulb" style="color:#b79ae8;"></i> ของที่ลูกค้าอยากได้ (น้อง Eve เก็บมา)</div>
        <p class="tp-muted" style="font-size:.8rem;margin:0 0 12px;">ตอนลูกค้าถาม Eve แล้วค้นในร้านไม่เจอ ระบบเก็บคำขอไว้ตรงนี้ — เอาไปหาสินค้ามาเติมได้</p>

        @if($topWishes->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                @foreach($topWishes as $w)
                    <span class="tp-pill" style="background:var(--surf);color:var(--ink);font-size:.78rem;padding:5px 11px;">
                        {{ $w->query }} <b style="color:#b79ae8;">×{{ $w->cnt }}</b>
                    </span>
                @endforeach
            </div>
        @endif

        @if($recentWishes->isEmpty())
            <div style="text-align:center;color:var(--ink2);padding:14px;">ยังไม่มีคำขอจากลูกค้า</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:560px;">
                    <thead><tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                        <th style="padding:8px 12px;">คำขอ</th><th style="padding:8px 12px;">งบ</th><th style="padding:8px 12px;">เมื่อ</th><th style="padding:8px 12px;text-align:center;">สถานะ</th><th style="padding:8px 12px;text-align:right;">จัดการ</th>
                    </tr></thead>
                    <tbody>
                    @foreach($recentWishes as $w)
                        @php $ws = ['pending'=>['รอ','#e0a52e'],'fulfilled'=>['หาให้แล้ว','#5aa07e'],'none'=>['ไม่ทำ','var(--ink2)']][$w->status] ?? [$w->status,'var(--ink2)']; @endphp
                        <tr style="border-top:1px solid color-mix(in srgb,var(--ink2) 14%,transparent);">
                            <td style="padding:8px 12px;color:var(--ink);">{{ $w->query }}</td>
                            <td class="tp-num" style="padding:8px 12px;color:var(--ink2);">{{ $w->budget ? '≤ '.number_format($w->budget).' ฿' : '–' }}</td>
                            <td class="tp-muted" style="padding:8px 12px;font-size:.74rem;">{{ $w->created_at?->diffForHumans() }}</td>
                            <td style="padding:8px 12px;text-align:center;"><span class="tp-pill" style="background:{{ $ws[1] }};color:#fff;font-size:10px;">{{ $ws[0] }}</span></td>
                            <td style="padding:8px 12px;text-align:right;white-space:nowrap;">
                                <form method="POST" action="{{ route('admin.lazada-hub.wishes.status', $w) }}" style="display:inline;">@csrf
                                    <input type="hidden" name="status" value="fulfilled"><button class="tp-btn tp-btn-sm" title="หาให้แล้ว"><i class="fas fa-check" style="color:#5aa07e;"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.lazada-hub.wishes.status', $w) }}" style="display:inline;">@csrf
                                    <input type="hidden" name="status" value="none"><button class="tp-btn tp-btn-sm" title="ไม่ทำ"><i class="fas fa-xmark" style="color:var(--ink2);"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
