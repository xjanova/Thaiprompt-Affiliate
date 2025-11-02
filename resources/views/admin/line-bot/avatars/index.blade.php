@extends('layouts.admin')

@section('title', 'Avatar Management')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{ showUploadModal: false, uploadType: 'image' }">
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-red-700 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Avatar Management</h1>
                <p class="text-amber-100">Manage avatars for chat widget</p>
            </div>
            <button @click="showUploadModal = true"
                    class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition">
                <i class="fas fa-upload mr-2"></i>Upload Avatar
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
            <p class="text-green-800"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse($avatars as $avatar)
            <div class="bg-white rounded-xl shadow-lg border overflow-hidden hover:shadow-xl transition">
                <div class="aspect-square bg-gray-100 flex items-center justify-center">
                    @if($avatar->type === 'image' || $avatar->type === 'gif')
                        <img src="{{ $avatar->avatar_url }}" alt="{{ $avatar->name }}" class="w-full h-full object-cover">
                    @elseif($avatar->type === 'lottie')
                        <div class="text-center">
                            <i class="fas fa-play-circle text-4xl text-purple-500"></i>
                            <p class="text-xs text-gray-500 mt-2">Lottie</p>
                        </div>
                    @else
                        <video src="{{ $avatar->avatar_url }}" class="w-full h-full object-cover" autoplay loop muted></video>
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $avatar->name }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs px-2 py-1 bg-orange-100 text-orange-800 rounded-full">{{ strtoupper($avatar->type) }}</span>
                        @if($avatar->is_active)
                            <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i></span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-6 bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="w-24 h-24 rounded-full bg-orange-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-user-circle text-orange-500 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">No Avatars Yet</h3>
                <p class="text-gray-600 mb-6">Upload your first avatar</p>
                <button @click="showUploadModal = true"
                        class="px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-xl">
                    <i class="fas fa-upload mr-2"></i>Upload Avatar
                </button>
            </div>
        @endforelse
    </div>

    <!-- Upload Modal -->
    <div x-show="showUploadModal" x-cloak @click.self="showUploadModal = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">Upload Avatar</h3>
                    <button @click="showUploadModal = false" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.line-bot.avatars.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Avatar Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 border rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Type</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" @click="uploadType = 'image'" :class="uploadType === 'image' ? 'bg-orange-500 text-white' : 'bg-gray-100'"
                                class="p-3 rounded-lg transition">
                            <i class="fas fa-image text-xl"></i>
                            <p class="text-xs mt-1">Image</p>
                        </button>
                        <button type="button" @click="uploadType = 'gif'" :class="uploadType === 'gif' ? 'bg-orange-500 text-white' : 'bg-gray-100'"
                                class="p-3 rounded-lg transition">
                            <i class="fas fa-file-image text-xl"></i>
                            <p class="text-xs mt-1">GIF</p>
                        </button>
                        <button type="button" @click="uploadType = 'lottie'" :class="uploadType === 'lottie' ? 'bg-orange-500 text-white' : 'bg-gray-100'"
                                class="p-3 rounded-lg transition">
                            <i class="fas fa-code text-xl"></i>
                            <p class="text-xs mt-1">Lottie</p>
                        </button>
                        <button type="button" @click="uploadType = 'video'" :class="uploadType === 'video' ? 'bg-orange-500 text-white' : 'bg-gray-100'"
                                class="p-3 rounded-lg transition">
                            <i class="fas fa-video text-xl"></i>
                            <p class="text-xs mt-1">Video</p>
                        </button>
                    </div>
                    <input type="hidden" name="type" x-model="uploadType">
                </div>

                <div x-show="uploadType !== 'lottie'">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload File</label>
                    <input type="file" name="avatar_file" class="w-full px-4 py-3 border rounded-lg">
                </div>

                <div x-show="uploadType === 'lottie'" x-cloak>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Lottie URL</label>
                    <input type="url" name="lottie_url" class="w-full px-4 py-3 border rounded-lg">
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showUploadModal = false"
                            class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-lg hover:from-orange-600 hover:to-red-700 transition font-semibold">
                        <i class="fas fa-upload mr-2"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
@endsection
