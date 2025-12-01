@extends('layouts.admin-v3')

@section('title', 'Cloudflare Cache Management')

@section('content')
<div class="container-fluid px-4 py-6" x-data="cacheManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-red-500 via-pink-600 to-rose-500 dark:from-red-600 dark:via-pink-700 dark:to-rose-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('admin.cloudflare.index') }}" class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">Cache Management</h1>
                        <p class="text-pink-100">จัดการ Cloudflare Cache - Purge, Clear, Optimize</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$isConfigured)
    <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-xl">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-yellow-800 dark:text-yellow-200">ยังไม่ได้ตั้งค่า Cloudflare API</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Purge Everything -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Purge Everything</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ล้าง cache ทั้งหมดของ Zone</p>
                </div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-4 mb-4">
                <p class="text-sm text-red-800 dark:text-red-200">
                    <strong>คำเตือน:</strong> การ purge cache ทั้งหมดจะทำให้ทุก request ต้องไปที่ origin server จนกว่าจะ cache ใหม่ อาจส่งผลต่อ performance ชั่วคราว
                </p>
            </div>
            <button @click="purgeAll()"
                    :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                    class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Purge Cache ทั้งหมด</span>
                <span x-show="loading" class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    กำลังดำเนินการ...
                </span>
            </button>
        </div>

        <!-- Purge by URLs -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Purge by URLs</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ล้าง cache เฉพาะ URLs ที่ระบุ</p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URLs (หนึ่ง URL ต่อบรรทัด)</label>
                <textarea x-model="urls"
                          rows="5"
                          class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="https://example.com/page1&#10;https://example.com/page2"></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">สูงสุด 30 URLs ต่อครั้ง</p>
            </div>
            <button @click="purgeUrls()"
                    :disabled="loading || !urls.trim() || !{{ $isConfigured ? 'true' : 'false' }}"
                    class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                Purge URLs
            </button>
        </div>

        <!-- Purge by Prefixes -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Purge by Prefixes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ล้าง cache ตาม URL prefix (Enterprise only)</p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prefixes (หนึ่ง prefix ต่อบรรทัด)</label>
                <textarea x-model="prefixes"
                          rows="5"
                          class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder="https://example.com/api/&#10;https://example.com/images/"></textarea>
            </div>
            <button @click="purgePrefixes()"
                    :disabled="loading || !prefixes.trim() || !{{ $isConfigured ? 'true' : 'false' }}"
                    class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                Purge Prefixes
            </button>
        </div>

        <!-- Quick Purge Buttons -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Purge</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ล้าง cache เฉพาะส่วนที่ใช้บ่อย</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button @click="quickPurge('/')"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="px-4 py-3 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all disabled:opacity-50">
                    หน้าแรก
                </button>
                <button @click="quickPurge('/css/', '/js/')"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="px-4 py-3 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all disabled:opacity-50">
                    CSS & JS
                </button>
                <button @click="quickPurge('/images/', '/icons/')"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="px-4 py-3 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all disabled:opacity-50">
                    Images
                </button>
                <button @click="quickPurge('/api/')"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="px-4 py-3 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all disabled:opacity-50">
                    API
                </button>
            </div>
        </div>
    </div>

    <!-- Result Message -->
    <div x-show="message"
         x-transition
         :class="messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border-green-500' : 'bg-red-50 dark:bg-red-900/30 border-red-500'"
         class="fixed bottom-4 right-4 max-w-sm border-l-4 p-4 rounded-xl shadow-lg">
        <div class="flex items-center">
            <template x-if="messageType === 'success'">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </template>
            <template x-if="messageType === 'error'">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </template>
            <span :class="messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'" x-text="message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cacheManager() {
    return {
        loading: false,
        urls: '',
        prefixes: '',
        message: '',
        messageType: 'success',

        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => {
                this.message = '';
            }, 5000);
        },

        async purgeAll() {
            if (!confirm('คุณต้องการล้าง cache ทั้งหมดใช่หรือไม่?')) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.purge-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.showMessage(data.message || (data.success ? 'สำเร็จ' : 'เกิดข้อผิดพลาด'), data.success ? 'success' : 'error');
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async purgeUrls() {
            const urlList = this.urls.split('\n').map(u => u.trim()).filter(u => u);
            if (urlList.length === 0) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.purge-urls") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ urls: urlList })
                });
                const data = await response.json();
                this.showMessage(data.message || (data.success ? 'สำเร็จ' : 'เกิดข้อผิดพลาด'), data.success ? 'success' : 'error');
                if (data.success) this.urls = '';
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async purgePrefixes() {
            const prefixList = this.prefixes.split('\n').map(p => p.trim()).filter(p => p);
            if (prefixList.length === 0) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.purge-prefixes") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ prefixes: prefixList })
                });
                const data = await response.json();
                this.showMessage(data.message || (data.success ? 'สำเร็จ' : 'เกิดข้อผิดพลาด'), data.success ? 'success' : 'error');
                if (data.success) this.prefixes = '';
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async quickPurge(...paths) {
            const baseUrl = '{{ config("app.url") }}';
            const urls = paths.map(p => baseUrl + p);

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.purge-urls") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ urls: urls })
                });
                const data = await response.json();
                this.showMessage(data.message || (data.success ? 'สำเร็จ' : 'เกิดข้อผิดพลาด'), data.success ? 'success' : 'error');
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        }
    }
}
</script>
@endpush
@endsection
