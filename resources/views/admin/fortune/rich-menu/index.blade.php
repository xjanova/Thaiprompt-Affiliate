@extends('layouts.admin-v3')

@section('title', 'Rich Menu - แม่หมอจันทรา')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="richMenuDeploy()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🔮 Rich Menu - แม่หมอจันทรา
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">สร้างและ deploy Rich Menu สำหรับบอทดูดวง LINE</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.fortune.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>📊</span> Dashboard
            </a>
            <a href="{{ route('admin.fortune.channels.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>📡</span> ช่องทาง
            </a>
        </div>
    </div>

    {{-- สถานะปัจจุบัน --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            📌 สถานะ Rich Menu ปัจจุบัน
        </h2>

        @if($activeMenu)
            <div class="flex flex-col md:flex-row gap-6">
                {{-- ข้อมูล --}}
                <div class="flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <p class="text-xs text-green-600 dark:text-green-400 font-semibold uppercase">สถานะ</p>
                            <p class="text-lg font-bold text-green-700 dark:text-green-300 mt-1">Active</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                            <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold uppercase">Rich Menu ID</p>
                            <p class="text-sm font-mono text-purple-700 dark:text-purple-300 mt-1 truncate" title="{{ $activeMenu->rich_menu_id }}">{{ $activeMenu->rich_menu_id }}</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase">Deploy เมื่อ</p>
                            <p class="text-sm font-bold text-blue-700 dark:text-blue-300 mt-1">{{ $activeMenu->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase">Deploy โดย</p>
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-1">{{ $activeMenu->creator->name ?? 'System' }}</p>
                        </div>
                    </div>
                </div>

                {{-- ภาพ Rich Menu --}}
                @if($activeMenu->image_path)
                <div class="w-full md:w-80 shrink-0">
                    <img src="{{ asset('storage/'.$activeMenu->image_path) }}"
                         alt="Rich Menu"
                         class="w-full rounded-lg shadow border border-gray-200 dark:border-gray-600">
                </div>
                @endif
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-4xl mb-3">🔮</div>
                <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มี Rich Menu ที่ active</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">กด "Deploy" เพื่อสร้างและ deploy Rich Menu ใหม่</p>
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            🚀 จัดการ Rich Menu
        </h2>

        <div class="flex flex-col sm:flex-row gap-4">
            {{-- Preview --}}
            <button @click="previewRichMenu()"
                    :disabled="loading"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-300 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl">
                <template x-if="previewLoading">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span>🖼️ Preview ภาพ</span>
            </button>

            {{-- Deploy --}}
            <button @click="deployRichMenu()"
                    :disabled="loading"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl">
                <template x-if="deployLoading">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span>🚀 Deploy Rich Menu</span>
            </button>

            {{-- Re-set Default (ถ้า Rich Menu ไม่ขึ้น) --}}
            @if($activeMenu)
            <button @click="reSetDefault()"
                    :disabled="loading"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl">
                <template x-if="reSetLoading">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span>🔄 Re-set Default</span>
            </button>
            @endif
        </div>

        {{-- คำอธิบายปุ่ม --}}
        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 space-y-1">
            <p>🖼️ <strong>Preview</strong> — สร้างภาพดูตัวอย่าง (ไม่ deploy)</p>
            <p>🚀 <strong>Deploy</strong> — สร้างใหม่ทั้งหมดแล้ว deploy ไป LINE</p>
            <p>🔄 <strong>Re-set Default</strong> — ใช้เมื่อ Rich Menu ไม่ขึ้น ส่งคำสั่ง set default อีกครั้ง (ไม่สร้างภาพใหม่)</p>
        </div>

        {{-- Preview Image --}}
        <div x-show="previewUrl" x-cloak class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">ตัวอย่างภาพ Rich Menu</h3>
            <img :src="previewUrl" alt="Preview Rich Menu" class="w-full max-w-2xl rounded-lg shadow border border-gray-200 dark:border-gray-600">
        </div>

        {{-- Result Messages --}}
        <div x-show="successMessage" x-cloak
             class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-green-700 dark:text-green-300 font-semibold" x-text="successMessage"></p>
        </div>

        <div x-show="errorMessage" x-cloak
             class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-700 dark:text-red-300 font-semibold" x-text="errorMessage"></p>
        </div>
    </div>

    {{-- ประวัติ Deploy --}}
    @if($deployHistory->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            📋 ประวัติ Deploy
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rich Menu ID</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">วันที่ Deploy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($deployHistory as $menu)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 truncate max-w-[200px]" title="{{ $menu->rich_menu_id }}">
                            {{ $menu->rich_menu_id }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($menu->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $menu->created_at->format('d/m/Y H:i') }}
                            <span class="text-xs text-gray-400 dark:text-gray-500">({{ $menu->created_at->diffForHumans() }})</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function richMenuDeploy() {
    return {
        loading: false,
        previewLoading: false,
        deployLoading: false,
        reSetLoading: false,
        previewUrl: null,
        successMessage: null,
        errorMessage: null,

        async previewRichMenu() {
            this.previewLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.preview") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.previewUrl = data.preview_url;
                    this.successMessage = 'สร้างภาพ Preview สำเร็จ!';
                } else {
                    this.errorMessage = data.error || 'ไม่สามารถสร้าง Preview ได้';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.previewLoading = false;
                this.loading = false;
            }
        },

        async reSetDefault() {
            this.reSetLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.re-set-default") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.successMessage = data.message || 'Re-set default สำเร็จ!';
                } else {
                    this.errorMessage = data.error || 'Re-set default ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.reSetLoading = false;
                this.loading = false;
            }
        },

        async deployRichMenu() {
            if (!confirm('ยืนยันการ Deploy Rich Menu?\n\nRich Menu เดิมจะถูกแทนที่ด้วย Rich Menu ใหม่')) {
                return;
            }

            this.deployLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.successMessage = 'Deploy สำเร็จ! Rich Menu ID: ' + data.rich_menu_id;
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    this.errorMessage = data.error || 'Deploy ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.deployLoading = false;
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
