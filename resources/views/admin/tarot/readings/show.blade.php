@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <h1 class="text-3xl font-bold mb-6">รายละเอียดการทำนาย #{{ $reading->id }}</h1>

    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm mb-2">ผู้ใช้</h3>
            <p class="text-xl font-bold">{{ $reading->user->name ?? 'Guest' }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm mb-2">หมวดหมู่</h3>
            <p class="text-xl font-bold">{{ $reading->category->name_th }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm mb-2">รูปแบบ</h3>
            <p class="text-xl font-bold">{{ $reading->spreadType->name_th }}</p>
        </div>
    </div>

    @if($reading->question)
    <div class="glass-fusion rounded-xl shadow p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="font-bold text-lg mb-2">คำถาม</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $reading->question }}</p>
    </div>
    @endif

    <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="font-bold text-lg mb-4">ไพ่ที่ถูกเลือก</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($reading->cards as $readingCard)
            <div class="border rounded-xl p-4">
                <img src="{{ $readingCard->card->image_url }}" alt="{{ $readingCard->card->getName() }}" class="w-full h-48 object-contain mb-3 {{ $readingCard->is_reversed ? 'transform rotate-180' : '' }}">
                <h4 class="font-bold">{{ $readingCard->card->getName() }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $readingCard->position_name }}</p>
                @if($readingCard->is_reversed)
                    <span class="text-xs text-red-600">(กลับหัว)</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
