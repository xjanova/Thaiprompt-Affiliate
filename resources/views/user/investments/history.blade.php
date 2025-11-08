@extends('layouts.user')

@section('title', 'ประวัติการลงทุน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Investment History Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">📜 ประวัติการลงทุน</h1>
                    <p class="text-purple-100">ดูข้อมูลการลงทุนทั้งหมดของคุณ</p>
                </div>
                <a href="{{ route('user.investments.index') }}"
                   class="bg-white bg-opacity-20 hover:bg-opacity-30 px-6 py-3 rounded-xl transition font-semibold hidden md:block">
                    ← กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🔍 ตัวกรอง</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>🟢 กำลังใช้งาน</option>
                    <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>🔒 ล็อค</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>✅ เสร็จสิ้น</option>
                    <option value="withdrawn" {{ request('status') == 'withdrawn' ? 'selected' : '' }}>💸 ถอนแล้ว</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ ยกเลิก</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">แผนการลงทุน</label>
                <select name="plan_id" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <div class="w-full flex gap-2">
                    <button type="submit" class="flex-1 px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition font-semibold shadow-lg">
                        🔍 ค้นหา
                    </button>
                    <a href="{{ route('user.investments.history') }}" class="px-6 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition font-semibold">
                        ล้าง
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Investment History Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                📊 รายการลงทุน ({{ $positions->total() }} รายการ)
            </h3>
        </div>

        @if($positions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">แผน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวนลงทุน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ROI ที่ได้รับ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($positions as $position)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $position->investmentPlan->display_name ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $position->investmentPlan->roi_rate }}% ROI • {{ $position->investmentPlan->term_days }} วัน
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                    ฿{{ number_format($position->amount, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($position->earned_roi ?? 0, 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    คาดหวัง: ฿{{ number_format($position->expected_roi ?? 0, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $position->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $position->status === 'locked' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                    {{ $position->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    {{ $position->status === 'withdrawn' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                    {{ $position->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                    @if($position->status === 'active') 🟢 Active
                                    @elseif($position->status === 'locked') 🔒 Locked
                                    @elseif($position->status === 'completed') ✅ Completed
                                    @elseif($position->status === 'withdrawn') 💸 Withdrawn
                                    @elseif($position->status === 'cancelled') ❌ Cancelled
                                    @else {{ ucfirst($position->status) }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $position->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $position->created_at->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('user.investments.show', $position->id) }}"
                                   class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($positions->hasPages())
            <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4">
                {{ $positions->appends(request()->query())->links() }}
            </div>
            @endif
        @else
            <div class="px-6 py-12 text-center">
                <div class="text-gray-400">
                    <span class="text-4xl block mb-2">📭</span>
                    <p class="text-sm">ยังไม่มีข้อมูลการลงทุน</p>
                    <a href="{{ route('user.investments.plans') }}"
                       class="mt-4 inline-block px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition font-semibold">
                        เริ่มลงทุน
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Summary Cards -->
    @if($positions->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">✅</div>
            <p class="text-white text-opacity-80 text-sm mb-1">Active Positions</p>
            <p class="text-3xl font-bold">{{ $positions->where('status', 'active')->count() }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">🏁</div>
            <p class="text-white text-opacity-80 text-sm mb-1">Completed</p>
            <p class="text-3xl font-bold">{{ $positions->where('status', 'completed')->count() }}</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">💰</div>
            <p class="text-white text-opacity-80 text-sm mb-1">Total Earned</p>
            <p class="text-3xl font-bold">฿{{ number_format($positions->sum('earned_roi'), 2) }}</p>
        </div>
    </div>
    @endif

</div>
@endsection
