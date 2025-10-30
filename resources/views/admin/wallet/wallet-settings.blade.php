@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบ Wallet')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">ตั้งค่าระบบ Wallet</h2>
                <p class="text-indigo-100 text-sm">จัดการการตั้งค่าค่าธรรมเนียม ภาษี และข้อจำกัดต่างๆ</p>
            </div>
            <div class="text-6xl">⚙️</div>
        </div>
    </div>

    <!-- Settings by Group -->
    @foreach($groups as $groupKey => $groupName)
        @if(isset($settings[$groupKey]) && $settings[$groupKey]->count() > 0)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-indigo-100">
                <h3 class="text-xl font-bold text-gray-900">{{ $groupName }}</h3>
            </div>

            <div class="p-6">
                <div class="space-y-6">
                    @foreach($settings[$groupKey] as $setting)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <form action="{{ route('admin.wallet-settings.update', $setting->key) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">
                                        {{ $setting->label }}
                                    </label>
                                    @if($setting->description)
                                        <p class="text-xs text-gray-600 mb-3">{{ $setting->description }}</p>
                                    @endif

                                    <div class="flex items-center space-x-3">
                                        @if($setting->type === 'boolean')
                                            <!-- Boolean Toggle -->
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="value" value="1" {{ $setting->value == '1' ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                                <span class="ml-3 text-sm font-medium text-gray-700">
                                                    {{ $setting->value == '1' ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                                </span>
                                            </label>

                                        @elseif($setting->type === 'percentage')
                                            <!-- Percentage Input -->
                                            <div class="flex items-center space-x-2">
                                                <input type="number" name="value" value="{{ $setting->value }}"
                                                       step="0.01" min="0" max="100"
                                                       class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                       required>
                                                <span class="text-gray-700 font-semibold">%</span>
                                            </div>

                                        @elseif($setting->type === 'number')
                                            <!-- Number Input -->
                                            <input type="number" name="value" value="{{ $setting->value }}"
                                                   step="0.01" min="0"
                                                   class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                   required>

                                        @elseif($setting->type === 'text')
                                            <!-- Text Input -->
                                            <input type="text" name="value" value="{{ $setting->value }}"
                                                   class="flex-1 max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                   required>

                                        @else
                                            <!-- Default String Input -->
                                            <input type="text" name="value" value="{{ $setting->value }}"
                                                   class="flex-1 max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        @endif

                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                                            บันทึก
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if($setting->validation_rules)
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-semibold">กฎการตรวจสอบ:</span> {{ $setting->validation_rules }}
                                </div>
                            @endif
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    @endforeach

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">เครื่องมือเพิ่มเติม</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Calculate Fee Preview -->
            <div class="border border-gray-200 rounded-lg p-4" x-data="{ amount: 1000, fee: 0 }">
                <h4 class="font-bold text-gray-900 mb-3">คำนวณค่าธรรมเนียม</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">จำนวนเงิน (THB)</label>
                        <input type="number" x-model="amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button x-on:click="fetch('{{ route('admin.wallet-settings.calculate-fee') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ amount: amount }) }).then(r => r.json()).then(data => { fee = data.fee })"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                        คำนวณ
                    </button>
                    <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
                        <span class="font-semibold">ค่าธรรมเนียม:</span>
                        <span class="text-lg font-bold text-indigo-600 ml-2" x-text="fee.toFixed(2)">0.00</span> THB
                    </div>
                </div>
            </div>

            <!-- Reset to Defaults -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-bold text-gray-900 mb-3">รีเซ็ตค่าเริ่มต้น</h4>
                <p class="text-sm text-gray-600 mb-3">คืนค่าการตั้งค่าทั้งหมดกลับเป็นค่าเริ่มต้นของระบบ</p>
                <form action="{{ route('admin.wallet-settings.reset') }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือว่าต้องการรีเซ็ตการตั้งค่าทั้งหมด?')">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-lg transition">
                        รีเซ็ตทั้งหมด
                    </button>
                </form>
            </div>

            <!-- Bulk Update -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-bold text-gray-900 mb-3">อัปเดตแบบกลุ่ม</h4>
                <p class="text-sm text-gray-600 mb-3">อัปเดตการตั้งค่าหลายรายการพร้อมกัน</p>
                <button x-on:click="alert('ฟีเจอร์นี้กำลังพัฒนา')" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 rounded-lg transition">
                    อัปเดตกลุ่ม
                </button>
            </div>
        </div>
    </div>

    <!-- Documentation -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start space-x-4">
            <div class="text-4xl">📚</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">คำอธิบายการตั้งค่า</h3>
                <div class="space-y-2 text-sm text-gray-700">
                    <p><strong>ข้อจำกัดและขีดจำกัด:</strong> กำหนดจำนวนเงินขั้นต่ำและสูงสุดในการถอนเงิน</p>
                    <p><strong>ค่าธรรมเนียม:</strong> กำหนดค่าธรรมเนียมแบบเปอร์เซ็นต์หรือคงที่สำหรับการถอนเงิน</p>
                    <p><strong>ภาษี:</strong> กำหนดเปอร์เซ็นต์ภาษีที่หักจากการถอนเงิน</p>
                    <p><strong>ช่องทางการชำระเงิน:</strong> เปิด/ปิดช่องทางการชำระเงินแต่ละประเภท</p>
                    <p><strong>ทั่วไป:</strong> การตั้งค่าอื่นๆ เช่น การอนุมัติอัตโนมัติ</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-submit form on toggle change
    document.querySelectorAll('input[type="checkbox"][name="value"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
</script>
@endpush
@endsection
