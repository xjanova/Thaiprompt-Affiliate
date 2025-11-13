@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Top Bar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left Side -->
                <div class="flex items-center space-x-4">
                    <a
                        href="{{ route('prompt-to-web.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับ
                    </a>

                    <div class="hidden md:block">
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $page->title }}
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            สร้างเมื่อ {{ $page->created_at->diffForHumans() }} •
                            <i class="fas fa-eye"></i> {{ $page->views }} views
                        </p>
                    </div>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-2" x-data="{ viewMode: 'desktop' }">
                    <!-- View Mode Toggle -->
                    <div class="hidden lg:flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        <button
                            @click="viewMode = 'mobile'; updatePreview()"
                            :class="viewMode === 'mobile' ? 'bg-white dark:bg-gray-600 shadow' : ''"
                            class="px-3 py-1 rounded text-sm font-medium text-gray-700 dark:text-gray-300 transition-all"
                            title="มือถือ"
                        >
                            <i class="fas fa-mobile-alt"></i>
                        </button>
                        <button
                            @click="viewMode = 'tablet'; updatePreview()"
                            :class="viewMode === 'tablet' ? 'bg-white dark:bg-gray-600 shadow' : ''"
                            class="px-3 py-1 rounded text-sm font-medium text-gray-700 dark:text-gray-300 transition-all"
                            title="แท็บเล็ต"
                        >
                            <i class="fas fa-tablet-alt"></i>
                        </button>
                        <button
                            @click="viewMode = 'desktop'; updatePreview()"
                            :class="viewMode === 'desktop' ? 'bg-white dark:bg-gray-600 shadow' : ''"
                            class="px-3 py-1 rounded text-sm font-medium text-gray-700 dark:text-gray-300 transition-all"
                            title="เดสก์ท็อป"
                        >
                            <i class="fas fa-desktop"></i>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <button
                        onclick="window.open('{{ route('prompt-to-web.show', $page->slug) }}', '_blank')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
                        title="เปิดในหน้าต่างใหม่"
                    >
                        <i class="fas fa-external-link-alt mr-2"></i>
                        <span class="hidden sm:inline">เปิดหน้าเว็บ</span>
                    </button>

                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
                        >
                            <i class="fas fa-ellipsis-v"></i>
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg py-1 z-50"
                        >
                            <button
                                onclick="copyToClipboard('{{ route('prompt-to-web.show', $page->slug) }}')"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                <i class="fas fa-link mr-2"></i> คัดลอกลิงก์
                            </button>
                            <button
                                onclick="downloadHtml()"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                <i class="fas fa-download mr-2"></i> ดาวน์โหลด HTML
                            </button>
                            @auth
                                @if(auth()->id() === $page->user_id)
                                <hr class="my-1 border-gray-200 dark:border-gray-600">
                                <button
                                    onclick="deletePage({{ $page->id }})"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600"
                                >
                                    <i class="fas fa-trash mr-2"></i> ลบหน้าเว็บ
                                </button>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Container -->
    <div class="p-4" x-data="{ viewMode: 'desktop' }">
        <div class="mx-auto transition-all duration-300" :style="getContainerStyle()">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl overflow-hidden" style="height: calc(100vh - 150px);">
                <iframe
                    id="previewFrame"
                    src="{{ route('prompt-to-web.show', $page->slug) }}"
                    class="w-full h-full border-0"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                ></iframe>
            </div>
        </div>
    </div>

    <!-- Info Panel (Bottom) -->
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-3 px-4 z-40">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between text-sm">
                <div class="flex items-center space-x-4 text-gray-600 dark:text-gray-400">
                    <span><i class="fas fa-robot mr-1"></i> {{ $page->provider ?? 'Auto' }}</span>
                    <span><i class="fas fa-coins mr-1"></i> {{ number_format($page->tokens_used) }} tokens</span>
                    <span><i class="fas fa-dollar-sign mr-1"></i> ${{ number_format($page->cost, 4) }}</span>
                </div>
                <div class="text-gray-500 dark:text-gray-400">
                    <span>Powered by Prompt to Web</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Get container style based on view mode
function getContainerStyle() {
    const mode = Alpine.$data(document.querySelector('[x-data]')).viewMode;

    const styles = {
        mobile: 'max-width: 375px',
        tablet: 'max-width: 768px',
        desktop: 'max-width: 100%'
    };

    return styles[mode] || styles.desktop;
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('คัดลอกลิงก์สำเร็จ!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Download HTML
function downloadHtml() {
    const iframe = document.getElementById('previewFrame');
    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
    const html = iframeDoc.documentElement.outerHTML;

    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '{{ $page->slug }}.html';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Delete page
async function deletePage(id) {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะลบหน้าเว็บนี้?')) {
        return;
    }

    try {
        const response = await fetch(`/prompt-to-web/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('ลบหน้าเว็บสำเร็จ');
            window.location.href = '{{ route("prompt-to-web.index") }}';
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    }
}

// Alpine.js helper for view mode
document.addEventListener('alpine:init', () => {
    Alpine.data('viewMode', () => ({
        viewMode: 'desktop',

        getContainerStyle() {
            const styles = {
                mobile: 'max-width: 375px',
                tablet: 'max-width: 768px',
                desktop: 'max-width: 100%'
            };

            return styles[this.viewMode] || styles.desktop;
        },

        updatePreview() {
            // Trigger a reflow to apply the new styles
            setTimeout(() => {
                document.getElementById('previewFrame').contentWindow.location.reload();
            }, 100);
        }
    }));
});
</script>
@endpush
@endsection
