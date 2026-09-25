{{--
 | สินค้าทั้งหมดของร้าน + ตัวกรอง — ธีม V4
 | ตัวแปร: $store, $layoutSettings (show_sidebar, sidebar_position, products_per_row), $products (paginator), $categories, $isPreview
 | ตัวกรอง GET store.show: search, category (slug), sort (latest|price_low|price_high|popular|rating|name), min_price, max_price
 --}}
@php
    $vaPreview = (bool) ($isPreview ?? false);
    $vaMin = [2 => '240px', 3 => '210px', 4 => '172px', 5 => '152px', 6 => '136px'][(int) ($layoutSettings->products_per_row ?? 4)] ?? '172px';
    $vaSidebar = (bool) ($layoutSettings->show_sidebar ?? true);
    $vaRight = ($layoutSettings->sidebar_position ?? 'left') === 'right';
    $vaQ = fn (string $k) => is_scalar(request($k)) ? (string) request($k) : '';
    $vaSorts = ['latest' => 'ล่าสุด', 'popular' => 'ยอดนิยม', 'price_low' => 'ราคาต่ำ → สูง', 'price_high' => 'ราคาสูง → ต่ำ', 'rating' => 'คะแนนสูงสุด', 'name' => 'ชื่อสินค้า'];
    $vaTotal = (isset($products) && method_exists($products, 'total')) ? $products->total() : (isset($products) ? $products->count() : 0);
    $vaFav = [];
    if (! $vaPreview && auth()->check() && isset($products) && $products->count() > 0) {
        try {
            $vaFav = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', collect(method_exists($products, 'items') ? $products->items() : $products)->pluck('id')->filter()->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $vaFav = [];
        }
    }
@endphp

<section class="sf-wrap sf-section" id="all-products">
    <div style="display:grid; grid-template-columns:{{ $vaSidebar ? ($vaRight ? 'minmax(0, 1fr) 290px' : '290px minmax(0, 1fr)') : 'minmax(0, 1fr)' }}; gap:18px; align-items:start;" class="va-layout">
        @if($vaSidebar)
            <aside style="order:{{ $vaRight ? 2 : 1 }};" class="sf-sticky">
                <form method="GET" action="{{ $vaPreview ? '#' : route('store.show', $store->store_slug) }}#all-products" class="tp-card sf-stack" style="gap:12px;"
                      @if($vaPreview) onsubmit="return false;" @endif>
                    <div class="tp-section-h"><i class="fas fa-filter" style="color:var(--store-a);"></i> ค้นหา &amp; กรอง</div>
                    <label style="display:flex; flex-direction:column; gap:4px;">
                        <span class="tp-muted" style="font-size:12px; font-weight:600;">ค้นหาสินค้า</span>
                        <input type="search" name="search" value="{{ $vaQ('search') }}" class="tp-input" placeholder="พิมพ์ชื่อสินค้า..." style="height:44px;" @disabled($vaPreview)>
                    </label>
                    @if(isset($categories) && $categories->count() > 0)
                        <label style="display:flex; flex-direction:column; gap:4px;">
                            <span class="tp-muted" style="font-size:12px; font-weight:600;">หมวดหมู่</span>
                            <select name="category" class="tp-input" style="height:44px; padding:0 12px;" @disabled($vaPreview)>
                                <option value="">ทุกหมวดหมู่</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->slug }}" @selected($vaQ('category') === (string) $cat->slug)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <label style="display:flex; flex-direction:column; gap:4px;">
                            <span class="tp-muted" style="font-size:12px; font-weight:600;">ราคาต่ำสุด</span>
                            <input type="number" name="min_price" min="0" value="{{ $vaQ('min_price') }}" class="tp-input" style="height:44px;" inputmode="numeric" @disabled($vaPreview)>
                        </label>
                        <label style="display:flex; flex-direction:column; gap:4px;">
                            <span class="tp-muted" style="font-size:12px; font-weight:600;">สูงสุด</span>
                            <input type="number" name="max_price" min="0" value="{{ $vaQ('max_price') }}" class="tp-input" style="height:44px;" inputmode="numeric" @disabled($vaPreview)>
                        </label>
                    </div>
                    <label style="display:flex; flex-direction:column; gap:4px;">
                        <span class="tp-muted" style="font-size:12px; font-weight:600;">เรียงตาม</span>
                        <select name="sort" class="tp-input" style="height:44px; padding:0 12px;" @disabled($vaPreview)>
                            @foreach($vaSorts as $sk => $sl)
                                <option value="{{ $sk }}" @selected(($vaQ('sort') ?: 'latest') === $sk)>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="sf-btn3d is-block" style="background:linear-gradient(180deg, var(--store-a), var(--store-b));" @disabled($vaPreview)><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
                    @if(! $vaPreview && request()->hasAny(['search', 'category', 'min_price', 'max_price', 'sort']))
                        <a href="{{ route('store.show', $store->store_slug) }}#all-products" class="tp-btn" style="text-decoration:none;">ล้างตัวกรอง</a>
                    @endif
                </form>
            </aside>
        @endif

        <div style="order:{{ $vaRight ? 1 : 2 }}; min-width:0;">
            <div class="sf-section-h" style="margin-bottom:12px;">
                <h2 class="sf-title" style="font-size:20px;"><i class="fas fa-bag-shopping" style="color:var(--store-a);"></i> สินค้าทั้งหมด <span class="tp-muted tp-num" style="font-size:14px; font-weight:600;">({{ number_format($vaTotal) }})</span></h2>
            </div>
            @if(isset($products) && $products->count() > 0)
                <div class="sf-grid" style="--sf-min:{{ $vaMin }};">
                    @foreach($products as $product)
                        <x-theme-v4.product-card :product="$product" :preview="$vaPreview"
                            :favorited="in_array((int) ($product->id ?? 0), $vaFav, true)"
                            :href="$vaPreview ? '#' : route('store.product', ['storeSlug' => $store->store_slug, 'productSlug' => $product->slug ?: $product->id])" />
                    @endforeach
                </div>
                @if(! $vaPreview && method_exists($products, 'hasPages') && $products->hasPages())
                    <div style="margin-top:20px;">{{ $products->fragment('all-products')->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}</div>
                @endif
            @else
                <div class="tp-card" style="text-align:center; padding:40px 16px;">
                    <div style="font-size:44px;" aria-hidden="true">🔎</div>
                    <h3 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ไม่พบสินค้า</h3>
                    <p class="tp-muted" style="margin:0;">ลองค้นหาด้วยคำอื่น หรือเลือกหมวดหมู่อื่น</p>
                </div>
            @endif
        </div>
    </div>
</section>

@once
@push('styles')
<style>
    @media (max-width: 900px) { .va-layout { grid-template-columns:minmax(0, 1fr) !important; } .va-layout > aside { order:1 !important; } }
</style>
@endpush
@endonce
