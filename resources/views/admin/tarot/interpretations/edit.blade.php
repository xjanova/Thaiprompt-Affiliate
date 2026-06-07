@extends('layouts.admin-v3')

@section('title', 'แก้ไขคำทำนาย - ' . $card->name_th)

@section('content')
<div class="container mx-auto px-4 py-8"
     x-data="{ activeTab: '{{ $categories->first()->id ?? 0 }}' }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="flex items-center gap-4">
            <img src="{{ $card->image_url }}" alt="{{ $card->name_th }}"
                 class="h-20 w-14 rounded-lg object-contain shadow-lg">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขคำทำนาย</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $card->name_th }} ({{ $card->name_en }})</p>
                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-xs font-medium
                    {{ $card->type == 'major_arcana' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                    {{ $card->type == 'major_arcana' ? 'Major Arcana' : 'Minor Arcana' }}
                    @if($card->suit) - {{ ucfirst($card->suit) }}@endif
                </span>
            </div>
        </div>
        <div class="flex gap-3">
            <form action="{{ route('admin.tarot.interpretations.copy-defaults', $card->id) }}" method="POST"
                  onsubmit="return confirm('คัดลอกคำทำนายพื้นฐานจากไพ่ไปทุกหมวด?')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    คัดลอกค่าเริ่มต้น
                </button>
            </form>
            <a href="{{ route('admin.tarot.interpretations.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Default Meanings Reference --}}
    <div class="glass-fusion rounded-xl shadow p-4 mb-6 border border-white/20 dark:border-white/10">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">คำทำนายพื้นฐาน (อ้างอิง):</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center text-green-800 dark:text-green-400 font-medium mb-1">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    หัวตั้ง
                </div>
                <p class="text-gray-700 dark:text-gray-300">{{ Str::limit($card->upright_meaning_th, 150) ?: 'ยังไม่มีข้อมูล' }}</p>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <div class="flex items-center text-red-800 dark:text-red-400 font-medium mb-1">
                    <svg class="w-4 h-4 mr-1 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    กลับหัว
                </div>
                <p class="text-gray-700 dark:text-gray-300">{{ Str::limit($card->reversed_meaning_th, 150) ?: 'ยังไม่มีข้อมูล' }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.tarot.interpretations.update', $card->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Category Tabs --}}
        <div class="mb-6">
            <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
                @foreach($categories as $category)
                    @php
                        $interpretation = $interpretations[$category->id] ?? null;
                        $hasContent = $interpretation && $interpretation->hasCustomInterpretation();
                    @endphp
                    <button type="button"
                            @click="activeTab = '{{ $category->id }}'"
                            :class="activeTab === '{{ $category->id }}'
                                ? 'bg-purple-600 text-white shadow-lg'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition">
                        <span class="mr-2">{{ $category->icon ?? '📖' }}</span>
                        {{ $category->name_th }}
                        @if($hasContent)
                            <span class="ml-2 w-2 h-2 bg-green-400 rounded-full"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tab Content --}}
        @foreach($categories as $category)
            @php
                $interpretation = $interpretations[$category->id] ?? new \App\Models\TarotCardInterpretation([
                    'card_id' => $card->id,
                    'category_id' => $category->id,
                ]);
            @endphp
            <div x-show="activeTab === '{{ $category->id }}'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="space-y-6">

                <input type="hidden" name="interpretations[{{ $category->id }}][category_id]" value="{{ $category->id }}">

                {{-- Category Header --}}
                <div class="glass-fusion rounded-xl shadow p-4 border border-white/20 dark:border-white/10"
                     style="border-left: 4px solid {{ $category->color }};">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">{{ $category->icon ?? '📖' }}</span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $category->name_th }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $category->description_th }}</p>
                        </div>
                    </div>
                </div>

                {{-- Upright Interpretation --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-green-200 dark:border-green-700 bg-green-50/30 dark:bg-green-900/10">
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        คำทำนายหัวตั้ง (Upright) - {{ $category->name_th }}
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาไทย</label>
                            <textarea name="interpretations[{{ $category->id }}][upright_interpretation_th]"
                                      rows="6"
                                      class="w-full rounded-xl border-green-300 dark:border-green-700 dark:bg-gray-800 dark:text-white focus:ring-green-500 focus:border-green-500"
                                      placeholder="กรอกคำทำนายหัวตั้งสำหรับหมวด {{ $category->name_th }}...">{{ $interpretation->upright_interpretation_th }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ (ไม่บังคับ)</label>
                            <textarea name="interpretations[{{ $category->id }}][upright_interpretation_en]"
                                      rows="6"
                                      class="w-full rounded-xl border-green-300 dark:border-green-700 dark:bg-gray-800 dark:text-white focus:ring-green-500 focus:border-green-500"
                                      placeholder="Enter upright interpretation for {{ $category->name_en ?? $category->name_th }}...">{{ $interpretation->upright_interpretation_en }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Reversed Interpretation --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-red-200 dark:border-red-700 bg-red-50/30 dark:bg-red-900/10">
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        คำทำนายกลับหัว (Reversed) - {{ $category->name_th }}
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาไทย</label>
                            <textarea name="interpretations[{{ $category->id }}][reversed_interpretation_th]"
                                      rows="6"
                                      class="w-full rounded-xl border-red-300 dark:border-red-700 dark:bg-gray-800 dark:text-white focus:ring-red-500 focus:border-red-500"
                                      placeholder="กรอกคำทำนายกลับหัวสำหรับหมวด {{ $category->name_th }}...">{{ $interpretation->reversed_interpretation_th }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ (ไม่บังคับ)</label>
                            <textarea name="interpretations[{{ $category->id }}][reversed_interpretation_en]"
                                      rows="6"
                                      class="w-full rounded-xl border-red-300 dark:border-red-700 dark:bg-gray-800 dark:text-white focus:ring-red-500 focus:border-red-500"
                                      placeholder="Enter reversed interpretation for {{ $category->name_en ?? $category->name_th }}...">{{ $interpretation->reversed_interpretation_en }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Advice --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-blue-200 dark:border-blue-700 bg-blue-50/30 dark:bg-blue-900/10">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        คำแนะนำพิเศษ - {{ $category->name_th }}
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาไทย</label>
                            <textarea name="interpretations[{{ $category->id }}][advice_th]"
                                      rows="4"
                                      class="w-full rounded-xl border-blue-300 dark:border-blue-700 dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="กรอกคำแนะนำสำหรับหมวด {{ $category->name_th }}...">{{ $interpretation->advice_th }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ (ไม่บังคับ)</label>
                            <textarea name="interpretations[{{ $category->id }}][advice_en]"
                                      rows="4"
                                      class="w-full rounded-xl border-blue-300 dark:border-blue-700 dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Enter advice for {{ $category->name_en ?? $category->name_th }}...">{{ $interpretation->advice_en }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Submit --}}
        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.tarot.interpretations.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition transform hover:scale-[1.02] shadow-lg">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกคำทำนายทั้งหมด
            </button>
        </div>
    </form>
</div>
@endsection
