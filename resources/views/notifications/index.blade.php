@extends('layouts.user')

@section('title', 'การแจ้งเตือน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">🔔 การแจ้งเตือน</h1>
                    <p class="text-indigo-100 text-lg">ติดตามข่าวสารและกิจกรรมของคุณ</p>
                </div>
                @if($unreadCount > 0)
                <div class="flex gap-3">
                    <form action="{{ route('user.notifications.read-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl font-semibold transition transform hover:scale-105 flex items-center gap-2">
                            <span>✓</span>
                            <span>อ่านทั้งหมด ({{ $unreadCount }})</span>
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📬</div>
                <div>
                    <p class="text-xs text-gray-500">ทั้งหมด</p>
                    <p class="text-xl font-bold text-gray-800">{{ $notifications->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition">
            <div class="flex items-center gap-3">
                <div class="text-3xl">🔵</div>
                <div>
                    <p class="text-xs text-gray-500">ยังไม่อ่าน</p>
                    <p class="text-xl font-bold text-blue-600">{{ $unreadCount }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition">
            <div class="flex items-center gap-3">
                <div class="text-3xl">💰</div>
                <div>
                    <p class="text-xs text-gray-500">การเงิน</p>
                    <p class="text-xl font-bold text-green-600">{{ $notifications->where('type', 'wallet')->count() + $notifications->where('type', 'withdrawal')->count() + $notifications->where('type', 'deposit')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition">
            <div class="flex items-center gap-3">
                <div class="text-3xl">⭐</div>
                <div>
                    <p class="text-xs text-gray-500">สำคัญ</p>
                    <p class="text-xl font-bold text-orange-600">{{ $notifications->where('is_important', true)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">รายการแจ้งเตือน</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                <div class="p-4 hover:bg-gray-50 transition {{ !$notification->is_read ? 'bg-blue-50' : '' }}"
                     x-data="{ showActions: false }">
                    <div class="flex gap-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl
                                {{ $notification->color === 'green' ? 'bg-green-100' : '' }}
                                {{ $notification->color === 'blue' ? 'bg-blue-100' : '' }}
                                {{ $notification->color === 'red' ? 'bg-red-100' : '' }}
                                {{ $notification->color === 'orange' ? 'bg-orange-100' : '' }}
                                {{ $notification->color === 'purple' ? 'bg-purple-100' : '' }}
                                {{ $notification->color === 'gray' ? 'bg-gray-100' : '' }}">
                                {{ $notification->icon }}
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-gray-900 {{ !$notification->is_read ? 'font-bold' : '' }}">
                                            {{ $notification->title }}
                                        </h3>
                                        @if(!$notification->is_read)
                                            <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                        @endif
                                        @if($notification->is_important)
                                            <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-xs font-semibold">สำคัญ</span>
                                        @endif
                                    </div>

                                    <p class="text-sm text-gray-600 mb-2">{{ $notification->message }}</p>

                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>

                                        <span class="px-2 py-0.5 rounded-full text-xs
                                            {{ $notification->priority_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $notification->priority_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $notification->priority_color === 'orange' ? 'bg-orange-100 text-orange-800' : '' }}
                                            {{ $notification->priority_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $notification->priority_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ $notification->priority_label }}
                                        </span>

                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs">
                                            {{ $notification->type_label }}
                                        </span>
                                    </div>

                                    <!-- Action Button -->
                                    @if($notification->action_url && $notification->action_text)
                                        <div class="mt-3">
                                            <a href="{{ $notification->action_url }}"
                                               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                                                {{ $notification->action_text }}
                                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <!-- Actions Menu -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="text-gray-400 hover:text-gray-600 p-1">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>

                                    <div x-show="open"
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                                         style="display: none;">
                                        <div class="py-1">
                                            @if(!$notification->is_read)
                                                <form action="{{ route('user.notifications.read', $notification->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        ทำเครื่องหมายว่าอ่านแล้ว
                                                    </button>
                                                </form>
                                            @endif

                                            <form action="{{ route('user.notifications.archive', $notification->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                    </svg>
                                                    เก็บถาวร
                                                </button>
                                            </form>

                                            <form action="{{ route('user.notifications.destroy', $notification->id) }}" method="POST"
                                                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบการแจ้งเตือนนี้?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">ไม่มีการแจ้งเตือน</h3>
                    <p class="text-gray-500">คุณไม่มีการแจ้งเตือนในขณะนี้</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
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
