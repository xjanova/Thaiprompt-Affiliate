@extends('layouts.user-v4')

@section('title', 'คู่มือเสริมทางเศรษฐี Pro - ฉบับสมบูรณ์พร้อม 3D Visualization')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ลิงก์ที่ใช้ในหน้านี้ (กรองเฉพาะ route ที่มีจริง) ─────────
    $genealogyUrl   = RouteFacade::has('user.mlm.genealogy') ? route('user.mlm.genealogy') : null;
    $incomeSimUrl   = RouteFacade::has('user.mlm.income-simulator') ? route('user.mlm.income-simulator') : null;
    $marketplaceUrl = RouteFacade::has('user.marketplace.products') ? route('user.marketplace.products') : null;
    $wealthGuideUrl = RouteFacade::has('user.wealth-guide') ? route('user.wealth-guide') : null;

    // ── ตารางยศ Rank Bonus (8 ระดับ) ───────────────────────────
    $ranks = [
        ['🥉', 'Bronze',   '100฿ | 5%',                   '#cd7f32'],
        ['🥈', 'Silver',   '500฿ | 7.5%',                 '#9aa3ad'],
        ['🥇', 'Gold',     '2,000฿ + 500/ด. | 10%',       '#e0a52e'],
        ['💎', 'Platinum', '10,000฿ + 2,000/ด. | 15%',    '#5689b8'],
        ['💠', 'Diamond',  '50,000฿ + 5,000/ด. | 20%',    '#5aa07e'],
        ['👑', 'Crown',    '100,000฿ + 10,000/ด. | 25%',  '#c08a2e'],
        ['🏆', 'Royal',    '300,000฿ + 25,000/ด. | 30%',  '#9b6fb0'],
        ['🌟', 'Legend',   '1,000,000฿ + 100,000/ด. | 35%','#d9534f'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px;">
                <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:28px;">💎</span>
                <div style="flex:1; min-width:220px;">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">WEALTH GUIDE PRO · ฉบับสมบูรณ์</div>
                    <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">คู่มือเสริมทางเศรษฐี Pro</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ฉบับสมบูรณ์ พร้อม 3D Visualization</div>
                </div>
                <a href="{{ route('user.dashboard') }}" class="tp-icon-btn" title="กลับหน้าหลัก"><i class="fas fa-arrow-left"></i></a>
            </div>

            {{-- Quick Stats --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; margin-top:18px;">
                @php
                    $heroStats = [
                        ['📊', '6+', 'ช่องทางรายได้'],
                        ['💰', '∞', 'ไม่จำกัดรายได้'],
                        ['🎯', '8', 'ระดับยศ'],
                        ['🚀', '3D', 'Interactive Tools'],
                    ];
                @endphp
                @foreach($heroStats as [$emoji, $num, $label])
                    <div class="tp-card" style="padding:14px 12px; text-align:center; box-shadow:var(--inset-sm);">
                        <div style="font-size:22px;">{{ $emoji }}</div>
                        <div class="tp-num" style="font-size:22px; font-weight:800; margin-top:4px; color:var(--deep1);">{{ $num }}</div>
                        <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            {{-- CTA Buttons --}}
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:16px;">
                <button type="button" onclick="scrollToSection('income-overview')" class="tp-btn tp-btn-primary">🎯 เริ่มต้นสร้างรายได้</button>
                <button type="button" onclick="scrollToSection('3d-mindmap')" class="tp-btn">🧠 ดู 3D Mind Map</button>
                <button type="button" onclick="scrollToSection('income-calculator')" class="tp-btn">💎 คำนวณรายได้</button>
            </div>
        </div>
    </div>

    {{-- ── 3D MIND MAP ──────────────────────────────────────── --}}
    <section id="3d-mindmap" style="scroll-margin-top:80px;">
        <div class="tp-card" style="padding:20px;">
            <div style="text-align:center; margin-bottom:16px;">
                <div class="tp-section-h" style="justify-content:center; font-size:18px;">🧠 3D Mind Map แผนธุรกิจ</div>
                <p style="color:var(--ink2); font-size:13px; margin:8px 0 0;">ภาพรวมระบบรายได้ทั้งหมดในมุมมอง 3 มิติ - หมุน ซูม และสำรวจได้อย่างอิสระ</p>
            </div>

            {{-- 3D Mind Map Container --}}
            <div style="position:relative; border-radius:16px; overflow:hidden; box-shadow:var(--inset-sm); height:600px; background:var(--bg);">
                <div id="wealth-3d-mindmap" style="width:100%; height:100%;"></div>

                {{-- Controls Overlay --}}
                <div style="position:absolute; top:14px; left:14px; z-index:20; display:flex; flex-direction:column; gap:8px;">
                    <button id="toggle-rotate" type="button" class="tp-btn tp-btn-sm">🔄 Auto Rotate</button>
                    <button id="reset-camera" type="button" class="tp-btn tp-btn-sm">📷 Reset View</button>
                </div>

                {{-- Info Panel --}}
                <div style="position:absolute; bottom:14px; left:14px; right:14px; z-index:20;">
                    <div class="tp-card" style="padding:12px 14px;">
                        <div style="font-size:12.5px; color:var(--ink2);">
                            <strong style="color:var(--deep1);">💡 วิธีใช้:</strong> ลาก = หมุนดู | ล้อเมาส์ = ซูม | คลิกจุด = ดูรายละเอียด
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Navigation --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-top:16px;">
                @php
                    $mapNodes = [
                        ['💵', 'รายได้ตรง',      'var(--deep1)'],
                        ['⚖️', 'Binary Matching', '#d9534f'],
                        ['👑', 'Rank Bonus',     '#5aa07e'],
                        ['🤝', 'Sponsor Bonus',  '#9b6fb0'],
                    ];
                @endphp
                @foreach($mapNodes as [$emoji, $label, $color])
                    <button type="button" onclick="focusMindMapNode('{{ $label }}')" class="tp-card tp-card-hover" style="padding:14px 10px; text-align:center; border:0; cursor:pointer; background:transparent;">
                        <div style="font-size:22px;">{{ $emoji }}</div>
                        <div style="font-size:12.5px; font-weight:700; margin-top:6px; color:{{ $color }};">{{ $label }}</div>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── 3D INCOME FLOW ───────────────────────────────────── --}}
    <section id="income-flow" style="scroll-margin-top:80px;">
        <div class="tp-card" style="padding:20px;">
            <div style="text-align:center; margin-bottom:16px;">
                <div class="tp-section-h" style="justify-content:center; font-size:18px;">💸 กระแสเงินสด 3D</div>
                <p style="color:var(--ink2); font-size:13px; margin:8px 0 0;">ดูการไหลของเงินจากทุกช่องทางรายได้แบบเรียลไทม์</p>
            </div>

            {{-- 3D Income Flow Container --}}
            <div style="position:relative; border-radius:16px; overflow:hidden; box-shadow:var(--inset-sm); height:600px; background:var(--bg);">
                <div id="wealth-3d-income-flow" style="width:100%; height:100%;"></div>
            </div>

            {{-- Income Summary --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-top:16px;">
                @php
                    $flowSummary = [
                        ['Direct',      '฿15,000', 'var(--deep1)'],
                        ['Binary',      '฿8,000',  '#d9534f'],
                        ['Rank',        '฿25,000', '#5aa07e'],
                        ['Sponsor',     '฿5,000',  '#9b6fb0'],
                        ['Marketplace', '฿12,000', '#e0a52e'],
                        ['Team',        '฿18,000', '#5689b8'],
                    ];
                @endphp
                @foreach($flowSummary as [$label, $amount, $color])
                    <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                        <div style="font-size:11px; color:var(--ink2); margin-bottom:4px;">{{ $label }}</div>
                        <div class="tp-num" style="font-size:18px; font-weight:800; color:{{ $color }};">{{ $amount }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── 6 ช่องทางรายได้ ───────────────────────────────────── --}}
    <section id="income-overview" style="scroll-margin-top:80px;">
        <div style="text-align:center; margin-bottom:18px;">
            <div class="tp-section-h" style="justify-content:center; font-size:20px;">💰 6 ช่องทางรายได้ครบวงจร</div>
            <p style="color:var(--ink2); font-size:13px; max-width:640px; margin:8px auto 0;">
                สร้างรายได้จากหลากหลายช่องทาง ไม่พึ่งพาช่องทางเดียว มั่นคงและยั่งยืน
            </p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">

            {{-- Income Stream 1: Direct Commission --}}
            <div class="tp-card tp-card-hover" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:24px; background:color-mix(in srgb, var(--accent1) 16%, transparent);">💵</span>
                    <div style="flex:1; min-width:0;">
                        <h3 style="font-size:17px; font-weight:800; color:var(--deep1); margin:0 0 6px;">1. Direct Commission (รายได้ตรง)</h3>
                        <span class="tp-pill tp-pill-soft">10%-1% ต่อระดับ (10 ชั้น)</span>
                    </div>
                </div>

                <p style="color:var(--ink2); font-size:13px; margin:0 0 14px;">รายได้จากการขายตรงและทีมของคุณ แบบ Unilevel ลึกถึง 10 ชั้น</p>

                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                    @foreach([['ชั้นที่ 1 (Direct)','10%'],['ชั้นที่ 2','5%'],['ชั้นที่ 3-10','1-3%']] as [$layer, $pct])
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="font-size:12.5px; color:var(--ink2);">{{ $layer }}</span>
                            <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ $pct }}</span>
                        </div>
                    @endforeach
                </div>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--deep1); font-weight:600; margin-bottom:4px;">💡 ตัวอย่างรายได้:</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        สมาชิกชั้น 1 ซื้อ 1,000฿ = คุณได้ <strong style="color:var(--deep1);">100฿</strong><br>
                        มี 10 คน = <strong style="color:var(--deep1);">1,000฿/เดือน</strong>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:14px;">
                    @if($genealogyUrl)
                        <a href="{{ $genealogyUrl }}" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">ดูโครงสร้างทีม</a>
                    @endif
                    @if($incomeSimUrl)
                        <a href="{{ $incomeSimUrl }}" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">คำนวณรายได้</a>
                    @endif
                </div>
            </div>

            {{-- Income Stream 2: Binary Matching --}}
            <div class="tp-card tp-card-hover" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:24px; background:rgba(217,83,79,.16);">⚖️</span>
                    <div style="flex:1; min-width:0;">
                        <h3 style="font-size:17px; font-weight:800; color:#d9534f; margin:0 0 6px;">2. Binary Matching Bonus</h3>
                        <span class="tp-pill" style="color:#d9534f; background:rgba(217,83,79,.16);">100฿ ต่อคู่ (Match 50%)</span>
                    </div>
                </div>

                <p style="color:var(--ink2); font-size:13px; margin:0 0 14px;">รายได้จากคู่ขาซ้าย-ขวา ยิ่งสมดุลยิ่งได้มาก ระบบ Binary Tree</p>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); margin-bottom:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px; margin-bottom:8px;">
                        <span style="color:var(--ink2);">ขาซ้าย: 5 คน</span>
                        <span style="color:#d9534f;">5 PV</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px; margin-bottom:8px;">
                        <span style="color:var(--ink2);">ขาขวา: 5 คน</span>
                        <span style="color:#d9534f;">5 PV</span>
                    </div>
                    <div style="border-top:1px solid color-mix(in srgb,var(--ink2) 13%,transparent); padding-top:8px; margin-top:4px; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:12.5px; font-weight:700;">ได้ 5 คู่</span>
                        <span class="tp-num" style="font-weight:700; color:#d9534f;">= 5,000฿</span>
                    </div>
                </div>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:#d9534f; font-weight:600; margin-bottom:4px;">💡 กลยุทธ์:</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        สร้างทีมให้สมดุลทั้ง 2 ขา จะได้รายได้สูงสุด<br>
                        <strong style="color:#d9534f;">50,000 PV จับคู่ = 25,000฿</strong>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:14px;">
                    @if($genealogyUrl)
                        <a href="{{ $genealogyUrl }}?view=binary" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">ดู Binary Tree</a>
                    @endif
                    <button type="button" onclick="calculateBinaryBonus()" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">คำนวณโบนัส</button>
                </div>
            </div>

            {{-- Income Stream 3: Rank Achievement Bonus --}}
            <div class="tp-card tp-card-hover" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:24px; background:rgba(90,160,126,.16);">👑</span>
                    <div style="flex:1; min-width:0;">
                        <h3 style="font-size:17px; font-weight:800; color:#5aa07e; margin:0 0 6px;">3. Rank Achievement Bonus</h3>
                        <span class="tp-pill" style="color:#5aa07e; background:rgba(90,160,126,.16);">100฿ - 1,000,000฿ (8 ระดับ)</span>
                    </div>
                </div>

                <p style="color:var(--ink2); font-size:13px; margin:0 0 14px;">โบนัสพิเศษเมื่อคุณขึ้นยศ ทั้งโบนัสครั้งเดียวและรายเดือน</p>

                <div style="display:flex; flex-direction:column; gap:7px; margin-bottom:14px;">
                    @foreach($ranks as [$emoji, $name, $reward, $color])
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-radius:11px; box-shadow:var(--inset-sm); border-left:3px solid {{ $color }};">
                            <span style="font-size:12.5px; color:{{ $color }}; font-weight:600;">{{ $emoji }} {{ $name }}</span>
                            <span class="tp-num" style="font-size:11.5px; font-weight:700; color:{{ $color }};">{{ $reward }}</span>
                        </div>
                    @endforeach
                </div>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:#5aa07e; font-weight:600; margin-bottom:4px;">💡 ยศสูงสุด Legend:</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        Legend = <strong style="color:#5aa07e;">1,000,000฿ โบนัสเลื่อนตำแหน่ง!</strong><br>
                        + <strong style="color:#5aa07e;">100,000฿ ทุกเดือน</strong> ตลอดชีวิต
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <a href="#rank-roadmap" class="tp-btn tp-btn-sm" style="width:100%; justify-content:center;">ดู Roadmap สู่ Diamond</a>
                </div>
            </div>

            {{-- Income Stream 4: Sponsorship Bonus --}}
            <div class="tp-card tp-card-hover" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:24px; background:rgba(155,111,176,.16);">🤝</span>
                    <div style="flex:1; min-width:0;">
                        <h3 style="font-size:17px; font-weight:800; color:#9b6fb0; margin:0 0 6px;">4. Sponsorship Bonus</h3>
                        <span class="tp-pill" style="color:#9b6fb0; background:rgba(155,111,176,.16);">10-20% ของยอดแรก</span>
                    </div>
                </div>

                <p style="color:var(--ink2); font-size:13px; margin:0 0 14px;">โบนัสพิเศษเมื่อคุณแนะนำสมาชิกใหม่ รับทันทีจากยอดซื้อแรก</p>

                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                    <div style="padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-size:12.5px; color:var(--ink2);">สมาชิกธรรมดา</span>
                            <span class="tp-num" style="font-weight:700; color:#9b6fb0;">10%</span>
                        </div>
                        <div style="font-size:12px; color:var(--ink2);">แนะนำ 1 คน ซื้อ 5,000฿ = คุณได้ 500฿</div>
                    </div>
                    <div style="padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-size:12.5px; color:var(--ink2);">สมาชิกระดับสูง</span>
                            <span class="tp-num" style="font-weight:700; color:#9b6fb0;">20%</span>
                        </div>
                        <div style="font-size:12px; color:var(--ink2);">แนะนำ 1 คน ซื้อ 20,000฿ = คุณได้ 4,000฿</div>
                    </div>
                </div>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:#9b6fb0; font-weight:600; margin-bottom:4px;">💡 เคล็ดลับ:</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        แนะนำ <strong style="color:#9b6fb0;">10 คน/เดือน</strong> ๆ ละ 5,000฿<br>
                        = <strong style="color:#9b6fb0;">5,000฿ โบนัสทันที</strong>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:14px;">
                    <button type="button" onclick="copyReferralLink()" class="tp-btn tp-btn-primary tp-btn-sm" style="flex:1; justify-content:center;">📋 Copy ลิงก์แนะนำ</button>
                    <button type="button" onclick="generateQRCode()" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">📱 QR Code</button>
                </div>
            </div>

            {{-- Income Stream 5: Marketplace Affiliate --}}
            <div class="tp-card tp-card-hover" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:24px; background:rgba(224,165,46,.16);">🛒</span>
                    <div style="flex:1; min-width:0;">
                        <h3 style="font-size:17px; font-weight:800; color:#e0a52e; margin:0 0 6px;">5. Marketplace Affiliate</h3>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <span class="tp-pill" style="color:#9b6fb0; background:rgba(155,111,176,.16); font-size:10px;">Lazada 2-10%</span>
                            <span class="tp-pill" style="color:#e0a52e; background:rgba(224,165,46,.16); font-size:10px;">Shopee 5-15%</span>
                            <span class="tp-pill" style="color:#5689b8; background:rgba(86,137,184,.16); font-size:10px;">TikTok 8-20%</span>
                        </div>
                    </div>
                </div>

                <p style="color:var(--ink2); font-size:13px; margin:0 0 14px;">รายได้จากการเป็น Affiliate ของ Lazada, Shopee, TikTok Shop</p>

                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                    @foreach([['🛒','Lazada','Commission: 2-10%','#9b6fb0'],['🛍️','Shopee','Commission: 5-15%','#e0a52e'],['🎵','TikTok Shop','Commission: 8-20%','#5689b8']] as [$emoji, $name, $comm, $color])
                        <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="font-size:20px;">{{ $emoji }}</span>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:12.5px; color:{{ $color }}; font-weight:600;">{{ $name }}</div>
                                <div style="font-size:11px; color:var(--ink2);">{{ $comm }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:#e0a52e; font-weight:600; margin-bottom:4px;">💡 โอกาส:</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        แชร์สินค้า <strong style="color:#e0a52e;">ราคา 1,000฿</strong> มี 100 คนซื้อ<br>
                        Commission 10% = <strong style="color:#e0a52e;">10,000฿</strong>
                    </div>
                </div>

                @if($marketplaceUrl)
                    <div style="margin-top:14px;">
                        <a href="{{ $marketplaceUrl }}" class="tp-btn tp-btn-primary tp-btn-sm" style="width:100%; justify-content:center;">🚀 เริ่มต้น Marketplace Affiliate</a>
                    </div>
                @endif
            </div>

            {{-- Income Stream 6: Team Override --}}
            <div class="tp-card tp-card-hover" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:24px; background:rgba(86,137,184,.16);">👥</span>
                    <div style="flex:1; min-width:0;">
                        <h3 style="font-size:17px; font-weight:800; color:#5689b8; margin:0 0 6px;">6. Team Override Bonus</h3>
                        <span class="tp-pill" style="color:#5689b8; background:rgba(86,137,184,.16);">5-10% จากทีม</span>
                    </div>
                </div>

                <p style="color:var(--ink2); font-size:13px; margin:0 0 14px;">รายได้จากยอดขายรวมของทีม - ยิ่งทีมใหญ่ยิ่งได้มาก</p>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); margin-bottom:14px;">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:8px;">ตัวอย่างการคำนวณ:</div>
                    <div style="display:flex; flex-direction:column; gap:4px; font-size:12.5px; color:var(--ink2);">
                        <div>ทีมรวม 100 คน</div>
                        <div>ยอดขายเฉลี่ย 2,000฿/คน/เดือน</div>
                        <div>ยอดรวม = 200,000฿</div>
                    </div>
                    <div style="border-top:1px solid color-mix(in srgb,var(--ink2) 13%,transparent); padding-top:8px; margin-top:8px; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:12.5px; font-weight:700;">Override 5%</span>
                        <span class="tp-num" style="font-weight:700; color:#5689b8;">= 10,000฿</span>
                    </div>
                </div>

                <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:#5689b8; font-weight:600; margin-bottom:4px;">💡 กลยุทธ์:</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        สร้างทีม <strong style="color:#5689b8;">500 คน</strong><br>
                        = <strong style="color:#5689b8;">50,000฿/เดือน</strong> จาก Override
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:14px;">
                    @if($genealogyUrl)
                        <a href="{{ $genealogyUrl }}" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">📊 ดูทีม</a>
                    @endif
                    <button type="button" onclick="calculateTeamBonus()" class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">💰 คำนวณ</button>
                </div>
            </div>
        </div>

        {{-- Total Potential --}}
        <div class="tp-card" style="padding:24px; margin-top:16px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="text-align:center;">
                <div style="font-size:34px; margin-bottom:8px;">🎯</div>
                <h3 style="font-size:20px; font-weight:800; color:var(--deep1); margin:0 0 16px;">รายได้รวมที่เป็นไปได้</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:14px;">
                    @foreach([['ระดับเริ่มต้น','฿10,000+','#5aa07e'],['ระดับกลาง','฿50,000+','#e0a52e'],['ระดับสูง (Diamond)','฿200,000+','#d9534f']] as [$lvl, $amt, $color])
                        <div class="tp-card" style="padding:16px; box-shadow:var(--inset-sm);">
                            <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">{{ $lvl }}</div>
                            <div class="tp-num" style="font-size:22px; font-weight:800; color:{{ $color }};">{{ $amt }}</div>
                            <div style="font-size:11px; color:var(--ink2); margin-top:2px;">ต่อเดือน</div>
                        </div>
                    @endforeach
                </div>
                <p style="font-size:12px; color:var(--ink2); margin:0;">* รายได้ขึ้นอยู่กับความพยายามและการทำงานของคุณและทีม</p>
            </div>
        </div>
    </section>

    {{-- ── เครื่องคำนวณรายได้ ─────────────────────────────────── --}}
    <section id="income-calculator" style="scroll-margin-top:80px;">
        <div class="tp-card" style="padding:20px;">
            <div style="text-align:center; margin-bottom:18px;">
                <div class="tp-section-h" style="justify-content:center; font-size:18px;">🧮 เครื่องคำนวณรายได้</div>
                <p style="color:var(--ink2); font-size:13px; margin:8px 0 0;">คำนวณรายได้ที่คุณสามารถทำได้ตามเป้าหมายของคุณ</p>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                {{-- Calculator Input --}}
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div>
                        <label for="calc-direct-sales" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">จำนวนสมาชิกที่แนะนำต่อเดือน</label>
                        <input type="number" id="calc-direct-sales" value="5" min="0" class="tp-input" style="width:100%;">
                    </div>
                    <div>
                        <label for="calc-avg-purchase" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ยอดซื้อเฉลี่ยต่อคน (฿)</label>
                        <input type="number" id="calc-avg-purchase" value="3000" min="0" step="100" class="tp-input" style="width:100%;">
                    </div>
                    <div>
                        <label for="calc-team-size" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ขนาดทีมปัจจุบัน (คน)</label>
                        <input type="number" id="calc-team-size" value="20" min="0" class="tp-input" style="width:100%;">
                    </div>
                    <div>
                        <label for="calc-binary-pairs" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">Binary Pairs ต่อสัปดาห์</label>
                        <input type="number" id="calc-binary-pairs" value="10" min="0" class="tp-input" style="width:100%;">
                    </div>
                    <button type="button" onclick="calculateIncome()" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; padding:13px;">💎 คำนวณรายได้</button>
                </div>

                {{-- Calculator Results --}}
                <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm);">
                    <h3 style="font-size:15px; font-weight:700; color:var(--deep1); margin:0 0 14px;">รายได้ประมาณการต่อเดือน</h3>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:11px; box-shadow:var(--inset-sm);">
                            <span style="font-size:12.5px; color:var(--ink2);">Direct Commission</span>
                            <span class="tp-num" style="font-weight:700; color:var(--deep1);" id="result-direct">฿0</span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:11px; box-shadow:var(--inset-sm);">
                            <span style="font-size:12.5px; color:var(--ink2);">Binary Matching</span>
                            <span class="tp-num" style="font-weight:700; color:#d9534f;" id="result-binary">฿0</span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:11px; box-shadow:var(--inset-sm);">
                            <span style="font-size:12.5px; color:var(--ink2);">Sponsor Bonus</span>
                            <span class="tp-num" style="font-weight:700; color:#9b6fb0;" id="result-sponsor">฿0</span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:11px; box-shadow:var(--inset-sm);">
                            <span style="font-size:12.5px; color:var(--ink2);">Team Override</span>
                            <span class="tp-num" style="font-weight:700; color:#5689b8;" id="result-team">฿0</span>
                        </div>

                        <div style="border-top:2px solid color-mix(in srgb, var(--accent1) 50%, transparent); padding-top:14px; margin-top:4px;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <span style="font-size:15px; font-weight:700;">รายได้รวม</span>
                                <span class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);" id="result-total">฿0</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:16px; padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm);">
                        <div style="font-size:12px; color:#5689b8; margin-bottom:4px;">📈 รายได้ต่อปี (โดยประมาณ)</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:#5689b8;" id="result-yearly">฿0</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── ROADMAP สู่ DIAMOND ───────────────────────────────── --}}
    <section id="rank-roadmap" style="scroll-margin-top:80px;">
        <div style="text-align:center; margin-bottom:18px;">
            <div class="tp-section-h" style="justify-content:center; font-size:20px;">🗺️ Roadmap สู่ Diamond</div>
            <p style="color:var(--ink2); font-size:13px; max-width:640px; margin:8px auto 0;">
                เส้นทางชัดเจนจากมือใหม่สู่ระดับ Diamond พร้อมเป้าหมายที่วัดผลได้
            </p>
        </div>

        <div style="display:flex; flex-direction:column; gap:14px;">
            @php
                $roadmap = [
                    ['🥉','Bronze','ระดับเริ่มต้น','#cd7f32',['สมัครสมาชิก','มียอดซื้อขั้นต่ำ 5,000฿','แนะนำ 3 คน'],'รางวัล: 5,000฿ + 1,000฿/เดือน'],
                    ['🥈','Silver','ระดับกลาง','#9aa3ad',['ทีม 15 คน','ยอดขายรวม 50,000฿/เดือน','มี Bronze 3 คน'],'รางวัล: 20,000฿ + 5,000฿/เดือน'],
                    ['🥇','Gold','ระดับสูง','#e0a52e',['ทีม 50 คน','ยอดขายรวม 150,000฿/เดือน','มี Silver 3 คน'],'รางวัล: 50,000฿ + 15,000฿/เดือน'],
                    ['💎','Platinum','ระดับมืออาชีพ','#9b6fb0',['ทีม 150 คน','ยอดขายรวม 500,000฿/เดือน','มี Gold 3 คน'],'รางวัล: 200,000฿ + 50,000฿/เดือน'],
                    ['💠','Diamond','ระดับสูงสุด 🏆','#5aa07e',['ทีม 500 คน','ยอดขายรวม 2,000,000฿/เดือน','มี Platinum 3 คน'],'รางวัล: 500,000฿ + 150,000฿/เดือน'],
                ];
            @endphp
            @foreach($roadmap as $i => [$emoji, $name, $tier, $color, $conditions, $reward])
                <div class="tp-card" style="padding:18px; border-left:4px solid {{ $color }};">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                        <span class="tp-tile" style="width:50px; height:50px; border-radius:15px; font-size:26px; background:color-mix(in srgb, {{ $color }} 18%, transparent);">{{ $emoji }}</span>
                        <div style="flex:1; min-width:160px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="tp-num" style="font-size:11px; color:var(--ink2); font-weight:700;">{{ $i + 1 }}.</span>
                                <h3 style="font-size:18px; font-weight:800; color:{{ $color }}; margin:0;">{{ $name }}</h3>
                            </div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $tier }}</div>
                        </div>
                        <div style="flex:2; min-width:220px;">
                            <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">เงื่อนไข:</div>
                            <ul style="list-style:none; padding:0; margin:0 0 10px; display:flex; flex-direction:column; gap:3px;">
                                @foreach($conditions as $cond)
                                    <li style="font-size:12.5px; color:var(--ink2);"><span style="color:#5aa07e;">✓</span> {{ $cond }}</li>
                                @endforeach
                            </ul>
                            <div class="tp-num" style="font-size:13px; font-weight:700; color:{{ $color }};">{{ $reward }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Timeline Summary --}}
        <div class="tp-card" style="padding:20px; margin-top:16px;">
            <div style="text-align:center; margin-bottom:14px;">
                <h3 style="font-size:17px; font-weight:800; color:var(--deep1); margin:0 0 4px;">⏱️ กี่นานถึงขึ้น Diamond?</h3>
                <p style="font-size:12.5px; color:var(--ink2); margin:0;">ขึ้นอยู่กับความพยายามและกลยุทธ์ของคุณ</p>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
                @foreach([['🚀','Fast Track','6-12 เดือน','ทำงานเต็มเวลา + สร้างทีมเร็ว','#5aa07e'],['📈','Standard','1-2 ปี','ทำงานสม่ำเสมอ + สร้างทีมแข็งแกร่ง','#e0a52e'],['🎯','Steady','2-3 ปี','ทำงานเป็นรายได้เสริม','#5689b8']] as [$emoji, $title, $time, $desc, $color])
                    <div class="tp-card" style="padding:16px; text-align:center; box-shadow:var(--inset-sm);">
                        <div style="font-size:26px; margin-bottom:6px;">{{ $emoji }}</div>
                        <div style="font-size:13px; font-weight:700; color:{{ $color }}; margin-bottom:4px;">{{ $title }}</div>
                        <div class="tp-num" style="font-size:18px; font-weight:800; margin-bottom:6px;">{{ $time }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── ACTION STEPS ─────────────────────────────────────── --}}
    <section>
        <div class="tp-card" style="padding:24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="text-align:center; margin-bottom:18px;">
                <div style="font-size:42px; margin-bottom:8px;">🚀</div>
                <h2 style="font-size:22px; font-weight:800; color:var(--deep1); margin:0 0 8px;">เริ่มต้นวันนี้!</h2>
                <p style="font-size:14px; color:var(--ink2); max-width:520px; margin:0 auto;">
                    ทุกการเดินทางเริ่มต้นด้วยก้าวแรก - ก้าวของคุณคืออะไร?
                </p>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; max-width:840px; margin:0 auto;">
                <div class="tp-card" style="padding:20px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:30px; margin-bottom:10px;">1️⃣</div>
                    <h3 style="font-size:15px; font-weight:700; margin:0 0 6px;">ศึกษาระบบ</h3>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0 0 14px;">เรียนรู้ทุกช่องทางรายได้</p>
                    @if($wealthGuideUrl)
                        <a href="{{ $wealthGuideUrl }}" class="tp-btn tp-btn-sm">อ่านคู่มือฉบับเต็ม</a>
                    @endif
                </div>

                <div class="tp-card" style="padding:20px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:30px; margin-bottom:10px;">2️⃣</div>
                    <h3 style="font-size:15px; font-weight:700; margin:0 0 6px;">วางแผน</h3>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0 0 14px;">คำนวณเป้าหมายของคุณ</p>
                    @if($incomeSimUrl)
                        <a href="{{ $incomeSimUrl }}" class="tp-btn tp-btn-sm">ใช้เครื่องคำนวณ</a>
                    @endif
                </div>

                <div class="tp-card" style="padding:20px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:30px; margin-bottom:10px;">3️⃣</div>
                    <h3 style="font-size:15px; font-weight:700; margin:0 0 6px;">ลงมือทำ</h3>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0 0 14px;">เริ่มแชร์และสร้างทีม</p>
                    <button type="button" onclick="copyReferralLink()" class="tp-btn tp-btn-sm">Copy ลิงก์แนะนำ</button>
                </div>
            </div>

            <div style="text-align:center; margin-top:18px;">
                <a href="{{ route('user.dashboard') }}" class="tp-btn tp-btn-primary" style="padding:13px 28px;">🏠 ไปที่แดชบอร์ด</a>
            </div>
        </div>
    </section>

</div>

@vite(['resources/js/wealth-guide-pro.js'])
@endsection

@push('scripts')
<script>
// เลื่อนไปยัง section ที่กำหนด (smooth scroll)
function scrollToSection(sectionId) {
    document.getElementById(sectionId)?.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// เครื่องคำนวณรายได้
function calculateIncome() {
    const directSales = parseInt(document.getElementById('calc-direct-sales').value) || 0;
    const avgPurchase = parseInt(document.getElementById('calc-avg-purchase').value) || 0;
    const teamSize = parseInt(document.getElementById('calc-team-size').value) || 0;
    const binaryPairs = parseInt(document.getElementById('calc-binary-pairs').value) || 0;

    // คำนวณรายได้แต่ละช่องทาง
    const directCommission = directSales * avgPurchase * 0.3;
    const binaryBonus = binaryPairs * 1000 * 4; // ต่อสัปดาห์ x 4 สัปดาห์
    const sponsorBonus = directSales * avgPurchase * 0.15;
    const teamOverride = teamSize * avgPurchase * 0.05;

    const total = directCommission + binaryBonus + sponsorBonus + teamOverride;
    const yearly = total * 12;

    // อัพเดทผลลัพธ์
    document.getElementById('result-direct').textContent = '฿' + Math.floor(directCommission).toLocaleString();
    document.getElementById('result-binary').textContent = '฿' + Math.floor(binaryBonus).toLocaleString();
    document.getElementById('result-sponsor').textContent = '฿' + Math.floor(sponsorBonus).toLocaleString();
    document.getElementById('result-team').textContent = '฿' + Math.floor(teamOverride).toLocaleString();
    document.getElementById('result-total').textContent = '฿' + Math.floor(total).toLocaleString();
    document.getElementById('result-yearly').textContent = '฿' + Math.floor(yearly).toLocaleString();

    // อนิเมชันตัวเลขรวม
    animateValue('result-total', 0, total, 1000);
}

function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    const startTime = Date.now();
    const animate = () => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);
        element.textContent = '฿' + current.toLocaleString();
        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    };
    animate();
}

// คัดลอกลิงก์แนะนำ
function copyReferralLink() {
    const referralCode = @json(auth()->user()->affiliate_code ?? 'YOUR_CODE');
    const link = @json(url('/register')) + '?ref=' + referralCode;
    navigator.clipboard.writeText(link).then(() => {
        if (typeof window.showNotification === 'function') {
            window.showNotification('คัดลอกลิงก์แนะนำเรียบร้อย! ' + link, 'success');
        } else {
            alert('✅ คัดลอกลิงก์แนะนำเรียบร้อย!\n\n' + link);
        }
    });
}

// สร้าง QR Code (อยู่ระหว่างพัฒนา)
function generateQRCode() {
    if (typeof window.showNotification === 'function') {
        window.showNotification('ฟีเจอร์ QR Code กำลังพัฒนา...', 'info');
    } else {
        alert('🚧 ฟีเจอร์ QR Code กำลังพัฒนา...');
    }
}

// คำนวณรายได้เริ่มต้นเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    calculateIncome();
});
</script>
@endpush
