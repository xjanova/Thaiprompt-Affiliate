@extends('layouts.admin')

@section('title', $pageTitle ?? 'รายละเอียดการสร้าง')

@section('content')
<div class="space-y-6" x-data="generationDetail()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดการสร้าง Content</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $generation->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $generation->status === 'completed' ? 'สำเร็จ' : 'ล้มเหลว' }}
                </span>
                <span class="ml-2">{{ $generation->created_at->format('d/m/Y H:i:s') }}</span>
            </p>
        </div>
        <a href="{{ route('admin.ai-content-writer.generations') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> กลับ
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Generated Content --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Content ที่สร้าง</h3>
                    @if($generation->content)
                    <button @click="copyContent" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-copy mr-1"></i> คัดลอก
                    </button>
                    @endif
                </div>
                <div class="p-4">
                    @if($generation->content)
                    <div class="prose dark:prose-invert max-w-none">
                        <pre class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg overflow-auto max-h-[500px]" id="generatedContent">{{ $generation->content }}</pre>
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-exclamation-triangle text-4xl mb-3 text-yellow-500"></i>
                        <p>ไม่มี Content (การสร้างอาจล้มเหลว)</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Input Prompts --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Prompts ที่ใช้</h3>
                </div>
                <div class="p-4 space-y-4">
                    @if($generation->system_prompt)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">System Prompt</label>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ $generation->system_prompt }}</p>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">User Prompt</label>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ $generation->user_prompt }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Error Details (if failed) --}}
            @if($generation->status === 'failed' && $generation->error_message)
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                <div class="p-4 border-b border-red-200 dark:border-red-800">
                    <h3 class="font-semibold text-red-700 dark:text-red-400">ข้อผิดพลาด</h3>
                </div>
                <div class="p-4">
                    <pre class="text-sm text-red-700 dark:text-red-400 whitespace-pre-wrap">{{ $generation->error_message }}</pre>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- AI Provider Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล AI</h3>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="p-2 rounded-lg {{ $generation->ai_provider === 'openai' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : ($generation->ai_provider === 'claude' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400') }}">
                            <i class="fas {{ $generation->ai_provider === 'openai' ? 'fa-robot' : ($generation->ai_provider === 'claude' ? 'fa-brain' : 'fa-gem') }} fa-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($generation->ai_provider) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $generation->model }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Temperature</span>
                            <span class="text-gray-900 dark:text-white">{{ $generation->temperature ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Max Tokens</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format($generation->max_tokens ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Usage Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">สถิติการใช้งาน</h3>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ number_format($generation->prompt_tokens ?? 0) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Prompt Tokens</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($generation->completion_tokens ?? 0) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Completion Tokens</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Tokens รวม</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($generation->tokens_used ?? 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ค่าใช้จ่าย</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">${{ number_format($generation->cost_usd ?? 0, 6) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">เวลาตอบกลับ</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format(($generation->response_time_ms ?? 0) / 1000, 2) }}s</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Related Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลที่เกี่ยวข้อง</h3>

                <div class="space-y-3 text-sm">
                    @if($generation->project)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">โปรเจกต์</span>
                        <a href="{{ route('admin.ai-content-writer.projects.show', $generation->project_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                            {{ Str::limit($generation->project->name, 20) }}
                        </a>
                    </div>
                    @endif
                    @if($generation->template)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">เทมเพลต</span>
                        <a href="{{ route('admin.ai-content-writer.templates.edit', $generation->template_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                            {{ Str::limit($generation->template->name, 20) }}
                        </a>
                    </div>
                    @endif
                    @if($generation->user)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สร้างโดย</span>
                        <span class="text-gray-900 dark:text-white">{{ $generation->user->name ?? '-' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="space-y-3">
                @if($generation->content)
                <a href="{{ route('admin.ai-content-writer.playground') }}?regenerate={{ $generation->id }}"
                   class="block w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-center rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> สร้างใหม่ใน Playground
                </a>
                @endif
                <button onclick="deleteGeneration({{ $generation->id }})"
                        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <i class="fas fa-trash mr-2"></i> ลบรายการนี้
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generationDetail() {
    return {
        copyContent() {
            const content = document.getElementById('generatedContent').textContent;
            navigator.clipboard.writeText(content);
            alert('คัดลอกแล้ว!');
        }
    }
}

async function deleteGeneration(id) {
    if (!confirm('ต้องการลบรายการนี้?')) return;

    try {
        const response = await fetch(`{{ url('admin/ai-content-writer/generations') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '{{ route("admin.ai-content-writer.generations") }}';
        } else {
            alert(result.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการลบ');
    }
}
</script>
@endpush
@endsection
