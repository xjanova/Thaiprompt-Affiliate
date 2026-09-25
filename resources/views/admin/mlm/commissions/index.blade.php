@extends('layouts.admin-v4')

@section('title', 'คอมมิชชั่นค่าแนะนำ (MLM)')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $statusMeta = [
        'pending' => ['รออนุมัติ', $c['warn']],
        'approved' => ['อนุมัติแล้ว', $c['info']],
        'paid' => ['จ่ายแล้ว', $c['ok']],
        'rejected' => ['ปฏิเสธ', $c['bad']],
        'cancelled' => ['ยกเลิก', $c['mute']],
        'clawback' => ['ดึงคืน', $c['bad']],
    ];
    // ค่าตรงกับ enum mlm_commissions.type (ตัวกรองเดิมใช้ unilevel/binary ที่ไม่มีใน DB → กรองแล้วไม่เจออะไร)
    $typeMeta = [
        'unilevel_direct' => 'ยูนิเลเวล (ชั้นตรง)',
        'unilevel_indirect' => 'ยูนิเลเวล (ชั้นลึก)',
        'unilevel_rollup' => 'ยูนิเลเวล (Roll-up)',
        'binary_pair' => 'ไบนารี (จับคู่)',
        'binary_matching' => 'ไบนารี (Matching)',
        'sponsor_bonus' => 'โบนัสผู้แนะนำ',
        'rank_bonus' => 'โบนัสตำแหน่ง',
        'leadership_bonus' => 'โบนัสผู้นำ',
        'matching_bonus' => 'Matching โบนัส',
        'pool_bonus' => 'โบนัสกองกลาง',
    ];
    $pageIds = $commissions->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->values();
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{
        selected: [],
        pageIds: @js($pageIds),
        busy: false,
        urls: { approve: @js(route('admin.mlm.commissions.approve')), pay: @js(route('admin.mlm.commissions.pay')), bulk: @js(route('admin.mlm.commissions.bulk-action')) },
        get allChecked() { return this.pageIds.length > 0 && this.selected.length === this.pageIds.length; },
        toggleAll(on) { this.selected = on ? [...this.pageIds] : []; },
        async run(action, ids) {
            ids = ids || this.selected;
            if (!ids.length) { this.$dispatch('notify', { type: 'warning', message: 'กรุณาเลือกคอมมิชชั่นก่อน' }); return; }
            let reason = null;
            if (action === 'reject') {
                reason = prompt('เหตุผลที่ปฏิเสธ (' + ids.length + ' รายการ)');
                if (!reason) return;
            } else {
                const label = action === 'approve' ? 'อนุมัติ' : 'จ่ายเงิน';
                if (!confirm(label + 'คอมมิชชั่น ' + ids.length + ' รายการ?' + (action === 'pay' ? ' (เงินเข้ากระเป๋าสมาชิกทันที)' : ''))) return;
            }
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            fd.append('action', action);
            ids.forEach(id => fd.append('commission_ids[]', id));
            if (reason) fd.append('reason', reason);
            const url = action === 'reject' ? this.urls.bulk : this.urls[action];
            this.busy = true;
            try {
                const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error(data.message || 'fail');
                this.$dispatch('notify', { type: 'success', message: data.message || 'สำเร็จ' });
                setTimeout(() => window.location.reload(), 700);
            } catch (e) {
                this.$dispatch('notify', { type: 'error', message: (e && e.message && e.message !== 'fail') ? e.message : 'ดำเนินการไม่สำเร็จ กรุณาลองใหม่' });
            } finally {
                this.busy = false;
            }
        }
     }">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบค่าแนะนำ · คอมมิชชั่น</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">คอมมิชชั่นค่าแนะนำ 💰</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">จ่ายจากกองทุนผู้แนะนำที่หักจากยอดขายสินค้าที่มี PV — ตรวจ อนุมัติ แล้วจ่ายเข้ากระเป๋าสมาชิก</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            @if(($stats['pending_count'] ?? 0) > 0)
                <form method="POST" action="{{ route('admin.mlm.commissions.approve-all') }}"
                      @submit="if (!confirm(@js('อนุมัติคอมมิชชั่นที่รออนุมัติทั้งหมด '.number_format($stats['pending_count']).' รายการ?'))) $event.preventDefault()">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm"><i class="fas fa-check-double"></i> อนุมัติทั้งหมด</button>
                </form>
            @endif
            @if(($stats['approved_count'] ?? 0) > 0)
                <form method="POST" action="{{ route('admin.mlm.commissions.pay-all') }}"
                      @submit="if (!confirm(@js('จ่ายคอมมิชชั่นที่อนุมัติแล้วทั้งหมด '.number_format($stats['approved_count']).' รายการ ยอด ฿'.number_format($stats['approved_amount'], 2).' ?'))) $event.preventDefault()">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-money-bill-transfer"></i> จ่ายทั้งหมด</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
        @foreach([
            ['รออนุมัติ', $stats['pending_count'] ?? 0, $stats['pending_amount'] ?? 0, $c['warn'], 'fa-hourglass-half', 'pending'],
            ['อนุมัติแล้ว (รอจ่าย)', $stats['approved_count'] ?? 0, $stats['approved_amount'] ?? 0, $c['info'], 'fa-circle-check', 'approved'],
            ['จ่ายแล้ว', $stats['paid_count'] ?? 0, $stats['paid_amount'] ?? 0, $c['ok'], 'fa-sack-dollar', 'paid'],
        ] as [$label, $count, $amount, $color, $icon, $status])
            <a href="{{ route('admin.mlm.commissions.index', ['status' => $status]) }}" class="tp-card tp-card-hover" style="padding:16px; text-decoration:none; color:inherit;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; font-size:17px; background:{{ $color }};"><i class="fas {{ $icon }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:22px; font-weight:800; line-height:1;">{{ number_format((int) $count) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
                <div class="tp-num" style="font-size:14px; font-weight:700; margin-top:10px; color:{{ $color }};">฿{{ number_format((float) $amount, 2) }}</div>
            </a>
        @endforeach
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.mlm.commissions.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="{{ $lbl }}">แผน</label>
                <select name="plan_id" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) request('plan_id') === (string) $plan->id)>{{ $plan->display_name ?? $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">สถานะ</label>
                <select name="status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    @foreach($statusMeta as $value => [$label])
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">ประเภท</label>
                <select name="type" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    @foreach($typeMeta as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">ตั้งแต่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input">
            </div>
            <div>
                <label style="{{ $lbl }}">ถึง</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input">
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.mlm.commissions.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i></a>
            </div>
        </form>
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:12px 16px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; box-shadow:var(--inset-sm);">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                <input type="checkbox" :checked="allChecked" @change="toggleAll($event.target.checked)" style="accent-color:var(--accent1); width:17px; height:17px;">
                เลือกทั้งหน้า <span class="tp-pill tp-pill-soft tp-num" x-show="selected.length" x-cloak x-text="selected.length + ' รายการ'"></span>
            </label>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="tp-btn tp-btn-sm" @click="run('approve')" :disabled="busy"><i class="fas fa-check"></i> อนุมัติที่เลือก</button>
                <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="run('pay')" :disabled="busy"><i class="fas fa-money-bill-wave"></i> จ่ายที่เลือก</button>
                <button type="button" class="tp-btn tp-btn-sm" style="color:{{ $c['bad'] }};" @click="run('reject')" :disabled="busy"><i class="fas fa-xmark"></i> ปฏิเสธที่เลือก</button>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:960px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }} width:40px;"></th>
                        <th style="{{ $th }}">สมาชิก</th>
                        <th style="{{ $th }}">ประเภท</th>
                        <th style="{{ $th }}">จาก</th>
                        <th style="{{ $th }} text-align:right;">จำนวน</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }}">วันที่</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $commission)
                        @php
                            [$sLabel, $sColor] = $statusMeta[$commission->status] ?? [$commission->status, $c['mute']];
                            $memberName = $commission->member?->user?->name ?? $commission->user?->name ?? 'ไม่ระบุ';
                        @endphp
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <input type="checkbox" value="{{ $commission->id }}" x-model.number="selected" style="accent-color:var(--accent1); width:17px; height:17px;">
                            </td>
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:9px;">
                                    <span class="tp-tile" style="width:32px; height:32px; border-radius:50%; font-size:12px; font-weight:800;">{{ mb_strtoupper(mb_substr($memberName, 0, 1)) }}</span>
                                    <div style="min-width:0;">
                                        <div style="font-weight:600; max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $memberName }}</div>
                                        <div class="tp-num" style="font-size:11.5px; color:var(--ink2);">{{ $commission->member?->member_code ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }} white-space:nowrap;">
                                <span class="tp-pill tp-pill-soft">{{ $typeMeta[$commission->type] ?? $commission->type }}</span>
                                @if($commission->level)<span class="tp-num" style="font-size:11.5px; color:var(--ink2);"> Lv.{{ $commission->level }}</span>@endif
                            </td>
                            <td style="{{ $td }}">
                                @if($commission->fromMember)
                                    <div style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $commission->fromMember->user?->name ?? '-' }}</div>
                                    <div class="tp-num" style="font-size:11.5px; color:var(--ink2);">{{ $commission->fromMember->member_code }}</div>
                                @else
                                    <span style="color:var(--ink2);">—</span>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div class="tp-num" style="font-weight:800;">฿{{ number_format((float) $commission->commission_amount, 2) }}</div>
                                @if((float) $commission->pv_amount > 0)<div class="tp-num" style="font-size:11.5px; color:var(--ink2);">{{ number_format((float) $commission->pv_amount, 2) }} PV</div>@endif
                            </td>
                            <td style="{{ $td }}"><span class="tp-pill" style="{{ $pill($sColor) }}">{{ $sLabel }}</span></td>
                            <td style="{{ $td }} white-space:nowrap;">
                                <div class="tp-num">{{ $commission->created_at?->format('d/m/Y') }}</div>
                                <div class="tp-num" style="font-size:11.5px; color:var(--ink2);">{{ $commission->created_at?->format('H:i') }}</div>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    @if($commission->status === 'pending')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:{{ $c['ok'] }};" title="อนุมัติ" @click="run('approve', [{{ $commission->id }}])" :disabled="busy"><i class="fas fa-check"></i></button>
                                    @elseif($commission->status === 'approved')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:{{ $c['info'] }};" title="จ่าย" @click="run('pay', [{{ $commission->id }}])" :disabled="busy"><i class="fas fa-money-bill-wave"></i></button>
                                    @endif
                                    <a href="{{ route('admin.mlm.commissions.show', $commission) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="ดูรายละเอียด"><i class="fas fa-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ไม่พบคอมมิชชั่นตามเงื่อนไขนี้
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($commissions->hasPages())
        <div>{{ $commissions->links() }}</div>
    @endif
</div>
@endsection
