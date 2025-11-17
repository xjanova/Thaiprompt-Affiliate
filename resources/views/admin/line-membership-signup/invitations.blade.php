@extends('layouts.admin')

@section('title', 'LINE Signup Invitations')

@section('content')
{{--
    LINE Signup Invitations - V3 Theme
    ใช้ Tailwind CSS + Alpine.js
    Dark Mode Support + Responsive
--}}
<div class="min-h-screen" x-data="invitationsManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500 p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <i class="fas fa-envelope text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            Signup Invitations
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
                                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors duration-200"
                                    title="คัดลอก"
                                >
                                    <i class="fas fa-copy text-xs"></i>
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
</div>

@push('scripts')
<script>
/**
 * Invitations Manager - Alpine.js Component
 *
 * จัดการ state สำหรับ invitations
 */
function invitationsManager() {
    return {
        statusFilter: '{{ request("status") }}',

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
                alert('คัดลอก invitation link แล้ว!');
            });
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
@endpush
@endsection
