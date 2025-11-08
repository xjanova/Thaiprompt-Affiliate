@extends('layouts.user')

@section('title', 'Ticket ของฉัน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">Ticket Support</h2>
                <p class="text-blue-100 text-sm">ติดตามและจัดการคำขอความช่วยเหลือของคุณ</p>
            </div>
            <a href="{{ route('user.tickets.create') }}" class="px-6 py-3 bg-white hover:bg-gray-100 text-blue-600 font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105">
                <i class="fa-solid fa-plus mr-2"></i>
                สร้าง Ticket ใหม่
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-blue-100 text-sm">ทั้งหมด</span>
                    <i class="fa-solid fa-ticket text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['total']) }}</p>
                <p class="text-sm text-blue-100 mt-1">Tickets</p>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-blue-100 text-sm">เปิดอยู่</span>
                    <i class="fa-solid fa-folder-open text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['open']) }}</p>
                <p class="text-sm text-blue-100 mt-1">Tickets</p>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-blue-100 text-sm">ปิดแล้ว</span>
                    <i class="fa-solid fa-check-circle text-2xl"></i>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['closed']) }}</p>
                <p class="text-sm text-blue-100 mt-1">Tickets</p>
            </div>
        </div>
    </div>

    <!-- Quick Filters -->
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('user.tickets.index') }}" class="px-6 py-3 {{ !$filters['status'] ? 'bg-gradient-to-r from-indigo-600 to-purple-600' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300' }} {{ !$filters['status'] ? 'text-white' : '' }} font-semibold rounded-lg shadow-lg transition-all">
            <i class="fa-solid fa-list mr-2"></i>
            ทั้งหมด
        </a>
        <a href="{{ route('user.tickets.index', ['status' => 'open']) }}" class="px-6 py-3 {{ $filters['status'] == 'open' ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300' }} font-semibold rounded-lg shadow-lg transition-all">
            <i class="fa-solid fa-folder-open mr-2"></i>
            เปิดอยู่
        </a>
        <a href="{{ route('user.tickets.index', ['status' => 'in_progress']) }}" class="px-6 py-3 {{ $filters['status'] == 'in_progress' ? 'bg-gradient-to-r from-yellow-500 to-orange-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300' }} font-semibold rounded-lg shadow-lg transition-all">
            <i class="fa-solid fa-spinner mr-2"></i>
            กำลังดำเนินการ
        </a>
        <a href="{{ route('user.tickets.index', ['status' => 'resolved']) }}" class="px-6 py-3 {{ $filters['status'] == 'resolved' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300' }} font-semibold rounded-lg shadow-lg transition-all">
            <i class="fa-solid fa-check mr-2"></i>
            แก้ไขแล้ว
        </a>
    </div>

    <!-- Tickets List -->
    <div class="grid grid-cols-1 gap-6">
        @forelse($tickets as $ticket)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="text-sm font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $ticket->ticket_number }}</span>
                                @if($ticket->category)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $ticket->category->color }}20; color: {{ $ticket->category->color }}">
                                        @if($ticket->category->icon)
                                            <i class="{{ $ticket->category->icon }} mr-1"></i>
                                        @endif
                                        {{ $ticket->category->name }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    @if($ticket->status == 'open') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($ticket->status == 'in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($ticket->status == 'waiting_customer') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @elseif($ticket->status == 'resolved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ $ticket->status_label }}
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    @if($ticket->priority == 'critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($ticket->priority == 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                    @elseif($ticket->priority == 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ $ticket->priority_label }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $ticket->subject }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">{{ $ticket->description }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                            <span>
                                <i class="fa-solid fa-clock mr-1"></i>
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </span>
                            @if($ticket->assignedTo)
                                <span>
                                    <i class="fa-solid fa-user mr-1"></i>
                                    มอบหมายให้: {{ $ticket->assignedTo->name }}
                                </span>
                            @endif
                            @if($ticket->last_reply_at)
                                <span>
                                    <i class="fa-solid fa-reply mr-1"></i>
                                    ตอบกลับล่าสุด: {{ $ticket->last_reply_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                        <a href="{{ route('user.tickets.show', $ticket->id) }}" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg transition-all">
                            <i class="fa-solid fa-eye mr-2"></i>
                            ดูรายละเอียด
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-12 text-center">
                <i class="fa-solid fa-ticket text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Ticket</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">คุณยังไม่มี Ticket ในระบบ สร้าง Ticket ใหม่เพื่อรับความช่วยเหลือ</p>
                <a href="{{ route('user.tickets.create') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-lg shadow-lg transition-all transform hover:scale-105">
                    <i class="fa-solid fa-plus mr-2"></i>
                    สร้าง Ticket ใหม่
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($tickets->hasPages())
        <div class="flex justify-center">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
