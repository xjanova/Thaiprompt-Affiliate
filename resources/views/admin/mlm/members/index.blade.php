@extends('layouts.admin-v4')

@section('title', 'จัดการสมาชิก')

@php
    use Illuminate\Support\Str;

    // สถิติ (อิงพฤติกรรมเดิม: รวมจากหน้าปัจจุบันสำหรับ PV/รายได้)
    $stTotal    = $members->total();
    $stActive   = $members->where('status', 'active')->count();
    $stPv       = $members->sum('total_pv');
    $stEarnings = $members->sum('total_earnings');

    $curStatus = request('status', '');

    // ข้อมูลสำหรับแผงรายละเอียด (client-side, ไม่ต้อง AJAX)
    $memberData = $members->getCollection()->map(function ($m) {
        $name = $m->user->name ?? '—';
        return [
            'id'        => $m->id,
            'name'      => $name,
            'code'      => $m->member_code,
            'initial'   => mb_strtoupper(mb_substr($name, 0, 1)),
            'plan'      => $m->plan?->display_name ?? 'ไม่มีแผน',
            'pv'        => number_format($m->total_pv, 2),
            'earnings'  => number_format($m->total_earnings, 2),
            'refs'      => (int) $m->total_direct_referrals,
            'status'    => $m->status,
            'phone'     => $m->user->phone ?? '—',
            'email'     => $m->user->email ?? '—',
            'province'  => $m->user->city ?? ($m->user->state ?? '—'),
            'joined'    => optional($m->joined_at ?? $m->created_at)->locale('th')->translatedFormat('M Y'),
            'showUrl'   => route('admin.mlm.members.show', $m),
            'treeUrl'   => route('admin.mlm.members.genealogy', $m),
            'statusUrl' => route('admin.mlm.members.status', $m),
        ];
    })->values();

    $statusMeta = [
        'active'    => ['label' => 'ใช้งาน',   'color' => '#5aa07e'],
        'inactive'  => ['label' => 'ไม่ใช้งาน', 'color' => '#9a8f7c'],
        'suspended' => ['label' => 'ระงับ',     'color' => '#d9534f'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="tpMembers(@js($memberData))">

    {{-- หัวข้อ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบ MLM · สมาชิก</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">จัดการสมาชิก</h1>
        </div>
        <a href="{{ route('admin.mlm.members.create') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-user-plus"></i> เพิ่มสมาชิก
        </a>
    </div>

    {{-- KPI --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @php
            $cards = [
                ['สมาชิกทั้งหมด', 'Total Members', number_format($stTotal), 'fa-users'],
                ['ใช้งาน', 'Active', number_format($stActive), 'fa-circle-check'],
                ['PV (หน้านี้)', 'Page PV', number_format($stPv, 0), 'fa-gem'],
                ['รายได้ (หน้านี้)', 'Page Earnings', '฿' . number_format($stEarnings, 0), 'fa-coins'],
            ];
        @endphp
        @foreach($cards as [$label, $en, $val, $icon])
            <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:18px;"><i class="fas {{ $icon }}"></i></span>
                <div style="min-width:0;">
                    <div style="font-size:12px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1.1;">{{ $val }}</div>
                    <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $en }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- master-detail --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:16px; align-items:start;">

        {{-- ซ้าย: ค้นหา + กรอง + รายชื่อ --}}
        <div class="tp-card" style="padding:16px; display:flex; flex-direction:column; gap:14px;">
            {{-- ค้นหา + แผน (GET form, ตัด field ว่างก่อนส่ง) --}}
            <form method="GET" action="{{ route('admin.mlm.members.index') }}"
                  onsubmit="for (const el of this.elements) { if (el.name && !el.value) el.disabled = true; }">
                <input type="hidden" name="status" value="{{ $curStatus }}">
                <div class="tp-well" style="display:flex; align-items:center; gap:10px; padding:11px 15px;">
                    <i class="fas fa-magnifying-glass" style="color:var(--ink2); font-size:13px;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ · อีเมล · รหัสสมาชิก…" class="tp-input" style="box-shadow:none; background:transparent; padding:0;">
                </div>
                <div style="display:flex; gap:8px; margin-top:10px;">
                    <select name="plan_id" class="tp-well tp-input" style="flex:1; padding:9px 12px; font-size:12.5px;">
                        <option value="">ทุกแผน</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->display_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="flex:none;">กรอง</button>
                </div>
            </form>

            {{-- ชิปสถานะ (ลิงก์, คงค่าค้นหา/แผนเดิมไว้) --}}
            <div style="display:flex; flex-wrap:wrap; gap:7px;">
                @php
                    $chips = ['' => 'ทั้งหมด', 'active' => 'ใช้งาน', 'suspended' => 'ระงับ', 'inactive' => 'ไม่ใช้งาน'];
                @endphp
                @foreach($chips as $val => $label)
                    @php $on = $curStatus === $val; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['status' => $val === '' ? null : $val, 'page' => null]) }}"
                       class="tp-btn tp-btn-sm" style="{{ $on ? 'box-shadow:var(--inset); color:var(--deep1);' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- รายชื่อ --}}
            <div style="display:flex; flex-direction:column; gap:7px; max-height:560px; overflow-y:auto; padding-right:2px;">
                @forelse($memberData as $i => $m)
                    <button type="button" @click="select({{ $i }})"
                            class="tp-card" :style="sel.id === {{ $m['id'] }} ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);'"
                            style="cursor:pointer; text-align:left; display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:15px;">
                        <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px;">{{ $m['initial'] }}</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $m['name'] }}</div>
                            <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $m['code'] }}</div>
                        </div>
                        <div style="text-align:right; flex:none;">
                            <span class="tp-pill tp-pill-soft" style="font-size:9.5px;">{{ Str::limit($m['plan'], 10) }}</span>
                            <div style="display:flex; align-items:center; gap:5px; justify-content:flex-end; margin-top:5px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:{{ $statusMeta[$m['status']]['color'] ?? '#9a8f7c' }};"></span>
                                <span style="font-size:10px; color:var(--ink2);">{{ $statusMeta[$m['status']]['label'] ?? $m['status'] }}</span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:40px 0; font-size:13px;">
                        <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:8px; opacity:.5;"></i>
                        ไม่พบข้อมูลสมาชิก
                    </div>
                @endforelse
            </div>

            {{-- pagination --}}
            @if($members->hasPages())
                <div class="tp-num" style="display:flex; justify-content:center;">{{ $members->withQueryString()->links() }}</div>
            @endif
        </div>

        {{-- ขวา: รายละเอียด --}}
        <div class="tp-card" style="padding:22px;" x-show="sel" x-cloak>
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:64px; height:64px; border-radius:18px; font-size:26px;" x-text="sel.initial"></span>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:19px; font-weight:800;" x-text="sel.name"></span>
                        <span class="tp-pill tp-pill-gold" x-text="sel.plan"></span>
                    </div>
                    <div class="tp-num" style="font-size:12px; color:var(--ink2); margin-top:3px;">
                        <span x-text="sel.code"></span> · เข้าร่วม <span x-text="sel.joined"></span>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:9px; margin-top:16px; flex-wrap:wrap;">
                <a :href="sel.showUrl" class="tp-btn tp-btn-primary tp-btn-sm"><i class="fas fa-id-card"></i> จัดการ</a>
                <a :href="sel.treeUrl" class="tp-btn tp-btn-sm"><i class="fas fa-sitemap"></i> ผังสายงาน</a>
                <button type="button" @click="toggleStatus()" class="tp-btn tp-btn-sm"
                        :style="sel.status === 'active' ? 'color:#d9534f;' : 'color:#5aa07e;'">
                    <i class="fas" :class="sel.status === 'active' ? 'fa-ban' : 'fa-circle-check'"></i>
                    <span x-text="sel.status === 'active' ? 'ระงับ' : 'เปิดใช้งาน'"></span>
                </button>
            </div>

            {{-- mini stats --}}
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:18px;">
                <div class="tp-well" style="padding:12px; border-radius:14px; text-align:center;">
                    <div class="tp-num" style="font-size:17px; font-weight:800; color:var(--deep1);" x-text="sel.pv"></div>
                    <div style="font-size:10.5px; color:var(--ink2); margin-top:2px;">PV สะสม</div>
                </div>
                <div class="tp-well" style="padding:12px; border-radius:14px; text-align:center;">
                    <div class="tp-num" style="font-size:17px; font-weight:800; color:var(--deep1);">฿<span x-text="sel.earnings"></span></div>
                    <div style="font-size:10.5px; color:var(--ink2); margin-top:2px;">รายได้</div>
                </div>
                <div class="tp-well" style="padding:12px; border-radius:14px; text-align:center;">
                    <div class="tp-num" style="font-size:17px; font-weight:800; color:var(--deep1);" x-text="sel.refs"></div>
                    <div style="font-size:10.5px; color:var(--ink2); margin-top:2px;">ผู้แนะนำตรง</div>
                </div>
            </div>

            {{-- ข้อมูลติดต่อ --}}
            <div class="tp-divider" style="margin:18px 0;"></div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-phone" style="margin-right:5px;"></i>โทรศัพท์</div>
                    <div class="tp-num" style="font-size:13.5px; font-weight:600; margin-top:3px;" x-text="sel.phone"></div>
                </div>
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-envelope" style="margin-right:5px;"></i>อีเมล</div>
                    <div style="font-size:13px; font-weight:600; margin-top:3px; overflow:hidden; text-overflow:ellipsis;" x-text="sel.email"></div>
                </div>
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-location-dot" style="margin-right:5px;"></i>จังหวัด</div>
                    <div style="font-size:13.5px; font-weight:600; margin-top:3px;" x-text="sel.province"></div>
                </div>
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-circle-info" style="margin-right:5px;"></i>สถานะ</div>
                    <div style="font-size:13.5px; font-weight:700; margin-top:3px;"
                         :style="'color:' + (sel.status === 'active' ? '#5aa07e' : sel.status === 'suspended' ? '#d9534f' : '#9a8f7c')"
                         x-text="sel.status === 'active' ? 'ใช้งาน' : sel.status === 'suspended' ? 'ระงับ' : 'ไม่ใช้งาน'"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function tpMembers(list) {
        return {
            members: list,
            sel: list.length ? list[0] : null,
            select(i) { this.sel = this.members[i]; },
            toggleStatus() {
                if (!this.sel) return;
                const next = this.sel.status === 'active' ? 'suspended' : 'active';
                fetch(this.sel.statusUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: next }),
                })
                .then(r => r.json())
                .then(d => {
                    if (d && d.success) {
                        this.$dispatch('notify', { type: 'success', message: 'อัปเดตสถานะเป็น ' + next + ' แล้ว' });
                        setTimeout(() => window.location.reload(), 700);
                    } else {
                        this.$dispatch('notify', { type: 'error', message: 'อัปเดตสถานะไม่สำเร็จ' });
                    }
                })
                .catch(() => this.$dispatch('notify', { type: 'error', message: 'เกิดข้อผิดพลาด' }));
            },
        };
    }
</script>
@endpush
@endsection
