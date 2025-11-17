@extends('layouts.admin-v3')

@section('title', 'ประวัติการทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">ประวัติการทำนายทั้งหมด</h1>

    <div class="glass-fusion rounded-xl shadow overflow-x-auto" border border-white/20 dark:border-white/10>
        <table class="min-w-full">
            <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ผู้ใช้</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">หมวดหมู่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รูปแบบ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคา</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($readings as $reading)
                <tr>
                    <td class="px-6 py-4">#{{ $reading->id }}</td>
                    <td class="px-6 py-4">{{ $reading->user->name ?? 'Guest' }}</td>
                    <td class="px-6 py-4">{{ $reading->category->name_th }}</td>
                    <td class="px-6 py-4">{{ $reading->spreadType->name_th }}</td>
                    <td class="px-6 py-4">
                        @if($reading->is_free)
                            <span class="text-green-600">ฟรี</span>
                        @else
                            ฿{{ number_format($reading->amount_paid) }}
                        @endif
                    </td>
                    <td class="px-6 py-4">{{ $reading->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.tarot.readings.show', $reading->id) }}" class="text-blue-600 hover:text-blue-900">ดู</a>
                        <form action="{{ route('admin.tarot.readings.destroy', $reading->id) }}" method="POST" class="inline ml-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('ลบ?')">ลบ</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">
            {{ $readings->links() }}
        </div>
    </div>
</div>
@endsection
