@extends('layouts.admin')

@section('title', $pageTitle ?? 'รายละเอียดโปรเจกต์')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $project->name }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $project->status_color }}">
                    {{ $project->status_label }}
                </span>
                <span class="ml-2">{{ $project->content_type_label }}</span>
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.ai-content-writer.projects') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Project Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลโปรเจกต์</h3>

                <div class="space-y-4">
                    @if($project->description)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">คำอธิบาย</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $project->description }}</p>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">เทมเพลต</label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ $project->template?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">AI Provider</label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ ucfirst($project->ai_provider ?? '-') }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">สร้างเมื่อ</label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ $project->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">อัปเดตล่าสุด</label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ $project->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Generations List --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">การสร้าง Content ({{ $generations->total() }})</h3>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($generations as $generation)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 text-xs rounded-full {{ $generation->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $generation->status === 'completed' ? 'สำเร็จ' : 'ล้มเหลว' }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $generation->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-coins mr-1"></i> ${{ number_format($generation->cost_usd ?? 0, 4) }}
                            </div>
                        </div>

                        @if($generation->content)
                        <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line line-clamp-4">{{ $generation->content }}</p>
                        </div>
                        @endif

                        <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>
                                <i class="fas fa-robot mr-1"></i>
                                {{ $generation->model ?? '-' }}
                            </span>
                            <span>
                                <i class="fas fa-chart-bar mr-1"></i>
                                {{ number_format($generation->tokens_used ?? 0) }} tokens
                            </span>
                            <a href="{{ route('admin.ai-content-writer.generations.show', $generation->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                ดูรายละเอียด <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-feather-alt text-4xl mb-4"></i>
                        <p>ยังไม่มีการสร้าง Content</p>
                    </div>
                    @endforelse
                </div>

                @if($generations->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $generations->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">สถิติ</h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">การสร้างทั้งหมด</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_generations'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สำเร็จ</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($stats['completed'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">ล้มเหลว</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($stats['failed'] ?? 0) }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Tokens ใช้ไป</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_tokens'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">ค่าใช้จ่ายรวม</span>
                        <span class="font-semibold text-purple-600 dark:text-purple-400">${{ number_format($stats['total_cost'] ?? 0, 4) }}</span>
                    </div>
                </div>
            </div>

            {{-- Settings Used --}}
            @if($project->settings)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่าที่ใช้</h3>

                <div class="space-y-3 text-sm">
                    @if(isset($project->settings['temperature']))
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Temperature</span>
                        <span class="text-gray-900 dark:text-white">{{ $project->settings['temperature'] }}</span>
                    </div>
                    @endif
                    @if(isset($project->settings['max_tokens']))
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Max Tokens</span>
                        <span class="text-gray-900 dark:text-white">{{ number_format($project->settings['max_tokens']) }}</span>
                    </div>
                    @endif
                    @if(isset($project->settings['tone']))
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Tone</span>
                        <span class="text-gray-900 dark:text-white">{{ $project->settings['tone'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">จัดการ</h3>

                <div class="space-y-3">
                    <button onclick="updateStatus('{{ $project->id }}', 'completed')"
                            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition {{ $project->status === 'completed' ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $project->status === 'completed' ? 'disabled' : '' }}>
                        <i class="fas fa-check mr-2"></i> ทำเครื่องหมายเสร็จสิ้น
                    </button>
                    <button onclick="deleteProject('{{ $project->id }}')"
                            class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        <i class="fas fa-trash mr-2"></i> ลบโปรเจกต์
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function updateStatus(id, status) {
    if (!confirm('ต้องการเปลี่ยนสถานะโปรเจกต์?')) return;

    try {
        const response = await fetch(`{{ url('admin/ai-content-writer/projects') }}/${id}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: status })
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด');
    }
}

async function deleteProject(id) {
    if (!confirm('ต้องการลบโปรเจกต์นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;

    try {
        const response = await fetch(`{{ url('admin/ai-content-writer/projects') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '{{ route("admin.ai-content-writer.projects") }}';
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
