{{--
 | แถบบนพื้นที่สมาชิก — ธีมโนวา (กรมท่าเสมอ ทั้งโหมดสว่าง/มืด)
 | อยู่ใน x-data="nvmShell" ของ layouts/user-nova จึงเรียก openMenu() ได้ และใช้ $store.nvmTheme สลับโหมดมืด
 | ช่องค้นหา = ค้นเมนูของฉัน (จาก config/menus.php ผ่าน MenuService) แล้วพาไปหน้านั้น
 |
 | props: menus  array  เมนูที่ MenuService::getMenuForRole('user') คืนมา (layout ส่งให้ ไม่ต้องดึงซ้ำ)
 --}}
@props(['menus' => []])
@php
    use Illuminate\Support\Facades\Route as NvmRoute;

    $nvmUser = auth()->user();
    $nvmRoute = fn (string $name) => NvmRoute::has($name) ? route($name) : null;

    // ยอดกระเป๋าเงินจริงอยู่ที่ตาราง wallets (users ไม่มีคอลัมน์ wallet_balance)
    $nvmWallet = (float) ($nvmUser?->wallet?->balance ?? 0);

    // จำนวนแจ้งเตือนที่ยังไม่อ่าน — ถ้านับไม่ได้ (ตาราง/scope มีปัญหา) ก็ไม่แสดงตัวเลข ดีกว่าทำหน้าพัง
    try {
        $nvmUnread = (int) ($nvmUser?->unread_notifications_count ?? 0);
    } catch (\Throwable $e) {
        $nvmUnread = 0;
    }

    $nvmRankName = $nvmUser?->currentRank ? ($nvmUser->currentRank->name_th ?: $nvmUser->currentRank->name) : null;
    $nvmInitial = mb_substr(trim((string) ($nvmUser->name ?? '')) ?: '?', 0, 1);

    // สลับแดชบอร์ด (ชุดเดียวกับแถบบนธีม V4)
    $nvmCanAdmin = $nvmUser && $nvmUser->hasRole(['admin', 'super_admin']);
    $nvmCanSeller = $nvmUser && $nvmUser->hasRole(['seller', 'super_admin']);

    // รายการค้นหาเมนู: เมนูเดี่ยว + เมนูย่อยที่มีลิงก์จริง (ข้ามเส้นคั่น/หัวข้อ)
    $nvmSearch = [];
    foreach ($menus as $nvmGroup) {
        $nvmGroupUrl = $nvmGroup['url'] ?? null;
        if ($nvmGroupUrl && $nvmGroupUrl !== '#' && empty($nvmGroup['submenu'])) {
            $nvmSearch[] = ['label' => $nvmGroup['label'] ?? '', 'url' => $nvmGroupUrl, 'group' => '', 'ext' => ! empty($nvmGroup['external'])];
        }
        foreach (($nvmGroup['submenu'] ?? []) as $nvmSub) {
            $nvmSubUrl = is_array($nvmSub) ? ($nvmSub['url'] ?? null) : null;
            if (! $nvmSubUrl || $nvmSubUrl === '#' || in_array($nvmSub['label'] ?? '', ['', '---'], true)) {
                continue;
            }
            $nvmSearch[] = ['label' => $nvmSub['label'], 'url' => $nvmSubUrl, 'group' => $nvmGroup['label'] ?? '', 'ext' => ! empty($nvmSub['external'])];
        }
    }

    $nvmCartUrl = $nvmRoute('cart.index');
    $nvmCartCountUrl = $nvmRoute('cart.count');
    $nvmWalletUrl = $nvmRoute('user.wallet.index');
    $nvmNotiUrl = $nvmRoute('user.notifications.index');
@endphp

<header class="nvm-top">
    <div class="nvm-top__in">
        <button class="nvm-ibtn nvm-top__burger" type="button" @click="openMenu()" aria-label="เปิดเมนู" aria-controls="nvm-side" :aria-expanded="menuOpen">
            <svg class="nvm-ico"><use href="#nvm-i-menu"/></svg>
        </button>

        <a class="nvm-top__logo" href="{{ url('/') }}" title="กลับหน้าแรก Thai Prompt">
            <x-theme-v4.brand-logo :height="34" variant="dark" />
        </a>

        {{-- ค้นหาเมนู --}}
        <div class="nvm-top__search"
             x-data="{ q: '', all: @js($nvmSearch), get hits() { const k = this.q.trim().toLowerCase(); return k ? this.all.filter(i => (i.label + ' ' + i.group).toLowerCase().includes(k)).slice(0, 8) : []; }, go() { const h = this.hits[0]; if (h) { h.ext ? window.open(h.url, '_blank', 'noopener') : (location.href = h.url); } } }"
             @keydown.escape="q = ''" @click.outside="q = ''">
            <label class="nvm-sr" for="nvm-search">ค้นหาเมนู</label>
            <svg class="nvm-ico"><use href="#nvm-i-search"/></svg>
            <input id="nvm-search" type="search" x-model="q" @keydown.enter.prevent="go()" autocomplete="off" placeholder="ค้นหาเมนู เช่น ถอนเงิน ทีมของฉัน…">
            <div class="nvm-drop nvm-drop--search" x-show="q.trim() !== ''" x-cloak>
                <template x-for="h in hits" :key="h.url + h.label">
                    <a class="nvm-drop__item" :href="h.url" :target="h.ext ? '_blank' : null" :rel="h.ext ? 'noopener' : null">
                        <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>
                        <span x-text="h.label"></span>
                        <small x-text="h.group"></small>
                    </a>
                </template>
                <div class="nvm-drop__empty" x-show="hits.length === 0">ไม่พบเมนูที่ตรงกับ “<span x-text="q.trim()"></span>”</div>
            </div>
        </div>

        <div class="nvm-top__right">
            @if($nvmWalletUrl)
                <a class="nvm-wchip" href="{{ $nvmWalletUrl }}" title="กระเป๋าเงิน">
                    <img src="{{ asset('images/nova/icons/wallet.webp') }}" alt="" width="30" height="30">
                    <span><small>ยอดเงิน</small><b class="nvm-num">฿{{ number_format($nvmWallet, 2) }}</b></span>
                </a>
            @endif

            @if($nvmCartUrl)
                <a class="nvm-ibtn nvm-top__cart" href="{{ $nvmCartUrl }}" aria-label="ตะกร้าสินค้า"
                   x-data="{ c: 0, load() { @if($nvmCartCountUrl) fetch(@js($nvmCartCountUrl), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.ok ? r.json() : null).then(d => { if (d) this.c = Number(d.count) || 0; }).catch(() => {}); @endif } }"
                   x-init="load()" @cart-updated.window="load()">
                    <svg class="nvm-ico"><use href="#nvm-i-cart"/></svg>
                    <span class="nvm-ibtn__count nvm-num" x-show="c > 0" x-cloak x-text="c > 99 ? '99+' : c"></span>
                </a>
            @endif

            <button class="nvm-ibtn" type="button" @click="$store.nvmTheme.toggle()" :aria-label="$store.nvmTheme.dark ? 'เปลี่ยนเป็นโหมดสว่าง' : 'เปลี่ยนเป็นโหมดมืด'" title="สลับโหมดสว่าง/มืด">
                <svg class="nvm-ico" x-show="!$store.nvmTheme.dark"><use href="#nvm-i-moon"/></svg>
                <svg class="nvm-ico" x-show="$store.nvmTheme.dark" x-cloak><use href="#nvm-i-sun"/></svg>
            </button>

            @if($nvmNotiUrl)
                <a class="nvm-ibtn" href="{{ $nvmNotiUrl }}" aria-label="การแจ้งเตือน{{ $nvmUnread > 0 ? ' ยังไม่อ่าน '.$nvmUnread.' รายการ' : '' }}">
                    <svg class="nvm-ico"><use href="#nvm-i-bell"/></svg>
                    @if($nvmUnread > 0)
                        <span class="nvm-ibtn__count nvm-num">{{ $nvmUnread > 99 ? '99+' : $nvmUnread }}</span>
                    @endif
                </a>
            @endif

            @if($nvmUser)
                <div style="position:relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button class="nvm-me" type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" aria-label="บัญชีของฉัน">
                        <span class="nvm-avatar">
                            {{ $nvmInitial }}
                            <img src="{{ $nvmUser->profile_picture_url }}" alt="" onerror="this.remove()">
                        </span>
                        <span class="nvm-me__txt">
                            <b>{{ $nvmUser->name }}</b>
                            <small>{{ $nvmRankName ? 'ระดับ'.$nvmRankName : 'สมาชิก' }}</small>
                        </span>
                    </button>

                    <div class="nvm-drop nvm-drop--me" x-show="open" x-cloak x-transition.opacity role="menu">
                        <div class="nvm-drop__head">
                            <span class="nvm-avatar">
                                {{ $nvmInitial }}
                                <img src="{{ $nvmUser->profile_picture_url }}" alt="" onerror="this.remove()">
                            </span>
                            <span style="min-width:0">
                                <b>{{ $nvmUser->name }}</b>
                                <small>{{ $nvmUser->email }}</small>
                            </span>
                        </div>

                        @if($nvmCanAdmin || $nvmCanSeller)
                            <div class="nvm-drop__label nvm-latin">สลับแดชบอร์ด</div>
                            @if($nvmCanAdmin && $nvmRoute('admin.dashboard'))
                                <a class="nvm-drop__item" href="{{ route('admin.dashboard') }}" role="menuitem"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>หลังบ้านแอดมิน</a>
                            @endif
                            @if($nvmCanSeller && $nvmRoute('seller.dashboard'))
                                <a class="nvm-drop__item" href="{{ route('seller.dashboard') }}" role="menuitem"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>ร้านค้าของฉัน</a>
                            @endif
                            <hr>
                        @endif

                        @if($nvmRoute('user.profile'))
                            <a class="nvm-drop__item" href="{{ route('user.profile') }}" role="menuitem"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg>โปรไฟล์</a>
                        @endif
                        @if($nvmRoute('user.settings'))
                            <a class="nvm-drop__item" href="{{ route('user.settings') }}" role="menuitem"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>ตั้งค่าบัญชี</a>
                        @endif
                        <a class="nvm-drop__item" href="{{ url('/') }}" role="menuitem"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>ไปหน้าแรก</a>
                        <hr>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="nvm-drop__item nvm-drop__item--out" role="menuitem"><svg class="nvm-ico"><use href="#nvm-i-logout"/></svg>ออกจากระบบ</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</header>
