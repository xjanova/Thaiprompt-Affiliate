@extends('layouts.admin-v3')

@section('title', 'จัดการฟีเจอร์แอพ')

@section('content')
<div x-data="{
    deleteId: null,
    toggleFeature(id) {
        fetch(`/admin/app-features/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-600 to-rose-600 dark:from-purple-600 dark:via-pink-700 dark:to-rose-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">จัดการฟีเจอร์แอพ</h1>
                    <p class="text-purple-100 dark:text-pink-200">ควบคุมฟีเจอร์ต่างๆ ของแอพพลิเคชั่น</p>
                </div>
            </div>
            <a href="{{ route('admin.app-features.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold border border-white/30">
                <i class="fas fa-plus mr-2"></i>เพิ่มฟีเจอร์ใหม่
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 p-4 rounded-xl shadow-md">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-green-800 dark:text-green-300 font-semibold">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Features by Category -->
    @forelse($features as $category => $categoryFeatures)
        <div class="mb-6">
            <!-- Category Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 dark:from-indigo-600 dark:to-purple-700 rounded-t-2xl p-4 shadow-lg">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-folder-open mr-3"></i>
                    {{ ucfirst($category) }}
                    <span class="ml-3 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">
                        {{ $categoryFeatures->count() }} ฟีเจอร์
                    </span>
                </h2>
            </div>

            <!-- Features Grid -->
            <div class="bg-white dark:bg-slate-800 rounded-b-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($categoryFeatures as $feature)
                        <div class="p-6 hover:bg-gradient-to-r hover:from-purple-50/50 hover:to-pink-50/50 dark:hover:from-purple-900/10 dark:hover:to-pink-900/10 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <!-- Feature Info -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <!-- Status Badge -->
                                        <label class="relative inline-flex items-center cursor-pointer"
                                               onclick="event.preventDefault();
                                                       if(confirm('ต้องการเปลี่ยนสถานะฟีเจอร์นี้?')) {
                                                           toggleFeature({{ $feature->id }});
                                                       }">
                                            <input type="checkbox" class="sr-only peer" {{ $feature->is_enabled ? 'checked' : '' }}>
                                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-pink-600"></div>
                                        </label>

                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ $feature->feature_name }}
                                        </h3>

                                        <!-- Platform Badge -->
                                        @if($feature->platform)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $feature->platform === 'android' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                                {{ $feature->platform === 'ios' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                                {{ $feature->platform === 'all' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}">
                                                <i class="fas fa-{{ $feature->platform === 'android' ? 'robot' : ($feature->platform === 'ios' ? 'apple' : 'mobile-alt') }} mr-1"></i>
                                                {{ strtoupper($feature->platform) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <span class="flex items-center">
                                            <i class="fas fa-key mr-2 text-purple-500"></i>
                                            <code class="px-2 py-1 bg-gray-100 dark:bg-slate-700 rounded text-xs font-mono">
                                                {{ $feature->feature_key }}
                                            </code>
                                        </span>
                                        @if($feature->min_app_version)
                                            <span class="flex items-center">
                                                <i class="fas fa-code-branch mr-2 text-blue-500"></i>
                                                เวอร์ชั่นขั้นต่ำ: {{ $feature->min_app_version }}
                                            </span>
                                        @endif
                                        <span class="flex items-center">
                                            <i class="fas fa-sort mr-2 text-gray-500"></i>
                                            ลำดับ: {{ $feature->sort_order }}
                                        </span>
                                    </div>

                                    @if($feature->description)
                                        <p class="text-gray-700 dark:text-gray-300 mb-2">
                                            {{ $feature->description }}
                                        </p>
                                    @endif

                                    @if($feature->config)
                                        <div class="mt-2 p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-cog mr-1"></i>Config:
                                            </span>
                                            <pre class="text-xs text-gray-700 dark:text-gray-300 mt-1 overflow-x-auto">{{ json_encode($feature->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 ml-4">
                                    <a href="{{ route('admin.app-features.edit', $feature) }}"
                                       class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </a>
                                    <button @click="deleteId = {{ $feature->id }}"
                                            class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                        <i class="fas fa-trash mr-1"></i>ลบ
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-12 text-center">
            <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-box-open text-4xl text-purple-600 dark:text-purple-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีฟีเจอร์</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้างฟีเจอร์แรกของคุณ</p>
            <a href="{{ route('admin.app-features.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                <i class="fas fa-plus mr-2"></i>เพิ่มฟีเจอร์ใหม่
            </a>
        </div>
    @endforelse

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteId"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="deleteId = null"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบฟีเจอร์นี้?</p>
                <div class="flex gap-3">
                    <button @click="deleteId = null"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-lg transition-all duration-200 font-medium">
                        ยกเลิก
                    </button>
                    <form :action="`/admin/app-features/${deleteId}`" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-lg transition-all duration-200 font-medium shadow-lg">
                            ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function toggleFeature(id) {
    fetch(`/admin/app-features/${id}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}
</script>
@endsection
