@extends('layouts.admin-v3')

@section('title', 'Trending Models - โมเดล AI ยอดนิยม')

@section('content')
<div class="space-y-6" x-data="trendingModelsManager()">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-fire text-3xl text-white"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                    Trending Models จาก Hugging Face
                </h1>
                <p class="text-white/80 mt-2 drop-shadow">
                    โมเดล AI ยอดนิยมและมาแรงที่สุด พร้อม deploy บน Cloud GPU
                </p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold text-white drop-shadow-lg">
                    {{ $models->total() }}
                </div>
                <p class="text-white/80 text-sm mt-1">Models</p>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-search"></i> ค้นหา Model
                </label>
                <input type="text"
                       x-model="search"
                       @input.debounce.500ms="filterModels()"
                       placeholder="ค้นหาชื่อ model, author, หรือคำอธิบาย..."
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400">
            </div>

            {{-- Category Filter --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-filter"></i> หมวดหมู่
                </label>
                <select x-model="selectedCategory"
                        @change="filterModels()"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="">ทั้งหมด</option>
                    <option value="Image Generation">Image Generation</option>
                    <option value="Language Model">Language Model</option>
                    <option value="Audio Processing">Audio Processing</option>
                    <option value="Video Generation">Video Generation</option>
                    <option value="Vision-Language">Vision-Language</option>
                    <option value="Audio Generation">Audio Generation</option>
                    <option value="Code Generation">Code Generation</option>
                    <option value="Translation">Translation</option>
                    <option value="Embeddings">Embeddings</option>
                </select>
            </div>

            {{-- Sort By --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-sort"></i> เรียงตาม
                </label>
                <select x-model="sortBy"
                        @change="filterModels()"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="trending">🔥 Trending Score</option>
                    <option value="popular">📊 ยอดนิยม (Downloads)</option>
                    <option value="likes">❤️ Likes</option>
                    <option value="new">🆕 ใหม่ล่าสุด</option>
                    <option value="cost_low">💰 ราคาต่ำ-สูง</option>
                    <option value="cost_high">💸 ราคาสูง-ต่ำ</option>
                </select>
            </div>
        </div>

        {{-- Quick Filters --}}
        <div class="flex flex-wrap gap-2 mt-4">
            <button @click="selectedCategory = ''; filterModels()"
                    :class="selectedCategory === '' ? 'bg-cyan-500 text-white' : 'glass-neu text-white/80'"
                    class="px-4 py-2 rounded-lg font-medium transition hover:scale-105">
                ทั้งหมด
            </button>
            <button @click="toggleFilter('featured')"
                    :class="filters.featured ? 'bg-yellow-500 text-white' : 'glass-neu text-white/80'"
                    class="px-4 py-2 rounded-lg font-medium transition hover:scale-105">
                <i class="fas fa-star"></i> Featured
            </button>
            <button @click="toggleFilter('recommended')"
                    :class="filters.recommended ? 'bg-green-500 text-white' : 'glass-neu text-white/80'"
                    class="px-4 py-2 rounded-lg font-medium transition hover:scale-105">
                <i class="fas fa-thumbs-up"></i> แนะนำ
            </button>
            <button @click="toggleFilter('production')"
                    :class="filters.production ? 'bg-blue-500 text-white' : 'glass-neu text-white/80'"
                    class="px-4 py-2 rounded-lg font-medium transition hover:scale-105">
                <i class="fas fa-check-circle"></i> Production Ready
            </button>
            <button @click="toggleFilter('freeTier')"
                    :class="filters.freeTier ? 'bg-purple-500 text-white' : 'glass-neu text-white/80'"
                    class="px-4 py-2 rounded-lg font-medium transition hover:scale-105">
                <i class="fas fa-gift"></i> รัน FREE ได้
            </button>
        </div>
    </div>

    {{-- Models Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($models as $model)
        <div class="glass-fusion rounded-2xl p-6 border border-white/30 hover:border-cyan-400/50 hover:scale-105 transition-all duration-300">
            {{-- Header --}}
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-xl font-bold text-white">{{ $model->model_name }}</h3>
                        @if($model->is_featured)
                            <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs font-bold rounded-lg border border-yellow-500/30">
                                <i class="fas fa-star"></i> Featured
                            </span>
                        @endif
                    </div>
                    <div class="text-white/60 text-sm">
                        <i class="fas fa-user"></i> {{ $model->author }}
                    </div>
                </div>

                {{-- Trending Score Badge --}}
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br
                        @if($model->trending_score >= 90) from-red-400 to-orange-500
                        @elseif($model->trending_score >= 70) from-orange-400 to-yellow-500
                        @else from-yellow-400 to-green-500
                        @endif
                        flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">{{ $model->trending_score }}</div>
                            <div class="text-xs text-white/80">Score</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Category & Task --}}
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 text-xs font-bold rounded-full border border-cyan-500/30">
                    {{ $model->category }}
                </span>
                <span class="px-3 py-1 bg-purple-500/20 text-purple-300 text-xs font-medium rounded-full border border-purple-500/30">
                    {{ $model->task }}
                </span>
                @if($model->difficulty_level === 'beginner')
                    <span class="px-3 py-1 bg-green-500/20 text-green-300 text-xs font-medium rounded-full border border-green-500/30">
                        <i class="fas fa-seedling"></i> Beginner
                    </span>
                @elseif($model->difficulty_level === 'intermediate')
                    <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 text-xs font-medium rounded-full border border-yellow-500/30">
                        <i class="fas fa-graduation-cap"></i> Intermediate
                    </span>
                @else
                    <span class="px-3 py-1 bg-red-500/20 text-red-300 text-xs font-medium rounded-full border border-red-500/30">
                        <i class="fas fa-rocket"></i> Advanced
                    </span>
                @endif
            </div>

            {{-- Description --}}
            <p class="text-white/70 text-sm mb-4 line-clamp-3">
                {{ $model->description }}
            </p>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="text-center">
                    <div class="text-white/60 text-xs mb-1">Downloads</div>
                    <div class="text-white font-bold text-sm">
                        @if($model->downloads >= 1000000)
                            {{ number_format($model->downloads / 1000000, 1) }}M
                        @elseif($model->downloads >= 1000)
                            {{ number_format($model->downloads / 1000, 0) }}K
                        @else
                            {{ number_format($model->downloads) }}
                        @endif
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-white/60 text-xs mb-1">Likes</div>
                    <div class="text-white font-bold text-sm">
                        @if($model->likes >= 1000)
                            {{ number_format($model->likes / 1000, 1) }}K
                        @else
                            {{ number_format($model->likes) }}
                        @endif
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-white/60 text-xs mb-1">Size</div>
                    <div class="text-white font-bold text-sm">{{ $model->model_size }}</div>
                </div>
            </div>

            {{-- Hardware Requirements --}}
            <div class="bg-white/5 rounded-xl p-3 mb-4">
                <div class="flex items-center gap-2 text-white/80 text-xs mb-2">
                    <i class="fas fa-microchip"></i>
                    <span class="font-semibold">GPU Requirements:</span>
                </div>
                <div class="text-white text-sm">
                    <div><strong>Min VRAM:</strong> {{ $model->min_gpu_memory }}</div>
                    <div class="text-white/60 text-xs mt-1">{{ $model->recommended_gpu }}</div>
                </div>
                <div class="mt-2 text-orange-400 font-bold text-lg">
                    ${{ number_format($model->estimated_cost_per_hour, 2) }}/hr
                </div>
            </div>

            {{-- Features --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @if($model->supports_inference_api)
                    <span class="px-2 py-1 bg-blue-500/20 text-blue-300 text-xs rounded">
                        <i class="fas fa-plug"></i> API
                    </span>
                @endif
                @if($model->can_run_locally)
                    <span class="px-2 py-1 bg-green-500/20 text-green-300 text-xs rounded">
                        <i class="fas fa-laptop"></i> Local
                    </span>
                @endif
                @if($model->is_production_ready)
                    <span class="px-2 py-1 bg-purple-500/20 text-purple-300 text-xs rounded">
                        <i class="fas fa-check-circle"></i> Prod Ready
                    </span>
                @endif
                @if($model->is_recommended)
                    <span class="px-2 py-1 bg-yellow-500/20 text-yellow-300 text-xs rounded">
                        <i class="fas fa-star"></i> แนะนำ
                    </span>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <a href="{{ $model->model_url }}" target="_blank"
                   class="flex-1 px-4 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold rounded-xl hover:scale-105 transition-transform text-center text-sm">
                    <i class="fas fa-external-link-alt"></i> View on HF
                </a>
                <a href="{{ route('admin.ai-rental.cost-calculator') }}?model={{ $model->id }}"
                   class="flex-1 px-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition-transform text-center text-sm">
                    <i class="fas fa-calculator"></i> Calculate Cost
                </a>
            </div>

            {{-- Additional Links --}}
            @if($model->demo_url || $model->paper_url)
            <div class="flex gap-2 mt-2">
                @if($model->demo_url)
                <a href="{{ $model->demo_url }}" target="_blank"
                   class="flex-1 px-3 py-2 glass-neu text-white/80 hover:text-white text-center text-xs rounded-lg transition">
                    <i class="fas fa-play-circle"></i> Demo
                </a>
                @endif
                @if($model->paper_url)
                <a href="{{ $model->paper_url }}" target="_blank"
                   class="flex-1 px-3 py-2 glass-neu text-white/80 hover:text-white text-center text-xs rounded-lg transition">
                    <i class="fas fa-file-pdf"></i> Paper
                </a>
                @endif
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-full glass-fusion rounded-2xl p-12 border border-white/30 text-center">
            <div class="w-24 h-24 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-4xl text-white/50"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">ไม่พบ Models</h3>
            <p class="text-white/70">ลองเปลี่ยนตัวกรองหรือคำค้นหา</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($models->hasPages())
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        {{ $models->links() }}
    </div>
    @endif

    {{-- Info Box --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
            <i class="fas fa-info-circle text-cyan-400"></i>
            เกี่ยวกับ Trending Models
        </h3>
        <div class="text-white/70 space-y-2 text-sm">
            <p>
                <strong class="text-white">Trending Score</strong> คำนวณจาก: Downloads (40%), Likes (30%),
                ความใหม่ (20%), และความสมบูรณ์ของข้อมูล (10%)
            </p>
            <p>
                <strong class="text-white">Estimated Cost</strong> เป็นราคาโดยประมาณต่อชั่วโมง
                สำหรับการ deploy บน Cloud GPU (ไม่รวมค่า egress และ storage)
            </p>
            <p>
                <strong class="text-white">Hardware Requirements</strong> เป็นข้อมูลแนะนำ
                ความต้องการจริงขึ้นกับ batch size, quantization, และการใช้งาน
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function trendingModelsManager() {
    return {
        search: '',
        selectedCategory: '',
        sortBy: 'trending',
        filters: {
            featured: false,
            recommended: false,
            production: false,
            freeTier: false,
        },

        toggleFilter(filterName) {
            this.filters[filterName] = !this.filters[filterName];
            this.filterModels();
        },

        filterModels() {
            // Build query parameters
            const params = new URLSearchParams();

            if (this.search) params.append('search', this.search);
            if (this.selectedCategory) params.append('category', this.selectedCategory);
            if (this.sortBy) params.append('sort', this.sortBy);

            // Add active filters
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) params.append(key, '1');
            });

            // Reload page with new parameters
            const url = new URL(window.location.href);
            url.search = params.toString();
            window.location.href = url.toString();
        }
    }
}
</script>
@endpush
@endsection
