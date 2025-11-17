@extends('layouts.admin-v3')

@section('title', 'จัดการ Ticket')

@section('content')
<div class="space-y-6">
    <!-- Header with Stats -->
    <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="mb-6">
            <h2 class="text-3xl font-bold mb-2">ระบบ Ticket Support</h2>
            <p class="text-indigo-100 text-sm">จัดการและตอบกลับ Ticket จากผู้ใช้งานทั้งหมด</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <!-- Total -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100 text-sm">ทั้งหมด</span>
                    <i class="fa-solid fa-ticket text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['total']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">Tickets</p>
            </div>

            <!-- Open -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100 text-sm">เปิดอยู่</span>
                    <i class="fa-solid fa-folder-open text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['open']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">Tickets</p>
            </div>

            <!-- In Progress -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100 text-sm">กำลังดำเนินการ</span>
                    <i class="fa-solid fa-spinner text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['in_progress']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">Tickets</p>
            </div>

            <!-- Critical -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100 text-sm">วิกฤต</span>
                    <i class="fa-solid fa-exclamation-triangle text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['critical']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">Tickets</p>
            </div>

            <!-- My Tickets -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100 text-sm">ของฉัน</span>
                    <i class="fa-solid fa-user text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['my_tickets']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">Tickets</p>
            </div>
        </div>
    </div>

    <!-- Management Menu -->
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white mb-4 flex items-center">
            <i class="fa-solid fa-cog mr-2"></i>
            จัดการระบบ Ticket
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
            <!-- Analytics -->
            <a href="{{ route('admin.tickets.analytics') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-blue-200 dark:border-blue-800">
                <i class="fa-solid fa-chart-line text-3xl text-blue-600 dark:text-blue-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">Analytics</span>
            </a>

            <!-- Ratings -->
            <a href="{{ route('admin.tickets.ratings') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 hover:from-yellow-100 hover:to-orange-100 dark:hover:from-yellow-900/30 dark:hover:to-orange-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-yellow-200 dark:border-yellow-800">
                <i class="fa-solid fa-star text-3xl text-yellow-600 dark:text-yellow-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">ความพึงพอใจ</span>
            </a>

            <!-- Categories -->
            <a href="{{ route('admin.tickets.categories.index') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 hover:from-purple-100 hover:to-pink-100 dark:hover:from-purple-900/30 dark:hover:to-pink-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-purple-200 dark:border-purple-800">
                <i class="fa-solid fa-folder text-3xl text-purple-600 dark:text-purple-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">หมวดหมู่</span>
            </a>

            <!-- Canned Responses -->
            <a href="{{ route('admin.tickets.canned-responses.index') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 hover:from-green-100 hover:to-emerald-100 dark:hover:from-green-900/30 dark:hover:to-emerald-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-green-200 dark:border-green-800">
                <i class="fa-solid fa-comment-dots text-3xl text-green-600 dark:text-green-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">ข้อความสำเร็จรูป</span>
            </a>

            <!-- SLA Policies -->
            <a href="{{ route('admin.tickets.sla-policies.index') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 hover:from-red-100 hover:to-rose-100 dark:hover:from-red-900/30 dark:hover:to-rose-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-red-200 dark:border-red-800">
                <i class="fa-solid fa-clock text-3xl text-red-600 dark:text-red-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">SLA Policies</span>
            </a>

            <!-- Assignment Rules -->
            <a href="{{ route('admin.tickets.assignment-rules.index') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-cyan-50 to-teal-50 dark:from-cyan-900/20 dark:to-teal-900/20 hover:from-cyan-100 hover:to-teal-100 dark:hover:from-cyan-900/30 dark:hover:to-teal-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-cyan-200 dark:border-cyan-800">
                <i class="fa-solid fa-user-check text-3xl text-cyan-600 dark:text-cyan-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">กฎการมอบหมาย</span>
            </a>

            <!-- Knowledge Base -->
            <a href="{{ route('admin.tickets.kb-articles.index') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 hover:from-amber-100 hover:to-yellow-100 dark:hover:from-amber-900/30 dark:hover:to-yellow-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-amber-200 dark:border-amber-800">
                <i class="fa-solid fa-book text-3xl text-amber-600 dark:text-amber-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">ฐานความรู้</span>
            </a>

            <!-- Settings -->
            <a href="{{ route('admin.tickets.settings') }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-900/20 dark:to-gray-900/20 hover:from-slate-100 hover:to-gray-100 dark:hover:from-slate-900/30 dark:hover:to-gray-900/30 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg border border-slate-200 dark:border-slate-800">
                <i class="fa-solid fa-cog text-3xl text-slate-600 dark:text-slate-400 mb-2"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 text-center">ตั้งค่า</span>
            </a>
        </div>
    </div>

    <!-- Quick Filters -->
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('admin.tickets.index', ['status' => 'open']) }}"
           class="inline-flex items-center px-6 py-3 {{ $filters['status'] == 'open' ? 'bg-gradient-to-r from-blue-600 to-indigo-700' : 'bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700' }} text-white font-semibold rounded-xl shadow-lg transition-all transform hover:scale-105">
            <i class="fa-solid fa-folder-open mr-2"></i>
            เปิดอยู่ ({{ $stats['open'] }})
        </a>
        <a href="{{ route('admin.tickets.index', ['status' => 'in_progress']) }}"
           class="inline-flex items-center px-6 py-3 {{ $filters['status'] == 'in_progress' ? 'bg-gradient-to-r from-yellow-600 to-orange-700' : 'bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700' }} text-white font-semibold rounded-xl shadow-lg transition-all transform hover:scale-105">
            <i class="fa-solid fa-spinner mr-2"></i>
            กำลังดำเนินการ ({{ $stats['in_progress'] }})
        </a>
        <a href="{{ route('admin.tickets.index', ['priority' => 'critical']) }}"
           class="inline-flex items-center px-6 py-3 {{ $filters['priority'] == 'critical' ? 'bg-gradient-to-r from-red-600 to-pink-700' : 'bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700' }} text-white font-semibold rounded-xl shadow-lg transition-all transform hover:scale-105">
            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
            วิกฤต ({{ $stats['critical'] }})
        </a>
        <a href="{{ route('admin.tickets.index', ['unassigned' => 1]) }}"
           class="inline-flex items-center px-6 py-3 {{ $filters['unassigned'] ? 'bg-gradient-to-r from-purple-600 to-pink-700' : 'bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700' }} text-white font-semibold rounded-xl shadow-lg transition-all transform hover:scale-105">
            <i class="fa-solid fa-user-slash mr-2"></i>
            ยังไม่มอบหมาย ({{ $stats['unassigned'] }})
        </a>
    </div>

    <!-- Filters -->
    <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white mb-4 flex items-center">
            <i class="fa-solid fa-filter mr-2"></i>
            กรองข้อมูล
        </h3>
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="open" {{ $filters['status'] == 'open' ? 'selected' : '' }}>เปิด</option>
                    <option value="in_progress" {{ $filters['status'] == 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="waiting_customer" {{ $filters['status'] == 'waiting_customer' ? 'selected' : '' }}>รอลูกค้า</option>
                    <option value="resolved" {{ $filters['status'] == 'resolved' ? 'selected' : '' }}>แก้ไขแล้ว</option>
                    <option value="closed" {{ $filters['status'] == 'closed' ? 'selected' : '' }}>ปิด</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ความสำคัญ</label>
                <select name="priority" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="low" {{ $filters['priority'] == 'low' ? 'selected' : '' }}>ต่ำ</option>
                    <option value="medium" {{ $filters['priority'] == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                    <option value="high" {{ $filters['priority'] == 'high' ? 'selected' : '' }}>สูง</option>
                    <option value="critical" {{ $filters['priority'] == 'critical' ? 'selected' : '' }}>วิกฤต</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">หมวดหมู่</label>
                <select name="category_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ผู้ดูแล</label>
                <select name="assigned_to" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($staffUsers as $staff)
                        <option value="{{ $staff->id }}" {{ $filters['assigned_to'] == $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="หมายเลข, หัวข้อ..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="md:col-span-5 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition-all">
                    <i class="fa-solid fa-search mr-2"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.tickets.index') }}" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-xl shadow-lg transition-all">
                    <i class="fa-solid fa-refresh mr-2"></i>
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">หมายเลข</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">หัวข้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">หมวดหมู่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">ความสำคัญ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">ผู้ดูแล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">สร้างเมื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $ticket->ticket_number }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($ticket->subject, 50) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ Str::limit($ticket->description, 60) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $ticket->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $ticket->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($ticket->category)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $ticket->category->color }}20; color: {{ $ticket->category->color }}">
                                        @if($ticket->category->icon)
                                            <i class="{{ $ticket->category->icon }} mr-1"></i>
                                        @endif
                                        {{ $ticket->category->name }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                    @if($ticket->priority == 'critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($ticket->priority == 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                    @elseif($ticket->priority == 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ $ticket->priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    @if($ticket->status == 'open') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($ticket->status == 'in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($ticket->status == 'waiting_customer') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @elseif($ticket->status == 'resolved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @else bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($ticket->assignedTo)
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $ticket->assignedTo->name }}</div>
                                @else
                                    <span class="text-sm text-gray-400 italic">ไม่ได้มอบหมาย</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                <div>{{ $ticket->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs">{{ $ticket->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors">
                                    <i class="fa-solid fa-eye mr-2"></i>
                                    ดู
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                    <i class="fa-solid fa-ticket text-5xl mb-4"></i>
                                    <p class="text-lg">ไม่พบ Ticket</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tickets->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 dark:border-slate-700">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
