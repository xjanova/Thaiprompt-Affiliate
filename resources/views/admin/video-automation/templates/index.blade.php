@extends('layouts.admin-v3')

@section('title', 'จัดการ Templates - Video Automation')

@section('styles')
<style>
    /* การ์ด Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ปุ่ม Gradient */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    /* Template Card */
    .template-card {
        transition: all 0.3s ease;
    }
    .template-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    .dark .template-card:hover {
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    /* Genre Badge Colors */
    .genre-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .genre-pop { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); color: white; }
    .genre-rock { background: linear-gradient(135deg, #4a4a4a, #6a6a6a); color: white; }
    .genre-jazz { background: linear-gradient(135deg, #f4a261, #e76f51); color: white; }
    .genre-classical { background: linear-gradient(135deg, #8ecae6, #219ebc); color: white; }
    .genre-electronic { background: linear-gradient(135deg, #7209b7, #b5179e); color: white; }
    .genre-hiphop { background: linear-gradient(135deg, #fb8500, #ffb703); color: #333; }
    .genre-country { background: linear-gradient(135deg, #606c38, #9b9b4f); color: white; }
    .genre-rnb { background: linear-gradient(135deg, #7b2cbf, #9d4edd); color: white; }
    .genre-lofi { background: linear-gradient(135deg, #a8dadc, #457b9d); color: white; }
    .genre-ambient { background: linear-gradient(135deg, #2a9d8f, #40c9a2); color: white; }
    .genre-thai { background: linear-gradient(135deg, #d4a373, #bc6c25); color: white; }
    .genre-default { background: linear-gradient(135deg, #94a3b8, #64748b); color: white; }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    จัดการ Templates
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                รูปแบบสำเร็จรูปสำหรับสร้างวิดีโออัตโนมัติ
            </p>
        </div>
        <a href="{{ route('admin.video-automation.templates.create') }}"
           class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้าง Template ใหม่
        </a>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-2xl p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ชื่อ template..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            {{-- Genre Filter --}}
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แนวเพลง</label>
                <select name="genre"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    @foreach(\App\Models\VideoAutoTemplate::GENRES as $genre => $label)
                        <option value="{{ $genre }}" {{ request('genre') == $genre ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <a href="{{ route('admin.video-automation.templates.index') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Templates Grid --}}
    @if($templates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($templates as $template)
                <div class="template-card glass-card rounded-2xl overflow-hidden">
                    {{-- Preview Area --}}
                    <div class="relative h-40 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800">
                        @if($template->preview_image)
                            <img src="{{ Storage::url($template->preview_image) }}"
                                 alt="{{ $template->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Status Badge --}}
                        <div class="absolute top-3 right-3">
                            @if($template->is_active)
                                <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full">ใช้งาน</span>
                            @else
                                <span class="px-2 py-1 bg-gray-500 text-white text-xs rounded-full">ปิดใช้งาน</span>
                            @endif
                        </div>

                        {{-- Default Badge --}}
                        @if($template->is_default)
                            <div class="absolute top-3 left-3">
                                <span class="px-2 py-1 bg-yellow-500 text-white text-xs rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    ค่าเริ่มต้น
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                            {{ $template->name }}
                        </h3>

                        @if($template->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                {{ $template->description }}
                            </p>
                        @endif

                        {{-- Tags --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            @php
                                $genreClass = 'genre-' . ($template->music_genre ?? 'default');
                            @endphp
                            <span class="genre-badge {{ $genreClass }}">
                                {{ \App\Models\VideoAutoTemplate::GENRES[$template->music_genre] ?? $template->music_genre }}
                            </span>

                            @if($template->image_style)
                                <span class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 text-xs rounded-full">
                                    {{ \App\Models\VideoAutoTemplate::IMAGE_STYLES[$template->image_style] ?? $template->image_style }}
                                </span>
                            @endif
                        </div>

                        {{-- Stats --}}
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                {{ $template->projects_count ?? 0 }} โปรเจกต์
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $template->video_settings['duration'] ?? 60 }}s
                            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.video-automation.templates.edit', $template) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                   title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button type="button"
                                        onclick="duplicateTemplate({{ $template->id }})"
                                        class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition"
                                        title="ทำสำเนา">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.video-automation.templates.destroy', $template) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('ต้องการลบ template นี้หรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition"
                                            title="ลบ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <a href="{{ route('admin.video-automation.projects.create', ['template' => $template->id]) }}"
                               class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition">
                                ใช้ Template
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $templates->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ยังไม่มี Template
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                สร้าง Template แรกของคุณเพื่อเริ่มใช้งานระบบ Video Automation
            </p>
            <a href="{{ route('admin.video-automation.templates.create') }}"
               class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้าง Template แรก
            </a>
        </div>
    @endif
</div>

{{-- Duplicate Form (Hidden) --}}
<form id="duplicateForm" action="" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@section('scripts')
<script>
function duplicateTemplate(templateId) {
    if (confirm('ต้องการทำสำเนา template นี้หรือไม่?')) {
        const form = document.getElementById('duplicateForm');
        form.action = `{{ url('/admin/video-automation/templates') }}/${templateId}/duplicate`;
        form.submit();
    }
}
</script>
@endsection
