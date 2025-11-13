@props([
    'name' => 'images',
    'multiple' => false,
    'maxFiles' => 10,
    'maxSize' => 5, // MB
    'existingImages' => [],
    'required' => false,
])

<div x-data="imageUploader({
    name: '{{ $name }}',
    multiple: {{ $multiple ? 'true' : 'false' }},
    maxFiles: {{ $maxFiles }},
    maxSize: {{ $maxSize }},
    existingImages: @js($existingImages),
    required: {{ $required ? 'true' : 'false' }}
})" class="image-uploader">

    <!-- Upload Area -->
    <div @dragover.prevent="dragOver = true"
         @dragleave.prevent="dragOver = false"
         @drop.prevent="handleDrop($event)"
         :class="{'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': dragOver}"
         class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl transition-all duration-200 hover:border-indigo-400 dark:hover:border-indigo-500">

        <input type="file"
               :name="multiple ? name + '[]' : name"
               :multiple="multiple"
               accept="image/jpeg,image/png,image/gif,image/webp"
               @change="handleFileSelect($event)"
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
               :required="required && files.length === 0 && existingImages.length === 0">

        <div class="p-8 text-center pointer-events-none">
            <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>

            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-semibold text-indigo-600 dark:text-indigo-400">คลิกเพื่ออัปโหลด</span>
                หรือลากไฟล์มาวางที่นี่
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                รองรับ JPG, PNG, GIF, WebP สูงสุด {{ $maxSize }}MB
                <template x-if="multiple">
                    <span>(อัปโหลดได้สูงสุด {{ $maxFiles }} ไฟล์)</span>
                </template>
            </p>
        </div>
    </div>

    <!-- Error Messages -->
    <template x-if="errors.length > 0">
        <div class="mt-3 space-y-1">
            <template x-for="error in errors" :key="error">
                <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="error"></span>
                </p>
            </template>
        </div>
    </template>

    <!-- Preview Grid -->
    <template x-if="files.length > 0 || existingImages.length > 0">
        <div class="mt-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                รูปภาพที่เลือก (<span x-text="files.length + existingImages.length"></span>)
            </h4>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <!-- Existing Images -->
                <template x-for="(image, index) in existingImages" :key="'existing-' + index">
                    <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-400 transition-all">
                        <img :src="image.url" :alt="image.name || 'Image'"
                             class="w-full h-full object-cover">

                        <!-- Overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                            <button type="button"
                                    @click="removeExistingImage(index)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity bg-red-600 hover:bg-red-700 text-white rounded-full p-2 transform hover:scale-110">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Badge for existing -->
                        <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                            อัปโหลดแล้ว
                        </div>
                    </div>
                </template>

                <!-- New Files -->
                <template x-for="(file, index) in files" :key="'new-' + index">
                    <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 border-2 border-indigo-500 dark:border-indigo-400 hover:border-indigo-600 dark:hover:border-indigo-300 transition-all">
                        <img :src="file.preview" :alt="file.name"
                             class="w-full h-full object-cover">

                        <!-- Overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                            <button type="button"
                                    @click="removeFile(index)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity bg-red-600 hover:bg-red-700 text-white rounded-full p-2 transform hover:scale-110">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- File info -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                            <p class="text-xs text-white truncate" x-text="file.name"></p>
                            <p class="text-xs text-gray-300" x-text="formatFileSize(file.size)"></p>
                        </div>

                        <!-- Badge for new -->
                        <div class="absolute top-2 right-2 bg-indigo-500 text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg animate-pulse">
                            ใหม่
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Hidden inputs for deleted existing images -->
    <template x-for="imageId in deletedImageIds" :key="imageId">
        <input type="hidden" name="deleted_images[]" :value="imageId">
    </template>
</div>

<script>
function imageUploader(config) {
    return {
        name: config.name,
        multiple: config.multiple,
        maxFiles: config.maxFiles,
        maxSize: config.maxSize * 1024 * 1024, // Convert to bytes
        maxSizeMB: config.maxSize, // Keep original MB value for error messages
        files: [],
        existingImages: config.existingImages || [],
        deletedImageIds: [],
        errors: [],
        dragOver: false,
        required: config.required,

        init() {
            this.$watch('files', () => {
                this.validateFiles();
            });
        },

        handleFileSelect(event) {
            const selectedFiles = Array.from(event.target.files);
            this.addFiles(selectedFiles, event.target);
        },

        handleDrop(event) {
            this.dragOver = false;
            const droppedFiles = Array.from(event.dataTransfer.files);
            const input = event.currentTarget.querySelector('input[type="file"]');
            this.addFiles(droppedFiles, input);
        },

        addFiles(newFiles, inputElement = null) {
            this.errors = [];

            // Filter only image files
            const imageFiles = newFiles.filter(file => file.type.startsWith('image/'));

            if (imageFiles.length !== newFiles.length) {
                this.errors.push('บางไฟล์ไม่ใช่รูปภาพและถูกข้าม');
            }

            // Check max files limit
            const totalFiles = this.files.length + this.existingImages.length + imageFiles.length;
            if (!this.multiple && totalFiles > 1) {
                this.errors.push('เลือกได้เพียง 1 ไฟล์เท่านั้น');
                return;
            }

            if (totalFiles > this.maxFiles) {
                this.errors.push(`สามารถอัปโหลดได้สูงสุด ${this.maxFiles} ไฟล์`);
                return;
            }

            // Validate files first
            const validFiles = [];
            imageFiles.forEach(file => {
                if (file.size > this.maxSize) {
                    this.errors.push(`${file.name}: ขนาดไฟล์เกิน ${this.maxSizeMB}MB`);
                } else {
                    validFiles.push(file);
                }
            });

            // If no input element, just show previews
            if (!inputElement) {
                validFiles.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.files.push({
                            file: file,
                            name: file.name,
                            size: file.size,
                            preview: e.target.result
                        });
                    };
                    reader.readAsDataURL(file);
                });
                return;
            }

            // Use DataTransfer to store actual files in input element
            const dataTransfer = new DataTransfer();

            // Add existing files from input
            if (inputElement.files) {
                Array.from(inputElement.files).forEach(file => {
                    dataTransfer.items.add(file);
                });
            }

            // Add new valid files
            validFiles.forEach(file => {
                if (this.multiple || dataTransfer.files.length === 0) {
                    dataTransfer.items.add(file);
                }
            });

            // Update input element with all files
            inputElement.files = dataTransfer.files;

            // Create previews
            validFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.files.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        preview: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            });
        },

        removeFile(index) {
            const removed = this.files.splice(index, 1)[0];
            this.errors = [];

            // Update input element to remove the file
            this.updateInputFiles();
        },

        updateInputFiles() {
            // Find the input element
            const input = this.$el.querySelector('input[type="file"]');
            if (!input) return;

            // Create new DataTransfer with remaining files
            const dataTransfer = new DataTransfer();
            this.files.forEach(item => {
                if (item.file) {
                    dataTransfer.items.add(item.file);
                }
            });

            // Update input
            input.files = dataTransfer.files;
        },

        removeExistingImage(index) {
            const removed = this.existingImages.splice(index, 1)[0];
            if (removed.id) {
                this.deletedImageIds.push(removed.id);
            }
        },

        validateFiles() {
            // You can add additional validation here
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    }
}
</script>

<style>
.image-uploader {
    @apply w-full;
}
</style>
