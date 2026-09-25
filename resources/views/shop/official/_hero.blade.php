{{--
 | ส่วนหัวของหน้าร้านค้าทางการ (ใช้ร่วม: index / category / featured)
 | ตัวแปร: $ohTitle, $ohSubtitle, $ohStats (array ของ ['value' => int, 'label' => string]), $ohCrumbs (array ของ ['label' => , 'href' => |null]),
 |         $ohSearch (ค่าค้นหาเดิม|null — null = ไม่แสดงช่องค้นหา), $ohAction (ปลายทางฟอร์มค้นหา)
 --}}
<section class="sf-wrap" style="padding-top:22px;">
    <div style="position:relative; overflow:hidden; border-radius:28px; padding:clamp(22px, 4vw, 40px); color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--deep1) 0%, var(--accent1) 55%, var(--accent2) 100%); box-shadow:0 18px 44px rgba(0,0,0,.2);">
        <div aria-hidden="true" style="position:absolute; inset:0; background:radial-gradient(520px 260px at 92% -10%, rgba(255,255,255,.28), transparent 60%), radial-gradient(420px 220px at 0% 110%, rgba(255,255,255,.14), transparent 60%);"></div>
        <div style="position:relative;">
            <nav class="sf-breadcrumb" aria-label="เส้นทาง" style="color:rgba(255,255,255,.85);">
                @foreach($ohCrumbs as $crumb)
                    @if(! $loop->first)<span aria-hidden="true">/</span>@endif
                    @if(! empty($crumb['href']))
                        <a href="{{ $crumb['href'] }}" style="color:inherit;">{{ $crumb['label'] }}</a>
                    @else
                        <span style="font-weight:700;">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:18px; margin-top:10px;">
                <div style="min-width:0; max-width:640px;">
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:800; background:rgba(255,255,255,.2);"><i class="fas fa-crown"></i> OFFICIAL STORE · ของแท้ 100%</span>
                    <h1 style="margin:10px 0 6px; font-size:clamp(24px, 4.6vw, 38px); font-weight:800; line-height:1.15; text-shadow:0 2px 8px rgba(0,0,0,.2); overflow-wrap:anywhere;">{{ $ohTitle }}</h1>
                    <p style="margin:0; opacity:.94; line-height:1.6;">{{ $ohSubtitle }}</p>
                </div>
                @if(! empty($ohStats))
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        @foreach($ohStats as $stat)
                            <div style="padding:12px 16px; border-radius:18px; text-align:center; background:rgba(255,255,255,.18); min-width:92px;">
                                <div class="tp-num" style="font-size:22px; font-weight:800;">{{ number_format((int) $stat['value']) }}</div>
                                <div style="font-size:11.5px; opacity:.9;">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($ohSearch !== null)
                <form method="GET" action="{{ $ohAction }}" role="search" style="display:flex; gap:8px; margin-top:18px; max-width:560px;">
                    <label for="oh-search" style="position:absolute; left:-9999px;">ค้นหาสินค้าร้านทางการ</label>
                    <input id="oh-search" type="search" name="search" value="{{ $ohSearch }}" class="tp-input" placeholder="ค้นหาสินค้าในร้านทางการ..." style="height:48px;">
                    <button type="submit" class="sf-btn3d is-soft" style="color:var(--deep1);"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
                </form>
            @endif
        </div>
    </div>
</section>
