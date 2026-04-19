@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'รายละเอียดกฎการคิดราคา')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $rule->name }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    @php
                        $typeColors = [
                            'fixed' => 'bg-gray-100 text-gray-800',
                            'distance' => 'bg-blue-100 text-blue-800',
                            'time' => 'bg-purple-100 text-purple-800',
                            'zone' => 'bg-green-100 text-green-800',
                            'peak' => 'bg-red-100 text-red-800',
                        ];
                        $typeLabels = [
                            'fixed' => 'ราคาคงที่',
                            'distance' => 'ตามระยะทาง',
                            'time' => 'ตามเวลา',
                            'zone' => 'ตามโซน',
                            'peak' => 'ช่วงพีค',
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm rounded-full {{ $typeColors[$rule->type ?? 'fixed'] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $typeLabels[$rule->type ?? 'fixed'] ?? $rule->type }}
                    </span>
                    @if($rule->is_active)
                        <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">เปิดใช้งาน</span>
                    @else
                        <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800">ปิดใช้งาน</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.service-pricing-rules.edit', $rule) }}" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-edit mr-2"></i>แก้ไข
                </a>
                <a href="{{ route('admin.service-pricing-rules.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ข้อมูลทั่วไป --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>ข้อมูลทั่วไป
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ID</dt>
                    <dd class="font-mono font-semibold">{{ $rule->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ชื่อกฎ</dt>
                    <dd class="font-semibold">{{ $rule->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ลำดับความสำคัญ</dt>
                    <dd class="font-semibold">{{ $rule->priority ?? 0 }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">บริการ</dt>
                    <dd class="font-semibold">{{ $rule->service->name ?? 'ใช้กับทุกบริการ' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">หมวดหมู่</dt>
                    <dd class="font-semibold">{{ $rule->category->name ?? 'ใช้กับทุกหมวด' }}</dd>
                </div>
                @if($rule->description)
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-400 mb-1">คำอธิบาย</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $rule->description }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- การคำนวณราคา --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-calculator mr-2 text-green-500"></i>การคำนวณราคา
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ราคาพื้นฐาน</dt>
                    <dd class="font-bold text-lg text-green-600">฿{{ number_format($rule->base_price ?? $rule->flat_fee ?? 0, 2) }}</dd>
                </div>
                @if($rule->price_per_km > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">ราคาต่อ km</dt>
                        <dd class="font-semibold">฿{{ number_format($rule->price_per_km, 2) }}</dd>
                    </div>
                @endif
                @if($rule->price_per_minute > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">ราคาต่อนาที</dt>
                        <dd class="font-semibold">฿{{ number_format($rule->price_per_minute, 2) }}</dd>
                    </div>
                @endif
                @if($rule->multiplier && $rule->multiplier != 1)
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">ตัวคูณ</dt>
                        <dd class="font-semibold">x{{ $rule->multiplier }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- ช่วงระยะทาง --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-route mr-2 text-purple-500"></i>ช่วงระยะทาง
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ระยะทางขั้นต่ำ</dt>
                    <dd class="font-semibold">{{ $rule->min_distance ?? $rule->min_distance_km ?? 0 }} km</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ระยะทางสูงสุด</dt>
                    <dd class="font-semibold">{{ ($rule->max_distance ?? $rule->max_distance_km) ? ($rule->max_distance ?? $rule->max_distance_km) . ' km' : 'ไม่จำกัด' }}</dd>
                </div>
            </dl>

            @if(method_exists($rule, 'getDistanceRangeText'))
                <div class="mt-4 p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                    <p class="text-sm text-purple-600 dark:text-purple-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        ช่วงระยะทาง: {{ $rule->getDistanceRangeText() }}
                    </p>
                    @if(method_exists($rule, 'getFormulaText'))
                        <p class="text-sm text-purple-600 dark:text-purple-400 mt-1">
                            สูตร: {{ $rule->getFormulaText() }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ข้อมูลเพิ่มเติม --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-clock mr-2 text-orange-500"></i>ข้อมูลเพิ่มเติม
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</dt>
                    <dd class="font-semibold">{{ $rule->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">อัพเดทล่าสุด</dt>
                    <dd class="font-semibold">{{ $rule->updated_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
            <i class="fas fa-cogs mr-2 text-gray-500"></i>การดำเนินการ
        </h2>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('admin.service-pricing-rules.toggle-active', $rule) }}" method="POST" class="inline">
                @csrf
                @if($rule->is_active)
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-toggle-off mr-2"></i>ปิดใช้งาน
                    </button>
                @else
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-toggle-on mr-2"></i>เปิดใช้งาน
                    </button>
                @endif
            </form>
            <form action="{{ route('admin.service-pricing-rules.destroy', $rule) }}" method="POST" class="inline" onsubmit="return confirm('ลบกฎนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                    <i class="fas fa-trash mr-2"></i>ลบกฎ
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
