@extends('layouts.admin-v3')

@section('title', 'Broadcast Messages')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Broadcast Messages</h1>
                <p class="text-emerald-100">Send messages to multiple users at once</p>
            </div>
            <a href="{{ route('admin.line-bot.broadcast.create') }}"
               class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:glass-fusion transition">
                <i class="fas fa-bullhorn mr-2"></i>New Broadcast
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
            <p class="text-green-800"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif

    <div class="space-y-4">
        @forelse($broadcasts as $broadcast)
            <div class="glass-fusion rounded-2xl shadow-lg border p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-gray-900">{{ $broadcast->name }}</h3>
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                @if($broadcast->status === 'completed') bg-green-100 text-green-800
                                @elseif($broadcast->status === 'sending') bg-blue-100 text-blue-800
                                @elseif($broadcast->status === 'scheduled') bg-yellow-100 text-yellow-800
                                @else bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white
                                @endif">
                                {{ strtoupper($broadcast->status) }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ Str::limit($broadcast->message, 150) }}</p>

                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-users mr-1"></i>Target: {{ ucfirst($broadcast->target_type) }}</span>
                            @if($broadcast->scheduled_at)
                                <span><i class="fas fa-clock mr-1"></i>{{ $broadcast->scheduled_at->format('M d, Y H:i') }}</span>
                            @endif
                            @if($broadcast->sent_count > 0)
                                <span><i class="fas fa-check mr-1"></i>Sent: {{ number_format($broadcast->sent_count) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2 ml-4">
                        @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled')
                            <form method="POST" action="{{ route('admin.line-bot.broadcast.send', $broadcast->id) }}" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Send this broadcast now?')"
                                        class="px-4 py-2 bg-green-100 text-green-700 rounded-xl hover:bg-green-200 transition text-sm font-semibold">
                                    <i class="fas fa-paper-plane mr-1"></i>Send
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.line-bot.broadcast.show', $broadcast->id) }}"
                           class="px-4 py-2 bg-blue-100 text-blue-700 rounded-xl hover:bg-blue-200 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-1"></i>View
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-fusion rounded-2xl shadow-lg p-12 text-center" border border-white/20 dark:border-white/10>
                <div class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-bullhorn text-emerald-500 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">No Broadcasts Yet</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first broadcast message</p>
                <a href="{{ route('admin.line-bot.broadcast.create') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl">
                    <i class="fas fa-bullhorn mr-2"></i>Create Broadcast
                </a>
            </div>
        @endforelse
    </div>

    @if($broadcasts->hasPages())
        <div class="mt-8">{{ $broadcasts->links() }}</div>
    @endif
</div>
@endsection
