@extends('layouts.admin-v3')

@section('title', 'จัดการคำทำนายตามหมวดหมู่')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการคำทำนายตามหมวดหมู่</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">แก้ไขคำทำนายของแต่ละไพ่สำหรับทุกหมวดหมู่</p>
        </div>
        <div class="flex gap-3">
            <form action="{{ route('admin.tarot.interpretations.copy-all-defaults') }}" method="POST"
                  onsubmit="return confirm('คัดลอกคำทำนายพื้นฐานจากไพ่ไปทุกหมวด? การดำเนินการนี้จะแทนที่คำทำนายที่มีอยู่')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    คัดลอกค่าเริ่มต้นทั้งหมด
                </button>
            </form>
            <a href="{{ route('admin.tarot.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Categories Legend --}}
    <div class="glass-fusion rounded-xl shadow p-4 mb-6 border border-white/20 dark:border-white/10">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">หมวดหมู่:</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($categories as $category)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                      style="background-color: {{ $category->color }}20; color: {{ $category->color }};">
                    <span class="mr-1">{{ $category->icon ?? '📖' }}</span>
                    {{ $category->name_th }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Cards Table --}}
    <div class="glass-fusion rounded-xl shadow overflow-hidden border border-white/20 dark:border-white/10">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ไพ่
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            สถานะคำทำนาย
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($cards as $card)
                        @php
                            $interpretationCount = $card->interpretations->filter(function($i) {
                                return !empty($i->upright_interpretation_th) || !empty($i->reversed_interpretation_th);
                            })->count();
                            $totalCategories = $categories->count();
                            $percentage = $totalCategories > 0 ? round(($interpretationCount / $totalCategories) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-8">
                                        <img src="{{ $card->image_url }}" alt="{{ $card->name_th }}"
                                             class="h-12 w-8 rounded object-contain shadow">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $card->name_th }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $card->name_en }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $card->type == 'major_arcana' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                    {{ $card->type == 'major_arcana' ? 'Major Arcana' : 'Minor Arcana' }}
                                </span>
                                @if($card->suit)
                                    <span class="ml-1 inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ ucfirst($card->suit) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center gap-1 mb-1">
                                        @foreach($categories as $category)
                                            @php
                                                $hasInterpretation = $card->interpretations->first(function($i) use ($category) {
                                                    return $i->category_id == $category->id
                                                        && (!empty($i->upright_interpretation_th) || !empty($i->reversed_interpretation_th));
                                                });
                                            @endphp
                                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px]
                                                {{ $hasInterpretation ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-400' }}"
                                                  title="{{ $category->name_th }}: {{ $hasInterpretation ? 'มีคำทำนาย' : 'ยังไม่มี' }}">
                                                {{ $hasInterpretation ? '✓' : '·' }}
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="w-full max-w-[100px] bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all
                                            {{ $percentage >= 100 ? 'bg-green-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $interpretationCount }}/{{ $totalCategories }} หมวด
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.tarot.interpretations.edit', $card->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        แก้ไขคำทำนาย
                                    </a>
                                    <form action="{{ route('admin.tarot.interpretations.copy-defaults', $card->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition"
                                                title="คัดลอกค่าเริ่มต้นจากไพ่ไปทุกหมวด">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $cards->links() }}
    </div>
</div>
@endsection
