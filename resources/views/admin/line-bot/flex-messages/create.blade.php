@extends('layouts.admin')

@section('title', isset($template) ? 'Edit Flex Message' : 'Create Flex Message')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.line-bot.flex.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @isset($template) Edit @else Create @endisset Flex Message Template
                </h1>
                <p class="text-sm text-gray-600">Design beautiful interactive messages for LINE</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ isset($template) ? route('admin.line-bot.flex.update', $template->id) : route('admin.line-bot.flex.store') }}">
        @csrf
        @isset($template) @method('PUT') @endisset

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Basic Information</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Template Name *</label>
                            <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-pink-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-pink-500">
                                <option value="welcome" {{ old('category', $template->category ?? '') === 'welcome' ? 'selected' : '' }}>Welcome</option>
                                <option value="promotion" {{ old('category', $template->category ?? '') === 'promotion' ? 'selected' : '' }}>Promotion</option>
                                <option value="product" {{ old('category', $template->category ?? '') === 'product' ? 'selected' : '' }}>Product</option>
                                <option value="notification" {{ old('category', $template->category ?? '') === 'notification' ? 'selected' : '' }}>Notification</option>
                                <option value="other" {{ old('category', $template->category ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-pink-500">{{ old('description', $template->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Flex JSON Editor -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Flex Message JSON</h3>
                        <a href="https://developers.line.biz/flex-simulator/" target="_blank"
                           class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>Flex Simulator
                        </a>
                    </div>

                    <textarea name="flex_content" id="flex-json" rows="20"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-pink-500 font-mono text-sm"
                        placeholder='{"type": "bubble", "body": {...}}'
                        required>{{ old('flex_content', isset($template) ? json_encode($template->flex_content, JSON_PRETTY_PRINT) : '') }}</textarea>

                    <p class="text-xs text-gray-500 mt-2">
                        Paste your Flex Message JSON from the
                        <a href="https://developers.line.biz/flex-simulator/" target="_blank" class="text-blue-600 hover:underline">LINE Flex Message Simulator</a>
                    </p>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Guide -->
                <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-2xl border border-pink-200 p-6 sticky top-6">
                    <h3 class="font-bold text-pink-900 mb-4">
                        <i class="fas fa-lightbulb mr-2"></i>Quick Guide
                    </h3>
                    <ol class="space-y-3 text-sm text-pink-800">
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold">1</span>
                            <span>Visit LINE Flex Message Simulator</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold">2</span>
                            <span>Design your message visually</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold">3</span>
                            <span>Copy the JSON code</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold">4</span>
                            <span>Paste it in the JSON editor</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold">5</span>
                            <span>Save and test your template</span>
                        </li>
                    </ol>
                </div>

                <!-- Example Templates -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 mb-4">
                        <i class="fas fa-star mr-2 text-yellow-500"></i>Seed Templates
                    </h3>
                    <p class="text-sm text-gray-600 mb-3">Check out our pre-made templates for inspiration</p>
                    <a href="{{ route('admin.line-bot.flex.index') }}" class="text-sm text-pink-600 hover:underline">
                        View seed templates <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.line-bot.flex.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to list
                    </a>
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-xl hover:from-pink-700 hover:to-rose-700 transition shadow-lg font-bold">
                        <i class="fas fa-save mr-2"></i>
                        @isset($template) Update @else Create @endisset Template
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
