{{-- resources/views/user/fortune-referral/tree.blade.php --}}
{{--
    ผังสายงานดูดวง - User
    แสดงโครงสร้างการแนะนำเพื่อนดูดวง (L1/L2) ของ user ปัจจุบัน
    ใช้ OrgChartViewer + Fortune commission data overlay

    @author TP-Affiliate Team
    @version 4.0.0
--}}

@extends('layouts.user-v4')

@section('title', 'ผังสายงานดูดวง')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="flex:1; min-width:220px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="tp-tile" style="width:50px; height:50px; border-radius:15px; font-size:22px; background:#7c5cbf;"><span style="color:#fff;">🔮</span></span>
                        <div>
                            <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ผังสายงานดูดวง</h1>
                            <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)</div>
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="{{ route('user.fortune-referral.commissions') }}" class="tp-btn tp-btn-sm">💰 คอมมิชชั่น</a>
                    <a href="{{ route('user.fortune-referral.recruit') }}" class="tp-btn tp-btn-sm">📢 ชวนเพื่อน</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── การ์ดสถิติ ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:18px;">💰</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">รายได้รวม</span>
            </div>
            <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--deep1);">฿{{ number_format($stats['total'], 2) }}</div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="display:inline-flex; padding:1px 8px; border-radius:999px; font-size:11px; font-weight:700; color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent);">L1</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">สายตรง</span>
            </div>
            <div class="tp-num" style="font-size:23px; font-weight:800; color:#5689b8;">฿{{ number_format($stats['level1'], 2) }}</div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="display:inline-flex; padding:1px 8px; border-radius:999px; font-size:11px; font-weight:700; color:#7c5cbf; background:color-mix(in srgb, #7c5cbf 16%, transparent);">L2</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">ชั้นหลาน</span>
            </div>
            <div class="tp-num" style="font-size:23px; font-weight:800; color:#7c5cbf;">฿{{ number_format($stats['level2'], 2) }}</div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:18px;">👥</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">ผู้แนะนำ</span>
            </div>
            <div class="tp-num" style="font-size:23px; font-weight:800; color:#5aa07e;">{{ number_format($stats['referral_count']) }} คน</div>
        </div>
    </div>

    @if($currentMember)
        {{-- ── ตัวควบคุม ─────────────────────────────────────── --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:16px; background:#7c5cbf;"><i class="fas fa-sitemap" style="color:#fff;"></i></span>
                    <div>
                        <div style="font-weight:800; color:var(--ink);">สายงานของฉัน</div>
                        <div style="font-size:12px; color:var(--ink2);">รหัส: {{ $currentMember->member_code ?? '-' }}</div>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-end; gap:12px;">
                    <div>
                        <label style="display:block; font-size:11px; font-weight:600; color:var(--ink2); margin-bottom:4px;">ความลึก</label>
                        <select id="depth-selector" class="tp-input" style="padding:8px 12px;">
                            <option value="3">3 ระดับ</option>
                            <option value="5" selected>5 ระดับ</option>
                            <option value="7">7 ระดับ</option>
                            <option value="10">10 ระดับ</option>
                        </select>
                    </div>
                    <button id="btn-reload-tree" class="tp-btn tp-btn-primary" style="background:#7c5cbf; border-color:#7c5cbf;">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>
        </div>

        {{-- ── ตัวแสดงผัง ─────────────────────────────────────── --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:14px 20px; box-shadow:var(--inset-sm); display:flex; align-items:center; gap:8px;">
                <i class="fas fa-project-diagram" style="color:#7c5cbf;"></i>
                <span style="font-weight:800; color:var(--ink);">โครงสร้างสายงาน</span>
            </div>
            <div style="height:clamp(350px, calc(100vh - 450px), 650px);">
                <div id="fortune-tree-container" style="width:100%; height:100%;"></div>
            </div>
        </div>
    @else
        {{-- ── ยังไม่มี MLM Member ─────────────────────────────── --}}
        <div class="tp-card" style="padding:32px; text-align:center;">
            <div style="width:80px; height:80px; margin:0 auto 20px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                <span style="font-size:40px;">🔮</span>
            </div>
            <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 8px;">ยังไม่มีข้อมูลสายงาน</h3>
            <p style="color:var(--ink2); margin:0 0 20px;">คุณยังไม่ได้เป็นสมาชิกในระบบ MLM เริ่มชวนเพื่อนมาดูดวงเพื่อสร้างสายงานของคุณ!</p>
            <a href="{{ route('user.fortune-referral.recruit') }}" class="tp-btn tp-btn-primary" style="background:#7c5cbf; border-color:#7c5cbf;">📢 ชวนเพื่อนดูดวง</a>
        </div>
    @endif

    {{-- ── คำอธิบาย + วิธีใช้ ─────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">📋 คำอธิบายสัญลักษณ์</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="display:inline-flex; padding:2px 12px; border-radius:999px; font-size:11px; font-weight:700; color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent);">L1</span>
                    <span style="font-size:13px; color:var(--ink);">ชั้น 1 (สายตรง) — เพื่อนที่คุณแนะนำโดยตรง</span>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="display:inline-flex; padding:2px 12px; border-radius:999px; font-size:11px; font-weight:700; color:#7c5cbf; background:color-mix(in srgb, #7c5cbf 16%, transparent);">L2</span>
                    <span style="font-size:13px; color:var(--ink);">ชั้น 2 (ชั้นหลาน) — เพื่อนของเพื่อนที่คุณแนะนำ</span>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="width:16px; height:16px; border-radius:50%; background:#5aa07e;"></span>
                    <span style="font-size:13px; color:var(--ink);">สมาชิกที่ยังใช้งานอยู่</span>
                </div>
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">💡 วิธีใช้งาน</div>
            <div style="display:flex; flex-direction:column; gap:8px; font-size:13px; color:var(--ink);">
                <p style="margin:0;">📱 <strong>มือถือ:</strong> ลากนิ้วเพื่อเลื่อน, บีบนิ้วเพื่อซูม</p>
                <p style="margin:0;">🖥️ <strong>คอมพิวเตอร์:</strong> คลิกค้างลากเพื่อเลื่อน, Scroll wheel เพื่อซูม</p>
                <p style="margin:0;">👆 <strong>คลิก/แตะ</strong> ที่การ์ดสมาชิกเพื่อดูข้อมูลเพิ่มเติม</p>
                <p style="margin:0;">🔄 กดปุ่ม <strong>"รีเฟรช"</strong> เพื่อโหลดข้อมูลล่าสุด</p>
            </div>
        </div>
    </div>
</div>
@endsection

@if($currentMember)
@push('scripts')
<script src="{{ asset('assets/js/org-chart-viewer.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let viewer = null;
    const container = document.getElementById('fortune-tree-container');
    const depthSelector = document.getElementById('depth-selector');
    const btnReload = document.getElementById('btn-reload-tree');

    /**
     * โหลดและแสดงผังสายงานดูดวง
     */
    async function loadFortuneTree() {
        const depth = depthSelector.value;

        // สร้าง viewer ถ้ายังไม่มี
        if (!viewer) {
            viewer = new OrgChartViewer(container, {
                treeType: 'unilevel',
                maxDepth: parseInt(depth),
                nodeWidth: window.innerWidth < 768 ? 160 : 200,
                nodeHeight: window.innerWidth < 768 ? 110 : 120,
                horizontalSpacing: window.innerWidth < 768 ? 20 : 40,
                verticalSpacing: window.innerWidth < 768 ? 80 : 100
            });
        } else {
            viewer.options.maxDepth = parseInt(depth);
            viewer.showLoading();
        }

        // Fetch tree data จาก API
        try {
            const response = await fetch(`{{ route('user.fortune-referral.tree-data') }}?depth=${depth}`);
            const result = await response.json();

            if (result.success && result.data) {
                viewer.setData(result.data);
            } else {
                if (viewer) viewer.hideLoading();
                // แสดง empty state ในคอนเทนเนอร์
                container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400"><div class="text-center"><span class="text-4xl mb-4 block">🔮</span><p class="text-lg font-semibold">ยังไม่มีข้อมูลสายงาน</p><p class="text-sm mt-2">เริ่มชวนเพื่อนมาดูดวงเพื่อสร้างสายงาน!</p></div></div>';
            }
        } catch (error) {
            console.error('Error loading fortune tree:', error);
            if (viewer) viewer.hideLoading();
            container.innerHTML = '<div class="flex items-center justify-center h-full text-red-500"><div class="text-center"><span class="text-4xl mb-4 block">⚠️</span><p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p></div></div>';
        }
    }

    // Event Listeners
    btnReload.addEventListener('click', loadFortuneTree);

    depthSelector.addEventListener('change', function() {
        if (viewer) {
            loadFortuneTree();
        }
    });

    // Auto-load on page load
    loadFortuneTree();

    // Responsive handling
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (viewer) {
                viewer.options.nodeWidth = window.innerWidth < 768 ? 160 : 200;
                viewer.options.nodeHeight = window.innerWidth < 768 ? 110 : 120;
                viewer.options.horizontalSpacing = window.innerWidth < 768 ? 20 : 40;
                viewer.options.verticalSpacing = window.innerWidth < 768 ? 80 : 100;

                if (viewer.data) {
                    viewer.nodeCount = 0;
                    viewer.maxDepthReached = 0;
                    viewer.render();
                    viewer.fitToScreen();
                }
            }
        }, 250);
    });
});
</script>
@endpush
@endif
