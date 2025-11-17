@extends('layouts.admin-v3')

@section('title', 'Rich Menu Management')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-700 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Rich Menu Management</h1>
                <p class="text-purple-100">Create interactive menus for LINE chat</p>
            </div>
            <a href="{{ route('admin.line-bot.rich-menu.create') }}"
               class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:glass-fusion transition">
                <i class="fas fa-plus mr-2"></i>New Rich Menu
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
            <p class="text-green-800"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($richMenus as $menu)
            <div class="glass-fusion rounded-2xl shadow-lg border overflow-hidden" border border-white/20 dark:border-white/10>
                @if($menu->menu_image_url)
                    <img src="{{ $menu->menu_image_url }}" alt="{{ $menu->name }}" class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-purple-300"></i>
                    </div>
                @endif

                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-bold text-gray-900">{{ $menu->name }}</h3>
                        @if($menu->is_default)
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                <i class="fas fa-check-circle mr-1"></i>Default
                            </span>
                        @endif
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $menu->chat_bar_text }}</p>

                    <div class="flex gap-2">
                        @if(!$menu->is_default)
                            <form method="POST" action="{{ route('admin.line-bot.rich-menu.set-default', $menu->id) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-100 text-green-700 rounded-xl hover:bg-green-200 transition text-sm font-semibold">
                                    <i class="fas fa-star mr-1"></i>Set Default
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.line-bot.rich-menu.edit', $menu->id) }}"
                           class="flex-1 px-4 py-2 bg-purple-100 text-purple-700 rounded-xl hover:bg-purple-200 transition text-sm font-semibold text-center">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 glass-fusion rounded-2xl shadow-lg p-12 text-center" border border-white/20 dark:border-white/10>
                <div class="w-24 h-24 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-th-large text-purple-500 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">No Rich Menus Yet</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first rich menu</p>
                <a href="{{ route('admin.line-bot.rich-menu.create') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl">
                    <i class="fas fa-plus mr-2"></i>Create First Menu
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
