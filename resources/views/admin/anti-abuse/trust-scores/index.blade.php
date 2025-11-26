{{--
    Trust Scores Index - รายการคะแนนความน่าเชื่อถือทั้งหมด
    แสดง User และ Provider Trust Scores พร้อมตัวกรอง
--}}
@extends('layouts.admin')

@section('title', 'จัดการคะแนนความน่าเชื่อถือ')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="trustScoreManager()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-shield-alt text-blue-500 mr-3"></i>
                    จัดการคะแนนความน่าเชื่อถือ
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    ดูและจัดการ Trust Score ของผู้ใช้และผู้ให้บริการ
                </p>
            </div>
            <a href="{{ route('admin.anti-abuse.dashboard') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับไป Dashboard
            </a>
        </div>
    </div>

    {{-- Stats Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
        @php
            $trustLevels = [
                'verified' => ['label' => 'ยืนยันแล้ว', 'color' => 'purple', 'icon' => 'fa-check-circle'],
                'trusted' => ['label' => 'น่าเชื่อถือ', 'color' => 'green', 'icon' => 'fa-thumbs-up'],
                'standard' => ['label' => 'ปกติ', 'color' => 'blue', 'icon' => 'fa-user'],
                'new' => ['label' => 'ใหม่', 'color' => 'gray', 'icon' => 'fa-user-plus'],
                'warning' => ['label' => 'เตือน', 'color' => 'yellow', 'icon' => 'fa-exclamation-triangle'],
                'restricted' => ['label' => 'จำกัด', 'color' => 'orange', 'icon' => 'fa-ban'],
            ];
        @endphp
        @foreach($trustLevels as $level => $config)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border-l-4 border-{{ $config['color'] }}-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $config['label'] }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $trustScores->where('trust_level', $level)->count() }}
                        </p>
                    </div>
                    <i class="fas {{ $config['icon'] }} text-2xl text-{{ $config['color'] }}-500"></i>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" action="{{ route('admin.anti-abuse.trust-scores') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Entity Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="entity_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="user" {{ request('entity_type') === 'user' ? 'selected' : '' }}>ผู้ใช้</option>
                    <option value="provider" {{ request('entity_type') === 'provider' ? 'selected' : '' }}>ผู้ให้บริการ</option>
                </select>
            </div>

            {{-- Trust Level Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับความน่าเชื่อถือ</label>
                <select name="trust_level"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($trustLevels as $level => $config)
                        <option value="{{ $level }}" {{ request('trust_level') === $level ? 'selected' : '' }}>
                            {{ $config['label'] }}
                        </option>
                    @endforeach
                    <option value="suspended" {{ request('trust_level') === 'suspended' ? 'selected' : '' }}>ระงับชั่วคราว</option>
                    <option value="banned" {{ request('trust_level') === 'banned' ? 'selected' : '' }}>แบน</option>
                </select>
            </div>

            {{-- Score Range Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คะแนน</label>
                <select name="score_range"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="high" {{ request('score_range') === 'high' ? 'selected' : '' }}>สูง (80+)</option>
                    <option value="medium" {{ request('score_range') === 'medium' ? 'selected' : '' }}>กลาง (50-79)</option>
                    <option value="low" {{ request('score_range') === 'low' ? 'selected' : '' }}>ต่ำ (0-49)</option>
                </select>
            </div>

            {{-- Search & Buttons --}}
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.anti-abuse.trust-scores') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Trust Scores Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้/ผู้ให้บริการ
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Trust Score
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ระดับ
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            การจอง
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ข้อพิพาท
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ข้อจำกัด
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($trustScores as $score)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            {{-- User/Provider Info --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if($score->entity_type === 'user' && $score->user)
                                            {{-- ใช้ profile_picture_url ซึ่งเป็น accessor ที่ถูกต้องของ User model --}}
                                            <x-user-avatar :user="$score->user" size="md" :ring="false" />
                                        @elseif($score->entity_type === 'provider' && $score->provider)
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 src="{{ $score->provider->logo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($score->provider->name ?? 'P') }}"
                                                 alt="{{ $score->provider->name ?? 'Provider' }}">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500 dark:text-gray-400"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            @if($score->entity_type === 'user' && $score->user)
                                                {{ $score->user->name }}
                                            @elseif($score->entity_type === 'provider' && $score->provider)
                                                {{ $score->provider->name ?? 'Provider #' . $score->provider_id }}
                                            @else
                                                ไม่พบข้อมูล
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                {{ $score->entity_type === 'user' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                                {{ $score->entity_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Trust Score --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center">
                                    <div class="relative w-16 h-16">
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none"
                                                    class="text-gray-200 dark:text-gray-600"/>
                                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none"
                                                    stroke-dasharray="{{ 176 * ($score->trust_score / 100) }} 176"
                                                    class="{{ $score->trust_score >= 80 ? 'text-green-500' : ($score->trust_score >= 60 ? 'text-blue-500' : ($score->trust_score >= 40 ? 'text-yellow-500' : 'text-red-500')) }}"/>
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-900 dark:text-white">
                                            {{ number_format($score->trust_score, 0) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Trust Level --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $levelColors = [
                                        'verified' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                        'trusted' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'standard' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'new' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'restricted' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'banned' => 'bg-black text-white',
                                    ];
                                @endphp
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $levelColors[$score->trust_level] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $score->getTrustLevelLabel() }}
                                </span>
                            </td>

                            {{-- Bookings --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $score->completed_bookings ?? 0 }}/{{ $score->total_bookings ?? 0 }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    สำเร็จ/ทั้งหมด
                                </div>
                            </td>

                            {{-- Disputes --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center space-y-1">
                                    @if(($score->dispute_count ?? 0) > 0)
                                        <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                                            {{ $score->dispute_count }} ครั้ง
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            แพ้ {{ $score->dispute_lost_count ?? 0 }}
                                        </span>
                                    @else
                                        <span class="text-sm text-green-600 dark:text-green-400">
                                            ไม่มี
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Restrictions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center space-y-1 text-xs">
                                    @if($score->requires_prepayment)
                                        <span class="text-orange-600 dark:text-orange-400">
                                            <i class="fas fa-money-bill mr-1"></i>ต้องจ่ายล่วงหน้า
                                        </span>
                                    @endif
                                    @if($score->max_booking_value)
                                        <span class="text-blue-600 dark:text-blue-400">
                                            <i class="fas fa-tag mr-1"></i>วงเงินสูงสุด ฿{{ number_format($score->max_booking_value) }}
                                        </span>
                                    @endif
                                    @if($score->suspended_until && $score->suspended_until->isFuture())
                                        <span class="text-red-600 dark:text-red-400">
                                            <i class="fas fa-clock mr-1"></i>ระงับถึง {{ $score->suspended_until->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if($score->banned_at)
                                        <span class="text-red-600 dark:text-red-400 font-bold">
                                            <i class="fas fa-ban mr-1"></i>ถูกแบน
                                        </span>
                                    @endif
                                    @if(!$score->requires_prepayment && !$score->max_booking_value && !$score->banned_at && !($score->suspended_until && $score->suspended_until->isFuture()))
                                        <span class="text-green-600 dark:text-green-400">
                                            <i class="fas fa-check mr-1"></i>ไม่มีข้อจำกัด
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.anti-abuse.trust-scores.show', $score) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$score->banned_at)
                                        <button @click="openSuspendModal({{ $score->id }}, '{{ $score->entity_type }}', '{{ $score->entity_type === 'user' ? ($score->user->name ?? 'User') : ($score->provider->name ?? 'Provider') }}')"
                                                class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300"
                                                title="ระงับชั่วคราว">
                                            <i class="fas fa-pause-circle"></i>
                                        </button>
                                        <button @click="openBanModal({{ $score->id }}, '{{ $score->entity_type }}', '{{ $score->entity_type === 'user' ? ($score->user->name ?? 'User') : ($score->provider->name ?? 'Provider') }}')"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="แบน">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @else
                                        <form action="{{ route('admin.anti-abuse.trust-scores.unban', $score) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    title="ปลดแบน"
                                                    onclick="return confirm('ยืนยันการปลดแบน?')">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-shield-alt text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูล Trust Score</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($trustScores->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $trustScores->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Suspend Modal --}}
    <div x-show="showSuspendModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="suspend-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showSuspendModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showSuspendModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showSuspendModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="'/admin/anti-abuse/trust-scores/' + suspendScoreId + '/suspend'" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-pause-circle text-orange-600 dark:text-orange-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="suspend-modal-title">
                                    ระงับการใช้งานชั่วคราว
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="suspendUserName"></p>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            ระยะเวลา (วัน) <span class="text-red-500">*</span>
                                        </label>
                                        <select name="days" required
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500">
                                            <option value="1">1 วัน</option>
                                            <option value="3">3 วัน</option>
                                            <option value="7" selected>7 วัน</option>
                                            <option value="14">14 วัน</option>
                                            <option value="30">30 วัน</option>
                                            <option value="90">90 วัน</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            เหตุผล <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="reason" rows="3" required
                                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500"
                                                  placeholder="ระบุเหตุผลในการระงับ..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-pause-circle mr-2"></i>
                            ระงับการใช้งาน
                        </button>
                        <button type="button"
                                @click="showSuspendModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Ban Modal --}}
    <div x-show="showBanModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="ban-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showBanModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showBanModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showBanModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="'/admin/anti-abuse/trust-scores/' + banScoreId + '/ban'" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="ban-modal-title">
                                    แบนถาวร
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="banUserName"></p>

                                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                                    <p class="text-sm text-red-700 dark:text-red-300">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <strong>คำเตือน:</strong> การแบนถาวรจะทำให้ผู้ใช้ไม่สามารถใช้งานระบบได้อีก สามารถปลดแบนได้ภายหลัง
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        เหตุผล <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="reason" rows="3" required
                                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500"
                                              placeholder="ระบุเหตุผลในการแบน..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-ban mr-2"></i>
                            ยืนยันแบน
                        </button>
                        <button type="button"
                                @click="showBanModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function trustScoreManager() {
    return {
        // Suspend Modal
        showSuspendModal: false,
        suspendScoreId: null,
        suspendUserName: '',

        // Ban Modal
        showBanModal: false,
        banScoreId: null,
        banUserName: '',

        openSuspendModal(scoreId, entityType, name) {
            this.suspendScoreId = scoreId;
            this.suspendUserName = (entityType === 'user' ? 'ผู้ใช้: ' : 'ผู้ให้บริการ: ') + name;
            this.showSuspendModal = true;
        },

        openBanModal(scoreId, entityType, name) {
            this.banScoreId = scoreId;
            this.banUserName = (entityType === 'user' ? 'ผู้ใช้: ' : 'ผู้ให้บริการ: ') + name;
            this.showBanModal = true;
        }
    }
}
</script>
@endpush
@endsection
