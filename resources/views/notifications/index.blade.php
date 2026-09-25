{{--
 | การแจ้งเตือน (ธีม V4) — ใช้ร่วมกันทั้งฝั่งสมาชิกและผู้ขาย
 | GAP-23 (2026-09-25): เข้าจากเมนูผู้ขาย (seller.notifications.*) ต้องอยู่ในเปลือกผู้ขาย และยิงฟอร์มไป route ของผู้ขาย
 | ไม่ให้ sidebar/เมนูสลับเป็นของสมาชิก
 --}}
@extends(request()->routeIs('seller.*') ? 'layouts.seller-v4' : 'layouts.user-v4')

@section('title', 'การแจ้งเตือน')

@php
    // prefix ของ route ตามเปลือกที่เปิดอยู่ (ทั้งสองชุดชี้ไป NotificationController ตัวเดียวกัน)
    $np = request()->routeIs('seller.*') ? 'seller.notifications.' : 'user.notifications.';

    $tone = [
        'green' => 'var(--tp-ok, #5aa07e)',
        'blue' => 'var(--tp-info, #5689b8)',
        'red' => 'var(--tp-bad, #d9534f)',
        'orange' => 'var(--tp-warn, #e08a3c)',
        'purple' => 'var(--tp-violet, #7c5cbf)',
    ];
    $toneDefault = 'var(--ink2)';
    $financeCount = $notifications->whereIn('type', ['wallet', 'withdrawal', 'deposit'])->count();
    $importantCount = $notifications->where('is_important', true)->count();
    $statCards = [
        ['📬', 'ทั้งหมด', $notifications->total(), 'var(--ink)'],
        ['🔵', 'ยังไม่อ่าน', $unreadCount, $tone['blue']],
        ['💰', 'การเงิน (หน้านี้)', $financeCount, $tone['green']],
        ['⭐', 'สำคัญ (หน้านี้)', $importantCount, $tone['orange']],
    ];
    $bulkConfig = [
        'readUrl' => route($np . 'bulk-mark-as-read'),
        'deleteUrl' => route($np . 'bulk-delete'),
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px;">🔔</span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">การแจ้งเตือน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามข่าวสารและกิจกรรมของคุณ</div>
                </div>
            </div>
            @if($unreadCount > 0)
                <form action="{{ route($np . 'read-all') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm"><span>✓</span> อ่านทั้งหมด ({{ $unreadCount }})</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ── สถิติ ─────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px;">
        @foreach($statCards as [$icon, $label, $value, $color])
            <div class="tp-card" style="padding:16px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:26px;">{{ $icon }}</span>
                    <div>
                        <p style="font-size:11px; color:var(--ink2); margin:0;">{{ $label }}</p>
                        <p class="tp-num" style="font-size:20px; font-weight:800; color:{{ $color }}; margin:0;">{{ number_format($value) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── รายการแจ้งเตือน ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;" x-data="notificationBulk(@js($bulkConfig))">
        <div style="padding:18px 24px; box-shadow:var(--inset-sm);">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div class="tp-section-h">รายการแจ้งเตือน</div>
                <div class="flex" style="align-items:center; gap:8px; flex-wrap:wrap;" x-show="selectedIds.length > 0" x-cloak>
                    <span style="font-size:13px; color:var(--ink2);" x-text="'เลือก ' + selectedIds.length + ' รายการ'"></span>
                    <button type="button" @click="bulk('read')" :disabled="busy" class="tp-btn tp-btn-sm"
                            style="color:var(--tp-on-accent, #fff); background:{{ $tone['blue'] }};"><i class="fas fa-check"></i> อ่านแล้ว</button>
                    <button type="button" @click="bulk('delete')" :disabled="busy" class="tp-btn tp-btn-sm"
                            style="color:var(--tp-on-accent, #fff); background:{{ $tone['red'] }};"><i class="fas fa-trash"></i> ลบ</button>
                </div>
            </div>
            @if($notifications->count() > 0)
                <label style="display:inline-flex; align-items:center; cursor:pointer; margin-top:12px; gap:8px;">
                    <input type="checkbox" :checked="allChecked()" @change="toggleAll($event.target.checked)" style="width:16px; height:16px; accent-color:var(--accent1);">
                    <span style="font-size:13px; color:var(--ink2);">เลือกทั้งหมดในหน้านี้</span>
                </label>
            @endif
        </div>

        <div>
            @forelse($notifications as $notification)
                @php
                    $icColor = $tone[$notification->color] ?? $toneDefault;
                    $prColor = $tone[$notification->priority_color] ?? $toneDefault;
                @endphp
                <div style="padding:16px 24px; border-top:1px solid color-mix(in srgb, var(--ink2) 10%, transparent); {{ ! $notification->is_read ? 'background:color-mix(in srgb, var(--accent1) 7%, transparent);' : '' }}">
                    <div style="display:flex; gap:14px;">
                        <div style="flex-shrink:0; padding-top:8px;">
                            <input type="checkbox" class="notification-checkbox" style="width:18px; height:18px; accent-color:var(--accent1);"
                                   value="{{ $notification->id }}" x-model.number="selectedIds" aria-label="เลือกการแจ้งเตือนนี้">
                        </div>
                        <div style="flex-shrink:0;">
                            <div style="width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; background:color-mix(in srgb, {{ $icColor }} 16%, transparent);">{{ $notification->icon }}</div>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px;">
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                                        <h3 style="font-weight:{{ ! $notification->is_read ? 800 : 600 }}; color:var(--ink); margin:0; font-size:14.5px; overflow-wrap:anywhere;">{{ $notification->title }}</h3>
                                        @if(! $notification->is_read)<span style="width:8px; height:8px; background:{{ $tone['blue'] }}; border-radius:50%;"></span>@endif
                                        @if($notification->is_important)<span class="tp-pill" style="color:{{ $tone['red'] }}; background:color-mix(in srgb, {{ $tone['red'] }} 16%, transparent);">สำคัญ</span>@endif
                                    </div>
                                    <p style="font-size:13px; color:var(--ink2); margin:0 0 8px; overflow-wrap:anywhere;">{{ $notification->message }}</p>
                                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:11px; color:var(--ink2);">
                                        <span><i class="fas fa-clock" style="margin-right:4px;"></i>{{ $notification->created_at->diffForHumans() }}</span>
                                        <span class="tp-pill" style="color:{{ $prColor }}; background:color-mix(in srgb, {{ $prColor }} 16%, transparent);">{{ $notification->priority_label }}</span>
                                        <span class="tp-pill" style="box-shadow:var(--inset-sm); color:var(--ink2);">{{ $notification->type_label }}</span>
                                    </div>
                                    @if($notification->action_url && $notification->action_text)
                                        <div style="margin-top:12px;">
                                            <a href="{{ $notification->action_url }}" class="tp-btn tp-btn-sm tp-btn-primary" style="text-decoration:none;">{{ $notification->action_text }} <i class="fas fa-arrow-right" style="margin-left:4px;"></i></a>
                                        </div>
                                    @endif
                                </div>
                                <div style="position:relative;" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" aria-label="ตัวเลือก" style="color:var(--ink2); background:none; border:none; padding:6px; cursor:pointer;"><i class="fas fa-ellipsis-v"></i></button>
                                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                                         class="tp-card" style="position:absolute; right:0; margin-top:6px; width:210px; padding:6px; z-index:20;">
                                        @if(! $notification->is_read)
                                            <form action="{{ route($np . 'read', $notification->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="tpdd-item" style="width:100%; background:none; border:none; cursor:pointer; font-family:inherit;"><i class="fas fa-check"></i> ทำเครื่องหมายว่าอ่านแล้ว</button>
                                            </form>
                                        @endif
                                        <form action="{{ route($np . 'archive', $notification->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="tpdd-item" style="width:100%; background:none; border:none; cursor:pointer; font-family:inherit;"><i class="fas fa-box-archive"></i> เก็บถาวร</button>
                                        </form>
                                        <form action="{{ route($np . 'destroy', $notification->id) }}" method="POST" onsubmit="return confirm('ลบการแจ้งเตือนนี้ใช่ไหม? ลบแล้วกู้คืนไม่ได้')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tpdd-item" style="width:100%; background:none; border:none; cursor:pointer; font-family:inherit; color:{{ $tone['red'] }};"><i class="fas fa-trash"></i> ลบ</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding:48px 24px; text-align:center;">
                    <div style="font-size:56px; margin-bottom:16px;">📭</div>
                    <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 8px;">ไม่มีการแจ้งเตือน</h3>
                    <p style="color:var(--ink2); margin:0;">คุณไม่มีการแจ้งเตือนในขณะนี้</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div style="padding:14px 24px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                {{ $notifications->links('vendor.pagination.tp-v4') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * เลือกหลายรายการ → ทำเครื่องหมายอ่าน / ลบ (เรียก endpoint ตามเปลือกที่เปิดอยู่)
 */
function notificationBulk(cfg) {
    return {
        selectedIds: [],
        busy: false,
        allIds() {
            return Array.from(document.querySelectorAll('.notification-checkbox')).map((cb) => parseInt(cb.value, 10));
        },
        allChecked() {
            const all = this.allIds();
            return all.length > 0 && all.every((id) => this.selectedIds.includes(id));
        },
        toggleAll(checked) {
            this.selectedIds = checked ? this.allIds() : [];
        },
        async bulk(kind) {
            if (this.busy || this.selectedIds.length === 0) return;
            const n = this.selectedIds.length;
            const question = kind === 'delete'
                ? 'ลบการแจ้งเตือน ' + n + ' รายการใช่ไหม? ลบแล้วกู้คืนไม่ได้'
                : 'ทำเครื่องหมายว่าอ่านแล้ว ' + n + ' รายการ?';
            if (!confirm(question)) return;
            this.busy = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]');
                const res = await fetch(kind === 'delete' ? cfg.deleteUrl : cfg.readUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                    },
                    body: JSON.stringify({ notification_ids: this.selectedIds })
                });
                const data = await res.json().catch(() => null);
                if (res.ok && data && data.success) {
                    window.location.reload();
                    return;
                }
                const msg = (data && data.message) ? data.message : 'ทำรายการไม่สำเร็จ กรุณาลองใหม่';
                if (window.showNotification) window.showNotification(msg, 'error'); else alert(msg);
            } catch (e) {
                const msg = 'เชื่อมต่อไม่ได้ กรุณาลองใหม่';
                if (window.showNotification) window.showNotification(msg, 'error'); else alert(msg);
            } finally {
                this.busy = false;
            }
        }
    };
}
</script>
@endpush
