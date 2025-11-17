@extends('layouts.admin-v3')

@section('title', 'รายละเอียดโฆษณา')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-ad mr-3"></i>
                    {{ $advertisement->title }}
                </h1>
                <p class="text-purple-100">รายละเอียดและสถิติโฆษณา</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.pos.advertisements.preview', $advertisement) }}"
                   target="_blank"
                   class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-50 transition-all">
                    <i class="fas fa-play mr-2"></i>
                    ดูตัวอย่าง
                </a>
                <a href="{{ route('admin.pos.advertisements.edit', $advertisement) }}"
                   class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-all">
                    <i class="fas fa-edit mr-2"></i>
                    แก้ไข
                </a>
                <a href="{{ route('admin.pos.advertisements.index') }}"
                   class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-eye text-3xl opacity-75"></i>
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">การดู</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['view_count']) }}</p>
            <p class="text-blue-100 text-sm mt-1">ครั้งที่แสดง</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-mouse-pointer text-3xl opacity-75"></i>
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">คลิก</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['click_count']) }}</p>
            <p class="text-green-100 text-sm mt-1">ครั้งที่คลิก</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-percentage text-3xl opacity-75"></i>
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">CTR</span>
            </div>
            <p class="text-3xl font-bold">{{ $stats['click_through_rate'] }}%</p>
            <p class="text-purple-100 text-sm mt-1">อัตราคลิก</p>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-clock text-3xl opacity-75"></i>
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">ล่าสุด</span>
            </div>
            <p class="text-lg font-bold">
                {{ $stats['last_displayed'] ? $stats['last_displayed']->diffForHumans() : 'ยังไม่ได้แสดง' }}
            </p>
            <p class="text-orange-100 text-sm mt-1">แสดงล่าสุด</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-image text-purple-600 mr-2"></i>
                    ตัวอย่างโฆษณา
                </h2>

                <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-8 flex items-center justify-center min-h-[400px]">
                    @if($advertisement->type === 'image' && $advertisement->image_url)
                        <img src="{{ Storage::url($advertisement->image_url) }}"
                             alt="{{ $advertisement->title }}"
                             class="max-w-full max-h-96 rounded-lg shadow-2xl">
                    @elseif($advertisement->type === 'video' && $advertisement->video_url)
                        <video controls
                               class="max-w-full max-h-96 rounded-lg shadow-2xl">
                            <source src="{{ Storage::url($advertisement->video_url) }}" type="video/mp4">
                        </video>
                    @elseif($advertisement->type === 'html' && $advertisement->html_content)
                        <div class="w-full bg-white dark:bg-gray-800 p-6 rounded-lg">
                            {!! $advertisement->html_content !!}
                        </div>
                    @elseif($advertisement->type === 'promotion')
                        <div class="bg-gradient-to-br from-yellow-400 via-orange-500 to-red-500 text-white p-12 rounded-2xl shadow-2xl text-center max-w-md">
                            <i class="fas fa-tags text-6xl mb-4"></i>
                            <h3 class="text-4xl font-bold mb-4">{{ $advertisement->title }}</h3>
                            @if($advertisement->promotion_code)
                                <div class="bg-white text-gray-900 py-3 px-6 rounded-lg text-2xl font-mono font-bold mb-4">
                                    {{ $advertisement->promotion_code }}
                                </div>
                            @endif
                            @if($advertisement->discount_percentage)
                                <p class="text-5xl font-bold mb-2">{{ $advertisement->discount_percentage }}%</p>
                                <p class="text-xl">ส่วนลด</p>
                            @elseif($advertisement->discount_amount)
                                <p class="text-5xl font-bold mb-2">฿{{ number_format($advertisement->discount_amount) }}</p>
                                <p class="text-xl">ส่วนลด</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Description -->
            @if($advertisement->description)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-align-left text-indigo-600 mr-2"></i>
                        คำอธิบาย
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ $advertisement->description }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status & Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    ข้อมูล
                </h2>

                <div class="space-y-4">
                    <!-- Status -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สถานะ</span>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $advertisement->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $advertisement->is_active ? 'ใช้งาน' : 'หยุดใช้งาน' }}
                        </span>
                    </div>

                    <!-- Type -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ประเภท</span>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                            {{ ucfirst($advertisement->type) }}
                        </span>
                    </div>

                    <!-- Store -->
                    @if($advertisement->store)
                        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">ร้านค้า</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ $advertisement->store->name }}</span>
                        </div>
                    @endif

                    <!-- Duration -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ระยะเวลา</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $advertisement->duration_seconds }} วินาที</span>
                    </div>

                    <!-- Order -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ลำดับ</span>
                        <span class="text-gray-900 dark:text-white font-medium">#{{ $advertisement->order }}</span>
                    </div>

                    <!-- Idle Only -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">แสดงเมื่อไม่ใช้งาน</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ $advertisement->show_on_idle_only ? 'ใช่' : 'ไม่' }}
                        </span>
                    </div>

                    <!-- Link -->
                    @if($advertisement->link_url)
                        <div class="pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 block mb-2">ลิงก์</span>
                            <a href="{{ $advertisement->link_url }}"
                               target="_blank"
                               class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm break-all">
                                {{ $advertisement->link_url }}
                            </a>
                        </div>
                    @endif

                    <!-- Created -->
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white font-medium text-sm">
                            {{ $advertisement->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Promotion Details -->
            @if($advertisement->type === 'promotion')
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl shadow-lg p-6 border-2 border-yellow-300 dark:border-yellow-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-percent text-yellow-600 mr-2"></i>
                        รายละเอียดโปรโมชั่น
                    </h2>

                    <div class="space-y-3">
                        @if($advertisement->promotion_code)
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">โค้ดโปรโมชั่น</span>
                                <p class="text-2xl font-mono font-bold text-gray-900 dark:text-white">{{ $advertisement->promotion_code }}</p>
                            </div>
                        @endif

                        @if($advertisement->discount_amount)
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">ส่วนลด</span>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($advertisement->discount_amount) }}</p>
                            </div>
                        @endif

                        @if($advertisement->discount_percentage)
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">ส่วนลด</span>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $advertisement->discount_percentage }}%</p>
                            </div>
                        @endif

                        @if($advertisement->promotion_start)
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">เริ่มโปรโมชั่น</span>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    {{ \Carbon\Carbon::parse($advertisement->promotion_start)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endif

                        @if($advertisement->promotion_end)
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">สิ้นสุดโปรโมชั่น</span>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    {{ \Carbon\Carbon::parse($advertisement->promotion_end)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">การจัดการ</h2>

                <div class="space-y-3">
                    <a href="{{ route('admin.pos.advertisements.edit', $advertisement) }}"
                       class="flex items-center justify-center bg-yellow-500 text-white px-4 py-3 rounded-lg hover:bg-yellow-600 transition-all">
                        <i class="fas fa-edit mr-2"></i>
                        แก้ไขโฆษณา
                    </a>

                    <a href="{{ route('admin.pos.advertisements.preview', $advertisement) }}"
                       target="_blank"
                       class="flex items-center justify-center bg-purple-500 text-white px-4 py-3 rounded-lg hover:bg-purple-600 transition-all">
                        <i class="fas fa-play mr-2"></i>
                        ดูตัวอย่าง
                    </a>

                    <form action="{{ route('admin.pos.advertisements.duplicate', $advertisement) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-center bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition-all">
                            <i class="fas fa-copy mr-2"></i>
                            คัดลอกโฆษณา
                        </button>
                    </form>

                    <form action="{{ route('admin.pos.advertisements.toggle-status', $advertisement) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full flex items-center justify-center {{ $advertisement->is_active ? 'bg-gray-500 hover:bg-gray-600' : 'bg-green-500 hover:bg-green-600' }} text-white px-4 py-3 rounded-lg transition-all">
                            <i class="fas fa-power-off mr-2"></i>
                            {{ $advertisement->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.pos.advertisements.destroy', $advertisement) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบโฆษณานี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full flex items-center justify-center bg-red-500 text-white px-4 py-3 rounded-lg hover:bg-red-600 transition-all">
                            <i class="fas fa-trash mr-2"></i>
                            ลบโฆษณา
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from('.space-y-6 > div', {
            opacity: 0,
            y: 20,
            duration: 0.5,
            stagger: 0.1,
            ease: 'power2.out'
        });
    });
</script>
@endpush
@endsection
