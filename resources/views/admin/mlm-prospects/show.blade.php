@extends('layouts.admin')

@section('title', 'รายละเอียดผู้มุ่งหวัง')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                {{-- Profile Picture --}}
                <div class="flex-shrink-0 h-20 w-20">
                    @if($prospect->line_profile_picture)
                        <img class="h-20 w-20 rounded-full ring-4 ring-indigo-100 dark:ring-indigo-900"
                             src="{{ $prospect->line_profile_picture }}"
                             alt="{{ $prospect->line_display_name }}">
                    @else
                        <div class="h-20 w-20 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center ring-4 ring-indigo-100 dark:ring-indigo-900">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="ml-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $prospect->line_display_name ?? 'ไม่ระบุชื่อ' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        สร้างเมื่อ: {{ $prospect->created_at->format('d/m/Y H:i') }}
                    </p>
                    <div class="mt-2">
                        @php
                            $statusConfig = [
                                'pending' => ['color' => 'yellow', 'label' => 'รอดำเนินการ', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                'in_progress' => ['color' => 'indigo', 'label' => 'กำลังดำเนินการ', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                                'completed' => ['color' => 'green', 'label' => 'สำเร็จ', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                'expired' => ['color' => 'red', 'label' => 'หมดอายุ', 'icon' => 'M6 18L18 6M6 6l12 12'],
                            ];
                            $config = $statusConfig[$prospect->status] ?? ['color' => 'gray', 'label' => $prospect->status, 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                        @endphp
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30 text-{{ $config['color'] }}-800 dark:text-{{ $config['color'] }}-400">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                            </svg>
                            {{ $config['label'] }}
                        </span>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.mlm-prospects.index') }}"
               class="px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-700 dark:hover:bg-gray-600 transition">
                ← กลับ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- LINE Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                    </svg>
                    ข้อมูล LINE
                </h2>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">LINE User ID</span>
                        <code class="text-sm bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded font-mono text-gray-800 dark:text-gray-200">
                            {{ $prospect->line_user_id }}
                        </code>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Display Name</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $prospect->line_display_name ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Referral Token</span>
                        <code class="text-sm bg-indigo-100 dark:bg-indigo-900/30 px-3 py-1 rounded font-mono text-indigo-800 dark:text-indigo-400">
                            {{ $prospect->referral_token }}
                        </code>
                    </div>
                </div>
            </div>

            {{-- Signup Data --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    ข้อมูลที่เก็บจากการสมัคร
                </h2>

                @if($prospect->signup_data && is_array($prospect->signup_data) && count($prospect->signup_data) > 0)
                    <div class="space-y-3">
                        @foreach($prospect->signup_data as $key => $value)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                                </div>
                                <div class="text-base text-gray-900 dark:text-gray-100">
                                    @if(is_array($value))
                                        <pre class="text-xs bg-gray-200 dark:bg-gray-600 p-2 rounded overflow-auto">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        {{ $value }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูลที่เก็บ</p>
                    </div>
                @endif
            </div>

            {{-- Current Step --}}
            @if($prospect->current_step)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        ขั้นตอนปัจจุบัน
                    </h2>

                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-200 dark:border-purple-800 rounded-lg">
                        <div class="text-lg font-semibold text-purple-900 dark:text-purple-100">
                            {{ $prospect->current_step }}
                        </div>
                        <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">
                            ผู้มุ่งหวังอยู่ที่ขั้นตอนนี้ในการสมัคร
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Sponsor Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    ผู้แนะนำ
                </h2>

                @if($prospect->sponsorUser)
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($prospect->sponsorUser->name, 0, 1) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $prospect->sponsorUser->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $prospect->sponsorUser->email }}
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.users.show', $prospect->sponsorUser->id) }}"
                               class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                ดูโปรไฟล์ผู้แนะนำ
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @elseif($prospect->sponsorMember)
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-green-400 to-teal-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($prospect->sponsorMember->name, 0, 1) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $prospect->sponsorMember->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    สมาชิก #{{ $prospect->sponsorMember->id }}
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ไม่มีผู้แนะนำ</p>
                    </div>
                @endif
            </div>

            {{-- Registered User --}}
            @if($prospect->registeredUser)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ผู้ใช้ที่สมัครแล้ว
                    </h2>

                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($prospect->registeredUser->name, 0, 1) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $prospect->registeredUser->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $prospect->registeredUser->email }}
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.users.show', $prospect->registeredUser->id) }}"
                               class="inline-flex items-center text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">
                                ดูโปรไฟล์ผู้ใช้
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Timestamps --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ข้อมูลเวลา
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $prospect->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <span class="text-gray-600 dark:text-gray-400">อัพเดทล่าสุด:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $prospect->updated_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    @if($prospect->expires_at)
                        <div class="flex items-center justify-between p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded">
                            <span class="text-orange-600 dark:text-orange-400">หมดอายุ:</span>
                            <span class="font-medium text-orange-900 dark:text-orange-100">
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
