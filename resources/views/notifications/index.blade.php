@extends('layouts.user-v4')

@section('title', 'การแจ้งเตือน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; background:#7c5cbf;"><span style="color:#fff;">🔔</span></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">การแจ้งเตือน</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามข่าวสารและกิจกรรมของคุณ</div>
                    </div>
                </div>
                @if($unreadCount > 0)
                    <form action="{{ route('user.notifications.read-all') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-sm"><span>✓</span> อ่านทั้งหมด ({{ $unreadCount }})</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ── สถิติ ─────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px;">
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:26px;">📬</span>
                <div><p style="font-size:11px; color:var(--ink2); margin:0;">ทั้งหมด</p><p class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink); margin:0;">{{ $notifications->total() }}</p></div>
            </div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:26px;">🔵</span>
                <div><p style="font-size:11px; color:var(--ink2); margin:0;">ยังไม่อ่าน</p><p class="tp-num" style="font-size:20px; font-weight:800; color:#5689b8; margin:0;">{{ $unreadCount }}</p></div>
            </div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:26px;">💰</span>
                <div><p style="font-size:11px; color:var(--ink2); margin:0;">การเงิน</p><p class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e; margin:0;">{{ $notifications->where('type', 'wallet')->count() + $notifications->where('type', 'withdrawal')->count() + $notifications->where('type', 'deposit')->count() }}</p></div>
            </div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:26px;">⭐</span>
                <div><p style="font-size:11px; color:var(--ink2); margin:0;">สำคัญ</p><p class="tp-num" style="font-size:20px; font-weight:800; color:#e08a3c; margin:0;">{{ $notifications->where('is_important', true)->count() }}</p></div>
            </div>
        </div>
    </div>

    {{-- ── รายการแจ้งเตือน ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;" x-data="{
        selectedIds: [],
        selectAll: false,
        toggleAll() {
            this.selectAll = !this.selectAll;
            if (this.selectAll) {
                this.selectedIds = Array.from(document.querySelectorAll('.notification-checkbox')).map(cb => parseInt(cb.value));
            } else {
                this.selectedIds = [];
            }
        }
    }">
        <div style="padding:20px 24px; box-shadow:var(--inset-sm);">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div class="tp-section-h">รายการแจ้งเตือน</div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;" x-show="selectedIds.length > 0" x-cloak>
                    <span style="font-size:13px; color:var(--ink2);" x-text="'เลือก ' + selectedIds.length + ' รายการ'"></span>
                    <button @click="bulkMarkAsRead()" class="tp-btn tp-btn-sm" style="background:#5689b8; border-color:#5689b8; color:#fff;"><i class="fas fa-check"></i> อ่านแล้ว</button>
                    <button @click="bulkDelete()" class="tp-btn tp-btn-sm" style="background:#d9534f; border-color:#d9534f; color:#fff;"><i class="fas fa-trash"></i> ลบ</button>
                </div>
            </div>
            @if($notifications->count() > 0)
                <label style="display:inline-flex; align-items:center; cursor:pointer; margin-top:14px; gap:8px;">
                    <input type="checkbox" x-model="selectAll" @change="toggleAll()" style="width:16px; height:16px;">
                    <span style="font-size:13px; color:var(--ink2);">เลือกทั้งหมด</span>
                </label>
            @endif
        </div>

        <div>
            @forelse($notifications as $notification)
                @php
                    $icColor = match($notification->color) {
                        'green' => '#5aa07e', 'blue' => '#5689b8', 'red' => '#d9534f',
                        'orange' => '#e08a3c', 'purple' => '#7c5cbf', default => '#8a8a8a',
                    };
                    $prColor = match($notification->priority_color) {
                        'green' => '#5aa07e', 'blue' => '#5689b8', 'orange' => '#e08a3c',
                        'red' => '#d9534f', default => '#8a8a8a',
                    };
                @endphp
                <div style="padding:16px 24px; border-top:1px solid color-mix(in srgb, var(--ink2) 10%, transparent); {{ !$notification->is_read ? 'background:color-mix(in srgb, #5689b8 7%, transparent);' : '' }}" x-data="{ showActions: false }">
                    <div style="display:flex; gap:14px;">
                        <div style="flex-shrink:0; padding-top:8px;">
                            <input type="checkbox" class="notification-checkbox" style="width:18px; height:18px;" value="{{ $notification->id }}" x-model="selectedIds">
                        </div>
                        <div style="flex-shrink:0;">
                            <div style="width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; background:color-mix(in srgb, {{ $icColor }} 16%, transparent);">{{ $notification->icon }}</div>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px;">
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                                        <h3 style="font-weight:{{ !$notification->is_read ? 800 : 600 }}; color:var(--ink); margin:0;">{{ $notification->title }}</h3>
                                        @if(!$notification->is_read)<span style="width:8px; height:8px; background:#5689b8; border-radius:50%;"></span>@endif
                                        @if($notification->is_important)<span style="padding:1px 8px; border-radius:6px; font-size:11px; font-weight:600; color:#d9534f; background:color-mix(in srgb, #d9534f 16%, transparent);">สำคัญ</span>@endif
                                    </div>
                                    <p style="font-size:13px; color:var(--ink2); margin:0 0 8px;">{{ $notification->message }}</p>
                                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:11px; color:var(--ink2);">
                                        <span><i class="fas fa-clock" style="margin-right:4px;"></i>{{ $notification->created_at->diffForHumans() }}</span>
                                        <span style="padding:2px 8px; border-radius:999px; color:{{ $prColor }}; background:color-mix(in srgb, {{ $prColor }} 16%, transparent);">{{ $notification->priority_label }}</span>
                                        <span style="padding:2px 8px; border-radius:999px; box-shadow:var(--inset-sm); color:var(--ink2);">{{ $notification->type_label }}</span>
                                    </div>
                                    @if($notification->action_url && $notification->action_text)
                                        <div style="margin-top:12px;">
                                            <a href="{{ $notification->action_url }}" class="tp-btn tp-btn-sm tp-btn-primary" style="background:#7c5cbf; border-color:#7c5cbf;">{{ $notification->action_text }} <i class="fas fa-arrow-right" style="margin-left:4px;"></i></a>
                                        </div>
                                    @endif
                                </div>
                                <div style="position:relative;" x-data="{ open: false }">
                                    <button @click="open = !open" style="color:var(--ink2); background:none; border:none; padding:4px; cursor:pointer;"><i class="fas fa-ellipsis-v"></i></button>
                                    <div x-show="open" x-cloak @click.away="open = false"
                                         x-transition
                                         style="display:none; position:absolute; right:0; margin-top:8px; width:200px; border-radius:12px; box-shadow:var(--raise); background:var(--surf1); z-index:20; overflow:hidden;">
                                        @if(!$notification->is_read)
                                            <form action="{{ route('user.notifications.read', $notification->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" style="display:flex; align-items:center; width:100%; padding:10px 16px; font-size:13px; color:var(--ink); background:none; border:none; cursor:pointer; gap:8px;"><i class="fas fa-check"></i> ทำเครื่องหมายว่าอ่านแล้ว</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('user.notifications.archive', $notification->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" style="display:flex; align-items:center; width:100%; padding:10px 16px; font-size:13px; color:var(--ink); background:none; border:none; cursor:pointer; gap:8px;"><i class="fas fa-box-archive"></i> เก็บถาวร</button>
                                        </form>
                                        <form action="{{ route('user.notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบการแจ้งเตือนนี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="display:flex; align-items:center; width:100%; padding:10px 16px; font-size:13px; color:#d9534f; background:none; border:none; cursor:pointer; gap:8px;"><i class="fas fa-trash"></i> ลบ</button>
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
                {{ $notifications->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Auto-hide success messages
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
</script>
@endpush
@endsection

@push('scripts')
<script>
// Bulk Mark as Read
function bulkMarkAsRead() {
    const selectedIds = Alpine.store ? Alpine.store('selectedIds') : this.selectedIds;

    if (!selectedIds || selectedIds.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือนอย่างน้อย 1 รายการ');
        return;
    }

    if (!confirm(`ต้องการทำเครื่องหมายอ่าน ${selectedIds.length} รายการใช่หรือไม่?`)) {
        return;
    }

    fetch('{{ route("user.notifications.bulk-mark-as-read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            notification_ids: selectedIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถดำเนินการได้'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการทำเครื่องหมายอ่าน');
    });
}

// Bulk Delete
function bulkDelete() {
    const selectedIds = Alpine.store ? Alpine.store('selectedIds') : this.selectedIds;

    if (!selectedIds || selectedIds.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือนอย่างน้อย 1 รายการ');
        return;
    }

    if (!confirm(`ต้องการลบการแจ้งเตือน ${selectedIds.length} รายการใช่หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้`)) {
        return;
    }

    fetch('{{ route("user.notifications.bulk-delete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            notification_ids: selectedIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถดำเนินการได้'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการลบ');
    });
}
</script>
@endpush
