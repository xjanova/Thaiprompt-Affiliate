{{-- Quick 1-Card Tarot Reading — Interactive Card Flip --}}
@extends('frontend.horoscope.layout')

@push('styles')
<style>
    /* 3D Card Flip */
    .card-container { perspective: 1200px; }
    .card-inner {
        position: relative;
        width: 100%;
        height: 100%;
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        transform-style: preserve-3d;
    }
    .card-inner.flipped { transform: rotateY(180deg); }
    .card-front, .card-back {
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        border-radius: 1rem;
    }
    .card-back { transform: rotateY(180deg); }

    /* Deck Stack Effect */
    .deck-card {
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .deck-card:hover {
        transform: translateY(-30px) rotateY(5deg) scale(1.05);
        z-index: 50;
    }

    /* Glow Animation */
    @keyframes card-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(139, 92, 246, 0.2); }
        50% { box-shadow: 0 0 50px rgba(139, 92, 246, 0.5), 0 0 100px rgba(139, 92, 246, 0.2); }
    }
    .card-glow { animation: card-glow 2s ease-in-out infinite; }

    /* Sparkle */
    @keyframes sparkle {
        0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
        50% { opacity: 1; transform: scale(1) rotate(180deg); }
    }

    /* Shuffle Animation */
    @keyframes shuffle-left {
        0% { transform: translateX(0) rotate(0); }
        25% { transform: translateX(-60px) rotate(-5deg); }
        50% { transform: translateX(0) rotate(0); }
        75% { transform: translateX(60px) rotate(5deg); }
        100% { transform: translateX(0) rotate(0); }
    }
    .shuffling { animation: shuffle-left 0.6s ease-in-out; }
</style>
@endpush

@section('horoscope-content')
<div x-data="quickTarot()" x-init="init()" class="min-h-[80vh]">

    {{-- ==================== Step 1: เลือกหมวดหมู่ + ตั้งคำถาม ==================== --}}
    <section x-show="step === 'setup'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="container mx-auto px-4 py-12">

        {{-- Breadcrumb --}}
        <div class="mb-6">
            <nav class="flex items-center gap-2 text-sm text-purple-300/50">
                <a href="{{ route('horoscope.home') }}" class="hover:text-purple-200 transition">🏠 หน้าแรก</a>
                <span>/</span>
                <a href="{{ route('horoscope.tarot.index') }}" class="hover:text-purple-200 transition">ไพ่ทาโรต์</a>
                <span>/</span>
                <span class="text-purple-200/80">Quick Reading</span>
            </nav>
        </div>

        <div class="max-w-2xl mx-auto text-center">
            {{-- หัวข้อ --}}
            <div class="mb-8">
                <span class="text-5xl mb-3 block">🃏</span>
                <h1 class="text-3xl md:text-4xl font-black mb-2">
                    <span class="shimmer-text">เปิดไพ่ 1 ใบ</span>
                </h1>
                <p class="text-purple-300/60 text-sm">
                    ตั้งจิตและนึกถึงคำถามของคุณ แล้วเลือกไพ่ 1 ใบ
                </p>
            </div>

            {{-- เลือกหมวดหมู่ --}}
            <div class="mb-8">
                <label class="text-purple-200/80 text-sm font-bold mb-3 block">เลือกหมวดหมู่คำถาม</label>
                <div class="flex flex-wrap justify-center gap-2">
                    @php
                        $catIcons = [
                            'love-relationships' => '❤️',
                            'career-finance' => '💼',
                            'personal-growth' => '🌟',
                            'health-wellness' => '💚',
                            'general' => '🔮',
                        ];
                    @endphp
                    @foreach($categories as $cat)
                        <button @click="category = '{{ $cat->slug }}'"
                                :class="category === '{{ $cat->slug }}'
                                    ? 'bg-violet-500/30 border-violet-500/50 text-white'
                                    : 'bg-white/5 border-white/10 text-purple-300/60 hover:bg-white/10'"
                                class="px-4 py-2.5 rounded-xl border text-sm font-medium transition-all duration-200 flex items-center gap-2">
                            <span>{{ $catIcons[$cat->slug] ?? '🔮' }}</span>
                            {{ $cat->name_th ?? $cat->name_en }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- คำถาม (ไม่บังคับ) --}}
            <div class="mb-8">
                <label class="text-purple-200/80 text-sm font-bold mb-3 block">คำถามของคุณ (ไม่บังคับ)</label>
                <div class="relative">
                    <textarea x-model="question"
                              maxlength="500"
                              rows="2"
                              placeholder="เช่น ช่วงนี้ความรักจะเป็นอย่างไร? หรือเว้นว่างเพื่อดูดวงภาพรวม..."
                              class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-sm focus:outline-none focus:border-violet-500/50 focus:ring-2 focus:ring-violet-500/20 resize-none transition-all"></textarea>
                    <div class="absolute bottom-2 right-3 text-purple-300/30 text-xs" x-text="question.length + '/500'"></div>
                </div>
            </div>

            {{-- ปุ่มเริ่มจับไพ่ --}}
            <button @click="startDeck()"
                    class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white font-bold rounded-2xl shadow-xl shadow-violet-500/30 hover:shadow-violet-500/50 hover:scale-105 transition-all duration-300 text-lg">
                ✨ เริ่มจับไพ่
            </button>
        </div>
    </section>

    {{-- ==================== Step 2: เลือกไพ่จากกองไพ่ ==================== --}}
    <section x-show="step === 'pick'"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="container mx-auto px-4 py-12">

        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-2">
                🃏 เลือกไพ่ 1 ใบ
            </h2>
            <p class="text-purple-300/60 text-sm">
                ตั้งจิต นึกถึงคำถาม แล้วคลิกเลือกไพ่ที่รู้สึกดึงดูดมากที่สุด
            </p>
        </div>

        {{-- กองไพ่ (แสดง 7 ใบ) --}}
        <div class="flex justify-center items-end gap-2 md:gap-3 flex-wrap max-w-4xl mx-auto mb-8"
             :class="{ 'pointer-events-none opacity-50': isShuffling }">
            <template x-for="(card, index) in deckCards" :key="index">
                <button @click="pickCard(index)"
                        class="deck-card card-container w-20 h-32 md:w-28 md:h-44 relative cursor-pointer"
                        :style="`transform: rotate(${(index - 3) * 4}deg); transition-delay: ${index * 50}ms;`"
                        :class="{ 'shuffling': isShuffling }">
                    {{-- หลังไพ่ --}}
                    <div class="w-full h-full rounded-xl bg-gradient-to-br from-indigo-900 via-purple-900 to-violet-900 border-2 border-violet-400/30 shadow-xl card-glow flex items-center justify-center overflow-hidden">
                        {{-- ลวดลายหลังไพ่ --}}
                        <div class="absolute inset-2 border border-violet-400/20 rounded-lg"></div>
                        <div class="absolute inset-3 border border-violet-400/10 rounded-lg"></div>
                        <div class="text-3xl md:text-4xl opacity-60">⭐</div>
                    </div>
                </button>
            </template>
        </div>

        {{-- ปุ่มสับไพ่ --}}
        <div class="text-center">
            <button @click="shuffleDeck()"
                    :disabled="isShuffling"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/5 border border-white/10 rounded-xl text-purple-300/60 hover:text-white hover:bg-white/10 transition-all text-sm disabled:opacity-50">
                🔀 สับไพ่ใหม่
            </button>
        </div>
    </section>

    {{-- ==================== Step 3: เปิดไพ่ + คำตีความ ==================== --}}
    <section x-show="step === 'reveal'"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="container mx-auto px-4 py-12">

        <div class="max-w-3xl mx-auto">
            {{-- Card Flip Area --}}
            <div class="text-center mb-8">
                <div class="card-container w-48 h-72 md:w-56 md:h-80 mx-auto mb-6"
                     @click="flipCard()">
                    <div class="card-inner" :class="{ 'flipped': isFlipped }">
                        {{-- หน้าหลังไพ่ --}}
                        <div class="card-front w-full h-full rounded-2xl bg-gradient-to-br from-indigo-900 via-purple-900 to-violet-900 border-2 border-violet-400/30 shadow-2xl card-glow flex items-center justify-center cursor-pointer overflow-hidden">
                            <div class="absolute inset-3 border border-violet-400/20 rounded-xl"></div>
                            <div class="absolute inset-5 border border-violet-400/10 rounded-xl"></div>
                            <div class="text-center">
                                <div class="text-5xl mb-2">⭐</div>
                                <div class="text-violet-300/60 text-xs">คลิกเพื่อเปิดไพ่</div>
                            </div>
                        </div>

                        {{-- หน้าไพ่ --}}
                        <div class="card-back w-full h-full rounded-2xl bg-gradient-to-br from-white to-gray-100 border-2 border-violet-400/30 shadow-2xl overflow-hidden"
                             :class="{ 'rotate-180': result?.card?.is_reversed }">
                            <template x-if="result?.card">
                                <div class="w-full h-full flex flex-col items-center justify-center p-3">
                                    <img :src="result.card.image_url"
                                         :alt="result.card.name_th"
                                         class="w-full h-full object-contain rounded-lg"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-full h-full hidden flex-col items-center justify-center bg-gradient-to-br from-violet-100 to-fuchsia-100 rounded-lg">
                                        <div class="text-4xl mb-2" x-text="result.card.type === 'major_arcana' ? '👑' : '🎴'"></div>
                                        <div class="text-violet-800 font-bold text-sm text-center px-2" x-text="result.card.name_th"></div>
                                        <div class="text-violet-600 text-xs mt-1" x-text="result.card.name_en"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ชื่อไพ่ (แสดงหลัง flip) --}}
                <div x-show="isFlipped && result" x-transition class="mb-2">
                    <h2 class="text-2xl md:text-3xl font-black text-white" x-text="result?.card?.name_th"></h2>
                    <p class="text-purple-300/60 text-sm" x-text="result?.card?.name_en"></p>
                    <div class="flex justify-center gap-2 mt-2">
                        <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/80"
                              x-text="result?.card?.orientation"></span>
                        <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/80"
                              x-text="result?.card?.type === 'major_arcana' ? 'Major Arcana' : result?.card?.suit"></span>
                    </div>
                </div>
            </div>

            {{-- Sparkle Effect (แสดงตอน flip) --}}
            <template x-if="isFlipped">
                <div class="fixed inset-0 pointer-events-none z-50" x-data x-init="setTimeout(() => $el.remove(), 2000)">
                    <template x-for="i in 15" :key="'sp-'+i">
                        <div class="absolute text-xl"
                             :style="`left: ${20 + Math.random() * 60}%; top: ${20 + Math.random() * 60}%; animation: sparkle ${0.5 + Math.random()}s ease-out forwards; animation-delay: ${Math.random() * 0.5}s;`">
                            ✨
                        </div>
                    </template>
                </div>
            </template>

            {{-- คำตีความ --}}
            <div x-show="isFlipped && result" x-transition class="space-y-4">

                {{-- Keywords --}}
                <div class="flex flex-wrap justify-center gap-2 mb-4" x-show="result?.card?.keywords?.length">
                    <template x-for="kw in (result?.card?.keywords || [])" :key="kw">
                        <span class="px-3 py-1 bg-violet-500/15 border border-violet-500/20 rounded-full text-violet-300 text-xs" x-text="kw"></span>
                    </template>
                </div>

                {{-- ความหมายไพ่ --}}
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
                    <h3 class="text-white font-bold text-lg mb-2 flex items-center gap-2">
                        <span>📖</span> ความหมาย
                    </h3>
                    <p class="text-purple-200/80 text-sm leading-relaxed" x-text="result?.card?.meaning"></p>
                </div>

                {{-- Loading AI --}}
                <div x-show="isInterpreting" class="bg-violet-500/10 backdrop-blur-lg rounded-2xl p-8 border border-violet-500/20 text-center">
                    <div class="inline-flex items-center gap-3">
                        <svg class="animate-spin h-5 w-5 text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-violet-300 font-medium">AI กำลังตีความไพ่...</span>
                    </div>
                </div>

                {{-- AI Interpretation --}}
                <div x-show="!isInterpreting && interpretation" class="space-y-4">
                    {{-- คำตีความ AI --}}
                    <div class="bg-gradient-to-br from-violet-600/10 to-fuchsia-600/10 backdrop-blur-lg rounded-2xl p-6 border border-violet-500/20">
                        <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                            <span>🔮</span> คำตีความจาก AI
                        </h3>
                        <p class="text-purple-200/80 text-sm leading-relaxed whitespace-pre-line" x-text="interpretation"></p>
                    </div>

                    {{-- คำแนะนำ --}}
                    <div x-show="advice" class="bg-gradient-to-br from-emerald-600/10 to-teal-600/10 backdrop-blur-lg rounded-2xl p-6 border border-emerald-500/20">
                        <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                            <span>💡</span> คำแนะนำ
                        </h3>
                        <p class="text-purple-200/80 text-sm leading-relaxed whitespace-pre-line" x-text="advice"></p>
                    </div>

                    {{-- พลังงาน --}}
                    <div x-show="energy" class="bg-gradient-to-br from-amber-600/10 to-orange-600/10 backdrop-blur-lg rounded-2xl p-5 border border-amber-500/20">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl shrink-0">⚡</span>
                            <div>
                                <div class="text-amber-300 font-bold text-sm mb-1">พลังงานของไพ่</div>
                                <p class="text-purple-200/70 text-sm" x-text="energy"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- แชร์ + เปิดใหม่ --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                    <div>
                        @include('frontend.horoscope.partials._share-buttons', [
                            'url' => route('horoscope.tarot.quick'),
                            'title' => 'เปิดไพ่ทาโรต์ออนไลน์ฟรี — Quick Reading',
                        ])
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('horoscope.tarot.index') }}"
                           class="px-5 py-2.5 bg-white/5 border border-white/10 rounded-xl text-purple-300/60 hover:text-white hover:bg-white/10 transition-all text-sm">
                            Full Reading →
                        </a>
                        <button @click="resetReading()"
                                class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all text-sm">
                            🔄 เปิดไพ่ใหม่
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
function quickTarot() {
    return {
        // State
        step: 'setup',           // setup → pick → reveal
        category: 'general',
        question: '',
        deckCards: [],
        isShuffling: false,
        selectedIndex: null,

        // Card reveal state
        isFlipped: false,
        isInterpreting: false,
        result: null,
        interpretation: null,
        advice: null,
        energy: null,

        init() {
            // สร้างกองไพ่ 7 ใบ
            this.generateDeck();
        },

        // สร้างกองไพ่แบบสุ่ม
        generateDeck() {
            this.deckCards = Array.from({ length: 7 }, (_, i) => ({
                index: i,
                rotation: (i - 3) * 4,
            }));
        },

        // เริ่มจับไพ่ (ไปขั้นเลือกไพ่)
        startDeck() {
            this.step = 'pick';
            // Shuffle animation
            this.shuffleDeck();
        },

        // Animation สับไพ่
        shuffleDeck() {
            this.isShuffling = true;
            setTimeout(() => {
                this.generateDeck();
                this.isShuffling = false;
            }, 600);
        },

        // เลือกไพ่ 1 ใบ
        async pickCard(index) {
            if (this.isShuffling) return;

            this.selectedIndex = index;
            this.step = 'reveal';
            this.isFlipped = false;
            this.isInterpreting = false;
            this.interpretation = null;
            this.advice = null;
            this.energy = null;

            // รอ animation เข้า section แล้วค่อย flip
            setTimeout(() => {
                this.flipCard();
            }, 500);
        },

        // Flip card + เรียก API
        async flipCard() {
            if (this.isFlipped) return;

            this.isFlipped = true;
            this.isInterpreting = true;

            try {
                // เรียก API ตีความ
                const response = await fetch('{{ route("horoscope.tarot.quick-interpret") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        card_id: 0,  // API จะสุ่มไพ่ให้
                        is_reversed: Math.random() > 0.7,  // 30% กลับหัว
                        category: this.category,
                        question: this.question,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.result = data.data;
                    this.interpretation = data.data.interpretation;
                    this.advice = data.data.advice;
                    this.energy = data.data.energy;
                } else {
                    this.interpretation = 'ไม่สามารถตีความได้ในขณะนี้ กรุณาลองใหม่';
                }
            } catch (error) {
                console.error('Tarot interpret error:', error);
                this.interpretation = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
            }

            this.isInterpreting = false;
        },

        // รีเซ็ตและเปิดไพ่ใหม่
        resetReading() {
            this.step = 'setup';
            this.question = '';
            this.isFlipped = false;
            this.result = null;
            this.interpretation = null;
            this.advice = null;
            this.energy = null;
            this.selectedIndex = null;
            this.generateDeck();
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
}
</script>
@endsection
