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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ภาพ/วีดีโอ</th>
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
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl">{{ $slider->getMediaTypeIcon() }}</span>
                                        <span class="text-sm font-medium text-gray-700">{{ $slider->getMediaTypeLabel() }}</span>
                                    </div>
                                    @if($slider->isVideo())
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ ucfirst($slider->video_type ?? '') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $thumbnail = $slider->getThumbnailUrl();
                                    @endphp
                                    @if($thumbnail)
                                        <div class="relative h-16 w-24 rounded overflow-hidden group">
                                            <img src="{{ asset($thumbnail) }}"
                                                 alt="{{ $slider->title }}"
                                                 class="h-full w-full object-cover"
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27100%27 height=%27100%27%3E%3Crect fill=%27%23e5e7eb%27 width=%27100%27 height=%27100%27/%3E%3Ctext fill=%27%239ca3af%27 font-family=%27sans-serif%27 font-size=%2712%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dy=%27.3em%27%3E{{ $slider->isVideo() ? '🎥' : '🖼️' }}%3C/text%3E%3C/svg%3E';">
                                            @if($slider->isVideo())
                                                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                                                    <span class="text-white text-2xl">▶</span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="h-16 w-24 bg-gray-200 rounded flex items-center justify-center">
                                            <span class="text-gray-400 text-xs">{{ $slider->isVideo() ? '🎥' : 'ไม่มีรูป' }}</span>
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
                                    @elseif($slider->isVideo() && $slider->video_url)
                                        <div class="text-sm text-purple-600 truncate max-w-xs">
                                            <a href="{{ $slider->video_url }}" target="_blank" class="hover:underline">
                                                {{ Str::limit($slider->video_url, 30) }}
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
                        <li><strong>รูปภาพ:</strong> แนะนำขนาด 1920x600px, สูงสุด 5MB (JPG, PNG, GIF)</li>
                        <li><strong>วีดีโอ:</strong> รองรับ YouTube, Vimeo, อัพโหลดเอง (MP4, WebM, OGG สูงสุด 50MB)</li>
                        <li>สไลด์จะแสดงตามลำดับที่กำหนด</li>
                        <li>เฉพาะสไลด์ที่เปิดใช้งานเท่านั้นที่จะแสดงในหน้าแรก</li>
                        <li>วีดีโอจะเล่นจบแล้วสไลด์ต่ออัตโนมัติ (ยกเว้นถ้าเปิด loop)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
