{{--
    Trust Score Show - รายละเอียด Trust Score
    แสดงข้อมูลละเอียดและปรับแต่งคะแนน
--}}
@extends('layouts.admin-v3')

@section('title', 'รายละเอียด Trust Score')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="trustScoreDetail()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
                        <li>
                            <a href="{{ route('admin.anti-abuse.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                Anti-Abuse
                            </a>
                        </li>
                        <li><span class="mx-2 text-gray-400">/</span></li>
                        <li>
                            <a href="{{ route('admin.anti-abuse.trust-scores') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                Trust Scores
                            </a>
                        </li>
                        <li><span class="mx-2 text-gray-400">/</span></li>
                        <li class="text-gray-700 dark:text-gray-200">รายละเอียด</li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    รายละเอียด Trust Score
                </h1>
            </div>
            <a href="{{ route('admin.anti-abuse.trust-scores') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับไปรายการ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column - User Info & Score --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- User/Provider Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="text-center">
                    {{-- Avatar --}}
                    <div class="mb-4">
                        @if($trustScore->entity_type === 'user' && $trustScore->user)
                            {{-- ใช้ profile_picture_url ซึ่งเป็น accessor ที่ถูกต้องของ User model --}}
                            <div class="mx-auto w-24 h-24 border-4 border-{{ $trustScore->getTrustLevelColor() }}-500 rounded-full overflow-hidden">
                                <x-user-avatar :user="$trustScore->user" size="2xl" :ring="false" class="w-full h-full" />
                            </div>
                        @elseif($trustScore->entity_type === 'provider' && $trustScore->provider)
                            <img class="h-24 w-24 rounded-full object-cover mx-auto border-4 border-{{ $trustScore->getTrustLevelColor() }}-500"
                                 src="{{ $trustScore->provider->logo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($trustScore->provider->name ?? 'P') . '&size=96' }}"
                                 alt="{{ $trustScore->provider->name ?? 'Provider' }}">
                        @else
                            <div class="h-24 w-24 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center mx-auto">
                                <i class="fas fa-user text-3xl text-gray-500 dark:text-gray-400"></i>
                            </div>
                        @endif
                    </div>

                    {{-- Name --}}
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        @if($trustScore->entity_type === 'user' && $trustScore->user)
                            {{ $trustScore->user->name }}
                        @elseif($trustScore->entity_type === 'provider' && $trustScore->provider)
                            {{ $trustScore->provider->name ?? 'Provider #' . $trustScore->provider_id }}
                        @else
                            ไม่พบข้อมูล
                        @endif
                    </h2>

                    {{-- Entity Type Badge --}}
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-2
                        {{ $trustScore->entity_type === 'user' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                        <i class="fas {{ $trustScore->entity_type === 'user' ? 'fa-user' : 'fa-store' }} mr-2"></i>
                        {{ $trustScore->entity_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ' }}
                    </span>

                    {{-- Trust Level --}}
                    @php
                        $levelColors = [
                            'verified' => 'bg-purple-500',
                            'trusted' => 'bg-green-500',
                            'standard' => 'bg-blue-500',
                            'new' => 'bg-gray-500',
                            'warning' => 'bg-yellow-500',
                            'restricted' => 'bg-orange-500',
                            'suspended' => 'bg-red-500',
                            'banned' => 'bg-black',
                        ];
                    @endphp
                    <div class="mt-4">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold text-white {{ $levelColors[$trustScore->trust_level] ?? 'bg-gray-500' }}">
                            {{ $trustScore->getTrustLevelLabel() }}
                        </span>
                    </div>
                </div>

                {{-- Main Trust Score --}}
                <div class="mt-6 text-center">
                    <div class="relative inline-block">
                        <svg class="w-32 h-32 transform -rotate-90">
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none"
                                    class="text-gray-200 dark:text-gray-600"/>
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none"
                                    stroke-dasharray="{{ 352 * ($trustScore->trust_score / 100) }} 352"
                                    class="{{ $trustScore->trust_score >= 80 ? 'text-green-500' : ($trustScore->trust_score >= 60 ? 'text-blue-500' : ($trustScore->trust_score >= 40 ? 'text-yellow-500' : 'text-red-500')) }}"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($trustScore->trust_score, 1) }}
                        </span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Trust Score</p>
                </div>

                {{-- Status Alerts --}}
                @if($trustScore->banned_at)
                    <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-ban text-red-600 dark:text-red-400 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">ถูกแบนแล้ว</p>
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $trustScore->ban_reason }}</p>
                                <p class="text-xs text-red-600 dark:text-red-400">เมื่อ {{ $trustScore->banned_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                @elseif($trustScore->suspended_until && $trustScore->suspended_until->isFuture())
                    <div class="mt-6 p-4 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-pause-circle text-orange-600 dark:text-orange-400 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-orange-800 dark:text-orange-200">ถูกระงับชั่วคราว</p>
                                <p class="text-xs text-orange-600 dark:text-orange-400">{{ $trustScore->suspension_reason }}</p>
                                <p class="text-xs text-orange-600 dark:text-orange-400">ถึง {{ $trustScore->suspended_until->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div class="mt-6 space-y-2">
                    @if(!$trustScore->banned_at)
                        <button @click="showAdjustModal = true"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-sliders-h mr-2"></i>
                            ปรับคะแนน
                        </button>
                        <button @click="showSuspendModal = true"
                                class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                            <i class="fas fa-pause-circle mr-2"></i>
                            ระงับชั่วคราว
                        </button>
                        <button @click="showBanModal = true"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-ban mr-2"></i>
                            แบน
                        </button>
                    @else
                        <form action="{{ route('admin.anti-abuse.trust-scores.unban', $trustScore) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                                    onclick="return confirm('ยืนยันการปลดแบน?')">
                                <i class="fas fa-unlock mr-2"></i>
                                ปลดแบน
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Restrictions Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-lock text-gray-500 mr-2"></i>
                    ข้อจำกัดการใช้งาน
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ต้องจ่ายล่วงหน้า</span>
                        @if($trustScore->requires_prepayment)
                            <span class="text-red-600 dark:text-red-400"><i class="fas fa-check"></i> ใช่</span>
                        @else
                            <span class="text-green-600 dark:text-green-400"><i class="fas fa-times"></i> ไม่</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ต้องวางมัดจำ</span>
                        @if($trustScore->requires_deposit)
                            <span class="text-red-600 dark:text-red-400"><i class="fas fa-check"></i> ใช่</span>
                        @else
                            <span class="text-green-600 dark:text-green-400"><i class="fas fa-times"></i> ไม่</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">วงเงินสูงสุด</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $trustScore->max_booking_value ? '฿' . number_format($trustScore->max_booking_value) : 'ไม่จำกัด' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">จองพร้อมกันได้</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $trustScore->max_active_bookings ?? 'ไม่จำกัด' }} รายการ
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Score Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Score Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-chart-pie text-blue-500 mr-2"></i>
                    รายละเอียดคะแนน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $scoreCategories = [
                            'payment_score' => ['label' => 'การชำระเงิน', 'icon' => 'fa-credit-card', 'color' => 'green', 'weight' => 30],
                            'cancellation_score' => ['label' => 'การยกเลิก', 'icon' => 'fa-times-circle', 'color' => 'red', 'weight' => 25],
                            'punctuality_score' => ['label' => 'ความตรงต่อเวลา', 'icon' => 'fa-clock', 'color' => 'blue', 'weight' => 15],
                            'behavior_score' => ['label' => 'พฤติกรรม', 'icon' => 'fa-smile', 'color' => 'purple', 'weight' => 20],
                            'verification_score' => ['label' => 'การยืนยันตัวตน', 'icon' => 'fa-id-card', 'color' => 'yellow', 'weight' => 10],
                        ];
                    @endphp

                    @foreach($scoreCategories as $field => $config)
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <i class="fas {{ $config['icon'] }} text-{{ $config['color'] }}-500 mr-2"></i>
                                    {{ $config['label'] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    น้ำหนัก {{ $config['weight'] }}%
                                </span>
                            </div>
                            <div class="flex items-center">
                                <div class="flex-1 mr-3">
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                                        <div class="bg-{{ $config['color'] }}-500 h-3 rounded-full transition-all duration-500"
                                             style="width: {{ $trustScore->$field ?? 100 }}%"></div>
                                    </div>
                                </div>
                                <span class="text-lg font-bold text-gray-900 dark:text-white w-12 text-right">
                                    {{ number_format($trustScore->$field ?? 100, 0) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Statistics --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                    สถิติการใช้งาน
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $trustScore->total_bookings ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">การจองทั้งหมด</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $trustScore->completed_bookings ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">สำเร็จ</p>
                    </div>
                    <div class="text-center p-4 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $trustScore->cancelled_bookings ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ยกเลิก</p>
                    </div>
                    <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $trustScore->no_show_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ไม่มา</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $trustScore->late_cancellation_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ยกเลิกสาย</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $trustScore->dispute_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ข้อพิพาท</p>
                    </div>
                    <div class="text-center p-4 bg-pink-50 dark:bg-pink-900/30 rounded-lg">
                        <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ $trustScore->dispute_lost_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">แพ้ข้อพิพาท</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ $trustScore->warnings_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">คำเตือน</p>
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-history text-purple-500 mr-2"></i>
                    กิจกรรมล่าสุด
                </h3>

                <div class="space-y-4">
                    @forelse($recentActivities ?? [] as $activity)
                        <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-{{ $activity['color'] ?? 'gray' }}-100 dark:bg-{{ $activity['color'] ?? 'gray' }}-900 flex items-center justify-center">
                                    <i class="fas {{ $activity['icon'] ?? 'fa-circle' }} text-{{ $activity['color'] ?? 'gray' }}-500 text-xs"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $activity['title'] ?? 'กิจกรรม' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity['description'] ?? '' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ $activity['date'] ?? '' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">ไม่มีกิจกรรมล่าสุด</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Adjust Score Modal --}}
    <div x-show="showAdjustModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="adjust-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAdjustModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showAdjustModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showAdjustModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.anti-abuse.trust-scores.adjust', $trustScore) }}" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-sliders-h text-blue-500 mr-2"></i>
                            ปรับคะแนน Trust Score
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    หมวดหมู่ที่ต้องการปรับ <span class="text-red-500">*</span>
                                </label>
                                <select name="category" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                    <option value="payment_score">การชำระเงิน ({{ $trustScore->payment_score ?? 100 }})</option>
                                    <option value="cancellation_score">การยกเลิก ({{ $trustScore->cancellation_score ?? 100 }})</option>
                                    <option value="punctuality_score">ความตรงต่อเวลา ({{ $trustScore->punctuality_score ?? 100 }})</option>
                                    <option value="behavior_score">พฤติกรรม ({{ $trustScore->behavior_score ?? 100 }})</option>
                                    <option value="verification_score">การยืนยันตัวตน ({{ $trustScore->verification_score ?? 100 }})</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    การปรับ <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center space-x-4">
                                    <select name="adjustment_type"
                                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                        <option value="add">+ เพิ่ม</option>
                                        <option value="subtract">- ลด</option>
                                        <option value="set">= ตั้งค่า</option>
                                    </select>
                                    <input type="number" name="amount" min="0" max="100" step="0.1" required
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="จำนวน">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    เหตุผล <span class="text-red-500">*</span>
                                </label>
                                <textarea name="reason" rows="3" required
                                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="ระบุเหตุผลในการปรับคะแนน..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-save mr-2"></i>
                            บันทึก
                        </button>
                        <button type="button"
                                @click="showAdjustModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                <form action="{{ route('admin.anti-abuse.trust-scores.suspend', $trustScore) }}" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-pause-circle text-orange-500 mr-2"></i>
                            ระงับการใช้งานชั่วคราว
                        </h3>

                        <div class="space-y-4">
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
                <form action="{{ route('admin.anti-abuse.trust-scores.ban', $trustScore) }}" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-ban text-red-500 mr-2"></i>
                            แบนถาวร
                        </h3>

                        <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-lg mb-4">
                            <p class="text-sm text-red-700 dark:text-red-300">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>คำเตือน:</strong> การแบนถาวรจะทำให้ผู้ใช้ไม่สามารถใช้งานระบบได้อีก สามารถปลดแบนได้ภายหลัง
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                เหตุผล <span class="text-red-500">*</span>
                            </label>
                            <textarea name="reason" rows="3" required
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500"
                                      placeholder="ระบุเหตุผลในการแบน..."></textarea>
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
function trustScoreDetail() {
    return {
        showAdjustModal: false,
        showSuspendModal: false,
        showBanModal: false
    }
}
</script>
@endpush
@endsection
