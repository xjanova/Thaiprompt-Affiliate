@extends('layouts.admin-v4')

@section('title', 'ผู้ใช้ดูดวง')

@section('content')
{{-- ====================================================================
     ผู้ใช้ดูดวง — รายการ (ธีม V4 นวลทองคำ)
     - master-detail / data-table: ค้นหา/กรอง + ส่งข้อความ + เพิ่มเครดิตด่วน
     - คงฟอร์มเดิม 100%: send-message / broadcast / quick-add-credits
     - Alpine ย้ายไป @push('scripts')
==================================================================== --}}
<div x-data="fortuneUsers()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ========================= HEADER ========================= --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ผู้ใช้</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ผู้ใช้ดูดวง 👥</h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- เปิดฟอร์ม Broadcast --}}
            <button type="button" @click="showBroadcast = !showBroadcast; showSendMessage = false"
                    class="tp-btn">
                <i class="fas fa-bullhorn"></i>
                <span>ส่งถึงทุกคน</span>
            </button>
            {{-- เปิดฟอร์มส่งรายคน --}}
            <button type="button" @click="showSendMessage = !showSendMessage; showBroadcast = false"
                    class="tp-btn tp-btn-primary">
                <i class="fas fa-comment-dots"></i>
                <span>ส่งข้อความรายคน</span>
            </button>
        </div>
    </div>

    {{-- ========================= KPI GRID ========================= --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:14px;">
        {{-- ผู้ใช้ทั้งหมด --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile"><i class="fas fa-users"></i></div>
            <div>
                <div class="tp-muted" style="font-size:12px;">ผู้ใช้ทั้งหมด</div>
                <div class="tp-num" style="font-size:24px; font-weight:800;">{{ number_format($stats['total_users']) }}</div>
            </div>
        </div>
        {{-- Facebook --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="color:#5689b8;"><i class="fab fa-facebook-f"></i></div>
            <div>
                <div class="tp-muted" style="font-size:12px;">Facebook</div>
                <div class="tp-num" style="font-size:24px; font-weight:800;">{{ number_format($stats['facebook_users']) }}</div>
            </div>
        </div>
        {{-- LINE --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="color:#5aa07e;"><i class="fab fa-line"></i></div>
            <div>
                <div class="tp-muted" style="font-size:12px;">LINE</div>
                <div class="tp-num" style="font-size:24px; font-weight:800;">{{ number_format($stats['line_users']) }}</div>
            </div>
        </div>
        {{-- ลูกค้าจ่ายเงิน --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="color:#d6824a;"><i class="fas fa-hand-holding-dollar"></i></div>
            <div>
                <div class="tp-muted" style="font-size:12px;">ลูกค้าจ่ายเงิน</div>
                <div class="tp-num" style="font-size:24px; font-weight:800;">{{ number_format($stats['paying_users']) }}</div>
            </div>
        </div>
        {{-- รายได้รวม --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile"><i class="fas fa-coins"></i></div>
            <div>
                <div class="tp-muted" style="font-size:12px;">รายได้รวม</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#5aa07e;">฿{{ number_format($stats['total_revenue'], 0) }}</div>
            </div>
        </div>
    </div>

    {{-- ========================= SEND MESSAGE FORM (รายคน) ========================= --}}
    <div x-show="showSendMessage" x-cloak class="tp-card" style="border:1px solid var(--a1soft);">
        <div class="tp-section-h" style="margin-bottom:14px;">
            <i class="fas fa-comment-dots" style="color:var(--accent1);"></i>
            <span>ส่งข้อความรายคน</span>
        </div>
        <form action="{{ route('admin.fortune.users.send-message') }}" method="POST">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:14px; margin-bottom:14px;">
                {{-- Platform --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">Platform *</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform" x-model="sendPlatform"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="facebook">Facebook Messenger</option>
                            <option value="line">LINE</option>
                        </select>
                    </div>
                </div>
                {{-- User ID --}}
                <div style="grid-column:span 2; min-width:0;">
                    <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">User ID *</label>
                    <div class="tp-well tp-input">
                        <input type="text" name="facebook_user_id" x-model="sendUserId" required
                               placeholder="Facebook/LINE User ID"
                               style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px;">
                    </div>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">ข้อความ *</label>
                <div class="tp-well tp-input">
                    <textarea name="message" rows="4" required maxlength="2000"
                              placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
                              style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px; resize:vertical;"></textarea>
                </div>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-paper-plane"></i><span>ส่งข้อความ</span>
                </button>
                <button type="button" @click="showSendMessage = false" class="tp-btn">
                    <i class="fas fa-xmark"></i><span>ยกเลิก</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ========================= BROADCAST FORM ========================= --}}
    <div x-show="showBroadcast" x-cloak class="tp-card" style="border:1px solid var(--a2soft);">
        <div class="tp-section-h" style="margin-bottom:14px;">
            <i class="fas fa-bullhorn" style="color:var(--accent2);"></i>
            <span>ส่งข้อความถึงทุกคน (Broadcast)</span>
        </div>
        <form action="{{ route('admin.fortune.users.broadcast') }}" method="POST"
              onsubmit="return confirm('ยืนยันส่งข้อความ Broadcast? จะส่งไปทุกคนตามเงื่อนไขที่เลือก')">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:14px; margin-bottom:14px;">
                {{-- ช่องทาง --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">ช่องทาง</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="all">ทุกช่องทาง</option>
                            <option value="facebook">Facebook เท่านั้น</option>
                            <option value="line">LINE เท่านั้น</option>
                        </select>
                    </div>
                </div>
                {{-- กลุ่มเป้าหมาย --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">กลุ่มเป้าหมาย</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="target"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="all">ทุกคนที่เคยดูดวง</option>
                            <option value="paid">เฉพาะลูกค้าจ่ายเงิน</option>
                            <option value="recent">ดูดวงภายใน 7 วันล่าสุด</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">ข้อความ *</label>
                <div class="tp-well tp-input">
                    <textarea name="message" rows="4" required maxlength="2000"
                              placeholder="พิมพ์ข้อความ Broadcast..."
                              style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px; resize:vertical;"></textarea>
                </div>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-paper-plane"></i><span>ส่ง Broadcast</span>
                </button>
                <button type="button" @click="showBroadcast = false" class="tp-btn">
                    <i class="fas fa-xmark"></i><span>ยกเลิก</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ========================= SEARCH & FILTER ========================= --}}
    <div class="tp-card">
        <form method="GET" action="{{ route('admin.fortune.users.index') }}"
              style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            {{-- ค้นหา --}}
            <div style="flex:1; min-width:200px;">
                <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">ค้นหา</label>
                <div class="tp-well tp-input" style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-magnifying-glass tp-muted" style="font-size:13px;"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="User ID หรือชื่อ..."
                           style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px;">
                </div>
            </div>
            {{-- Platform --}}
            <div style="min-width:150px;">
                <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">Platform</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="platform"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="">ทุก Platform</option>
                        <option value="facebook" {{ request('platform') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="line" {{ request('platform') === 'line' ? 'selected' : '' }}>LINE</option>
                    </select>
                </div>
            </div>
            {{-- เรียงลำดับ --}}
            <div style="min-width:150px;">
                <label class="tp-muted" style="display:block; font-size:12px; font-weight:600; margin-bottom:6px;">เรียงลำดับ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="sort"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="last_reading_at" {{ request('sort') === 'last_reading_at' ? 'selected' : '' }}>ล่าสุด</option>
                        <option value="total_readings" {{ request('sort') === 'total_readings' ? 'selected' : '' }}>จำนวนดู</option>
                        <option value="total_spent" {{ request('sort') === 'total_spent' ? 'selected' : '' }}>ยอดจ่าย</option>
                    </select>
                </div>
            </div>
            {{-- จ่ายเงินแล้ว + ปุ่ม --}}
            <div style="display:flex; gap:10px; align-items:center;">
                <label class="tp-pill tp-pill-soft" style="cursor:pointer; display:inline-flex; align-items:center; gap:7px; white-space:nowrap;">
                    <input type="checkbox" name="paid_only" value="1" {{ request('paid_only') ? 'checked' : '' }}
                           style="accent-color:var(--accent1); width:15px; height:15px;">
                    <span>จ่ายเงินแล้ว</span>
                </label>
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i><span>ค้นหา</span>
                </button>
                @if(request()->hasAny(['search', 'platform', 'paid_only', 'sort']))
                    <a href="{{ route('admin.fortune.users.index') }}" class="tp-btn">
                        <i class="fas fa-eraser"></i><span>ล้าง</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ========================= USERS TABLE ========================= --}}
    <div class="tp-card" style="overflow-x:auto;">
        <table style="width:100%; border-collapse:separate; border-spacing:0 8px; min-width:760px;">
            <thead>
                <tr style="text-align:left;">
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ผู้ใช้</th>
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">Platform</th>
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">ดูดวง</th>
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">จ่ายเงิน</th>
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">ยอดจ่ายรวม</th>
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ดูล่าสุด</th>
                    <th style="padding:6px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr style="box-shadow:var(--inset-sm); border-radius:12px;">
                        {{-- ผู้ใช้ --}}
                        <td style="padding:12px; border-radius:12px 0 0 12px;">
                            <a href="{{ route('admin.fortune.users.show', ['platform' => $user->platform ?? 'facebook', 'userId' => $user->facebook_user_id]) }}"
                               style="text-decoration:none; display:block;">
                                <div class="tp-num" style="font-size:14px; font-weight:700; color:var(--accent1);">
                                    {{ $user->facebook_user_name ?? '-' }}
                                </div>
                                <div class="tp-muted" style="font-size:11px; font-family:monospace; word-break:break-all;">
                                    {{ $user->facebook_user_id }}
                                </div>
                            </a>
                        </td>

                        {{-- Platform --}}
                        <td style="padding:12px; text-align:center;">
                            @if(($user->platform ?? 'facebook') === 'line')
                                <span class="tp-pill" style="color:#5aa07e;"><i class="fab fa-line"></i> LINE</span>
                            @else
                                <span class="tp-pill" style="color:#5689b8;"><i class="fab fa-facebook-f"></i> Facebook</span>
                            @endif
                        </td>

                        {{-- จำนวนดูดวง --}}
                        <td style="padding:12px; text-align:center;">
                            <div class="tp-num" style="font-size:14px; font-weight:800;">{{ $user->total_readings }}</div>
                            @if($user->deep_readings > 0)
                                <div style="font-size:11px; color:#b79ae8; font-weight:600;">{{ $user->deep_readings }} ละเอียด</div>
                            @endif
                        </td>

                        {{-- จ่ายเงิน --}}
                        <td style="padding:12px; text-align:center;">
                            @if($user->paid_readings > 0)
                                <span class="tp-pill" style="color:#5aa07e;">{{ $user->paid_readings }} ครั้ง</span>
                            @else
                                <span class="tp-muted" style="font-size:11px;">ฟรี</span>
                            @endif
                        </td>

                        {{-- ยอดจ่ายรวม --}}
                        <td style="padding:12px; text-align:right;">
                            @if($user->total_spent > 0)
                                <span class="tp-num" style="font-size:14px; font-weight:800; color:#5aa07e;">฿{{ number_format($user->total_spent, 0) }}</span>
                            @else
                                <span class="tp-muted" style="font-size:11px;">-</span>
                            @endif
                        </td>

                        {{-- ดูล่าสุด --}}
                        <td style="padding:12px;">
                            <div style="font-size:13px; color:var(--ink);">
                                {{ \Carbon\Carbon::parse($user->last_reading_at)->diffForHumans() }}
                            </div>
                            <div class="tp-muted" style="font-size:11px;">
                                {{ \Carbon\Carbon::parse($user->last_reading_at)->format('d/m/Y H:i') }}
                            </div>
                        </td>

                        {{-- จัดการ --}}
                        <td style="padding:12px; border-radius:0 12px 12px 0;">
                            <div style="display:flex; justify-content:flex-end; gap:7px;">
                                {{-- ส่งข้อความ --}}
                                <button type="button"
                                        @click="openSendTo('{{ $user->platform ?? 'facebook' }}', '{{ $user->facebook_user_id }}', '{{ addslashes($user->facebook_user_name ?? '') }}')"
                                        class="tp-icon-btn" title="ส่งข้อความ">
                                    <i class="fas fa-comment-dots" style="color:var(--accent1);"></i>
                                </button>

                                {{-- เพิ่มเครดิต --}}
                                <form action="{{ route('admin.fortune.users.quick-add-credits') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="facebook_user_id" value="{{ $user->facebook_user_id }}">
                                    <input type="hidden" name="platform" value="{{ $user->platform ?? 'facebook' }}">
                                    <input type="hidden" name="facebook_user_name" value="{{ $user->facebook_user_name }}">
                                    <input type="hidden" name="amount" value="3">
                                    <button type="submit" class="tp-icon-btn" title="เพิ่ม 3 เครดิตฟรี"
                                            style="color:#5aa07e; font-weight:700; font-size:12px; gap:2px;">
                                        <i class="fas fa-gift"></i>+3
                                    </button>
                                </form>

                                {{-- ดูประวัติ --}}
                                <a href="{{ route('admin.fortune.users.show', ['platform' => $user->platform ?? 'facebook', 'userId' => $user->facebook_user_id]) }}"
                                   class="tp-icon-btn" title="ดูประวัติ">
                                    <i class="fas fa-clipboard-list" style="color:var(--ink2);"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:48px 12px; text-align:center;">
                            <div style="font-size:42px; color:var(--ink2); margin-bottom:10px;"><i class="fas fa-inbox"></i></div>
                            <p style="font-size:16px; font-weight:700; color:var(--ink); margin:0;">ยังไม่มีผู้ใช้ดูดวง</p>
                            <p class="tp-muted" style="font-size:13px; margin:4px 0 0;">เมื่อมีคนเริ่มดูดวง ข้อมูลจะแสดงที่นี่</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ========================= PAGINATION ========================= --}}
    @if($users->hasPages())
        <div>
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // คอมโพเนนต์ Alpine จัดการฟอร์มส่งข้อความรายคน/Broadcast
    function fortuneUsers() {
        return {
            showSendMessage: false,
            showBroadcast: false,
            sendPlatform: 'facebook',
            sendUserId: '',

            // เปิดฟอร์มส่งข้อความ พร้อมเติม platform + userId ของผู้ใช้ที่เลือก
            openSendTo(platform, userId, name) {
                this.sendPlatform = platform;
                this.sendUserId = userId;
                this.showSendMessage = true;
                this.showBroadcast = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    }
</script>
@endpush
