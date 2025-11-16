@extends('layouts.user-arrow-x')

@section('title', 'สร้างลิงก์เชิญ')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">สร้างลิงก์เชิญสมาชิก</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">สร้างลิงก์เพื่อเชิญเพื่อนของคุณสมัครผ่าน LINE</p>
            </div>
            <a href="{{ route('user.prospects.index') }}"
               class="px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-700 dark:hover:bg-gray-600 transition">
                ← กลับ
            </a>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg shadow-md p-8 border border-indigo-200 dark:border-indigo-800">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-12 h-12 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-6 flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">วิธีการใช้งาน</h2>
                <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex items-start">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold mr-3">1</span>
                        <p>กดปุ่ม <strong>"สร้างลิงก์เชิญ"</strong> ด้านล่างเพื่อสร้างลิงก์พิเศษสำหรับเชิญเพื่อนของคุณ</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold mr-3">2</span>
                        <p>แชร์ลิงก์หรือ QR Code ที่ได้ให้เพื่อนของคุณผ่าน LINE, Facebook หรือช่องทางอื่นๆ</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold mr-3">3</span>
                        <p>เมื่อเพื่อนคุณคลิกลิงก์ ระบบจะนำไปสู่ LINE Bot เพื่อเริ่มกระบวนการสมัครสมาชิก</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold mr-3">4</span>
                        <p>ติดตามสถานะการสมัครของเพื่อนคุณได้ที่หน้า <strong>"ผู้มุ่งหวังของฉัน"</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Features --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">รายได้จากการแนะนำ</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">รับค่าคอมมิชชั่นเมื่อเพื่อนที่คุณแนะนำสมัครสมาชิกสำเร็จ</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full mb-4">
                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">ติดตามสถานะ</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">ติดตามความคืบหน้าของผู้มุ่งหวังแต่ละคนได้แบบ Real-time</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full mb-4">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">สร้างทีมของคุณ</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">สร้างเครือข่ายและทีมของคุณเองผ่านระบบ MLM</p>
        </div>
    </div>

    {{-- Create Button --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
        <div class="text-center">
            <svg class="mx-auto w-20 h-20 text-indigo-600 dark:text-indigo-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">พร้อมเริ่มต้นแล้วใช่ไหม?</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">คลิกปุ่มด้านล่างเพื่อสร้างลิงก์เชิญพิเศษของคุณ</p>

            <form method="POST" action="{{ route('user.prospects.store') }}" class="inline-block">
                @csrf
                <button type="submit"
                        class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 dark:from-indigo-500 dark:to-purple-500 dark:hover:from-indigo-600 dark:hover:to-purple-600 text-white text-lg font-bold rounded-lg transition shadow-lg hover:shadow-xl flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    สร้างลิงก์เชิญเลย
                </button>
            </form>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                * ลิงก์เชิญจะมีอายุ 7 วัน หลังจากนั้นจะหมดอายุอัตโนมัติ
            </p>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-1">หมายเหตุสำคัญ</h3>
                <ul class="text-sm text-yellow-800 dark:text-yellow-200 space-y-1 list-disc list-inside">
                    <li>แต่ละลิงก์เชิญสามารถใช้ได้กับบุคคลหนึ่งคนเท่านั้น</li>
                    <li>คุณสามารถสร้างลิงก์เชิญได้ไม่จำกัดจำนวน</li>
                    <li>ลิงก์จะหมดอายุหลังจาก 7 วัน หรือเมื่อมีการสมัครสำเร็จ</li>
                    <li>ผู้ที่คลิกลิงก์จะต้องเพิ่มเพื่อน LINE Bot ของเราเพื่อเริ่มสมัคร</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
