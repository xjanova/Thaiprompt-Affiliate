{{--
    หน้าแสดงรายละเอียดการสนทนา
    แสดงว่าบอทคุยอะไรกับผู้สมัคร แบบ Chat Interface
--}}
@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการสนทนา - LINE Recruitment')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.line-recruitment.conversations') }}"
                       class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr($conversation->line_user_id, -2)) }}
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ Str::limit($conversation->line_user_id, 25) }}
                            </h1>
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                @if($conversation->status === 'active')
                                    <span class="flex items-center text-green-600 dark:text-green-400">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="text-gray-500">{{ ucfirst($conversation->status) }}</span>
                                @endif
                                <span>|</span>
                                <span>{{ $conversation->message_count }} ข้อความ</span>
                                <span>|</span>
                                <span>{{ $conversation->aiSetting->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('admin.line-recruitment.conversations.delete', $conversation->id) }}"
                          method="POST" class="inline"
                          onsubmit="return confirm('ยืนยันการลบการสนทนานี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Chat Messages --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Conversation Info --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Session ID</p>
                        <p class="font-mono text-gray-900 dark:text-white">{{ Str::limit($conversation->session_id, 12) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">เริ่มต้น</p>
                        <p class="text-gray-900 dark:text-white">{{ $conversation->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">ข้อความล่าสุด</p>
                        <p class="text-gray-900 dark:text-white">{{ $conversation->last_message_at?->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">AI Provider</p>
                        <p class="text-gray-900 dark:text-white">{{ $conversation->aiSetting->provider ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto" id="messagesContainer">
                @forelse($conversation->messages as $message)
                    @if($message->role === 'user')
                        {{-- User Message --}}
                        <div class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="bg-green-500 text-white rounded-2xl rounded-tr-md px-4 py-3">
                                    <p class="whitespace-pre-wrap">{{ $message->message }}</p>
                                </div>
                                <div class="flex items-center justify-end gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $message->created_at->format('H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- AI/Bot Message --}}
                        <div class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="flex items-start gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-xs flex-shrink-0">
                                        AI
                                    </div>
                                    <div>
                                        <div class="bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-2xl rounded-tl-md px-4 py-3">
                                            <p class="whitespace-pre-wrap">{{ $message->message }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $message->created_at->format('H:i') }}</span>
                                            @if($message->metadata)
                                                @if(isset($message->metadata['tokens_used']))
                                                    <span>| {{ $message->metadata['tokens_used'] }} tokens</span>
                                                @endif
                                                @if(isset($message->metadata['filtered']) && $message->metadata['filtered'])
                                                    <span class="text-orange-500">| Filtered</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มีข้อความในการสนทนานี้</p>
                    </div>
                @endforelse
            </div>

            {{-- Context Info (if available) --}}
            @if($conversation->context)
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Context Data</h3>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded-lg overflow-x-auto text-gray-600 dark:text-gray-400">{{ json_encode($conversation->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>

        {{-- AI Settings Info --}}
        @if($conversation->aiSetting)
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">AI Settings ที่ใช้</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อ</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->aiSetting->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Provider</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->aiSetting->provider }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Model</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->aiSetting->model }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Temperature</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->aiSetting->temperature }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Max Tokens</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->aiSetting->max_tokens }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Response Style</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->aiSetting->response_style ?? 'friendly' }}</p>
                    </div>
                </div>
                @if($conversation->aiSetting->system_prompt)
                    <div class="mt-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">System Prompt</p>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg text-sm text-gray-600 dark:text-gray-400">
                            {{ Str::limit($conversation->aiSetting->system_prompt, 300) }}
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Scroll to bottom of messages on load
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endpush
@endsection
