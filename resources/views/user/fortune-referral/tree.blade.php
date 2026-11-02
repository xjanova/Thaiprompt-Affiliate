{{--
    ผังสายงานดูดวง (ธีมโนวาเต็ม) — FortuneReferralDashboardController::tree
    - $currentMember = MlmMember ของสมาชิก (null = ยังไม่อยู่ในระบบ MLM → แสดงหน้าว่าง ไม่โหลดผัง)
    - ผังวาดด้วย OrgChartViewer (public/assets/js/org-chart-viewer.js มีสไตล์ในตัว รองรับ html.dark)
      ข้อมูลจาก route user.fortune-referral.tree-data (เฉพาะสายงานใต้สมาชิกคนนี้)
--}}
@extends('layouts.user-nova')

@section('title', 'ผังสายงานดูดวง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/fortune-referral.css') }}?v={{ filemtime(public_path('theme-nova/member/fortune-referral.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);

    $kpis = [
        ['รายได้รวม', 'Total', $baht($stats['total']), 'images/nova/deco/coins.webp', 'คอมมิชชั่นดูดวงทั้งหมด'],
        ['L1 สายตรง', 'Direct', $baht($stats['level1']), 'images/nova/deco/crystal.webp', 'เพื่อนที่คุณแนะนำ'],
        ['L2 ชั้นหลาน', 'Second level', $baht($stats['level2']), 'images/nova/deco/lotus.webp', 'เพื่อนของเพื่อน'],
        ['ผู้ที่คุณแนะนำ', 'Referrals', number_format((int) $stats['referral_count']).' คน', 'images/nova/mascot/welcome.webp', 'สายตรงในระบบ MLM'],
    ];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-fr-t-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.genealogy'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.genealogy') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>สายงานของฉัน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Fortune network</div>
            <h1 id="nvm-fr-t-h">ผังสายงานดูดวง</h1>
            <p class="nvm-ph__lead">โครงสร้างการแนะนำเพื่อนดูดวงของคุณ — Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)</p>
            <div class="nvm-pills">
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.fortune-referral.commissions') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>คอมมิชชั่น</a>
                <a class="nvm-pill nvm-pill--gold" href="{{ route('user.fortune-referral.recruit') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ชวนเพื่อน</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุปสายงานดูดวง">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi nvm-rv">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

@if($currentMember)
    <section class="nvm-card nvm-box" aria-label="ตัวควบคุมผัง">
        <div class="nvm-fr-ctl">
            <div class="nvm-fr-ctl__who">
                <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg></span>
                <div>
                    <b>สายงานของฉัน</b>
                    <small>รหัสสมาชิก: <span class="nvm-num">{{ $currentMember->member_code ?? '-' }}</span></small>
                </div>
            </div>
            <div class="nvm-fr-ctl__form">
                <div class="nvm-field">
                    <label class="nvm-label" for="depth-selector">ความลึก</label>
                    <select id="depth-selector" class="nvm-input">
                        <option value="3">3 ระดับ</option>
                        <option value="5" selected>5 ระดับ</option>
                        <option value="7">7 ระดับ</option>
                        <option value="10">10 ระดับ</option>
                    </select>
                </div>
                <button type="button" id="btn-reload-tree" class="nvm-btn nvm-btn--gold">
                    <svg class="nvm-ico"><use href="#nvm-i-history"/></svg>รีเฟรช
                </button>
            </div>
        </div>
    </section>

    <section class="nvm-card nvm-fr-tree" aria-labelledby="nvm-fr-tree-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Structure</small><h2 id="nvm-fr-tree-h">โครงสร้างสายงาน</h2></div></div>
        <div class="nvm-fr-tree__box" id="fortune-tree-container"></div>
        {{-- กล่องข้อความว่าง/ผิดพลาด แยกจากกล่องผัง — ไม่แทนที่ DOM ของผัง จึงใช้ viewer ตัวเดิมได้ตลอด --}}
        <div class="nvm-fr-tree__box" id="fortune-tree-message" role="status" hidden></div>
    </section>

    {{-- แม่แบบข้อความว่าง/ผิดพลาด ใส่ลงกล่องผังเมื่อโหลดข้อมูลไม่ได้ --}}
    <template id="nvm-fr-tree-empty">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีข้อมูลสายงาน</b>
            เริ่มชวนเพื่อนมาดูดวงเพื่อสร้างสายงาน!
        </div>
    </template>
    <template id="nvm-fr-tree-error">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>เกิดข้อผิดพลาดในการโหลดข้อมูล</b>
            กดปุ่ม "รีเฟรช" เพื่อลองใหม่อีกครั้ง
        </div>
    </template>
@else
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีข้อมูลสายงาน</b>
            คุณยังไม่ได้เป็นสมาชิกในระบบ MLM เริ่มชวนเพื่อนมาดูดวงเพื่อสร้างสายงานของคุณ!
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.fortune-referral.recruit') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ชวนเพื่อนดูดวง</a></div>
        </div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-fr-legend-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Legend</small><h2 id="nvm-fr-legend-h">คำอธิบายสัญลักษณ์</h2></div></div>
        <ul class="nvm-fr-legend">
            <li><span class="nvm-chip nvm-chip--info">L1</span>ชั้น 1 (สายตรง) — เพื่อนที่คุณแนะนำโดยตรง</li>
            <li><span class="nvm-chip nvm-chip--warn">L2</span>ชั้น 2 (ชั้นหลาน) — เพื่อนของเพื่อนที่คุณแนะนำ</li>
            <li><span class="nvm-fr-dot" aria-hidden="true"></span>สมาชิกที่ยังใช้งานอยู่</li>
        </ul>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-fr-use-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How to use</small><h2 id="nvm-fr-use-h">วิธีใช้งาน</h2></div></div>
        <ol class="nvm-steps">
            <li>มือถือ: ลากนิ้วเพื่อเลื่อน บีบนิ้วเพื่อซูม</li>
            <li>คอมพิวเตอร์: คลิกค้างแล้วลากเพื่อเลื่อน ใช้ล้อเมาส์เพื่อซูม</li>
            <li>คลิกหรือแตะที่การ์ดสมาชิกเพื่อดูข้อมูลเพิ่มเติม</li>
            <li>กดปุ่ม "รีเฟรช" เพื่อโหลดข้อมูลล่าสุด</li>
        </ol>
    </aside>
</div>

</div>
@endsection

@if($currentMember)
@push('scripts')
<script src="{{ asset('assets/js/org-chart-viewer.js') }}?v={{ filemtime(public_path('assets/js/org-chart-viewer.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var viewer = null;
    var container = document.getElementById('fortune-tree-container');
    var messageBox = document.getElementById('fortune-tree-message');
    var depthSelector = document.getElementById('depth-selector');
    var btnReload = document.getElementById('btn-reload-tree');
    var dataUrl = @js(route('user.fortune-referral.tree-data'));
    var loadSeq = 0; // เปลี่ยนความลึกระหว่างรอผล → ทิ้งผลของคำขอเก่า ไม่ให้วาดทับผลใหม่

    var isMobile = function () { return window.innerWidth < 768; };
    var sizing = function () {
        return {
            nodeWidth: isMobile() ? 160 : 200,
            nodeHeight: isMobile() ? 110 : 120,
            horizontalSpacing: isMobile() ? 20 : 40,
            verticalSpacing: isMobile() ? 80 : 100
        };
    };

    // แสดงข้อความว่าง/ผิดพลาดในกล่องแยก แล้วซ่อนกล่องผังไว้ (ไม่ลบ DOM ของผัง)
    // เดิมแทนที่ DOM ของผังแล้วสร้าง OrgChartViewer ใหม่ทุกครั้ง → ตัวฟังเหตุการณ์ระดับ document ของตัวเก่าค้างสะสม
    var showMessage = function (templateId) {
        var tpl = document.getElementById(templateId);
        messageBox.replaceChildren(tpl.content.cloneNode(true));
        container.hidden = true;
        messageBox.hidden = false;
    };

    var showChart = function () {
        messageBox.hidden = true;
        messageBox.replaceChildren();
        container.hidden = false;
    };

    /**
     * โหลดและแสดงผังสายงานดูดวง
     */
    async function loadFortuneTree() {
        var depth = parseInt(depthSelector.value, 10);
        var seq = ++loadSeq;
        btnReload.disabled = true;
        showChart();

        if (!viewer) {
            viewer = new OrgChartViewer(container, Object.assign({ treeType: 'unilevel', maxDepth: depth }, sizing()));
        } else {
            viewer.options.maxDepth = depth;
            viewer.showLoading();
        }

        try {
            var response = await fetch(dataUrl + '?depth=' + depth, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (seq !== loadSeq) return;

            // 404 = ยังไม่เป็นสมาชิก MLM → ข้อความว่าง · สถานะผิดพลาดอื่น (เช่น 500) → ข้อความผิดพลาดให้กดรีเฟรช
            if (!response.ok) {
                showMessage(response.status === 404 ? 'nvm-fr-tree-empty' : 'nvm-fr-tree-error');
                return;
            }

            var result = await response.json();
            if (seq !== loadSeq) return;

            if (result.success && result.data) {
                viewer.setData(result.data);
            } else {
                showMessage('nvm-fr-tree-empty');
            }
        } catch (error) {
            if (seq !== loadSeq) return;
            console.error('โหลดผังสายงานดูดวงไม่สำเร็จ:', error);
            showMessage('nvm-fr-tree-error');
        } finally {
            if (seq === loadSeq) btnReload.disabled = false;
        }
    }

    btnReload.addEventListener('click', loadFortuneTree);
    depthSelector.addEventListener('change', loadFortuneTree);

    loadFortuneTree();

    // ปรับขนาดการ์ดตามจอ (หน่วงเวลาไม่ให้วาดใหม่ถี่ ๆ ตอนลากขนาดหน้าต่าง)
    var resizeTimeout;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            if (!viewer || container.hidden) return;
            Object.assign(viewer.options, sizing());
            if (viewer.data) {
                viewer.nodeCount = 0;
                viewer.maxDepthReached = 0;
                viewer.render();
                viewer.fitToScreen();
            }
        }, 250);
    });
});
</script>
@endpush
@endif
