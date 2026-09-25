{{--
 | ชุดสไตล์ + สคริปต์กลางของหน้าร้านค้า ธีม V4 (การ์ดสินค้า ปุ่ม 3D ชิป ไทม์ไลน์ ฯลฯ)
 | ใส่ครั้งเดียวต่อหน้า:  <x-theme-v4.shop-kit />   (ใส่ซ้ำก็ไม่เป็นไร — ใช้ @once)
 |
 | สคริปต์กลาง window.tpShop:
 |   tpShop.addToCart(productId, qty, { redirect: url|null, button: el|null })  → Promise<boolean>
 |   tpShop.toggleFavorite(productId, buttonEl)
 |   tpShop.notify(message, 'success'|'error'|'info')
 | สีทั้งหมดอ้าง CSS variables ของธีม (ค่า hex มีเฉพาะเป็นค่าสำรองของ var())
 --}}
@php
    $skRoute = fn (string $name, $params = [], string $fallback = '') => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : $fallback;
    $skConfig = [
        'isGuest' => ! auth()->check(),
        'loginUrl' => $skRoute('login', [], url('/login')),
        'cartUrl' => $skRoute('cart.index', [], url('/cart')),
        'cartAddUrl' => $skRoute('cart.add', [], url('/cart/add')),
        'favoriteUrl' => $skRoute('user.interactions.products.favorite', ['product' => '__ID__']),
    ];
@endphp

@once
@push('styles')
<style>
    .sf-wrap { width:100%; max-width:1240px; margin:0 auto; padding-left:clamp(16px,3vw,32px); padding-right:clamp(16px,3vw,32px); box-sizing:border-box; }
    .sf-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(var(--sf-min, 176px), 1fr)); gap:14px; }
    @media (max-width: 520px) { .sf-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; } }
    .sf-card { position:relative; display:flex; flex-direction:column; min-width:0; border-radius:20px; overflow:hidden; background:var(--card-bg); box-shadow:var(--card-shadow); border:var(--card-border); transition:transform .2s ease, box-shadow .2s ease; }
    .sf-card:hover { transform:translateY(-3px); box-shadow:var(--card-shadow-hover); }
    .sf-card > a { text-decoration:none; color:inherit; }
    .sf-media { position:relative; aspect-ratio:1/1; background:var(--surf); box-shadow:var(--inset-sm); overflow:hidden; display:grid; place-items:center; }
    .sf-media img { width:100%; height:100%; object-fit:cover; transition:transform .5s ease; }
    .sf-card:hover .sf-media img { transform:scale(1.05); }
    .sf-media .sf-noimg { font-size:34px; opacity:.4; }
    .sf-badges { position:absolute; top:8px; left:8px; display:flex; flex-direction:column; align-items:flex-start; gap:4px; z-index:2; }
    .sf-badge { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:800; line-height:1; padding:5px 8px; border-radius:8px; color:var(--on-accent, #fff); box-shadow:0 2px 6px rgba(0,0,0,.16); text-shadow:0 1px 1px rgba(0,0,0,.18); }
    .sf-badge-sale { background:linear-gradient(135deg, var(--sf-sale, #e0564f), var(--sf-sale2, #c43d63)); }
    .sf-badge-gold { background:linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .sf-badge-deep { background:linear-gradient(135deg, var(--deep1), var(--deep2)); }
    .sf-badge-ok { background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467)); }
    .sf-badge-ink { background:color-mix(in srgb, var(--ink) 82%, transparent); }
    .sf-fav { position:absolute; top:8px; right:8px; z-index:3; width:38px; height:38px; border-radius:50%; border:0; cursor:pointer; display:grid; place-items:center; color:var(--ink2); background:color-mix(in srgb, var(--surf) 88%, transparent); box-shadow:var(--raise); transition:transform .12s ease, color .15s ease; }
    .sf-fav:active { transform:scale(.92); }
    .sf-fav.is-on { color:var(--sf-sale, #e0564f); }
    .sf-ribbon { position:absolute; left:0; right:0; bottom:0; padding:5px 8px; font-size:11px; font-weight:700; text-align:center; color:var(--on-accent, #fff); background:linear-gradient(90deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467)); }
    .sf-ribbon.is-warm { background:linear-gradient(90deg, var(--accent2), var(--deep2)); }
    .sf-body { padding:11px 12px 12px; display:flex; flex-direction:column; gap:6px; flex:1; min-width:0; }
    .sf-name { font-size:13px; font-weight:600; color:var(--ink); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.8em; overflow-wrap:anywhere; }
    .sf-price { font-family:var(--tp-font-num); font-size:17px; font-weight:800; color:var(--deep1); }
    .sf-compare { font-size:11.5px; color:var(--ink2); text-decoration:line-through; }
    .sf-meta { font-size:11px; color:var(--ink2); display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .sf-stars { color:var(--sf-star, #e6b347); }
    .sf-points { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:700; padding:3px 8px; border-radius:20px; color:var(--deep2); background:var(--a2soft); }
    .sf-card-cta { padding:0 12px 12px; }
    .sf-card-cta .tp-btn { width:100%; height:40px; }

    .sf-btn3d { position:relative; display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:48px; padding:0 22px; border:0; border-radius:15px; cursor:pointer; font-family:inherit; font-weight:800; font-size:14px; line-height:1.1; color:var(--on-accent, #fff); text-decoration:none; text-shadow:0 1px 2px rgba(0,0,0,.2);
        background:linear-gradient(180deg, color-mix(in srgb, var(--accent1) 78%, white) 0%, var(--accent1) 42%, var(--deep1) 100%);
        box-shadow:inset 0 1px 0 rgba(255,255,255,.5), inset 0 -2px 0 rgba(0,0,0,.10), 0 4px 0 color-mix(in srgb, var(--deep1) 72%, black), 0 10px 22px rgba(0,0,0,.18);
        transition:transform .1s ease, box-shadow .1s ease, filter .15s ease; }
    .sf-btn3d:hover { filter:brightness(1.04); }
    .sf-btn3d:active { transform:translateY(3px); box-shadow:inset 0 1px 0 rgba(255,255,255,.4), 0 1px 0 color-mix(in srgb, var(--deep1) 72%, black), 0 4px 10px rgba(0,0,0,.16); }
    .sf-btn3d.is-alt { background:linear-gradient(180deg, color-mix(in srgb, var(--accent2) 78%, white) 0%, var(--accent2) 42%, var(--deep2) 100%);
        box-shadow:inset 0 1px 0 rgba(255,255,255,.5), inset 0 -2px 0 rgba(0,0,0,.10), 0 4px 0 color-mix(in srgb, var(--deep2) 72%, black), 0 10px 22px rgba(0,0,0,.18); }
    .sf-btn3d.is-soft { color:var(--ink); text-shadow:none; background:linear-gradient(180deg, var(--sl, var(--surf)) 0%, var(--surf) 100%);
        box-shadow:inset 0 1px 0 rgba(255,255,255,.5), 0 3px 0 var(--sd, rgba(0,0,0,.12)), 0 8px 18px rgba(0,0,0,.10); }
    .sf-btn3d[disabled], .sf-btn3d.is-disabled { filter:grayscale(.7); opacity:.55; cursor:not-allowed; transform:none; }
    .sf-btn3d.is-block { width:100%; }

    .sf-chip { display:inline-flex; align-items:center; gap:6px; min-height:40px; padding:8px 14px; border:0; border-radius:20px; cursor:pointer; font-family:inherit; font-size:12.5px; font-weight:600; white-space:nowrap; text-decoration:none; color:var(--ink); background:var(--surf); box-shadow:var(--raise); transition:transform .1s ease; }
    .sf-chip:active { transform:translateY(1px); box-shadow:var(--inset-sm); }
    .sf-chip.is-on { color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); text-shadow:0 1px 2px rgba(0,0,0,.14); }
    .sf-scroll { display:flex; gap:10px; overflow-x:auto; padding:4px 2px 12px; -webkit-overflow-scrolling:touch; }

    .sf-section { padding-top:28px; padding-bottom:8px; }
    .sf-section-h { display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .sf-kicker { font-size:12px; font-weight:700; letter-spacing:.4px; color:var(--deep2); }
    .sf-title { margin:4px 0 0; font-size:clamp(20px, 3.4vw, 26px); font-weight:800; letter-spacing:-.3px; color:var(--ink); }
    .sf-h1 { margin:0; font-size:clamp(24px, 4.4vw, 34px); font-weight:800; letter-spacing:-.5px; line-height:1.2; color:var(--ink); overflow-wrap:anywhere; }
    .sf-breadcrumb { display:flex; flex-wrap:wrap; gap:6px; align-items:center; font-size:12.5px; color:var(--ink2); }
    .sf-breadcrumb a { color:var(--ink2); text-decoration:none; }
    .sf-breadcrumb a:hover { color:var(--deep1); }

    .sf-2col { display:grid; grid-template-columns:minmax(0, 1fr) 370px; gap:20px; align-items:start; }
    .sf-sticky { position:sticky; top:96px; }
    @media (max-width: 980px) { .sf-2col { grid-template-columns:1fr; } .sf-sticky { position:static; } }
    .sf-stack { display:flex; flex-direction:column; gap:16px; min-width:0; }
    .sf-row { display:flex; justify-content:space-between; align-items:baseline; gap:12px; font-size:13.5px; color:var(--ink2); }
    .sf-row strong { color:var(--ink); }
    .sf-total { display:flex; justify-content:space-between; align-items:baseline; gap:12px; padding-top:12px; border-top:1px solid color-mix(in srgb, var(--ink2) 22%, transparent); }
    .sf-total .tp-num { font-size:26px; font-weight:800; color:var(--deep1); }

    .sf-note { padding:12px 14px; border-radius:14px; font-size:13px; line-height:1.65; color:var(--ink); }
    .sf-note-ok { background:color-mix(in srgb, var(--sf-ok, #4f9e7e) 14%, transparent); }
    .sf-note-warn { background:color-mix(in srgb, var(--accent2) 18%, transparent); }
    .sf-note-err { background:color-mix(in srgb, var(--sf-sale, #d9534f) 14%, transparent); }
    .sf-note-info { background:var(--a1soft); }

    .sf-opt { display:flex; gap:12px; align-items:flex-start; width:100%; padding:14px; border-radius:16px; cursor:pointer; text-align:left; font-family:inherit; color:var(--ink); background:var(--surf); box-shadow:var(--raise); border:2px solid transparent; transition:box-shadow .15s ease, border-color .15s ease; }
    .sf-opt.is-on { box-shadow:var(--inset-sm); border-color:var(--accent1); }
    .sf-opt.is-disabled { opacity:.5; cursor:not-allowed; }
    .sf-opt .sf-radio { width:20px; height:20px; flex:none; margin-top:2px; border-radius:50%; box-shadow:var(--inset-sm); background:var(--bg); display:grid; place-items:center; }
    .sf-opt.is-on .sf-radio::after { content:''; width:10px; height:10px; border-radius:50%; background:linear-gradient(135deg, var(--accent1), var(--accent2)); }

    .sf-tl { list-style:none; margin:0; padding:0; }
    .sf-tl-item { position:relative; display:flex; gap:14px; padding-bottom:18px; }
    .sf-tl-item:not(:last-child)::before { content:''; position:absolute; left:19px; top:42px; bottom:0; width:2px; background:color-mix(in srgb, var(--accent1) 45%, transparent); }
    .sf-tl-item.is-todo:not(:last-child)::before { background:color-mix(in srgb, var(--ink2) 22%, transparent); }
    .sf-tl-dot { width:40px; height:40px; flex:none; border-radius:50%; display:grid; place-items:center; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow:var(--raise); }
    .sf-tl-item.is-todo .sf-tl-dot { color:var(--ink2); background:var(--surf); box-shadow:var(--inset-sm); }
    .sf-tl-item.is-now .sf-tl-dot { animation:tpPulse 1.8s ease-in-out infinite; }

    .sf-thumb { width:64px; height:64px; flex:none; border-radius:14px; overflow:hidden; background:var(--surf); box-shadow:var(--inset-sm); display:grid; place-items:center; }
    .sf-thumb img { width:100%; height:100%; object-fit:cover; }
    .sf-qty { display:inline-flex; align-items:center; border-radius:14px; background:var(--surf); box-shadow:var(--inset-sm); }
    .sf-qty button { width:44px; height:44px; border:0; background:transparent; cursor:pointer; font-size:18px; font-weight:800; color:var(--ink); font-family:inherit; }
    .sf-qty input { width:54px; height:44px; text-align:center; border:0; background:transparent; font-weight:800; font-size:16px; color:var(--ink); outline:0; font-family:var(--tp-font-num); -moz-appearance:textfield; }
    .sf-qty input::-webkit-outer-spin-button, .sf-qty input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
    .sf-map { height:320px; border-radius:16px; overflow:hidden; box-shadow:var(--inset-sm); background:var(--surf); position:relative; z-index:0; }
    .sf-prose { color:var(--ink); font-size:14.5px; line-height:1.8; overflow-wrap:anywhere; }
    .sf-prose img { max-width:100%; height:auto; border-radius:12px; }
    .sf-prose a { color:var(--deep1); }
    .sf-prose ul { list-style:disc; padding-left:1.4em; margin:.4em 0; }
    .sf-prose ol { list-style:decimal; padding-left:1.4em; margin:.4em 0; }
    .sf-prose p { margin:.35em 0; }
    .sf-prose h1, .sf-prose h2, .sf-prose h3, .sf-prose h4 { font-weight:800; margin:.8em 0 .3em; line-height:1.4; }
    .sf-prose table { width:100%; border-collapse:collapse; display:block; overflow-x:auto; }
    .sf-prose td, .sf-prose th { padding:6px 8px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 22%, transparent); }
    .sf-tabs { display:flex; gap:6px; overflow-x:auto; padding:6px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm); }
    .sf-tab { flex:1; min-width:max-content; min-height:42px; padding:0 14px; border:0; border-radius:12px; cursor:pointer; font-family:inherit; font-size:13px; font-weight:700; color:var(--ink2); background:transparent; }
    .sf-tab.is-on { color:var(--deep1); background:var(--card-bg); box-shadow:var(--raise); }
    @media (max-width: 640px) { .sf-hide-sm { display:none !important; } }
    @media (min-width: 641px) { .sf-only-sm { display:none !important; } }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const cfg = @json($skConfig);
        const pending = new Set();

        function csrf() {
            const m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.content : '';
        }

        function notify(message, type) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));
        }

        async function addToCart(productId, quantity, opts) {
            opts = opts || {};
            if (cfg.isGuest) {
                notify('กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า', 'info');
                setTimeout(function () { window.location.href = cfg.loginUrl; }, 700);
                return false;
            }
            if (pending.has(productId)) {
                return false;
            }
            pending.add(productId);
            const btn = opts.button || null;
            if (btn) { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }

            try {
                const res = await fetch(cfg.cartAddUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: Math.max(1, parseInt(quantity || 1, 10) || 1),
                        attributes: opts.attributes || {}
                    })
                });

                if (res.status === 419) {
                    notify('หน้านี้เปิดค้างไว้นานเกินไป กำลังโหลดใหม่...', 'error');
                    setTimeout(function () { window.location.reload(); }, 900);
                    return false;
                }
                if (res.status === 401) {
                    window.location.href = cfg.loginUrl;
                    return false;
                }

                let data = null;
                try { data = await res.json(); } catch (e) { data = null; }

                if (!res.ok || !data || data.success === false) {
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                        return false;
                    }
                    notify((data && data.message) || 'เพิ่มสินค้าลงตะกร้าไม่สำเร็จ กรุณาลองใหม่', 'error');
                    return false;
                }

                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                if (opts.redirect) {
                    window.location.href = opts.redirect;
                } else {
                    notify(data.message || 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว', 'success');
                }
                return true;
            } catch (e) {
                notify('เชื่อมต่อไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่', 'error');
                return false;
            } finally {
                pending.delete(productId);
                if (btn) { btn.disabled = false; btn.removeAttribute('aria-busy'); }
            }
        }

        function paintFavorite(btn, on) {
            btn.dataset.favorited = on ? '1' : '0';
            btn.classList.toggle('is-on', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            const label = on ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            const icon = btn.querySelector('i');
            if (icon) { icon.classList.toggle('fas', on); icon.classList.toggle('far', !on); }
        }

        async function toggleFavorite(productId, btn) {
            if (cfg.isGuest) {
                notify('กรุณาเข้าสู่ระบบเพื่อบันทึกสินค้าที่ชอบ', 'info');
                setTimeout(function () { window.location.href = cfg.loginUrl; }, 700);
                return;
            }
            if (!btn || btn.dataset.busy === '1' || !cfg.favoriteUrl) {
                return;
            }
            btn.dataset.busy = '1';
            const was = btn.dataset.favorited === '1';
            paintFavorite(btn, !was);

            try {
                const res = await fetch(cfg.favoriteUrl.replace('__ID__', encodeURIComponent(productId)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '{}'
                });
                const type = res.headers.get('content-type') || '';
                if (!res.ok || type.indexOf('application/json') === -1) {
                    throw new Error('favorite_failed');
                }
                const data = await res.json();
                paintFavorite(btn, !!data.favorited);
                notify(data.message || (data.favorited ? 'เพิ่มในรายการโปรดแล้ว' : 'นำออกจากรายการโปรดแล้ว'), 'success');
            } catch (e) {
                paintFavorite(btn, was);
                notify('บันทึกรายการโปรดไม่สำเร็จ กรุณาลองใหม่', 'error');
            } finally {
                btn.dataset.busy = '0';
            }
        }

        window.tpShop = { addToCart: addToCart, toggleFavorite: toggleFavorite, notify: notify, csrf: csrf, config: cfg };
        if (typeof window.showNotification !== 'function') {
            window.showNotification = function (message, type) { notify(message, type); };
        }
    })();
</script>
@endpush
@endonce
