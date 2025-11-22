@extends('layouts.admin-v3')

@section('title', isset($richMenu) ? 'Edit Rich Menu' : 'Create Rich Menu')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.rich-menu.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @isset($richMenu) Edit @else Create @endisset Rich Menu
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Design interactive menu for LINE chat</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ isset($richMenu) ? route('admin.line-bot.rich-menu.update', $richMenu->id) : route('admin.line-bot.rich-menu.store') }}" enctype="multipart/form-data">
        @csrf
        @isset($richMenu) @method('PUT') @endisset

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="glass-fusion rounded-2xl shadow-lg p-6 hover:scale-105 transition-transform border border-white/20 dark:border-white/10">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Menu Information</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Menu Name *</label>
                            <input type="text" name="name" value="{{ old('name', $richMenu->name ?? '') }}" required
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Chat Bar Text *</label>
                            <input type="text" name="chat_bar_text" value="{{ old('chat_bar_text', $richMenu->chat_bar_text ?? 'Menu') }}" required maxlength="14"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Text shown on chat bar (max 14 characters)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Menu Size</label>
                            <select name="size" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="full" {{ old('size', $richMenu->size ?? 'full') === 'full' ? 'selected' : '' }}>Full (2500x1686px)</option>
                                <option value="half" {{ old('size', $richMenu->size ?? '') === 'half' ? 'selected' : '' }}>Half (2500x843px)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Menu Image *</label>
                            <input type="file" name="menu_image" accept="image/*"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500"
                                {{ isset($richMenu) ? '' : 'required' }}>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Upload menu template image (2500x1686px or 2500x843px)</p>
                            @isset($richMenu)
                                @if($richMenu->menu_image_url)
                                    <img src="{{ $richMenu->menu_image_url }}" alt="Current menu" class="mt-2 max-w-xs rounded-xl border">
                                @endif
                            @endisset
                        </div>
                    </div>
                </div>

                <div class="glass-fusion rounded-2xl shadow-lg p-6 hover:scale-105 transition-transform border border-white/20 dark:border-white/10">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Menu Structure (JSON)</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Define clickable areas and actions. Use
                        <a href="https://developers.line.biz/en/docs/messaging-api/using-rich-menus/" target="_blank" class="text-purple-600 hover:underline">
                            LINE Rich Menu documentation
                        </a>
                        for reference.
                    </p>

                    <textarea name="menu_data" rows="15"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                        placeholder='{"areas": [{"bounds": {...}, "action": {...}}]}'
                        required>{{ old('menu_data', isset($richMenu) ? json_encode($richMenu->menu_data, JSON_PRETTY_PRINT) : '') }}</textarea>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl border border-purple-200 p-6 sticky top-6">
                    <h3 class="font-bold text-purple-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Rich Menu Guide
                    </h3>

                    <div class="space-y-3 text-sm text-purple-800">
                        <div class="p-3 glass-fusion rounded-xl border border-white/20 dark:border-white/10">
                            <p class="font-semibold mb-1">Image Size:</p>
                            <p class="text-xs">Full: 2500×1686px</p>
                            <p class="text-xs">Half: 2500×843px</p>
                        </div>

                        <div class="p-3 glass-fusion rounded-xl border border-white/20 dark:border-white/10">
                            <p class="font-semibold mb-1">Design Tools:</p>
                            <a href="https://www.figma.com/" target="_blank" class="text-xs text-purple-600 hover:underline block">• Figma</a>
                            <a href="https://www.canva.com/" target="_blank" class="text-xs text-purple-600 hover:underline block">• Canva</a>
                            <a href="https://www.photoshop.com/" target="_blank" class="text-xs text-purple-600 hover:underline block">• Photoshop</a>
                        </div>

                        <div class="p-3 glass-fusion rounded-xl border border-white/20 dark:border-white/10">
                            <p class="font-semibold mb-1">Action Types:</p>
                            <p class="text-xs">• postback</p>
                            <p class="text-xs">• message</p>
                            <p class="text-xs">• uri</p>
                            <p class="text-xs">• richmenuswitch</p>
                        </div>
                    </div>

                    <a href="https://developers.line.biz/en/docs/messaging-api/using-rich-menus/"
                       target="_blank"
                       class="mt-4 block w-full px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition text-center text-sm">
                        <i class="fas fa-book mr-2"></i>Full Documentation
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10 hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.line-bot.rich-menu.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to list
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition shadow-lg font-bold">
                    <i class="fas fa-save mr-2"></i>
                    @isset($richMenu) Update @else Create @endisset Menu
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
