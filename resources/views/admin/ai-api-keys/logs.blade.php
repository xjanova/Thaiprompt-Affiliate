@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div x-data="logsView()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            @if($keyId)
                @php($currentKey = \App\Models\AiApiKey::find($keyId))
                <a href="{{ $currentKey ? route('admin.ai-api-keys.provider', $currentKey->provider) : route('admin.ai-api-keys.index') }}"
                   class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
            @else
                <a href="{{ route('admin.ai-api-keys.index') }}"
                   class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Usage Logs
                    @if($keyId && isset($currentKey))
                        <span class="text-base font-normal text-gray-500 dark:text-gray-400">— {{ $currentKey->name }}</span>
                    @endif
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    ประวัติการใช้งาน API Keys
                    @if($logs->total() > 0)
                        ({{ number_format($logs->total()) }} รายการ)
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ $keyId ? route('admin.ai-api-keys.key.logs', $keyId) : route('admin.ai-api-keys.logs') }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @unless($keyId)
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Provider</label>
                <select name="provider"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">ทุก Provider</option>
                    @foreach($providers as $key => $name)
                        <option value="{{ $key }}" {{ request('provider') === $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">สถานะ</label>
                <select name="success"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">ทุกสถานะ</option>
                    <option value="false" {{ request('success') === 'false' ? 'selected' : '' }}>❌ ล้มเหลว เท่านั้น</option>
                    <option value="true" {{ request('success') === 'true' ? 'selected' : '' }}>✅ สำเร็จ เท่านั้น</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    กรอง
                </button>
                <a href="{{ $keyId ? route('admin.ai-api-keys.key.logs', $keyId) : route('admin.ai-api-keys.logs') }}"
                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                        @unless($keyId)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Key</th>
                        @endunless
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tokens</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลาตอบ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Error</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition {{ ! $log->is_success ? 'bg-red-50/30 dark:bg-red-900/10' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        @unless($keyId)
                        <td class="px-4 py-3">
                            @if($log->apiKey)
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->apiKey->name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \App\Models\AiApiKey::PROVIDERS[$log->apiKey->provider] ?? $log->apiKey->provider }}
                                    </span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">— ลบแล้ว —</span>
                            @endif
                        </td>
                        @endunless
                        <td class="px-4 py-3">
                            <span class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $log->request_type ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $log->model ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($log->is_success)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    ✅ สำเร็จ
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    ❌ ล้มเหลว
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="text-sm text-gray-900 dark:text-white font-mono">{{ number_format($log->total_tokens) }}</div>
                            @if($log->input_tokens || $log->output_tokens)
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    in {{ number_format($log->input_tokens) }} / out {{ number_format($log->output_tokens) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($log->response_time_ms !== null)
                                <span class="text-sm text-gray-700 dark:text-gray-300 font-mono">{{ number_format($log->response_time_ms) }} ms</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-md">
                            @if(! empty($log->error_message))
                                {{-- คลิกเพื่อ expand เต็ม (long messages) --}}
                                <button type="button"
                                        @click="showLog({
                                            time: @js($log->created_at->format('Y-m-d H:i:s')),
                                            key: @js($log->apiKey?->name ?? '— ลบแล้ว —'),
                                            error: @js($log->error_message),
                                        })"
                                        class="text-left text-xs text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100 font-mono truncate max-w-xs block underline-offset-2 hover:underline cursor-pointer"
                                        title="คลิกเพื่อดูเต็ม">
                                    {{ \Illuminate\Support\Str::limit($log->error_message, 80) }}
                                </button>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $keyId ? 7 : 8 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p>ยังไม่มี usage logs</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            {{ $logs->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- 🔍 Log Detail Modal — ดูเต็ม error message --}}
    <div x-show="logModal.open"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="logModal.open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="logModal.open = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[85vh] overflow-hidden flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายละเอียด Error</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        <span x-text="logModal.key"></span> · <span x-text="logModal.time"></span>
                    </p>
                </div>
                <button type="button" @click="logModal.open = false"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <pre class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap break-words font-mono"
                     x-text="logModal.error"></pre>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-2">
                <button type="button"
                        @click="copyError()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                    📋 คัดลอก
                </button>
                <button type="button" @click="logModal.open = false"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function logsView() {
    return {
        logModal: {
            open: false,
            time: '',
            key: '',
            error: '',
        },

        showLog(payload) {
            this.logModal = {
                open: true,
                time: payload.time || '',
                key: payload.key || '',
                error: payload.error || '',
            };
        },

        async copyError() {
            try {
                await navigator.clipboard.writeText(this.logModal.error);
                alert('คัดลอกแล้ว');
            } catch (e) {
                alert('คัดลอกไม่สำเร็จ');
            }
        },
    };
}
</script>
@endpush
@endsection
