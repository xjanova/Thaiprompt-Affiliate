{{--
 | หมวดหมู่สินค้าของร้าน — ธีม V4
 | ตัวแปร: $store, $layoutSettings (show_categories, categories_title, categories_style grid|list), $categories, $isPreview
 --}}
@php
    $vcPreview = (bool) ($isPreview ?? false);
    $vcActive = is_scalar(request('category')) ? (string) request('category') : '';
    $vcIcon = function ($category) {
        $icon = trim((string) ($category->icon ?? ''));
        if ($icon === '') {
            return ['type' => 'text', 'value' => '📦'];
        }

        return str_starts_with($icon, 'fa') ? ['type' => 'fa', 'value' => $icon] : ['type' => 'text', 'value' => mb_substr($icon, 0, 4)];
    };
@endphp

@if($layoutSettings->show_categories && isset($categories) && $categories->count() > 0)
    <section class="sf-wrap sf-section">
        <div class="sf-section-h">
            <h2 class="sf-title"><i class="fas fa-layer-group" style="color:var(--store-a);"></i> {{ $layoutSettings->categories_title ?: 'หมวดหมู่สินค้า' }}</h2>
        </div>
        @if(($layoutSettings->categories_style ?? 'grid') === 'grid')
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:12px;">
                @foreach($categories as $category)
                    @php $ic = $vcIcon($category); @endphp
                    <a href="{{ $vcPreview ? '#' : route('store.show', ['slug' => $store->store_slug, 'category' => $category->slug]).'#all-products' }}"
                       class="tp-card tp-card-hover" style="padding:14px 10px; text-decoration:none; display:flex; flex-direction:column; align-items:center; gap:8px; text-align:center; {{ $vcActive === $category->slug ? 'outline:2px solid var(--store-a);' : '' }}">
                        <span style="width:54px; height:54px; border-radius:18px; display:grid; place-items:center; font-size:24px; color:var(--store-a); background:color-mix(in srgb, var(--store-a) 14%, transparent); box-shadow:var(--inset-sm);">
                            @if($ic['type'] === 'fa')<i class="{{ $ic['value'] }}"></i>@else{{ $ic['value'] }}@endif
                        </span>
                        <span style="font-size:13px; font-weight:700; color:var(--ink);">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="sf-scroll">
                @foreach($categories as $category)
                    @php $ic = $vcIcon($category); @endphp
                    <a href="{{ $vcPreview ? '#' : route('store.show', ['slug' => $store->store_slug, 'category' => $category->slug]).'#all-products' }}"
                       class="sf-chip {{ $vcActive === $category->slug ? 'is-on' : '' }}">
                        @if($ic['type'] === 'fa')<i class="{{ $ic['value'] }}"></i>@else{{ $ic['value'] }}@endif {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endif
