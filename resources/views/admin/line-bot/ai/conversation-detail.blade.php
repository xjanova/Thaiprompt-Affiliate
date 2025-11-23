@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการสนทนา - LINE AI Bot')

@section('content')
<div class="min-h-screen py-6" x-data="{
    showUserInfo: false,
    showSettings: false
}">
    <!-- Beautiful LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-8 shadow-2xl shadow-green-500/30">
        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 glass-fusion rounded-full -translate-x-48 -translate-y-48 border border-white/20 dark:border-white/10"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 glass-fusion rounded-full translate-x-48 translate-y-48 border border-white/20 dark:border-white/10"></div>
        </div>

        <!-- Floating Chat Bubbles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute top-10 right-10 w-16 h-16 glass-fusion rounded-full animate-pulse border border-white/20 dark:border-white/10"></div>
            <div class="absolute bottom-10 left-10 w-12 h-12 glass-fusion rounded-full animate-pulse border border-white/20 dark:border-white/10" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 right-1/4 w-8 h-8 glass-fusion rounded-full animate-pulse border border-white/20 dark:border-white/10" style="animation-delay: 0.3s"></div>
        </div>

        <div class="relative">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-xl border border-white/30 border border-white/20 dark:border-white/10">
                        <i class="fas fa-comments text-white text-3xl drop-shadow-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg">💬 รายละเอียดการสนทนา</h1>
                        <p class="text-white/95 text-lg font-medium">ประวัติการสนทนาและข้อมูลผู้ใช้งาน</p>
                    </div>
                </div>
                <a href="{{ route('admin.line-bot.ai.conversations') }}"
                   class="px-6 py-3 glass-fusion backdrop-blur-md border-2 border-white/40 text-white rounded-xl hover:glass-fusion transition-all duration-300 font-bold flex items-center gap-2 shadow-lg">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับ</span>
                </a>
            </div>

            <!-- Conversation Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 border border-white/20 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-hashtag text-white text-xl"></i>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">ID</p>
                            <p class="text-white text-lg font-bold">#{{ $conversation->id }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 border border-white/20 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-message text-white text-xl"></i>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">ข้อความทั้งหมด</p>
                            <p class="text-white text-lg font-bold">{{ $conversation->messages->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 border border-white/20 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-white text-xl"></i>
                        <div>
                            <p class="text-white/80 text-xs font-semibold">เริ่มเมื่อ</p>
                            <p class="text-white text-sm font-bold">{{ $conversation->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 border border-white/20 dark:border-white/10">
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
            <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden border border-white/20 dark:border-white/10">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-6 py-4 border-b-2 border-gray-200 dark:border-gray-700 dark:border-slate-600">
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
                <div class="p-6 space-y-6 max-h-[800px] overflow-y-auto bg-gradient-to-b from-gray-50 to-white dark:from-slate-900 dark:to-slate-800">
                    @forelse($conversation->messages->sortBy('created_at') as $message)
                        @if($message->role === 'user')
                            <!-- User Message (Right Side - LINE Style) -->
                            <div class="flex items-end justify-end gap-3 animate-fade-in">
                                <div class="flex flex-col items-end max-w-[75%]">
                                    <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-3xl rounded-br-md px-6 py-4 shadow-lg shadow-green-500/20 transform hover:scale-105 transition-all duration-300">
                                        <p class="text-white text-base leading-relaxed break-words">{{ $message->content }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
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
                                    <div class="glass-fusion dark:bg-slate-700 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 rounded-3xl rounded-bl-md px-6 py-4 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 border border-white/20 dark:border-white/10">
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
                            <div class="w-24 h-24 rounded-full bg-gray-100/50 dark:bg-slate-700 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comment-slash text-gray-400 dark:text-gray-600 dark:text-gray-400 text-4xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีข้อความ</h3>
                            <p class="text-gray-600 dark:text-gray-400">การสนทนายังไม่เริ่มต้น</p>
                        </div>
                    @endforelse
                </div>

                <!-- Chat Footer Stats -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-6 py-4 border-t-2 border-gray-200 dark:border-gray-700 dark:border-slate-600">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">รวม Tokens</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-coins text-yellow-500 mr-1"></i>{{ number_format($conversation->messages->sum('tokens_used')) }}
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">เวลาเฉลี่ย</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-clock text-blue-500 mr-1"></i>{{ number_format($conversation->messages->where('role', 'assistant')->avg('response_time'), 2) }}s
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ระยะเวลา</p>
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
            <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden border border-white/20 dark:border-white/10">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user-circle"></i>
                        ข้อมูลผู้ใช้
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($conversation->user)
                        <div class="flex items-center gap-4 pb-4 border-b border-gray-200 dark:border-gray-700 dark:border-slate-700">
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
                                <p class="text-sm text-gray-500 dark:text-gray-400">LINE User</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-100/50 dark:bg-slate-700 rounded-xl">
                                <i class="fas fa-id-badge text-blue-600 dark:text-blue-400"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">LINE ID</p>
                                    <p class="font-mono text-sm text-gray-900 dark:text-white truncate">{{ $conversation->user->line_user_id }}</p>
                                </div>
                            </div>

                            @if($conversation->user->email)
                                <div class="flex items-center gap-3 p-3 bg-gray-100/50 dark:bg-slate-700 rounded-xl">
                                    <i class="fas fa-envelope text-green-600 dark:text-green-400"></i>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Email</p>
                                        <p class="text-sm text-gray-900 dark:text-white truncate">{{ $conversation->user->email }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center gap-3 p-3 bg-gray-100/50 dark:bg-slate-700 rounded-xl">
                                <i class="fas fa-calendar text-purple-600 dark:text-purple-400"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">สมาชิกเมื่อ</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $conversation->user->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-user-slash text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-600 dark:text-gray-400">ไม่พบข้อมูลผู้ใช้</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Settings Card -->
            <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden border border-white/20 dark:border-white/10">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-robot"></i>
                        การตั้งค่า AI
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($conversation->aiSetting)
                        <div class="space-y-3">
                            <div class="p-4 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-purple-200 dark:border-purple-800">
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
                            <i class="fas fa-robot text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-600 dark:text-gray-400">ไม่พบการตั้งค่า AI</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Conversation Metadata -->
            <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden border border-white/20 dark:border-white/10">
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        ข้อมูลเพิ่มเติม
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-100/50 dark:bg-slate-700 rounded-xl">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-calendar-plus mr-2"></i>สร้างเมื่อ
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $conversation->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-100/50 dark:bg-slate-700 rounded-xl">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-calendar-check mr-2"></i>อัพเดทล่าสุด
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $conversation->updated_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-100/50 dark:bg-slate-700 rounded-xl">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-circle mr-2"></i>สถานะ
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            {{ $conversation->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($conversation->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
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
    animation: fade-in 0.3s ease-out;
}
</style>
@endsection
