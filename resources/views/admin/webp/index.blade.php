@extends('layouts.admin')

@section('title', 'จัดการรูปภาพ WebP')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="webpManagement()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
            🖼️ จัดการรูปภาพ WebP
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            แปลงและจัดการรูปภาพเป็นรูปแบบ WebP เพื่อเพิ่มความเร็วเว็บไซต์
        </p>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
        <!-- Total Converted (All) -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">รวมแปลงทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($conversionStats['total_converted']) }}</h3>
                    <p class="text-indigo-100 text-xs mt-1">
                        🔄 {{ number_format($conversionStats['total_from_uploads']) }} +
                        📊 {{ number_format($conversionStats['total_from_batch']) }}
                    </p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-sync-alt text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Images -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">รูปภาพทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_images']) }}</h3>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-images text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- WebP Images -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">รูป WebP</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['webp_images']) }}</h3>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Non-WebP Images -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">รอการแปลง</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['non_webp_images']) }}</h3>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-hourglass-half text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Potential Savings -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">ประหยัดได้ (โดยประมาณ)</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['potential_savings'] / 1024, 0) }} KB</h3>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversion History -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-history mr-2 text-indigo-500"></i>
            ประวัติการแปลง WebP
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- From Uploads -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-gray-800 dark:text-white flex items-center">
                        <i class="fas fa-upload mr-2 text-blue-500"></i>
                        จากการอัพโหลด (อัตโตมัติ)
                    </h3>
                </div>
                <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">
                    {{ number_format($conversionStats['total_from_uploads']) }}
                </div>
                @if($conversionStats['last_upload_conversion'])
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-clock mr-1"></i>
                        อัพเดตล่าสุด: {{ $conversionStats['last_upload_conversion']->format('d/m/Y H:i') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">ยังไม่มีการแปลงจากการอัพโหลด</p>
                @endif
            </div>

            <!-- From Batch -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-gray-800 dark:text-white flex items-center">
                        <i class="fas fa-layer-group mr-2 text-green-500"></i>
                        จากการแปลงกลุ่ม (บัตช์)
                    </h3>
                </div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                    {{ number_format($conversionStats['total_from_batch']) }}
                </div>
                @if($conversionStats['last_batch_conversion'])
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-clock mr-1"></i>
                        อัพเดตล่าสุด: {{ $conversionStats['last_batch_conversion']->format('d/m/Y H:i') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">ยังไม่มีการแปลงจากการแปลงกลุ่ม</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Conversion Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-sync-alt mr-2 text-blue-500"></i>
            แปลงรูปภาพเป็น WebP
        </h2>

        <form action="{{ route('admin.webp.convert') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Directory Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-folder mr-1"></i> เลือกโฟลเดอร์
                    </label>
                    <select name="directory"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">ทั้งหมด</option>
                        <option value="branding">Branding (Logo/Favicon)</option>
                        <option value="avatars">LINE Avatars</option>
                        <option value="rich-menus">LINE Rich Menus</option>
                        <option value="withdrawal-slips">Transfer Slips</option>
                        <option value="page-loaders">Page Loaders</option>
                    </select>
                </div>

                <!-- Quality Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-sliders-h mr-1"></i> คุณภาพ WebP (1-100)
                    </label>
                    <input type="number"
                           name="quality"
                           value="85"
                           min="1"
                           max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <!-- Delete Original -->
                <div class="flex items-end">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox"
                               name="delete_original"
                               value="1"
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="fas fa-trash mr-1 text-red-500"></i> ลบรูปต้นฉบับ
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    GIF และ SVG จะไม่ถูกแปลงเพื่อรักษาคุณภาพ
                </p>
                <button type="submit"
                        @click="startConversion"
                        class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-2.5 rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl flex items-center">
                    <i class="fas fa-magic mr-2"></i>
                    เริ่มแปลง
                </button>
            </div>
        </form>

        <!-- Progress Modal -->
        <div x-show="showProgress"
             x-cloak
             class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
             style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        <i class="fas fa-sync-alt text-blue-600 dark:text-blue-400 text-2xl"
                           :class="{ 'fa-spin': progressData.status === 'processing' }"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
                        กำลังแปลงรูปภาพเป็น WebP
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400" x-text="progressData.message">
                        กำลังเริ่มต้น...
                    </p>
                </div>

                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            ความคืบหน้า
                        </span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400" x-text="progressData.percentage + '%'">
                            0%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all duration-300 ease-out"
                             :style="{ width: progressData.percentage + '%' }"></div>
                    </div>
                </div>

                <!-- Details (when completed) -->
                <div x-show="progressData.status === 'completed' && progressData.details"
                     class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-green-800 dark:text-green-300 mb-3 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        สรุปผลการแปลง
                    </h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-white dark:bg-gray-800 rounded p-2">
                            <span class="text-gray-600 dark:text-gray-400">ไฟล์ทั้งหมด:</span>
                            <span class="font-bold text-gray-800 dark:text-white ml-1" x-text="progressData.details?.total_files || 0"></span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded p-2">
                            <span class="text-gray-600 dark:text-gray-400">แปลงสำเร็จ:</span>
                            <span class="font-bold text-green-600 dark:text-green-400 ml-1" x-text="progressData.details?.converted || 0"></span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded p-2">
                            <span class="text-gray-600 dark:text-gray-400">อัพเดต DB:</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400 ml-1" x-text="progressData.details?.database_updates?.total_updated || 0"></span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded p-2">
                            <span class="text-gray-600 dark:text-gray-400">ข้อผิดพลาด:</span>
                            <span class="font-bold text-red-600 dark:text-red-400 ml-1" x-text="progressData.details?.errors || 0"></span>
                        </div>
                    </div>
                </div>

                <!-- Error (when failed) -->
                <div x-show="progressData.status === 'failed'"
                     class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                    <p class="text-red-700 dark:text-red-400 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span x-text="progressData.message">เกิดข้อผิดพลาด</span>
                    </p>
                </div>

                <!-- Close Button -->
                <button @click="closeProgress"
                        :disabled="progressData.status === 'processing'"
                        class="w-full bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="progressData.status === 'processing'">กำลังดำเนินการ...</span>
                    <span x-show="progressData.status === 'completed'">เสร็จสิ้น - รีโหลดหน้า</span>
                    <span x-show="progressData.status === 'failed'">ปิด</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Directory Details -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-folder-open mr-2 text-purple-500"></i>
            รายละเอียดแต่ละโฟลเดอร์
        </h2>

        <div class="space-y-4">
            @forelse($stats['directories'] as $dir)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg p-2">
                                <i class="fas fa-folder text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-white">{{ $dir['name'] }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($dir['total']) }} ไฟล์
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ number_format($dir['size'] / 1024, 2) }} KB
                            </p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        @php
                            $webpPercentage = $dir['total'] > 0 ? ($dir['webp'] / $dir['total']) * 100 : 0;
                        @endphp
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">ความคืบหน้า WebP</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ number_format($webpPercentage, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-2.5 rounded-full transition-all duration-300"
                                 style="width: {{ $webpPercentage }}%"></div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $dir['total'] }}</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-2">
                            <p class="text-xs text-green-600 dark:text-green-400">WebP</p>
                            <p class="text-lg font-bold text-green-700 dark:text-green-400">{{ $dir['webp'] }}</p>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2">
                            <p class="text-xs text-orange-600 dark:text-orange-400">รอแปลง</p>
                            <p class="text-lg font-bold text-orange-700 dark:text-orange-400">{{ $dir['non_webp'] }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                    <p>ไม่พบรูปภาพในระบบ</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Documentation Link -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <div class="flex items-start space-x-4">
            <div class="bg-blue-500 rounded-full p-3 flex-shrink-0">
                <i class="fas fa-book text-white text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2">คู่มือการใช้งาน</h3>
                <p class="text-sm text-blue-700 dark:text-blue-400 mb-3">
                    ดูรายละเอียดเพิ่มเติมเกี่ยวกับระบบแปลง WebP และวิธีใช้งาน Command Line
                </p>
                <a href="{{ asset('WEBP_CONVERSION_GUIDE.md') }}"
                   target="_blank"
                   class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    เปิดคู่มือ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function webpManagement() {
    return {
        showProgress: false,
        jobId: '{{ session("job_id") }}' || null,
        progressInterval: null,
        progressData: {
            percentage: 0,
            message: 'กำลังเริ่มต้น...',
            status: 'pending', // pending, processing, completed, failed
            details: null
        },

        init() {
            // Check if there's a job_id from session (redirect after form submit)
            if (this.jobId) {
                this.showProgress = true;
                this.startPolling();
            }
        },

        startConversion() {
            // Show progress modal immediately
            this.showProgress = true;
            this.progressData = {
                percentage: 0,
                message: 'กำลังเริ่มต้น...',
                status: 'processing',
                details: null
            };
        },

        startPolling() {
            // Poll every 1 second
            this.progressInterval = setInterval(() => {
                this.fetchProgress();
            }, 1000);
        },

        async fetchProgress() {
            try {
                const response = await fetch(`{{ route('admin.webp.progress') }}?job_id=${this.jobId}`);
                const data = await response.json();

                this.progressData = data;

                // Stop polling when completed or failed
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(this.progressInterval);

                    // Auto reload after 3 seconds if completed
                    if (data.status === 'completed') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                }
            } catch (error) {
                console.error('Error fetching progress:', error);
            }
        },

        closeProgress() {
            this.showProgress = false;

            // Clear interval if still running
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }

            // Reload page if completed
            if (this.progressData.status === 'completed') {
                window.location.reload();
            }
        }
    }
}
</script>
@endsection
