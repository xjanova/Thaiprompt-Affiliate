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
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                {{ $page->title }}
            </h1>
            @if($page->updated_at)
                <p class="text-sm text-gray-600">
                    อัปเดตล่าสุด: {{ $page->updated_at->format('d F Y') }}
                </p>
            @endif
        </div>

        <!-- Page Content -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <article class="prose prose-lg max-w-none p-8 md:p-12">
                {!! $page->content !!}
            </article>
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="{{ route('home') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition font-medium">
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
        color: #374151;
    }

    .legal-document h1 {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #111827;
    }

    .legal-document h2 {
        font-size: 1.5rem;
        font-weight: bold;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .legal-document h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #374151;
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
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 2rem;
    }

    .legal-document .contact-info {
        background-color: #f9fafb;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
        border: 1px solid #e5e7eb;
    }

    .legal-document .acknowledgment {
        background-color: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 1.25rem;
        margin-top: 2rem;
        border-radius: 0.375rem;
    }

    .legal-document a {
        color: #2563eb;
        text-decoration: underline;
        text-decoration-color: #93c5fd;
        transition: all 0.2s;
    }

    .legal-document a:hover {
        color: #1d4ed8;
        text-decoration-color: #2563eb;
    }

    .legal-document strong {
        font-weight: 600;
        color: #1f2937;
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
