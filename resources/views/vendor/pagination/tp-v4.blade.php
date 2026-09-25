{{--
 | ตัวแบ่งหน้าธีม V4 (ใช้ตัวแปร CSS ของธีม — สว่าง/มืดตามธีมอัตโนมัติ)
 | ใช้งาน: $paginator->links('vendor.pagination.tp-v4')
 --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="แบ่งหน้า" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;">
        <div style="font-size:12px; color:var(--ink2);">
            @if ($paginator->firstItem())
                แสดง <span class="tp-num" style="font-weight:700; color:var(--ink);">{{ $paginator->firstItem() }}</span>
                – <span class="tp-num" style="font-weight:700; color:var(--ink);">{{ $paginator->lastItem() }}</span>
                จาก <span class="tp-num" style="font-weight:700; color:var(--ink);">{{ $paginator->total() }}</span> รายการ
            @endif
        </div>
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
            @if ($paginator->onFirstPage())
                <span class="tp-btn tp-btn-sm" style="opacity:.45; cursor:default;" aria-disabled="true">← ก่อนหน้า</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="tp-btn tp-btn-sm" style="text-decoration:none;">← ก่อนหน้า</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="tp-num" style="padding:0 6px; color:var(--ink2);">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="tp-btn tp-btn-sm tp-btn-primary tp-num" aria-current="page" style="min-width:34px;">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="tp-btn tp-btn-sm tp-num sv4-hide-sm" style="min-width:34px; text-decoration:none;" aria-label="หน้า {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="tp-btn tp-btn-sm" style="text-decoration:none;">ถัดไป →</a>
            @else
                <span class="tp-btn tp-btn-sm" style="opacity:.45; cursor:default;" aria-disabled="true">ถัดไป →</span>
            @endif
        </div>
    </nav>
@endif
