@extends('layouts.landing')

@section('title', 'รับสิทธิ์ดูไพ่ฟรีรายเดือน — แม่หมอจันทรา')

@section('content')
{{-- Landing page: รับสิทธิ์ดูไพ่ฟรี 1 ใบประจำเดือน
     ลูกค้ามาจากโพสต์ในกลุ่ม FB → กด "เปิด Messenger" → ref ไป webhook → grant credit --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 relative overflow-hidden pt-16 lg:pt-20">

    {{-- พื้นหลัง stars --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        @for ($i = 0; $i < 30; $i++)
            <div class="absolute w-1 h-1 bg-white rounded-full"
                 style="top: {{ rand(0, 100) }}%; left: {{ rand(0, 100) }}%;
                        opacity: {{ rand(20, 80) / 100 }};
                        animation: twinkle {{ rand(2, 5) }}s ease-in-out infinite;"></div>
        @endfor
    </div>

    <style>
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.5); }
        }
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        .moon-glow {
            box-shadow: 0 0 60px rgba(251, 191, 36, 0.5), 0 0 100px rgba(251, 191, 36, 0.2);
        }
        .btn-glow {
            box-shadow: 0 0 30px rgba(168, 85, 247, 0.6), 0 0 60px rgba(168, 85, 247, 0.3);
            transition: all 0.3s ease;
        }
        .btn-glow:hover {
            box-shadow: 0 0 50px rgba(168, 85, 247, 0.9), 0 0 100px rgba(168, 85, 247, 0.5);
            transform: scale(1.05);
        }
    </style>

    {{-- ภาพประกอบถุงทองรางวัล (เจนเอง เก็บที่ public/images/art) --}}
    <x-art.backdrop image="cos-reward" tone="dark" :opacity="0.42" mask="fade-bottom" height="min(72vh, 640px)" />

    <div class="relative max-w-2xl mx-auto px-4 py-8 sm:py-16">

        {{-- การ์ดหลัก --}}
        <div class="bg-gradient-to-br from-purple-900/40 to-indigo-900/40 backdrop-blur-xl rounded-3xl border border-purple-500/30 p-6 sm:p-10 shadow-2xl">

            {{-- พระจันทร์เสี้ยว --}}
            <div class="flex justify-center mb-6">
                <div class="text-7xl sm:text-8xl moon-glow rounded-full"
                     style="animation: float-slow 4s ease-in-out infinite;">
                    🌙
                </div>
            </div>

            {{-- หัวข้อ --}}
            <h1 class="text-3xl sm:text-4xl font-bold text-center text-white mb-3">
                ✨ สิทธิพิเศษเดือน{{ $thaiMonth }}
            </h1>
            <p class="text-center text-purple-200 text-lg mb-8">
                สำหรับสมาชิกในกลุ่มแม่หมอจันทรา
            </p>

            {{-- รายละเอียดสิทธิ์ --}}
            <div class="bg-black/30 rounded-2xl p-5 mb-6 border border-purple-400/20">
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-3xl">🎁</span>
                    <div>
                        <div class="text-yellow-300 font-semibold text-lg">สิทธิ์ดูไพ่ฟรี 1 ใบ</div>
                        <div class="text-purple-200 text-sm">
                            ใช้ดูดวงสถานการณ์ปัจจุบันกับแม่หมอจันทรา
                        </div>
                    </div>
                </div>
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-3xl">🔮</span>
                    <div>
                        <div class="text-yellow-300 font-semibold text-lg">แม่หมอเลือกไพ่ให้</div>
                        <div class="text-purple-200 text-sm">
                            สุ่มไพ่จากสำรับ 78 ใบ + ทำนายเฉพาะคุณ
                        </div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-3xl">📅</span>
                    <div>
                        <div class="text-yellow-300 font-semibold text-lg">รีเซ็ตทุกวันที่ 1</div>
                        <div class="text-purple-200 text-sm">
                            กดได้ครั้งเดียวต่อเดือน — เดือนหน้ามาใหม่
                        </div>
                    </div>
                </div>
            </div>

            {{-- ปุ่มเปิด Messenger --}}
            @if ($messengerUrl)
                <a href="{{ $messengerUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-glow block w-full bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 text-white text-xl font-bold py-5 px-6 rounded-2xl text-center mb-4">
                    💬 เปิด Messenger รับสิทธิ์
                </a>
                <p class="text-center text-purple-300 text-sm">
                    คลิกแล้วระบบจะเปิด Messenger อัตโนมัติ
                </p>
            @else
                <div class="bg-red-500/20 border border-red-500/40 rounded-xl p-4 text-center text-red-200">
                    ⚠️ ระบบยังไม่ได้ตั้งค่า Facebook Page
                </div>
            @endif

            {{-- ลิงก์กลุ่ม --}}
            @if ($groupUrl)
                <div class="mt-6 pt-6 border-t border-purple-400/20">
                    <p class="text-center text-purple-200 mb-3">ยังไม่ได้เป็นสมาชิกกลุ่ม?</p>
                    <a href="{{ $groupUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="block w-full bg-blue-600/30 hover:bg-blue-600/50 border border-blue-400/40 text-white py-3 px-6 rounded-xl text-center transition">
                        🌟 เข้ากลุ่มแม่หมอจันทรา
                    </a>
                </div>
            @endif

            {{-- เงื่อนไข --}}
            <div class="mt-6 text-center text-xs text-purple-400/70">
                สิทธิ์นี้ใช้ได้ภายในเดือน{{ $thaiMonth }} เท่านั้น<br>
                กดได้ครั้งเดียวต่อบัญชี Facebook
            </div>
        </div>
    </div>
</div>
@endsection
