@extends('layouts.seller')

@section('title', 'บัญชีธนาคาร - รับชำระเงินตรง')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">บัญชีธนาคาร</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการบัญชีธนาคารที่ลูกค้าจะโอนเงินเข้าโดยตรง</p>
        </div>
        <a href="{{ route('seller.sms-gateway.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
            &larr; กลับ
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-green-800 dark:text-green-300">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- ฟอร์มเพิ่มบัญชี --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-8" x-data="{ showForm: false }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เพิ่มบัญชีธนาคาร</h3>
            <button @click="showForm = !showForm"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                <span x-text="showForm ? 'ยกเลิก' : '+ เพิ่มบัญชี'"></span>
            </button>
        </div>

        <form action="{{ route('seller.sms-gateway.bank-accounts.create') }}" method="POST"
              x-show="showForm" x-transition class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ธนาคาร</label>
                    <select name="bank_code" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">-- เลือกธนาคาร --</option>
                        @foreach(config('smschecker.supported_banks', []) as $code => $name)
                        <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบัญชี</label>
                    <input type="text" name="account_name" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="ชื่อ-นามสกุลในบัญชี">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขที่บัญชี</label>
                    <input type="text" name="account_number" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="เลขที่บัญชีธนาคาร">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สาขา (ไม่บังคับ)</label>
                    <input type="text" name="branch"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="ชื่อสาขา">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PromptPay (ไม่บังคับ)</label>
                    <input type="text" name="promptpay_id"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="เบอร์โทร หรือ เลขบัตรประชาชน">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท PromptPay</label>
                    <select name="promptpay_type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">-- ไม่ใช้ --</option>
                        <option value="phone">เบอร์โทรศัพท์</option>
                        <option value="citizen_id">เลขบัตรประชาชน</option>
                        <option value="ewallet">e-Wallet</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_primary" value="1" id="is_primary"
                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                <label for="is_primary" class="text-sm text-gray-700 dark:text-gray-300">ตั้งเป็นบัญชีหลัก</label>
            </div>
            <div>
                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    บันทึกบัญชี
                </button>
            </div>
        </form>
    </div>

    {{-- รายการบัญชี --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">บัญชีธนาคารของร้าน ({{ $accounts->count() }})</h3>
        </div>

        @forelse($accounts as $account)
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $account->bank_display_name }}</span>
                        @if($account->is_primary)
                        <span class="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 px-2 py-0.5 rounded-full">หลัก</span>
                        @endif
                        @if(! $account->is_active)
                        <span class="text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 px-2 py-0.5 rounded-full">ปิดใช้งาน</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $account->account_name }} | {{ $account->account_number }}
                    </p>
                    @if($account->promptpay_id)
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                        PromptPay: {{ $account->promptpay_id }}
                    </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('seller.sms-gateway.bank-accounts.delete', $account->id) }}" method="POST"
                          onsubmit="return confirm('ต้องการลบบัญชีนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm">ลบ</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
            <p>ยังไม่มีบัญชีธนาคาร</p>
            <p class="text-sm mt-1">เพิ่มบัญชีด้านบนเพื่อให้ลูกค้าสามารถโอนเงินเข้าร้านคุณได้โดยตรง</p>
        </div>
        @endforelse
    </div>

    {{-- คำเตือน --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mt-6">
        <p class="text-sm text-amber-800 dark:text-amber-300">
            <strong>สำคัญ:</strong> ตรวจสอบให้แน่ใจว่าบัญชีธนาคารที่เพิ่มถูกต้อง เพราะลูกค้าจะโอนเงินเข้าบัญชีเหล่านี้โดยตรง
        </p>
    </div>
</div>
@endsection
