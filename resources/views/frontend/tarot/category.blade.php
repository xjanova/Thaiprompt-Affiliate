@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900">
    <div class="container mx-auto px-4 py-12">
        <!-- Back Button -->
        <a href="{{ route('tarot.index') }}" class="inline-flex items-center text-white hover:text-purple-300 mb-8">
            <i class="fas fa-arrow-left mr-2"></i> กลับ
        </a>

        <!-- Category Header -->
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 mb-8 border border-white border-opacity-20">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas {{ $category->icon }} mr-3" style="color: {{ $category->color }}"></i>
                        {{ $category->name_th }}
                    </h1>
                    <p class="text-purple-200 text-lg">{{ $category->description_th }}</p>
                </div>
                <div class="text-right">
                    @if($category->price > 0)
                        <div class="bg-yellow-500 text-yellow-900 px-6 py-3 rounded-full font-bold text-xl mb-2">
                            {{ number_format($category->price, 0) }} บาท
                        </div>
                        @if($category->is_free_first)
                            @php
                                $userId = Auth::id();
                                $sessionId = session()->getId();
                                $ipAddress = request()->ip();
                                $remaining = \App\Models\TarotUserLimit::getRemainingQuota($category->id, $userId, $sessionId, $ipAddress);
                            @endphp
                            @if($remaining > 0)
                                <span class="text-green-300 text-sm">✓ โควต้าฟรีเหลือ: {{ $remaining }} ครั้ง/วัน</span>
                            @else
                                <span class="text-red-300 text-sm">❌ หมดโควต้าฟรีวันนี้แล้ว</span>
                            @endif
                        @endif
                    @else
                        <div class="bg-green-500 text-white px-6 py-3 rounded-full font-bold text-xl mb-2">
                            ฟรี
                        </div>
                        @php
                            $userId = Auth::id();
                            $sessionId = session()->getId();
                            $ipAddress = request()->ip();
                            $remaining = \App\Models\TarotUserLimit::getRemainingQuota($category->id, $userId, $sessionId, $ipAddress);
                        @endphp
                        <span class="text-green-300 text-sm">โควต้าเหลือ: {{ $remaining }} ครั้ง/วัน</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Question Input -->
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 mb-8 border border-white border-opacity-20">
            <label class="block text-white text-xl font-bold mb-4">
                คำถามของคุณ (ไม่บังคับ)
            </label>
            <textarea id="question"
                      rows="3"
                      class="w-full px-4 py-3 rounded-lg bg-white bg-opacity-20 text-white placeholder-purple-300 border border-white border-opacity-30 focus:outline-none focus:border-purple-400"
                      placeholder="พิมพ์คำถามที่คุณต้องการทราบคำตอบ..."></textarea>
        </div>

        <!-- Spread Types -->
        <h2 class="text-3xl font-bold text-white mb-6">เลือกรูปแบบการเปิดไพ่</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($spreadTypes as $spreadType)
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-6 hover:bg-opacity-20 transition-all border border-white border-opacity-20">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white">
                        {{ $spreadType->name_th }}
                    </h3>
                    <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm">
                        {{ $spreadType->card_count }} ใบ
                    </span>
                </div>

                <p class="text-purple-200 mb-3">
                    {{ $spreadType->description_th }}
                </p>

                @if($spreadType->base_price > 0)
                    <p class="text-yellow-300 text-sm mb-4">
                        + ราคารูปแบบ: ฿{{ number_format($spreadType->base_price, 0) }}
                    </p>
                @endif

                <!-- Buttons -->
                <div class="space-y-2">
                    <button onclick="startReading({{ $spreadType->id }})"
                            class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white py-3 rounded-lg font-semibold transition-all">
                        ⚡ ทำนายทันที
                    </button>
                    <button onclick="addToCart({{ $spreadType->id }})"
                            class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white py-3 rounded-lg font-semibold transition-all border border-white border-opacity-30">
                        🛒 เพิ่มเข้าตะกร้า
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Card Back Preview -->
<div class="fixed bottom-4 right-4 bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-4 border border-white border-opacity-20">
    <p class="text-white text-sm mb-2">รูปหลังไพ่:</p>
    <img src="{{ $cardBackImage->image_url ?? asset('images/tarot/card-back-default.png') }}"
         alt="Card Back"
         class="w-16 h-24 rounded-lg shadow-lg">
</div>

@push('scripts')
<script>
function startReading(spreadTypeId) {
    const question = document.getElementById('question').value;
    const categoryId = {{ $category->id }};
    const canUseFree = {{ $canUseFree ? 'true' : 'false' }};

    // Show loading
    Swal.fire({
        title: 'กำลังสับไพ่...',
        html: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send request
    fetch('{{ route('tarot.start') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            category_id: categoryId,
            spread_type_id: spreadTypeId,
            question: question,
            use_free: canUseFree
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.requires_payment) {
            // Redirect to payment
            Swal.fire({
                title: 'ต้องชำระเงิน',
                html: `จำนวนเงิน: ${data.amount} บาท<br>${data.category}`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'ชำระเงิน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = data.payment_url;
                }
            });
        } else if (data.success) {
            // Redirect to reading
            window.location.href = data.redirect_url;
        } else {
            Swal.fire('เกิดข้อผิดพลาด', data.message || 'กรุณาลองใหม่อีกครั้ง', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('เกิดข้อผิดพลาด', 'กรุณาลองใหม่อีกครั้ง', 'error');
    });
}

function addToCart(spreadTypeId) {
    const question = document.getElementById('question').value;

    if (!question || question.trim() === '') {
        Swal.fire({
            title: 'กรุณาใส่คำถาม',
            text: 'โปรดพิมพ์คำถามที่คุณต้องการทราบคำตอบ',
            icon: 'warning'
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: 'กำลังเพิ่มเข้าตะกร้า...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Add to cart
    const formData = new FormData();
    formData.append('category_id', {{ $category->id }});
    formData.append('spread_type_id', spreadTypeId);
    formData.append('question', question);

    fetch('{{ route('tarot.cart.add') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            Swal.fire({
                title: 'เพิ่มเข้าตะกร้าแล้ว!',
                text: 'ต้องการไปที่ตะกร้าหรือไม่?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'ไปที่ตะกร้า',
                cancelButtonText: 'เลือกต่อ'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route('tarot.cart.index') }}';
                } else {
                    // Clear question for next item
                    document.getElementById('question').value = '';
                }
            });
        } else {
            throw new Error('Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('เกิดข้อผิดพลาด', 'กรุณาลองใหม่อีกครั้ง', 'error');
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@endsection
