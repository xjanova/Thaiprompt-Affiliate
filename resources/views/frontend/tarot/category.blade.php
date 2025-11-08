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
                            @if($canUseFree)
                                <span class="text-green-300 text-sm">✓ คุณมีสิทธิ์ดูฟรี 1 ครั้ง</span>
                            @else
                                <span class="text-red-300 text-sm">หมดสิทธิ์ฟรีวันนี้แล้ว</span>
                            @endif
                        @endif
                    @else
                        <div class="bg-green-500 text-white px-6 py-3 rounded-full font-bold text-xl">
                            ฟรี
                        </div>
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
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-6 hover:bg-opacity-20 transition-all cursor-pointer border border-white border-opacity-20"
                 onclick="startReading({{ $spreadType->id }})">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white">
                        {{ $spreadType->name_th }}
                    </h3>
                    <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm">
                        {{ $spreadType->card_count }} ใบ
                    </span>
                </div>

                <p class="text-purple-200 mb-4">
                    {{ $spreadType->description_th }}
                </p>

                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition-all">
                    เลือกแบบนี้
                </button>
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
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@endsection
