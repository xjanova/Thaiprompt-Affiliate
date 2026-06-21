@extends('layouts.admin-v4')

@section('title', 'คลังความรู้แม่หมอ (RAG)')

@section('content')
{{-- 🧠 คลังองค์ความรู้แม่หมอ (RAG) — ธีม V4 นวลทองคำ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:14px;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · คลังความรู้แม่หมอ
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);margin:0;">
                <i class="fas fa-brain" style="color:var(--accent1);margin-right:8px;"></i>คลังความรู้แม่หมอ (RAG)
            </h1>
            <p class="tp-muted" style="font-size:13px;margin:8px 0 0;max-width:680px;line-height:1.6;">
                องค์ความรู้ที่แม่หมอ (AI) ดึงไปทำนายตามหน้าไพ่ — สุขภาพ / ฮวงจุ้ย / เจ้าที่ / องค์เทพ / ไสยศาสตร์ ·
                แก้ไขแล้วใช้ได้ทันที
            </p>
        </div>
        <a href="{{ route('admin.fortune.knowledge.create') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-plus"></i> เพิ่มองค์ความรู้
        </a>
    </div>

    {{-- ===== KPI / สถิติ ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        {{-- ทั้งหมด --}}
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--accent1);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ทั้งหมด</div>
                    <div class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);line-height:1.1;">
                        {{ number_format($stats['total']) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- เปิดใช้งาน --}}
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#5aa07e;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">เปิดใช้งาน</div>
                    <div class="tp-num" style="font-size:26px;font-weight:800;color:#5aa07e;line-height:1.1;">
                        {{ number_format($stats['active']) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- แยกตามหมวด --}}
        <div class="tp-card tp-raise" style="padding:18px;grid-column:span 2;min-width:0;">
            <div class="tp-muted" style="font-size:12px;margin-bottom:10px;">
                <i class="fas fa-tags" style="margin-right:6px;"></i>แยกตามหมวด
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($categoryLabels as $key => $label)
                    <span class="tp-pill tp-pill-soft" style="font-size:12px;">
                        {{ $label }}
                        <b class="tp-num" style="color:var(--accent1);margin-left:4px;">{{ $stats['category_breakdown'][$key] ?? 0 }}</b>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== ตัวกรอง / ค้นหา ===== --}}
    <form method="GET" class="tp-card" style="padding:18px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;align-items:stretch;">
            {{-- ค้นหา --}}
            <div class="tp-well tp-input" style="padding:0;display:flex;align-items:center;gap:8px;padding-left:14px;">
                <i class="fas fa-magnifying-glass tp-muted" style="font-size:13px;"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหา หัวข้อ/เนื้อหา/ไพ่..."
                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px 11px 0;color:var(--ink);font-size:14px;">
            </div>

            {{-- หมวด --}}
            <div class="tp-well tp-input" style="padding:0;">
                <select name="category" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                    <option value="">— ทุกหมวด —</option>
                    @foreach($categoryLabels as $key => $label)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- สถานะ --}}
            <div class="tp-well tp-input" style="padding:0;">
                <select name="active" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                    <option value="">— ทุกสถานะ —</option>
                    <option value="1" @selected($active === '1')>เปิดใช้งาน</option>
                    <option value="0" @selected($active === '0')>ปิด</option>
                </select>
            </div>

            {{-- ปุ่มค้นหา --}}
            <button type="submit" class="tp-btn tp-btn-primary" style="justify-content:center;">
                <i class="fas fa-magnifying-glass"></i> ค้นหา
            </button>
        </div>
    </form>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;">หมวด</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;">หัวข้อ / เนื้อหา</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;">ไพ่/หัวเรื่อง</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:center;">สถานะ</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- หมวด --}}
                            <td style="padding:14px 16px;vertical-align:top;">
                                <span class="tp-pill tp-pill-gold" style="font-size:12px;white-space:nowrap;">
                                    {{ $categoryLabels[$row->category] ?? $row->category }}
                                </span>
                                @if($row->severity)
                                    <div style="margin-top:6px;font-size:12px;color:#d9534f;">
                                        <i class="fas fa-triangle-exclamation"></i> {{ $row->severity }}
                                    </div>
                                @endif
                            </td>

                            {{-- หัวข้อ / เนื้อหา --}}
                            <td style="padding:14px 16px;vertical-align:top;max-width:420px;">
                                <div style="font-weight:600;color:var(--ink);">{{ $row->title }}</div>
                                <div class="tp-muted" style="font-size:12px;margin-top:4px;line-height:1.5;">
                                    {{ \Illuminate\Support\Str::limit($row->content, 160) }}
                                </div>
                            </td>

                            {{-- ไพ่ / หัวเรื่อง --}}
                            <td style="padding:14px 16px;vertical-align:top;font-size:13px;color:var(--ink);">
                                @if($row->card_name)
                                    @php $c = $cardMap[$row->card_name] ?? null; @endphp
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        @if($c && $c->image_url)
                                            <img src="{{ $c->image_url }}" alt="{{ $row->card_name }}"
                                                 style="width:38px;height:auto;border-radius:6px;box-shadow:var(--raise);flex-shrink:0;" loading="lazy">
                                        @else
                                            <span style="font-size:20px;">🃏</span>
                                        @endif
                                        <div>
                                            <div style="font-weight:600;color:var(--ink);">{{ $c->name_th ?? $row->card_name }}</div>
                                            <div class="tp-muted" style="font-size:11px;">{{ $row->card_name }}</div>
                                        </div>
                                    </div>
                                @elseif($row->subject)
                                    <div class="tp-muted" style="font-size:12px;">{{ $row->subject }}</div>
                                @else
                                    <span class="tp-muted" style="font-size:12px;">—</span>
                                @endif
                            </td>

                            {{-- สถานะ (toggle) --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:center;">
                                <form method="POST" action="{{ route('admin.fortune.knowledge.toggle', $row) }}" style="margin:0;">
                                    @csrf
                                    @if($row->is_active)
                                        <button type="submit" class="tp-pill" style="border:0;cursor:pointer;font-size:12px;color:#5aa07e;background:var(--inset);">
                                            <i class="fas fa-circle" style="font-size:8px;margin-right:4px;"></i> เปิด
                                        </button>
                                    @else
                                        <button type="submit" class="tp-pill" style="border:0;cursor:pointer;font-size:12px;color:#9a8f7c;background:var(--inset);">
                                            <i class="far fa-circle" style="font-size:9px;margin-right:4px;"></i> ปิด
                                        </button>
                                    @endif
                                </form>
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:right;white-space:nowrap;">
                                <div style="display:inline-flex;align-items:center;gap:8px;">
                                    <a href="{{ route('admin.fortune.knowledge.edit', $row) }}" class="tp-icon-btn" title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.fortune.knowledge.destroy', $row) }}" style="margin:0;display:inline;"
                                          onsubmit="return confirm('ยืนยันลบองค์ความรู้นี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:48px 16px;text-align:center;">
                                <div style="font-size:40px;color:var(--ink2);opacity:.5;margin-bottom:12px;">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <div class="tp-muted" style="font-size:14px;">
                                    ยังไม่มีองค์ความรู้ — กด "เพิ่มองค์ความรู้" เพื่อเริ่ม หรือรัน seeder
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    <div>{{ $rows->links() }}</div>

</div>
@endsection
