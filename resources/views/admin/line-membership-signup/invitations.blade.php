@extends('layouts.admin-v3')

@section('title', 'LINE Signup Invitations')

@section('content')
{{--
    LINE Signup Invitations - V3 Theme
    ใช้ Tailwind CSS + Alpine.js
    Dark Mode Support + Responsive
--}}
<div class="min-h-screen" x-data="invitationsManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-[#00B900] via-[#06C755] to-[#00E600] p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            📨 Signup Invitations
                        </h1>
                        <p class="text-white/90 text-sm md:text-base mt-1">
                            จัดการ invitation links สำหรับ LINE signup
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.line-membership-signup.index') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl text-white font-medium hover:bg-white/30 transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        {{-- Total Invitations --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 p-6 rounded-2xl border border-[#06C755]/30">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-[#00B900] dark:text-[#00E600]">ลิงก์เชิญทั้งหมด</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center">
                    <i class="fas fa-link text-white text-xl"></i>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $invitations->total() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count).toLocaleString()">0</h2>
            </div>
            <p class="text-xs text-[#00B900] dark:text-[#00E600]">ทั้งหมด</p>
        </div>

        {{-- Active Links --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 p-6 rounded-2xl border border-white/20 dark:border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-600 dark:text-blue-400">ลิงก์ที่ใช้งานได้</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($invitations->where('status', 'sent')->count()) }}</h2>
            <p class="text-xs text-blue-600 dark:text-blue-400">Active</p>
        </div>

        {{-- Expired Links --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 p-6 rounded-2xl border border-white/20 dark:border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">ลิงก์หมดอายุ</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-white text-xl"></i>
                </div>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($invitations->where('status', 'expired')->count()) }}</h2>
            <p class="text-xs text-red-600 dark:text-red-400">Expired</p>
        </div>

        {{-- Accepted Invitations --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 p-6 rounded-2xl border border-white/20 dark:border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-green-600 dark:text-green-400">ยอมรับแล้ว</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-check text-white text-xl"></i>
                </div>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($invitations->where('status', 'accepted')->count()) }}</h2>
            <p class="text-xs text-green-600 dark:text-green-400">Conversion {{ $invitations->total() > 0 ? number_format(($invitations->where('status', 'accepted')->count() / $invitations->total()) * 100, 1) : 0 }}%</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.line-membership-signup.invitations') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-filter mr-1"></i>
                    สถานะ
                </label>
                <select
                    name="status"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-300"
                    x-model="statusFilter"
                >
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button
                    type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-medium transition-all duration-300 hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-2"
                >
                    <i class="fas fa-filter"></i>
                    <span>กรองข้อมูล</span>
                </button>
                <a
                    href="{{ route('admin.line-membership-signup.invitations') }}"
                    class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-all duration-300 flex items-center gap-2"
                >
                    <i class="fas fa-redo"></i>
                    <span>รีเซ็ต</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Invitations Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-table text-green-500"></i>
                รายการ Invitations ({{ $invitations->total() }} รายการ)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Invitation Link</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Sender</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Recipient</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invitations as $invitation)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">#{{ $invitation->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 max-w-xs">
                                <code class="text-xs bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded font-mono text-gray-900 dark:text-white truncate">
                                    {{ $invitation->invitation_link ?? 'N/A' }}
                                </code>
                                @if($invitation->invitation_link)
                                <button
                                    @click="copyToClipboard('{{ $invitation->invitation_link }}')"
                                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-gradient-to-br from-[#00B900] to-[#00E600] text-white hover:from-[#00A000] hover:to-[#00D000] transition-all duration-200 transform hover:scale-105"
                                    title="คัดลอก"
                                >
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                                <button
                                    @click="showQR('{{ $invitation->invitation_link }}')"
                                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors duration-200"
                                    title="QR Code"
                                >
                                    <i class="fas fa-qrcode text-xs"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invitation->sender)
                                <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $invitation->sender->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $invitation->sender->email }}</div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $invitation->recipient_line_id ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invitation->status === 'accepted')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    <i class="fas fa-check-circle"></i>
                                    Accepted
                                </span>
                            @elseif($invitation->status === 'sent')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-paper-plane"></i>
                                    Sent
                                </span>
                            @elseif($invitation->status === 'expired')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    <i class="fas fa-times-circle"></i>
                                    Expired
                                </span>
                            @else
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    {{ $invitation->status }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $invitation->created_at->format('d M Y') }}</span>
                                <span class="text-xs text-gray-500">{{ $invitation->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <button
                                    @click="viewDetails({{ json_encode($invitation) }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors duration-200"
                                    title="ดูรายละเอียด"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <i class="fas fa-inbox text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบข้อมูล invitations</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($invitations->hasPages())
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            {{ $invitations->links() }}
        </div>
        @endif
    </div>

    {{-- QR Code Modal --}}
    <div x-show="showQRModal"
         x-cloak
         @click.self="closeQRModal()"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.stop
             class="glass-fusion backdrop-blur-xl bg-white/90 dark:bg-slate-800/90 rounded-3xl p-8 max-w-md w-full border border-white/20 dark:border-slate-700/50 shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#06C755]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                    QR Code เชิญเพื่อน
                </h3>
                <button @click="closeQRModal()"
                        class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-times text-gray-600 dark:text-gray-300"></i>
                </button>
            </div>

            {{-- QR Code Container --}}
            <div class="bg-white p-6 rounded-2xl mb-6 flex items-center justify-center">
                <div id="qrcode" class="flex items-center justify-center"></div>
            </div>

            <p class="text-center text-gray-600 dark:text-gray-400 mb-6">
                ให้เพื่อนสแกน QR Code นี้เพื่อสมัครสมาชิก
            </p>

            <button @click="closeQRModal()"
                    class="w-full px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#00A000] hover:to-[#00D000] text-white rounded-xl font-bold transition-all duration-300 transform hover:scale-105">
                ปิด
            </button>
        </div>
    </div>
</div>

@push('scripts')
{{-- QR Code Library --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
/**
 * Animated counter function
 */
function animateCount(start, end, duration, callback) {
    const startTime = Date.now();
    const endTime = startTime + duration;

    function update() {
        const now = Date.now();
        const progress = Math.min((now - startTime) / duration, 1);
        const value = start + (end - start) * progress;
        callback(value);

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

/**
 * Invitations Manager - Alpine.js Component
 *
 * จัดการ state สำหรับ invitations
 */
function invitationsManager() {
    return {
        statusFilter: '{{ request("status") }}',
        showQRModal: false,
        currentLink: '',
        qrCode: null,

        /**
         * เริ่มต้น component
         */
        init() {
            // เพิ่ม functionality เพิ่มเติมได้ที่นี่
        },

        /**
         * คัดลอก link ไปยัง clipboard
         */
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show success notification
                this.showNotification('✅ คัดลอก invitation link แล้ว!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                this.showNotification('❌ ไม่สามารถคัดลอกได้');
            });
        },

        /**
         * แสดง QR Code
         */
        showQR(link) {
            this.currentLink = link;
            this.showQRModal = true;

            // Wait for modal to render
            this.$nextTick(() => {
                // Clear previous QR code
                const container = document.getElementById('qrcode');
                container.innerHTML = '';

                // Generate new QR code
                this.qrCode = new QRCode(container, {
                    text: link,
                    width: 256,
                    height: 256,
                    colorDark: '#06C755',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            });
        },

        /**
         * ปิด QR Modal
         */
        closeQRModal() {
            this.showQRModal = false;
            this.currentLink = '';
        },

        /**
         * แสดง notification
         */
        showNotification(message) {
            // Simple alert for now - can be replaced with toast notification
            alert(message);
        },

        /**
         * ดูรายละเอียด invitation
         */
        viewDetails(invitation) {
            console.log('Invitation details:', invitation);
            // เพิ่ม modal หรือ redirect ไปหน้ารายละเอียดได้ที่นี่
        }
    };
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endpush
@endsection
