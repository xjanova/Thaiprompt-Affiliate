@extends('layouts.admin-v3')

@section('title', 'Payment Gateways Management')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-4xl font-bold text-white mb-2">Payment Gateways</h1>
                    <p class="text-blue-100 text-lg">จัดการช่องทางการชำระเงินทั้งหมดของระบบ</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20">
                    <div class="text-white/70 text-sm font-semibold mb-1">ทั้งหมด</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['total'] }}</div>
                </div>
                <div class="bg-green-500/20 backdrop-blur-md rounded-xl p-4 border border-green-300/30">
                    <div class="text-green-100 text-sm font-semibold mb-1">กำลังใช้งาน</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['active'] }}</div>
                </div>
                <div class="bg-blue-500/20 backdrop-blur-md rounded-xl p-4 border border-blue-300/30">
                    <div class="text-blue-100 text-sm font-semibold mb-1">ตั้งค่าแล้ว</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['configured'] }}</div>
                </div>
                <div class="bg-purple-500/20 backdrop-blur-md rounded-xl p-4 border border-purple-300/30">
                    <div class="text-purple-100 text-sm font-semibold mb-1">เร็วๆ นี้</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['coming_soon'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-900">สำเร็จ!</p>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-900">ข้อผิดพลาด!</p>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Gateways by Category -->
    @foreach($gateways as $category => $categoryGateways)
        <div class="mb-8">
            <!-- Category Header -->
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-{{ $category === 'bank' ? 'university' : ($category === 'e-wallet' ? 'wallet' : ($category === 'crypto' ? 'bitcoin' : ($category === 'international' ? 'globe' : 'credit-card'))) }} text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $categoryGateways->first()->category_label }}</h2>
                    <p class="text-sm text-gray-600">{{ $categoryGateways->count() }} ช่องทาง</p>
                </div>
            </div>

            <!-- Gateways Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($categoryGateways as $gateway)
                    <div class="group bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 {{ $gateway->is_coming_soon ? 'opacity-75' : '' }}">
                        <!-- Gateway Header -->
                        <div class="relative p-6 bg-gradient-to-br from-{{ $gateway->color === '#3B82F6' ? 'blue' : ($gateway->color === '#10B981' ? 'green' : ($gateway->color === '#F59E0B' ? 'yellow' : 'purple')) }}-500 to-{{ $gateway->color === '#3B82F6' ? 'indigo' : ($gateway->color === '#10B981' ? 'emerald' : ($gateway->color === '#F59E0B' ? 'orange' : 'pink')) }}-600">
                            @if($gateway->is_coming_soon)
                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 bg-white/90 backdrop-blur-sm text-gray-800 rounded-full text-xs font-bold shadow-lg">
                                        Coming Soon
                                    </span>
                                </div>
                            @else
                                <div class="absolute top-3 right-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer gateway-toggle"
                                               data-id="{{ $gateway->id }}"
                                               {{ $gateway->is_active ? 'checked' : '' }}
                                               {{ !$gateway->isConfigured() ? 'disabled' : '' }}>
                                        <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-white/50 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-white/30 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"></div>
                                    </label>
                                </div>
                            @endif

                            <div class="flex items-center gap-4 mb-3">
                                @if($gateway->logo_url)
                                    <img src="{{ $gateway->logo_url }}" alt="{{ $gateway->name }}" class="w-16 h-16 object-contain bg-white rounded-xl p-2">
                                @else
                                    <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-4xl">
                                        {{ $gateway->icon ?? '💳' }}
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-white">{{ $gateway->name }}</h3>
                                    <p class="text-white/80 text-sm">{{ $gateway->code }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Gateway Body -->
                        <div class="p-6">
                            <!-- Status Badge -->
                            <div class="flex items-center gap-2 mb-4">
                                @php
                                    $statusColors = [
                                        'green' => 'bg-green-100 text-green-800 border-green-200',
                                        'yellow' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'red' => 'bg-red-100 text-red-800 border-red-200',
                                        'gray' => 'bg-gray-100 text-gray-800 border-gray-200',
                                    ];
                                    $statusIcons = [
                                        'green' => 'check-circle',
                                        'yellow' => 'exclamation-triangle',
                                        'red' => 'times-circle',
                                        'gray' => 'minus-circle',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusColors[$gateway->status_badge] ?? $statusColors['gray'] }}">
                                    <i class="fas fa-{{ $statusIcons[$gateway->status_badge] ?? $statusIcons['gray'] }} mr-1"></i>
                                    {{ $gateway->status_text }}
                                </span>

                                @if($gateway->test_mode)
                                    <span class="inline-flex items-center px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-bold border border-orange-200">
                                        <i class="fas fa-flask mr-1"></i>Test Mode
                                    </span>
                                @endif
                            </div>

                            <!-- Description -->
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                {{ $gateway->description ?? 'ไม่มีคำอธิบาย' }}
                            </p>

                            <!-- Features -->
                            <div class="flex gap-2 mb-4">
                                @if($gateway->supports_deposit)
                                    <span class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold">
                                        <i class="fas fa-arrow-down mr-1"></i>ฝาก
                                    </span>
                                @endif
                                @if($gateway->supports_withdrawal)
                                    <span class="inline-flex items-center px-2 py-1 bg-purple-50 text-purple-700 rounded-lg text-xs font-semibold">
                                        <i class="fas fa-arrow-up mr-1"></i>ถอน
                                    </span>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 mt-4">
                                @if(!$gateway->is_coming_soon)
                                    <a href="{{ route('admin.payment-gateways.edit', $gateway) }}"
                                       class="flex-1 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl text-center font-semibold">
                                        <i class="fas fa-cog mr-2"></i>ตั้งค่า
                                    </a>

                                    @if($gateway->isConfigured())
                                        <button type="button"
                                                onclick="testGateway({{ $gateway->id }})"
                                                class="px-4 py-2 bg-white border-2 border-indigo-600 text-indigo-600 rounded-xl hover:bg-indigo-50 transition-all duration-300 font-semibold">
                                            <i class="fas fa-vial"></i>
                                        </button>
                                    @endif
                                @else
                                    <div class="flex-1 px-4 py-2 bg-gray-100 text-gray-500 rounded-xl text-center font-semibold cursor-not-allowed">
                                        <i class="fas fa-clock mr-2"></i>เร็วๆ นี้
                                    </div>
                                @endif
                            </div>

                            <!-- Last Tested -->
                            @if($gateway->last_tested_at)
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        ทดสอบล่าสุด: {{ $gateway->last_tested_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if($gateways->isEmpty())
        <!-- Empty State -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                <i class="fas fa-credit-card text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">ยังไม่มี Payment Gateway</h3>
            <p class="text-gray-600 mb-6">กรุณารัน Seeder เพื่อเพิ่ม Payment Gateways เริ่มต้น</p>
            <code class="inline-block px-4 py-2 bg-gray-100 rounded-lg text-sm">
                php artisan db:seed --class=PaymentGatewaySeeder
            </code>
        </div>
    @endif
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
// Toggle gateway active status
document.querySelectorAll('.gateway-toggle').forEach(toggle => {
    toggle.addEventListener('change', async function() {
        const gatewayId = this.dataset.id;
        const isActive = this.checked;

        try {
            const response = await fetch(`/admin/payment-gateways/${gatewayId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Show success notification
                showNotification('success', data.message || (isActive ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว'));
            } else {
                // Revert toggle
                this.checked = !isActive;
                showNotification('error', data.error || 'เกิดข้อผิดพลาด');
            }
        } catch (error) {
            // Revert toggle
            this.checked = !isActive;
            showNotification('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
        }
    });
});

// Test gateway connection
async function testGateway(gatewayId) {
    showNotification('info', 'กำลังทดสอบการเชื่อมต่อ...');

    try {
        const response = await fetch(`/admin/payment-gateways/${gatewayId}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            showNotification('success', data.message || 'ทดสอบการเชื่อมต่อสำเร็จ');
        } else {
            showNotification('error', data.message || 'ทดสอบการเชื่อมต่อล้มเหลว');
        }
    } catch (error) {
        showNotification('error', 'เกิดข้อผิดพลาดในการทดสอบ');
    }
}

// Show notification
function showNotification(type, message) {
    const colors = {
        success: 'from-green-50 to-emerald-50 border-green-200',
        error: 'from-red-50 to-pink-50 border-red-200',
        info: 'from-blue-50 to-cyan-50 border-blue-200',
    };

    const icons = {
        success: 'check',
        error: 'exclamation-circle',
        info: 'info-circle',
    };

    const iconColors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
    };

    const textColors = {
        success: 'text-green-900',
        error: 'text-red-900',
        info: 'text-blue-900',
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-xl bg-gradient-to-r ${colors[type]} border animate-fade-in shadow-lg z-50 max-w-md`;
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full ${iconColors[type]} flex items-center justify-center mr-3">
                <i class="fas fa-${icons[type]} text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm ${textColors[type]}">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
@endsection
