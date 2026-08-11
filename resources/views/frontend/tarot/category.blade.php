@extends('layouts.app')

@section('content')
{{-- หน้าเลือกรูปแบบการเปิดไพ่ - V3 Premium Design --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 relative overflow-hidden"
     x-data="{ ready: false }"
     x-init="setTimeout(() => ready = true, 100)">

    {{-- พื้นหลังแบบ Mystical --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="stars-bg absolute inset-0"></div>

        {{-- วงแหวนเวทย์มนต์ --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] opacity-10">
            <div class="absolute inset-0 border border-purple-400 rounded-full animate-spin-slow"></div>
            <div class="absolute inset-8 border border-pink-400 rounded-full animate-spin-slow-reverse"></div>
            <div class="absolute inset-16 border border-cyan-400 rounded-full animate-spin-slow"></div>
        </div>

        {{-- กลุ่มหมอก Gradient --}}
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-pink-600/20 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-cyan-600/10 rounded-full blur-[80px] animate-pulse"></div>
    </div>

    <div class="container mx-auto px-4 py-12 relative z-10">
        {{-- Back Button --}}
        <a href="{{ route('tarot.index') }}"
           class="inline-flex items-center gap-2 text-purple-300 hover:text-white mb-8 transition-colors group"
           x-show="ready"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 transform -translate-x-4"
           x-transition:enter-end="opacity-100 transform translate-x-0">
            <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับหน้าหลัก
        </a>

        {{-- Category Header --}}
        <div class="relative mb-12"
             x-show="ready"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform -translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            {{-- Glow Effect --}}
            <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/10 to-cyan-500/20 rounded-3xl blur-2xl"></div>

            {{-- Card Body --}}
            <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/80 to-slate-900/90 backdrop-blur-xl rounded-3xl p-8 border border-white/10 shadow-2xl">
                <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-6">
                    <div class="flex items-center gap-6">
                        {{-- Category Icon --}}
                        <div class="relative flex-shrink-0">
                            <div class="absolute inset-0 rounded-2xl blur-xl opacity-60 animate-pulse"
                                 style="background: {{ $category->color ?? '#9333ea' }}"></div>
                            <div class="relative w-20 h-20 rounded-2xl flex items-center justify-center shadow-2xl"
                                 style="background: linear-gradient(135deg, {{ $category->color ?? '#9333ea' }}, {{ $category->color ?? '#ec4899' }}99)">
                                @if($category->icon)
                                    <i class="fas {{ $category->icon }} text-white text-3xl drop-shadow-lg"></i>
                                @else
                                    <span class="text-4xl">🔮</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h1 class="text-3xl md:text-4xl font-black text-white mb-2">
                                {{ $category->name_th }}
                            </h1>
                            <p class="text-purple-200/80 text-lg">{{ $category->description_th }}</p>
                        </div>
                    </div>

                    {{-- Price & Quota Info --}}
                    <div class="text-center md:text-right flex-shrink-0">
                        @if($category->price > 0)
                            <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-900 rounded-full font-bold text-xl shadow-lg shadow-amber-500/30 mb-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                                </svg>
                                {{ number_format($category->price, 0) }} บาท
                            </div>
                            @if($category->is_free_first)
                                @php
                                    $userId = Auth::id();
                                    $sessionId = session()->getId();
                                    $ipAddress = request()->ip();
                                    $remaining = \App\Models\TarotUserLimit::getRemainingQuota($category->id, $userId, $sessionId, $ipAddress);
                                @endphp
                                @if($remaining > 0)
                                    <div class="text-emerald-400 text-sm font-medium flex items-center justify-center md:justify-end gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        โควต้าฟรีเหลือ: {{ $remaining }} ครั้ง/วัน
                                    </div>
                                @else
                                    <div class="text-red-400 text-sm font-medium flex items-center justify-center md:justify-end gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        หมดโควต้าฟรีวันนี้แล้ว
                                    </div>
                                @endif
                            @endif
                        @else
                            <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-full font-bold text-xl shadow-lg shadow-emerald-500/30 mb-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5V9a1 1 0 10-2 0v1H4a2 2 0 110-4h1.17C5.06 5.687 5 5.35 5 5zm4 1V5a1 1 0 10-1 1h1zm3 0a1 1 0 10-1-1v1h1z" clip-rule="evenodd"/>
                                    <path d="M9 11H3v5a2 2 0 002 2h4v-7zM11 18h4a2 2 0 002-2v-5h-6v7z"/>
                                </svg>
                                ฟรี
                            </div>
                            @php
                                $userId = Auth::id();
                                $sessionId = session()->getId();
                                $ipAddress = request()->ip();
                                $remaining = \App\Models\TarotUserLimit::getRemainingQuota($category->id, $userId, $sessionId, $ipAddress);
                            @endphp
                            <div class="text-emerald-400 text-sm font-medium">
                                โควต้าเหลือ: {{ $remaining }} ครั้ง/วัน
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Question Input Section --}}
        <div class="relative mb-12"
             x-show="ready"
             x-transition:enter="transition ease-out duration-500 delay-100"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-purple-500/10 to-pink-500/10 rounded-2xl blur-xl"></div>

            <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/80 to-slate-900/90 backdrop-blur-xl rounded-2xl p-8 border border-white/10 shadow-xl">
                <label class="block text-white text-xl font-bold mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    คำถามของคุณ <span class="text-purple-400/60 text-sm font-normal">(ไม่บังคับ)</span>
                </label>
                <textarea id="question"
                          rows="3"
                          class="w-full px-5 py-4 rounded-xl bg-slate-800/50 text-white placeholder-purple-300/50 border-2 border-white/10 focus:border-purple-400/50 focus:ring-4 focus:ring-purple-500/20 transition-all resize-none"
                          placeholder="พิมพ์คำถามที่คุณต้องการทราบคำตอบ... เช่น ความรักของฉันจะเป็นอย่างไร?"></textarea>
            </div>
        </div>

        {{-- Spread Types Section --}}
        <div x-show="ready"
             x-transition:enter="transition ease-out duration-500 delay-200"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            <h2 class="text-3xl md:text-4xl font-black text-white mb-8 text-center">
                <span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent">
                    เลือกรูปแบบการเปิดไพ่
                </span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                @foreach($spreadTypes as $index => $spreadType)
                <div class="group perspective-1000"
                     style="animation-delay: {{ ($index + 1) * 100 }}ms">

                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
                        {{-- Glow Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/30 via-pink-500/20 to-cyan-500/30 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        {{-- Card Body --}}
                        <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/80 to-slate-900/90 backdrop-blur-xl rounded-2xl p-6 border border-white/10 group-hover:border-purple-400/50 shadow-2xl transition-all duration-500 h-full flex flex-col">

                            {{-- Header --}}
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-white group-hover:text-purple-200 transition-colors">
                                    {{ $spreadType->name_th }}
                                </h3>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full text-sm font-bold shadow-lg shadow-purple-500/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    {{ $spreadType->card_count }} ใบ
                                </span>
                            </div>

                            {{-- Description --}}
                            <p class="text-purple-200/70 mb-4 flex-grow">
                                {{ $spreadType->description_th }}
                            </p>

                            {{-- Price Badge --}}
                            @if($spreadType->base_price > 0)
                                <div class="mb-5 inline-flex items-center gap-2 text-amber-400 text-sm font-medium">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                    </svg>
                                    + ราคารูปแบบ: ฿{{ number_format($spreadType->base_price, 0) }}
                                </div>
                            @endif

                            {{-- Buttons --}}
                            <div class="space-y-3 mt-auto">
                                <button onclick="startReading({{ $spreadType->id }})"
                                        class="group/btn relative w-full overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-purple-500/30 transform hover:scale-[1.02] transition-all duration-300">
                                    {{-- Shine Effect --}}
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>
                                    <span class="relative flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        ทำนายทันที
                                    </span>
                                </button>

                                <button onclick="addToCart({{ $spreadType->id }})"
                                        class="w-full bg-white/10 hover:bg-white/20 backdrop-blur-lg border border-white/20 hover:border-white/40 text-white py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    เพิ่มเข้าตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Card Back Preview - Fixed Bottom Right --}}
    <div class="fixed bottom-6 right-6 z-30"
         x-show="ready"
         x-transition:enter="transition ease-out duration-500 delay-500"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0">

        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/50 to-pink-500/50 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
            <div class="relative bg-gradient-to-br from-slate-900/95 via-purple-900/90 to-slate-900/95 backdrop-blur-xl rounded-2xl p-4 border border-white/10 shadow-2xl">
                <p class="text-purple-300 text-xs mb-2 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    รูปหลังไพ่
                </p>
                {{-- 🃏 (2026-06-08) 9:16 + object-contain แสดงหลังไพ่เต็มใบไม่ขาด --}}
                {{-- ⚠️ เดิมชี้ไปที่ card-back-default.png ซึ่งไม่มีไฟล์อยู่จริง (มีแต่ .svg) → รูปแตก
                     เปลี่ยนมาใช้ภาพหลังไพ่ลายกนกที่เจนไว้ใน public/images/art --}}
                <img src="{{ $cardBackImage->image_url ?? asset('images/art/tarot-card-back.webp') }}"
                     alt="Card Back"
                     class="w-[54px] h-24 rounded-lg shadow-lg ring-2 ring-purple-500/30 object-contain">
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* ดาวบนพื้นหลัง */
    .stars-bg {
        background-image:
            radial-gradient(2px 2px at 20px 30px, white, transparent),
            radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
            radial-gradient(1px 1px at 90px 40px, white, transparent),
            radial-gradient(2px 2px at 160px 120px, rgba(255,255,255,0.6), transparent),
            radial-gradient(1px 1px at 230px 80px, white, transparent);
        background-size: 350px 200px;
        animation: stars-twinkle 8s ease-in-out infinite;
    }

    @keyframes stars-twinkle {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 1; }
    }

    /* หมุนช้าๆ */
    @keyframes spin-slow {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 40s linear infinite;
    }
    .animate-spin-slow-reverse {
        animation: spin-slow 35s linear infinite reverse;
    }

    /* Perspective */
    .perspective-1000 { perspective: 1000px; }
    .rotate-y-3 { transform: rotateY(3deg); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function startReading(spreadTypeId) {
    const question = document.getElementById('question').value;
    const categoryId = {{ $category->id }};
    const canUseFree = {{ $canUseFree ? 'true' : 'false' }};

    // Show loading
    Swal.fire({
        title: '<span class="text-purple-300">🔮 กำลังสับไพ่...</span>',
        html: '<span class="text-purple-200">โปรดรอสักครู่</span>',
        background: '#1e1b4b',
        color: '#fff',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send request
    fetch('{{ route('tarot.start') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            category_id: categoryId,
            spread_type_id: spreadTypeId,
            question: question,
            use_free: canUseFree
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.requires_payment) {
            // Redirect to payment
            Swal.fire({
                title: '<span class="text-amber-300">💳 ต้องชำระเงิน</span>',
                html: `<div class="text-purple-200">จำนวนเงิน: <span class="text-amber-400 font-bold">${data.amount} บาท</span><br>${data.category}</div>`,
                icon: 'info',
                background: '#1e1b4b',
                color: '#fff',
                showCancelButton: true,
                confirmButtonText: '✨ ชำระเงิน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#9333ea',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = data.payment_url;
                }
            });
        } else if (data.success) {
            // Redirect to reading
            window.location.href = data.redirect_url;
        } else {
            Swal.fire({
                title: '<span class="text-red-400">❌ เกิดข้อผิดพลาด</span>',
                text: data.message || 'กรุณาลองใหม่อีกครั้ง',
                icon: 'error',
                background: '#1e1b4b',
                color: '#fff',
                confirmButtonColor: '#9333ea'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: '<span class="text-red-400">❌ เกิดข้อผิดพลาด</span>',
            text: 'กรุณาลองใหม่อีกครั้ง',
            icon: 'error',
            background: '#1e1b4b',
            color: '#fff',
            confirmButtonColor: '#9333ea'
        });
    });
}

function addToCart(spreadTypeId) {
    const question = document.getElementById('question').value;

    if (!question || question.trim() === '') {
        Swal.fire({
            title: '<span class="text-amber-300">⚠️ กรุณาใส่คำถาม</span>',
            text: 'โปรดพิมพ์คำถามที่คุณต้องการทราบคำตอบ',
            icon: 'warning',
            background: '#1e1b4b',
            color: '#fff',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: '<span class="text-purple-300">🛒 กำลังเพิ่มเข้าตะกร้า...</span>',
        background: '#1e1b4b',
        color: '#fff',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Add to cart
    const formData = new FormData();
    formData.append('category_id', {{ $category->id }});
    formData.append('spread_type_id', spreadTypeId);
    formData.append('question', question);

    fetch('{{ route('tarot.cart.add') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            Swal.fire({
                title: '<span class="text-emerald-400">✅ เพิ่มเข้าตะกร้าแล้ว!</span>',
                text: 'ต้องการไปที่ตะกร้าหรือไม่?',
                icon: 'success',
                background: '#1e1b4b',
                color: '#fff',
                showCancelButton: true,
                confirmButtonText: '🛒 ไปที่ตะกร้า',
                cancelButtonText: 'เลือกต่อ',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route('tarot.cart.index') }}';
                } else {
                    // Clear question for next item
                    document.getElementById('question').value = '';
                }
            });
        } else {
            throw new Error('Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: '<span class="text-red-400">❌ เกิดข้อผิดพลาด</span>',
            text: 'กรุณาลองใหม่อีกครั้ง',
            icon: 'error',
            background: '#1e1b4b',
            color: '#fff',
            confirmButtonColor: '#9333ea'
        });
    });
}
</script>
@endpush
@endsection
