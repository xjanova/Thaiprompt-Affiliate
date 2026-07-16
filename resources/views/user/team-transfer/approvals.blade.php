@extends('layouts.user-v4')

@section('title', 'คำขอย้ายทีมที่ต้องอนุมัติ')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#5aa07e;"><i class="fas fa-tasks" style="color:#fff;"></i></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">✅ คำขอย้ายทีมที่ต้องอนุมัติ</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ลูกทีมของคุณที่ขอย้ายไปหาแม่ทีมใหม่</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #5aa07e;" x-data="{ show: true }" x-show="show" x-transition>
            <span style="color:var(--ink); font-weight:600;"><i class="fas fa-check-circle" style="color:#5aa07e; margin-right:8px;"></i>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #d9534f;" x-data="{ show: true }" x-show="show" x-transition>
            <span style="color:var(--ink); font-weight:600;"><i class="fas fa-exclamation-circle" style="color:#d9534f; margin-right:8px;"></i>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ── แท็บ ──────────────────────────────────────────────── --}}
    <div x-data="{ activeTab: 'pending' }">
        <div style="display:flex; gap:8px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); margin-bottom:6px;">
            <button @click="activeTab = 'pending'" class="tp-btn" style="border:none; border-radius:0; border-bottom:2px solid transparent; background:none;"
                    :style="activeTab === 'pending' ? 'border-bottom-color:#5689b8; color:#5689b8;' : 'color:var(--ink2);'">
                รอดำเนินการ ({{ $pendingApprovals->total() }})
            </button>
            <button @click="activeTab = 'processed'" class="tp-btn" style="border:none; border-radius:0; border-bottom:2px solid transparent; background:none;"
                    :style="activeTab === 'processed' ? 'border-bottom-color:#5689b8; color:#5689b8;' : 'color:var(--ink2);'">
                ดำเนินการแล้ว ({{ $processedApprovals->total() }})
            </button>
        </div>

        {{-- ── รอดำเนินการ ────────────────────────────────────── --}}
        <div x-show="activeTab === 'pending'" x-transition style="padding-top:18px;">
            @if($pendingApprovals->count() > 0)
                <div style="display:flex; flex-direction:column; gap:16px;">
                    @foreach($pendingApprovals as $request)
                        <div class="tp-card" style="padding:24px; border-left:4px solid #d9a441;" x-data="{ showRejectModal: false }">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px;">
                                <div style="display:flex; align-items:flex-start; gap:12px;">
                                    <span class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; background:#d9a441;"><i class="fas fa-clock" style="color:#fff;"></i></span>
                                    <div>
                                        <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0;">{{ $request->member->user->name ?? 'N/A' }}</h3>
                                        <p style="font-size:12px; color:var(--ink2); margin:2px 0 0;">รหัส: {{ $request->member->member_code ?? 'N/A' }}</p>
                                        <p style="font-size:12px; color:var(--ink2); margin:0;">{{ $request->created_at->locale('th')->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <span style="display:inline-flex; align-items:center; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; color:#d9a441; background:color-mix(in srgb, #d9a441 16%, transparent);"><span style="width:8px; height:8px; border-radius:50%; margin-right:8px; background:#d9a441;"></span>รอการอนุมัติ</span>
                            </div>

                            <div style="margin-bottom:18px; padding:16px; border-radius:10px; box-shadow:var(--inset-sm);">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="flex:1;">
                                        <p style="font-size:11px; color:#5689b8; margin:0 0 2px;">ต้องการย้ายไปหา</p>
                                        <p style="font-weight:800; color:var(--ink); margin:0;">{{ $request->newSponsor->user->name ?? 'N/A' }}</p>
                                        <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->newSponsor->member_code ?? 'N/A' }}</p>
                                    </div>
                                    <div style="width:44px; height:44px; background:#5689b8; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas fa-arrow-right" style="color:#fff;"></i></div>
                                </div>
                            </div>

                            @if($request->reason)
                                <div style="margin-bottom:18px;">
                                    <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">เหตุผลในการย้าย</div>
                                    <div style="padding:14px; border-radius:10px; box-shadow:var(--inset-sm);"><p style="color:var(--ink); margin:0;">{{ $request->reason }}</p></div>
                                </div>
                            @endif

                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('user.team-transfer.approve', $request) }}" style="flex:1; min-width:140px;">
                                    @csrf
                                    <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; background:#5aa07e; border-color:#5aa07e;" onclick="return confirm('คุณต้องการอนุมัติให้ {{ $request->member->user->name ?? 'สมาชิก' }} ย้ายทีมใช่หรือไม่?')"><i class="fas fa-check"></i> อนุมัติ</button>
                                </form>
                                <button @click="showRejectModal = true" class="tp-btn" style="flex:1; min-width:140px; justify-content:center; color:#d9534f;"><i class="fas fa-times"></i> ปฏิเสธ</button>
                            </div>

                            {{-- Reject Modal --}}
                            <div x-show="showRejectModal" x-cloak @click.away="showRejectModal = false" style="display:none; position:fixed; inset:0; z-index:60; overflow-y:auto;">
                                <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
                                    <div style="position:fixed; inset:0; background:rgba(0,0,0,0.5);"></div>
                                    <div class="tp-card" style="position:relative; max-width:440px; width:100%; padding:24px;" @click.stop>
                                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                                            <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0;">ปฏิเสธคำขอ</h3>
                                            <button @click="showRejectModal = false" style="background:none; border:none; color:var(--ink2); cursor:pointer; font-size:20px;"><i class="fas fa-times"></i></button>
                                        </div>
                                        <form method="POST" action="{{ route('user.team-transfer.reject', $request) }}">
                                            @csrf
                                            <div style="margin-bottom:16px;">
                                                <label for="rejection_reason_{{ $request->id }}" style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">เหตุผลที่ปฏิเสธ <span style="color:#d9534f;">*</span></label>
                                                <textarea id="rejection_reason_{{ $request->id }}" name="rejection_reason" rows="4" required maxlength="500" class="tp-input" style="resize:vertical;" placeholder="กรุณาระบุเหตุผลที่ปฏิเสธคำขอนี้"></textarea>
                                                <p style="margin:8px 0 0; font-size:13px; color:var(--ink2);">สมาชิกจะเห็นเหตุผลที่คุณระบุ</p>
                                            </div>
                                            <div style="display:flex; gap:12px;">
                                                <button type="button" @click="showRejectModal = false" class="tp-btn" style="flex:1; justify-content:center;">ยกเลิก</button>
                                                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center; background:#d9534f; border-color:#d9534f;">ยืนยันปฏิเสธ</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:20px;">{{ $pendingApprovals->links() }}</div>
            @else
                <div class="tp-card" style="padding:48px; text-align:center;">
                    <div style="width:80px; height:80px; margin:0 auto 20px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;"><i class="fas fa-check-circle" style="font-size:32px; color:var(--ink2);"></i></div>
                    <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 8px;">ไม่มีคำขอที่รอดำเนินการ</h3>
                    <p style="color:var(--ink2); margin:0;">ยังไม่มีลูกทีมขอย้ายทีมในขณะนี้</p>
                </div>
            @endif
        </div>

        {{-- ── ดำเนินการแล้ว ──────────────────────────────────── --}}
        <div x-show="activeTab === 'processed'" x-cloak x-transition style="padding-top:18px;">
            @if($processedApprovals->count() > 0)
                <div style="display:flex; flex-direction:column; gap:16px;">
                    @foreach($processedApprovals as $request)
                        @php
                            $stColor = match($request->status) {
                                'approved', 'completed' => '#5aa07e', 'rejected' => '#d9534f',
                                'paid' => '#5689b8', default => '#8a8a8a',
                            };
                        @endphp
                        <div class="tp-card" style="padding:24px;">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
                                <div style="display:flex; align-items:flex-start; gap:12px;">
                                    <span class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; background:#8a8a8a;"><i class="fas fa-user" style="color:#fff;"></i></span>
                                    <div>
                                        <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0;">{{ $request->member->user->name ?? 'N/A' }}</h3>
                                        <p style="font-size:12px; color:var(--ink2); margin:2px 0 0;">รหัส: {{ $request->member->member_code ?? 'N/A' }}</p>
                                        <p style="font-size:12px; color:var(--ink2); margin:0;">{{ $request->created_at->locale('th')->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <span style="display:inline-flex; align-items:center; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; color:{{ $stColor }}; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);"><span style="width:8px; height:8px; border-radius:50%; margin-right:8px; background:{{ $stColor }};"></span>{{ $request->status_label }}</span>
                            </div>

                            <div style="margin-bottom:14px; padding:14px; border-radius:10px; box-shadow:var(--inset-sm);">
                                <p style="font-size:11px; color:var(--ink2); margin:0 0 2px;">ย้ายไปหา</p>
                                <p style="font-weight:700; color:var(--ink); margin:0;">{{ $request->newSponsor->user->name ?? 'N/A' }}</p>
                                <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->newSponsor->member_code ?? 'N/A' }}</p>
                            </div>

                            @if($request->status === 'approved' && $request->approved_at)
                                <p style="font-size:13px; color:var(--ink2); margin:0;">อนุมัติเมื่อ: {{ $request->approved_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                            @elseif($request->status === 'rejected' && $request->rejected_at)
                                <p style="font-size:13px; color:var(--ink2); margin:0 0 8px;">ปฏิเสธเมื่อ: {{ $request->rejected_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                                @if($request->rejection_reason)
                                    <div style="padding:12px; border-radius:10px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
                                        <p style="font-size:11px; color:#d9534f; margin:0 0 2px;">เหตุผล:</p>
                                        <p style="font-size:13px; color:var(--ink); margin:0;">{{ $request->rejection_reason }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:20px;">{{ $processedApprovals->appends(['processed_page' => $processedApprovals->currentPage()])->links() }}</div>
            @else
                <div class="tp-card" style="padding:48px; text-align:center;">
                    <div style="width:80px; height:80px; margin:0 auto 20px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;"><i class="fas fa-clipboard-list" style="font-size:32px; color:var(--ink2);"></i></div>
                    <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 8px;">ยังไม่มีคำขอที่ดำเนินการ</h3>
                    <p style="color:var(--ink2); margin:0;">ยังไม่มีประวัติการอนุมัติหรือปฏิเสธคำขอ</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
