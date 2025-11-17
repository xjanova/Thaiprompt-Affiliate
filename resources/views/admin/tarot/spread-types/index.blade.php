@extends('layouts.admin-v3')

@section('title', 'รูปแบบการเปิดไพ่')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">รูปแบบการเปิดไพ่</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($spreadTypes as $spread)
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-xl font-bold mb-2">{{ $spread->name_th }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-3">{{ $spread->description_th }}</p>
            <div class="flex items-center justify-between">
                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">
                    {{ $spread->card_count }} ใบ
                </span>
                @if($spread->is_active)
                    <span class="text-green-600 text-sm">● Active</span>
                @else
                    <span class="text-gray-400 text-sm">● Inactive</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
