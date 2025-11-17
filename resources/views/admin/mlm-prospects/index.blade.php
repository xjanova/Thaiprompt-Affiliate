@extends('layouts.admin-v3')

@section('title', 'จัดการผู้มุ่งหวัง MLM')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 dark:from-orange-800 dark:via-amber-800 dark:to-yellow-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-user-plus text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการผู้มุ่งหวัง MLM</h1>
                        <p class="text-amber-100 text-lg mt-1">ติดตามและจัดการผู้ที่กำลังสมัครผ่าน LINE Signup Flow</p>
                    </div>
                </div>

                {{-- Expire Old Button --}}
                <form method="POST" action="{{ route('admin.mlm-prospects.expire-old') }}" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะหมดอายุรายการเก่า?');" class="flex-shrink-0">
                    @csrf
                    <button type="submit"
                            class="glass-fusion hover:bg-white/20 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                        <i class="fas fa-clock"></i>
                        หมดอายุรายการเก่า
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        {{-- Total --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['pending']) }}</p>
                </div>
            </div>
        </div>

        {{-- In Progress --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="fas fa-spinner fa-pulse text-white text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">กำลังดำเนินการ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['in_progress']) }}</p>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">สำเร็จ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['completed']) }}</p>
                </div>
            </div>
        </div>

        {{-- Expired --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="fas fa-times-circle text-white text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">หมดอายุ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['expired']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-filter text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">กรองข้อมูล</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">ค้นหาและกรองรายการตามเงื่อนไข</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.mlm-prospects.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Search --}}
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1"></i>
                    ค้นหา
                </label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ request('search') }}"
                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm"
                       placeholder="ชื่อ LINE, Token, LINE User ID">
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-tag mr-1"></i>
                    สถานะ
                </label>
                <select name="status"
                        id="status"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col justify-end gap-3">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 dark:from-orange-500 dark:to-amber-500 dark:hover:from-orange-600 dark:hover:to-amber-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.mlm-prospects.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-semibold shadow-lg transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i>
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    {{-- Prospects Table --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 px-8 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <i class="fas fa-list text-orange-600 dark:text-orange-400"></i>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">รายการผู้มุ่งหวัง</h3>
                <span class="ml-auto text-sm text-gray-600 dark:text-gray-400">ทั้งหมด {{ $prospects->total() }} รายการ</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            วันที่สร้าง
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ข้อมูล LINE
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Referral Token
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ผู้แนะนำ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้ที่สมัคร
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            การดำเนินการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($prospects as $prospect)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- Created Date --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $prospect->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $prospect->created_at->format('H:i') }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- LINE Info --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($prospect->line_picture_url)
                                        <img class="w-12 h-12 rounded-xl object-cover shadow-lg flex-shrink-0"
                                             src="{{ $prospect->line_picture_url }}"
                                             alt="{{ $prospect->line_display_name }}">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center shadow-lg flex-shrink-0">
                                            <i class="fab fa-line text-white text-xl"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $prospect->line_display_name ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">
                                            {{ Str::limit($prospect->line_user_id, 20) }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Referral Token --}}
                            <td class="px-6 py-4">
                                <code class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg font-mono shadow-sm">
                                    {{ Str::limit($prospect->referral_token, 16) }}
                                </code>
                            </td>

                            {{-- Sponsor --}}
                            <td class="px-6 py-4">
                                @if($prospect->sponsorUser)
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $prospect->sponsorUser->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $prospect->sponsorUser->email }}</div>
                                @elseif($prospect->sponsorMember)
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $prospect->sponsorMember->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">สมาชิก #{{ $prospect->sponsorMember->id }}</div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">ไม่มีผู้แนะนำ</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4">
                                @php
                                    $statusConfig = [
                                        'pending' => ['color' => 'yellow', 'bg' => 'from-yellow-100 to-yellow-200 dark:from-yellow-900/30 dark:to-yellow-800/30', 'text' => 'text-yellow-800 dark:text-yellow-300', 'border' => 'border-yellow-300 dark:border-yellow-700', 'icon' => 'fa-clock', 'label' => 'รอดำเนินการ'],
                                        'in_progress' => ['color' => 'indigo', 'bg' => 'from-indigo-100 to-indigo-200 dark:from-indigo-900/30 dark:to-indigo-800/30', 'text' => 'text-indigo-800 dark:text-indigo-300', 'border' => 'border-indigo-300 dark:border-indigo-700', 'icon' => 'fa-spinner fa-pulse', 'label' => 'กำลังดำเนินการ'],
                                        'completed' => ['color' => 'green', 'bg' => 'from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30', 'text' => 'text-green-800 dark:text-green-300', 'border' => 'border-green-300 dark:border-green-700', 'icon' => 'fa-check-circle', 'label' => 'สำเร็จ'],
                                        'expired' => ['color' => 'red', 'bg' => 'from-red-100 to-red-200 dark:from-red-900/30 dark:to-red-800/30', 'text' => 'text-red-800 dark:text-red-300', 'border' => 'border-red-300 dark:border-red-700', 'icon' => 'fa-times-circle', 'label' => 'หมดอายุ'],
                                    ];
                                    $config = $statusConfig[$prospect->status] ?? ['color' => 'gray', 'bg' => 'from-gray-100 to-gray-200 dark:from-gray-900/30 dark:to-gray-800/30', 'text' => 'text-gray-800 dark:text-gray-300', 'border' => 'border-gray-300 dark:border-gray-700', 'icon' => 'fa-question-circle', 'label' => $prospect->status];
                                @endphp
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-br {{ $config['bg'] }} border-2 {{ $config['border'] }} rounded-xl {{ $config['text'] }} text-xs font-semibold shadow-sm">
                                    <i class="fas {{ $config['icon'] }}"></i>
                                    {{ $config['label'] }}
                                </span>
                            </td>

                            {{-- Registered User --}}
                            <td class="px-6 py-4">
                                @if($prospect->registeredUser)
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user-check text-green-600 dark:text-green-400"></i>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $prospect->registeredUser->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $prospect->registered_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">ยังไม่สมัคร</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.mlm-prospects.show', $prospect->id) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600 text-white rounded-lg text-sm font-semibold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                                    <i class="fas fa-eye"></i>
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl flex items-center justify-center">
                                        <i class="fas fa-inbox text-gray-400 dark:text-gray-500 text-4xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">ไม่พบข้อมูลผู้มุ่งหวัง</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">ลองปรับเงื่อนไขการค้นหาใหม่</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($prospects->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $prospects->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
