@extends('layouts.admin-v3')

@section('title', $news->title . ' - Hugging Face News')

@section('content')
<div class="space-y-6">
    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.ai-rental.news.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 glass-neu text-white/80 hover:text-white rounded-lg transition">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าข่าวสาร
        </a>
    </div>

    {{-- Article Header --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30">
        {{-- Meta Badges --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 text-sm font-bold rounded-full border border-cyan-500/30">
                {{ $news->categories[0] ?? 'General' }}
            </span>
            <span class="px-3 py-1
                @if($news->news_type === 'new_model') bg-green-500
                @elseif($news->news_type === 'trending') bg-red-500 animate-pulse
                @elseif($news->news_type === 'tutorial') bg-blue-500
                @else bg-purple-500
                @endif
                text-white text-sm font-bold rounded-full">
                @switch($news->news_type)
                    @case('new_model') 🆕 New Model @break
                    @case('model_update') 🔄 Model Update @break
                    @case('trending') 🔥 Trending @break
                    @case('tutorial') 📚 Tutorial @break
                    @case('best_practices') 💡 Best Practices @break
                    @default {{ $news->news_type }}
                @endswitch
            </span>
            @if($news->importance === 'critical')
            <span class="px-3 py-1 bg-orange-500 text-white text-sm font-bold rounded-full animate-pulse">
                <i class="fas fa-exclamation-circle"></i> สำคัญมาก
            </span>
            @endif
            @if($news->is_featured)
            <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 text-sm font-bold rounded-full border border-yellow-500/30">
                <i class="fas fa-star"></i> Featured
            </span>
            @endif
        </div>

        {{-- Title --}}
        <h1 class="text-4xl font-bold text-white mb-4 leading-tight">
            {{ $news->title }}
        </h1>

        {{-- Summary --}}
        <p class="text-xl text-white/80 mb-6 leading-relaxed">
            {{ $news->summary }}
        </p>

        {{-- Meta Info --}}
        <div class="flex flex-wrap items-center gap-6 text-white/60 text-sm pb-6 border-b border-white/10">
            <div class="flex items-center gap-2">
                <i class="fas fa-user"></i>
                <span>{{ $news->author_name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar"></i>
                <span>{{ $news->published_at->format('d M Y') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-clock"></i>
                <span>{{ $news->published_at->diffForHumans() }}</span>
            </div>
            @if(isset($news->metadata['reading_time']))
            <div class="flex items-center gap-2">
                <i class="fas fa-book-reader"></i>
                <span>{{ $news->metadata['reading_time'] }} read</span>
            </div>
            @endif
            <div class="flex items-center gap-2">
                <i class="fas fa-eye"></i>
                <span>{{ number_format($news->view_count) }} views</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-heart"></i>
                <span>{{ number_format($news->like_count) }} likes</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-3 space-y-6">
            {{-- Featured Model Card --}}
            @if($news->model_id && $news->model_name)
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-robot text-cyan-400"></i>
                    Model ที่เกี่ยวข้อง
                </h3>
                <div class="bg-gradient-to-r from-cyan-500/10 to-purple-500/10 border border-cyan-500/30 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-white font-bold text-xl mb-1">{{ $news->model_name }}</div>
                            <div class="text-white/60 text-sm font-mono">{{ $news->model_id }}</div>
                        </div>
                        <div class="flex gap-2">
                            @if($news->model_url)
                            <a href="{{ $news->model_url }}" target="_blank"
                               class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold rounded-lg hover:scale-105 transition text-sm">
                                <i class="fas fa-external-link-alt"></i> View on HF
                            </a>
                            @endif
                            @if($news->demo_url)
                            <a href="{{ $news->demo_url }}" target="_blank"
                               class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-lg hover:scale-105 transition text-sm">
                                <i class="fas fa-play-circle"></i> Try Demo
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Article Content --}}
            <div class="glass-fusion rounded-2xl p-8 border border-white/30">
                <div class="prose prose-invert prose-lg max-w-none">
                    {!! \Illuminate\Support\Str::markdown($news->content) !!}
                </div>
            </div>

            {{-- Tags --}}
            @if($news->tags && count($news->tags) > 0)
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-lg font-bold text-white mb-4">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($news->tags as $tag)
                    <span class="px-3 py-1 bg-white/10 text-white/80 text-sm rounded-lg border border-white/20">
                        #{{ $tag }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Share Buttons --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-lg font-bold text-white mb-4">แชร์บทความนี้</h3>
                <div class="flex gap-3">
                    <button onclick="shareToTwitter()"
                            class="flex-1 px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fab fa-twitter"></i> Twitter
                    </button>
                    <button onclick="shareToFacebook()"
                            class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fab fa-facebook"></i> Facebook
                    </button>
                    <button onclick="shareToLinkedIn()"
                            class="flex-1 px-4 py-3 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fab fa-linkedin"></i> LinkedIn
                    </button>
                    <button onclick="copyLink()"
                            class="flex-1 px-4 py-3 glass-neu text-white hover:bg-white/20 font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fas fa-link"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Quick Actions --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    @if($news->model_url)
                    <a href="{{ $news->model_url }}" target="_blank"
                       class="w-full px-4 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold rounded-xl hover:scale-105 transition text-center text-sm block">
                        <i class="fas fa-external-link-alt"></i> View on Hugging Face
                    </a>
                    @endif
                    @if($news->paper_url)
                    <a href="{{ $news->paper_url }}" target="_blank"
                       class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-bold rounded-xl hover:scale-105 transition text-center text-sm block">
                        <i class="fas fa-file-pdf"></i> Read Research Paper
                    </a>
                    @endif
                    @if($news->demo_url)
                    <a href="{{ $news->demo_url }}" target="_blank"
                       class="w-full px-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition text-center text-sm block">
                        <i class="fas fa-play-circle"></i> Try Demo
                    </a>
                    @endif
                    <a href="{{ route('admin.ai-rental.cost-calculator') }}"
                       class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition text-center text-sm block">
                        <i class="fas fa-calculator"></i> Cost Calculator
                    </a>
                </div>
            </div>

            {{-- Related Models Link --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-lg font-bold text-white mb-4">Explore More</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.ai-rental.trending-models.index') }}"
                       class="flex items-center justify-between p-3 glass-neu rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition">
                        <span><i class="fas fa-fire text-orange-400"></i> Trending Models</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="{{ route('admin.ai-rental.setup-guide') }}"
                       class="flex items-center justify-between p-3 glass-neu rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition">
                        <span><i class="fas fa-book-open text-blue-400"></i> Setup Guide</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="{{ route('admin.ai-rental.news.index') }}"
                       class="flex items-center justify-between p-3 glass-neu rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition">
                        <span><i class="fas fa-newspaper text-purple-400"></i> All News</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            {{-- Difficulty Badge --}}
            @if(isset($news->metadata['difficulty']))
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-sm font-bold text-white/70 mb-2">Difficulty Level</h3>
                <div class="px-4 py-3 text-center
                    @if($news->metadata['difficulty'] === 'beginner') bg-green-500/20 border-green-500/30 text-green-300
                    @elseif($news->metadata['difficulty'] === 'intermediate') bg-yellow-500/20 border-yellow-500/30 text-yellow-300
                    @else bg-red-500/20 border-red-500/30 text-red-300
                    @endif
                    border rounded-xl font-bold">
                    @if($news->metadata['difficulty'] === 'beginner')
                        <i class="fas fa-seedling"></i> Beginner
                    @elseif($news->metadata['difficulty'] === 'intermediate')
                        <i class="fas fa-graduation-cap"></i> Intermediate
                    @else
                        <i class="fas fa-rocket"></i> Advanced
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
/* Prose Styling for Markdown Content */
.prose {
    color: rgba(255, 255, 255, 0.9);
}

.prose h1, .prose h2, .prose h3, .prose h4 {
    color: white;
    font-weight: bold;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose h1 { font-size: 2rem; }
.prose h2 { font-size: 1.75rem; }
.prose h3 { font-size: 1.5rem; }

.prose p {
    margin-bottom: 1rem;
    line-height: 1.8;
}

.prose code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
    color: #22d3ee;
}

.prose pre {
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    padding: 1.5rem;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.prose pre code {
    background: none;
    padding: 0;
    color: #86efac;
}

.prose ul, .prose ol {
    margin: 1rem 0;
    padding-left: 2rem;
}

.prose li {
    margin: 0.5rem 0;
}

.prose a {
    color: #22d3ee;
    text-decoration: underline;
}

.prose a:hover {
    color: #67e8f9;
}

.prose table {
    width: 100%;
    margin: 1.5rem 0;
    border-collapse: collapse;
}

.prose th, .prose td {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.prose th {
    background: rgba(255, 255, 255, 0.05);
    font-weight: bold;
}

.prose blockquote {
    border-left: 4px solid #22d3ee;
    padding-left: 1rem;
    margin: 1.5rem 0;
    color: rgba(255, 255, 255, 0.8);
    font-style: italic;
}
</style>
@endpush

@push('scripts')
<script>
function shareToTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('{{ $news->title }}');
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
}

function shareToFacebook() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareToLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=400');
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        alert('✅ Link copied to clipboard!');
    });
}
</script>
@endpush
@endsection
