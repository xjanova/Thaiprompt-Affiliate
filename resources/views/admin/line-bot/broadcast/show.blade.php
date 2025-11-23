@extends('layouts.admin-v3')

@section('title', 'Broadcast Details')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.broadcast.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $broadcast->name }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Broadcast Details</p>
            </div>
            <div>
                <span class="px-4 py-2 rounded-xl text-sm font-bold
                    @if($broadcast->status === 'completed') bg-green-100 text-green-800
                    @elseif($broadcast->status === 'sending') bg-blue-100 text-blue-800 animate-pulse
                    @elseif($broadcast->status === 'scheduled') bg-yellow-100 text-yellow-800
                    @else bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white
                    @endif">
                    {{ strtoupper($broadcast->status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Statistics -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-users text-3xl opacity-80"></i>
                    </div>
                    <p class="text-sm opacity-90">Target Users</p>
                    <p class="text-3xl font-bold">{{ number_format($broadcast->total_recipients ?? 0) }}</p>
                </div>

                <div style="background: var(--arrow-x-success-gradient)" class="rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-check-circle text-3xl opacity-80"></i>
                    </div>
                    <p class="text-sm opacity-90">Sent</p>
                    <p class="text-3xl font-bold">{{ number_format($broadcast->sent_count) }}</p>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-exclamation-circle text-3xl opacity-80"></i>
                    </div>
                    <p class="text-sm opacity-90">Failed</p>
                    <p class="text-3xl font-bold">{{ number_format($broadcast->failed_count) }}</p>
                </div>
            </div>

            <!-- Message Content -->
            <div class="glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 mb-4">Message Content</h3>
                <div class="p-4 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ $broadcast->message }}</p>
                </div>
            </div>

            <!-- Details -->
            <div class="glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 mb-4">Broadcast Details</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Target Type:</span>
                        <span class="font-semibold text-gray-900">{{ ucfirst($broadcast->target_type) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Message Type:</span>
                        <span class="font-semibold text-gray-900">{{ ucfirst($broadcast->message_type) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Created:</span>
                        <span class="font-semibold text-gray-900">{{ $broadcast->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($broadcast->scheduled_at)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Scheduled For:</span>
                            <span class="font-semibold text-gray-900">{{ $broadcast->scheduled_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                    @if($broadcast->sent_at)
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Sent At:</span>
                            <span class="font-semibold text-gray-900">{{ $broadcast->sent_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Actions -->
            @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled')
                <div class="glass-fusion rounded-2xl shadow-lg p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <form method="POST" action="{{ route('admin.line-bot.broadcast.send', $broadcast->id) }}" class="w-full">
                            @csrf
                            <button type="submit" onclick="return confirm('Send this broadcast now?')"
                                    class="w-full px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-700 hover:to-teal-700 transition shadow-lg font-semibold">
                                <i class="fas fa-paper-plane mr-2"></i>Send Now
                            </button>
                        </form>

                        <a href="{{ route('admin.line-bot.broadcast.edit', $broadcast->id) }}"
                           class="block w-full px-4 py-3 bg-blue-100 text-blue-700 rounded-xl hover:bg-blue-200 transition text-center font-semibold">
                            <i class="fas fa-edit mr-2"></i>Edit Broadcast
                        </a>

                        <form method="POST" action="{{ route('admin.line-bot.broadcast.destroy', $broadcast->id) }}"
                              onsubmit="return confirm('Delete this broadcast?')" class="w-full">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-red-100 text-red-700 rounded-xl hover:bg-red-200 transition font-semibold">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Progress -->
            @if($broadcast->status === 'sending' || $broadcast->status === 'completed')
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-200 p-6">
                    <h3 class="font-bold text-emerald-900 mb-4">Progress</h3>
                    @if($broadcast->total_recipients > 0)
                        @php
                            $progress = ($broadcast->sent_count / $broadcast->total_recipients) * 100;
                        @endphp
                        <div class="mb-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-emerald-800">{{ number_format($progress, 1) }}%</span>
                                <span class="text-emerald-800">{{ $broadcast->sent_count }} / {{ $broadcast->total_recipients }}</span>
                            </div>
                            <div class="w-full bg-emerald-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 h-3 rounded-full transition-all duration-500"
                                     style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    @endif

                    @if($broadcast->status === 'sending')
                        <p class="text-sm text-emerald-800 mt-3">
                            <i class="fas fa-sync-alt fa-spin mr-2"></i>Broadcast in progress...
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
