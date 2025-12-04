{{-- รายละเอียด Cloud Provider (V3 UI) --}}
@extends('layouts.admin-v3')

@section('title', $cloudProvider->name . ' - Cloud Provider')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.ai-rental.cloud-providers.index') }}"
                   class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-server text-3xl text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                            {{ $cloudProvider->name }}
                        </h1>
                        @if($cloudProvider->is_recommended)
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 text-sm font-bold rounded-full border border-yellow-500/30">
                            <i class="fas fa-star"></i> แนะนำ
                        </span>
                        @endif
                        @if($cloudProvider->is_active)
                        <span class="px-3 py-1 bg-green-500/20 text-green-300 text-sm font-medium rounded-full">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                        @else
                        <span class="px-3 py-1 bg-red-500/20 text-red-300 text-sm font-medium rounded-full">
                            <i class="fas fa-times-circle"></i> Inactive
                        </span>
                        @endif
                    </div>
                    <p class="text-white/60 mt-1">{{ $cloudProvider->slug }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.ai-rental.cloud-providers.edit', $cloudProvider) }}"
                   class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 text-white font-bold rounded-xl hover:scale-105 transition shadow-lg">
                    <i class="fas fa-edit"></i> แก้ไข
                </a>
                @if($cloudProvider->website_url)
                <a href="{{ $cloudProvider->website_url }}"
                   target="_blank"
                   class="px-6 py-3 glass-neu text-white font-bold rounded-xl hover:bg-white/20 transition">
                    <i class="fas fa-external-link-alt"></i> เว็บไซต์
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-fusion rounded-2xl p-4 border border-white/30 text-center">
            <div class="text-3xl font-bold text-cyan-400">{{ $stats['total_configs'] }}</div>
            <div class="text-white/60 text-sm">Cloud Configs</div>
        </div>
        <div class="glass-fusion rounded-2xl p-4 border border-white/30 text-center">
            <div class="text-3xl font-bold text-green-400">{{ $stats['active_configs'] }}</div>
            <div class="text-white/60 text-sm">Active Configs</div>
        </div>
        <div class="glass-fusion rounded-2xl p-4 border border-white/30 text-center">
            <div class="text-3xl font-bold text-purple-400">{{ $stats['total_deployments'] }}</div>
            <div class="text-white/60 text-sm">Total Deployments</div>
        </div>
        <div class="glass-fusion rounded-2xl p-4 border border-white/30 text-center">
            <div class="text-3xl font-bold text-orange-400">{{ $stats['running_deployments'] }}</div>
            <div class="text-white/60 text-sm">Running</div>
        </div>
        <div class="glass-fusion rounded-2xl p-4 border border-white/30 text-center">
            <div class="text-3xl font-bold text-blue-400">{{ number_format($stats['total_hours'], 1) }}</div>
            <div class="text-white/60 text-sm">Total Hours</div>
        </div>
        <div class="glass-fusion rounded-2xl p-4 border border-white/30 text-center">
            <div class="text-3xl font-bold text-yellow-400">${{ number_format($stats['total_cost'], 2) }}</div>
            <div class="text-white/60 text-sm">Total Cost</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Description --}}
            @if($cloudProvider->description)
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-info-circle text-cyan-400"></i> รายละเอียด
                </h2>
                <p class="text-white/80 leading-relaxed">{{ $cloudProvider->description }}</p>
            </div>
            @endif

            {{-- Pricing Info --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-dollar-sign text-green-400"></i> ราคา & Tier
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Tier --}}
                    <div class="bg-white/5 rounded-xl p-4">
                        <div class="text-white/60 text-sm mb-2">Tier</div>
                        @if($cloudProvider->tier === 'free')
                            <span class="px-4 py-2 bg-green-500/20 text-green-300 text-lg font-bold rounded-full border border-green-500/30">
                                <i class="fas fa-gift"></i> FREE
                            </span>
                        @elseif($cloudProvider->tier === 'paid')
                            <span class="px-4 py-2 bg-orange-500/20 text-orange-300 text-lg font-bold rounded-full border border-orange-500/30">
                                <i class="fas fa-dollar-sign"></i> PAID
                            </span>
                        @else
                            <span class="px-4 py-2 bg-purple-500/20 text-purple-300 text-lg font-bold rounded-full border border-purple-500/30">
                                <i class="fas fa-star"></i> FREE + PAID
                            </span>
                        @endif
                    </div>

                    {{-- Price Range --}}
                    <div class="bg-white/5 rounded-xl p-4">
                        <div class="text-white/60 text-sm mb-2">ราคาต่อชั่วโมง</div>
                        @if($cloudProvider->isFree() && !$cloudProvider->min_price_per_hour)
                            <div class="text-2xl font-bold text-green-400">FREE</div>
                            @if($cloudProvider->free_gpu_hours)
                            <div class="text-white/60 text-sm">{{ $cloudProvider->free_gpu_hours }} ชั่วโมง/เดือน</div>
                            @endif
                        @elseif($cloudProvider->min_price_per_hour)
                            <div class="text-2xl font-bold text-white">
                                ${{ number_format($cloudProvider->min_price_per_hour, 2) }}
                                @if($cloudProvider->max_price_per_hour && $cloudProvider->max_price_per_hour != $cloudProvider->min_price_per_hour)
                                    - ${{ number_format($cloudProvider->max_price_per_hour, 2) }}
                                @endif
                            </div>
                            <div class="text-white/60 text-sm">per hour</div>
                        @else
                            <div class="text-white/50">ไม่ระบุ</div>
                        @endif
                    </div>
                </div>

                {{-- Free Tier Details --}}
                @if($cloudProvider->has_free_tier)
                <div class="mt-4 bg-green-500/10 rounded-xl p-4 border border-green-500/30">
                    <div class="flex items-center gap-2 text-green-400 font-bold mb-2">
                        <i class="fas fa-gift"></i> Free Tier Available
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @if($cloudProvider->free_gpu_hours)
                        <div>
                            <span class="text-white/60">GPU Hours:</span>
                            <span class="text-white ml-2">{{ $cloudProvider->free_gpu_hours }} hrs/month</span>
                        </div>
                        @endif
                        @if($cloudProvider->free_storage_gb)
                        <div>
                            <span class="text-white/60">Storage:</span>
                            <span class="text-white ml-2">{{ $cloudProvider->free_storage_gb }} GB</span>
                        </div>
                        @endif
                    </div>
                    @if($cloudProvider->limitations)
                    <div class="mt-2 text-white/60 text-sm">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        {{ $cloudProvider->limitations }}
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- GPU Types --}}
            @if($cloudProvider->gpu_types && count($cloudProvider->gpu_types) > 0)
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-microchip text-purple-400"></i> GPU Types
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($cloudProvider->gpu_types as $gpu)
                    <span class="px-4 py-2 bg-purple-500/20 text-purple-300 rounded-full border border-purple-500/30 text-sm font-medium">
                        <i class="fas fa-memory"></i> {{ $gpu }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Setup Steps --}}
            @if($cloudProvider->setup_steps && count($cloudProvider->setup_steps) > 0)
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-list-ol text-blue-400"></i> ขั้นตอนการตั้งค่า
                </h2>
                <div class="space-y-3">
                    @foreach($cloudProvider->setup_steps as $index => $step)
                    <div class="flex items-start gap-3 bg-white/5 rounded-xl p-4">
                        <div class="w-8 h-8 bg-blue-500/20 text-blue-400 rounded-full flex items-center justify-center font-bold flex-shrink-0">
                            {{ $index + 1 }}
                        </div>
                        <div class="text-white/80">{{ $step }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Features --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-check-square text-cyan-400"></i> Features
                </h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <span class="text-white/80"><i class="fas fa-hug text-orange-400 w-6"></i> Hugging Face</span>
                        @if($cloudProvider->supports_huggingface)
                            <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                        @else
                            <span class="text-red-400"><i class="fas fa-times-circle"></i></span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <span class="text-white/80"><i class="fab fa-docker text-blue-400 w-6"></i> Docker</span>
                        @if($cloudProvider->supports_docker)
                            <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                        @else
                            <span class="text-red-400"><i class="fas fa-times-circle"></i></span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <span class="text-white/80"><i class="fas fa-book text-purple-400 w-6"></i> Jupyter</span>
                        @if($cloudProvider->supports_jupyter)
                            <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                        @else
                            <span class="text-red-400"><i class="fas fa-times-circle"></i></span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <span class="text-white/80"><i class="fas fa-terminal text-green-400 w-6"></i> SSH</span>
                        @if($cloudProvider->supports_ssh)
                            <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                        @else
                            <span class="text-red-400"><i class="fas fa-times-circle"></i></span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <span class="text-white/80"><i class="fas fa-code text-cyan-400 w-6"></i> API</span>
                        @if($cloudProvider->supports_api)
                            <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                        @else
                            <span class="text-red-400"><i class="fas fa-times-circle"></i></span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Rating --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-star text-yellow-400"></i> คะแนน
                </h2>
                <div class="text-center">
                    @if($cloudProvider->user_rating)
                    <div class="text-5xl font-bold text-white mb-2">
                        {{ number_format($cloudProvider->user_rating, 1) }}
                    </div>
                    <div class="flex items-center justify-center gap-1 mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= round($cloudProvider->user_rating))
                                <i class="fas fa-star text-yellow-400"></i>
                            @else
                                <i class="far fa-star text-white/30"></i>
                            @endif
                        @endfor
                    </div>
                    <div class="text-white/60 text-sm">{{ $cloudProvider->total_reviews }} รีวิว</div>
                    @else
                    <div class="text-white/50">ยังไม่มีคะแนน</div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/60">Popularity Score:</span>
                        <span class="text-white font-bold">{{ number_format($cloudProvider->popularity_score) }}</span>
                    </div>
                </div>
            </div>

            {{-- Links --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-link text-cyan-400"></i> ลิงก์
                </h2>
                <div class="space-y-3">
                    @if($cloudProvider->website_url)
                    <a href="{{ $cloudProvider->website_url }}"
                       target="_blank"
                       class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition text-white">
                        <i class="fas fa-globe w-6 text-blue-400"></i>
                        <span>เว็บไซต์</span>
                        <i class="fas fa-external-link-alt ml-auto text-white/50"></i>
                    </a>
                    @endif
                    @if($cloudProvider->setup_guide_url)
                    <a href="{{ $cloudProvider->setup_guide_url }}"
                       target="_blank"
                       class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition text-white">
                        <i class="fas fa-book w-6 text-green-400"></i>
                        <span>คู่มือการใช้งาน</span>
                        <i class="fas fa-external-link-alt ml-auto text-white/50"></i>
                    </a>
                    @endif
                    @if($cloudProvider->api_documentation_url)
                    <a href="{{ $cloudProvider->api_documentation_url }}"
                       target="_blank"
                       class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition text-white">
                        <i class="fas fa-code w-6 text-purple-400"></i>
                        <span>API Documentation</span>
                        <i class="fas fa-external-link-alt ml-auto text-white/50"></i>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Metadata --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-info text-cyan-400"></i> ข้อมูลเพิ่มเติม
                </h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white/60">Display Order:</span>
                        <span class="text-white">{{ $cloudProvider->display_order }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">สร้างเมื่อ:</span>
                        <span class="text-white">{{ $cloudProvider->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">อัพเดทล่าสุด:</span>
                        <span class="text-white">{{ $cloudProvider->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cloud Configs Section --}}
    @if($cloudProvider->cloudConfigs && $cloudProvider->cloudConfigs->count() > 0)
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-cogs text-cyan-400"></i> Cloud Configs ({{ $cloudProvider->cloudConfigs->count() }})
            </h2>
            <a href="{{ route('admin.ai-rental.configs.create') }}?provider={{ $cloudProvider->id }}"
               class="px-4 py-2 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition text-sm">
                <i class="fas fa-plus"></i> เพิ่ม Config
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-white/5 border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-bold text-white">ชื่อ</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-white">GPU</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-white">ราคา/ชม.</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-white">สถานะ</th>
                        <th class="px-4 py-3 text-right text-sm font-bold text-white">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @foreach($cloudProvider->cloudConfigs->take(5) as $config)
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-white">{{ $config->name ?? 'Config #' . $config->id }}</div>
                        </td>
                        <td class="px-4 py-3 text-center text-white/80">
                            {{ $config->gpu_type ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($config->price_per_hour)
                                <span class="text-green-400 font-bold">${{ number_format($config->price_per_hour, 2) }}</span>
                            @else
                                <span class="text-white/50">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($config->is_active ?? true)
                                <span class="px-2 py-1 bg-green-500/20 text-green-300 text-xs rounded-full">Active</span>
                            @else
                                <span class="px-2 py-1 bg-red-500/20 text-red-300 text-xs rounded-full">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.ai-rental.configs.edit', $config) }}"
                               class="text-yellow-400 hover:text-yellow-300 transition">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($cloudProvider->cloudConfigs->count() > 5)
        <div class="mt-4 text-center">
            <a href="{{ route('admin.ai-rental.configs.index') }}?provider={{ $cloudProvider->id }}"
               class="text-cyan-400 hover:text-cyan-300 transition">
                ดูทั้งหมด {{ $cloudProvider->cloudConfigs->count() }} configs <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        @endif
    </div>
    @endif

    {{-- Action Buttons --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                @if($cloudProvider->is_active)
                <form action="{{ route('admin.ai-rental.cloud-providers.deactivate', $cloudProvider) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="px-4 py-2 bg-orange-500/20 text-orange-400 hover:bg-orange-500/30 rounded-xl transition"
                            onclick="return confirm('ต้องการปิดใช้งาน {{ $cloudProvider->name }} หรือไม่?')">
                        <i class="fas fa-pause"></i> ปิดใช้งาน
                    </button>
                </form>
                @else
                <form action="{{ route('admin.ai-rental.cloud-providers.activate', $cloudProvider) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="px-4 py-2 bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded-xl transition">
                        <i class="fas fa-play"></i> เปิดใช้งาน
                    </button>
                </form>
                @endif
            </div>

            <form action="{{ route('admin.ai-rental.cloud-providers.destroy', $cloudProvider) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-xl transition"
                        onclick="return confirm('ต้องการลบ {{ $cloudProvider->name }} หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!')">
                    <i class="fas fa-trash"></i> ลบ Provider
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
