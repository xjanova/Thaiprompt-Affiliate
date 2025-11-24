@extends('layouts.admin-v3')

@section('title', 'My Cloud Configurations')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-key text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                        My Cloud Configurations
                    </h1>
                    <p class="text-white/80 mt-1">
                        จัดการ API Keys และการตั้งค่า Cloud Providers
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.ai-rental.configs.create') }}"
               class="px-6 py-3 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition shadow-lg">
                <i class="fas fa-plus"></i> เพิ่ม Configuration
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-database text-2xl text-blue-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Total Configs</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Active</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Has Error</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['has_error'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Provider Filter --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-filter"></i> Provider
                </label>
                <select name="provider"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="">ทั้งหมด</option>
                    @foreach($providers as $provider)
                    <option value="{{ $provider->id }}" {{ request('provider') == $provider->id ? 'selected' : '' }}>
                        {{ $provider->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-toggle-on"></i> Status
                </label>
                <select name="active"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="">ทั้งหมด</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition">
                    <i class="fas fa-filter"></i> กรอง
                </button>
                <a href="{{ route('admin.ai-rental.configs.index') }}"
                   class="px-6 py-3 glass-neu text-white font-bold rounded-xl hover:bg-white/20 transition">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Configurations Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($configs as $config)
        <div class="glass-fusion rounded-2xl p-6 border border-white/30 hover:border-cyan-400/50 hover:scale-105 transition-all duration-300">
            {{-- Header --}}
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        {{ $config->config_name }}
                        @if($config->is_default)
                        <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs font-bold rounded-lg">
                            <i class="fas fa-star"></i> DEFAULT
                        </span>
                        @endif
                    </h3>
                    <div class="text-white/60 text-sm mt-1">
                        {{ $config->cloudProvider->name }}
                    </div>
                </div>

                {{-- Status Badge --}}
                <div>
                    @if($config->is_active)
                    <span class="px-3 py-1 bg-green-500/20 text-green-300 text-sm font-medium rounded-full">
                        <i class="fas fa-check-circle"></i> Active
                    </span>
                    @else
                    <span class="px-3 py-1 bg-red-500/20 text-red-300 text-sm font-medium rounded-full">
                        <i class="fas fa-times-circle"></i> Inactive
                    </span>
                    @endif
                </div>
            </div>

            {{-- GPU Config --}}
            @if($config->gpu_config)
            <div class="bg-white/5 rounded-xl p-4 mb-4">
                <div class="text-white/60 text-xs font-semibold mb-2">GPU Configuration:</div>
                <div class="space-y-1 text-sm">
                    @if(isset($config->gpu_config['region']))
                    <div class="flex justify-between text-white/80">
                        <span>Region:</span>
                        <span class="font-medium">{{ $config->gpu_config['region'] }}</span>
                    </div>
                    @endif
                    @if(isset($config->gpu_config['gpu_type']))
                    <div class="flex justify-between text-white/80">
                        <span>GPU Type:</span>
                        <span class="font-medium">{{ $config->gpu_config['gpu_type'] }}</span>
                    </div>
                    @endif
                    @if(isset($config->gpu_config['max_instances']))
                    <div class="flex justify-between text-white/80">
                        <span>Max Instances:</span>
                        <span class="font-medium">{{ $config->gpu_config['max_instances'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- API Key Status --}}
            <div class="flex items-center gap-2 mb-4 text-sm">
                @if($config->api_key)
                <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-lg">
                    <i class="fas fa-key"></i> API Key ตั้งค่าแล้ว
                </span>
                @else
                <span class="px-3 py-1 bg-orange-500/20 text-orange-300 rounded-lg">
                    <i class="fas fa-exclamation-triangle"></i> ยังไม่ตั้งค่า API Key
                </span>
                @endif
            </div>

            {{-- Meta Info --}}
            <div class="border-t border-white/10 pt-4 mb-4">
                <div class="grid grid-cols-2 gap-2 text-xs text-white/60">
                    <div>
                        <div class="mb-1">สร้างเมื่อ:</div>
                        <div class="text-white/80">{{ $config->created_at->format('d M Y') }}</div>
                    </div>
                    @if($config->last_used_at)
                    <div>
                        <div class="mb-1">ใช้งานล่าสุด:</div>
                        <div class="text-white/80">{{ $config->last_used_at->diffForHumans() }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                {{-- Edit --}}
                <a href="{{ route('admin.ai-rental.configs.edit', $config) }}"
                   class="flex-1 px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-600 text-white font-bold rounded-xl hover:scale-105 transition text-center text-sm">
                    <i class="fas fa-edit"></i> แก้ไข
                </a>

                {{-- Set Default --}}
                @if(!$config->is_default)
                <form action="{{ route('admin.ai-rental.configs.set-default', $config) }}" method="POST" class="flex-1">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="w-full px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-bold rounded-xl hover:scale-105 transition text-sm">
                        <i class="fas fa-star"></i> Set Default
                    </button>
                </form>
                @endif

                {{-- Delete --}}
                <form action="{{ route('admin.ai-rental.configs.destroy', $config) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-xl transition text-sm"
                            onclick="return confirm('ต้องการลบ {{ $config->config_name }} หรือไม่?')">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full glass-fusion rounded-2xl p-12 border border-white/30 text-center">
            <div class="w-24 h-24 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-key text-4xl text-white/50"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">ยังไม่มี Configuration</h3>
            <p class="text-white/70 mb-6">เริ่มต้นด้วยการเพิ่ม API Key ของ Cloud Provider</p>
            <a href="{{ route('admin.ai-rental.configs.create') }}"
               class="inline-block px-6 py-3 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition">
                <i class="fas fa-plus"></i> เพิ่ม Configuration แรก
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($configs->hasPages())
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        {{ $configs->links() }}
    </div>
    @endif

    {{-- Help Box --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
            <i class="fas fa-question-circle text-blue-400"></i>
            วิธีใช้งาน
        </h3>
        <div class="text-white/70 space-y-2 text-sm">
            <p>
                <strong class="text-white">1. เพิ่ม Configuration</strong> - สร้าง configuration ใหม่สำหรับแต่ละ Cloud Provider
            </p>
            <p>
                <strong class="text-white">2. ใส่ API Key</strong> - กรอก API Key และ Secret จาก Cloud Provider (ข้อมูลจะถูก encrypt)
            </p>
            <p>
                <strong class="text-white">3. ตั้งค่า GPU</strong> - เลือก region, GPU type, และจำนวน instances สูงสุด
            </p>
            <p>
                <strong class="text-white">4. Set Default</strong> - ตั้งค่า config ที่ใช้บ่อยเป็น default
            </p>
        </div>
    </div>
</div>
@endsection
