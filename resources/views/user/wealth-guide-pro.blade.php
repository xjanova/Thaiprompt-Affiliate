{{--
    คู่มือเส้นทางเศรษฐี ฉบับ PRO (ธีมโนวาเต็ม) — route closure user.wealth-guide-pro
    · Mind Map / กระแสเงิน 3 มิติ วาดโดย resources/js/wealth-guide-pro.js (Three.js) ลงใน #wealth-3d-mindmap / #wealth-3d-income-flow
      ปุ่ม #toggle-rotate / #reset-camera และฟังก์ชัน focusMindMapNode() มาจากไฟล์ JS นั้น — ห้ามเปลี่ยน id
    · เครื่องคำนวณรายได้ใช้ id calc-* / result-* (สคริปต์ท้ายหน้า)
    · สีประจำช่องทาง/ยศส่งเข้า CSS ผ่านตัวแปร --c เท่านั้น
--}}
@extends('layouts.user-nova')

@section('title', 'คู่มือเส้นทางเศรษฐี PRO')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ลิงก์ที่ใช้ในหน้านี้ (กรองเฉพาะ route ที่มีจริง) ─────────
    $genealogyUrl = RouteFacade::has('user.mlm.genealogy') ? route('user.mlm.genealogy') : null;
    $incomeSimUrl = RouteFacade::has('user.mlm.income-simulator') ? route('user.mlm.income-simulator') : null;
    $marketplaceUrl = RouteFacade::has('user.marketplace.products') ? route('user.marketplace.products') : null;
    $wealthGuideUrl = RouteFacade::has('user.wealth-guide') ? route('user.wealth-guide') : null;
    $referralPageUrl = RouteFacade::has('user.mlm.referral') ? route('user.mlm.referral') : null;

    // ── ลิงก์แนะนำของสมาชิกคนนี้ ─────────────────────────────
    // 🐛 เดิมอ่าน auth()->user()->affiliate_code ซึ่งไม่มีในตาราง users → ทุกคนได้ลิงก์ ?ref=YOUR_CODE (แนะนำแล้วไม่ผูกผู้แนะนำ)
    //    ใช้ member_code ของสมาชิก MLM และหน้าสมัครแบบเดียวกับ MlmDashboardController::referral
    $mlmMember = auth()->user()->mlmMembers()->first();
    $referralUrl = null;
    if ($mlmMember && $mlmMember->member_code) {
        $referralUrl = RouteFacade::has('line.registration.register')
            ? route('line.registration.register', ['ref' => $mlmMember->member_code])
            : (RouteFacade::has('register') ? route('register', ['ref' => $mlmMember->member_code]) : url('/register?ref='.urlencode($mlmMember->member_code)));
    }

    $heroStats = [
        ['6+', 'ช่องทางรายได้'],
        ['∞', 'ไม่จำกัดรายได้'],
        ['8', 'ระดับยศ'],
        ['3D', 'เครื่องมือแบบโต้ตอบ'],
    ];

    // ปุ่มนำทางใน Mind Map — [ไอคอน, label ที่ JS ใช้หา node, สี]
    $mapNodes = [
        ['cash', 'รายได้ตรง', '#b8862b'],
        ['swap', 'Binary Matching', '#c2453f'],
        ['award', 'Rank Bonus', '#2f8a63'],
        ['users', 'Sponsor Bonus', '#8d5fc7'],
    ];

    $flowSummary = [
        ['Direct', '฿15,000', '#b8862b'],
        ['Binary', '฿8,000', '#c2453f'],
        ['Rank', '฿25,000', '#2f8a63'],
        ['Sponsor', '฿5,000', '#8d5fc7'],
        ['Marketplace', '฿12,000', '#e0a52e'],
        ['Team', '฿18,000', '#5689b8'],
    ];

    // ── ตารางยศ Rank Bonus (8 ระดับ) — [ชื่อ, รางวัล, สี] ─────────
    $ranks = [
        ['Bronze', '100฿ | 5%', '#b07a3c'],
        ['Silver', '500฿ | 7.5%', '#8a93a0'],
        ['Gold', '2,000฿ + 500/ด. | 10%', '#e0a52e'],
        ['Platinum', '10,000฿ + 2,000/ด. | 15%', '#5689b8'],
        ['Diamond', '50,000฿ + 5,000/ด. | 20%', '#2f8a63'],
        ['Crown', '100,000฿ + 10,000/ด. | 25%', '#c08a2e'],
        ['Royal', '300,000฿ + 25,000/ด. | 30%', '#8d5fc7'],
        ['Legend', '1,000,000฿ + 100,000/ด. | 35%', '#c2453f'],
    ];

    $potential = [
        ['ระดับเริ่มต้น', '฿10,000+', '#2f8a63'],
        ['ระดับกลาง', '฿50,000+', '#e0a52e'],
        ['ระดับสูง (Diamond)', '฿200,000+', '#c2453f'],
    ];

    // ── Roadmap 5 ขั้น — [ชื่อ, ระดับ, สี, เงื่อนไข[], รางวัล] ─────
    $roadmap = [
        ['Bronze', 'ระดับเริ่มต้น', '#b07a3c', ['สมัครสมาชิก', 'มียอดซื้อขั้นต่ำ 5,000฿', 'แนะนำ 3 คน'], 'รางวัล: 5,000฿ + 1,000฿/เดือน'],
        ['Silver', 'ระดับกลาง', '#8a93a0', ['ทีม 15 คน', 'ยอดขายรวม 50,000฿/เดือน', 'มี Bronze 3 คน'], 'รางวัล: 20,000฿ + 5,000฿/เดือน'],
        ['Gold', 'ระดับสูง', '#e0a52e', ['ทีม 50 คน', 'ยอดขายรวม 150,000฿/เดือน', 'มี Silver 3 คน'], 'รางวัล: 50,000฿ + 15,000฿/เดือน'],
        ['Platinum', 'ระดับมืออาชีพ', '#8d5fc7', ['ทีม 150 คน', 'ยอดขายรวม 500,000฿/เดือน', 'มี Gold 3 คน'], 'รางวัล: 200,000฿ + 50,000฿/เดือน'],
        ['Diamond', 'ระดับสูงสุด', '#2f8a63', ['ทีม 500 คน', 'ยอดขายรวม 2,000,000฿/เดือน', 'มี Platinum 3 คน'], 'รางวัล: 500,000฿ + 150,000฿/เดือน'],
    ];

    $timeline = [
        ['send', 'Fast Track', '6-12 เดือน', 'ทำงานเต็มเวลา + สร้างทีมเร็ว', '#2f8a63'],
        ['up', 'Standard', '1-2 ปี', 'ทำงานสม่ำเสมอ + สร้างทีมแข็งแกร่ง', '#e0a52e'],
        ['clock', 'Steady', '2-3 ปี', 'ทำงานเป็นรายได้เสริม', '#5689b8'],
    ];
@endphp

@section('content')

{{-- ── ฮีโร่ ─────────────────────────────────────────────── --}}
<section class="nvm-ph" aria-labelledby="nvm-um-wp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด</a>
            <div class="nvm-eyebrow nvm-latin">Wealth guide pro</div>
            <h1 id="nvm-um-wp-h">คู่มือเส้นทางเศรษฐี PRO</h1>
            <p class="nvm-ph__lead">ฉบับสมบูรณ์ พร้อมแผนภาพ 3 มิติ เครื่องคำนวณ และ Roadmap สู่ Diamond</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="#income-overview"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>เริ่มต้นสร้างรายได้</a>
                <a class="nvm-btn nvm-btn--ghost" href="#3d-mindmap"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>ดู 3D Mind Map</a>
                <a class="nvm-btn nvm-btn--ghost" href="#income-calculator"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>คำนวณรายได้</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-kpis">
    @foreach($heroStats as [$num, $label])
        <div class="nvm-card nvm-kpi"><div class="nvm-kpi__lbl">{{ $label }}</div><div class="nvm-kpi__val nvm-num">{{ $num }}</div></div>
    @endforeach
</div>

{{-- ── 3D Mind Map ──────────────────────────────────────── --}}
<section id="3d-mindmap" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="nvm-um-wp-map">
    <div class="nvm-sec-h"><div><small class="nvm-latin">3D mind map</small><h2 id="nvm-um-wp-map">Mind Map แผนธุรกิจ 3 มิติ</h2></div></div>
    <p class="nvm-desc">ภาพรวมระบบรายได้ทั้งหมดในมุมมอง 3 มิติ — หมุน ซูม และสำรวจได้อย่างอิสระ</p>

    <div class="nvm-um-3d nvm-gap-t">
        <div id="wealth-3d-mindmap" class="nvm-um-3d__stage"></div>
        <div class="nvm-um-3d__ctrl">
            <button id="toggle-rotate" type="button" class="nvm-btn nvm-btn--ghost nvm-btn--sm">หมุนอัตโนมัติ</button>
            <button id="reset-camera" type="button" class="nvm-btn nvm-btn--ghost nvm-btn--sm">รีเซ็ตมุมมอง</button>
        </div>
        <p class="nvm-um-3d__hint"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ลาก = หมุนดู · ล้อเมาส์ = ซูม · คลิกจุด = ดูรายละเอียด</p>
    </div>

    <div class="nvm-um-nodes">
        @foreach($mapNodes as [$icon, $label, $clr])
            <button type="button" class="nvm-um-node-btn" style="--c: {{ $clr }}" onclick="window.focusMindMapNode && window.focusMindMapNode(@js($label))">
                <svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg><span>{{ $label }}</span>
            </button>
        @endforeach
    </div>
</section>

{{-- ── 3D Income Flow ───────────────────────────────────── --}}
<section id="income-flow" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="nvm-um-wp-flow">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Income flow</small><h2 id="nvm-um-wp-flow">กระแสเงินสด 3 มิติ</h2></div></div>
    <p class="nvm-desc">ดูการไหลของเงินจากทุกช่องทางรายได้ (ตัวเลขตัวอย่าง)</p>
    <div class="nvm-um-3d nvm-gap-t">
        <div id="wealth-3d-income-flow" class="nvm-um-3d__stage"></div>
    </div>
    <div class="nvm-um-flow">
        @foreach($flowSummary as [$label, $amount, $clr])
            <div class="nvm-mini" style="--c: {{ $clr }}"><small>{{ $label }}</small><b class="nvm-num">{{ $amount }}</b></div>
        @endforeach
    </div>
</section>

{{-- ── 6 ช่องทางรายได้ ───────────────────────────────────── --}}
<section id="income-overview" class="nvm-um-chap" aria-labelledby="nvm-um-wp-6">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Six income streams</small><h2 id="nvm-um-wp-6">6 ช่องทางรายได้ครบวงจร</h2></div></div>
    <p class="nvm-desc">สร้างรายได้จากหลากหลายช่องทาง ไม่พึ่งพาช่องทางเดียว มั่นคงและยั่งยืน</p>

    <div class="nvm-um-streams nvm-gap-t">
        {{-- 1. Direct Commission --}}
        <article class="nvm-card nvm-box nvm-um-stream" style="--c: #b8862b">
            <div class="nvm-um-panel__h">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg></span>
                <div><h3 class="nvm-um-panel__t">1. Direct Commission (รายได้ตรง)</h3><span class="nvm-chip nvm-chip--gold">10%-1% ต่อระดับ (10 ชั้น)</span></div>
            </div>
            <p class="nvm-desc">รายได้จากการขายตรงและทีมของคุณ แบบ Unilevel ลึกถึง 10 ชั้น</p>
            <dl class="nvm-kv nvm-um-kv">
                @foreach([['ชั้นที่ 1 (Direct)', '10%'], ['ชั้นที่ 2', '5%'], ['ชั้นที่ 3-10', '1-3%']] as [$layer, $pct])
                    <dt>{{ $layer }}</dt><dd class="nvm-num">{{ $pct }}</dd>
                @endforeach
            </dl>
            <div class="nvm-um-callout is-ok">
                <b>ตัวอย่างรายได้</b>
                <p>สมาชิกชั้น 1 ซื้อ 1,000฿ = คุณได้ <strong>100฿</strong><br>มี 10 คน = <strong>1,000฿/เดือน</strong></p>
            </div>
            <div class="nvm-um-acts">
                @if($genealogyUrl)<a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ $genealogyUrl }}">ดูโครงสร้างทีม</a>@endif
                @if($incomeSimUrl)<a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ $incomeSimUrl }}">คำนวณรายได้</a>@endif
            </div>
        </article>

        {{-- 2. Binary Matching --}}
        <article class="nvm-card nvm-box nvm-um-stream" style="--c: #c2453f">
            <div class="nvm-um-panel__h">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg></span>
                <div><h3 class="nvm-um-panel__t">2. Binary Matching Bonus</h3><span class="nvm-chip nvm-chip--bad">100฿ ต่อคู่ (Match 50%)</span></div>
            </div>
            <p class="nvm-desc">รายได้จากคู่ขาซ้าย-ขวา ยิ่งสมดุลยิ่งได้มาก ระบบ Binary Tree</p>
            <dl class="nvm-kv nvm-um-kv">
                <dt>ขาซ้าย: 5 คน</dt><dd class="nvm-num">5 PV</dd>
                <dt>ขาขวา: 5 คน</dt><dd class="nvm-num">5 PV</dd>
                <dt><b>ได้ 5 คู่</b></dt><dd class="nvm-num">= 500฿</dd>
            </dl>
            <div class="nvm-um-callout is-ok">
                <b>กลยุทธ์</b>
                <p>สร้างทีมให้สมดุลทั้ง 2 ขา จะได้รายได้สูงสุด<br><strong>50,000 PV จับคู่ = 25,000฿</strong></p>
            </div>
            <div class="nvm-um-acts">
                @if($genealogyUrl)<a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ $genealogyUrl }}?view=binary">ดู Binary Tree</a>@endif
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" onclick="nvmUmCalcFocus('calc-binary-pairs')">คำนวณโบนัส</button>
            </div>
        </article>

        {{-- 3. Rank Achievement --}}
        <article class="nvm-card nvm-box nvm-um-stream" style="--c: #2f8a63">
            <div class="nvm-um-panel__h">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg></span>
                <div><h3 class="nvm-um-panel__t">3. Rank Achievement Bonus</h3><span class="nvm-chip nvm-chip--ok">100฿ - 1,000,000฿ (8 ระดับ)</span></div>
            </div>
            <p class="nvm-desc">โบนัสพิเศษเมื่อคุณขึ้นยศ ทั้งโบนัสครั้งเดียวและรายเดือน</p>
            <ul class="nvm-um-ranklist" aria-label="โบนัสตามยศ 8 ระดับ">
                @foreach($ranks as [$name, $reward, $clr])
                    <li style="--c: {{ $clr }}"><span>{{ $name }}</span><b class="nvm-num">{{ $reward }}</b></li>
                @endforeach
            </ul>
            <div class="nvm-um-callout is-ok">
                <b>ยศสูงสุด Legend</b>
                <p>Legend = <strong>1,000,000฿ โบนัสเลื่อนตำแหน่ง!</strong><br>+ <strong>100,000฿ ทุกเดือน</strong></p>
            </div>
            <div class="nvm-um-acts"><a class="nvm-btn nvm-btn--line nvm-btn--sm" href="#rank-roadmap">ดู Roadmap สู่ Diamond</a></div>
        </article>

        {{-- 4. Sponsorship --}}
        <article class="nvm-card nvm-box nvm-um-stream" style="--c: #8d5fc7">
            <div class="nvm-um-panel__h">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg></span>
                <div><h3 class="nvm-um-panel__t">4. Sponsorship Bonus</h3><span class="nvm-chip nvm-chip--info">10-20% ของยอดแรก</span></div>
            </div>
            <p class="nvm-desc">โบนัสพิเศษเมื่อคุณแนะนำสมาชิกใหม่ รับทันทีจากยอดซื้อแรก</p>
            <dl class="nvm-kv nvm-um-kv">
                <dt>สมาชิกธรรมดา<small>แนะนำ 1 คน ซื้อ 5,000฿ = คุณได้ 500฿</small></dt><dd class="nvm-num">10%</dd>
                <dt>สมาชิกระดับสูง<small>แนะนำ 1 คน ซื้อ 20,000฿ = คุณได้ 4,000฿</small></dt><dd class="nvm-num">20%</dd>
            </dl>
            <div class="nvm-um-callout is-ok">
                <b>เคล็ดลับ</b>
                <p>แนะนำ <strong>10 คน/เดือน</strong> คนละ 5,000฿<br>= <strong>5,000฿ โบนัสทันที</strong></p>
            </div>
            <div class="nvm-um-acts">
                @if($referralUrl)
                    <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" data-ref-url="{{ $referralUrl }}" onclick="window.nvmCopy(this.dataset.refUrl, 'คัดลอกลิงก์แนะนำแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอกลิงก์แนะนำ</button>
                    @if($referralPageUrl)<a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ $referralPageUrl }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>QR Code</a>@endif
                @else
                    <p class="nvm-note">ยังไม่มีรหัสสมาชิก MLM — เข้าร่วมแผนก่อนจึงจะได้ลิงก์แนะนำของคุณ</p>
                @endif
            </div>
        </article>

        {{-- 5. Marketplace Affiliate --}}
        <article class="nvm-card nvm-box nvm-um-stream" style="--c: #e0a52e">
            <div class="nvm-um-panel__h">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-cart"/></svg></span>
                <div><h3 class="nvm-um-panel__t">5. Marketplace Affiliate</h3><span class="nvm-chip nvm-chip--warn">2-20% ตามแพลตฟอร์ม</span></div>
            </div>
            <p class="nvm-desc">รายได้จากการเป็น Affiliate ของ Lazada, Shopee, TikTok Shop</p>
            <dl class="nvm-kv nvm-um-kv">
                <dt>Lazada</dt><dd class="nvm-num">2-10%</dd>
                <dt>Shopee</dt><dd class="nvm-num">5-15%</dd>
                <dt>TikTok Shop</dt><dd class="nvm-num">8-20%</dd>
            </dl>
            <div class="nvm-um-callout is-ok">
                <b>โอกาส</b>
                <p>แชร์สินค้า <strong>ราคา 1,000฿</strong> มี 100 คนซื้อ<br>Commission 10% = <strong>10,000฿</strong></p>
            </div>
            @if($marketplaceUrl)
                <div class="nvm-um-acts"><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ $marketplaceUrl }}"><svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>เริ่มต้น Marketplace Affiliate</a></div>
            @endif
        </article>

        {{-- 6. Team Override --}}
        <article class="nvm-card nvm-box nvm-um-stream" style="--c: #5689b8">
            <div class="nvm-um-panel__h">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg></span>
                <div><h3 class="nvm-um-panel__t">6. Team Override Bonus</h3><span class="nvm-chip nvm-chip--info">5-10% จากทีม</span></div>
            </div>
            <p class="nvm-desc">รายได้จากยอดขายรวมของทีม — ยิ่งทีมใหญ่ยิ่งได้มาก</p>
            <dl class="nvm-kv nvm-um-kv">
                <dt>ทีมรวม</dt><dd class="nvm-num">100 คน</dd>
                <dt>ยอดขายเฉลี่ย</dt><dd class="nvm-num">2,000฿/คน/เดือน</dd>
                <dt>ยอดรวม</dt><dd class="nvm-num">200,000฿</dd>
                <dt><b>Override 5%</b></dt><dd class="nvm-num">= 10,000฿</dd>
            </dl>
            <div class="nvm-um-callout is-ok">
                <b>กลยุทธ์</b>
                <p>สร้างทีม <strong>500 คน</strong><br>= <strong>50,000฿/เดือน</strong> จาก Override</p>
            </div>
            <div class="nvm-um-acts">
                @if($genealogyUrl)<a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ $genealogyUrl }}">ดูทีม</a>@endif
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" onclick="nvmUmCalcFocus('calc-team-size')">คำนวณ</button>
            </div>
        </article>
    </div>

    <div class="nvm-um-total nvm-gap-t">
        <h3>รายได้รวมที่เป็นไปได้ (ต่อเดือน)</h3>
        <div class="nvm-um-flow">
            @foreach($potential as [$lvl, $amt, $clr])
                <div class="nvm-mini" style="--c: {{ $clr }}"><small>{{ $lvl }}</small><b class="nvm-num">{{ $amt }}</b></div>
            @endforeach
        </div>
        <p class="nvm-note">* รายได้ขึ้นอยู่กับความพยายามและการทำงานของคุณและทีม</p>
    </div>
</section>

{{-- ── เครื่องคำนวณรายได้ ─────────────────────────────────── --}}
<section id="income-calculator" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="nvm-um-wp-calc">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Calculator</small><h2 id="nvm-um-wp-calc">เครื่องคำนวณรายได้</h2></div></div>
    <p class="nvm-desc">ประมาณการรายได้ตามเป้าหมายของคุณ (ใช้อัตราตามที่แสดงในหน้านี้)</p>

    <div class="nvm-um-grid nvm-um-grid--wide nvm-gap-t">
        <form class="nvm-form" onsubmit="event.preventDefault(); calculateIncome();">
            <div class="nvm-field">
                <label class="nvm-label" for="calc-direct-sales">จำนวนสมาชิกที่แนะนำต่อเดือน</label>
                <input type="number" id="calc-direct-sales" value="5" min="0" class="nvm-input nvm-num" inputmode="numeric">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="calc-avg-purchase">ยอดซื้อเฉลี่ยต่อคน (฿)</label>
                <input type="number" id="calc-avg-purchase" value="3000" min="0" step="100" class="nvm-input nvm-num" inputmode="numeric">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="calc-team-size">ขนาดทีมปัจจุบัน (คน)</label>
                <input type="number" id="calc-team-size" value="20" min="0" class="nvm-input nvm-num" inputmode="numeric">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="calc-binary-pairs">Binary Pairs ต่อสัปดาห์</label>
                <input type="number" id="calc-binary-pairs" value="10" min="0" class="nvm-input nvm-num" inputmode="numeric">
            </div>
            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>คำนวณรายได้</button>
        </form>

        <div class="nvm-um-panel" aria-live="polite">
            <b class="nvm-um-panel__t">รายได้ประมาณการต่อเดือน</b>
            <dl class="nvm-kv nvm-um-kv">
                <dt>Direct Commission (10%)</dt><dd class="nvm-num" id="result-direct">฿0</dd>
                <dt>Binary Matching (100฿/คู่)</dt><dd class="nvm-num" id="result-binary">฿0</dd>
                <dt>Sponsor Bonus (15%)</dt><dd class="nvm-num" id="result-sponsor">฿0</dd>
                <dt>Team Override (5%)</dt><dd class="nvm-num" id="result-team">฿0</dd>
            </dl>
            <div class="nvm-um-total__sum">
                <small>รายได้รวม</small>
                <b class="nvm-num" id="result-total">฿0</b>
                <small>รายได้ต่อปี (โดยประมาณ) <span class="nvm-num" id="result-yearly">฿0</span></small>
            </div>
        </div>
    </div>
</section>

{{-- ── Roadmap สู่ Diamond ───────────────────────────────── --}}
<section id="rank-roadmap" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="nvm-um-wp-road">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Roadmap</small><h2 id="nvm-um-wp-road">Roadmap สู่ Diamond</h2></div></div>
    <p class="nvm-desc">เส้นทางชัดเจนจากมือใหม่สู่ระดับ Diamond พร้อมเป้าหมายที่วัดผลได้</p>

    <div class="nvm-um-ranks nvm-gap-t">
        @foreach($roadmap as $i => [$name, $tier, $clr, $conditions, $reward])
            <article class="nvm-um-rank" style="--c: {{ $clr }}">
                <div class="nvm-um-rank__h">
                    <span class="nvm-um-rank__no nvm-num">{{ $i + 1 }}</span>
                    <div><b>{{ $name }}</b><small>{{ $tier }}</small></div>
                </div>
                <div>
                    <small class="nvm-um-rank__lbl">เงื่อนไข</small>
                    <ul class="nvm-um-list is-check">
                        @foreach($conditions as $cond)<li>{{ $cond }}</li>@endforeach
                    </ul>
                    <p class="nvm-um-rank__reward nvm-num">{{ $reward }}</p>
                </div>
            </article>
        @endforeach
    </div>

    <h3 class="nvm-um-h3">กี่นานถึงขึ้น Diamond?</h3>
    <div class="nvm-um-grid">
        @foreach($timeline as [$icon, $title, $time, $desc, $clr])
            <div class="nvm-um-panel nvm-um-time" style="--c: {{ $clr }}">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                <b class="nvm-um-panel__t">{{ $title }}</b>
                <b class="nvm-num nvm-um-time__v">{{ $time }}</b>
                <p class="nvm-desc">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ── เริ่มต้นวันนี้ ──────────────────────────────────────── --}}
<section class="nvm-card nvm-refer nvm-box nvm-um-cta" aria-labelledby="nvm-um-wp-go">
    <img class="nvm-refer__coins" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="120" height="120" loading="lazy">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Start today</small><h2 id="nvm-um-wp-go">เริ่มต้นวันนี้!</h2></div></div>
    <p>ทุกการเดินทางเริ่มต้นด้วยก้าวแรก — ก้าวของคุณคืออะไร?</p>
    <ol class="nvm-um-go">
        <li><b>ศึกษาระบบ</b><small>เรียนรู้ทุกช่องทางรายได้</small>@if($wealthGuideUrl)<a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ $wealthGuideUrl }}">อ่านคู่มือฉบับเต็ม</a>@endif</li>
        <li><b>วางแผน</b><small>คำนวณเป้าหมายของคุณ</small>@if($incomeSimUrl)<a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ $incomeSimUrl }}">ใช้เครื่องคำนวณ</a>@endif</li>
        <li><b>ลงมือทำ</b><small>เริ่มแชร์และสร้างทีม</small>@if($referralUrl)<button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" data-ref-url="{{ $referralUrl }}" onclick="window.nvmCopy(this.dataset.refUrl, 'คัดลอกลิงก์แนะนำแล้ว')">คัดลอกลิงก์แนะนำ</button>@endif</li>
    </ol>
    <div class="nvm-hero__actions">
        <a class="nvm-btn nvm-btn--gold" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>ไปที่แดชบอร์ด</a>
    </div>
</section>

@vite(['resources/js/wealth-guide-pro.js'])
@endsection

@push('scripts')
<script>
// เลื่อนไปที่เครื่องคำนวณแล้วโฟกัสช่องที่เกี่ยวข้อง (แทน prompt()/alert() เดิม)
function nvmUmCalcFocus(inputId) {
    const el = document.getElementById(inputId);
    if (!el) return;
    document.getElementById('income-calculator')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    setTimeout(() => { el.focus(); el.select?.(); }, 450);
}

// เครื่องคำนวณรายได้ — ใช้อัตราเดียวกับที่หน้าแสดง
// 🐛 เดิมคิด Direct 30% และ Binary 1,000฿/คู่ ขัดกับเนื้อหาในหน้า (10% และ 100฿/คู่) → ตัวเลขเกินจริง 3-10 เท่า
function calculateIncome() {
    const num = (id) => Math.max(0, parseInt(document.getElementById(id)?.value, 10) || 0);
    const directSales = num('calc-direct-sales');
    const avgPurchase = num('calc-avg-purchase');
    const teamSize = num('calc-team-size');
    const binaryPairs = num('calc-binary-pairs');

    const directCommission = directSales * avgPurchase * 0.10;
    const binaryBonus = binaryPairs * 100 * 4; // ต่อสัปดาห์ × 4 สัปดาห์
    const sponsorBonus = directSales * avgPurchase * 0.15;
    const teamOverride = teamSize * avgPurchase * 0.05;
    const total = directCommission + binaryBonus + sponsorBonus + teamOverride;

    const fmt = (n) => '฿' + Math.floor(n).toLocaleString('th-TH');
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = fmt(v); };
    set('result-direct', directCommission);
    set('result-binary', binaryBonus);
    set('result-sponsor', sponsorBonus);
    set('result-team', teamOverride);
    set('result-yearly', total * 12);
    animateValue('result-total', total, fmt);
}

// อนิเมชันตัวเลขรวม (ยกเลิกรอบเก่าถ้ากดซ้ำเร็วๆ)
let nvmUmAnim = 0;
function animateValue(id, end, fmt) {
    const el = document.getElementById(id);
    if (!el) return;
    cancelAnimationFrame(nvmUmAnim);
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = fmt(end); return; }
    const t0 = performance.now();
    const step = (t) => {
        const p = Math.min((t - t0) / 800, 1);
        el.textContent = fmt(end * p);
        if (p < 1) nvmUmAnim = requestAnimationFrame(step);
    };
    nvmUmAnim = requestAnimationFrame(step);
}

document.addEventListener('DOMContentLoaded', calculateIncome);
</script>
@endpush
