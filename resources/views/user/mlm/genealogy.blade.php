{{--
    ผังสายงาน MLM (ธีมโนวาเต็ม) — MlmDashboardController::genealogy
    ตัวแปร: $member (พร้อม plan), $treeType (binary|unilevel), $referralUrl
    ตัวแสดงผังคือ resources/js/mlm-genealogy-v3.js (window.MlmGenealogyV3) ดึงข้อมูลจาก user.mlm.genealogy-data
    - ข้อมูลผังไม่มีอีเมล/รายได้ของสมาชิกคนอื่นแล้ว (ตัดที่ controller) แผงรายละเอียดจึงไม่มีช่องอีเมล
    - ตัวเลือก "กรองสถานะ" เดิมแค่โหลดผังใหม่ ไม่ได้กรองจริง (ตัวแสดงผังไม่มีความสามารถนี้) จึงเอาออก
--}}
@extends('layouts.user-nova')

@section('title', 'ผังสายงาน MLM')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $me = auth()->user();
    $initial = mb_strtoupper(mb_substr(trim((string) $me->name) ?: 'U', 0, 1));
    $rank = $me->currentRank;

    // เมนู MLM: [ภาพ, ป้าย, คำอธิบาย, route]
    $tiles = array_filter([
        ['images/nova/mascot/welcome.webp', 'ทีมงาน', number_format((int) $member->total_direct_referrals).' คน', 'user.mlm.team'],
        ['images/nova/deco/gift.webp', 'ลิงก์เชิญ', 'แชร์รับเงิน', 'user.mlm.referral'],
        ['images/nova/deco/coins.webp', 'คอมมิชชั่น', 'ดูรายได้', 'user.mlm.commissions'],
        ['images/nova/icons/tarot.webp', 'บิลดูดวง', 'ค่าแนะนำ', 'user.fortune-referral.commissions'],
        ['images/nova/wallet/scale.webp', 'Binary', 'ตำแหน่งขา', $treeType === 'binary' ? 'user.mlm.binary-position' : ''],
        ['images/nova/deco/crystal.webp', 'จำลองรายได้', 'คำนวณ', 'user.mlm.income-simulator'],
    ], fn ($t) => $t[3] !== '' && $nvR($t[3]));

    $more = array_filter([
        ['grid', 'แดชบอร์ด MLM', 'ภาพรวมทีมและรายได้', 'user.mlm.dashboard'],
        ['users', 'จำลองสถานการณ์', 'ลองเปิด/ปิดสมาชิกในผังตัวอย่าง', 'user.mlm.scenario-simulator'],
        ['swap', 'เปรียบเทียบรายได้', 'เทียบแผนตัวอย่าง 3 แบบ', 'user.mlm.income-comparison'],
        ['moonstar', 'ชวนเพื่อนดูดวง', 'รับค่าแนะนำจากบิลดูดวง', 'user.fortune-referral.recruit'],
    ], fn ($t) => $nvR($t[3]));

    $stats = [
        ['สมาชิกทั้งหมด', number_format((int) $member->total_team_members)],
        ['แนะนำตรง', number_format((int) $member->total_direct_referrals)],
    ];
    if ($treeType === 'binary') {
        $stats[] = ['ขาซ้าย', number_format((int) $member->left_leg_members)];
        $stats[] = ['ขาขวา', number_format((int) $member->right_leg_members)];
    }
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page" x-data="nvmMlGenealogy(@js(route('user.mlm.genealogy-data')), @js($treeType))">

<section class="nvm-ph" aria-labelledby="nvm-mlg-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">MLM center</div>
            <div class="nvm-ml-who">
                <span class="nvm-avatar">
                    @if($me->profile_picture_url)
                        <img src="{{ $me->profile_picture_url }}" alt="" width="52" height="52">
                    @else
                        {{ $initial }}
                    @endif
                </span>
                <h1 id="nvm-mlg-h">{{ $me->name }}</h1>
            </div>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span class="nvm-num">{{ $member->member_code }}</span></span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>{{ $member->plan?->display_name ?? 'MLM' }}</span>
                {{-- rank อ่านผ่าน user->currentRank (MlmMember ไม่มี relation rank) --}}
                @if($rank)
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ $rank->name_th ?: $rank->name }}</span>
                @endif
            </div>
            <dl class="nvm-ml-herostats">
                @foreach($stats as [$label, $value])
                    <div><dt>{{ $label }}</dt><dd class="nvm-num">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/garland.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(count($tiles))
    <nav class="nvm-tiles" aria-label="เมนู MLM">
        @foreach($tiles as [$img, $label, $sub, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
                <small>{{ $sub }}</small>
            </a>
        @endforeach
    </nav>
@endif

<section class="nvm-card nvm-alert nvm-alert--ok" aria-label="ลิงก์แนะนำ">
    <img class="nvm-alert__img" src="{{ asset('images/nova/deco/gift.webp') }}" alt="" width="58" height="58" loading="lazy">
    <div class="nvm-alert__txt">
        <b>แชร์ลิงก์ ชวนเพื่อนมาร่วมทีม</b>
        <p>เพื่อนสมัครผ่านลิงก์นี้ จะอยู่ในสายงานของคุณอัตโนมัติ</p>
        <div class="nvm-copy nvm-copy--box">
            <code>{{ $referralUrl }}</code>
            <button type="button" @click="nvmCopy(@js($referralUrl), 'คัดลอกลิงก์เชิญแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
        </div>
    </div>
</section>

<section class="nvm-card nvm-box nvm-ml-tree" aria-labelledby="nvm-mlg-t-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Genealogy</small><h2 id="nvm-mlg-t-h">ผังสายงาน{{ $treeType === 'binary' ? ' Binary' : ' Unilevel' }}</h2></div>
    </div>

    <div class="nvm-ml-toolbar">
        <label class="nvm-ml-search">
            <svg class="nvm-ico"><use href="#nvm-i-search"/></svg>
            <span class="nvm-sr">ค้นหาสมาชิกในผัง</span>
            <input type="search" class="nvm-input" placeholder="ค้นหาชื่อหรือรหัสสมาชิก" x-model="query" @input.debounce.300ms="doSearch()" @keydown.enter.prevent="searchNext()">
            <span class="nvm-chip nvm-chip--gold nvm-num" x-show="resultCount > 0" x-cloak x-text="(currentIndex + 1) + '/' + resultCount"></span>
        </label>
        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="searchNext()" :disabled="resultCount < 2">ถัดไป</button>
        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="fit()"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>พอดีจอ</button>
    </div>
    <p class="nvm-err" x-show="query && searched && resultCount === 0" x-cloak>ไม่พบสมาชิกที่ตรงกับคำค้นในผังที่โหลดอยู่</p>

    <div class="nvm-ml-tree__stage">
        <div id="genealogy-container" class="nvm-ml-tree__canvas" x-ref="canvas"></div>

        <p class="nvm-ml-tree__miss" x-show="missing" x-cloak>
            <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>โหลดตัวแสดงผังไม่สำเร็จ ลองรีเฟรชหน้าอีกครั้ง
        </p>

        <ul class="nvm-ml-legend" aria-label="ความหมายของสี">
            <li><i class="is-ok"></i>รักษายอดแล้ว</li>
            <li><i class="is-warn"></i>ใกล้หมดอายุ</li>
            <li><i class="is-bad"></i>ยังไม่ถึงเกณฑ์</li>
        </ul>

        <aside class="nvm-ml-node" x-show="node" x-cloak x-transition.opacity aria-label="รายละเอียดสมาชิก">
            <button type="button" class="nvm-ml-node__x" @click="node = null" aria-label="ปิด"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg></button>
            <div class="nvm-ml-node__head">
                <span class="nvm-avatar nvm-ml-node__av" :class="'is-' + tone(node)">
                    <template x-if="node && node.avatar_url"><img :src="node.avatar_url" alt=""></template>
                    <template x-if="node && ! node.avatar_url"><span x-text="initial(node)"></span></template>
                </span>
                <b x-text="node ? (node.name || '-') : ''"></b>
                <small class="nvm-num" x-text="node ? (node.member_code || '') : ''"></small>
                <span class="nvm-chip nvm-chip--gold" x-show="node && node.rank_name" x-text="node ? node.rank_name : ''"></span>
            </div>
            <dl class="nvm-kv">
                <dt>สถานะ</dt><dd><span class="nvm-chip" :class="'nvm-chip--' + tone(node)" x-text="statusText(node)"></span></dd>
                <template x-if="node && node.retention_days_left != null">
                    <div class="nvm-ml-kvrow"><dt>หมดอายุใน</dt><dd class="nvm-num" x-text="node.retention_days_left + ' วัน'"></dd></div>
                </template>
                <dt>PV เดือนนี้</dt><dd class="nvm-num" x-text="num(node && node.monthly_pv)"></dd>
                <template x-if="node && node.total_team_members != null">
                    <div class="nvm-ml-kvrow"><dt>ทีมทั้งหมด</dt><dd class="nvm-num" x-text="num(node.total_team_members)"></dd></div>
                </template>
                <dt>แนะนำตรง</dt><dd class="nvm-num" x-text="num(node && node.direct_referrals)"></dd>
                <template x-if="node && node.joined_at">
                    <div class="nvm-ml-kvrow"><dt>เข้าร่วมเมื่อ</dt><dd class="nvm-num" x-text="node.joined_at"></dd></div>
                </template>
            </dl>
        </aside>
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-mlg-how-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How to use</small><h2 id="nvm-mlg-how-h">วิธีใช้งานผังสายงาน</h2></div></div>
        <ol class="nvm-steps">
            <li>ลากเพื่อเลื่อนดูผัง ใช้ล้อเมาส์หรือถ่างสองนิ้วเพื่อซูม</li>
            <li>แตะที่สมาชิกเพื่อดูรายละเอียด</li>
            <li>พิมพ์ชื่อหรือรหัสเพื่อค้นหา กด Enter เพื่อไปคนถัดไป</li>
            <li>ผังแสดงลึก 5 ชั้นจากตัวคุณ</li>
        </ol>
    </section>

    @if(count($more))
        <nav class="nvm-card nvm-box" aria-labelledby="nvm-mlg-more-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">More tools</small><h2 id="nvm-mlg-more-h">เครื่องมือเพิ่มเติม</h2></div></div>
            <ul class="nvm-txs">
                @foreach($more as [$icon, $label, $sub, $route])
                    <li>
                        <a class="nvm-tx nvm-ml-go" href="{{ route($route) }}">
                            <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                            <span class="nvm-tx__t"><b>{{ $label }}</b><small>{{ $sub }}</small></span>
                            <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</div>

</div>
@endsection

@push('scripts')
@vite('resources/js/mlm-genealogy-v3.js')
<script>
/**
 * หน้าผังสายงาน — ห่อ MlmGenealogyV3 (ตัววาดผัง) ด้วย Alpine
 * สคริปต์ของ Vite เป็น module จึงรันหลังสคริปต์นี้ — รอจน window.MlmGenealogyV3 พร้อมก่อนสร้างผัง
 */
function nvmMlGenealogy(apiUrl, treeType) {
    return {
        viewer: null,
        node: null,
        query: '',
        searched: false,
        resultCount: 0,
        currentIndex: -1,
        missing: false,

        init() {
            let tries = 0;
            const boot = () => {
                if (typeof window.MlmGenealogyV3 === 'undefined') {
                    if (++tries > 50) { this.missing = true; return; }
                    setTimeout(boot, 100);
                    return;
                }
                this.viewer = new window.MlmGenealogyV3(this.$refs.canvas, {
                    apiUrl: apiUrl,
                    treeType: treeType,
                    maxDepth: 5,
                    onNodeClick: (node) => { this.node = node; },
                });
                this.viewer.loadTree();
            };
            this.$nextTick(boot);
        },

        doSearch() {
            if (! this.viewer) { return; }
            const results = this.query.trim() ? this.viewer.search(this.query) : [];
            this.searched = this.query.trim() !== '';
            this.resultCount = results.length;
            this.currentIndex = results.length > 0 ? 0 : -1;
        },

        searchNext() {
            if (! this.viewer || this.resultCount === 0) { return; }
            this.viewer.searchNext();
            this.currentIndex = (this.currentIndex + 1) % this.resultCount;
        },

        fit() {
            if (this.viewer) { this.viewer.fitToScreen(); }
        },

        tone(n) {
            const s = n && n.retention_status;
            return s === 'active' ? 'ok' : (s === 'grace_period' ? 'warn' : (s === 'inactive' ? 'bad' : 'info'));
        },

        statusText(n) {
            const s = n && n.retention_status;
            return s === 'active' ? 'รักษายอดแล้ว' : (s === 'grace_period' ? 'ใกล้หมดอายุ' : 'ยังไม่ถึงเกณฑ์');
        },

        initial(n) {
            return ((n && n.name) || 'U').trim().charAt(0).toUpperCase();
        },

        num(v) {
            return Number(v || 0).toLocaleString('th-TH');
        },
    };
}
</script>
@endpush
