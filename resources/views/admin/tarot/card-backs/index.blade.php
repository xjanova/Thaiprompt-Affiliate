@extends('layouts.admin')

@section('title', 'รูปหลังไพ่')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">จัดการรูปหลังไพ่</h1>
        <button onclick="document.getElementById('upload-form').classList.toggle('hidden')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-upload mr-2"></i> อัพโหลดรูปใหม่
        </button>
    </div>

    <div id="upload-form" class="hidden bg-white rounded-lg shadow p-6 mb-6">
        <form action="{{ route('admin.tarot.card-backs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">ชื่อ *</label>
                <input type="text" name="name" required class="w-full rounded-lg border-gray-300">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">รูปภาพ *</label>
                <input type="file" name="image" required accept="image/*" class="w-full">
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_default" value="1" class="rounded">
                    <span class="ml-2 text-sm">ตั้งเป็นรูปเริ่มต้น</span>
                </label>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">อัพโหลด</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
        @foreach($cardBacks as $cardBack)
        <div class="bg-white rounded-lg shadow p-4">
            <img src="{{ $cardBack->image_url }}" alt="{{ $cardBack->name }}" class="w-full h-48 object-cover rounded mb-3">
            <h3 class="font-semibold text-sm mb-2">{{ $cardBack->name }}</h3>
            @if($cardBack->is_default)
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Default</span>
            @else
                <form action="{{ route('admin.tarot.card-backs.set-default', $cardBack->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">ตั้งเป็นเริ่มต้น</button>
                </form>
            @endif
            @if(!$cardBack->is_default)
                <form action="{{ route('admin.tarot.card-backs.destroy', $cardBack->id) }}" method="POST" class="inline ml-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:text-red-800" onclick="return confirm('ลบ?')">ลบ</button>
                </form>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
