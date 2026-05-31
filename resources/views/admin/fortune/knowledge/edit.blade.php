@extends('layouts.admin-v3')

@section('title', 'แก้ไของค์ความรู้ #' . $item->id)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🧠 แก้ไของค์ความรู้ #{{ $item->id }}</h1>
        <a href="{{ route('admin.fortune.knowledge.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">&larr; กลับคลังความรู้</a>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.fortune.knowledge.update', $item) }}"
          class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
        @csrf
        @method('PUT')
        @include('admin.fortune.knowledge._form', ['item' => $item])
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">💾 บันทึกการแก้ไข</button>
            <a href="{{ route('admin.fortune.knowledge.index') }}" class="px-6 py-2.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection
