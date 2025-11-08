@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 py-12">
    <div class="container mx-auto px-4 max-w-2xl">
        <h1 class="text-4xl font-bold text-white mb-8 text-center">ชำระเงิน</h1>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Order Summary -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">รายละเอียดการสั่งซื้อ</h2>
                <div class="bg-purple-50 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-700 font-semibold">หมวดหมู่:</span>
                        <span class="text-gray-900">{{ $category->name_th }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-700 font-semibold">รูปแบบการเปิดไพ่:</span>
                        <span class="text-gray-900">{{ $spreadType->name_th }}</span>
                    </div>
                    <div class="border-t border-purple-200 my-4"></div>
                    <div class="flex justify-between items-center text-xl font-bold">
                        <span class="text-gray-700">ยอดรวม:</span>
                        <span class="text-purple-600">{{ number_format($category->price, 2) }} บาท</span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">เลือกวิธีชำระเงิน</h2>

                <div class="space-y-3">
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 transition-all">
                        <input type="radio" name="payment_method" value="wallet" class="mr-3" checked>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">กระเป๋าเงิน</div>
                            <div class="text-sm text-gray-500">ชำระผ่านกระเป๋าเงินในระบบ</div>
                        </div>
                        <i class="fas fa-wallet text-2xl text-purple-600"></i>
                    </label>

                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 transition-all">
                        <input type="radio" name="payment_method" value="promptpay" class="mr-3">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">พร้อมเพย์</div>
                            <div class="text-sm text-gray-500">สแกน QR Code เพื่อชำระเงิน</div>
                        </div>
                        <i class="fas fa-qrcode text-2xl text-blue-600"></i>
                    </label>

                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 transition-all">
                        <input type="radio" name="payment_method" value="credit_card" class="mr-3">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">บัตรเครดิต/เดบิต</div>
                            <div class="text-sm text-gray-500">ชำระด้วยบัตรเครดิตหรือเดบิต</div>
                        </div>
                        <i class="fas fa-credit-card text-2xl text-green-600"></i>
                    </label>

                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 transition-all">
                        <input type="radio" name="payment_method" value="bank_transfer" class="mr-3">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">โอนเงินผ่านธนาคาร</div>
                            <div class="text-sm text-gray-500">โอนเงินผ่านบัญชีธนาคาร</div>
                        </div>
                        <i class="fas fa-university text-2xl text-indigo-600"></i>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4">
                <a href="{{ route('tarot.category', $category->slug) }}"
                   class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold text-center transition-all">
                    ยกเลิก
                </a>
                <button onclick="processPayment()"
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition-all">
                    ชำระเงิน
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function processPayment() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const categoryId = {{ $category->id }};
    const spreadTypeId = {{ $spreadType->id }};
    const question = new URLSearchParams(window.location.search).get('question');

    Swal.fire({
        title: 'กำลังประมวลผล...',
        html: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('{{ route('tarot.payment.process') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            category_id: categoryId,
            spread_type_id: spreadTypeId,
            payment_method: paymentMethod,
            question: question
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'ชำระเงินสำเร็จ!',
                text: 'กำลังนำคุณไปยังหน้าผลการทำนาย',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = data.redirect_url;
            });
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
@endpush
@endsection
