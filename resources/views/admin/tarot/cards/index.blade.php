@extends('layouts.admin')

@section('title', 'จัดการไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-cards text-purple-600 mr-2"></i>
            จัดการไพ่ทาโร่ต์
        </h1>
        <a href="{{ route('admin.tarot.cards.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i> เพิ่มไพ่ใหม่
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รูป</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">หมู่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($cards as $card)
                <tr>
                    <td class="px-6 py-4">
                        <img src="{{ $card->image_url }}" alt="{{ $card->name_th }}" class="h-16 w-auto rounded">
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $card->name_th }}</div>
                        <div class="text-sm text-gray-500">{{ $card->name_en }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        {{ $card->type == 'major_arcana' ? 'Major Arcana' : 'Minor Arcana' }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        {{ $card->suit ?? '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($card->is_active)
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        <a href="{{ route('admin.tarot.cards.edit', $card->id) }}" class="text-blue-600 hover:text-blue-900">แก้ไข</a>
                        <form action="{{ route('admin.tarot.cards.destroy', $card->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('ต้องการลบไพ่นี้?')">ลบ</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">
            {{ $cards->links() }}
        </div>
    </div>
</div>
@endsection
