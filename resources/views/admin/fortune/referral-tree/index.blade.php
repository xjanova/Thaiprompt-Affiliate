{{--
    ผังสายงานดูดวง - Admin (ธีม V4 "นวลทองคำ")
    แสดงโครงสร้างการแนะนำเพื่อนดูดวง (L1/L2) แบบ Interactive
    ใช้ OrgChartViewer (org-tree lib เดิม) + Fortune commission data overlay
    โหลดข้อมูลผ่าน AJAX endpoint: admin.fortune.referral-tree.tree-data

    @author TP-Affiliate Team
    @version 4.0.0 (V4 นวลทองคำ)
--}}

@extends('layouts.admin-v4')

@section('title', 'ผังสายงานดูดวง')

@section('content')
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== HEADER: eyebrow + h1 ซ้าย / ปุ่มขวา ===== --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · ผังสายงาน
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;">ผังสายงานดูดวง</h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                แสดงโครงสร้างการแนะนำเพื่อนดูดวง L1/L2 แบบ Interactive
            </p>
        </div>

        {{-- ปุ่มลัด --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.commissions.index'))
                <a href="{{ route('admin.fortune.commissions.index') }}" class="tp-btn">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>ภาพรวมคอมมิชชั่น</span>
                </a>
            @endif
            @if(Route::has('admin.fortune.commissions.manage'))
                <a href="{{ route('admin.fortune.commissions.manage') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-list-check"></i>
                    <span>จัดการคอมมิชชั่น</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ===== การ์ดเลือกสมาชิก (CRUD-form pattern) ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:12px;">
            <span class="tp-icon-btn" aria-hidden="true"><i class="fas fa-user-magnifying-glass"></i></span>
            <div>
                <div style="font-weight:700;color:var(--ink);">เลือกสมาชิกเพื่อดูผังสายงานดูดวง</div>
                <div class="tp-muted" style="font-size:12px;">เลือกสมาชิกที่ต้องการดูโครงสร้างสายงานการแนะนำดูดวง</div>
            </div>
        </div>

        <div class="tp-divider"></div>

        <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
            {{-- ช่องเลือกสมาชิก --}}
            <div style="flex:1 1 280px;min-width:240px;">
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                    <i class="fas fa-magnifying-glass" style="margin-right:4px;"></i> สมาชิก
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select id="member-selector"
                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="">-- เลือกสมาชิก --</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" data-code="{{ $member->member_code }}">
                                {{ $member->member_code }} - {{ $member->user->name ?? 'Unknown' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ความลึก --}}
            <div style="flex:0 0 auto;min-width:150px;">
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                    <i class="fas fa-layer-group" style="margin-right:4px;"></i> ความลึก
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select id="depth-selector"
                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="3">3 ระดับ</option>
                        <option value="5" selected>5 ระดับ</option>
                        <option value="7">7 ระดับ</option>
                        <option value="10">10 ระดับ</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่มแสดงผัง --}}
            <div style="flex:0 0 auto;">
                <button id="btn-view-tree" type="button" class="tp-btn tp-btn-primary" style="padding:12px 22px;">
                    <i class="fas fa-eye"></i>
                    <span>แสดงผังสายงาน</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== ตัวแสดงผัง (org-tree viz — เก็บ lib เดิม แค่ reskin container) ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        {{-- หัวการ์ดผัง --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--sd);background:var(--a1soft);">
            <div style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-sitemap" style="color:var(--accent1);"></i>
                <span style="font-weight:700;color:var(--ink);">โครงสร้างสายงานดูดวง</span>
            </div>
            <div id="current-member-info" class="tp-muted" style="font-size:13px;display:none;">
                กำลังดู: <span id="current-member-name" style="font-weight:700;color:var(--accent1);"></span>
            </div>
        </div>

        {{-- container ของ org chart (lib เดิมวาดในนี้) --}}
        <div class="tp-inset" style="margin:14px;border-radius:14px;overflow:hidden;">
            <div id="org-chart-shell" style="height:calc(100vh - 460px);min-height:480px;max-height:700px;">
                <div id="fortune-tree-container" style="width:100%;height:100%;"></div>
            </div>
        </div>
    </div>

    {{-- ===== คำอธิบาย + วิธีใช้ (2 คอลัมน์) ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;">

        {{-- คำอธิบายสัญลักษณ์ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:10px;">
                <span class="tp-icon-btn" aria-hidden="true"><i class="fas fa-circle-info"></i></span>
                <span style="font-weight:700;color:var(--ink);">คำอธิบายสัญลักษณ์</span>
            </div>
            <div class="tp-divider"></div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="tp-pill" style="background:rgba(86,137,184,.18);color:#5689b8;font-weight:700;">L1</span>
                    <span class="tp-muted" style="font-size:13px;">ชั้นที่ 1 (สายตรง) — คอมมิชชั่นจากผู้แนะนำโดยตรง</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="tp-pill" style="background:rgba(183,154,232,.18);color:#b79ae8;font-weight:700;">L2</span>
                    <span class="tp-muted" style="font-size:13px;">ชั้นที่ 2 (ชั้นหลาน) — คอมมิชชั่นจากผู้แนะนำของผู้แนะนำ</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="tp-pill" style="background:rgba(90,160,126,.18);color:#5aa07e;font-weight:700;">
                        <i class="fas fa-circle-check" style="margin-right:4px;"></i> active
                    </span>
                    <span class="tp-muted" style="font-size:13px;">สมาชิกที่ยังใช้งานอยู่</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="tp-pill" style="background:rgba(217,83,79,.18);color:#d9534f;font-weight:700;">
                        <i class="fas fa-circle-xmark" style="margin-right:4px;"></i> inactive
                    </span>
                    <span class="tp-muted" style="font-size:13px;">สมาชิกที่ไม่ใช้งาน</span>
                </div>
            </div>
        </div>

        {{-- วิธีใช้งาน --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:10px;">
                <span class="tp-icon-btn" aria-hidden="true"><i class="fas fa-lightbulb"></i></span>
                <span style="font-weight:700;color:var(--ink);">วิธีใช้งาน</span>
            </div>
            <div class="tp-divider"></div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <span class="tp-num" style="flex:0 0 28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:var(--a1soft);color:var(--accent1);font-weight:800;font-size:13px;">1</span>
                    <span class="tp-muted" style="font-size:13px;">เลือกสมาชิกที่ต้องการดูผังจาก dropdown ด้านบน</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <span class="tp-num" style="flex:0 0 28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:var(--a1soft);color:var(--accent1);font-weight:800;font-size:13px;">2</span>
                    <span class="tp-muted" style="font-size:13px;">กดปุ่ม "แสดงผังสายงาน" เพื่อแสดงโครงสร้าง</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <span class="tp-num" style="flex:0 0 28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:var(--a1soft);color:var(--accent1);font-weight:800;font-size:13px;">3</span>
                    <span class="tp-muted" style="font-size:13px;"><strong style="color:var(--ink);">เลื่อนดู:</strong> คลิกค้างลาก (Desktop) / ลากนิ้ว (Mobile)</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <span class="tp-num" style="flex:0 0 28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:var(--a1soft);color:var(--accent1);font-weight:800;font-size:13px;">4</span>
                    <span class="tp-muted" style="font-size:13px;"><strong style="color:var(--ink);">ซูม:</strong> Scroll wheel (Desktop) / บีบนิ้ว (Mobile)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ไฮไลต์ฟีเจอร์ (3 ไทล์) ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span class="tp-icon-btn" aria-hidden="true"><i class="fas fa-sitemap"></i></span>
                <span style="font-weight:700;color:var(--ink);">ระบบ L1/L2</span>
            </div>
            <p class="tp-muted" style="font-size:13px;margin:0;">โครงสร้าง 2 ชั้น — L1 สายตรง + L2 ชั้นหลาน สำหรับคอมมิชชั่นดูดวง</p>
        </div>

        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span class="tp-icon-btn" aria-hidden="true"><i class="fas fa-chart-column"></i></span>
                <span style="font-weight:700;color:var(--ink);">ข้อมูลรายได้</span>
            </div>
            <p class="tp-muted" style="font-size:13px;margin:0;">แสดงจำนวนคอมมิชชั่นและรายได้รวมในแต่ละ node ของสายงาน</p>
        </div>

        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span class="tp-icon-btn" aria-hidden="true"><i class="fas fa-mobile-screen"></i></span>
                <span style="font-weight:700;color:var(--ink);">Touch-Friendly</span>
            </div>
            <p class="tp-muted" style="font-size:13px;margin:0;">รองรับการใช้งานบนมือถือ ลากเลื่อนและซูมด้วยนิ้วได้</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- org-tree viz lib เดิม (เก็บ logic 100% — เป็น org tree ไม่ใช่ data chart) --}}
<script src="{{ asset('assets/js/org-chart-viewer.js') }}"></script>
<style>
    /* ปรับความสูง container ผังให้ responsive (ย้ายมาจาก styles stack เดิม) */
    @media (max-width: 768px) {
        #org-chart-shell {
            height: calc(100vh - 360px) !important;
            min-height: 400px !important;
        }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let viewer = null;
    const container = document.getElementById('fortune-tree-container');
    const memberSelector = document.getElementById('member-selector');
    const depthSelector = document.getElementById('depth-selector');
    const btnView = document.getElementById('btn-view-tree');
    const currentMemberInfo = document.getElementById('current-member-info');
    const currentMemberName = document.getElementById('current-member-name');

    /**
     * โหลดและแสดงผังสายงานดูดวง
     * คง AJAX endpoint + payload เดิม 100%
     */
    async function loadFortuneTree() {
        const memberId = memberSelector.value;
        const depth = depthSelector.value;

        if (!memberId) {
            // แจ้งเตือนผ่าน toast ของ layout V4 (fallback alert)
            window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'warning', message: 'กรุณาเลือกสมาชิก' } }));
            return;
        }

        // แสดงชื่อสมาชิกที่เลือก
        const selectedOption = memberSelector.options[memberSelector.selectedIndex];
        currentMemberName.textContent = selectedOption.text;
        currentMemberInfo.style.display = '';

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

        // Fetch tree data จาก API (endpoint + query เดิม)
        try {
            const response = await fetch(`/admin/fortune/referral-tree/${memberId}/tree-data?depth=${depth}`);
            const result = await response.json();

            if (result.success && result.data) {
                viewer.setData(result.data);
            } else {
                viewer.hideLoading();
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', message: 'ไม่พบข้อมูลผังสายงาน' } }));
            }
        } catch (error) {
            console.error('Error loading fortune tree:', error);
            if (viewer) viewer.hideLoading();
            window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', message: 'เกิดข้อผิดพลาดในการโหลดข้อมูล' } }));
        }
    }

    // Event Listeners
    btnView.addEventListener('click', loadFortuneTree);

    // เปลี่ยน depth ให้โหลดใหม่
    depthSelector.addEventListener('change', function() {
        if (viewer && memberSelector.value) {
            loadFortuneTree();
        }
    });

    // Auto-load สมาชิกคนแรกถ้ามี
    const firstMember = memberSelector.querySelector('option:nth-child(2)');
    if (firstMember) {
        memberSelector.value = firstMember.value;
        loadFortuneTree();
    }

    // จัดการ responsive resize ของผัง
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
