@extends('layouts.admin-v4')

@section('title', 'ฐานความรู้')

@section('content')
{{-- 📚 ฐานความรู้ (ธีม V4 นวลทองคำ) — คง route toggle/edit/destroy เดิม 100% --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · ฐานความรู้</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ฐานความรู้ 📚</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">บทความช่วยเหลือที่ผู้ใช้ค้นหาได้เอง ลดจำนวน Ticket</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
            <a href="{{ route('admin.tickets.kb-articles.create') }}" class="tp-btn tp-btn-sm tp-btn-primary">
                <i class="fas fa-plus"></i> เขียนบทความ
            </a>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    @php
        $kpis = [
            [$articles->total(),                             'บทความทั้งหมด', 'fa-file-lines', null],
            [$articles->where('is_public', true)->count(),   'สาธารณะ (หน้านี้)', 'fa-globe',  '#5aa07e'],
            [$articles->sum('view_count'),                   'ยอดเข้าชม',     'fa-eye',        '#5689b8'],
            [$articles->sum('helpful_count'),                'โหวตว่ามีประโยชน์', 'fa-thumbs-up', '#e0a52e'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($kpis as [$value, $label, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;{{ $color ? ' background:'.$color.';' : '' }}">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        @foreach(['หัวข้อ','หมวดหมู่','ผู้เขียน','เข้าชม','มีประโยชน์','การมองเห็น','แก้ไขล่าสุด'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                        @php $tags = is_array($article->tags) ? $article->tags : []; @endphp
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- หัวข้อ + แท็ก --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:13.5px; font-weight:700; color:var(--ink);">{{ Str::limit($article->title, 50) }}</div>
                                @if(count($tags) > 0)
                                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:6px;">
                                        @foreach(array_slice($tags, 0, 3) as $tag)
                                            <span class="tp-pill tp-pill-soft"><i class="fas fa-tag"></i> {{ $tag }}</span>
                                        @endforeach
                                        @if(count($tags) > 3)
                                            <span class="tp-pill tp-pill-soft">+{{ count($tags) - 3 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            {{-- หมวดหมู่ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill tp-pill-soft"><i class="fas fa-folder"></i> {{ $article->category->name ?? 'ไม่มีหมวดหมู่' }}</span>
                            </td>
                            {{-- ผู้เขียน --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="display:flex; align-items:center; gap:9px;">
                                    <span class="tp-tile" style="width:28px; height:28px; border-radius:50%; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:#4fa3a3;">
                                        {{ mb_strtoupper(mb_substr($article->author?->name ?: '?', 0, 1)) }}
                                    </span>
                                    <span style="font-size:13px; color:var(--ink);">{{ $article->author?->name ?: '—' }}</span>
                                </div>
                            </td>
                            {{-- เข้าชม --}}
                            <td style="padding:14px 16px; white-space:nowrap; font-size:13.5px; font-weight:700; color:var(--ink);">
                                <i class="fas fa-eye" style="color:var(--ink2); margin-right:5px;"></i>{{ number_format($article->view_count) }}
                            </td>
                            {{-- มีประโยชน์ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($article->helpful_count > 0)
                                    <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;">
                                        <i class="fas fa-thumbs-up"></i> {{ number_format($article->helpful_count) }}
                                    </span>
                                @else
                                    <span style="color:var(--ink2); font-size:12px;">—</span>
                                @endif
                            </td>
                            {{-- การมองเห็น (ปุ่ม toggle) --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <form action="{{ route('admin.tickets.kb-articles.toggle', $article->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @if($article->is_public)
                                        <button type="submit" class="tp-pill" title="กดเพื่อเปลี่ยนเป็นส่วนตัว"
                                                style="background:rgba(90,160,126,.18); color:#3f7a5c; border:none; cursor:pointer; font:inherit;">
                                            <i class="fas fa-globe"></i> สาธารณะ
                                        </button>
                                    @else
                                        <button type="submit" class="tp-pill" title="กดเพื่อเปลี่ยนเป็นสาธารณะ"
                                                style="background:color-mix(in srgb, var(--ink2) 18%, transparent); color:var(--ink2); border:none; cursor:pointer; font:inherit;">
                                            <i class="fas fa-lock"></i> ส่วนตัว
                                        </button>
                                    @endif
                                </form>
                            </td>
                            {{-- แก้ไขล่าสุด --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="font-size:13px; color:var(--ink);">{{ $article->updated_at->format('d/m/Y') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $article->updated_at->format('H:i') }}</div>
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:7px;">
                                    <a href="{{ route('admin.tickets.kb-articles.edit', $article->id) }}" class="tp-btn tp-btn-sm" title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('admin.tickets.kb-articles.destroy', $article->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ลบบทความ &quot;{{ Str::limit($article->title, 40) }}&quot; ใช่หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:0;">
                                <div style="text-align:center; color:var(--ink2); padding:44px 0;">
                                    <i class="fas fa-book" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    <div style="font-size:14px; font-weight:600;">ยังไม่มีบทความ</div>
                                    <div style="font-size:12px; margin-top:4px;">เขียนบทความแรกเพื่อช่วยให้ผู้ใช้แก้ปัญหาได้เอง</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($articles->hasPages())
        <div>{{ $articles->appends(request()->query())->links() }}</div>
    @endif

</div>
@endsection
