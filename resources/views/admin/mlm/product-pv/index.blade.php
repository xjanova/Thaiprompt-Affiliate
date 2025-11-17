@extends('layouts.admin-v3')

@section('title', 'กำหนด PV สินค้า')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 dark:from-amber-800 dark:via-orange-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-gem text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">กำหนด PV สินค้า</h1>
                            <p class="text-amber-100 text-lg mt-1">กำหนดค่า Point Value และค่าคอมมิชชั่นสำหรับสินค้า</p>
                        </div>
                    </div>
                </div>

                {{-- Add Button --}}
                <div>
                    <a href="{{ route('admin.mlm.product-pv.create') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-plus group-hover:scale-110 transition-transform"></i>
                        เพิ่มการกำหนด PV
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/30 dark:to-cyan-900/30 border-l-4 border-blue-500 dark:border-blue-400 rounded-xl p-6 shadow-md">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-800/50 p-3 rounded-xl">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-2xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-lightbulb mr-1"></i>
                    เกี่ยวกับ PV (Point Value)
                </h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    <strong>PV</strong> คือค่าคะแนนที่กำหนดให้กับสินค้า ใช้ในการคำนวณคอมมิชชั่น MLM
                    แต่ละสินค้าสามารถกำหนด PV แยกตามแต่ละแผน MLM ได้
                    หากไม่กำหนด PV จะใช้ค่า <span class="font-semibold text-blue-700 dark:text-blue-300">Global Rate</span> จากการตั้งค่าแผน MLM
                </p>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-purple-600 dark:text-purple-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Plan Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-layer-group mr-1 text-purple-600 dark:text-purple-400"></i>
                    แผน MLM
                </label>
                <select name="plan_id"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search Input --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1 text-blue-600 dark:text-blue-400"></i>
                    ค้นหาสินค้า
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ชื่อสินค้า หรือ SKU"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
            </div>

            {{-- Search Button --}}
            <div class="flex items-end">
                <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600 text-white px-4 py-3 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Product PV Table --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Table Header --}}
                <thead class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-box mr-2"></i>สินค้า
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-layer-group mr-2"></i>แผน MLM
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-gem mr-2"></i>PV Value
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-percentage mr-2"></i>อัตราคอมมิชชั่น
                        </th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-eye mr-2"></i>แสดงในหน้าสินค้า
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-cog mr-2"></i>จัดการ
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($productPvs as $productPv)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        {{-- Product Info --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($productPv->product->images->count() > 0)
                                    <img src="{{ $productPv->product->images->first()->image_path }}"
                                         alt="{{ $productPv->product->name }}"
                                         class="w-14 h-14 rounded-xl object-cover shadow-lg flex-shrink-0">
                                @else
                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center shadow-lg flex-shrink-0">
                                        <i class="fas fa-image text-gray-400 dark:text-gray-500 text-2xl"></i>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $productPv->product->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        SKU: {{ $productPv->product->sku }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 font-semibold">
                                        ราคา: ฿{{ number_format($productPv->product->price, 2) }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Plan --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 shadow">
                                <i class="fas fa-layer-group mr-1"></i>
                                {{ $productPv->plan->display_name }}
                            </span>
                        </td>

                        {{-- PV Value --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                <i class="fas fa-gem text-xs"></i>
                                {{ number_format($productPv->pv_value, 2) }} PV
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ number_format(($productPv->pv_value / $productPv->product->price) * 100, 1) }}% ของราคา
                            </div>
                        </td>

                        {{-- Commission Rate --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($productPv->use_global_rate)
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 shadow">
                                    <i class="fas fa-globe mr-1"></i>
                                    Global Rate
                                </span>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">
                                    ฿{{ number_format($productPv->plan->commission_per_pv, 2) }}/PV
                                </div>
                            @else
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 shadow">
                                    <i class="fas fa-star mr-1"></i>
                                    Custom Rate
                                </span>
                                <div class="text-xs text-green-700 dark:text-green-400 mt-1 font-semibold">
                                    ฿{{ number_format($productPv->custom_commission_per_pv, 2) }}/PV
                                </div>
                            @endif
                        </td>

                        {{-- Display Options --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col items-center gap-2">
                                @if($productPv->show_pv_on_product_page)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 shadow">
                                        <i class="fas fa-check mr-1"></i>
                                        แสดง PV
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 shadow">
                                        <i class="fas fa-times mr-1"></i>
                                        ไม่แสดง PV
                                    </span>
                                @endif

                                @if($productPv->show_commission_preview)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 shadow">
                                        <i class="fas fa-eye mr-1"></i>
                                        แสดงค่าคอม
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.mlm.product-pv.edit', $productPv) }}"
                                   class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition-all duration-300 shadow hover:shadow-lg transform hover:scale-105"
                                   title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.mlm.product-pv.destroy', $productPv) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('ยืนยันการลบ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-xl transition-all duration-300 shadow hover:shadow-lg transform hover:scale-105"
                                            title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-4">
                                <div class="bg-gray-100 dark:bg-gray-700 p-8 rounded-full">
                                    <i class="fas fa-box-open text-6xl text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีการกำหนด PV สินค้า</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">เริ่มต้นด้วยการเพิ่มการกำหนด PV ให้กับสินค้า</p>
                                    <a href="{{ route('admin.mlm.product-pv.create') }}"
                                       class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-3 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl font-semibold">
                                        <i class="fas fa-plus"></i>
                                        เพิ่มการกำหนด PV
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($productPvs->hasPages())
        <div class="flex justify-center">
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-4">
                {{ $productPvs->links() }}
            </div>
        </div>
    @endif

    {{-- Quick Stats --}}
    @if($productPvs->count() > 0)
    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl shadow-lg p-6 border border-purple-100 dark:border-purple-800">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-xl">
                <i class="fas fa-chart-pie text-white"></i>
            </div>
            สถิติการกำหนด PV
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Total Products --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-5 shadow-lg border border-purple-200 dark:border-purple-700 transform hover:scale-105 transition-all">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
                    <i class="fas fa-box text-purple-600 dark:text-purple-400"></i>
                    จำนวนสินค้าที่กำหนด PV
                </p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($productPvs->total()) }}
                </p>
            </div>

            {{-- Average PV --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-5 shadow-lg border border-blue-200 dark:border-blue-700 transform hover:scale-105 transition-all">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
                    <i class="fas fa-gem text-blue-600 dark:text-blue-400"></i>
                    PV เฉลี่ย
                </p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($productPvs->avg('pv_value'), 2) }} PV
                </p>
            </div>

            {{-- Global Rate Count --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-5 shadow-lg border border-green-200 dark:border-green-700 transform hover:scale-105 transition-all">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
                    <i class="fas fa-globe text-green-600 dark:text-green-400"></i>
                    ใช้ Global Rate
                </p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($productPvs->where('use_global_rate', true)->count()) }}
                </p>
            </div>

            {{-- Custom Rate Count --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-5 shadow-lg border border-pink-200 dark:border-pink-700 transform hover:scale-105 transition-all">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
                    <i class="fas fa-star text-pink-600 dark:text-pink-400"></i>
                    ใช้ Custom Rate
                </p>
                <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">
                    {{ number_format($productPvs->where('use_global_rate', false)->count()) }}
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
