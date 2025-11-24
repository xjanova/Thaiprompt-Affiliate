@extends('layouts.admin-v3')

@section('title', 'Hugging Face News - ข่าวสาร AI Models')

@section('content')
<div class="space-y-6" x-data="newsManager()">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-newspaper text-3xl text-white"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-white drop-shadow-lg flex items-center gap-2">
                    Hugging Face News
                    <span class="px-3 py-1 bg-red-500/20 text-red-300 text-xs font-bold rounded-full border border-red-500/30 animate-pulse">
                        <i class="fas fa-bolt"></i> HOT
                    </span>
                </h1>
                <p class="text-white/80 mt-2 drop-shadow">
                    ข่าวสาร tutorials และอัพเดทล่าสุดเกี่ยวกับ AI Models
                </p>
            </div>
        </div>
    </div>

    {{-- Featured / Pinned News --}}
    @if($featured_news->count() > 0)
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-star text-yellow-400"></i>
            ข่าวเด่นวันนี้
        </h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($featured_news as $news)
            <a href="{{ route('admin.ai-rental.news.show', $news) }}"
               class="glass-neu rounded-2xl overflow-hidden border border-white/20 hover:border-cyan-400/50 hover:scale-105 transition-all duration-300 group">
                {{-- Image --}}
                @if($news->featured_image_url)
                <div class="aspect-video bg-gradient-to-br from-cyan-500/20 to-purple-500/20 relative overflow-hidden">
                    <img src="{{ $news->featured_image_url }}"
                         alt="{{ $news->title }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                    {{-- Badges --}}
                    <div class="absolute top-4 left-4 flex gap-2">
                        @if($news->is_pinned)
                        <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                            <i class="fas fa-thumbtack"></i> ปักหมุด
                        </span>
                        @endif
                        @if($news->importance === 'critical')
                        <span class="px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full animate-pulse">
                            <i class="fas fa-exclamation-circle"></i> สำคัญมาก
                        </span>
                        @endif
                    </div>
                </div>
                @endif

                <div class="p-6">
                    {{-- Category & Type --}}
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 text-xs font-bold rounded-full border border-cyan-500/30">
                            {{ $news->categories[0] ?? 'General' }}
                        </span>
                        <span class="px-3 py-1 bg-purple-500/20 text-purple-300 text-xs rounded-full border border-purple-500/30">
                            @switch($news->news_type)
                                @case('new_model') 🆕 Model ใหม่ @break
                                @case('model_update') 🔄 อัพเดท @break
                                @case('trending') 🔥 Trending @break
                                @case('tutorial') 📚 Tutorial @break
                                @case('best_practices') 💡 Best Practices @break
                                @default {{ $news->news_type }}
                            @endswitch
                        </span>
                    </div>

                    {{-- Title --}}
                    <h3 class="text-xl font-bold text-white mb-2 line-clamp-2 group-hover:text-cyan-400 transition">
                        {{ $news->title }}
                    </h3>

                    {{-- Summary --}}
                    <p class="text-white/70 text-sm mb-4 line-clamp-2">
                        {{ $news->summary }}
                    </p>

                    {{-- Meta --}}
                    <div class="flex items-center justify-between text-xs text-white/60">
                        <div class="flex items-center gap-3">
                            <span><i class="fas fa-user"></i> {{ $news->author_name }}</span>
                            <span><i class="fas fa-clock"></i> {{ $news->published_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span><i class="fas fa-eye"></i> {{ number_format($news->view_count) }}</span>
                            <span><i class="fas fa-heart"></i> {{ number_format($news->like_count) }}</span>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div class="md:col-span-1">
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-search"></i> ค้นหา
                </label>
                <input type="text"
                       x-model="search"
                       @input.debounce.500ms="filterNews()"
                       placeholder="ค้นหาข่าว..."
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400">
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-filter"></i> หมวดหมู่
                </label>
                <select x-model="selectedCategory"
                        @change="filterNews()"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="">ทั้งหมด</option>
                    <option value="Image Generation">Image Generation</option>
                    <option value="Language Models">Language Models</option>
                    <option value="Audio Processing">Audio Processing</option>
                    <option value="Tutorials">Tutorials</option>
                    <option value="Updates">Updates</option>
                </select>
            </div>

            {{-- News Type --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-tag"></i> ประเภท
                </label>
                <select x-model="selectedType"
                        @change="filterNews()"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="">ทั้งหมด</option>
                    <option value="new_model">🆕 Model ใหม่</option>
                    <option value="model_update">🔄 อัพเดท Model</option>
                    <option value="trending">🔥 Trending</option>
                    <option value="tutorial">📚 Tutorial</option>
                    <option value="best_practices">💡 Best Practices</option>
                </select>
            </div>
        </div>
    </div>

    {{-- News Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($news as $article)
        <a href="{{ route('admin.ai-rental.news.show', $article) }}"
           class="glass-fusion rounded-2xl overflow-hidden border border-white/30 hover:border-cyan-400/50 hover:scale-105 transition-all duration-300 group flex flex-col">
            {{-- Image or Gradient Header --}}
            <div class="aspect-video bg-gradient-to-br from-{{ ['cyan', 'purple', 'blue', 'green', 'orange'][($loop->index) % 5] }}-500/20 to-{{ ['purple', 'pink', 'cyan', 'blue', 'red'][($loop->index) % 5] }}-500/20 relative overflow-hidden">
                <div class="absolute inset-0 flex items-center justify-center">
                    <i class="fas
                        @switch($article->news_type)
                            @case('new_model') fa-sparkles @break
                            @case('model_update') fa-sync-alt @break
                            @case('trending') fa-fire @break
                            @case('tutorial') fa-book-open @break
                            @case('best_practices') fa-lightbulb @break
                            @default fa-newspaper
                        @endswitch
                        text-6xl text-white/20"></i>
                </div>

                {{-- Type Badge --}}
                <div class="absolute top-4 left-4">
                    <span class="px-3 py-1
                        @if($article->news_type === 'new_model') bg-green-500
                        @elseif($article->news_type === 'trending') bg-red-500 animate-pulse
                        @elseif($article->news_type === 'tutorial') bg-blue-500
                        @else bg-purple-500
                        @endif
                        text-white text-xs font-bold rounded-full">
                        @switch($article->news_type)
                            @case('new_model') 🆕 NEW @break
                            @case('model_update') 🔄 UPDATE @break
                            @case('trending') 🔥 HOT @break
                            @case('tutorial') 📚 GUIDE @break
                            @case('best_practices') 💡 TIPS @break
                        @endswitch
                    </span>
                </div>
            </div>

            <div class="p-6 flex-1 flex flex-col">
                {{-- Category --}}
                <div class="mb-3">
                    <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 text-xs font-bold rounded-full border border-cyan-500/30">
                        {{ $article->categories[0] ?? 'General' }}
                    </span>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-bold text-white mb-2 line-clamp-2 group-hover:text-cyan-400 transition flex-shrink-0">
                    {{ $article->title }}
                </h3>

                {{-- Summary --}}
                <p class="text-white/70 text-sm mb-4 line-clamp-3 flex-1">
                    {{ $article->summary }}
                </p>

                {{-- Model Badge --}}
                @if($article->model_name)
                <div class="mb-4 flex-shrink-0">
                    <div class="px-3 py-2 bg-white/5 rounded-lg border border-white/10">
                        <div class="text-white/60 text-xs mb-1">Featured Model:</div>
                        <div class="text-white font-medium text-sm">{{ $article->model_name }}</div>
                    </div>
                </div>
                @endif

                {{-- Meta Info --}}
                <div class="flex items-center justify-between text-xs text-white/60 pt-4 border-t border-white/10 flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user"></i>
                        <span>{{ Str::limit($article->author_name, 20) }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span><i class="fas fa-eye"></i> {{ number_format($article->view_count) }}</span>
                        <span><i class="fas fa-heart"></i> {{ $article->like_count }}</span>
                    </div>
                </div>

                {{-- Reading Time --}}
                <div class="mt-3 flex items-center gap-4 text-xs text-white/60 flex-shrink-0">
                    <span><i class="fas fa-clock"></i> {{ $article->published_at->diffForHumans() }}</span>
                    @if(isset($article->metadata['reading_time']))
                    <span><i class="fas fa-book-reader"></i> {{ $article->metadata['reading_time'] }}</span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="col-span-full glass-fusion rounded-2xl p-12 border border-white/30 text-center">
            <div class="w-24 h-24 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-newspaper text-4xl text-white/50"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">ไม่มีข่าวสาร</h3>
            <p class="text-white/70">ลองปรับตัวกรองหรือกลับมาดูใหม่ภายหลัง</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($news->hasPages())
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        {{ $news->links() }}
    </div>
    @endif

    {{-- Info Box --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-400"></i>
            เกี่ยวกับข่าวสาร
        </h3>
        <div class="text-white/70 space-y-2 text-sm">
            <p>
                <strong class="text-white">🔥 Trending</strong> - ข่าวที่กำลังฮิตและมีคนสนใจมากที่สุด
            </p>
            <p>
                <strong class="text-white">🆕 New Model</strong> - โมเดล AI ใหม่ที่เพิ่งเปิดตัว
            </p>
            <p>
                <strong class="text-white">📚 Tutorial</strong> - คู่มือและ step-by-step guides
            </p>
            <p>
                <strong class="text-white">💡 Best Practices</strong> - เทคนิคและแนวทางปฏิบัติที่ดี
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function newsManager() {
    return {
        search: '',
        selectedCategory: '',
        selectedType: '',

        filterNews() {
            // Build query parameters
            const params = new URLSearchParams();

            if (this.search) params.append('search', this.search);
            if (this.selectedCategory) params.append('category', this.selectedCategory);
            if (this.selectedType) params.append('type', this.selectedType);

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
