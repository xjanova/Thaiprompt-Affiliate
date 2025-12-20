@extends('layouts.seller')

@section('title', 'เริ่มต้นใช้งานร้านค้า')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-8 pb-24 lg:pb-8">
    {{-- Header --}}
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white mb-2">
            <i class="fas fa-store mr-3"></i>เริ่มต้นใช้งานร้านค้าของคุณ
        </h1>
        <p class="text-gray-200">ทำตามขั้นตอนง่ายๆ เพื่อเปิดร้านค้าและเริ่มขายสินค้า</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-500/20 backdrop-blur-sm border border-green-400/30 text-white rounded-xl p-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/20 backdrop-blur-sm border border-red-400/30 text-white rounded-xl p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-500/20 backdrop-blur-sm border border-yellow-400/30 text-white rounded-xl p-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-500/20 backdrop-blur-sm border border-blue-400/30 text-white rounded-xl p-4">
            <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
        </div>
    @endif

    {{-- Stepper Progress --}}
    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20">
        <div class="flex items-center justify-between">
            {{-- Step 1: KYC --}}
            <div class="flex-1 flex flex-col items-center">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg transition-all duration-300
                    {{ $currentStep >= 1 ? ($currentStep > 1 ? 'bg-green-500 text-white' : 'bg-purple-600 text-white ring-4 ring-purple-300') : 'bg-gray-600 text-gray-300' }}">
                    @if($currentStep > 1)
                        <i class="fas fa-check"></i>
                    @else
                        1
                    @endif
                </div>
                <span class="mt-2 text-sm font-medium {{ $currentStep >= 1 ? 'text-white' : 'text-gray-400' }}">ยืนยันตัวตน</span>
            </div>

            <div class="flex-1 h-1 rounded-full mx-2 {{ $currentStep > 1 ? 'bg-green-500' : 'bg-gray-600' }}"></div>

            {{-- Step 2: เลือก Package --}}
            <div class="flex-1 flex flex-col items-center">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg transition-all duration-300
                    {{ $currentStep >= 2 ? ($currentStep > 2 ? 'bg-green-500 text-white' : 'bg-purple-600 text-white ring-4 ring-purple-300') : 'bg-gray-600 text-gray-300' }}">
                    @if($currentStep > 2)
                        <i class="fas fa-check"></i>
                    @else
                        2
                    @endif
                </div>
                <span class="mt-2 text-sm font-medium {{ $currentStep >= 2 ? 'text-white' : 'text-gray-400' }}">ตั้งค่าร้าน</span>
            </div>

            <div class="flex-1 h-1 rounded-full mx-2 {{ $currentStep > 2 ? 'bg-green-500' : 'bg-gray-600' }}"></div>

            {{-- Step 3: เสร็จสิ้น --}}
            <div class="flex-1 flex flex-col items-center">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg transition-all duration-300
                    {{ $currentStep >= 3 ? 'bg-green-500 text-white' : 'bg-gray-600 text-gray-300' }}">
                    @if($currentStep >= 3)
                        <i class="fas fa-check"></i>
                    @else
                        3
                    @endif
                </div>
                <span class="mt-2 text-sm font-medium {{ $currentStep >= 3 ? 'text-white' : 'text-gray-400' }}">พร้อมใช้งาน</span>
            </div>
        </div>
    </div>

    {{-- Step Content --}}
    @if($currentStep == 1)
        {{-- Step 1: KYC Verification --}}
        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20">
            <div class="text-center mb-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-purple-500/20 rounded-full flex items-center justify-center ring-4 ring-purple-400/30">
                    <i class="fas fa-id-card text-4xl text-purple-300"></i>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">ยืนยันตัวตน (KYC)</h2>
                <p class="text-gray-300">เพื่อความปลอดภัยและความน่าเชื่อถือ กรุณายืนยันตัวตนก่อนเปิดร้านค้า</p>
            </div>

            @if($kycStatus['status'] === 'pending')
                {{-- รอการตรวจสอบ --}}
                <div class="text-center py-6">
                    <div class="inline-flex items-center px-6 py-3 bg-yellow-500/20 border border-yellow-400/30 text-yellow-200 rounded-full font-bold mb-4">
                        <i class="fas fa-hourglass-half mr-2 animate-pulse"></i>
                        กำลังรอตรวจสอบเอกสาร
                    </div>
                    <p class="text-gray-300 mb-6">ทีมงานกำลังตรวจสอบเอกสารของคุณ โปรดรอ 1-3 วันทำการ</p>

                    @if($kycStatus['latest_submission'])
                        <div class="bg-white/5 rounded-xl p-4 max-w-md mx-auto mb-4">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-400">วันที่ส่ง:</span>
                                <span class="text-white font-medium">
                                    {{ $kycStatus['latest_submission']->submitted_at ? $kycStatus['latest_submission']->submitted_at->format('d/m/Y H:i') : '-' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('user.kyc.index') }}"
                       class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-xl transition">
                        <i class="fas fa-eye mr-2"></i>ดูสถานะ KYC
                    </a>
                </div>

            @elseif($kycStatus['status'] === 'rejected')
                {{-- ถูกปฏิเสธ --}}
                <div class="text-center py-6">
                    <div class="inline-flex items-center px-6 py-3 bg-red-500/20 border border-red-400/30 text-red-200 rounded-full font-bold mb-4">
                        <i class="fas fa-times-circle mr-2"></i>
                        ไม่ผ่านการตรวจสอบ
                    </div>

                    @if($kycStatus['latest_submission'] && $kycStatus['latest_submission']->rejection_reason)
                        <div class="bg-red-500/10 border border-red-400/30 rounded-xl p-4 max-w-md mx-auto mb-6">
                            <p class="text-sm text-red-200">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <strong>เหตุผล:</strong> {{ $kycStatus['latest_submission']->rejection_reason }}
                            </p>
                        </div>
                    @endif

                    <a href="{{ route('user.kyc.create') }}"
                       class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-bold text-lg transition shadow-lg">
                        <i class="fas fa-redo mr-3"></i>ส่งเอกสารใหม่
                    </a>
                </div>

            @else
                {{-- ยังไม่ได้ส่ง KYC --}}
                <div class="text-center py-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto mb-8">
                        <div class="bg-white/5 rounded-xl p-4">
                            <i class="fas fa-shield-alt text-2xl text-purple-400 mb-2"></i>
                            <p class="text-sm text-gray-300">ปลอดภัย 100%</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <i class="fas fa-clock text-2xl text-purple-400 mb-2"></i>
                            <p class="text-sm text-gray-300">ตรวจสอบภายใน 1-3 วัน</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <i class="fas fa-lock text-2xl text-purple-400 mb-2"></i>
                            <p class="text-sm text-gray-300">ข้อมูลเป็นความลับ</p>
                        </div>
                    </div>

                    <a href="{{ route('user.kyc.create') }}"
                       class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-lg transition shadow-lg">
                        <i class="fas fa-upload mr-3"></i>เริ่มยืนยันตัวตน
                    </a>
                </div>
            @endif
        </div>

    @elseif($currentStep == 2)
        {{-- Step 2: ตั้งค่าร้านและเลือก Package --}}
        <div x-data="{
            selectedPackage: null,
            subscriptionType: 'monthly',
            storeName: '{{ $store ? $store->store_name : '' }}',
            showModal: false
        }">
            {{-- ถ้ามี store อยู่แล้ว --}}
            @if($store)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 mb-6">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-purple-500/20 flex items-center justify-center mr-4">
                            @if($store->store_logo)
                                <img src="{{ asset($store->store_logo) }}" alt="{{ $store->store_name }}" class="w-16 h-16 rounded-full object-cover">
                            @else
                                <i class="fas fa-store text-2xl text-purple-300"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $store->store_name }}</h3>
                            <p class="text-gray-400">ร้านค้าของคุณพร้อมแล้ว กรุณาเลือกแพ็คเกจ</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Store Name Input (ถ้ายังไม่มี store) --}}
            @if(!$store)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 mb-6">
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-store mr-2"></i>ชื่อร้านค้า
                    </label>
                    <input type="text"
                           x-model="storeName"
                           placeholder="กรอกชื่อร้านค้าของคุณ"
                           class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
            @endif

            {{-- Package Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($packages as $package)
                    <div class="relative bg-white/10 backdrop-blur-md rounded-2xl border-2 transition-all duration-300 overflow-hidden cursor-pointer group
                                {{ $package->is_featured ? 'border-purple-400' : 'border-white/20' }}"
                         :class="selectedPackage == {{ $package->id }} ? 'ring-4 ring-purple-500 border-purple-500 scale-105' : 'hover:border-purple-400/50'"
                         @click="selectedPackage = {{ $package->id }}">

                        {{-- Badge --}}
                        @if($package->badge)
                            <div class="absolute top-0 right-0 px-3 py-1 text-xs font-bold text-white rounded-bl-xl"
                                 style="background-color: {{ $package->badge_color ?? '#9333EA' }}">
                                {{ $package->badge }}
                            </div>
                        @endif

                        <div class="p-6">
                            {{-- Package Name --}}
                            <h3 class="text-xl font-bold text-white mb-1">{{ $package->display_name }}</h3>
                            <p class="text-gray-400 text-sm mb-4">{{ $package->description }}</p>

                            {{-- Price --}}
                            <div class="mb-4">
                                @if($package->price == 0)
                                    <div class="text-3xl font-bold text-green-400">ฟรี</div>
                                @else
                                    <div x-show="subscriptionType === 'monthly'">
                                        <span class="text-3xl font-bold text-white">฿{{ number_format($package->price) }}</span>
                                        <span class="text-gray-400">/เดือน</span>
                                    </div>
                                    <div x-show="subscriptionType === 'yearly'" x-cloak>
                                        <span class="text-3xl font-bold text-white">฿{{ number_format($package->yearly_price ?? $package->price * 10) }}</span>
                                        <span class="text-gray-400">/ปี</span>
                                        @if($package->yearly_price && $package->yearly_price < $package->price * 12)
                                            <span class="ml-2 px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">
                                                ประหยัด {{ round((1 - $package->yearly_price / ($package->price * 12)) * 100) }}%
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Trial Info --}}
                            @if($package->trial_days > 0)
                                <div class="mb-4 px-3 py-2 bg-blue-500/20 border border-blue-400/30 rounded-lg">
                                    <i class="fas fa-gift text-blue-400 mr-2"></i>
                                    <span class="text-blue-200 text-sm">ทดลองใช้ฟรี {{ $package->trial_days }} วัน</span>
                                </div>
                            @endif

                            {{-- Features --}}
                            <ul class="space-y-2 text-sm mb-4">
                                @foreach($package->features ?? [] as $feature)
                                    <li class="flex items-start text-gray-300">
                                        <i class="fas fa-check text-green-400 mr-2 mt-1 flex-shrink-0"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Commission Rate --}}
                            <div class="pt-4 border-t border-white/10 text-sm text-gray-400">
                                <i class="fas fa-percentage mr-1"></i>
                                ค่าคอมมิชชั่น: <span class="text-white font-medium">{{ $package->commission_rate }}%</span>
                            </div>
                        </div>

                        {{-- Select Indicator --}}
                        <div class="absolute bottom-4 right-4 w-6 h-6 rounded-full border-2 flex items-center justify-center"
                             :class="selectedPackage == {{ $package->id }} ? 'bg-purple-500 border-purple-500' : 'border-gray-500'">
                            <i class="fas fa-check text-white text-xs" x-show="selectedPackage == {{ $package->id }}"></i>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Subscription Type Toggle (สำหรับ paid packages) --}}
            <div class="mt-6 flex justify-center" x-show="selectedPackage && selectedPackage != {{ $packages->where('price', 0)->first()?->id ?? 0 }}">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-1 inline-flex">
                    <button type="button"
                            class="px-6 py-2 rounded-lg font-medium transition"
                            :class="subscriptionType === 'monthly' ? 'bg-purple-600 text-white' : 'text-gray-400 hover:text-white'"
                            @click="subscriptionType = 'monthly'">
                        รายเดือน
                    </button>
                    <button type="button"
                            class="px-6 py-2 rounded-lg font-medium transition"
                            :class="subscriptionType === 'yearly' ? 'bg-purple-600 text-white' : 'text-gray-400 hover:text-white'"
                            @click="subscriptionType = 'yearly'">
                        รายปี (ประหยัด)
                    </button>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <form action="{{ route('seller.onboarding.create-store') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="store_name" :value="storeName">
                    <input type="hidden" name="package_id" :value="selectedPackage">
                    <input type="hidden" name="subscription_type" :value="subscriptionType">

                    <button type="submit"
                            class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-lg transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!selectedPackage || (!storeName && !{{ $store ? 'true' : 'false' }})">
                        <i class="fas fa-rocket mr-2"></i>
                        <span x-text="selectedPackage == {{ $packages->where('price', 0)->first()?->id ?? 0 }} ? 'เริ่มใช้งานฟรี' : 'ดำเนินการต่อ'">เริ่มใช้งาน</span>
                    </button>
                </form>

                <form action="{{ route('seller.onboarding.skip-package') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-8 py-4 bg-gray-600 hover:bg-gray-500 text-white rounded-xl font-medium transition">
                        <i class="fas fa-forward mr-2"></i>ใช้แพ็คเกจฟรีก่อน
                    </button>
                </form>
            </div>
        </div>

    @else
        {{-- Step 3: เสร็จสิ้น - Redirect ไป Dashboard --}}
        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-green-500/20 rounded-full flex items-center justify-center ring-4 ring-green-400/30">
                <i class="fas fa-check text-5xl text-green-400"></i>
            </div>
            <h2 class="text-3xl font-bold text-white mb-4">พร้อมใช้งานแล้ว!</h2>
            <p class="text-gray-300 mb-8">ร้านค้าของคุณพร้อมรับลูกค้าแล้ว เริ่มเพิ่มสินค้าและจัดการร้านได้เลย</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('seller.dashboard') }}"
                   class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-lg transition shadow-lg">
                    <i class="fas fa-tachometer-alt mr-2"></i>ไปยังแดชบอร์ด
                </a>
                <a href="{{ route('seller.products.create') }}"
                   class="px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-lg transition shadow-lg">
                    <i class="fas fa-plus-circle mr-2"></i>เพิ่มสินค้าแรก
                </a>
            </div>
        </div>
    @endif

    {{-- Help Section --}}
    <div class="bg-blue-500/10 backdrop-blur-md border border-blue-400/30 rounded-2xl p-6">
        <h3 class="text-lg font-bold text-white mb-4">
            <i class="fas fa-question-circle mr-2 text-blue-400"></i>ต้องการความช่วยเหลือ?
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <a href="{{ route('seller.user-guide.index') }}" class="flex items-center text-blue-200 hover:text-white transition">
                <i class="fas fa-book mr-2"></i>คู่มือการใช้งาน
            </a>
            <a href="#" class="flex items-center text-blue-200 hover:text-white transition">
                <i class="fas fa-comments mr-2"></i>ติดต่อทีมสนับสนุน
            </a>
            <a href="#" class="flex items-center text-blue-200 hover:text-white transition">
                <i class="fas fa-video mr-2"></i>วิดีโอสอนใช้งาน
            </a>
        </div>
    </div>
</div>
@endsection
