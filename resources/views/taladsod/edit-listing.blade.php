@extends('layouts.taladsod')
@section('title', 'แก้ไข ' . $listing->title . ' - ตลาดสดไทยพร้อม')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('taladsod.seller.dashboard') }}" class="inline-flex items-center text-green-600 hover:text-green-700 mb-4">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปแผงควบคุม
    </a>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">แก้ไขสินค้า</h1>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl">
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('taladsod.listing.update', $listing) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <!-- ชื่อสินค้า -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อสินค้า <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $listing->title) }}" required maxlength="200"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- คำอธิบาย -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3" maxlength="2000"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">{{ old('description', $listing->description) }}</textarea>
                </div>

                <!-- หมวดหมู่ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่ <span class="text-red-500">*</span></label>
                    <select name="category_id" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $listing->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->icon }} {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ราคา + หน่วย -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคา (บาท) <span class="text-red-500">*</span></label>
                        <input type="number" name="price" value="{{ old('price', $listing->price) }}" required min="0" step="0.01"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หน่วย <span class="text-red-500">*</span></label>
                        <input type="text" name="unit" value="{{ old('unit', $listing->unit) }}" required maxlength="50" placeholder="กก., ถุง, กำ"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <!-- จำนวน -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนที่มี <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity_available" value="{{ old('quantity_available', $listing->quantity_available) }}" required min="0"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Toggles -->
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_organic" value="0">
                        <input type="checkbox" name="is_organic" value="1" {{ old('is_organic', $listing->is_organic) ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">สินค้าอินทรีย์</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_available" value="0">
                        <input type="checkbox" name="is_available" value="1" {{ old('is_available', $listing->is_available) ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">เปิดขาย</span>
                    </label>
                </div>

                <!-- Current Images -->
                @if($listing->images && count($listing->images) > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพปัจจุบัน</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach($listing->images as $img)
                                <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <img src="{{ $img }}" alt="" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Submit -->
            <div class="mt-8 flex items-center gap-4">
                <button type="submit" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium transition shadow-lg">
                    บันทึกการแก้ไข
                </button>
                <a href="{{ route('taladsod.seller.dashboard') }}" class="px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>
@endsection