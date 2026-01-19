{{--
    Sidebar Footer Iframe Component

    ส่วน footer ของ sidebar ที่โหลดผ่าน iframe แบบ seamless
    รองรับ real-time update โดยไม่ต้อง reload sidebar

    @props
    - compact: bool - โหมดย่อ (auto-hide mode)

    @example
    <x-arrow-x.sidebar-footer-iframe :compact="!$store.sidebar.shouldExpand" />
--}}

@props([
    'compact' => false,
])

@php
    $footerUrl = route('admin.sidebar.footer');
@endphp

<div class="relative h-32"
     x-data="sidebarFooterManager()"
     x-init="init('{{ $footerUrl }}', {{ $compact ? 'true' : 'false' }})">

    {{-- Loading Skeleton (แสดงขณะ iframe กำลังโหลด) --}}
    <div x-show="isLoading"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 flex items-center justify-center">
        {{-- Shimmer Effect --}}
        <div class="w-full h-full p-4 space-y-3">
            {{-- Logo Skeleton --}}
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 bg-white/10 rounded-2xl animate-pulse"></div>
                <div class="flex-1 space-y-2" x-show="!{{ $compact ? 'true' : 'false' }}">
                    <div class="h-4 bg-white/10 rounded w-3/4 animate-pulse"></div>
                    <div class="h-3 bg-white/10 rounded w-1/2 animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Iframe (Seamless) --}}
    <iframe
        :src="iframeUrl"
        @load="handleLoad()"
        class="w-full h-full border-0 bg-transparent"
        scrolling="no"
        :style="'opacity: ' + (isLoading ? '0' : '1')"
        sandbox="allow-same-origin allow-scripts"
        loading="lazy"
        title="Sidebar Footer">
    </iframe>
</div>

<script>
/**
 * Sidebar Footer Manager
 *
 * จัดการ iframe footer ให้โหลดและแสดงผลแบบ seamless
 */
function sidebarFooterManager() {
    return {
        iframeUrl: '',
        isLoading: true,
        compact: false,

        /**
         * เริ่มต้น component
         */
        init(url, isCompact) {
            this.compact = isCompact;

            // สร้าง URL พร้อม parameters
            const params = new URLSearchParams({
                iframe: '1',
                compact: this.compact ? '1' : '0',
                t: Date.now() // Cache buster
            });

            this.iframeUrl = `${url}?${params.toString()}`;

            // Auto-reload ทุก 5 นาที (สำหรับ version/license update)
            setInterval(() => {
                this.reload();
            }, 5 * 60 * 1000);
        },

        /**
         * จัดการเมื่อ iframe โหลดเสร็จ
         */
        handleLoad() {
            // ซ่อน loading หลังจาก 200ms
            setTimeout(() => {
                this.isLoading = false;
            }, 200);
        },

        /**
         * Reload iframe
         */
        reload() {
            const iframe = this.$el.querySelector('iframe');
            if (iframe) {
                const params = new URLSearchParams({
                    iframe: '1',
                    compact: this.compact ? '1' : '0',
                    t: Date.now()
                });
                iframe.src = iframe.src.split('?')[0] + '?' + params.toString();
            }
        }
    }
}
</script>

<style>
/* Ensure iframe is seamless */
iframe {
    display: block;
    transition: opacity 0.3s ease;
}
</style>
