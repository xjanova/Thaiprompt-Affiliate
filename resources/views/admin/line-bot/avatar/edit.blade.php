@extends('layouts.admin')

@section('title', 'แก้ไข LINE Avatar')

@section('content')
<div class="min-h-screen py-6" x-data="{
    showDeleteModal: false
}">
    <!-- Stunning LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-10 shadow-2xl shadow-green-500/30">
        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-48 -translate-y-48 animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full translate-x-32 translate-y-32 animate-pulse" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 left-1/2 w-48 h-48 bg-white rounded-full -translate-x-24 -translate-y-24 animate-pulse" style="animation-delay: 0.3s"></div>
        </div>

        <!-- Floating Icons -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-10 right-20 animate-bounce" style="animation-delay: 0.2s">
                <i class="fas fa-edit text-white/20 text-7xl"></i>
            </div>
            <div class="absolute bottom-10 left-20 animate-bounce" style="animation-delay: 0.8s">
                <i class="fas fa-user-circle text-white/20 text-6xl"></i>
            </div>
        </div>

        <div class="relative">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-20 h-20 rounded-3xl bg-white/25 backdrop-blur-md flex items-center justify-center shadow-2xl border-2 border-white/30">
                        <i class="fas fa-pencil-alt text-white text-4xl drop-shadow-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-5xl font-black text-white mb-2 drop-shadow-lg tracking-tight">✏️ แก้ไข Avatar</h1>
                        <p class="text-white/95 text-xl font-medium">อัพเดทข้อมูลและการตั้งค่า Avatar</p>
                        <div class="mt-3 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-bold inline-flex items-center gap-2 border border-white/30">
                            <i class="fas fa-tag"></i>
                            <span>{{ $avatar->name }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = true"
                            class="px-6 py-4 bg-red-500/20 backdrop-blur-md border-2 border-red-400/40 text-white rounded-2xl hover:bg-red-500/30 transition-all duration-300 font-bold flex items-center gap-3 shadow-xl hover:scale-105">
                        <i class="fas fa-trash text-xl"></i>
                        <span class="text-lg">ลบ</span>
                    </button>
                    <a href="{{ route('admin.line-bot.avatar.index') }}"
                       class="px-8 py-4 bg-white/20 backdrop-blur-md border-2 border-white/40 text-white rounded-2xl hover:bg-white/30 transition-all duration-300 font-bold flex items-center gap-3 shadow-xl hover:scale-105">
                        <i class="fas fa-arrow-left text-xl"></i>
                        <span class="text-lg">กลับ</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-8 py-6 border-b-2 border-gray-200 dark:border-slate-600">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <i class="fas fa-cog text-[#00B900]"></i>
                        แก้ไขข้อมูล Avatar
                    </h2>
                </div>

                <form action="{{ route('admin.line-bot.avatars.update', $avatar->id) }}" method="POST" class="p-8 space-y-8">
                    @csrf
                    @method('PUT')

                    <!-- Avatar Name -->
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-tag text-[#00B900]"></i>
                            ชื่อ Avatar
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required value="{{ old('name', $avatar->name) }}"
                               class="w-full px-6 py-4 text-lg border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Avatar Type (Read-only display) -->
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-layer-group text-[#00B900]"></i>
                            ประเภท Avatar
                        </label>
                        <div class="p-6 border-2 border-gray-200 dark:border-slate-600 rounded-2xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center
                                    @if($avatar->type === 'image') bg-blue-100 dark:bg-blue-900/30
                                    @elseif($avatar->type === 'gif') bg-purple-100 dark:bg-purple-900/30
                                    @elseif($avatar->type === 'lottie') bg-orange-100 dark:bg-orange-900/30
                                    @else bg-indigo-100 dark:bg-indigo-900/30
                                    @endif">
                                    @if($avatar->type === 'image')
                                        <i class="fas fa-image text-blue-600 dark:text-blue-400 text-2xl"></i>
                                    @elseif($avatar->type === 'gif')
                                        <i class="fas fa-film text-purple-600 dark:text-purple-400 text-2xl"></i>
                                    @elseif($avatar->type === 'lottie')
                                        <i class="fas fa-play-circle text-orange-600 dark:text-orange-400 text-2xl"></i>
                                    @else
                                        <i class="fas fa-video text-indigo-600 dark:text-indigo-400 text-2xl"></i>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white capitalize">{{ $avatar->type }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        <i class="fas fa-lock mr-1"></i>ไม่สามารถเปลี่ยนประเภทได้หลังจากสร้างแล้ว
                                    </p>
                                </div>
                                <span class="px-4 py-2 rounded-xl text-sm font-bold
                                    @if($avatar->type === 'image') bg-blue-600 text-white
                                    @elseif($avatar->type === 'gif') bg-purple-600 text-white
                                    @elseif($avatar->type === 'lottie') bg-orange-600 text-white
                                    @else bg-indigo-600 text-white
                                    @endif">
                                    {{ strtoupper($avatar->type) }}
                                </span>
                            </div>
                        </div>
                        <input type="hidden" name="type" value="{{ $avatar->type }}">
                    </div>

                    <!-- Description -->
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                            <i class="fas fa-align-left text-[#00B900]"></i>
                            คำอธิบาย
                        </label>
                        <textarea name="description" rows="5"
                                  placeholder="อธิบายเกี่ยวกับ Avatar นี้..."
                                  class="w-full px-6 py-4 text-base border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">{{ old('description', $avatar->description) }}</textarea>
                    </div>

                    <!-- File Info (Read-only) -->
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-file text-[#00B900]"></i>
                            ข้อมูลไฟล์
                        </label>
                        <div class="p-6 border-2 border-gray-200 dark:border-slate-600 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-folder text-blue-600 dark:text-blue-400 text-xl"></i>
                                        <div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">แหล่งที่มา</p>
                                            <p class="font-bold text-gray-900 dark:text-white capitalize">{{ $avatar->source_type }}</p>
                                        </div>
                                    </div>
                                    @if($avatar->file_size)
                                        <div class="text-right">
                                            <p class="text-xs text-gray-600 dark:text-gray-400">ขนาดไฟล์</p>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ number_format($avatar->file_size / 1024, 2) }} KB</p>
                                        </div>
                                    @endif
                                </div>

                                @if($avatar->source_type === 'upload')
                                    <div class="pt-4 border-t border-gray-300 dark:border-slate-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">ตำแหน่งไฟล์:</p>
                                        <p class="font-mono text-sm text-gray-900 dark:text-white bg-white dark:bg-slate-700 px-4 py-2 rounded-lg break-all">
                                            {{ $avatar->file_path }}
                                        </p>
                                    </div>
                                @else
                                    <div class="pt-4 border-t border-gray-300 dark:border-slate-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">URL ลิงก์:</p>
                                        <a href="{{ $avatar->file_url }}" target="_blank"
                                           class="font-mono text-sm text-blue-600 dark:text-blue-400 hover:underline bg-white dark:bg-slate-700 px-4 py-2 rounded-lg break-all inline-flex items-center gap-2">
                                            {{ $avatar->file_url }}
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Status Toggles -->
                    <div class="space-y-4">
                        <!-- Active Status -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-2xl p-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <input type="checkbox" name="is_active" value="1" {{ $avatar->is_active ? 'checked' : '' }}
                                       class="mt-1 w-6 h-6 text-[#00B900] bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 rounded-lg focus:ring-4 focus:ring-green-500/20">
                                <div class="flex-1">
                                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 group-hover:text-[#00B900] dark:group-hover:text-[#00E600] transition-colors">
                                        <i class="fas fa-toggle-on mr-2"></i>เปิดใช้งาน Avatar นี้
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Avatar จะพร้อมใช้งานในระบบ</p>
                                </div>
                                <div class="px-4 py-2 rounded-full text-sm font-bold {{ $avatar->is_active ? 'bg-green-600 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                    {{ $avatar->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                </div>
                            </label>
                        </div>

                        <!-- Default Status -->
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-2xl p-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <input type="checkbox" name="is_default" value="1" {{ $avatar->is_default ? 'checked' : '' }}
                                       class="mt-1 w-6 h-6 text-yellow-600 bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 rounded-lg focus:ring-4 focus:ring-yellow-500/20">
                                <div class="flex-1">
                                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition-colors">
                                        <i class="fas fa-star mr-2"></i>ตั้งเป็น Avatar เริ่มต้น
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Avatar นี้จะถูกใช้เป็นค่าเริ่มต้นสำหรับ LINE Bot</p>
                                </div>
                                @if($avatar->is_default)
                                    <div class="px-4 py-2 bg-yellow-600 rounded-full text-sm font-bold text-white flex items-center gap-2">
                                        <i class="fas fa-crown"></i>
                                        <span>ค่าเริ่มต้น</span>
                                    </div>
                                @endif
                            </label>
                        </div>
                    </div>

                    <!-- Metadata Display -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 border-2 border-gray-200 dark:border-slate-600 rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            ข้อมูลเพิ่มเติม
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">สร้างโดย</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $avatar->creator->name ?? 'System' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">สร้างเมื่อ</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $avatar->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">อัพเดทล่าสุด</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $avatar->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ID</p>
                                <p class="font-mono font-semibold text-gray-900 dark:text-white">#{{ $avatar->id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-4 pt-6 border-t-2 border-gray-200 dark:border-slate-700">
                        <a href="{{ route('admin.line-bot.avatar.index') }}"
                           class="flex-1 px-8 py-5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-2xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-300 text-center text-lg font-bold flex items-center justify-center gap-3">
                            <i class="fas fa-times-circle text-xl"></i>
                            <span>ยกเลิก</span>
                        </a>
                        <button type="submit"
                                class="flex-1 px-8 py-5 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-2xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 text-center text-lg font-bold flex items-center justify-center gap-3">
                            <i class="fas fa-save text-xl"></i>
                            <span>บันทึกการเปลี่ยนแปลง</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview & Info Section -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Current Avatar Preview -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden sticky top-6">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-eye"></i>
                        Avatar ปัจจุบัน
                    </h3>
                </div>
                <div class="p-6">
                    <!-- Avatar Display -->
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-3xl flex items-center justify-center overflow-hidden mb-6 border-4 border-gray-300 dark:border-slate-600 shadow-inner">
                        @if($avatar->type === 'image' || $avatar->type === 'gif')
                            <img src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                                 alt="{{ $avatar->name }}"
                                 class="w-full h-full object-cover">
                        @elseif($avatar->type === 'lottie')
                            <div class="text-center p-8">
                                <i class="fas fa-play-circle text-purple-600 dark:text-purple-400 text-6xl mb-4"></i>
                                <p class="text-gray-600 dark:text-gray-400 font-bold">Lottie Animation</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">JSON Format</p>
                            </div>
                        @else
                            <video src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                                   class="w-full h-full object-cover"
                                   autoplay loop muted playsinline></video>
                        @endif
                    </div>

                    <!-- Status Badges -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-xl">
                            <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                <i class="fas fa-toggle-on"></i>สถานะ
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $avatar->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                {{ $avatar->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                            </span>
                        </div>

                        @if($avatar->is_default)
                            <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                                <span class="text-sm text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
                                    <i class="fas fa-crown"></i>ค่าเริ่มต้น
                                </span>
                                <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Quick Actions -->
                    <div class="space-y-3">
                        <a href="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}" target="_blank"
                           class="w-full px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all duration-300 text-center font-bold flex items-center justify-center gap-2">
                            <i class="fas fa-external-link-alt"></i>
                            <span>ดูไฟล์เต็มขนาด</span>
                        </a>

                        @if(!$avatar->is_default)
                            <form action="{{ route('admin.line-bot.avatars.set-default', $avatar->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-3 bg-gradient-to-r from-yellow-600 to-orange-600 text-white rounded-xl hover:shadow-xl transition-all duration-300 text-center font-bold flex items-center justify-center gap-2">
                                    <i class="fas fa-star"></i>
                                    <span>ตั้งเป็นค่าเริ่มต้น</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         @click.self="showDeleteModal = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all"
             @click.away="showDeleteModal = false">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-600 to-pink-600 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white">ยืนยันการลบ</h3>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <p class="text-gray-700 dark:text-gray-300 text-lg mb-4">
                    คุณแน่ใจหรือไม่ที่จะลบ Avatar นี้?
                </p>
                <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 mb-6">
                    <p class="text-red-800 dark:text-red-200 font-semibold flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <span>{{ $avatar->name }}</span>
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                        การดำเนินการนี้ไม่สามารถย้อนกลับได้
                    </p>
                </div>

                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                            class="flex-1 px-6 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-300 font-bold">
                        ยกเลิก
                    </button>
                    <form action="{{ route('admin.line-bot.avatars.destroy', $avatar->id) }}" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 text-white rounded-xl hover:shadow-2xl hover:shadow-red-500/50 transition-all duration-300 transform hover:-translate-y-1 font-bold flex items-center justify-center gap-2">
                            <i class="fas fa-trash"></i>
                            <span>ลบ Avatar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
@endsection
