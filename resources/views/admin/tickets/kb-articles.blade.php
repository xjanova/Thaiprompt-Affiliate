@extends('layouts.admin')

@section('title', 'Knowledge Base Articles')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-book mr-3"></i>
                    Knowledge Base Articles
                </h2>
                <p class="text-teal-100 text-lg">
                    จัดการบทความคู่มือและเอกสารช่วยเหลือ
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
                <a href="{{ route('admin.tickets.kb-articles.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-white text-teal-600 hover:bg-teal-50 rounded-xl font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Create Article
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Articles Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-teal-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Total Articles</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $articles->total() }}</p>
                </div>
                <div class="bg-teal-100 dark:bg-teal-900/30 p-4 rounded-lg">
                    <i class="fas fa-file-alt text-2xl text-teal-600 dark:text-teal-400"></i>
                </div>
            </div>
        </div>

        <!-- Public Articles Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Public Articles</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $articles->where('is_public', true)->count() }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg">
                    <i class="fas fa-globe text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Total Views Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Total Views</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $articles->sum('view_count') }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-lg">
                    <i class="fas fa-eye text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <!-- Helpful Votes Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Helpful Votes</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $articles->sum('helpful_count') }}</p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg">
                    <i class="fas fa-thumbs-up text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Title
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Author
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Views
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Helpful
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Visibility
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Last Updated
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($articles as $article)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ Str::limit($article->title, 50) }}
                                </div>
                                @if($article->tags && count($article->tags) > 0)
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @foreach(array_slice($article->tags, 0, 3) as $tag)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">
                                                <i class="fas fa-tag mr-1 text-xs"></i>
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                        @if(count($article->tags) > 3)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                +{{ count($article->tags) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                <i class="fas fa-folder mr-1"></i>
                                {{ $article->category->name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-teal-400 to-cyan-600 flex items-center justify-center text-white font-semibold">
                                        {{ substr($article->author->name ?? 'N', 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $article->author->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-eye text-blue-500 mr-2"></i>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($article->view_count) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($article->helpful_count > 0)
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <i class="fas fa-thumbs-up mr-1"></i>
                                        {{ $article->helpful_count }}
                                    </span>
                                </div>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('admin.tickets.kb-articles.toggle', $article->id) }}"
                                  method="POST"
                                  class="inline-block">
                                @csrf
                                @if($article->is_public)
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 transition-all">
                                        <i class="fas fa-globe mr-1"></i>
                                        Public
                                    </button>
                                @else
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-all">
                                        <i class="fas fa-lock mr-1"></i>
                                        Private
                                    </button>
                                @endif
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex items-center">
                                <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                {{ $article->updated_at->format('d M Y') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.tickets.kb-articles.edit', $article->id) }}"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-600">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.tickets.kb-articles.destroy', $article->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this article?')"
                                      class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-book text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No articles yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Create your first knowledge base article</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($articles->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
