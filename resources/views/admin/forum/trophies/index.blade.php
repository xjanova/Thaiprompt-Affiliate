@extends('layouts.admin-v3')

@section('title', 'จัดการถ้วยรางวัลฟอรั่ม')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">🏆 จัดการถ้วยรางวัล</h1>
            <p class="text-white/60 mt-1">สร้างและจัดการถ้วยรางวัลสำหรับผู้ใช้ฟอรั่ม</p>
        </div>
        <a href="{{ route('admin.platform-revenue.forum.trophies.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg hover:from-yellow-600 hover:to-orange-600 transition shadow-lg">
            <i class="fas fa-plus"></i>
            <span>สร้างถ้วยรางวัลใหม่</span>
        </a>
    </div>

    {{-- Trophies Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($trophies as $trophy)
            <div class="glass-card rounded-xl p-6 hover:bg-white/10 transition">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">{{ $trophy->icon ?? '🏆' }}</div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-white">{{ $trophy->name }}</h3>
                        <p class="text-white/60 text-sm mt-1">{{ Str::limit($trophy->description, 60) }}</p>
                        <div class="flex items-center gap-4 mt-3">
                            <div class="flex items-center gap-1 text-yellow-400">
                                <i class="fas fa-star text-xs"></i>
                                <span class="font-bold">{{ $trophy->points }}</span>
                                <span class="text-white/40 text-xs">คะแนน</span>
                            </div>
                            <div class="flex items-center gap-1 text-blue-400">
                                <i class="fas fa-users text-xs"></i>
                                <span class="font-bold">{{ $trophy->users_count }}</span>
                                <span class="text-white/40 text-xs">ผู้ได้รับ</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-white/10">
                    <div>
                        @if($trophy->is_automatic)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs">🤖 อัตโนมัติ</span>
                        @else
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded text-xs">👤 มอบเอง</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.platform-revenue.forum.trophies.edit', $trophy) }}"
                           class="p-2 text-blue-400 hover:bg-blue-500/20 rounded-lg transition">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.platform-revenue.forum.trophies.delete', $trophy) }}" method="POST" class="inline"
                              onsubmit="return confirm('ต้องการลบถ้วยรางวัลนี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full glass-card rounded-xl p-12 text-center">
                <i class="fas fa-trophy text-4xl mb-4 text-yellow-400"></i>
                <p class="text-white/60">ยังไม่มีถ้วยรางวัล</p>
                <a href="{{ route('admin.platform-revenue.forum.trophies.create') }}" class="text-blue-400 hover:underline mt-2 inline-block">
                    + สร้างถ้วยรางวัลแรก
                </a>
            </div>
        @endforelse
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
@endsection
