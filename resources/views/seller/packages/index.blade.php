@extends('layouts.app')

@section('title', 'เลือกแพ็คเกจ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                เลือกแพ็คเกจที่เหมาะกับธุรกิจของคุณ
            </h1>
            <p class="text-xl text-gray-600">
                เริ่มต้นฟรี หรือเลือกแพ็คเกจที่ใช่เพื่อปลดล็อกความสามารถทั้งหมด
            </p>
        </div>

        @if($store && $store->package)
            {{-- Current Package Info --}}
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border-l-4 border-indigo-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            แพ็คเกจปัจจุบัน: {{ $store->package->display_name }}
                        </h3>
                        <p class="text-gray-600">
                            @if($store->subscription_status === 'trial')
                                ทดลองใช้ฟรี - หมดอายุ {{ $store->trial_ends_at?->diffForHumans() }}
                            @elseif($store->subscription_status === 'active')
                                ใช้งานจนถึง {{ $store->subscription_expires_at?->format('d/m/Y') }}
                            @else
                                สถานะ: {{ $store->subscription_status }}
                            @endif
                        </p>
                    </div>
                    @if($store->subscription_status === 'active')
                        <form action="{{ route('seller.packages.cancel') }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกแพ็คเกจ?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                ยกเลิกแพ็คเกจ
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- Packages Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @foreach($packages as $package)
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden transform hover:scale-105 transition duration-300 {{ $package->is_featured ? 'ring-4 ring-purple-500' : '' }}">
                    {{-- Package Header --}}
                    <div class="bg-gradient-to-r {{ $package->is_featured ? 'from-purple-600 to-indigo-600' : 'from-indigo-500 to-purple-500' }} p-6 text-white relative">
                        @if($package->badge)
                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1 text-xs font-bold rounded-full" style="background-color: {{ $package->badge_color ?? '#10B981' }}">
                                    {{ $package->badge }}
                                </span>
                            </div>
                        @endif

                        <h3 class="text-2xl font-bold mb-2">{{ $package->display_name }}</h3>
                        <p class="text-white/90 text-sm mb-4">{{ $package->package_name }}</p>

                        <div class="mb-4">
                            @if($package->price == 0)
                                <div class="text-4xl font-bold">ฟรี</div>
                            @else
                                <div class="text-4xl font-bold">฿{{ number_format($package->price, 0) }}</div>
                                <div class="text-white/80 text-sm">/เดือน</div>
                            @endif
                        </div>

                        @if($package->yearly_price && $package->yearly_savings_percentage)
                            <div class="bg-white/20 rounded-lg p-2 text-sm">
                                <div class="font-semibold">฿{{ number_format($package->yearly_price, 0) }}/ปี</div>
                                <div class="text-xs">ประหยัด {{ $package->yearly_savings_percentage }}%</div>
                            </div>
                        @endif
                    </div>

                    {{-- Package Description --}}
                    @if($package->description)
                        <div class="px-6 py-4 bg-gray-50">
                            <p class="text-sm text-gray-600">{{ $package->description }}</p>
                        </div>
                    @endif

                    {{-- Features List --}}
                    <div class="px-6 py-4">
                        <ul class="space-y-3 text-sm">
                            {{-- Products --}}
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>{{ $package->max_products ? number_format($package->max_products) . ' สินค้า' : 'สินค้าไม่จำกัด' }}</span>
                            </li>

                            {{-- Images --}}
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>{{ $package->max_images_per_product }} รูป/สินค้า</span>
                            </li>

                            {{-- Storage --}}
                            @if($package->max_storage_mb)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>{{ number_format($package->max_storage_mb / 1024, 1) }} GB พื้นที่เก็บข้อมูล</span>
                                </li>
                            @endif

                            {{-- Orders --}}
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>{{ $package->max_monthly_orders ? number_format($package->max_monthly_orders) . ' ออเดอร์/เดือน' : 'ออเดอร์ไม่จำกัด' }}</span>
                            </li>

                            {{-- Commission --}}
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>ค่าคอมมิชชั่น {{ $package->commission_rate }}%</span>
                            </li>

                            {{-- Premium Features --}}
                            @if($package->allow_custom_domain)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>โดเมนส่วนตัว</span>
                                </li>
                            @endif

                            @if($package->allow_advanced_analytics)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>รายงานขั้นสูง</span>
                                </li>
                            @endif

                            @if($package->allow_marketing_tools)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>เครื่องมือการตลาด</span>
                                </li>
                            @endif

                            @if($package->allow_ai_bot)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>AI Chatbot</span>
                                </li>
                            @endif

                            @if($package->priority_support)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>ซัพพอร์ตพิเศษ</span>
                                </li>
                            @endif
                        </ul>

                        @if($package->trial_days > 0)
                            <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <p class="text-sm text-yellow-800 font-medium">
                                    🎁 ทดลองใช้ฟรี {{ $package->trial_days }} วัน
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Action Button --}}
                    <div class="px-6 pb-6">
                        @if($store && $store->package_id === $package->id)
                            <button disabled class="w-full py-3 bg-gray-300 text-gray-600 rounded-lg font-semibold cursor-not-allowed">
                                แพ็คเกจปัจจุบัน
                            </button>
                        @else
                            <form action="{{ route('seller.packages.subscribe', $package->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="subscription_type" value="monthly">
                                <button type="submit" class="w-full py-3 {{ $package->is_featured ? 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700' : 'bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600' }} text-white rounded-lg font-semibold transition shadow-lg hover:shadow-xl">
                                    @if($package->price == 0)
                                        เริ่มใช้ฟรี
                                    @else
                                        เลือกแพ็คเกจนี้
                                    @endif
                                </button>
                            </form>

                            @if($package->yearly_price)
                                <form action="{{ route('seller.packages.subscribe', $package->id) }}" method="POST" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="subscription_type" value="yearly">
                                    <button type="submit" class="w-full py-2 bg-white border-2 {{ $package->is_featured ? 'border-purple-600 text-purple-600 hover:bg-purple-50' : 'border-indigo-500 text-indigo-500 hover:bg-indigo-50' }} rounded-lg font-semibold transition">
                                        จ่ายรายปี (ประหยัด {{ $package->yearly_savings_percentage }}%)
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Additional Info --}}
        <div class="mt-12 text-center text-gray-600">
            <p class="mb-4">มีคำถาม? ติดต่อทีมงานของเราได้ที่ <a href="mailto:support@thaiprompt.online" class="text-indigo-600 hover:underline">support@thaiprompt.online</a></p>
            <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                กลับไปหน้าแดชบอร์ด
            </a>
        </div>
    </div>
</div>
@endsection
