@extends('layouts.user-v4')

@section('title', 'ผู้มุ่งหวัง (Leads)')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero + สถิติ ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#5689b8;"><i class="fas fa-users" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">👥 ผู้มุ่งหวัง (Leads)</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามและจัดการผู้มุ่งหวังที่เข้าชมหน้า Recruit ของคุณ</div>
                    </div>
                </div>
                <a href="{{ route('user.marketing.recruit.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับไปหน้าจัดการ</a>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">ผู้มุ่งหวังทั้งหมด</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">กำลังติดตาม</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#5aa07e;">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">สมัครแล้ว</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#7c5cbf;">{{ number_format($stats['converted']) }}</div>
                </div>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">หมดอายุแล้ว</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink2);">{{ number_format($stats['expired']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <a href="{{ route('user.marketing.recruit.leads', ['status' => 'all']) }}" class="tp-btn tp-btn-sm {{ $currentStatus === 'all' ? 'tp-btn-primary' : '' }}">ทั้งหมด ({{ number_format($stats['total']) }})</a>
        <a href="{{ route('user.marketing.recruit.leads', ['status' => 'active']) }}" class="tp-btn tp-btn-sm" style="{{ $currentStatus === 'active' ? 'background:#5aa07e; border-color:#5aa07e; color:#fff;' : '' }}">กำลังติดตาม ({{ number_format($stats['active']) }})</a>
        <a href="{{ route('user.marketing.recruit.leads', ['status' => 'converted']) }}" class="tp-btn tp-btn-sm" style="{{ $currentStatus === 'converted' ? 'background:#7c5cbf; border-color:#7c5cbf; color:#fff;' : '' }}">สมัครแล้ว ({{ number_format($stats['converted']) }})</a>
        <a href="{{ route('user.marketing.recruit.leads', ['status' => 'expired']) }}" class="tp-btn tp-btn-sm" style="{{ $currentStatus === 'expired' ? 'background:#8a8a8a; border-color:#8a8a8a; color:#fff;' : '' }}">หมดอายุ ({{ number_format($stats['expired']) }})</a>
    </div>

    {{-- ── ตาราง Leads ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($leads->count() > 0)
            <div style="overflow-x:auto;">
                <table style="min-width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="box-shadow:var(--inset-sm);">
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">ข้อมูลผู้เข้าชม</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">แหล่งที่มา</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">เวลา</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="padding:14px 20px;">
                                    <div style="display:flex; align-items:flex-start; gap:12px;">
                                        <div style="flex-shrink:0; width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #5689b8, #7c5cbf); display:flex; align-items:center; justify-content:center; color:#fff;"><i class="fas fa-user"></i></div>
                                        <div>
                                            @if($lead->status === 'converted' && $lead->mlmMember)
                                                <div style="font-weight:600; color:var(--ink);">{{ $lead->mlmMember->user->name ?? 'ไม่ระบุ' }}</div>
                                                <div style="font-size:12px; color:#5aa07e; font-weight:600;">✓ สมัครสมาชิกแล้ว</div>
                                            @else
                                                <div style="font-weight:600; color:var(--ink);">ผู้เข้าชมที่ {{ $lead->id }}</div>
                                            @endif
                                            <div style="font-size:11px; color:var(--ink2); margin-top:2px;">IP: {{ $lead->ip_address }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:14px 20px;">
                                    @if($lead->utm_params)
                                        @php $utm = is_string($lead->utm_params) ? json_decode($lead->utm_params, true) : $lead->utm_params; @endphp
                                        <span style="display:inline-flex; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:600; color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent);">{{ $utm['utm_source'] ?? 'ไม่ระบุ' }}</span>
                                        @if(isset($utm['utm_campaign']))
                                            <div style="font-size:11px; color:var(--ink2); margin-top:4px;">แคมเปญ: {{ $utm['utm_campaign'] }}</div>
                                        @endif
                                    @elseif($lead->referrer_url)
                                        <span style="color:var(--ink2);">{{ Str::limit(parse_url($lead->referrer_url, PHP_URL_HOST) ?? 'Direct', 30) }}</span>
                                    @else
                                        <span style="color:var(--ink2);">Direct</span>
                                    @endif
                                </td>
                                <td style="padding:14px 20px; color:var(--ink);">
                                    <div style="display:flex; flex-direction:column;">
                                        <span style="font-weight:600;">{{ $lead->locked_at->format('d/m/Y H:i') }}</span>
                                        @if($lead->status === 'locked' && $lead->expires_at > now())
                                            <span style="font-size:11px; color:#5aa07e; margin-top:2px;">หมดอายุ: {{ $lead->expires_at->format('d/m/Y H:i') }}</span>
                                            <span style="font-size:11px; color:var(--ink2);">(เหลือ {{ $lead->getTimeRemaining() }})</span>
                                        @elseif($lead->status === 'converted')
                                            <span style="font-size:11px; color:#7c5cbf; margin-top:2px;">สมัครเมื่อ: {{ $lead->converted_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding:14px 20px;">
                                    @if($lead->status === 'converted')
                                        <span style="display:inline-flex; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:600; color:#fff; background:#7c5cbf;">✓ สมัครแล้ว</span>
                                    @elseif($lead->status === 'locked' && $lead->expires_at > now())
                                        <span style="display:inline-flex; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:600; color:#fff; background:#5aa07e;">กำลังติดตาม</span>
                                    @else
                                        <span style="display:inline-flex; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:600; color:#fff; background:#8a8a8a;">หมดอายุ</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:14px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                {{ $leads->links() }}
            </div>
        @else
            <div style="text-align:center; padding:64px 24px;">
                <div style="width:80px; height:80px; margin:0 auto 20px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-users" style="font-size:32px; color:var(--ink2);"></i>
                </div>
                <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin:0 0 8px;">ยังไม่มีผู้มุ่งหวัง</h3>
                <p style="color:var(--ink2); margin:0 0 20px;">เริ่มแชร์ลิงก์ Recruit ของคุณเพื่อเริ่มต้นสร้างรายชื่อผู้มุ่งหวัง</p>
                <a href="{{ route('user.marketing.recruit.index') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fas fa-qrcode"></i> ดูลิงก์และ QR Code</a>
            </div>
        @endif
    </div>
</div>
@endsection
