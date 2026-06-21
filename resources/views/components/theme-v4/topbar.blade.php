{{--
 | แถบบน (Topbar) — ธีมนวลทองคำ V4
 | อยู่ภายใน x-data="tpShell" จึงใช้ toggleSidebar()/isMobile ได้ และใช้ $store.tp สำหรับธีม
 --}}
@php
    $user = auth()->user();
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $isAdmin  = \Illuminate\Support\Str::startsWith($currentRoute, 'admin.');
    $isSeller = \Illuminate\Support\Str::startsWith($currentRoute, 'seller.');
    $isUser   = \Illuminate\Support\Str::startsWith($currentRoute, 'user.');
    $hasAdminAccess  = $user && $user->hasRole(['admin', 'super_admin']);
    $hasSellerAccess = $user && $user->hasRole(['seller', 'super_admin']);
    $hasUserAccess   = (bool) $user;
    $available = ($hasAdminAccess ? 1 : 0) + ($hasSellerAccess ? 1 : 0) + ($hasUserAccess ? 1 : 0);
    $profileRoute  = $isSeller ? 'seller.profile' : ($isUser ? 'user.profile' : 'admin.profile.index');
    $settingsRoute = $isSeller ? 'seller.settings' : ($isUser ? 'user.settings' : 'admin.settings.index');
@endphp

<header style="display:flex; align-items:center; flex-wrap:wrap; gap:12px 16px; padding:14px clamp(12px,3vw,24px); position:sticky; top:0; z-index:40; background:var(--card-bg); -webkit-backdrop-filter:var(--card-blur); backdrop-filter:var(--card-blur); border-bottom:1px solid color-mix(in srgb, var(--ink2) 16%, transparent); transition:background .4s ease;">

    {{-- ปุ่มเมนู (ซ่อน/แสดง sidebar หรือเปิด drawer) --}}
    <button @click="toggleSidebar()" type="button" class="tp-icon-btn" title="ซ่อน/แสดงเมนู">
        <i class="fas fa-bars"></i>
    </button>

    {{-- โลโก้ --}}
    <a href="{{ route('admin.dashboard') }}" style="display:flex; align-items:center; gap:12px; flex:none; text-decoration:none; color:inherit;">
        <span class="tp-tile" style="width:42px; height:42px; border-radius:14px; font-family:var(--tp-font-num); font-weight:800; font-size:15px;">TP</span>
        <span style="line-height:1.2;" class="hidden sm:block">
            <span style="display:block; font-weight:700; font-size:15px; letter-spacing:.2px;">ไทยพรอมต์ แอฟฟิลิเอต</span>
            <span style="display:block; font-size:10px; color:var(--ink2); font-weight:600; letter-spacing:.3px;">TP-AFFILIATE · หลังบ้าน</span>
        </span>
    </a>

    {{-- ค้นหา --}}
    <div class="tp-well" style="flex:1; min-width:140px; max-width:420px; display:flex; align-items:center; gap:10px; padding:10px 15px;">
        <i class="fas fa-magnifying-glass" style="color:var(--ink2); font-size:13px;"></i>
        <input type="text" placeholder="ค้นหาสมาชิก · รหัส · ออเดอร์…" class="tp-input" style="box-shadow:none; background:transparent; padding:0;">
    </div>

    {{-- ปุ่มขวา --}}
    <div style="display:flex; align-items:center; gap:8px; margin-left:auto;">
        {{-- สลับโทนสี --}}
        <button @click="$store.tp.cyclePalette()" type="button" class="tp-icon-btn" title="สลับโทนสี (palette)">
            <span style="width:18px; height:18px; border-radius:50%; background:linear-gradient(135deg,var(--accent1) 50%,var(--accent2) 50%); box-shadow:inset 1px 1px 2px rgba(255,255,255,.5);"></span>
        </button>
        {{-- สลับสว่าง/มืด --}}
        <button @click="$store.tp.toggleDark()" type="button" class="tp-icon-btn" title="สลับโหมดสว่าง/มืด" style="color:var(--deep2);">
            <i class="fas fa-moon" x-show="!$store.tp.dark"></i>
            <i class="fas fa-sun" x-show="$store.tp.dark" x-cloak></i>
        </button>
        {{-- หน้าบ้าน --}}
        <a href="{{ url('/') }}" class="tp-btn hidden md:inline-flex" title="ไปหน้าบ้าน">
            หน้าบ้าน <span style="font-family:var(--tp-font-num);">→</span>
        </a>

        {{-- โปรไฟล์ --}}
        @auth
        <div x-data="{ open:false }" style="position:relative;">
            <button @click="open=!open" type="button" class="tp-icon-btn" style="width:auto; padding:0 6px 0 6px; gap:8px; display:flex; align-items:center;">
                <img src="{{ $user->profile_picture_url }}" alt="{{ $user->name }}"
                     style="width:32px; height:32px; border-radius:10px; object-fit:cover;"
                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(mb_substr($user->name,0,1)) }}&background=e6b347&color=fff&size=64';">
                <span class="hidden md:block" style="font-size:13px; font-weight:600; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $user->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:10px; color:var(--ink2);" :class="open && 'rotate-180'"></i>
            </button>

            <div x-show="open" x-cloak @click.outside="open=false"
                 x-transition.origin.top.right
                 class="tp-card"
                 style="position:absolute; right:0; top:calc(100% + 8px); width:268px; padding:8px; z-index:60;">

                {{-- หัว --}}
                <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:14px; background:color-mix(in srgb, var(--accent1) 12%, transparent); margin-bottom:6px;">
                    <img src="{{ $user->profile_picture_url }}" alt="" style="width:46px; height:46px; border-radius:12px; object-fit:cover;"
                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(mb_substr($user->name,0,1)) }}&background=e6b347&color=fff&size=96';">
                    <div style="min-width:0; flex:1;">
                        <div style="font-weight:700; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $user->name }}</div>
                        <div style="font-size:11px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $user->email }}</div>
                        <span class="tp-pill tp-pill-gold" style="margin-top:5px;">{{ $isSeller ? 'ร้านค้า' : ($isUser ? 'ผู้ใช้' : 'แอดมิน') }}</span>
                    </div>
                </div>

                {{-- สลับแดชบอร์ด --}}
                @if($available > 1)
                <div style="font-size:10px; font-weight:700; color:var(--ink2); padding:6px 10px 4px; letter-spacing:.4px;">สลับแดชบอร์ด</div>
                @if($hasAdminAccess)
                    <a href="{{ route('admin.dashboard') }}" class="tpdd-item">
                        <span class="tp-tile" style="width:30px;height:30px;border-radius:9px;font-size:12px;"><i class="fas fa-gauge-high"></i></span> แอดมิน
                    </a>
                @endif
                @if($hasSellerAccess)
                    <a href="{{ route('seller.dashboard') }}" class="tpdd-item">
                        <span class="tp-tile" style="width:30px;height:30px;border-radius:9px;font-size:12px;"><i class="fas fa-store"></i></span> ร้านค้า
                    </a>
                @endif
                @if($hasUserAccess)
                    <a href="{{ route('user.dashboard') }}" class="tpdd-item">
                        <span class="tp-tile" style="width:30px;height:30px;border-radius:9px;font-size:12px;"><i class="fas fa-user"></i></span> ผู้ใช้งาน
                    </a>
                @endif
                <hr class="tp-divider" style="margin:6px 4px;">
                @endif

                {{-- เมนูลัด --}}
                @if(\Illuminate\Support\Facades\Route::has($profileRoute))
                    <a href="{{ route($profileRoute) }}" class="tpdd-item"><span class="tpdd-ic"><i class="fas fa-user-circle"></i></span> โปรไฟล์</a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has($settingsRoute))
                    <a href="{{ route($settingsRoute) }}" class="tpdd-item"><span class="tpdd-ic"><i class="fas fa-cog"></i></span> ตั้งค่า</a>
                @endif
                <button @click="$store.tp.studioOpen = true; open=false" type="button" class="tpdd-item" style="width:100%; text-align:left; background:transparent; border:0; cursor:pointer; font-family:inherit;">
                    <span class="tpdd-ic"><i class="fas fa-palette"></i></span> ปรับแต่งธีม
                </button>
                <hr class="tp-divider" style="margin:6px 4px;">

                {{-- ออกจากระบบ --}}
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="tpdd-item" style="width:100%; text-align:left; background:transparent; border:0; cursor:pointer; font-family:inherit; color:#d9534f;">
                        <span class="tpdd-ic" style="color:#d9534f;"><i class="fas fa-arrow-right-from-bracket"></i></span> ออกจากระบบ
                    </button>
                </form>
            </div>
        </div>
        @else
        <a href="{{ route('login') }}" class="tp-btn">เข้าสู่ระบบ</a>
        <a href="{{ route('register') }}" class="tp-btn tp-btn-primary hidden sm:inline-flex">สมัครสมาชิก</a>
        @endauth
    </div>
</header>
{{-- หมายเหตุ: คลาส .tpdd-item / .tpdd-ic อยู่ใน resources/css/theme-v4.css --}}
