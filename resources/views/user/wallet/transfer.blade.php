@extends('layouts.user')

@section('title', 'โอนเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">📤 โอนเงิน</h1>
            </div>
            <p class="text-blue-100">โอนเงินให้ผู้ใช้อื่นในระบบ</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-blue-100 text-sm mb-1">ยอดเงินที่สามารถโอนได้</p>
                <p class="text-3xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Transfer Form -->
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">แบบฟอร์มโอนเงิน</h2>

        <form method="POST" action="{{ route('user.wallet.transfer.submit') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Wallet Address ผู้รับ</label>
                <input type="text"
                       name="wallet_address"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ระบุ Wallet Address ของผู้รับ (เช่น TPW...)">
                <p class="text-xs text-gray-500 mt-1">Wallet Address เริ่มต้นด้วย TPW ตามด้วยอักขระ 16 ตัว</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="1"
                       max="{{ $wallet->balance }}"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ระบุจำนวนเงินที่ต้องการโอน">
                <p class="text-xs text-gray-500 mt-1">ยอดเงินสูงสุดที่โอนได้: ฿{{ number_format($wallet->balance, 2) }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">PIN กระเป๋าเงิน (6 หลัก)</label>
                <input type="password"
                       name="pin"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ระบุ PIN 6 หลัก">
                <p class="text-xs text-gray-500 mt-1">กรอก PIN เพื่อยืนยันการโอนเงิน</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">รายละเอียด (ถ้ามี)</label>
                <textarea name="description"
                          rows="3"
                          maxlength="255"
                          class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="รายละเอียดการโอนเงิน (ไม่เกิน 255 ตัวอักษร)"></textarea>
            </div>

            <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 mb-2">⚠️ ข้อควรระวัง</h4>
                <ul class="space-y-1 text-sm text-blue-900 dark:text-blue-100">
                    <li>• ตรวจสอบ Wallet Address ของผู้รับให้ถูกต้อง</li>
                    <li>• การโอนเงินจะเกิดขึ้นทันทีและไม่สามารถยกเลิกได้</li>
                    <li>• ไม่สามารถโอนเงินให้ตัวเองได้</li>
                    <li>• เก็บรักษา PIN ของคุณเป็นความลับ</li>
                </ul>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('user.wallet.index') }}"
                   class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold rounded-lg hover:bg-gray-300 transition text-center">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    ยืนยันการโอนเงิน
                </button>
            </div>
        </form>
    </div>

    <!-- How to Find Wallet Address -->
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">❓ จะหา Wallet Address ของผู้รับได้อย่างไร?</h3>

        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
                    1
                </div>
                <div>
                    <p class="font-semibold mb-1">ขอจากผู้รับโดยตรง</p>
                    <p class="text-gray-600 dark:text-gray-400">ผู้รับสามารถดู Wallet Address ได้ที่หน้ากระเป๋าเงินของตน</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
                    2
                </div>
                <div>
                    <p class="font-semibold mb-1">ตรวจสอบความถูกต้อง</p>
                    <p class="text-gray-600 dark:text-gray-400">Wallet Address จะเริ่มต้นด้วย "TPW" และมีความยาวทั้งหมด 19 ตัวอักษร</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
                    3
                </div>
                <div>
                    <p class="font-semibold mb-1">ตัวอย่าง</p>
                    <code class="px-3 py-1 bg-gray-100 rounded text-indigo-600">{{ $wallet->wallet_address }}</code>
                    <p class="text-gray-600 mt-1">นี่คือ Wallet Address ของคุณ</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transfers -->
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">📊 การโอนเงินล่าสุด</h3>
            <a href="{{ route('user.wallet.transactions') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="text-center text-gray-500 py-8">
            <span class="text-4xl block mb-2">💸</span>
            <p class="text-sm">คลิก "ดูทั้งหมด" เพื่อดูประวัติการโอนเงิน</p>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">💡 คำแนะนำ</h3>
        <ul class="space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ตรวจสอบ Wallet Address ของผู้รับให้ถูกต้องทุกครั้ง</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>การโอนเงินจะเกิดขึ้นทันทีหลังยืนยัน</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ตรวจสอบยอดเงินคงเหลือก่อนทำรายการ</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>รักษา PIN ของคุณไว้เป็นความลับ</span>
            </li>
        </ul>
    </div>
</div>
@endsection
