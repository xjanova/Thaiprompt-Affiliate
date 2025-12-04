@extends('layouts.admin')

@section('title', 'รายได้ - Instructor')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.instructor.dashboard') }}"
           class="p-2 text-gray-400 hover:text-white transition">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">
                <i class="fas fa-coins mr-3"></i>
                รายได้จากคอร์ส
            </h1>
            <p class="text-gray-300 mt-1">สรุปรายได้จากค่าคอมมิชชั่นเมื่อผู้เรียนจบคอร์ส</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total Earnings --}}
        <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-3xl opacity-50"></i>
            </div>
            <p class="text-4xl font-bold">฿{{ number_format(array_sum($monthlyEarnings), 2) }}</p>
            <p class="text-green-100 mt-1">รายได้ทั้งหมด</p>
        </div>

        {{-- Total Courses --}}
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-book-open text-2xl"></i>
                </div>
                <i class="fas fa-graduation-cap text-3xl opacity-50"></i>
            </div>
            <p class="text-4xl font-bold">{{ $myCourses->count() }}</p>
            <p class="text-blue-100 mt-1">คอร์สทั้งหมด</p>
        </div>

        {{-- This Month --}}
        <div class="bg-gradient-to-br from-purple-600 to-pink-700 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar text-2xl"></i>
                </div>
                <i class="fas fa-chart-line text-3xl opacity-50"></i>
            </div>
            @php
                $thisMonth = now()->format('Y-m');
                $thisMonthEarning = $monthlyEarnings[$thisMonth] ?? 0;
            @endphp
            <p class="text-4xl font-bold">฿{{ number_format($thisMonthEarning, 2) }}</p>
            <p class="text-purple-100 mt-1">รายได้เดือนนี้</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Monthly Chart --}}
        <div class="lg:col-span-2 bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
            <h2 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-chart-bar mr-2 text-blue-400"></i>
                รายได้รายเดือน
            </h2>
            @if(count($monthlyEarnings) > 0)
                <div class="space-y-3">
                    @php
                        $maxEarning = max($monthlyEarnings) ?: 1;
                    @endphp
                    @foreach(array_reverse($monthlyEarnings, true) as $month => $earning)
                        @php
                            [$year, $monthNum] = explode('-', $month);
                            $monthNames = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                            $monthLabel = $monthNames[(int)$monthNum] . ' ' . ($year + 543);
                            $percentage = ($earning / $maxEarning) * 100;
                        @endphp
                        <div class="flex items-center gap-4">
                            <div class="w-24 text-sm text-gray-300">{{ $monthLabel }}</div>
                            <div class="flex-1">
                                <div class="h-8 bg-white/10 rounded-lg overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center px-3"
                                         style="width: {{ max($percentage, 5) }}%">
                                        <span class="text-white text-sm font-medium whitespace-nowrap">
                                            ฿{{ number_format($earning, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-chart-bar text-4xl text-gray-500 mb-4"></i>
                    <p class="text-gray-400">ยังไม่มีรายได้</p>
                </div>
            @endif
        </div>

        {{-- Course Earnings --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
            <h2 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-book mr-2 text-purple-400"></i>
                รายได้ตามคอร์ส
            </h2>
            <div class="space-y-4">
                @forelse($myCourses as $course)
                    @php
                        $completions = \App\Models\UserArticleProgress::where('article_id', $course->id)
                            ->where('status', 'completed')
                            ->count();
                        $rewardPerCompletion = ($course->coin_reward ?? 0) + ($course->money_reward ?? 0);
                        $commission = $rewardPerCompletion * ($course->instructor_commission ?? 10) / 100;
                        $courseEarning = $commission * $completions;
                    @endphp
                    <div class="p-4 bg-white/5 rounded-xl">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-white font-medium truncate">{{ $course->title }}</h3>
                                <p class="text-sm text-gray-400 mt-1">
                                    {{ $completions }} คนจบ • {{ $course->instructor_commission ?? 10 }}% ค่าคอมฯ
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-400">฿{{ number_format($courseEarning, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-500 mb-4"></i>
                        <p class="text-gray-400">ยังไม่มีคอร์ส</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- How Commission Works --}}
    <div class="bg-purple-500/10 border border-purple-500/20 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-purple-300 mb-3">
            <i class="fas fa-info-circle mr-2"></i>
            วิธีคำนวณค่าคอมมิชชั่น
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-purple-200">
            <div class="bg-white/5 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 bg-purple-500/30 rounded-full flex items-center justify-center font-bold">1</span>
                    <span class="font-semibold">ผู้เรียนจบคอร์ส</span>
                </div>
                <p class="text-purple-300 text-xs">เมื่อผู้เรียนเรียนจบและผ่าน Quiz (ถ้ามี)</p>
            </div>
            <div class="bg-white/5 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 bg-purple-500/30 rounded-full flex items-center justify-center font-bold">2</span>
                    <span class="font-semibold">ระบบแจกรางวัล</span>
                </div>
                <p class="text-purple-300 text-xs">ผู้เรียนได้รับ Coins และเงินตามที่ตั้งไว้</p>
            </div>
            <div class="bg-white/5 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 bg-purple-500/30 rounded-full flex items-center justify-center font-bold">3</span>
                    <span class="font-semibold">Instructor ได้ค่าคอมฯ</span>
                </div>
                <p class="text-purple-300 text-xs">คุณได้รับ % จากรางวัลที่แจกออกไป</p>
            </div>
        </div>
        <p class="text-purple-300 text-xs mt-4">
            <i class="fas fa-calculator mr-1"></i>
            ตัวอย่าง: คอร์สแจก 100 Coins, ค่าคอมฯ 10% = คุณได้ 10 Coins/คน
        </p>
    </div>
</div>
@endsection
