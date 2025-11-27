@extends('layouts.seller')

@section('title', 'จัดการแผนก - ' . ($store->store_name ?? 'ร้านค้า'))

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('seller.staff.index') }}"
               class="p-2 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการแผนก</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Add Department Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เพิ่มแผนกใหม่</h2>
            <form method="POST" action="{{ route('seller.staff.departments.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแผนก *</label>
                    <input type="text" name="name" required placeholder="เช่น ฝ่ายขาย, คลังสินค้า"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัส</label>
                    <input type="text" name="code" placeholder="SALES (ถ้าไม่ใส่ระบบจะสร้างให้)"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="2"
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <button type="submit"
                        class="w-full py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-lg transition font-medium">
                    + เพิ่มแผนก
                </button>
            </form>
        </div>

        {{-- Departments List --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">แผนกทั้งหมด ({{ $departments->count() }})</h2>
            @if($departments->count() > 0)
                <div class="space-y-3">
                    @foreach($departments as $dept)
                    <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $dept->name }}</p>
                            <p class="text-sm text-gray-500">{{ $dept->code }} • {{ $dept->employees_count }} พนักงาน</p>
                        </div>
                        <form method="POST" action="{{ route('seller.staff.departments.destroy', $dept) }}"
                              onsubmit="return confirm('ลบแผนก {{ $dept->name }} ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">ยังไม่มีแผนก</p>
            @endif
        </div>
    </div>
</div>
@endsection
