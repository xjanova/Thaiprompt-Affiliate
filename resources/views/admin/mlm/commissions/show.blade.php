@extends('layouts.admin-v4')

@section('title', 'รายละเอียดคอมมิชชั่น #' . $commission->id)

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $row = 'display:flex; justify-content:space-between; gap:12px; font-size:13px; padding:7px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 16%, transparent);';
    $statusMeta = [
        'pending' => ['รออนุมัติ', $c['warn']],
        'approved' => ['อนุมัติแล้ว รอจ่าย', $c['info']],
        'paid' => ['จ่ายแล้ว', $c['ok']],
        'rejected' => ['ปฏิเสธ', $c['bad']],
        'cancelled' => ['ยกเลิก', $c['mute']],
        'clawback' => ['ดึงคืน', $c['bad']],
    ];
    [$sLabel, $sColor] = $statusMeta[$commission->status] ?? [$commission->status, $c['mute']];
    $typeMeta = [
        'unilevel_direct' => 'ยูนิเลเวล (ชั้นตรง)', 'unilevel_indirect' => 'ยูนิเลเวล (ชั้นลึก)', 'unilevel_rollup' => 'ยูนิเลเวล (Roll-up)',
        'binary_pair' => 'ไบนารี (จับคู่)', 'binary_matching' => 'ไบนารี (Matching)', 'sponsor_bonus' => 'โบนัสผู้แนะนำ',
        'rank_bonus' => 'โบนัสตำแหน่ง', 'leadership_bonus' => 'โบนัสผู้นำ', 'matching_bonus' => 'Matching โบนัส', 'pool_bonus' => 'โบนัสกองกลาง',
    ];
    $member = $commission->member;
    $from = $commission->fromMember;
    $isOrderSource = in_array($commission->source_type, ['Order', \App\Models\Order::class], true) && $commission->source_id;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{
        busy: false,
        showReject: false,
        async run(action) {
            const label = action === 'approve' ? 'อนุมัติ' : 'จ่ายเงิน';
            if (!confirm(label + 'คอมมิชชั่นนี้?' + (action === 'pay' ? ' (เงินเข้ากระเป๋าสมาชิกทันที)' : ''))) return;
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            fd.append('commission_ids[]', {{ (int) $commission->id }});
            this.busy = true;
            try {
                const url = action === 'approve' ? @js(route('admin.mlm.commissions.approve')) : @js(route('admin.mlm.commissions.pay'));
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

    {{-- ===== หัว ===== --}}
    <div class="tp-card" style="padding:22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, var(--card-bg)), var(--card-bg) 70%);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:14px; align-items:flex-end;">
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบค่าแนะนำ · คอมมิชชั่น #{{ $commission->id }}</div>
                <div class="tp-num" style="font-size:clamp(26px,5vw,34px); font-weight:800; margin-top:4px;">฿{{ number_format((float) $commission->commission_amount, 2) }}</div>
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                    <span class="tp-pill" style="{{ $pill($sColor) }}">{{ $sLabel }}</span>
                    <span class="tp-pill tp-pill-soft">{{ $typeMeta[$commission->type] ?? $commission->type }}{{ $commission->level ? ' · Lv.'.$commission->level : '' }}</span>
                    @if($commission->is_rollup)<span class="tp-pill tp-pill-soft">Roll-up</span>@endif
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @if($commission->status === 'pending')
                    <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="run('approve')" :disabled="busy"><i class="fas fa-check"></i> อนุมัติ</button>
                    <button type="button" class="tp-btn tp-btn-sm" style="color:{{ $c['bad'] }};" @click="showReject = !showReject"><i class="fas fa-xmark"></i> ปฏิเสธ</button>
                @elseif($commission->status === 'approved')
                    <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="run('pay')" :disabled="busy"><i class="fas fa-money-bill-wave"></i> จ่ายเข้ากระเป๋า</button>
                @endif
                <a href="{{ route('admin.mlm.commissions.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> รายการคอมมิชชั่น</a>
            </div>
        </div>
        <form x-show="showReject" x-cloak method="POST" action="{{ route('admin.mlm.commissions.reject', $commission) }}" class="flex"
              style="margin-top:14px; gap:10px; flex-wrap:wrap; align-items:flex-start;"
              @submit="if (!confirm('ยืนยันปฏิเสธคอมมิชชั่นนี้?')) { $event.preventDefault(); return; } busy = true">
            @csrf
            <textarea name="reason" rows="2" maxlength="500" required class="tp-input" style="flex:1; min-width:220px;" placeholder="เหตุผลที่ปฏิเสธ"></textarea>
            <button type="submit" class="tp-btn tp-btn-sm" style="background:{{ $c['bad'] }}; color:var(--tp-on-accent,#fff);" :disabled="busy">ยืนยันปฏิเสธ</button>
        </form>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $c['bad'] }};">
            <ul style="margin:0; padding-left:18px; font-size:13px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:16px; align-items:start;">
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-calculator" style="color:var(--accent1);"></i> ที่มาของคอมมิชชั่น</div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">แผน</span><span>{{ $commission->plan?->display_name ?? $commission->plan?->name ?? '-' }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">PV ที่ใช้คิด</span><span class="tp-num">{{ number_format((float) $commission->pv_amount, 2) }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">ยอดขายต้นทาง</span><span class="tp-num">฿{{ number_format((float) $commission->sales_amount, 2) }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">อัตรา</span><span class="tp-num">{{ rtrim(rtrim(number_format((float) $commission->percentage, 2), '0'), '.') ?: '0' }}%</span></div>
            @if($commission->source_type)
                <div style="{{ $row }}"><span style="color:var(--ink2);">แหล่งที่มา</span>
                    <span>
                        @if($isOrderSource && \App\Models\Order::whereKey($commission->source_id)->exists())
                            <a href="{{ route('admin.ecommerce.orders.show', $commission->source_id) }}" style="color:var(--deep1);">ออเดอร์ #{{ $commission->source_id }}</a>
                        @else
                            {{ class_basename($commission->source_type) }} #{{ $commission->source_id }}
                        @endif
                    </span>
                </div>
            @endif
            @if($commission->notes)<div style="{{ $row }}"><span style="color:var(--ink2);">หมายเหตุ</span><span style="text-align:right; max-width:60%;">{{ $commission->notes }}</span></div>@endif
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-user" style="color:var(--accent1);"></i> ผู้รับคอมมิชชั่น</div>
            @if($member)
                <div style="{{ $row }}"><span style="color:var(--ink2);">ชื่อ</span><span style="text-align:right;">{{ $member->user?->name ?? '-' }}<div style="font-size:11.5px; color:var(--ink2);">{{ $member->user?->email }}</div></span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">รหัสสมาชิก</span><span class="tp-num">{{ $member->member_code }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">PV สะสม</span><span class="tp-num">{{ number_format((float) $member->total_pv, 2) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">รายได้สะสม</span><span class="tp-num">฿{{ number_format((float) $member->total_earnings, 2) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">แนะนำตรง</span><span class="tp-num">{{ number_format((int) $member->total_direct_referrals) }} คน</span></div>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                    <a href="{{ route('admin.mlm.members.show', $member) }}" class="tp-btn tp-btn-sm"><i class="fas fa-id-badge"></i> ข้อมูลสมาชิก</a>
                    <a href="{{ route('admin.mlm.genealogy.index', ['member' => $member->id]) }}" class="tp-btn tp-btn-sm"><i class="fas fa-sitemap"></i> ผังสายงาน</a>
                </div>
            @else
                <div style="font-size:13px; color:var(--ink2);">{{ $commission->user?->name ?? 'ไม่พบข้อมูลสมาชิก' }}</div>
            @endif
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-user-group" style="color:var(--accent1);"></i> มาจากการซื้อของ</div>
            @if($from)
                <div style="{{ $row }}"><span style="color:var(--ink2);">ชื่อ</span><span>{{ $from->user?->name ?? '-' }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">รหัสสมาชิก</span><span class="tp-num">{{ $from->member_code }}</span></div>
                <a href="{{ route('admin.mlm.members.show', $from) }}" class="tp-btn tp-btn-sm" style="margin-top:10px;"><i class="fas fa-id-badge"></i> ข้อมูลสมาชิก</a>
            @else
                <div style="font-size:13px; color:var(--ink2);">ไม่ระบุ</div>
            @endif
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-clock-rotate-left" style="color:var(--accent1);"></i> ลำดับเหตุการณ์</div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">สร้าง</span><span class="tp-num">{{ $commission->created_at?->format('d/m/Y H:i') }}</span></div>
            @if($commission->approved_at)<div style="{{ $row }}"><span style="color:var(--ink2);">อนุมัติ</span><span class="tp-num">{{ $commission->approved_at->format('d/m/Y H:i') }}</span></div>@endif
            @if($commission->paid_at)<div style="{{ $row }}"><span style="color:var(--ink2);">จ่ายเงิน</span><span class="tp-num">{{ $commission->paid_at->format('d/m/Y H:i') }}</span></div>@endif
            @if($commission->rejected_at)
                <div style="{{ $row }}"><span style="color:var(--ink2);">ปฏิเสธ</span><span class="tp-num">{{ $commission->rejected_at->format('d/m/Y H:i') }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">เหตุผล</span><span style="text-align:right; max-width:60%; color:{{ $c['bad'] }};">{{ $commission->rejection_reason ?: '-' }}</span></div>
            @endif
            @if($commission->walletTransaction)
                <div style="{{ $row }}"><span style="color:var(--ink2);">รายการกระเป๋า</span><span class="tp-num">#{{ $commission->walletTransaction->id }} · ฿{{ number_format((float) $commission->walletTransaction->amount, 2) }}</span></div>
            @endif
        </div>
    </div>
</div>
@endsection
