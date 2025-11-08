@extends('layouts.admin')

@section('title', 'เพิ่มไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">เพิ่มไพ่ทาโร่ต์ใหม่</h1>

    <form action="{{ route('admin.tarot.cards.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        @csrf

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อภาษาไทย *</label>
                <input type="text" name="name_th" required class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อภาษาอังกฤษ *</label>
                <input type="text" name="name_en" required class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ประเภท *</label>
                <select name="type" required class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                    <option value="major_arcana">Major Arcana</option>
                    <option value="minor_arcana">Minor Arcana</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">หมู่ (Minor)</label>
                <select name="suit" class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                    <option value="">-- ไม่ระบุ --</option>
                    <option value="wands">Wands</option>
                    <option value="cups">Cups</option>
                    <option value="swords">Swords</option>
                    <option value="pentacles">Pentacles</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเลข</label>
                <input type="number" name="number" class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพไพ่</label>
            <input type="file" name="image" accept="image/*" class="w-full">
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ความหมายหัวตั้ง (ไทย)</label>
                <textarea name="upright_meaning_th" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ความหมายกลับหัว (ไทย)</label>
                <textarea name="reversed_meaning_th" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
            </div>
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
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
