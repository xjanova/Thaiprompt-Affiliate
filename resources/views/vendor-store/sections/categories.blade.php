{{-- Section: Categories (หมวดหมู่สินค้า) --}}
@if($layoutSettings->show_categories && isset($categories) && $categories->count() > 0)
    @php
        $lc = $layoutSettings->layout_classes;
        $categoriesStyle = $layoutSettings->categories_style ?? 'grid';
        $isPreview = $isPreview ?? false;
    @endphp

    <section class="{{ $lc['section_spacing'] }}">
        <div class="{{ $lc['container'] }}">
            <h2 class="{{ $lc['heading'] }} mb-6 flex items-center gap-2" style="color: var(--store-primary)">
                <span>📁</span>
                {{ $layoutSettings->categories_title ?? 'หมวดหมู่สินค้า' }}
            </h2>

            @if($categoriesStyle === 'grid')
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($categories as $category)
                        <a href="{{ $isPreview ? '#' : route('store.show', ['slug' => $store->store_slug, 'category' => $category->slug]) }}"
                           class="group {{ $lc['category_card'] }} hover:shadow-xl transition hover:ring-2"
                           style="--tw-ring-color: var(--store-primary)">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-full flex items-center justify-center text-2xl"
                                 style="background: linear-gradient(135deg, {{ $layoutSettings->primary_color ?? '#6366f1' }}22, {{ $layoutSettings->secondary_color ?? '#8b5cf6' }}22)">
                                {{ $category->icon ?? '📦' }}
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-200 group-hover:store-primary-text transition">
                                {{ $category->name }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach($categories as $category)
                        <a href="{{ $isPreview ? '#' : route('store.show', ['slug' => $store->store_slug, 'category' => $category->slug]) }}"
                           class="px-4 py-2 bg-white/80 dark:bg-gray-800/80 {{ $lc['backdrop_blur'] }} {{ $lc['button'] }} shadow hover:shadow-md transition border hover:store-primary-text"
                           style="border-color: var(--store-primary)33">
                            {{ $category->icon ?? '📦' }} {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
