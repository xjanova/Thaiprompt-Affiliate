@extends('layouts.admin-v3')

@section('title', 'Session Details #' . $session->id)

@section('content')
{{--
    LINE Signup Session Details - V3 Theme
    ใช้ Tailwind CSS + Alpine.js
    Dark Mode Support + Responsive + Timeline
--}}
<div class="min-h-screen" x-data="sessionDetails()" x-init="init()">
    {{-- Page Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-[#00B900] via-[#06C755] to-[#00E600] p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            Session #{{ $session->id }}
                        </h1>
                        <p class="text-white/90 text-sm md:text-base mt-1">
                            รายละเอียดการ Signup ผ่าน LINE
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.line-membership-signup.sessions') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl text-white font-medium hover:bg-white/30 transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับรายการ</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        {{-- Session Info Card --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    ข้อมูลพื้นฐาน
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Session Token</label>
                        <div class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 px-3 py-2 rounded-lg">
                            {{ $session->session_token }}
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">LINE User ID</label>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center text-white">
                                <i class="fab fa-line text-sm"></i>
                            </div>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $session->line_user_id }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Step</label>
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300">
                                {{ $session->current_step }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</label>
                        <div class="mt-1">
                            @if($session->status === 'completed')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-sm font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    <i class="fas fa-check-circle"></i>
                                    Completed
                                </span>
                            @elseif($session->status === 'active')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                    </span>
                                    Active
                                </span>
                            @elseif($session->status === 'abandoned')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-sm font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Abandoned
                                </span>
                            @else
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    {{ $session->status }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</label>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-500"
                                    style="width: {{ $session->getProgressPercentage() }}%"
                                ></div>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $session->getProgressPercentage() }}%
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started At</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $session->started_at->format('d M Y H:i:s') }}
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $session->started_at->diffForHumans() }})</span>
                        </div>
                    </div>

                    @if($session->completed_at)
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed At</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $session->completed_at->format('d M Y H:i:s') }}
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                (Duration: {{ $session->started_at->diffInMinutes($session->completed_at) }} minutes)
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Step Logs Timeline --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-timeline text-purple-500"></i>
                    Timeline Steps
                </h2>

                @if($session->stepLogs && $session->stepLogs->count() > 0)
                <div class="relative">
                    {{-- Timeline Line --}}
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-400 via-purple-400 to-pink-400"></div>

                    <div class="space-y-6">
                        @foreach($session->stepLogs as $log)
                        <div class="relative pl-16">
                            {{-- Timeline Dot --}}
                            <div class="absolute left-0 top-0 w-12 h-12 rounded-xl
                                {{ $log->status === 'completed' ? 'bg-gradient-to-br from-green-400 to-green-600' :
                                   ($log->status === 'in_progress' ? 'bg-gradient-to-br from-blue-400 to-blue-600' :
                                   'bg-gradient-to-br from-gray-300 to-gray-400') }}
                                flex items-center justify-center text-white shadow-lg">
                                @if($log->status === 'completed')
                                    <i class="fas fa-check"></i>
                                @elseif($log->status === 'in_progress')
                                    <i class="fas fa-spinner fa-spin"></i>
                                @else
                                    <i class="fas fa-circle"></i>
                                @endif
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 hover:shadow-md transition-shadow duration-300">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ $log->step_name }}</h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->format('H:i:s') }}
                                    </span>
                                </div>

                                @if($log->data)
                                <div class="text-xs text-gray-600 dark:text-gray-400 font-mono bg-white dark:bg-gray-800 p-2 rounded mt-2">
                                    {{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                </div>
                                @endif

                                @if($log->completed_at)
                                <div class="mt-2 text-xs text-green-600 dark:text-green-400">
                                    <i class="fas fa-clock mr-1"></i>
                                    เสร็จใน {{ $log->created_at->diffInSeconds($log->completed_at) }} วินาที
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-list-timeline text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มี step logs</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-user text-indigo-500"></i>
                    User Information
                </h2>

                @if($session->user)
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($session->user->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ $session->user->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $session->user->email }}</div>
                        </div>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มี user ที่เชื่อมโยง</p>
                @endif
            </div>

            {{-- MLM Member Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-link text-purple-500"></i>
                    MLM Member
                </h2>

                @php
                    $mlmMember = $session->mlmMember();
                @endphp
                @if($mlmMember)
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Member Code</span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $mlmMember->member_code }}</span>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">ไม่มี MLM member</p>
                @endif
            </div>

            {{-- Rewards --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-gift text-yellow-500"></i>
                    Rewards
                </h2>

                @if($session->rewards && $session->rewards->count() > 0)
                <div class="space-y-2">
                    @foreach($session->rewards as $reward)
                    <div class="flex items-center gap-2 p-3 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg">
                        <i class="fas fa-star text-yellow-500"></i>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $reward->reward_type }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มี rewards</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Conversations --}}
    @if($session->conversations && $session->conversations->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-comments text-green-500"></i>
            Conversations ({{ $session->conversations->count() }})
        </h2>

        <div class="space-y-4 max-h-96 overflow-y-auto">
            @foreach($session->conversations as $conversation)
            <div class="flex gap-3 {{ $conversation->message_type === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-lg {{ $conversation->message_type === 'user' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-gray-100 dark:bg-gray-700' }} rounded-2xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        @if($conversation->message_type === 'user')
                            <i class="fas fa-user-circle text-blue-600 dark:text-blue-400"></i>
                            <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">User</span>
                        @else
                            <i class="fas fa-robot text-gray-600 dark:text-gray-400"></i>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Bot</span>
                        @endif
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $conversation->created_at->format('H:i') }}</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-white">
                        {{ $conversation->message }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * Session Details - Alpine.js Component
 *
 * จัดการ state สำหรับ session details
 */
function sessionDetails() {
    return {
        /**
         * เริ่มต้น component
         */
        init() {
            // เพิ่ม functionality เพิ่มเติมได้ที่นี่
        }
    };
}
</script>
@endpush
@endsection
