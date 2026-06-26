@extends('layouts.user-v4')

@section('title', 'ศูนย์กลาง MLM - สายงานและทีม')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ปุ่ม MLM หลัก (กรองเฉพาะ route ที่มีจริง) ─────────────────
    // [emoji, ชื่อ, คำอธิบายย่อย, route, สีพื้นไทล์]
    $primaryActions = [
        ['👥', 'ทีมงาน',        ($member->total_direct_referrals ?? 0) . ' คน', 'user.mlm.team',                    '#5689b8'],
        ['🔗', 'ลิ้งค์เชิญ',     'แชร์รับเงิน',                                  'user.mlm.referral',                '#5aa07e'],
        ['💰', 'คอมมิชชั่น',     'ดูรายได้',                                     'user.mlm.commissions',             '#e0a52e'],
        ['🔮', 'บิลดูดวง',       'ค่าแนะนำ',                                     'user.fortune-referral.commissions', '#8a6fb0'],
        ['🔀', 'Binary',         'ตำแหน่งขา',                                    'user.mlm.binary-position',         '#5e7cc4'],
        ['📊', 'จำลองรายได้',    'คำนวณ',                                        'user.mlm.income-simulator',        '#c46a8e'],
    ];

    // ── ปุ่มรอง ───────────────────────────────────────────────────
    $secondaryActions = [
        ['📋', 'แดชบอร์ด',          'ภาพรวม MLM', 'user.mlm.dashboard'],
        ['🎯', 'จำลองสถานการณ์',    'วางแผนทีม',  'user.mlm.scenario-simulator'],
        ['⚖️', 'เปรียบเทียบรายได้', 'วิเคราะห์',   'user.mlm.income-comparison'],
        ['🔮', 'ชวนเพื่อนดูดวง',    'รับค่าแนะนำ', 'user.fortune-referral.recruit'],
    ];

    // ── ค่าประเภท tree ส่งให้ JS viewer ───────────────────────────
    $jsTreeType = ($member->plan?->type === 'binary' || $member->plan?->type === 'hybrid') ? 'binary' : 'unilevel';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="genealogyPage()" x-init="init()">

    {{-- ── การ์ดต้อนรับ (Hero) ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:18px;">
                {{-- ข้อมูลสมาชิก + Avatar --}}
                <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                    <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:0; overflow:hidden; padding:0;">
                        <img src="{{ auth()->user()->profile_picture_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=6366f1&color=fff&size=80' }}"
                             alt="avatar" style="width:100%; height:100%; object-fit:cover;">
                    </span>
                    <div style="min-width:0;">
                        <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">ศูนย์กลาง MLM · MLM CENTER</div>
                        <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:3px 0 0;">{{ auth()->user()->name }}</h1>
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:9px;">
                            <span class="tp-pill tp-pill-soft tp-num">รหัส: {{ $member->member_code }}</span>
                            <span class="tp-pill" style="color:#fff; background:#5aa07e;"><i class="fas fa-circle-check" style="font-size:10px;"></i> {{ $member->plan?->name ?? 'Active' }}</span>
                            @if($member->rank)
                                <span class="tp-pill" style="color:#fff; background:{{ $member->rank->color ?? 'var(--deep1)' }};">{{ $member->rank->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- สถิติ 4 ใบ --}}
            @php
                $heroStats = [
                    ['สมาชิกทั้งหมด', number_format($member->total_team_members ?? 0),    'var(--deep1)'],
                    ['แนะนำตรง',      number_format($member->total_direct_referrals ?? 0), 'var(--deep1)'],
                    ['ขาซ้าย',        number_format($member->left_leg_members ?? 0),       '#5aa07e'],
                    ['ขาขวา',         number_format($member->right_leg_members ?? 0),      '#d9534f'],
                ];
            @endphp
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; margin-top:18px;">
                @foreach($heroStats as [$label, $val, $color])
                    <div style="padding:14px 16px; border-radius:16px; box-shadow:var(--inset-sm); text-align:center;">
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1.1; color:{{ $color }};">{{ $val }}</div>
                        <div style="font-size:11px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── ปุ่มเมนู MLM หลัก ─────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
        {{-- ผังสายงาน (กำลังดูอยู่) --}}
        <div class="tp-card" style="display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px; box-shadow:var(--inset); border:1px solid color-mix(in srgb, var(--accent1) 40%, transparent);">
            <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:21px;">🌳</span>
            <span style="font-size:13px; font-weight:700; text-align:center;">ผังสายงาน</span>
            <span class="tp-pill tp-pill-gold" style="font-size:10px;">กำลังดู</span>
        </div>

        @foreach($primaryActions as [$emoji, $label, $sub, $route, $color])
            @if(RouteFacade::has($route))
                <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:21px; background:color-mix(in srgb, {{ $color }} 88%, #000 0%);">{{ $emoji }}</span>
                    <span style="font-size:13px; font-weight:700; text-align:center;">{{ $label }}</span>
                    <span style="font-size:10px; color:var(--ink2);">{{ $sub }}</span>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ── ปุ่มรอง ─────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
        @foreach($secondaryActions as [$emoji, $label, $sub, $route])
            @if(RouteFacade::has($route))
                <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:12px; padding:14px 16px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:19px;">{{ $emoji }}</span>
                    <div style="min-width:0;">
                        <div style="font-size:13px; font-weight:700;">{{ $label }}</div>
                        <div style="font-size:11px; color:var(--ink2);">{{ $sub }}</div>
                    </div>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ── แชร์ลิ้งค์รับรายได้ ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 22px; background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 22%, transparent), transparent 72%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
                <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:22px; background:#5aa07e;">🎁</span>
                    <div style="min-width:0;">
                        <div style="font-size:16px; font-weight:800;">แชร์ลิ้งค์รับรายได้ทันที!</div>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">แนะนำเพื่อนมาสมัคร รับค่าคอมมิชชั่นตลอดชีพ</div>
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                    <input type="text" id="referral-link" readonly
                           value="{{ route('register', ['ref' => $member->member_code]) }}"
                           class="tp-input tp-num"
                           style="width:min(320px, 70vw); font-size:12.5px;">
                    <button type="button" @click="copyReferralLink()" class="tp-btn tp-btn-primary" style="white-space:nowrap;">
                        <span x-text="copyIcon">📋</span>
                        <span x-text="copyText">คัดลอกลิ้งค์</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ผังสายงาน (Genealogy Viewer) ─────────────────────────── --}}
    {{-- ⚠️ ใช้ไลบรารี MlmGenealogyV3 (tree visualization) — เก็บ container + JS เดิมทั้งหมด --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        {{-- Toolbar --}}
        <div style="padding:16px 18px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
                <div class="tp-section-h">🌳 ผังสายงาน MLM</div>

                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                    {{-- ค้นหา --}}
                    <div style="position:relative;">
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="doSearch()"
                               @keydown.enter.prevent="searchNext()"
                               placeholder="ค้นหาชื่อ/รหัส..."
                               class="tp-input"
                               style="width:190px; padding-left:30px; font-size:13px;">
                        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--ink2); font-size:13px; pointer-events:none;">🔎</span>
                        <span x-show="searchResultCount > 0"
                              x-text="(searchCurrentIndex + 1) + '/' + searchResultCount"
                              class="tp-num"
                              style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:11px; color:var(--deep1); font-weight:700;"></span>
                    </div>

                    {{-- Filter สถานะ --}}
                    <select x-model="statusFilter" @change="applyFilter()" class="tp-input" style="font-size:13px; width:auto;">
                        <option value="all">ทั้งหมด</option>
                        <option value="active">Active เท่านั้น</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    {{-- ปุ่ม Fit --}}
                    <button type="button" @click="fitToScreen()" class="tp-btn tp-btn-sm">🔄 พอดีจอ</button>
                </div>
            </div>
        </div>

        {{-- Genealogy Container --}}
        <div style="position:relative;">
            <div id="genealogy-container" style="min-height:500px;"></div>

            {{-- Node Detail Panel (Sidebar) --}}
            <div x-show="selectedNode" x-transition
                 style="position:absolute; top:0; right:0; width:min(320px, 100%); height:100%; overflow-y:auto; z-index:30;
                        background:color-mix(in srgb, var(--surf) 96%, transparent); backdrop-filter:blur(8px);
                        border-left:1px solid color-mix(in srgb, var(--ink2) 14%, transparent); box-shadow:var(--inset);">
                <div style="padding:20px; position:relative;">
                    {{-- ปิด --}}
                    <button type="button" @click="selectedNode = null" class="tp-icon-btn" style="position:absolute; top:12px; right:12px;">
                        <i class="fas fa-times"></i>
                    </button>

                    {{-- Avatar + ชื่อ --}}
                    <div style="text-align:center; margin-bottom:16px;">
                        <img :src="selectedNode?.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent((selectedNode?.name || 'U').charAt(0)) + '&background=6366f1&color=fff&size=80'"
                             style="width:64px; height:64px; border-radius:50%; margin:0 auto; border:4px solid; box-shadow:var(--inset-sm); object-fit:cover;"
                             :style="'border-color:' + (selectedNode?.retention_status === 'active' ? '#5aa07e' : (selectedNode?.retention_status === 'grace_period' ? '#e0a52e' : (selectedNode?.retention_status === 'inactive' ? '#d9534f' : 'var(--ink2)')))">
                        <h3 style="margin:8px 0 0; font-size:17px; font-weight:800;" x-text="selectedNode?.name || '-'"></h3>
                        <p class="tp-num" style="margin:2px 0 0; font-size:13px; color:var(--ink2);" x-text="selectedNode?.member_code || ''"></p>
                    </div>

                    {{-- Rank Badge --}}
                    <div x-show="selectedNode?.rank_name" style="display:flex; justify-content:center; margin-bottom:16px;">
                        <span class="tp-pill" style="color:#fff;"
                              :style="'background:' + (selectedNode?.rank_color || 'var(--deep1)')"
                              x-text="selectedNode?.rank_name"></span>
                    </div>

                    {{-- รายละเอียด --}}
                    <div style="display:flex; flex-direction:column; gap:9px; font-size:13px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">สถานะ</span>
                            <span style="font-weight:700;"
                                  :style="'color:' + (selectedNode?.retention_status === 'active' ? '#5aa07e' : (selectedNode?.retention_status === 'grace_period' ? '#e0a52e' : (selectedNode?.retention_status === 'inactive' ? '#d9534f' : 'var(--ink)')))"
                                  x-text="selectedNode?.retention_status === 'active' ? 'Active' : (selectedNode?.retention_status === 'grace_period' ? 'ใกล้หมดอายุ' : 'Inactive')"></span>
                        </div>
                        <div x-show="selectedNode?.retention_days_left != null" style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">หมดอายุใน</span>
                            <span class="tp-num" style="font-weight:700;" x-text="(selectedNode?.retention_days_left || 0) + ' วัน'"></span>
                        </div>
                        <div x-show="selectedNode?.email" style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">อีเมล</span>
                            <span style="font-weight:700; font-size:12px;" x-text="selectedNode?.email || '-'"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">PV ส่วนตัว</span>
                            <span class="tp-num" style="font-weight:700;" x-text="(selectedNode?.monthly_pv || 0).toLocaleString()"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">ทีมทั้งหมด</span>
                            <span class="tp-num" style="font-weight:700;" x-text="(selectedNode?.total_team_members || 0).toLocaleString()"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">แนะนำตรง</span>
                            <span class="tp-num" style="font-weight:700;" x-text="(selectedNode?.direct_referrals || 0).toLocaleString()"></span>
                        </div>
                        <div x-show="selectedNode?.joined_at" style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:12px; box-shadow:var(--inset-sm);">
                            <span style="color:var(--ink2);">เข้าร่วมเมื่อ</span>
                            <span class="tp-num" style="font-weight:700; font-size:12px;" x-text="selectedNode?.joined_at || '-'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Legend --}}
            <div style="position:absolute; bottom:14px; left:14px; display:flex; flex-wrap:wrap; gap:12px; z-index:10;
                        padding:8px 12px; border-radius:12px; font-size:11px;
                        background:color-mix(in srgb, var(--surf) 92%, transparent); backdrop-filter:blur(6px); box-shadow:var(--inset-sm);">
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="width:10px; height:10px; border-radius:50%; background:#5aa07e;"></span>
                    <span style="color:var(--ink2);">Active</span>
                </div>
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="width:10px; height:10px; border-radius:50%; background:#e0a52e;"></span>
                    <span style="color:var(--ink2);">ใกล้หมดอายุ</span>
                </div>
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="width:10px; height:10px; border-radius:50%; background:#d9534f;"></span>
                    <span style="color:var(--ink2);">Inactive</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── วิธีใช้งาน ───────────────────────────────────────────── --}}
    @php
        $tips = [
            ['🖱️', 'ลากเมาส์เลื่อนดู'],
            ['🔍', 'Scroll เพื่อซูม'],
            ['👆', 'คลิกดูรายละเอียด'],
            ['🔎', 'ค้นหาสมาชิก'],
        ];
    @endphp
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💡 วิธีใช้งานผังสายงาน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
            @foreach($tips as [$emoji, $text])
                <div style="display:flex; align-items:center; gap:10px; padding:11px 13px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <span style="font-size:18px;">{{ $emoji }}</span>
                    <span style="font-size:12.5px; color:var(--ink);">{{ $text }}</span>
                </div>
            @endforeach
        </div>
    </div>

</div>

@vite('resources/js/mlm-genealogy-v3.js')

@push('scripts')
<script>
function genealogyPage() {
    return {
        viewer: null,
        selectedNode: null,
        searchQuery: '',
        searchResultCount: 0,
        searchCurrentIndex: -1,
        statusFilter: 'all',
        copyIcon: '📋',
        copyText: 'คัดลอกลิ้งค์',

        init() {
            this.$nextTick(() => {
                const container = document.getElementById('genealogy-container');
                if (!container || typeof MlmGenealogyV3 === 'undefined') return;

                this.viewer = new MlmGenealogyV3(container, {
                    apiUrl: '{{ route("user.mlm.genealogy-data") }}',
                    treeType: @json($jsTreeType),
                    maxDepth: 5,
                    onNodeClick: (node) => {
                        this.selectedNode = node;
                    }
                });
                this.viewer.loadTree();
            });
        },

        doSearch() {
            if (!this.viewer) return;
            const results = this.viewer.search(this.searchQuery);
            this.searchResultCount = results.length;
            this.searchCurrentIndex = results.length > 0 ? 0 : -1;
        },

        searchNext() {
            if (!this.viewer) return;
            this.viewer.searchNext();
            if (this.searchResultCount > 0) {
                this.searchCurrentIndex = (this.searchCurrentIndex + 1) % this.searchResultCount;
            }
        },

        fitToScreen() {
            if (this.viewer) this.viewer.fitToScreen();
        },

        applyFilter() {
            // Filter จะถูกใช้ในครั้งถัดไปที่ render — ตอนนี้ reload tree
            if (this.viewer) this.viewer.loadTree();
        },

        copyReferralLink() {
            const input = document.getElementById('referral-link');
            if (!input) return;

            navigator.clipboard.writeText(input.value).then(() => {
                this.copyIcon = '✅';
                this.copyText = 'คัดลอกแล้ว!';
                if (window.showNotification) window.showNotification('คัดลอกลิ้งค์เชิญสำเร็จ!', 'success');
                setTimeout(() => {
                    this.copyIcon = '📋';
                    this.copyText = 'คัดลอกลิ้งค์';
                }, 2000);
            }).catch(() => {
                if (window.showNotification) window.showNotification('คัดลอกไม่สำเร็จ', 'error');
            });
        }
    };
}
</script>
@endpush
@endsection
