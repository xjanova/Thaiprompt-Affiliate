@extends('layouts.admin-v3')

@section('title', isset($broadcast) ? 'Edit Broadcast' : 'Create Broadcast')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.broadcast.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @isset($broadcast) Edit @else Create @endisset Broadcast
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Send messages to multiple users</p>
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

    <form method="POST" action="{{ isset($broadcast) ? route('admin.line-bot.broadcast.update', $broadcast->id) : route('admin.line-bot.broadcast.store') }}">
        @csrf
        @isset($broadcast) @method('PUT') @endisset

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Broadcast Information</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Broadcast Name *</label>
                            <input type="text" name="name" value="{{ old('name', $broadcast->name ?? '') }}" required
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Target Audience *</label>
                            <select name="target_type" required
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500">
                                <option value="all" {{ old('target_type', $broadcast->target_type ?? '') === 'all' ? 'selected' : '' }}>All Users</option>
                                <option value="sellers" {{ old('target_type', $broadcast->target_type ?? '') === 'sellers' ? 'selected' : '' }}>Sellers Only</option>
                                <option value="buyers" {{ old('target_type', $broadcast->target_type ?? '') === 'buyers' ? 'selected' : '' }}>Buyers Only</option>
                                <option value="custom" {{ old('target_type', $broadcast->target_type ?? '') === 'custom' ? 'selected' : '' }}>Custom List</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Message Type</label>
                            <select name="message_type" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500">
                                <option value="text">Text Message</option>
                                <option value="flex">Flex Message (Select Template)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Message Content *</label>
                            <textarea name="message" rows="6" required
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500">{{ old('message', $broadcast->message ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Schedule</label>
                            <select name="schedule_type" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 mb-2">
                                <option value="now">Send Now</option>
                                <option value="scheduled">Schedule for Later</option>
                            </select>

                            <input type="datetime-local" name="scheduled_at"
                                value="{{ old('scheduled_at', isset($broadcast) && $broadcast->scheduled_at ? $broadcast->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-200 p-6 sticky top-6">
                    <h3 class="font-bold text-emerald-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Broadcast Tips
                    </h3>

                    <div class="space-y-3 text-sm text-emerald-800">
                        <div class="p-3 glass-fusion rounded-xl" border border-white/20 dark:border-white/10>
                            <p class="font-semibold mb-1">Best Practices:</p>
                            <ul class="text-xs space-y-1">
                                <li>• Keep messages concise</li>
                                <li>• Use emojis for engagement</li>
                                <li>• Include clear call-to-action</li>
                                <li>• Test before sending</li>
                            </ul>
                        </div>

                        <div class="p-3 glass-fusion rounded-xl" border border-white/20 dark:border-white/10>
                            <p class="font-semibold mb-1">Timing:</p>
                            <p class="text-xs">Send during active hours (9 AM - 9 PM) for better engagement</p>
                        </div>

                        <div class="p-3 glass-fusion rounded-xl" border border-white/20 dark:border-white/10>
                            <p class="font-semibold mb-1">Target Wisely:</p>
                            <p class="text-xs">Segment your audience for more relevant messaging</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.line-bot.broadcast.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to list
                </a>
                <div class="flex gap-3">
                    <button type="submit" name="action" value="draft"
                            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 transition font-bold">
                        <i class="fas fa-save mr-2"></i>Save as Draft
                    </button>
                    <button type="submit" name="action" value="send"
                            class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-700 hover:to-teal-700 transition shadow-lg font-bold">
                        <i class="fas fa-paper-plane mr-2"></i>
                        @isset($broadcast) Update @else Send @endisset Broadcast
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
