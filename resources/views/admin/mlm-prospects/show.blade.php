@extends('layouts.admin-v3')

@section('title', 'รายละเอียดผู้มุ่งหวัง')

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
                <div class="flex items-center gap-6">
                    {{-- Profile Picture with Ring --}}
                    <div class="flex-shrink-0">
                        @if($prospect->line_profile_picture)
                            <img class="h-24 w-24 rounded-2xl ring-4 ring-white/30 shadow-2xl object-cover transform hover:scale-105 transition-all"
                                 src="{{ $prospect->line_profile_picture }}"
                                 alt="{{ $prospect->line_display_name }}">
                        @else
                            <div class="h-24 w-24 rounded-2xl bg-gradient-to-br from-white/20 to-white/10 flex items-center justify-center ring-4 ring-white/30 shadow-2xl backdrop-blur-sm transform hover:scale-105 transition-all">
                                <i class="fas fa-user text-white text-4xl drop-shadow-lg"></i>
                            </div>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">
                            {{ $prospect->line_display_name ?? 'ไม่ระบุชื่อ' }}
                        </h1>
                        <p class="text-orange-100 text-lg mt-1 flex items-center gap-2">
                            <i class="fas fa-calendar-alt drop-shadow"></i>
                            สร้างเมื่อ: {{ $prospect->created_at->format('d/m/Y H:i') }}
                        </p>
                        <div class="mt-3">
                            @php
                                $statusConfig = [
                                    'pending' => [
                                        'bg' => 'from-yellow-100 to-yellow-200 dark:from-yellow-900/30 dark:to-yellow-800/30',
                                        'text' => 'text-yellow-800 dark:text-yellow-300',
                                        'border' => 'border-yellow-300 dark:border-yellow-700',
                                        'icon' => 'fa-clock',
                                        'label' => 'รอดำเนินการ'
                                    ],
                                    'in_progress' => [
                                        'bg' => 'from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30',
                                        'text' => 'text-blue-800 dark:text-blue-300',
                                        'border' => 'border-blue-300 dark:border-blue-700',
                                        'icon' => 'fa-spinner',
                                        'label' => 'กำลังดำเนินการ'
                                    ],
                                    'completed' => [
                                        'bg' => 'from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30',
                                        'text' => 'text-green-800 dark:text-green-300',
                                        'border' => 'border-green-300 dark:border-green-700',
                                        'icon' => 'fa-check-circle',
                                        'label' => 'สำเร็จ'
                                    ],
                                    'expired' => [
                                        'bg' => 'from-red-100 to-red-200 dark:from-red-900/30 dark:to-red-800/30',
                                        'text' => 'text-red-800 dark:text-red-300',
                                        'border' => 'border-red-300 dark:border-red-700',
                                        'icon' => 'fa-times-circle',
                                        'label' => 'หมดอายุ'
                                    ],
                                ];
                                $config = $statusConfig[$prospect->status] ?? [
                                    'bg' => 'from-gray-100 to-gray-200 dark:from-gray-900/30 dark:to-gray-800/30',
                                    'text' => 'text-gray-800 dark:text-gray-300',
                                    'border' => 'border-gray-300 dark:border-gray-700',
                                    'icon' => 'fa-info-circle',
                                    'label' => $prospect->status
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br {{ $config['bg'] }} border-2 {{ $config['border'] }} rounded-xl {{ $config['text'] }} text-sm font-semibold shadow-lg">
                                <i class="fas {{ $config['icon'] }}"></i>
                                {{ $config['label'] }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Back Button --}}
                <a href="{{ route('admin.mlm-prospects.index') }}"
                   class="glass-fusion hover:bg-white/20 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                    <i class="fas fa-arrow-left"></i>
                    กลับ
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- LINE Information --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fab fa-line text-white text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูล LINE</h2>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">LINE User ID</span>
                        <code class="text-sm bg-white dark:bg-gray-800 px-4 py-2 rounded-lg font-mono text-gray-800 dark:text-gray-200 shadow-sm border border-gray-300 dark:border-gray-600">
                            {{ $prospect->line_user_id }}
                        </code>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Display Name</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ $prospect->line_display_name ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 rounded-xl border border-amber-200 dark:border-amber-700">
                        <span class="text-sm font-medium text-amber-700 dark:text-amber-400">Referral Token</span>
                        <code class="text-sm bg-white dark:bg-gray-800 px-4 py-2 rounded-lg font-mono text-amber-800 dark:text-amber-300 shadow-sm border border-amber-300 dark:border-amber-600 font-bold">
                            {{ $prospect->referral_token }}
                        </code>
                    </div>
                </div>
            </div>

            {{-- Signup Data --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clipboard-list text-white text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลที่เก็บจากการสมัคร</h2>
                </div>

                @if($prospect->signup_data && is_array($prospect->signup_data) && count($prospect->signup_data) > 0)
                    <div class="space-y-4">
                        @foreach($prospect->signup_data as $key => $value)
                            <div class="p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600">
                                <div class="text-sm font-bold text-orange-600 dark:text-orange-400 mb-2 flex items-center gap-2">
                                    <i class="fas fa-tag text-xs"></i>
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                                </div>
                                <div class="text-base text-gray-900 dark:text-gray-100">
                                    @if(is_array($value))
                                        <pre class="text-xs bg-gray-800 dark:bg-gray-900 text-gray-100 p-4 rounded-lg overflow-auto border border-gray-700 shadow-inner">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="font-medium">{{ $value }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                        </div>
                        <p class="text-base text-gray-500 dark:text-gray-400 font-medium">ยังไม่มีข้อมูลที่เก็บ</p>
                    </div>
                @endif
            </div>

            {{-- Current Step --}}
            @if($prospect->current_step)
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-route text-white text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ขั้นตอนปัจจุบัน</h2>
                    </div>

                    <div class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/20 border-2 border-purple-300 dark:border-purple-700 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                            <div class="text-xl font-bold text-purple-900 dark:text-purple-100">
                                {{ $prospect->current_step }}
                            </div>
                        </div>
                        <p class="text-sm text-purple-700 dark:text-purple-300 font-medium ml-13">
                            ผู้มุ่งหวังอยู่ที่ขั้นตอนนี้ในการสมัคร
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Sponsor Information --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-user-tie text-white"></i>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">ผู้แนะนำ</h2>
                </div>

                @if($prospect->sponsorUser)
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 h-14 w-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg text-xl">
                                {{ substr($prospect->sponsorUser->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-base font-bold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $prospect->sponsorUser->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $prospect->sponsorUser->email }}
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.users.show', $prospect->sponsorUser->id) }}"
                               class="inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold transition-colors">
                                <i class="fas fa-external-link-alt"></i>
                                ดูโปรไฟล์ผู้แนะนำ
                            </a>
                        </div>
                    </div>
                @elseif($prospect->sponsorMember)
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 h-14 w-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg text-xl">
                                {{ substr($prospect->sponsorMember->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-base font-bold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $prospect->sponsorMember->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    สมาชิก #{{ $prospect->sponsorMember->id }}
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-xl flex items-center justify-center shadow-lg mb-3">
                            <i class="fas fa-user-slash text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">ไม่มีผู้แนะนำ</p>
                    </div>
                @endif
            </div>

            {{-- Registered User --}}
            @if($prospect->registeredUser)
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-user-check text-white"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">ผู้ใช้ที่สมัครแล้ว</h2>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 h-14 w-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg text-xl">
                                {{ substr($prospect->registeredUser->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-base font-bold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $prospect->registeredUser->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $prospect->registeredUser->email }}
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.users.show', $prospect->registeredUser->id) }}"
                               class="inline-flex items-center gap-2 text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 font-semibold transition-colors">
                                <i class="fas fa-external-link-alt"></i>
                                ดูโปรไฟล์ผู้ใช้
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Timestamps --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">ข้อมูลเวลา</h2>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">สร้างเมื่อ:</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ $prospect->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">อัพเดทล่าสุด:</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ $prospect->updated_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    @if($prospect->expires_at)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/20 rounded-xl border-2 border-orange-300 dark:border-orange-700">
                            <span class="text-xs font-bold text-orange-700 dark:text-orange-400 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                หมดอายุ:
                            </span>
                            <span class="text-sm font-bold text-orange-900 dark:text-orange-100">
                                {{ \Carbon\Carbon::parse($prospect->expires_at)->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
