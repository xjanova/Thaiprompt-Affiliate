@extends('layouts.admin')

@section('title', 'แก้ไขไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">แก้ไขไพ่ทาโร่ต์</h1>

    <form action="{{ route('admin.tarot.cards.update', $card->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อภาษาไทย *</label>
                <input type="text" name="name_th" value="{{ $card->name_th }}" required class="w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อภาษาอังกฤษ *</label>
                <input type="text" name="name_en" value="{{ $card->name_en }}" required class="w-full rounded-lg border-gray-300">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพปัจจุบัน</label>
            <img src="{{ $card->image_url }}" alt="{{ $card->name_th }}" class="h-48 mb-2">
            <input type="file" name="image" accept="image/*" class="w-full">
            <p class="text-sm text-gray-500 mt-1">เลือกรูปใหม่หากต้องการเปลี่ยน</p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">คำสำคัญ (ไทย) - คั่นด้วยเครื่องหมายจุลภาค</label>
            <input type="text" name="keywords_th" value="{{ is_array($card->keywords_th) ? implode(', ', $card->keywords_th) : $card->keywords_th }}" class="w-full rounded-lg border-gray-300" placeholder="ความรัก, ความสุข, โอกาสใหม่">
            <p class="text-xs text-gray-500 mt-1">ตัวอย่าง: ความรัก, ความสุข, โอกาสใหม่</p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">คำสำคัญ (อังกฤษ) - คั่นด้วยเครื่องหมายจุลภาค</label>
            <input type="text" name="keywords_en" value="{{ is_array($card->keywords_en) ? implode(', ', $card->keywords_en) : $card->keywords_en }}" class="w-full rounded-lg border-gray-300" placeholder="love, happiness, new beginnings">
            <p class="text-xs text-gray-500 mt-1">Example: love, happiness, new beginnings</p>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย (ไทย)</label>
                <textarea name="description_th" rows="3" class="w-full rounded-lg border-gray-300">{{ $card->description_th }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย (อังกฤษ)</label>
                <textarea name="description_en" rows="3" class="w-full rounded-lg border-gray-300">{{ $card->description_en }}</textarea>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-green-800 mb-3">ความหมายหัวตั้ง (Upright)</h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาไทย</label>
                    <textarea name="upright_meaning_th" rows="4" class="w-full rounded-lg border-gray-300">{{ $card->upright_meaning_th }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาอังกฤษ</label>
                    <textarea name="upright_meaning_en" rows="4" class="w-full rounded-lg border-gray-300">{{ $card->upright_meaning_en }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-red-800 mb-3">ความหมายกลับหัว (Reversed)</h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาไทย</label>
                    <textarea name="reversed_meaning_th" rows="4" class="w-full rounded-lg border-gray-300">{{ $card->reversed_meaning_th }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาอังกฤษ</label>
                    <textarea name="reversed_meaning_en" rows="4" class="w-full rounded-lg border-gray-300">{{ $card->reversed_meaning_en }}</textarea>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ $card->is_active ? 'checked' : '' }} class="rounded border-gray-300">
                <span class="ml-2 text-sm text-gray-700">ใช้งาน</span>
            </label>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
                บันทึก
            </button>
            <a href="{{ route('admin.tarot.cards.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
