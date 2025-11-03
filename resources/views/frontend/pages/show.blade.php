@extends('layouts.app')

@section('title', $page->title)

@section('meta')
    @if(isset($page->meta_data['description']))
        <meta name="description" content="{{ $page->meta_data['description'] }}">
    @endif
    @if(isset($page->meta_data['keywords']))
        <meta name="keywords" content="{{ $page->meta_data['keywords'] }}">
    @endif
@endsection

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $page->title }}
            </h1>
            @if($page->updated_at)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    อัปเดตล่าสุด: {{ $page->updated_at->format('d F Y') }}
                </p>
            @endif
        </div>

        <!-- Page Content -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <article class="prose prose-lg dark:prose-invert max-w-none p-8">
                {!! $page->content !!}
            </article>
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="{{ route('home') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับหน้าแรก
            </a>
        </div>
    </div>
</div>

<style>
    /* Legal Document Styling */
    .legal-document {
        line-height: 1.8;
    }

    .legal-document h1 {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #1a202c;
    }

    .dark .legal-document h1 {
        color: #f7fafc;
    }

    .legal-document h2 {
        font-size: 1.5rem;
        font-weight: bold;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #2d3748;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.5rem;
    }

    .dark .legal-document h2 {
        color: #e2e8f0;
        border-bottom-color: #4a5568;
    }

    .legal-document h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #4a5568;
    }

    .dark .legal-document h3 {
        color: #cbd5e0;
    }

    .legal-document .section {
        margin-bottom: 2rem;
    }

    .legal-document ul,
    .legal-document ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }

    .legal-document li {
        margin-bottom: 0.5rem;
    }

    .legal-document p {
        margin-bottom: 1rem;
    }

    .legal-document .last-updated {
        color: #718096;
        font-size: 0.875rem;
        margin-bottom: 2rem;
    }

    .dark .legal-document .last-updated {
        color: #a0aec0;
    }

    .legal-document .contact-info {
        background-color: #f7fafc;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .dark .legal-document .contact-info {
        background-color: #2d3748;
    }

    .legal-document .acknowledgment {
        background-color: #ebf8ff;
        border-left: 4px solid #3182ce;
        padding: 1rem;
        margin-top: 2rem;
    }

    .dark .legal-document .acknowledgment {
        background-color: #2c5282;
        border-left-color: #63b3ed;
    }

    .legal-document a {
        color: #3182ce;
        text-decoration: underline;
    }

    .dark .legal-document a {
        color: #63b3ed;
    }

    .legal-document a:hover {
        color: #2c5282;
    }

    .dark .legal-document a:hover {
        color: #90cdf4;
    }

    .legal-document strong {
        font-weight: 600;
        color: #2d3748;
    }

    .dark .legal-document strong {
        color: #e2e8f0;
    }

    /* Responsive Typography */
    @media (max-width: 640px) {
        .legal-document h1 {
            font-size: 1.75rem;
        }

        .legal-document h2 {
            font-size: 1.25rem;
        }

        .legal-document h3 {
            font-size: 1.125rem;
        }
    }
</style>
@endsection
