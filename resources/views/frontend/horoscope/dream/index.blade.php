{{-- หน้าหลักทำนายฝัน — ค้นหา + หมวดหมู่ + เล่าฝันให้ AI ฟัง --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="dreamApp()" x-init="init()">

    {{-- ==================== Hero + Search ==================== --}}
    <section class="relative py-12 md:py-16 overflow-hidden">
        {{-- ภาพประกอบพระจันทร์+สัญลักษณ์ความฝัน (เจนเอง) --}}
        <x-art.backdrop image="cos-dream" tone="dark" :opacity="0.5" mask="vignette" />
        {{-- พื้นหลังเอฟเฟกต์เมฆ/ดวงจันทร์ --}}
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-10 right-10 w-24 h-24 bg-amber-300/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 left-10 w-32 h-32 bg-indigo-400/10 rounded-full blur-3xl" style="animation: float 6s ease-in-out infinite;"></div>
            <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-purple-400/10 rounded-full blur-2xl" style="animation: float 8s ease-in-out infinite 2s;"></div>
        </div>

        <div class="container mx-auto px-4 text-center relative">
            {{-- ไอคอน --}}
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-600/30 to-violet-600/30 border border-indigo-500/30 backdrop-blur-lg shadow-2xl shadow-indigo-500/20"
                     style="animation: float 4s ease-in-out infinite;">
                    <span class="text-5xl filter drop-shadow-[0_0_20px_rgba(129,140,248,0.6)]">💭</span>
                </div>
            </div>

            <h1 class="text-3xl md:text-5xl font-black mb-3">
                <span class="shimmer-text">ทำนายฝัน</span>
            </h1>
            <p class="text-purple-200/70 text-lg mb-2">ค้นหาความหมายของฝัน + เลขเด็ดจากความฝัน</p>
            <p class="text-purple-300/50 text-sm max-w-lg mx-auto mb-8">
                ค้นหาสัญลักษณ์จากพจนานุกรม {{ number_format($totalSymbols) }}+ รายการ หรือเล่าฝันให้ AI ทำนายแบบละเอียด
            </p>

            {{-- สถิติ --}}
            <div class="flex justify-center gap-6 mb-8">
                <div class="text-center">
                    <div class="text-2xl font-black text-white">{{ number_format($totalSymbols) }}+</div>
                    <div class="text-purple-300/50 text-xs">สัญลักษณ์</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-black text-white">{{ number_format($totalReadings) }}</div>
                    <div class="text-purple-300/50 text-xs">ครั้งที่ทำนาย</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-black text-white">{{ $todayReadings }}</div>
                    <div class="text-purple-300/50 text-xs">วันนี้</div>
                </div>
            </div>

            {{-- ช่องค้นหา --}}
            <div class="max-w-xl mx-auto relative">
                <div class="relative">
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="handleSearch()"
                           @keydown.escape="showSearchResults = false"
                           @focus="if(searchResults.length > 0) showSearchResults = true"
                           placeholder="🔍 ค้นหาสัญลักษณ์ในฝัน เช่น งู, น้ำ, ไฟ, ทอง..."
                           class="w-full px-6 py-4 bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl text-white placeholder-purple-300/40 text-lg focus:outline-none focus:border-indigo-500/60 focus:ring-2 focus:ring-indigo-500/20 transition-all pr-12">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2">
                        <svg x-show="!isSearching" class="w-5 h-5 text-purple-300/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <svg x-show="isSearching" class="w-5 h-5 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </div>
                </div>

                {{-- ผลค้นหา Dropdown --}}
                <div x-show="showSearchResults && searchResults.length > 0"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click.away="showSearchResults = false"
                     class="absolute top-full mt-2 w-full bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl shadow-black/50 overflow-hidden z-50 max-h-96 overflow-y-auto">

                    <template x-for="item in searchResults" :key="item.id">
                        <div class="px-5 py-3.5 hover:bg-white/5 border-b border-white/5 cursor-pointer transition-all text-left"
                             @click="showSymbolDetail(item)">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg" x-text="item.category_icon || '💭'"></span>
                                    <div>
                                        <div class="text-white font-bold text-sm" x-text="item.keyword"></div>
                                        <div class="text-purple-300/50 text-xs line-clamp-1" x-text="item.meaning"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs" x-text="item.omen_icon"></span>
                                    <template x-if="item.lucky_numbers && item.lucky_numbers.length > 0">
                                        <div class="flex gap-1">
                                            <template x-for="num in item.lucky_numbers.slice(0,3)" :key="num">
                                                <span class="px-1.5 py-0.5 bg-amber-500/20 text-amber-300 rounded text-[10px] font-mono font-bold" x-text="num"></span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="px-5 py-2.5 bg-white/5 text-center">
                        <span class="text-purple-300/40 text-xs" x-text="'พบ ' + searchResults.length + ' รายการ'"></span>
                    </div>
                </div>

                {{-- ไม่พบผลค้นหา --}}
                <div x-show="showSearchResults && searchResults.length === 0 && searchQuery.length >= 1 && !isSearching"
                     x-transition
                     class="absolute top-full mt-2 w-full bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-6 text-center z-50">
                    <div class="text-3xl mb-2">🔍</div>
                    <div class="text-white text-sm font-bold">ไม่พบสัญลักษณ์ "<span x-text="searchQuery"></span>"</div>
                    <div class="text-purple-300/50 text-xs mt-1">ลองเล่าฝันให้ AI ทำนายด้านล่าง</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== Modal แสดงรายละเอียดสัญลักษณ์ ==================== --}}
    <div x-show="selectedSymbol"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="selectedSymbol = null">
        <div x-show="selectedSymbol"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-3xl p-6 md:p-8 max-w-md w-full shadow-2xl">

            <template x-if="selectedSymbol">
                <div>
                    {{-- หัวข้อ --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl" x-text="selectedSymbol.category_icon || '💭'"></span>
                            <h3 class="text-white text-xl font-black" x-text="'ฝันเห็น' + selectedSymbol.keyword"></h3>
                        </div>
                        <button @click="selectedSymbol = null" class="text-purple-300/50 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- ลางดี/ร้าย --}}
                    <div class="mb-4">
                        <span x-show="selectedSymbol.is_good_omen"
                              class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-sm font-bold">
                            ✅ ลางดี
                        </span>
                        <span x-show="!selectedSymbol.is_good_omen"
                              class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500/20 text-amber-300 rounded-full text-sm font-bold">
                            ⚠️ ควรระวัง
                        </span>
                    </div>

                    {{-- ความหมาย --}}
                    <div class="bg-white/5 rounded-2xl p-4 mb-4">
                        <h4 class="text-purple-300/60 text-xs font-bold mb-2">ความหมาย</h4>
                        <p class="text-purple-100 text-sm leading-relaxed" x-text="selectedSymbol.meaning"></p>
                    </div>

                    {{-- เลขเด็ด --}}
                    <template x-if="selectedSymbol.lucky_numbers && selectedSymbol.lucky_numbers.length > 0">
                        <div class="mb-4">
                            <h4 class="text-purple-300/60 text-xs font-bold mb-2">🎯 เลขเด็ด</h4>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="num in selectedSymbol.lucky_numbers" :key="num">
                                    <span class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 bg-gradient-to-br from-amber-500/30 to-orange-500/30 border border-amber-500/30 rounded-xl text-amber-200 font-mono font-black text-lg shadow-lg shadow-amber-500/10"
                                          x-text="num"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- ความเข้มข้น --}}
                    <div class="flex items-center gap-2 text-purple-300/50 text-xs">
                        <span>ความเข้มข้น:</span>
                        <div class="flex gap-0.5">
                            <template x-for="i in 5" :key="i">
                                <div class="w-4 h-1.5 rounded-full transition"
                                     :class="i <= selectedSymbol.intensity ? 'bg-indigo-500' : 'bg-white/10'"></div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ==================== หมวดหมู่ Grid ==================== --}}
    <section class="container mx-auto px-4 mb-10">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <span class="text-2xl">📂</span> หมวดหมู่ความฝัน
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($categories as $cat)
                <a href="{{ route('horoscope.dream.category', $cat->slug) }}"
                   class="group bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10 hover:bg-white/10 hover:border-white/20 transition-all duration-300 text-center hover:-translate-y-1 hover:shadow-lg"
                   style="hover:shadow-color: {{ $cat->color_hex }}30;">
                    <div class="text-3xl mb-2 group-hover:scale-110 transition-transform duration-300">{{ $cat->icon }}</div>
                    <div class="text-white text-sm font-bold mb-0.5">{{ $cat->name_th }}</div>
                    <div class="text-purple-300/40 text-[10px]">{{ $cat->dream_symbols_count }} สัญลักษณ์</div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ==================== สัญลักษณ์ยอดนิยม ==================== --}}
    @if($popularSymbols->isNotEmpty())
    <section class="container mx-auto px-4 mb-10">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <span class="text-2xl">🔥</span> สัญลักษณ์ยอดนิยม
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($popularSymbols as $symbol)
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition-all cursor-pointer"
                     @click="showSymbolDetail({
                         id: {{ $symbol->id }},
                         keyword: '{{ addslashes($symbol->keyword_th) }}',
                         meaning: '{{ addslashes($symbol->meaning_th) }}',
                         lucky_numbers: {{ json_encode($symbol->lucky_numbers ?? []) }},
                         is_good_omen: {{ $symbol->is_good_omen ? 'true' : 'false' }},
                         intensity: {{ $symbol->intensity }},
                         category: '{{ addslashes($symbol->category?->name_th ?? '') }}',
                         category_icon: '{{ $symbol->category?->icon ?? '💭' }}'
                     })">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-lg">{{ $symbol->category?->icon ?? '💭' }}</span>
                        <span class="text-white font-bold text-sm">{{ $symbol->keyword_th }}</span>
                        <span class="ml-auto text-xs">{{ $symbol->is_good_omen ? '✅' : '⚠️' }}</span>
                    </div>
                    <p class="text-purple-300/50 text-xs line-clamp-2 mb-2">{{ $symbol->meaning_th }}</p>
                    @if(!empty($symbol->lucky_numbers))
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_slice($symbol->lucky_numbers, 0, 3) as $num)
                                <span class="px-1.5 py-0.5 bg-amber-500/20 text-amber-300 rounded text-[10px] font-mono font-bold">{{ $num }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ==================== เล่าฝันให้ AI ทำนาย ==================== --}}
    <section class="container mx-auto px-4 pb-12">
        <div class="max-w-2xl mx-auto">

            {{-- Header --}}
            <h2 class="text-xl md:text-2xl font-bold text-white mb-6 flex items-center gap-2">
                <span class="text-2xl">🤖</span> เล่าฝันให้ AI ทำนาย
            </h2>

            {{-- ฟอร์ม --}}
            <div x-show="!interpretResult" x-transition>
                <div class="bg-white/5 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-white/10">
                    <p class="text-purple-300/60 text-sm mb-5">
                        เล่าฝันของคุณให้ละเอียดที่สุด AI จะวิเคราะห์สัญลักษณ์และทำนายความหมาย พร้อมให้เลขเด็ด
                    </p>

                    {{-- พื้นที่เล่าฝัน --}}
                    <div class="mb-4">
                        <label class="text-white text-sm font-bold mb-2 block">เล่าฝันของคุณ *</label>
                        <textarea x-model="dreamDescription"
                                  rows="5"
                                  placeholder="เช่น ฝันว่าเดินอยู่ในป่า เห็นงูตัวใหญ่มาก สีทอง มันพ่นพิษใส่ แต่ไม่โดน แล้วก็เห็นพระพุทธรูปอยู่กลางป่า..."
                                  class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-sm focus:outline-none focus:border-indigo-500/50 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"></textarea>
                        <div class="flex justify-between mt-1.5">
                            <span class="text-purple-300/40 text-[10px]">ขั้นต่ำ 10 ตัวอักษร</span>
                            <span class="text-purple-300/40 text-[10px]" x-text="dreamDescription.length + '/2000'"></span>
                        </div>
                    </div>

                    {{-- คำถามเพิ่มเติม --}}
                    <div class="mb-6">
                        <label class="text-white text-sm font-bold mb-2 block">คำถามเพิ่มเติม <span class="text-purple-300/40 font-normal">(ไม่จำเป็น)</span></label>
                        <input type="text"
                               x-model="dreamQuestion"
                               placeholder="เช่น ฝันนี้หมายความว่าอะไร? จะโชคดีไหม?"
                               class="w-full px-5 py-3 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-sm focus:outline-none focus:border-indigo-500/50 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                    </div>

                    {{-- ปุ่มทำนาย --}}
                    <button @click="interpretDream()"
                            :disabled="isInterpreting || dreamDescription.length < 10"
                            :class="isInterpreting || dreamDescription.length < 10
                                ? 'opacity-50 cursor-not-allowed'
                                : 'hover:shadow-xl hover:shadow-indigo-500/30 hover:-translate-y-0.5'"
                            class="w-full py-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold text-lg rounded-2xl transition-all duration-300 flex items-center justify-center gap-2">
                        <template x-if="!isInterpreting">
                            <span class="flex items-center gap-2">
                                <span>🔮</span> ทำนายฝัน
                            </span>
                        </template>
                        <template x-if="isInterpreting">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                กำลังทำนาย...
                            </span>
                        </template>
                    </button>

                    {{-- Error --}}
                    <div x-show="interpretError" x-transition class="mt-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <p class="text-red-300 text-sm" x-text="interpretError"></p>
                    </div>
                </div>
            </div>

            {{-- ผลทำนาย --}}
            <div x-show="interpretResult" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <template x-if="interpretResult">
                    <div>
                        {{-- Header ผล --}}
                        <div class="bg-gradient-to-br from-indigo-600/20 to-violet-600/20 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-indigo-500/20 mb-4">
                            <div class="text-center mb-4">
                                <div class="text-4xl mb-2">🌙</div>
                                <h3 class="text-white text-xl font-black">ผลทำนายฝัน</h3>
                            </div>

                            {{-- สัญลักษณ์ที่พบ --}}
                            <template x-if="interpretResult.matched_keywords && interpretResult.matched_keywords.length > 0">
                                <div class="mb-4">
                                    <h4 class="text-purple-300/60 text-xs font-bold mb-2">🔑 สัญลักษณ์ที่พบในฝัน</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="kw in interpretResult.matched_keywords" :key="kw">
                                            <span class="px-3 py-1 bg-white/10 border border-white/10 rounded-full text-purple-200 text-xs font-bold" x-text="kw"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- คำทำนาย AI --}}
                            <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
                                <h4 class="text-white text-sm font-bold mb-3 flex items-center gap-2">
                                    <span>🤖</span> คำทำนายจาก AI
                                </h4>
                                <div class="text-purple-100 text-sm leading-relaxed whitespace-pre-line" x-text="interpretResult.ai_interpretation"></div>
                            </div>
                        </div>

                        {{-- เลขเด็ด --}}
                        <template x-if="interpretResult.lucky_numbers && interpretResult.lucky_numbers.length > 0">
                            <div class="bg-gradient-to-br from-amber-600/20 to-orange-600/20 backdrop-blur-lg rounded-3xl p-6 border border-amber-500/20 mb-4 text-center">
                                <h4 class="text-amber-200 text-sm font-bold mb-4 flex items-center justify-center gap-2">
                                    <span class="text-xl">🎯</span> เลขเด็ดจากความฝัน
                                </h4>
                                <div class="flex flex-wrap justify-center gap-3">
                                    <template x-for="(num, idx) in interpretResult.lucky_numbers" :key="idx">
                                        <div class="relative">
                                            <div class="w-14 h-14 flex items-center justify-center bg-gradient-to-br from-amber-500/40 to-orange-500/40 border-2 border-amber-400/40 rounded-2xl shadow-lg shadow-amber-500/20"
                                                 :style="'animation: float ' + (3 + idx * 0.5) + 's ease-in-out infinite ' + (idx * 0.3) + 's'">
                                                <span class="text-amber-100 font-mono font-black text-xl" x-text="num"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <p class="text-amber-300/50 text-xs mt-3">* เลขเด็ดคำนวณจากสัญลักษณ์ที่พบในฝัน</p>
                            </div>
                        </template>

                        {{-- ปุ่ม --}}
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a :href="interpretResult.result_url"
                               class="flex-1 py-3 bg-white/5 border border-white/10 rounded-2xl text-center text-white font-bold text-sm hover:bg-white/10 transition flex items-center justify-center gap-2">
                                <span>📋</span> ดูผลทำนายฉบับเต็ม
                            </a>
                            <button @click="resetInterpret()"
                                    class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl text-center text-white font-bold text-sm hover:shadow-lg hover:shadow-indigo-500/30 transition flex items-center justify-center gap-2">
                                <span>🔮</span> ทำนายฝันใหม่
                            </button>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </section>

    {{-- ==================== SEO Content ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
            <h2 class="text-white text-lg font-bold mb-3">ทำนายฝันออนไลน์{{ ($freeFortuneEnabled ?? true) ? ' ฟรี' : '' }}</h2>
            <div class="text-purple-300/50 text-sm leading-relaxed space-y-2">
                <p>ระบบทำนายฝันออนไลน์ที่รวบรวมพจนานุกรมความฝันมากกว่า {{ $totalSymbols }} สัญลักษณ์ พร้อมเลขเด็ดจากความฝัน ครอบคลุม {{ $categories->count() }} หมวดหมู่</p>
                <p>นอกจากค้นหาความหมายจากพจนานุกรมแล้ว คุณยังสามารถเล่าฝันให้ AI ทำนายแบบละเอียดได้ โดย AI จะวิเคราะห์สัญลักษณ์ทั้งหมดในฝัน ให้คำทำนายด้านความรัก การงาน การเงิน พร้อมเลขเด็ดที่คำนวณจากสัญลักษณ์</p>
            </div>
        </div>
    </section>

</div>

<script>
function dreamApp() {
    return {
        // ค้นหา
        searchQuery: '',
        searchResults: [],
        showSearchResults: false,
        isSearching: false,

        // Modal สัญลักษณ์
        selectedSymbol: null,

        // ทำนาย AI
        dreamDescription: '',
        dreamQuestion: '',
        isInterpreting: false,
        interpretResult: null,
        interpretError: '',

        init() {
            // ถ้ามี query จาก URL
            const urlParams = new URLSearchParams(window.location.search);
            const q = urlParams.get('q');
            if (q) {
                this.searchQuery = q;
                this.handleSearch();
            }
        },

        /**
         * ค้นหาพจนานุกรม (AJAX)
         */
        async handleSearch() {
            const query = this.searchQuery.trim();
            if (query.length < 1) {
                this.searchResults = [];
                this.showSearchResults = false;
                return;
            }

            this.isSearching = true;

            try {
                const response = await fetch('{{ route("horoscope.dream.search") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ query }),
                });

                const data = await response.json();

                if (data.success) {
                    this.searchResults = data.data;
                    this.showSearchResults = true;
                }
            } catch (error) {
                console.error('ค้นหาผิดพลาด:', error);
            } finally {
                this.isSearching = false;
            }
        },

        /**
         * แสดงรายละเอียดสัญลักษณ์ใน modal
         */
        showSymbolDetail(item) {
            this.selectedSymbol = item;
            this.showSearchResults = false;
        },

        /**
         * ทำนายฝันด้วย AI (AJAX)
         */
        async interpretDream() {
            if (this.dreamDescription.length < 10) return;

            this.isInterpreting = true;
            this.interpretError = '';

            try {
                const response = await fetch('{{ route("horoscope.dream.interpret") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        dream_description: this.dreamDescription,
                        question: this.dreamQuestion || null,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.interpretResult = data.data;
                } else {
                    this.interpretError = data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                }
            } catch (error) {
                console.error('ทำนายฝันผิดพลาด:', error);
                this.interpretError = 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง';
            } finally {
                this.isInterpreting = false;
            }
        },

        /**
         * รีเซ็ตฟอร์มทำนาย
         */
        resetInterpret() {
            this.dreamDescription = '';
            this.dreamQuestion = '';
            this.interpretResult = null;
            this.interpretError = '';
        },
    };
}
</script>
@endsection
