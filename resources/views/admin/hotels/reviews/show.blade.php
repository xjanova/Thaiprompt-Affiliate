@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-amber-50 to-orange-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-amber-600 via-orange-600 to-rose-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2" data-translate>รายละเอียดรีวิว</h1>
                    <p class="text-amber-100">{{ $review->hotel->name }}</p>
                </div>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('admin.hotels.reviews.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Review Content -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-2xl mr-4">
                                {{ substr($review->user->name, 0, 1) }}
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $review->user->name }}</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $review->created_at->format('d F Y') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->overall_rating)
                                    <svg class="w-8 h-8 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endif
                            @endfor
                            <span class="ml-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($review->overall_rating, 1) }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">{{ $review->content }}</p>
                </div>
            </div>

            <!-- Rating Breakdown -->
            @if($review->cleanliness_rating || $review->service_rating || $review->facilities_rating || $review->value_rating)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span data-translate>รายละเอียดคะแนน</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($review->cleanliness_rating)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 dark:text-gray-300 font-medium" data-translate>ความสะอาด</span>
                            <span class="text-gray-900 dark:text-white font-bold">{{ number_format($review->cleanliness_rating, 1) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-3 rounded-full" style="width: {{ ($review->cleanliness_rating / 5) * 100 }}%"></div>
                        </div>
                    </div>
                    @endif

                    @if($review->service_rating)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 dark:text-gray-300 font-medium" data-translate>การบริการ</span>
                            <span class="text-gray-900 dark:text-white font-bold">{{ number_format($review->service_rating, 1) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-3 rounded-full" style="width: {{ ($review->service_rating / 5) * 100 }}%"></div>
                        </div>
                    </div>
                    @endif

                    @if($review->facilities_rating)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 dark:text-gray-300 font-medium" data-translate>สิ่งอำนวยความสะดวก</span>
                            <span class="text-gray-900 dark:text-white font-bold">{{ number_format($review->facilities_rating, 1) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-3 rounded-full" style="width: {{ ($review->facilities_rating / 5) * 100 }}%"></div>
                        </div>
                    </div>
                    @endif

                    @if($review->value_rating)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 dark:text-gray-300 font-medium" data-translate>คุ้มค่า</span>
                            <span class="text-gray-900 dark:text-white font-bold">{{ number_format($review->value_rating, 1) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-3 rounded-full" style="width: {{ ($review->value_rating / 5) * 100 }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Hotel Response -->
            @if($review->response)
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl shadow-xl border-2 border-blue-200 dark:border-blue-800 overflow-hidden">
                <div class="p-6 border-b border-blue-200 dark:border-blue-800">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span data-translate>คำตอบจากโรงแรม</span>
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $review->response }}</p>
                    @if($review->response_at)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                        <span data-translate>ตอบกลับเมื่อ:</span> {{ $review->response_at->format('d/m/Y H:i') }}
                    </p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Review Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>ข้อมูลรีวิว</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ผู้รีวิว</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $review->user->name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $review->user->email }}</p>
                    </div>

                    @if($review->room_type)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ประเภทห้อง</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $review->room_type->name }}</p>
                    </div>
                    @endif

                    @if($review->booking)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>การจอง</p>
                        <a href="{{ route('admin.hotels.bookings.show', $review->booking->id) }}"
                           class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                            #{{ $review->booking->id }}
                        </a>
                    </div>
                    @endif

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>วันที่รีวิว</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $review->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>สถานะ</p>
                        @if($review->is_approved)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300" data-translate>
                                อนุมัติแล้ว
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300" data-translate>
                                รออนุมัติ
                            </span>
                        @endif
                    </div>

                    @if($review->is_featured)
                    <div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300" data-translate>
                            ⭐ รีวิวแนะนำ
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Moderation Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        <span data-translate>การจัดการ</span>
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    @if(!$review->is_approved)
                        <button onclick="approveReview({{ $review->id }})"
                                class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-xl transition font-semibold flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span data-translate>อนุมัติรีวิว</span>
                        </button>
                        <button onclick="rejectReview({{ $review->id }})"
                                class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-xl transition font-semibold flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span data-translate>ปฏิเสธรีวิว</span>
                        </button>
                    @endif

                    <button onclick="toggleFeatured({{ $review->id }})"
                            class="w-full px-6 py-3 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-xl transition font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        <span data-translate>{{ $review->is_featured ? 'ยกเลิกแนะนำ' : 'ตั้งเป็นแนะนำ' }}</span>
                    </button>

                    <button onclick="deleteReview({{ $review->id }})"
                            class="w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-xl transition font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span data-translate>ลบรีวิว</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * อนุมัติรีวิว
 */
function approveReview(id) {
    if (confirm('คุณต้องการอนุมัติรีวิวนี้?')) {
        fetch(`/admin/hotels/reviews/${id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('อนุมัติรีวิวสำเร็จ');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด');
            }
        });
    }
}

/**
 * ปฏิเสธรีวิว
 */
function rejectReview(id) {
    if (confirm('คุณต้องการปฏิเสธรีวิวนี้?')) {
        fetch(`/admin/hotels/reviews/${id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('ปฏิเสธรีวิวสำเร็จ');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด');
            }
        });
    }
}

/**
 * สลับสถานะแนะนำ
 */
function toggleFeatured(id) {
    fetch(`/admin/hotels/reviews/${id}/toggle-featured`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('อัพเดทสถานะสำเร็จ');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด');
        }
    });
}

/**
 * ลบรีวิว
 */
function deleteReview(id) {
    if (confirm('คุณแน่ใจหรือว่าต้องการลบรีวิวนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/hotels/reviews/${id}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
