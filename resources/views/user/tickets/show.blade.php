@extends('layouts.user')

@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <h2 class="text-3xl font-bold mb-2">{{ $ticket->subject }}</h2>
                <div class="flex items-center space-x-3 flex-wrap">
                    <span class="text-sm font-mono bg-white/20 px-3 py-1 rounded-lg">{{ $ticket->ticket_number }}</span>
                    @if($ticket->category)
                        <span class="inline-flex items-center px-3 py-1 bg-white/20 rounded-lg text-sm">
                            @if($ticket->category->icon)
                                <i class="{{ $ticket->category->icon }} mr-2"></i>
                            @endif
                            {{ $ticket->category->name }}
                        </span>
                    @endif
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium
                        @if($ticket->status == 'open') bg-blue-500/30
                        @elseif($ticket->status == 'in_progress') bg-yellow-500/30
                        @elseif($ticket->status == 'waiting_customer') bg-purple-500/30
                        @elseif($ticket->status == 'resolved') bg-green-500/30
                        @else bg-gray-500/30
                        @endif">
                        {{ $ticket->status_label }}
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @if(!$ticket->isClosed())
                    <form method="POST" action="{{ route('user.tickets.close', $ticket->id) }}" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปิด Ticket นี้?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all">
                            <i class="fa-solid fa-times-circle mr-2"></i>
                            ปิด Ticket
                        </button>
                    </form>
                @endif
                <a href="{{ route('user.tickets.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Status Info -->
    @if($ticket->status == 'resolved')
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
            <div class="flex items-center space-x-4">
                <i class="fa-solid fa-check-circle text-4xl text-green-600 dark:text-green-400"></i>
                <div>
                    <h3 class="text-lg font-bold text-green-900 dark:text-green-300 mb-1">Ticket นี้ได้รับการแก้ไขแล้ว</h3>
                    <p class="text-green-800 dark:text-green-400">หากคุณยังมีปัญหา สามารถตอบกลับเพื่อเปิด Ticket ใหม่อีกครั้ง</p>
                </div>
            </div>
        </div>
    @elseif($ticket->status == 'waiting_customer')
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-6">
            <div class="flex items-center space-x-4">
                <i class="fa-solid fa-hourglass-half text-4xl text-purple-600 dark:text-purple-400"></i>
                <div>
                    <h3 class="text-lg font-bold text-purple-900 dark:text-purple-300 mb-1">รอข้อมูลเพิ่มเติมจากคุณ</h3>
                    <p class="text-purple-800 dark:text-purple-400">ทีมงานกำลังรอคำตอบหรือข้อมูลเพิ่มเติมจากคุณ</p>
                </div>
            </div>
        </div>
    @elseif($ticket->status == 'in_progress')
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
            <div class="flex items-center space-x-4">
                <i class="fa-solid fa-spinner fa-spin text-4xl text-yellow-600 dark:text-yellow-400"></i>
                <div>
                    <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-300 mb-1">กำลังดำเนินการ</h3>
                    <p class="text-yellow-800 dark:text-yellow-400">
                        ทีมงานกำลังตรวจสอบและแก้ไขปัญหาของคุณ
                        @if($ticket->assignedTo)
                            โดย {{ $ticket->assignedTo->name }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Conversation -->
    <div class="space-y-6">
        <!-- Original Message -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-lg font-bold">
                        {{ substr($ticket->user->name, 0, 1) }}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $ticket->user->name }} <span class="text-sm font-normal text-gray-500">(คุณ)</span></h3>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->description }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Replies -->
        @foreach($ticket->publicReplies as $reply)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full {{ $reply->isFromStaff() ? 'bg-gradient-to-br from-purple-500 to-pink-600' : 'bg-gradient-to-br from-blue-500 to-indigo-600' }} flex items-center justify-center text-white text-lg font-bold">
                            {{ substr($reply->user->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $reply->user->name }}
                                    @if($reply->isFromStaff())
                                        <span class="ml-2 inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs font-medium">
                                            <i class="fa-solid fa-shield-halved mr-1"></i>
                                            ทีมงาน
                                        </span>
                                    @else
                                        <span class="text-sm font-normal text-gray-500">(คุณ)</span>
                                    @endif
                                </h3>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="bg-{{ $reply->isFromStaff() ? 'purple' : 'blue' }}-50 dark:bg-{{ $reply->isFromStaff() ? 'purple' : 'blue' }}-900/20 rounded-lg p-4">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Reply Form -->
    @if(!$ticket->isClosed())
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 sticky bottom-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fa-solid fa-reply mr-2"></i>
                ตอบกลับ
            </h3>
            <form method="POST" action="{{ route('user.tickets.reply', $ticket->id) }}">
                @csrf
                <div class="mb-4">
                    <textarea name="message" rows="4" required class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 resize-none" placeholder="พิมพ์ข้อความของคุณที่นี่..."></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fa-solid fa-info-circle mr-1"></i>
                        ทีมงานจะตอบกลับโดยเร็วที่สุด
                    </p>
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-lg shadow-lg transition-all transform hover:scale-105">
                        <i class="fa-solid fa-paper-plane mr-2"></i>
                        ส่งข้อความ
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-8 text-center">
            <i class="fa-solid fa-lock text-5xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Ticket นี้ถูกปิดแล้ว</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">หากคุณต้องการความช่วยเหลือเพิ่มเติม กรุณาสร้าง Ticket ใหม่</p>
            <a href="{{ route('user.tickets.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-lg shadow-lg transition-all">
                <i class="fa-solid fa-plus mr-2"></i>
                สร้าง Ticket ใหม่
            </a>
        </div>
    @endif
</div>
@endsection
