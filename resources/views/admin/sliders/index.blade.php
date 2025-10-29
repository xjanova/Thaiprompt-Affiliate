@extends('layouts.admin')

@section('title', 'จัดการสไลด์')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">จัดการสไลด์หน้าแรก</h2>
            <p class="text-gray-600 mt-1">จัดการรูปภาพสไลด์ที่แสดงในหน้าแรก (แนะนำ 5 รูป)</p>
        </div>
        <a href="{{ route('admin.sliders.create') }}" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md">
            เพิ่มสไลด์
        </a>
    </div>

    <!-- Sliders List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($sliders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รูปภาพ</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หัวข้อ</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คำอธิบาย</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sliders as $slider)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $slider->order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($slider->image)
                                        <img src="{{ asset($slider->image) }}" alt="{{ $slider->title }}" class="h-16 w-24 object-cover rounded">
                                    @else
                                        <div class="h-16 w-24 bg-gray-200 rounded flex items-center justify-center">
                                            <span class="text-gray-400 text-xs">ไม่มีรูป</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $slider->title ?: '-' }}</div>
                                    @if($slider->link)
                                        <div class="text-sm text-blue-600 truncate max-w-xs">
                                            <a href="{{ $slider->link }}" target="_blank" class="hover:underline">
                                                {{ $slider->link }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600 truncate max-w-xs">
                                        {{ $slider->description ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $slider->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $slider->is_active ? 'ใช้งาน' : 'ปิด' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="text-indigo-600 hover:text-indigo-900" title="แก้ไข">
                                            ✏️
                                        </a>
                                        <form method="POST" action="{{ route('admin.sliders.destroy', $slider) }}" class="inline" onsubmit="return confirm('คุณแน่ใจที่จะลบสไลด์นี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="ลบ">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📸</div>
                <p class="text-gray-500 text-lg">ยังไม่มีสไลด์</p>
                <a href="{{ route('admin.sliders.create') }}" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                    เพิ่มสไลด์แรก
                </a>
            </div>
        @endif
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <span class="text-2xl">💡</span>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">คำแนะนำ</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>แนะนำให้ใช้รูปภาพขนาด 1920x600 พิกเซล</li>
                        <li>ไฟล์รูปภาพควรมีขนาดไม่เกิน 5 MB</li>
                        <li>รองรับไฟล์ JPG, PNG, GIF</li>
                        <li>สไลด์จะแสดงตามลำดับที่กำหนด</li>
                        <li>เฉพาะสไลด์ที่เปิดใช้งานเท่านั้นที่จะแสดงในหน้าแรก</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
