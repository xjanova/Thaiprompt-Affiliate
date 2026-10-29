{{--
 | แถบล่างมือถือ (< 768px) — ซุ้มกนก + เหรียญตรากลาง ชุดเดียวกับแอป Thai Prompt
 | ปุ่มกลาง = สแกน QR (qr-barcode.scanner) — ยังไม่มีระบบ "สแกนจ่าย" จึงไม่ตั้งชื่อว่าจ่าย
 | ลิงก์ไหนไม่มี route ในระบบ จะไม่แสดงปุ่มนั้น
 --}}
@php
    $nvmTabRoute = request()->route()?->getName() ?? '';
    $nvmTabs = collect([
        ['route' => 'user.home', 'label' => 'หน้าหลัก', 'icon' => 'home', 'match' => ['user.home']],
        ['route' => 'user.dashboard', 'label' => 'แดชบอร์ด', 'icon' => 'grid', 'match' => ['user.dashboard']],
        ['route' => 'qr-barcode.scanner', 'label' => 'สแกน', 'icon' => 'qr', 'mid' => true, 'match' => ['qr-barcode.scanner']],
        ['route' => 'user.wallet.index', 'label' => 'กระเป๋า', 'icon' => 'wallet', 'match' => ['user.wallet.']],
        ['route' => 'user.mlm.team', 'label' => 'ทีม', 'icon' => 'users', 'match' => ['user.mlm.team', 'user.mlm.genealogy']],
    ])->filter(fn ($t) => \Illuminate\Support\Facades\Route::has($t['route']));
@endphp

@if($nvmTabs->isNotEmpty())
<nav class="nvm-tabbar" aria-label="เมนูลัด">
    <img class="nvm-tabbar__arch" src="{{ asset('images/nova/brand/tabbar-kanok-arch.webp') }}" alt="" aria-hidden="true">
    <div class="nvm-tabbar__in" style="grid-template-columns:repeat({{ $nvmTabs->count() }},minmax(0,1fr))">
        @foreach($nvmTabs as $tab)
            @php
                $tabOn = collect($tab['match'])->contains(fn ($m) => str_ends_with($m, '.') ? str_starts_with($nvmTabRoute, $m) : $nvmTabRoute === $m);
            @endphp
            <a class="nvm-tab {{ ! empty($tab['mid']) ? 'nvm-tab--mid' : '' }} {{ $tabOn ? 'is-on' : '' }}" href="{{ route($tab['route']) }}" @if($tabOn) aria-current="page" @endif>
                @if(! empty($tab['mid']))
                    <span class="nvm-tab__m"><img src="{{ asset('images/nova/brand/tabbar-kanok-medallion.webp') }}" alt=""><svg class="nvm-ico"><use href="#nvm-i-{{ $tab['icon'] }}"/></svg></span>
                @else
                    <svg class="nvm-ico"><use href="#nvm-i-{{ $tab['icon'] }}"/></svg>
                @endif
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
@endif
