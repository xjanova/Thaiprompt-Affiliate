@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการสนทนา - LINE AI Bot')

@push('styles')
@vite(['resources/css/app.css'])
<style>
/* Fade In Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

/* Smooth scroll for chat container */
.chat-container {
    scroll-behavior: smooth;
}

.chat-container::-webkit-scrollbar {
    width: 8px;
}

.chat-container::-webkit-scrollbar-track {
    background: transparent;
}

.chat-container::-webkit-scrollbar-thumb {
    background: rgba(0, 185, 0, 0.3);
    border-radius: 10px;
}

.chat-container::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 185, 0, 0.5);
}
</style>
@endpush

@push('scripts')
@vite(['resources/js/app.js'])
<script>
document.addEventListener('alpine:init', () => {
    // Auto scroll to bottom of chat on load
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
});
</script>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    showUserInfo: false,
    showSettings: false,
    showActions: false
}">
    <!-- Beautiful LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-8 shadow-2xl shadow-green-500/30 animate-fade-in">
        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 glass-fusion rounded-full -translate-x-48 -translate-y-48 border border-white/20"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 glass-fusion rounded-full translate-x-48 translate-y-48 border border-white/20"></div>
        </div>

        <!-- Floating Chat Bubbles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute top-10 right-10 w-16 h-16 glass-fusion rounded-full animate-pulse border border-white/20"></div>
            <div class="absolute bottom-10 left-10 w-12 h-12 glass-fusion rounded-full animate-pulse border border-white/20" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 right-1/4 w-8 h-8 glass-fusion rounded-full animate-pulse border border-white/20" style="animation-delay: 0.3s"></div>
        </div>

        <div class="relative">
            <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-xl border border-white/30">
                        <i class="fas fa-comments text-white text-3xl drop-shadow-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg">💬 รายละเอียดการสนทนา</h1>
                        <p class="text-white/95 text-lg font-medium">ประวัติการสนทนาและข้อมูลผู้ใช้งาน</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="showActions = !showActions"
                            class="px-6 py-3 glass-fusion backdrop-blur-md border-2 border-white/40 text-white rounded-xl hover:border-white/60 transition-all duration-300 font-bold flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i class="fas fa-ellipsis-v"></i>
                        <span>Actions</span>
                    </button>
                    <a href="{{ route('admin.line-bot.ai.conversations') }}"
                       class="px-6 py-3 glass-fusion backdrop-blur-md border-2 border-white/40 text-white rounded-xl hover:border-white/60 transition-all duration-300 font-bold flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับ</span>
                    </a>
                </div>
            </div>

            <!-- Quick Actions Dropdown -->
            <div x-show="showActions"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 @click.away="showActions = false"
                 class="absolute right-0 top-full mt-2 w-72 glass-fusion backdrop-blur-xl bg-white/95 dark:bg-slate-800/95 rounded-2xl shadow-2xl border border-white/30 overflow-hidden z-50">
                <div class="p-2 space-y-1">
                    <button class="w-full px-4 py-3 text-left hover:bg-blue-50 dark:hover:bg-slate-700 rounded-xl transition-all duration-200 flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <i class="fas fa-download text-blue-600 dark:text-blue-400"></i>
                        <span class="font-semibold">Export Conversation</span>
                    </button>
                    <button class="w-full px-4 py-3 text-left hover:bg-purple-50 dark:hover:bg-slate-700 rounded-xl transition-all duration-200 flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <i class="fas fa-redo text-purple-600 dark:text-purple-400"></i>
                        <span class="font-semibold">Regenerate Response</span>
                    </button>
                    <button class="w-full px-4 py-3 text-left hover:bg-orange-50 dark:hover:bg-slate-700 rounded-xl transition-all duration-200 flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <i class="fas fa-flag text-orange-600 dark:text-orange-400"></i>
                        <span class="font-semibold">Flag for Review</span>
                    </button>
                    <hr class="my-2 border-gray-200 dark:border-slate-700">
                    <button class="w-full px-4 py-3 text-left hover:bg-red-50 dark:hover:bg-slate-700 rounded-xl transition-all duration-200 flex items-center gap-3 text-red-600 dark:text-red-400">
                        <i class="fas fa-trash"></i>
                        <span class="font-semibold">Delete Conversation</span>
                    </button>
                </div>
            </div>

            <!-- Conversation Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 hover:border-white/40 transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-hashtag text-white text-xl"></i>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">ID</p>
                            <p class="text-white text-lg font-bold">#{{ $conversation->id }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 hover:border-white/40 transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-message text-white text-xl"></i>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">ข้อความทั้งหมด</p>
                            <p class="text-white text-lg font-bold">{{ $conversation->messages->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 hover:border-white/40 transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-white text-xl"></i>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">เริ่มเมื่อ</p>
                            <p class="text-white text-sm font-bold">{{ $conversation->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 hover:border-white/40 transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full {{ $conversation->status === 'active' ? 'bg-green-400 animate-pulse' : 'bg-gray-400' }}"></div>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">สถานะ</p>
                            <p class="text-white text-lg font-bold capitalize">{{ $conversation->status }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chat Messages (Main Content) -->
        <div class="lg:col-span-2">
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden animate-fade-in">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-6 py-4 border-b-2 border-gray-200 dark:border-slate-600">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                                @if($conversation->user && $conversation->user->line_profile_image)
                                    <img src="{{ $conversation->user->line_profile_image }}"
                                         alt="{{ $conversation->user->line_display_name }}"
                                         class="w-full h-full rounded-full object-cover">
                                @else
                                    <i class="fas fa-user text-white text-lg"></i>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                    {{ $conversation->user->line_display_name ?? 'Unknown User' }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-robot mr-1"></i>{{ $conversation->aiSetting->name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <span class="px-4 py-2 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-full text-xs font-bold shadow-lg">
                            <i class="fas fa-comments mr-1"></i>{{ $conversation->messages->count() }} ข้อความ
                        </span>
                    </div>
                </div>

                <!-- Chat Messages Container -->
                <div class="chat-container p-6 space-y-6 max-h-[800px] overflow-y-auto bg-gradient-to-b from-gray-50 to-white dark:from-slate-900 dark:to-slate-800">
                    @forelse($conversation->messages->sortBy('created_at') as $message)
                        @if($message->role === 'user')
                            <!-- User Message (Right Side - LINE Style) -->
                            <div class="flex items-end justify-end gap-3 animate-fade-in">
                                <div class="flex flex-col items-end max-w-[75%]">
                                    <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-3xl rounded-br-md px-6 py-4 shadow-lg shadow-green-500/20 transform hover:scale-105 transition-all duration-300">
                                        <p class="text-white text-base leading-relaxed break-words">{{ $message->content }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        <i class="fas fa-clock"></i>
                                        <span>{{ $message->created_at->format('H:i') }}</span>
                                        @if($message->tokens_used)
                                            <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 rounded-full">
                                                <i class="fas fa-coins text-xs"></i> {{ $message->tokens_used }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center flex-shrink-0 shadow-lg">
                                    @if($conversation->user && $conversation->user->line_profile_image)
                                        <img src="{{ $conversation->user->line_profile_image }}"
                                             alt="User"
                                             class="w-full h-full rounded-full object-cover">
                                    @else
                                        <i class="fas fa-user text-white"></i>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- AI Response (Left Side) -->
                            <div class="flex items-end gap-3 animate-fade-in">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                    <i class="fas fa-robot text-white"></i>
                                </div>
                                <div class="flex flex-col max-w-[75%]">
                                    <div class="glass-fusion backdrop-blur-xl bg-white/90 dark:bg-slate-700/90 border-2 border-gray-200 dark:border-slate-600 rounded-3xl rounded-bl-md px-6 py-4 shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                                        <p class="text-gray-900 dark:text-gray-100 text-base leading-relaxed break-words">{{ $message->content }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-robot"></i>
                                        <span>AI Response</span>
                                        <span>•</span>
                                        <i class="fas fa-clock"></i>
                                        <span>{{ $message->created_at->format('H:i') }}</span>
                                        @if($message->tokens_used)
                                            <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full">
                                                <i class="fas fa-coins text-xs"></i> {{ $message->tokens_used }}
                                            </span>
                                        @endif
                                        @if($message->response_time)
                                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                                                <i class="fas fa-tachometer-alt text-xs"></i> {{ number_format($message->response_time, 2) }}s
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <!-- Empty State -->
                        <div class="text-center py-16">
                            <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-3xl flex items-center justify-center mb-6 shadow-xl transform hover:scale-105 transition-all duration-300">
                                <i class="fas fa-comment-slash text-6xl text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-3">ยังไม่มีข้อความ</h3>
                            <p class="text-lg text-gray-600 dark:text-gray-400">การสนทนายังไม่เริ่มต้น</p>
                        </div>
                    @endforelse
                </div>

                <!-- Chat Footer Stats with Hover Effects -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-6 py-4 border-t-2 border-gray-200 dark:border-slate-600">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-3 rounded-xl hover:bg-white/50 dark:hover:bg-slate-600/50 transition-all duration-300 transform hover:scale-105">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">รวม Tokens</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-coins text-yellow-500 mr-1"></i>{{ number_format($conversation->messages->sum('tokens_used')) }}
                            </p>
                        </div>
                        <div class="text-center p-3 rounded-xl hover:bg-white/50 dark:hover:bg-slate-600/50 transition-all duration-300 transform hover:scale-105">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">เวลาเฉลี่ย</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-clock text-blue-500 mr-1"></i>{{ number_format($conversation->messages->where('role', 'assistant')->avg('response_time'), 2) }}s
                            </p>
                        </div>
                        <div class="text-center p-3 rounded-xl hover:bg-white/50 dark:hover:bg-slate-600/50 transition-all duration-300 transform hover:scale-105">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">ระยะเวลา</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-hourglass-half text-purple-500 mr-1"></i>{{ $conversation->created_at->diffForHumans($conversation->updated_at, true) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: User Info & AI Settings -->
        <div class="lg:col-span-1 space-y-6">
            <!-- User Information Card -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden animate-fade-in" style="animation-delay: 0.1s">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user-circle"></i>
                        ข้อมูลผู้ใช้
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($conversation->user)
                        <div class="flex items-center gap-4 pb-4 border-b border-gray-200 dark:border-slate-700">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-xl">
                                @if($conversation->user->line_profile_image)
                                    <img src="{{ $conversation->user->line_profile_image }}"
                                         alt="{{ $conversation->user->line_display_name }}"
                                         class="w-full h-full rounded-full object-cover">
                                @else
                                    <i class="fas fa-user text-white text-2xl"></i>
                                @endif
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ $conversation->user->line_display_name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">LINE User</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-slate-700 dark:to-slate-600 rounded-xl hover:shadow-md transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-id-badge text-blue-600 dark:text-blue-400 text-xl"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold">LINE ID</p>
                                    <p class="font-mono text-sm text-gray-900 dark:text-white truncate">{{ $conversation->user->line_user_id }}</p>
                                </div>
                            </div>

                            @if($conversation->user->email)
                                <div class="flex items-center gap-3 p-3 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-700 dark:to-slate-600 rounded-xl hover:shadow-md transition-all duration-300 transform hover:scale-105">
                                    <i class="fas fa-envelope text-green-600 dark:text-green-400 text-xl"></i>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold">Email</p>
                                        <p class="text-sm text-gray-900 dark:text-white truncate">{{ $conversation->user->email }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center gap-3 p-3 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-slate-700 dark:to-slate-600 rounded-xl hover:shadow-md transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-calendar text-purple-600 dark:text-purple-400 text-xl"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold">สมาชิกเมื่อ</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $conversation->user->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-2xl flex items-center justify-center mb-4">
                                <i class="fas fa-user-slash text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 font-semibold">ไม่พบข้อมูลผู้ใช้</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Settings Card -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden animate-fade-in" style="animation-delay: 0.2s">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-robot"></i>
                        การตั้งค่า AI
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($conversation->aiSetting)
                        <div class="space-y-3">
                            <div class="p-4 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-purple-200 dark:border-purple-800 hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center shadow-lg">
                                        <i class="fas fa-brain text-white text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $conversation->aiSetting->name }}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase">{{ $conversation->aiSetting->provider }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mb-1"><i class="fas fa-microchip mr-1"></i>Model</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $conversation->aiSetting->model }}</p>
                                </div>
                                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                                    <p class="text-xs text-green-600 dark:text-green-400 mb-1"><i class="fas fa-thermometer-half mr-1"></i>Temp</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $conversation->aiSetting->temperature }}</p>
                                </div>
                            </div>

                            <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                                <p class="text-xs text-orange-600 dark:text-orange-400 mb-1"><i class="fas fa-coins mr-1"></i>Max Tokens</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($conversation->aiSetting->max_tokens) }}</p>
                            </div>

                            @if($conversation->aiSetting->enable_conversation_history)
                                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                                    <p class="text-xs text-purple-600 dark:text-purple-400 mb-1"><i class="fas fa-history mr-1"></i>Memory Limit</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $conversation->aiSetting->conversation_memory_limit ?? 10 }} ข้อความ</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-2xl flex items-center justify-center mb-4">
                                <i class="fas fa-robot text-purple-600 dark:text-purple-400 text-3xl"></i>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 font-semibold">ไม่พบการตั้งค่า AI</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Conversation Metadata -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden animate-fade-in" style="animation-delay: 0.3s">
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        ข้อมูลเพิ่มเติม
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-slate-700 dark:to-slate-600 rounded-xl hover:shadow-md transition-all duration-300 transform hover:scale-105">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                            <i class="fas fa-calendar-plus mr-2 text-blue-600 dark:text-blue-400"></i>สร้างเมื่อ
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $conversation->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-700 dark:to-slate-600 rounded-xl hover:shadow-md transition-all duration-300 transform hover:scale-105">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                            <i class="fas fa-calendar-check mr-2 text-green-600 dark:text-green-400"></i>อัพเดทล่าสุด
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $conversation->updated_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-slate-700 dark:to-slate-600 rounded-xl hover:shadow-md transition-all duration-300 transform hover:scale-105">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                            <i class="fas fa-circle mr-2 text-purple-600 dark:text-purple-400"></i>สถานะ
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold shadow-md
                            {{ $conversation->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($conversation->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
