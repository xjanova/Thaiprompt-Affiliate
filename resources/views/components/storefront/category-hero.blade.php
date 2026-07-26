{{--
    หัวหมวดสินค้า — ใช้บนหน้าเลือกดูหมวด (?category=slug)

    แทนที่แบนเนอร์ใหญ่ของหน้าแรก ด้วยแถบหัวหมวดที่บอกชัดว่า "คุณอยู่ในหมวดอะไร"
    ภาพพื้นหลังมาจาก CategoryImageService (3 ชั้น: รูปแอดมิน → โมเสกจากสินค้าจริง → ไอคอน)
    ทับด้วยเฉดไล่จาง เพื่อให้ตัวหนังสืออ่านออกเสมอไม่ว่ารูปจะสว่างหรือมืด

    @param ProductCategory $category  หมวดที่กำลังดู (มี parent + children มาแล้ว)
    @param array|null $cover          ผลจาก CategoryImageService::cover()
    @param int $total                 จำนวนสินค้าที่พบ
--}}

@props([
    'category',
    'cover' => null,
    'total' => 0,
])

@php
    $mode = $cover['mode'] ?? 'glyph';
    $urls = array_values(array_filter((array) ($cover['urls'] ?? [])));
    $icon = $cover['icon'] ?? 'fas fa-tags';
@endphp

<section class="container mx-auto px-4 pt-4 pb-2">
    <div class="tp-glass tp-3d relative overflow-hidden rounded-2xl md:rounded-3xl">

        {{-- ชั้นภาพพื้นหลัง --}}
        <div class="absolute inset-0" aria-hidden="true">
            @if($mode === 'image' && ! empty($urls[0]))
                <img src="{{ $urls[0] }}"
                     alt=""
                     class="w-full h-full object-cover"
                     loading="lazy" decoding="async"
                     onerror="this.style.display='none';">
            @elseif($mode === 'mosaic' && count($urls) > 1)
                {{-- โมเสกจากสินค้าจริงในหมวดนี้ — เปลี่ยนตามของที่มีขายจริง ไม่ใช่ภาพตาย --}}
                <div class="grid grid-cols-2 md:grid-cols-4 h-full">
                    @foreach(array_slice($urls, 0, 4) as $u)
                        <img src="{{ $u }}"
                             alt=""
                             class="w-full h-full object-cover"
                             loading="lazy" decoding="async"
                             onerror="this.style.display='none';">
                    @endforeach
                </div>
            @else
                {{-- ไม่มีสินค้าให้ทำภาพ → ใช้เฉดสีล้วน (ยังสวย ไม่ใช่กล่องเปล่า) --}}
                <div class="w-full h-full bg-gradient-to-br from-orange-400 via-pink-500 to-violet-600 opacity-80"></div>
            @endif
        </div>

        {{-- เฉดไล่จาง — ทำให้ตัวหนังสืออ่านออกทับภาพใดก็ได้ (ซ้าย→ขวา ทึบ→โปร่ง) --}}
        <div class="absolute inset-0 bg-gradient-to-r
                    from-white/95 via-white/75 to-white/25
                    dark:from-gray-900/95 dark:via-gray-900/75 dark:to-gray-900/30"
             aria-hidden="true"></div>
        {{-- เฉดล่างเพิ่มอีกชั้น กันเคสรูปสว่างจ้าจนตัวอักษรจม --}}
        <div class="absolute inset-0 bg-gradient-to-t from-white/60 to-transparent
                    dark:from-gray-900/70"
             aria-hidden="true"></div>

        {{-- เนื้อหา --}}
        <div class="relative px-5 py-6 md:px-8 md:py-9">

            {{-- เส้นทาง (breadcrumb) --}}
            <nav class="flex flex-wrap items-center gap-1.5 text-xs md:text-sm text-gray-600 dark:text-gray-300 mb-2"
                 aria-label="เส้นทางหมวดหมู่">
                <a href="{{ route('home') }}" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">หน้าแรก</a>
                @if($category->parent)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('storefront.index', ['category' => $category->parent->slug]) }}"
                       class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">{{ $category->parent->name }}</a>
                @endif
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $category->name }}</span>
            </nav>

            <div class="flex items-start gap-3 md:gap-4">
                <span class="shrink-0 w-11 h-11 md:w-14 md:h-14 rounded-xl md:rounded-2xl
                             bg-gradient-to-br from-orange-500 to-pink-500
                             flex items-center justify-center shadow-lg">
                    <i class="{{ $icon }} text-white text-lg md:text-2xl"></i>
                </span>

                <div class="min-w-0">
                    <h1 class="text-xl md:text-3xl font-extrabold text-gray-900 dark:text-white leading-tight">
                        {{ $category->name }}
                    </h1>
                    @if($category->description)
                        <p class="mt-1 text-sm md:text-base text-gray-700 dark:text-gray-300 line-clamp-2 max-w-2xl">
                            {{ $category->description }}
                        </p>
                    @endif
                    <p class="mt-2 text-xs md:text-sm font-semibold text-orange-600 dark:text-orange-400">
                        {{ number_format($total) }} รายการ
                    </p>
                </div>
            </div>

            {{-- ชิปหมวดย่อย — กดเจาะลึกต่อได้โดยไม่ต้องกลับหน้าแรก --}}
            @if($category->children && $category->children->count() > 0)
                <div class="mt-4 flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                    @foreach($category->children as $child)
                        <a href="{{ route('storefront.index', ['category' => $child->slug]) }}"
                           class="shrink-0 px-3.5 py-1.5 rounded-full text-xs md:text-sm font-medium
                                  bg-white/80 dark:bg-gray-800/80 backdrop-blur
                                  text-gray-700 dark:text-gray-200
                                  border border-gray-200 dark:border-gray-600
                                  hover:border-orange-400 hover:text-orange-600
                                  dark:hover:border-orange-500 dark:hover:text-orange-400
                                  transition-colors whitespace-nowrap">
                            {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
