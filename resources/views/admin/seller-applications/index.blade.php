{{--
 | คิวอนุมัติคำขอเปิดร้านค้า (SELLER-07)
 | ตัวแปร: $applications (paginator ของ VendorStore status=pending พร้อม user), $recent (Collection ร้านที่เพิ่งอนุมัติ/ปฏิเสธ 30 วัน), $stats ['pending','active']
 | action: POST admin.seller-applications.approve / admin.seller-applications.reject (reason)
 --}}
@extends('layouts.admin-v4')

@section('title', 'คำขอเปิดร้านค้า')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">หลังบ้าน · ผู้ใช้งาน · ร้านค้า</div>
            <h1 class="tp-num" style="font-size:1.6rem; font-weight:800; color:var(--ink); margin:0;">คำขอเปิดร้านค้า</h1>
            <p class="tp-muted" style="margin:4px 0 0; font-size:.9rem;">อนุมัติแล้วผู้ใช้จะกลายเป็นผู้ขาย และเข้าหลังร้านเพื่อทำ KYC/ลงสินค้าต่อได้ทันที</p>
        </div>
        <a href="{{ route('admin.storefront.vendor-stores.index') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;">
            <i class="fas fa-store"></i> <span>ร้านค้าทั้งหมด</span>
        </a>
    </div>

    {{-- ── KPI ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <div class="tp-card">
            <div class="tp-num" style="font-size:1.7rem; font-weight:800; color:var(--ink); line-height:1;">{{ number_format($stats['pending']) }}</div>
            <div class="tp-muted" style="font-size:.78rem; margin-top:3px;">รออนุมัติ</div>
        </div>
        <div class="tp-card">
            <div class="tp-num" style="font-size:1.7rem; font-weight:800; color:var(--ink); line-height:1;">{{ number_format($stats['active']) }}</div>
            <div class="tp-muted" style="font-size:.78rem; margin-top:3px;">ร้านที่เปิดอยู่</div>
        </div>
    </div>

    {{-- ── รายการรออนุมัติ ── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px; box-shadow:var(--inset-sm);">
            <div class="tp-section-h">รออนุมัติ</div>
        </div>

        @forelse($applications as $store)
            @php $owner = $store->user; @endphp
            <div style="padding:16px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);"
                 x-data="{ showReject: false, busy: false }">
                <div style="display:flex; gap:16px; flex-wrap:wrap; justify-content:space-between;">
                    <div style="flex:1; min-width:240px;">
                        <div style="font-weight:800; color:var(--ink); font-size:1.02rem;">{{ $store->store_name }}</div>
                        <div class="tp-muted" style="font-size:.82rem; margin-top:2px;">
                            {{ $store->business_type === 'company' ? 'นิติบุคคล' : 'บุคคลธรรมดา' }}
                            @if($store->business_type === 'company' && $store->company_name) · {{ $store->company_name }} ({{ $store->tax_id }}) @endif
                            · ยื่นเมื่อ {{ $store->updated_at?->format('d/m/Y H:i') }}
                        </div>
                        <div style="font-size:.86rem; color:var(--ink2); margin-top:8px; line-height:1.6;">
                            📍 {{ $store->store_address }} {{ $store->store_city }} {{ $store->store_state }} {{ $store->store_postal_code }}<br>
                            📞 {{ $store->store_phone ?? '-' }}
                            @if($store->store_description)<br>📝 {{ \Illuminate\Support\Str::limit($store->store_description, 200) }}@endif
                        </div>
                    </div>
                    <div style="min-width:220px;">
                        @if($owner)
                            <div style="font-weight:700; color:var(--ink);">{{ $owner->name }}</div>
                            <div class="tp-muted" style="font-size:.8rem;">{{ $owner->email }} · {{ $owner->member_number }}</div>
                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:6px;">
                                <span class="tp-pill tp-pill-soft">บทบาท: {{ $owner->role }}</span>
                                <span class="tp-pill {{ $owner->kyc_status === 'approved' ? 'tp-pill-gold' : 'tp-pill-soft' }}">KYC: {{ $owner->kyc_status ?? 'ยังไม่ส่ง' }}</span>
                                @if($owner->blocked_at)<span class="tp-pill" style="background:var(--tp-bad,#d9534f); color:var(--tp-on-accent,#fff);">ถูกระงับ</span>@endif
                                @if($owner->trashed())<span class="tp-pill" style="background:var(--ink2); color:var(--tp-on-accent,#fff);">ลบบัญชีแล้ว</span>@endif
                            </div>
                            <a href="{{ route('admin.users.show', $owner->id) }}" style="display:inline-block; margin-top:6px; font-size:.8rem; color:var(--deep1);">ดูข้อมูลผู้ใช้</a>
                        @else
                            <div class="tp-muted">ไม่พบบัญชีเจ้าของ</div>
                        @endif
                    </div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
                    <form method="POST" action="{{ route('admin.seller-applications.approve', $store->id) }}"
                          @submit="if (!confirm(@js('อนุมัติร้าน '.$store->store_name.' ?'))) { $event.preventDefault(); return; } busy = true">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" :disabled="busy"><i class="fas fa-check"></i> <span>อนุมัติ</span></button>
                    </form>
                    <button type="button" class="tp-btn tp-btn-sm" @click="showReject = !showReject"><i class="fas fa-xmark"></i> <span>ปฏิเสธ</span></button>
                    <a href="{{ route('admin.storefront.vendor-stores.show', $store->id) }}" class="tp-btn tp-btn-sm" style="text-decoration:none;"><i class="fas fa-store"></i> <span>รายละเอียดร้าน</span></a>
                </div>

                <form x-show="showReject" x-cloak method="POST" action="{{ route('admin.seller-applications.reject', $store->id) }}" class="flex"
                      style="margin-top:12px; gap:10px; flex-wrap:wrap; align-items:flex-start;"
                      @submit="busy = true">
                    @csrf
                    <textarea name="reason" class="tp-input" rows="2" maxlength="500" required style="flex:1; min-width:240px;"
                              placeholder="เหตุผลที่ปฏิเสธ (ผู้สมัครจะเห็นข้อความนี้)"></textarea>
                    <button type="submit" class="tp-btn tp-btn-sm" style="background:var(--tp-bad,#d9534f); color:var(--tp-on-accent,#fff);" :disabled="busy">
                        <span>ยืนยันปฏิเสธ</span>
                    </button>
                </form>
            </div>
        @empty
            <div style="padding:40px 20px; text-align:center;" class="tp-muted">ไม่มีคำขอที่รออนุมัติ</div>
        @endforelse

        @if($applications->hasPages())
            <div style="padding:14px 20px;">{{ $applications->links() }}</div>
        @endif
    </div>

    {{-- ── ดำเนินการล่าสุด ── --}}
    @if($recent->isNotEmpty())
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:16px 20px; box-shadow:var(--inset-sm);">
                <div class="tp-section-h">ความเคลื่อนไหว 30 วันล่าสุด</div>
            </div>
            @foreach($recent as $store)
                <div style="padding:12px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent); display:flex; gap:12px; justify-content:space-between; flex-wrap:wrap; font-size:.88rem;">
                    <div style="color:var(--ink);">
                        <strong>{{ $store->store_name }}</strong>
                        <span class="tp-muted">· {{ $store->user?->name ?? '-' }}</span>
                    </div>
                    <div>
                        @if($store->status === 'active')
                            <span class="tp-pill tp-pill-gold">เปิดอยู่</span>
                        @else
                            <span class="tp-pill tp-pill-soft" title="{{ $store->suspension_reason }}">ปิด/ปฏิเสธ</span>
                        @endif
                        <span class="tp-muted" style="margin-left:6px;">{{ $store->updated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
