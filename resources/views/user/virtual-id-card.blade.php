@extends('layouts.user-arrow-x')

@section('title', 'บัตรประจำตัวสมาชิก')

@section('content')
{{--
/**
 * Virtual ID Card - บัตรประจำตัวเสมือนสำหรับสมาชิก
 *
 * ออกแบบให้สวยงามแตกต่างกันตาม Rank:
 * - Bronze: เรียบง่าย สีทองแดง
 * - Silver: shimmer สีเงิน
 * - Gold: gradient สีทอง เงางาม
 * - Platinum: holographic effect
 * - Diamond: sparkle และ glassmorphism
 * - Crown: มงกุฎทอง animation พิเศษ
 * - Royal: สีม่วงหรู gradient หลากสี
 * - Legend: สุดอลังการ particles, gradient, 3D effects
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush

@php
    // กำหนด rank level (1-8)
    $rankLevel = $currentRank?->level ?? 1;
    $rankName = $currentRank?->name ?? 'Bronze';
    $rankNameTh = $currentRank?->name_th ?? 'สำริด';
    $rankColor = $currentRank?->color ?? '#CD7F32';
    $rankBadge = $currentRank?->badge_icon ?? '🥉';
    $rankStars = $currentRank?->stars ?? 1;
@endphp

<div class="space-y-6" x-data="virtualIdCard()">
    {{-- Premium Hero Header (Purple-Indigo-Blue for Virtual ID Card) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-800 dark:via-indigo-800 dark:to-blue-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-address-card"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-id-card text-3xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                            บัตรประจำตัวสมาชิก
                        </h1>
                        <p class="text-purple-100 mt-1">
                            บัตรประจำตัวเสมือนของคุณ สามารถพิมพ์หรือดาวน์โหลดเก็บไว้ได้
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 flex-wrap justify-center md:justify-end">
                    <button @click="downloadCard()"
                            class="glass-fusion hover:bg-white/30 rounded-lg px-4 py-2 text-white font-medium transition-all flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        <span>ดาวน์โหลด</span>
                    </button>
                    <button @click="printCard()"
                            class="glass-fusion hover:bg-white/30 rounded-lg px-4 py-2 text-white font-medium transition-all flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        <span>พิมพ์</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ID Card Container --}}
    <div class="flex justify-center">
        <div class="relative" id="id-card-container">
            {{-- ID Card --}}
            <div id="virtual-id-card" class="relative w-[450px] h-[280px] rounded-2xl overflow-hidden shadow-2xl transform transition-all duration-500 hover:scale-[1.02]
                {{ $rankLevel >= 8 ? 'animate-glow-legend' : '' }}
                {{ $rankLevel >= 6 ? 'ring-4 ring-yellow-400/50' : '' }}"
                 style="perspective: 1000px;">

                {{-- Background ตามระดับ Rank --}}
                @include('user.partials.id-card-background', ['rankLevel' => $rankLevel, 'rankColor' => $rankColor])

                {{-- Card Content --}}
                <div class="absolute inset-0 p-6 flex flex-col justify-between z-10">
                    {{-- Header Section --}}
                    <div class="flex justify-between items-start">
                        {{-- Logo & Title --}}
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center shadow-lg">
                                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-8 h-8 object-contain"
                                     onerror="this.src='https://ui-avatars.com/api/?name=TP&background=8B5CF6&color=fff&bold=true'">
                            </div>
                            <div>
                                <div class="text-white/80 text-xs font-medium tracking-wider uppercase">Member ID Card</div>
                                <div class="text-white text-lg font-bold tracking-wide">TP Affiliate</div>
                            </div>
                        </div>

                        {{-- Rank Badge --}}
                        <div class="text-center">
                            <div class="text-4xl mb-1 {{ $rankLevel >= 5 ? 'animate-bounce-slow' : '' }}">
                                {{ $rankBadge }}
                            </div>
                            <div class="text-white text-xs font-bold tracking-wider uppercase
                                {{ $rankLevel >= 8 ? 'animate-pulse' : '' }}">
                                {{ $rankName }}
                            </div>
                        </div>
                    </div>

                    {{-- Member Info --}}
                    <div class="flex items-end justify-between">
                        {{-- Profile & Name --}}
                        <div class="flex items-center gap-4">
                            {{-- Profile Picture with rank border --}}
                            <div class="relative">
                                <div class="w-20 h-20 rounded-xl overflow-hidden shadow-xl
                                    {{ $rankLevel >= 8 ? 'ring-4 ring-yellow-400 animate-pulse-slow' : '' }}
                                    {{ $rankLevel >= 6 && $rankLevel < 8 ? 'ring-3 ring-white/50' : '' }}
                                    {{ $rankLevel >= 4 && $rankLevel < 6 ? 'ring-2 ring-white/30' : '' }}">
                                    {{-- ใช้ profile_picture_url accessor พร้อม onerror fallback --}}
                                    <img src="{{ $user->profile_picture_url }}"
                                         alt="{{ $user->name }}"
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
                                </div>
                                {{-- Stars badge --}}
                                <div class="absolute -bottom-2 -right-2 bg-black/40 backdrop-blur-md rounded-full px-2 py-0.5">
                                    <div class="flex text-yellow-400 text-xs">
                                        @for($i = 0; $i < min($rankStars, 5); $i++)
                                            <i class="fas fa-star"></i>
                                        @endfor
                                        @if($rankStars > 5)
                                            <span class="ml-1 text-white">+{{ $rankStars - 5 }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Name & Details --}}
                            <div class="space-y-1">
                                <div class="text-white font-bold text-xl tracking-wide drop-shadow-lg">
                                    {{ Str::limit($user->name, 20) }}
                                </div>
                                <div class="text-white/70 text-sm font-medium">
                                    <i class="fas fa-award mr-1"></i>
                                    ระดับ {{ $rankNameTh }}
                                </div>
                                @if($user->member_number)
                                <div class="text-white/60 text-xs font-mono tracking-widest">
                                    ID: {{ $user->member_number }}
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- QR Code & Member Since --}}
                        <div class="text-right">
                            <div class="bg-white rounded-lg p-1.5 shadow-lg inline-block mb-2">
                                <div class="w-14 h-14 bg-gray-100 flex items-center justify-center text-xs text-gray-400">
                                    {{-- QR placeholder - จะถูกแทนที่ด้วย QR จริง --}}
                                    <div id="qr-code" class="w-full h-full"></div>
                                </div>
                            </div>
                            <div class="text-white/60 text-xs">
                                เป็นสมาชิกตั้งแต่
                            </div>
                            <div class="text-white/90 text-sm font-semibold">
                                {{ $user->created_at->locale('th')->translatedFormat('M Y') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Decorative Elements ตามระดับ --}}
                @include('user.partials.id-card-decorations', ['rankLevel' => $rankLevel])

                {{-- Holographic Overlay (Platinum ขึ้นไป) --}}
                @if($rankLevel >= 4)
                <div class="absolute inset-0 pointer-events-none z-20 opacity-30
                    {{ $rankLevel >= 6 ? 'animate-holographic' : 'animate-shimmer-slow' }}">
                    <div class="absolute inset-0 bg-gradient-to-br from-transparent via-white/20 to-transparent
                        transform -translate-x-full animate-shimmer"></div>
                </div>
                @endif
            </div>

            {{-- Card Shadow (higher ranks = more dramatic shadow) --}}
            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-[90%] h-8 bg-black/20 dark:bg-black/40 blur-xl rounded-full
                {{ $rankLevel >= 6 ? 'w-[95%] h-10' : '' }}
                {{ $rankLevel >= 8 ? 'w-full h-12 animate-pulse-slow' : '' }}"></div>
        </div>
    </div>

    {{-- Card Back (ด้านหลัง) --}}
    <div class="flex justify-center mt-8">
        <div class="relative">
            <div class="relative w-[450px] h-[280px] rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-800 to-gray-900">
                {{-- Magnetic Stripe --}}
                <div class="absolute top-10 left-0 right-0 h-12 bg-gray-950"></div>

                {{-- Signature Strip --}}
                <div class="absolute top-28 left-6 right-6">
                    <div class="bg-white/90 h-10 rounded flex items-center px-4">
                        <span class="text-gray-400 text-sm italic">ลายเซ็นผู้ถือบัตร</span>
                    </div>
                </div>

                {{-- Info Section --}}
                <div class="absolute bottom-6 left-6 right-6">
                    <div class="flex justify-between items-end">
                        <div class="text-gray-400 text-xs space-y-1">
                            <div>บัตรนี้เป็นสมบัติของ TP Affiliate</div>
                            <div>กรุณาคืนหากพบ | Contact: support@tp-affiliate.com</div>
                        </div>
                        <div class="text-right">
                            <div class="text-gray-500 text-xs mb-1">Valid Thru</div>
                            <div class="text-white font-bold">{{ now()->addYear()->format('m/Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rank Info Cards --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Current Rank Card --}}
        <x-arrow-x.card-v3 class="p-6">
            <div class="text-center">
                <div class="text-5xl mb-3">{{ $rankBadge }}</div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $rankNameTh }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ระดับปัจจุบันของคุณ</p>
                <div class="flex justify-center mt-3">
                    @for($i = 0; $i < $rankStars; $i++)
                        <i class="fas fa-star text-yellow-400"></i>
                    @endfor
                </div>
            </div>
        </x-arrow-x.card-v3>

        {{-- Points Card --}}
        <x-arrow-x.card-v3 class="p-6">
            <div class="text-center">
                <div class="text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                    {{ number_format($user->rank_points ?? 0) }}
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">คะแนนสะสม</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Rank Points</p>
            </div>
        </x-arrow-x.card-v3>

        {{-- Member Since Card --}}
        <x-arrow-x.card-v3 class="p-6">
            <div class="text-center">
                <div class="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">
                    {{ $user->created_at->diffInDays(now()) }}
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">วันที่เป็นสมาชิก</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ตั้งแต่ {{ $user->created_at->locale('th')->translatedFormat('d M Y') }}</p>
            </div>
        </x-arrow-x.card-v3>
    </div>

    {{-- Privileges Section (ถ้ามี) --}}
    @if($currentRank && $currentRank->privileges && count($currentRank->privileges) > 0)
    <x-arrow-x.card-v3 class="p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-gem text-purple-500"></i>
            สิทธิพิเศษของคุณ
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($currentRank->getPrivilegesWithDescriptions() as $key => $privilege)
            <div class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                <span class="text-2xl">{{ $privilege['icon'] }}</span>
                <div>
                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ $privilege['name_th'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </x-arrow-x.card-v3>
    @endif
</div>

{{-- Print Styles --}}
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #id-card-container, #id-card-container * {
            visibility: visible;
        }
        #id-card-container {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
    }

    /* Custom Animations */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes holographic {
        0%, 100% { opacity: 0.2; background-position: 0% 50%; }
        50% { opacity: 0.4; background-position: 100% 50%; }
    }

    @keyframes glow-legend {
        0%, 100% { box-shadow: 0 0 30px rgba(236, 72, 153, 0.5), 0 0 60px rgba(139, 92, 246, 0.3); }
        50% { box-shadow: 0 0 50px rgba(236, 72, 153, 0.7), 0 0 100px rgba(139, 92, 246, 0.5); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    @keyframes sparkle {
        0%, 100% { opacity: 0; transform: scale(0); }
        50% { opacity: 1; transform: scale(1); }
    }

    .animate-shimmer {
        animation: shimmer 3s infinite;
    }

    .animate-shimmer-slow {
        animation: shimmer 5s infinite;
    }

    .animate-holographic {
        animation: holographic 4s ease-in-out infinite;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
        background-size: 200% 200%;
    }

    .animate-glow-legend {
        animation: glow-legend 3s ease-in-out infinite;
    }

    .animate-bounce-slow {
        animation: float 3s ease-in-out infinite;
    }

    .animate-pulse-slow {
        animation: pulse 3s ease-in-out infinite;
    }

    .animate-sparkle {
        animation: sparkle 2s ease-in-out infinite;
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
/**
 * Virtual ID Card Alpine Component
 *
 * จัดการการดาวน์โหลดและพิมพ์บัตรประจำตัว
 */
function virtualIdCard() {
    return {
        init() {
            // สร้าง QR Code
            this.generateQRCode();
        },

        /**
         * สร้าง QR Code สำหรับบัตร
         */
        generateQRCode() {
            const qrContainer = document.getElementById('qr-code');
            if (qrContainer && typeof QRCode !== 'undefined') {
                // สร้าง QR ที่ชี้ไปยังหน้าโปรไฟล์หรือ referral link
                const profileUrl = '{{ route("user.profile") }}';
                const memberNumber = '{{ $user->member_number ?? $user->id }}';

                QRCode.toCanvas(qrContainer, memberNumber, {
                    width: 56,
                    margin: 0,
                    color: {
                        dark: '#374151',
                        light: '#f3f4f6'
                    }
                }, function(error) {
                    if (error) {
                        console.error('QR Code error:', error);
                        // Fallback: แสดงเลขสมาชิก
                        qrContainer.innerHTML = '<span class="text-[8px] text-gray-500">' + memberNumber + '</span>';
                    }
                });
            }
        },

        /**
         * ดาวน์โหลดบัตรเป็นรูปภาพ PNG
         */
        async downloadCard() {
            const card = document.getElementById('virtual-id-card');

            try {
                // แสดง loading
                const originalContent = card.innerHTML;

                const canvas = await html2canvas(card, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: null,
                    logging: false
                });

                // สร้าง download link
                const link = document.createElement('a');
                link.download = 'tp-affiliate-id-card-{{ Str::slug($user->name) }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();

                // แสดง success message
                this.$dispatch('notify', {
                    type: 'success',
                    message: 'ดาวน์โหลดบัตรสำเร็จ!'
                });
            } catch (error) {
                console.error('Download error:', error);
                alert('เกิดข้อผิดพลาดในการดาวน์โหลด กรุณาลองใหม่');
            }
        },

        /**
         * พิมพ์บัตร
         */
        printCard() {
            window.print();
        }
    }
}
</script>
@endpush
