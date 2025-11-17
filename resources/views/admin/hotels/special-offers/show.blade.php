@extends('layouts.admin-v3')

@section('title', 'รายละเอียดโปรโมชั่น - ' . $offer->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-tags text-blue-600 dark:text-blue-400 mr-2"></i>
                {{ $offer->name }}
            </h1>
            <div class="flex space-x-2">
                <a href="{{ route('admin.hotels.special-offers.edit', $offer->id) }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition">
                    <i class="fas fa-edit mr-2"></i>แก้ไข
                </a>
                <form action="{{ route('admin.hotels.special-offers.destroy', $offer->id) }}" method="POST" class="inline"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบโปรโมชั่นนี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg transition">
                        <i class="fas fa-trash mr-2"></i>ลบ
                    </button>
                </form>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="flex text-sm text-gray-500 dark:text-gray-400 space-x-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.hotels.special-offers.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">โปรโมชั่นพิเศษ</a>
            <span>/</span>
            <span class="text-gray-900 dark:text-white font-medium">{{ $offer->name }}</span>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (Left 2 columns) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Banner -->
            @if($offer->banner_image)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <img src="{{ Storage::url($offer->banner_image) }}" alt="{{ $offer->name }}"
                         class="w-full h-64 object-cover">
                </div>
            @endif

            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mr-2"></i>
                    ข้อมูลพื้นฐาน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อโปรโมชั่น:</label>
                        <p class="text-gray-900 dark:text-white">{{ $offer->name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โค้ดส่วนลด:</label>
                        <div class="flex items-center space-x-2">
                            <code id="offerCode" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono">{{ $offer->code }}</code>
                            <button type="button" onclick="copyCode()"
                                    class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition text-sm">
                                <i class="fas fa-copy mr-1"></i>คัดลอก
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โรงแรม:</label>
                        <a href="{{ route('admin.hotels.show', $offer->hotel_id) }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition text-sm">
                            <i class="fas fa-hotel mr-2"></i>{{ $offer->hotel->name }}
                        </a>
                    </div>

                    @if($offer->description)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย:</label>
                            <p class="text-gray-900 dark:text-white">{{ $offer->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Discount Details -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-percent mr-2"></i>
                    รายละเอียดส่วนลด
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-emerald-100 mb-1">ประเภทส่วนลด:</label>
                        <p>
                            @if($offer->discount_type == 'percentage')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/30 text-white">
                                    เปอร์เซ็นต์
                                </span>
                            @elseif($offer->discount_type == 'fixed')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/30 text-white">
                                    จำนวนเงินคงที่
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/30 text-white">
                                    ฟรีจำนวนคืน
                                </span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-emerald-100 mb-1">มูลค่าส่วนลด:</label>
                        <p class="text-3xl font-bold">
                            @if($offer->discount_type == 'percentage')
                                {{ $offer->discount_value }}%
                            @elseif($offer->discount_type == 'fixed')
                                ฿{{ number_format($offer->discount_value) }}
                            @else
                                {{ $offer->free_nights }} คืน
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-emerald-100 mb-1">สถานะ:</label>
                        <div class="flex flex-wrap gap-2">
                            @if($offer->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/30 text-white">
                                    เปิดใช้งาน
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                                    ปิดใช้งาน
                                </span>
                            @endif
                            @if($offer->is_featured)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500/50 text-white">
                                    แนะนำ
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($offer->min_nights)
                        <div>
                            <label class="block text-sm font-medium text-emerald-100 mb-1">จำนวนคืนขั้นต่ำ:</label>
                            <p class="text-xl font-semibold">{{ $offer->min_nights }} คืน</p>
                        </div>
                    @endif

                    @if($offer->min_amount)
                        <div>
                            <label class="block text-sm font-medium text-emerald-100 mb-1">ยอดขั้นต่ำ:</label>
                            <p class="text-xl font-semibold">฿{{ number_format($offer->min_amount) }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Validity Period -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-calendar text-blue-600 dark:text-blue-400 mr-2"></i>
                    ระยะเวลาใช้งาน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่มต้น:</label>
                        <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($offer->valid_from)->format('d/m/Y') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด:</label>
                        <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($offer->valid_to)->format('d/m/Y') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ:</label>
                        @php
                            $now = now();
                            $validFrom = \Carbon\Carbon::parse($offer->valid_from);
                            $validTo = \Carbon\Carbon::parse($offer->valid_to);
                        @endphp
                        <p>
                            @if($now->lt($validFrom))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    ยังไม่เริ่ม
                                </span>
                            @elseif($now->gt($validTo))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    หมดอายุ
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    กำลังใช้งาน
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($offer->applicable_days && count($offer->applicable_days) > 0)
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่ใช้ได้:</label>
                            @php
                                $dayNames = [
                                    'monday' => 'จันทร์', 'tuesday' => 'อังคาร', 'wednesday' => 'พุธ',
                                    'thursday' => 'พฤหัสบดี', 'friday' => 'ศุกร์', 'saturday' => 'เสาร์', 'sunday' => 'อาทิตย์'
                                ];
                                $days = array_map(function($day) use ($dayNames) {
                                    return $dayNames[$day] ?? $day;
                                }, $offer->applicable_days);
                            @endphp
                            <p class="text-gray-900 dark:text-white">{{ implode(', ', $days) }}</p>
                        </div>
                    @else
                        <div class="md:col-span-3">
                            <p class="text-gray-500 dark:text-gray-400 italic">ใช้ได้ทุกวัน</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Room Types -->
            @if($offer->room_type_ids && count($offer->room_type_ids) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-bed text-blue-600 dark:text-blue-400 mr-2"></i>
                        ห้องพักที่ใช้ได้
                    </h3>

                    @php
                        $roomTypes = \App\Models\RoomType::whereIn('id', $offer->room_type_ids)->get();
                    @endphp

                    @if($roomTypes->count() > 0)
                        <ul class="space-y-2">
                            @foreach($roomTypes as $room)
                                <li class="flex items-center space-x-2">
                                    <i class="fas fa-check text-emerald-600 dark:text-emerald-400"></i>
                                    <a href="{{ route('admin.hotels.rooms.show', [$offer->hotel_id, $room->id]) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $room->name }}
                                    </a>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">(฿{{ number_format($room->base_price) }})</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">ไม่พบห้องพัก</p>
                    @endif
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <p class="text-gray-500 dark:text-gray-400 italic">ใช้ได้กับทุกห้องพัก</p>
                </div>
            @endif
        </div>

        <!-- Sidebar (Right 1 column) -->
        <div class="space-y-6">
            <!-- Statistics -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-2"></i>
                    สถิติการใช้งาน
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-purple-100 mb-1">จำนวนครั้งที่ใช้:</label>
                        <p class="text-4xl font-bold">{{ number_format($stats['total_uses']) }}</p>
                        @if($offer->max_uses)
                            <p class="text-sm text-purple-100 mt-1">จาก {{ number_format($offer->max_uses) }} ครั้ง</p>
                            <div class="mt-2 h-2 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-white rounded-full" style="width: {{ min(($stats['total_uses'] / $offer->max_uses) * 100, 100) }}%"></div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-purple-100 mb-1">ยอดส่วนลดรวม:</label>
                        <p class="text-3xl font-bold">฿{{ number_format($stats['total_savings']) }}</p>
                    </div>

                    @if($offer->max_uses_per_user)
                        <div>
                            <label class="block text-sm font-medium text-purple-100 mb-1">ใช้ได้ต่อคน:</label>
                            <p class="text-xl font-semibold">{{ $offer->max_uses_per_user }} ครั้ง</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-bolt text-blue-600 dark:text-blue-400 mr-2"></i>
                    การดำเนินการ
                </h3>

                <div class="space-y-2">
                    @if($offer->is_active)
                        <form action="{{ route('admin.hotels.special-offers.toggle-status', $offer->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 text-white rounded-lg transition">
                                <i class="fas fa-pause mr-2"></i>ปิดใช้งาน
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.hotels.special-offers.toggle-status', $offer->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white rounded-lg transition">
                                <i class="fas fa-play mr-2"></i>เปิดใช้งาน
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.hotels.special-offers.toggle-featured', $offer->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition">
                            <i class="fas fa-star mr-2"></i>{{ $offer->is_featured ? 'ยกเลิกแนะนำ' : 'ตั้งเป็นแนะนำ' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.hotels.special-offers.edit', $offer->id) }}"
                       class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-center rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>แก้ไขโปรโมชั่น
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-info text-blue-600 dark:text-blue-400 mr-2"></i>
                    ข้อมูลเพิ่มเติม
                </h3>

                <div class="space-y-3">
                    <div>
                        <strong class="text-gray-900 dark:text-white">สร้างเมื่อ:</strong>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $offer->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-900 dark:text-white">แก้ไขล่าสุด:</strong>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $offer->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyCode() {
    const code = document.getElementById('offerCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('คัดลอกโค้ด ' + code + ' เรียบร้อยแล้ว!');
    });
}
</script>
@endsection
