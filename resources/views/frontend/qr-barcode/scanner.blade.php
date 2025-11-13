@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 dark:from-gray-900 dark:via-indigo-900 dark:to-purple-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12 animate-fade-in">
            <h1 class="text-5xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 mb-4">
                <i class="fas fa-camera mr-3"></i>QR Code Scanner
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                สแกน QR Code และ Barcode ด้วยกล้อง หรืออัพโหลดรูปภาพ
            </p>
            <div class="mt-6">
                <a href="{{ route('qr-barcode.index') }}" class="inline-flex items-center px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>กลับไปสร้าง QR Code
                </a>
            </div>
        </div>

        <div x-data="qrScanner()">
            <!-- Scanner Mode Selection -->
            <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
                <div class="flex flex-col md:flex-row gap-4">
                    <button @click="scanMode = 'camera'"
                            :class="scanMode === 'camera' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg shadow-blue-500/50' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                            class="flex-1 py-6 rounded-2xl font-bold text-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-camera text-3xl mb-2"></i>
                        <div>สแกนด้วยกล้อง</div>
                        <div class="text-sm font-normal mt-1">เปิดกล้องและสแกน</div>
                    </button>

                    <button @click="scanMode = 'upload'"
                            :class="scanMode === 'upload' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg shadow-purple-500/50' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                            class="flex-1 py-6 rounded-2xl font-bold text-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-upload text-3xl mb-2"></i>
                        <div>อัพโหลดรูปภาพ</div>
                        <div class="text-sm font-normal mt-1">เลือกไฟล์จากเครื่อง</div>
                    </button>
                </div>
            </div>

            <!-- Camera Scanner -->
            <div x-show="scanMode === 'camera'" x-transition class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
                <h2 class="text-2xl font-bold text-white flex items-center mb-6">
                    <i class="fas fa-camera mr-3 text-blue-400"></i>สแกนด้วยกล้อง
                </h2>

                <!-- Camera View -->
                <div class="relative bg-black rounded-2xl overflow-hidden mb-6" style="aspect-ratio: 16/9;">
                    <video id="cameraVideo" class="w-full h-full object-cover" x-show="!scannedResult" autoplay playsinline></video>
                    <canvas id="scannerCanvas" class="absolute inset-0 w-full h-full"></canvas>

                    <!-- Loading Overlay -->
                    <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-black/50">
                        <div class="text-white text-center">
                            <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                            <p>กำลังเปิดกล้อง...</p>
                        </div>
                    </div>

                    <!-- Scan Frame -->
                    <div x-show="!scannedResult && !isLoading" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="border-4 border-cyan-400 rounded-2xl" style="width: 300px; height: 300px; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);">
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-cyan-400 rounded-tl-xl"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-cyan-400 rounded-tr-xl"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-cyan-400 rounded-bl-xl"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-cyan-400 rounded-br-xl"></div>
                        </div>
                    </div>
                </div>

                <!-- Camera Controls -->
                <div class="flex gap-4 justify-center">
                    <button @click="startCamera()" x-show="!cameraActive" class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-green-500/50 transition-all duration-300">
                        <i class="fas fa-play mr-2"></i>เปิดกล้อง
                    </button>
                    <button @click="stopCamera()" x-show="cameraActive" class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-red-500/50 transition-all duration-300">
                        <i class="fas fa-stop mr-2"></i>ปิดกล้อง
                    </button>
                    <button @click="switchCamera()" x-show="cameraActive && cameras.length > 1" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300">
                        <i class="fas fa-sync-alt mr-2"></i>สลับกล้อง
                    </button>
                </div>
            </div>

            <!-- Upload Scanner -->
            <div x-show="scanMode === 'upload'" x-transition class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
                <h2 class="text-2xl font-bold text-white flex items-center mb-6">
                    <i class="fas fa-upload mr-3 text-purple-400"></i>อัพโหลดรูปภาพ
                </h2>

                <!-- Upload Area -->
                <div @click="$refs.fileInput.click()"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)"
                     :class="isDragging ? 'border-cyan-400 bg-cyan-400/10' : 'border-white/30'"
                     class="border-4 border-dashed rounded-2xl p-12 text-center cursor-pointer transition-all duration-300 hover:border-cyan-400 hover:bg-white/5">

                    <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" accept="image/*" class="hidden">

                    <div x-show="!uploadPreview">
                        <i class="fas fa-cloud-upload-alt text-6xl text-cyan-400 mb-4"></i>
                        <p class="text-white text-xl font-semibold mb-2">คลิกหรือลากรูปภาพมาวางที่นี่</p>
                        <p class="text-gray-300">รองรับไฟล์ PNG, JPG, JPEG</p>
                    </div>

                    <div x-show="uploadPreview">
                        <img :src="uploadPreview" class="max-w-md mx-auto rounded-xl shadow-2xl mb-4">
                        <button @click.stop="clearUpload()" class="px-6 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all">
                            <i class="fas fa-times mr-2"></i>ลบรูปภาพ
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scan Result -->
            <div x-show="scannedResult" x-transition class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8">
                <h2 class="text-2xl font-bold text-white flex items-center mb-6">
                    <i class="fas fa-check-circle mr-3 text-green-400"></i>ผลการสแกน
                </h2>

                <div class="bg-white/5 rounded-2xl p-6 mb-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-qrcode text-4xl text-cyan-400"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-sm text-gray-400">ประเภท:</span>
                                <span class="px-3 py-1 bg-cyan-500 text-white text-sm font-semibold rounded-full" x-text="resultType"></span>
                            </div>
                            <div class="bg-gray-900 rounded-xl p-4 font-mono text-sm text-green-400 break-all max-h-48 overflow-y-auto">
                                <span x-text="scannedResult"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3">
                        <button @click="copyToClipboard(scannedResult)" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-blue-500/50 transition-all duration-300">
                            <i class="fas fa-copy mr-2"></i>คัดลอก
                        </button>
                        <button @click="openLink(scannedResult)" x-show="resultType === 'url'" class="px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-green-500/50 transition-all duration-300">
                            <i class="fas fa-external-link-alt mr-2"></i>เปิดลิงก์
                        </button>
                        <button @click="resetScanner()" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all duration-300">
                            <i class="fas fa-redo mr-2"></i>สแกนใหม่
                        </button>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="grid md:grid-cols-3 gap-6 mt-12">
                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-2xl border border-white/20 p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="text-4xl mb-4">📱</div>
                    <h3 class="text-xl font-bold text-white mb-2">รองรับทุกอุปกรณ์</h3>
                    <p class="text-gray-300">ใช้งานได้ทั้งมือถือ แท็บเล็ต และคอมพิวเตอร์</p>
                </div>

                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-2xl border border-white/20 p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="text-xl font-bold text-white mb-2">สแกนไว</h3>
                    <p class="text-gray-300">ตรวจจับและถอดรหัส QR Code ได้อย่างรวดเร็ว</p>
                </div>

                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-2xl border border-white/20 p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="text-4xl mb-4">🔒</div>
                    <h3 class="text-xl font-bold text-white mb-2">ปลอดภัย</h3>
                    <p class="text-gray-300">ประมวลผลทั้งหมดบนเครื่องของคุณ ไม่ส่งข้อมูลไปเซิร์ฟเวอร์</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@latest/umd/index.min.js"></script>
<script src="{{ asset('js/qr-scanner.js') }}"></script>
@endpush
@endsection
