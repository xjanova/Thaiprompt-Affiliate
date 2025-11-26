{{--
/**
 * ID Card Background - พื้นหลังบัตรตาม Rank
 *
 * ออกแบบให้สวยงามแตกต่างกัน 8 ระดับ:
 * Level 1: Bronze - สีทองแดงเรียบง่าย
 * Level 2: Silver - สีเงินมี shimmer
 * Level 3: Gold - สีทองเงางาม gradient
 * Level 4: Platinum - สีแพลตินัม holographic
 * Level 5: Diamond - สีฟ้าเพชร glassmorphism
 * Level 6: Crown - สีทองอมส้ม premium
 * Level 7: Royal - สีม่วงหรู gradient หลากสี
 * Level 8: Legend - สีชมพู-ม่วง สุดอลังการ
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 * @var string $rankColor สีหลักของ Rank
 */
--}}

@switch($rankLevel)
    {{-- Level 1: Bronze (สำริด) - เรียบง่าย สีทองแดง --}}
    @case(1)
        <div class="absolute inset-0 bg-gradient-to-br from-amber-700 via-orange-700 to-amber-800"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
        @break

    {{-- Level 2: Silver (เงิน) - shimmer สีเงิน --}}
    @case(2)
        <div class="absolute inset-0 bg-gradient-to-br from-gray-400 via-gray-300 to-gray-500"></div>
        <div class="absolute inset-0 bg-gradient-to-tl from-white/20 via-transparent to-white/10"></div>
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
        </div>
        @break

    {{-- Level 3: Gold (ทอง) - เงางาม gradient สีทอง --}}
    @case(3)
        <div class="absolute inset-0 bg-gradient-to-br from-yellow-500 via-amber-400 to-yellow-600"></div>
        <div class="absolute inset-0 bg-gradient-to-tl from-yellow-300/30 via-transparent to-amber-300/20"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-white/20 via-transparent to-black/10"></div>
        @break

    {{-- Level 4: Platinum (แพลตินัม) - holographic effect --}}
    @case(4)
        <div class="absolute inset-0 bg-gradient-to-br from-slate-300 via-gray-200 to-slate-400"></div>
        <div class="absolute inset-0 bg-gradient-to-tr from-purple-200/30 via-blue-200/20 to-pink-200/30"></div>
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-[conic-gradient(from_0deg,transparent,rgba(255,255,255,0.3),transparent,rgba(255,255,255,0.2),transparent)] animate-spin-slow opacity-50" style="animation-duration: 10s;"></div>
        </div>
        @break

    {{-- Level 5: Diamond (เพชร) - sparkle และ glassmorphism --}}
    @case(5)
        <div class="absolute inset-0 bg-gradient-to-br from-cyan-400 via-sky-300 to-blue-500"></div>
        <div class="absolute inset-0 bg-gradient-to-tl from-white/40 via-transparent to-cyan-200/30"></div>
        <div class="absolute inset-0 backdrop-blur-sm">
            <div class="absolute top-4 left-10 w-2 h-2 bg-white rounded-full animate-sparkle" style="animation-delay: 0s;"></div>
            <div class="absolute top-16 right-20 w-1.5 h-1.5 bg-white rounded-full animate-sparkle" style="animation-delay: 0.5s;"></div>
            <div class="absolute bottom-20 left-1/4 w-2 h-2 bg-white rounded-full animate-sparkle" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/3 right-10 w-1 h-1 bg-white rounded-full animate-sparkle" style="animation-delay: 1.5s;"></div>
        </div>
        @break

    {{-- Level 6: Crown (มงกุฎ) - ทอง premium มงกุฎ --}}
    @case(6)
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500 via-yellow-400 to-orange-500"></div>
        <div class="absolute inset-0 bg-gradient-to-tl from-orange-300/40 via-transparent to-yellow-200/30"></div>
        <div class="absolute inset-0">
            {{-- Crown pattern --}}
            <svg class="absolute -top-4 left-1/2 -translate-x-1/2 w-20 h-16 text-yellow-200/30" viewBox="0 0 100 80" fill="currentColor">
                <path d="M10 70 L10 30 L30 50 L50 20 L70 50 L90 30 L90 70 Z"/>
            </svg>
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-white/30 via-transparent to-black/20"></div>
        @break

    {{-- Level 7: Royal (รอยัล) - ม่วงหรู gradient หลากสี --}}
    @case(7)
        <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-violet-500 to-indigo-600"></div>
        <div class="absolute inset-0 bg-gradient-to-tl from-pink-400/30 via-transparent to-blue-400/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-purple-400/20 via-transparent to-violet-400/20"></div>
        <div class="absolute inset-0">
            {{-- Royal ornament pattern --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <pattern id="royal-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="10" cy="10" r="1" fill="white"/>
                        <path d="M5 5 L15 15 M15 5 L5 15" stroke="white" stroke-width="0.3" opacity="0.5"/>
                    </pattern>
                    <rect width="100" height="100" fill="url(#royal-pattern)"/>
                </svg>
            </div>
        </div>
        @break

    {{-- Level 8: Legend (ตำนาน) - สุดอลังการ particles, gradient, 3D --}}
    @case(8)
        {{-- Base gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-pink-600 via-purple-600 to-indigo-700"></div>

        {{-- Aurora effect --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -inset-[100%] animate-spin-slow opacity-50" style="animation-duration: 20s;">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-cyan-400/30 to-transparent transform rotate-45"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-pink-400/30 to-transparent transform -rotate-45"></div>
            </div>
        </div>

        {{-- Sparkle particles --}}
        <div class="absolute inset-0">
            @for($i = 0; $i < 8; $i++)
                <div class="absolute w-1.5 h-1.5 bg-white rounded-full animate-sparkle"
                     style="top: {{ rand(10, 90) }}%; left: {{ rand(10, 90) }}%; animation-delay: {{ $i * 0.3 }}s;"></div>
            @endfor
        </div>

        {{-- Star burst pattern --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(ellipse_at_center,rgba(255,255,255,0.3)_0%,transparent_70%)]"></div>
        </div>

        {{-- Gradient overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-purple-900/40 via-transparent to-pink-400/20"></div>

        {{-- Glowing border effect --}}
        <div class="absolute inset-0 rounded-2xl border-2 border-pink-400/50 shadow-[inset_0_0_30px_rgba(236,72,153,0.3)]"></div>
        @break

    {{-- Default fallback --}}
    @default
        <div class="absolute inset-0 bg-gradient-to-br from-gray-700 to-gray-900"></div>
@endswitch
