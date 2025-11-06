@props(['type' => 'id_card', 'label' => 'ถ่ายรูปบัตร'])

<div x-data="kycCameraCapture()" x-init="$watch('cameraMode', value => { if(!value) destroy() })" class="mb-6">
    <!-- Label -->
    <label class="block text-sm font-medium text-gray-700 mb-2">
        <i class="fas fa-camera mr-1"></i>
        {{ $label }} <span class="text-red-500">*</span>
    </label>

    <!-- Error Alert -->
    <div x-show="error" x-cloak class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <span x-text="error"></span>
    </div>

    <!-- Camera Mode Button -->
    <div class="flex gap-2 mb-3">
        <button type="button"
                @click="startCamera()"
                x-show="!cameraMode && !capturedImage"
                class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
            <i class="fas fa-camera"></i>
            <span>เปิดกล้องถ่ายรูป</span>
        </button>

        <button type="button"
                @click="$refs.fileInput.click()"
                x-show="!cameraMode && !capturedImage"
                class="flex-1 px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
            <i class="fas fa-upload"></i>
            <span>เลือกจากคลัง</span>
        </button>
    </div>

    <!-- Camera View -->
    <div x-show="cameraMode && !capturedImage" x-cloak class="relative bg-black rounded-lg overflow-hidden">
        <!-- Video Stream -->
        <video x-ref="video"
               class="w-full h-auto"
               autoplay
               playsinline
               muted></video>

        <!-- Card Frame Overlay -->
        <div class="absolute inset-0 pointer-events-none">
            <!-- Dark overlay with cutout -->
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>

            <!-- Card frame in center -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-4/5 max-w-md aspect-[1.6/1]">
                <!-- Frame border -->
                <div class="absolute inset-0 border-4 border-white rounded-lg shadow-lg"></div>

                <!-- Corner guides -->
                <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-green-400 rounded-tl-lg"></div>
                <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-green-400 rounded-tr-lg"></div>
                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-green-400 rounded-bl-lg"></div>
                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-green-400 rounded-br-lg"></div>

                <!-- Instructions -->
                <div class="absolute -top-16 left-1/2 transform -translate-x-1/2 text-center">
                    <p class="text-white text-sm font-medium bg-black bg-opacity-70 px-4 py-2 rounded-lg">
                        <i class="fas fa-hand-point-down mr-2"></i>
                        วางบัตรให้อยู่ในกรอบสี่เหลี่ยม
                    </p>
                </div>
            </div>
        </div>

        <!-- Camera Controls -->
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black to-transparent">
            <div class="flex items-center justify-between">
                <!-- Cancel -->
                <button type="button"
                        @click="stopCamera()"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition">
                    <i class="fas fa-times mr-2"></i>ยกเลิก
                </button>

                <!-- Capture Button -->
                <button type="button"
                        @click="capturePhoto()"
                        class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition transform">
                    <div class="w-14 h-14 bg-red-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-camera text-white text-xl"></i>
                    </div>
                </button>

                <!-- Switch Camera -->
                <button type="button"
                        @click="switchCamera()"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition">
                    <i class="fas fa-sync-alt mr-2"></i>หมุนกล้อง
                </button>
            </div>
        </div>

        <!-- Tips -->
        <div class="absolute top-4 left-4 right-4">
            <div class="bg-yellow-400 text-yellow-900 px-3 py-2 rounded-lg text-xs flex items-start gap-2">
                <i class="fas fa-lightbulb mt-0.5"></i>
                <div>
                    <p class="font-semibold mb-1">เคล็ดลับ:</p>
                    <ul class="space-y-0.5">
                        <li>• ถ่ายในที่แสงสว่างเพียงพอ</li>
                        <li>• วางบัตรเรียบ ไม่บิดเบี้ยว</li>
                        <li>• ข้อความต้องอ่านได้ชัดเจน</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Captured Image Preview -->
    <div x-show="capturedImage" x-cloak class="space-y-3">
        <div class="border-2 border-green-500 rounded-lg p-2 bg-green-50">
            <img :src="capturedImage" class="w-full h-auto rounded" alt="Captured">
        </div>

        <div class="flex gap-2">
            <button type="button"
                    @click="retakePhoto()"
                    class="flex-1 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition">
                <i class="fas fa-redo mr-2"></i>ถ่ายใหม่
            </button>

            <button type="button"
                    @click="capturedImage = null"
                    class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <i class="fas fa-check mr-2"></i>ใช้รูปนี้
            </button>
        </div>
    </div>

    <!-- Hidden canvas for capture -->
    <canvas x-ref="canvas" class="hidden"></canvas>

    <!-- Hidden file input (fallback) -->
    <input type="file"
           x-ref="fileInput"
           accept="image/jpeg,image/jpg,image/png"
           class="hidden">
</div>
