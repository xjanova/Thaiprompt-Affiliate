@extends('demo.layout')

@section('title', 'Theme 2: Glassmorphism')

@section('content')
{{-- Background Gradient --}}
<div class="fixed inset-0 bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 -z-10"></div>

{{-- Animated Background Circles --}}
<div class="fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Theme Header --}}
    <div class="text-center mb-12 animate-fade-in">
        <h1 class="text-5xl md:text-6xl font-bold mb-4 text-white drop-shadow-[0_4px_12px_rgba(0,0,0,0.3)]">
            Glassmorphism Theme
        </h1>
        <p class="text-xl text-white/90 drop-shadow-lg">
            เน้นความโปร่งใส Backdrop Blur สไตล์ Glass Minimal & Modern
        </p>
    </div>

    {{-- Stats Cards (Glass Style) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 animate-scale-in">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                    +12.5%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-white mb-1">12,584</h3>
            <p class="text-sm text-white/80">ผู้ใช้งานทั้งหมด</p>
        </div>

        <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 animate-scale-in" style="animation-delay: 100ms;">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
                <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                    +8.3%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-white mb-1">฿145,678</h3>
            <p class="text-sm text-white/80">ยอดขายวันนี้</p>
        </div>

        <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 animate-scale-in" style="animation-delay: 200ms;">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <i class="fas fa-file-invoice text-white text-xl"></i>
                </div>
                <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                    +15.2%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-white mb-1">389</h3>
            <p class="text-sm text-white/80">คำสั่งซื้อใหม่</p>
        </div>

        <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 animate-scale-in" style="animation-delay: 300ms;">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                </div>
                <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                    +22.7%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-white mb-1">฿2.5M</h3>
            <p class="text-sm text-white/80">รายได้รวม</p>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chart Card (Glass) --}}
        <div class="lg:col-span-2 animate-slide-up">
            <div class="glass rounded-2xl overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-white/20">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                        <h2 class="text-xl font-bold text-white">สถิติการขาย</h2>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="p-6">
                    <div class="h-64 flex items-center justify-center bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                        <div class="text-center">
                            <i class="fas fa-chart-area text-6xl text-white/70 mb-4"></i>
                            <p class="text-white/90">Glassmorphism Chart</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Card (Glass) --}}
        <div class="animate-slide-up" style="animation-delay: 200ms;">
            <div class="glass rounded-2xl overflow-hidden h-full">
                <div class="px-6 py-4 border-b border-white/20">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-2xl text-white"></i>
                        <h2 class="text-xl font-bold text-white">กิจกรรมล่าสุด</h2>
                    </div>
                </div>

                <div class="p-6 space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                        <div class="w-10 h-10 bg-green-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shopping-cart text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">คำสั่งซื้อใหม่ #12345</p>
                            <p class="text-xs text-white/70">5 นาทีที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                        <div class="w-10 h-10 bg-blue-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-plus text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">สมาชิกใหม่ลงทะเบียน</p>
                            <p class="text-xs text-white/70">15 นาทีที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                        <div class="w-10 h-10 bg-yellow-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">สินค้าใกล้หมด</p>
                            <p class="text-xs text-white/70">1 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                        <div class="w-10 h-10 bg-purple-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">รีวิวใหม่ 5 ดาว</p>
                            <p class="text-xs text-white/70">2 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature Cards (Glass) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass rounded-2xl p-8 hover:bg-white/20 transition-all duration-300 animate-scale-in">
            <div class="w-16 h-16 bg-white/30 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-feather-alt text-white text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-3">เบาและเร็ว</h3>
            <p class="text-white/80 mb-6">
                ใช้ backdrop-filter และ transparency เพื่อสร้าง UI ที่เบาและทันสมัย
            </p>
            <button class="w-full px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl transition-all">
                เรียนรู้เพิ่มเติม
            </button>
        </div>

        <div class="glass rounded-2xl p-8 hover:bg-white/20 transition-all duration-300 animate-scale-in" style="animation-delay: 100ms;">
            <div class="w-16 h-16 bg-white/30 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-eye text-white text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-3">โปร่งใสสวยงาม</h3>
            <p class="text-white/80 mb-6">
                Glassmorphism ทำให้ UI ดูโปร่งแสง minimal และมีความลึก
            </p>
            <button class="w-full px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl transition-all">
                ดูตัวอย่าง
            </button>
        </div>

        <div class="glass rounded-2xl p-8 hover:bg-white/20 transition-all duration-300 animate-scale-in" style="animation-delay: 200ms;">
            <div class="w-16 h-16 bg-white/30 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-sparkles text-white text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-3">ทันสมัย</h3>
            <p class="text-white/80 mb-6">
                เทรนด์ดีไซน์ล่าสุดที่ Apple, Microsoft และ Google ใช้
            </p>
            <button class="w-full px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl transition-all">
                ลองใช้เลย
            </button>
        </div>
    </div>

    {{-- Form Example (Glass) --}}
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="glass rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-white mb-6 text-center">
                ติดต่อเรา
            </h2>

            <form class="space-y-4">
                <div>
                    <label class="block text-white font-medium mb-2">ชื่อ-นามสกุล</label>
                    <input type="text"
                           placeholder="กรอกชื่อของคุณ"
                           class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/50 focus:bg-white/20 focus:border-white/40 focus:outline-none transition-all">
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">อีเมล</label>
                    <input type="email"
                           placeholder="your@email.com"
                           class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/50 focus:bg-white/20 focus:border-white/40 focus:outline-none transition-all">
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">ข้อความ</label>
                    <textarea rows="4"
                              placeholder="เขียนข้อความของคุณ..."
                              class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/50 focus:bg-white/20 focus:border-white/40 focus:outline-none transition-all resize-none"></textarea>
                </div>

                <button type="submit"
                        class="w-full px-6 py-4 bg-white/30 hover:bg-white/40 backdrop-blur-sm border border-white/30 text-white font-bold rounded-xl transition-all transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i>
                    ส่งข้อความ
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
console.log('Theme 2: Glassmorphism loaded');
</script>
@endpush
