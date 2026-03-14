/**
 * ตลาดสดไทยพร๊อม - Fresh Market JavaScript
 *
 * Alpine.js components สำหรับหน้าเว็บตลาดสด
 * รวม: Geolocation, Search, Map, Gallery, Order
 */

import Alpine from 'alpinejs';

// ===== Geolocation Helper =====
Alpine.data('geolocation', () => ({
    lat: null,
    lng: null,
    loading: false,
    error: null,
    permissionDenied: false,

    /**
     * ขอพิกัดจาก Browser
     */
    async getLocation() {
        this.loading = true;
        this.error = null;

        if (!navigator.geolocation) {
            this.error = 'เบราว์เซอร์ไม่รองรับ GPS';
            this.loading = false;
            return;
        }

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000, // Cache 5 นาที
                });
            });

            this.lat = position.coords.latitude;
            this.lng = position.coords.longitude;
            this.loading = false;

            // แจ้ง event ให้ components อื่นรู้
            this.$dispatch('location-updated', {
                lat: this.lat,
                lng: this.lng,
            });
        } catch (err) {
            this.loading = false;
            if (err.code === 1) {
                this.permissionDenied = true;
                this.error = 'กรุณาอนุญาตการเข้าถึงตำแหน่ง';
            } else if (err.code === 2) {
                this.error = 'ไม่สามารถระบุตำแหน่งได้';
            } else {
                this.error = 'หมดเวลาในการระบุตำแหน่ง';
            }
        }
    },
}));

// ===== Search Component =====
Alpine.data('freshMarketSearch', () => ({
    query: '',
    category: '',
    radius: 10,
    minPrice: null,
    maxPrice: null,
    sortBy: 'distance',
    results: [],
    loading: false,
    lat: null,
    lng: null,

    init() {
        // รับพิกัดจาก geolocation component
        this.$watch('lat', () => {
            if (this.lat && this.lng) {
                this.search();
            }
        });
    },

    /**
     * ค้นหาสินค้า
     */
    async search() {
        this.loading = true;

        const params = new URLSearchParams({
            ...(this.query && { q: this.query }),
            ...(this.category && { category: this.category }),
            ...(this.lat && { lat: this.lat }),
            ...(this.lng && { lng: this.lng }),
            radius: this.radius,
            ...(this.minPrice && { min_price: this.minPrice }),
            ...(this.maxPrice && { max_price: this.maxPrice }),
            sort: this.sortBy,
        });

        try {
            const response = await fetch(`/taladsod/api/listings?${params}`);
            const data = await response.json();
            this.results = data.data || [];
        } catch (err) {
            console.error('ค้นหาล้มเหลว:', err);
        } finally {
            this.loading = false;
        }
    },

    /**
     * ค้นหาสินค้าใกล้ตัว
     */
    async searchNearby() {
        if (!this.lat || !this.lng) return;

        this.loading = true;

        try {
            const response = await fetch(
                `/taladsod/api/nearby?lat=${this.lat}&lng=${this.lng}&radius=${this.radius}`
            );
            const data = await response.json();
            this.results = data.data || [];
        } catch (err) {
            console.error('ค้นหาใกล้ตัวล้มเหลว:', err);
        } finally {
            this.loading = false;
        }
    },
}));

// ===== Image Gallery Component =====
Alpine.data('imageGallery', (images = []) => ({
    images: images,
    currentIndex: 0,
    showLightbox: false,

    get currentImage() {
        return this.images[this.currentIndex] || null;
    },

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
    },

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
    },

    goTo(index) {
        this.currentIndex = index;
    },

    openLightbox(index = null) {
        if (index !== null) this.currentIndex = index;
        this.showLightbox = true;
    },

    closeLightbox() {
        this.showLightbox = false;
    },
}));

// ===== Listing Form Component (สำหรับผู้ขาย) =====
Alpine.data('listingForm', () => ({
    images: [],
    previewUrls: [],
    uploading: false,

    /**
     * จัดการเมื่อเลือกรูปภาพ
     */
    handleImageSelect(event) {
        const files = Array.from(event.target.files);
        const maxFiles = 5;

        if (this.images.length + files.length > maxFiles) {
            alert(`อัพโหลดได้สูงสุด ${maxFiles} รูป`);
            return;
        }

        files.forEach(file => {
            if (file.size > 5 * 1024 * 1024) {
                alert(`ไฟล์ ${file.name} ใหญ่เกิน 5MB`);
                return;
            }

            this.images.push(file);

            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrls.push(e.target.result);
            };
            reader.readAsDataURL(file);
        });
    },

    /**
     * ลบรูปภาพ
     */
    removeImage(index) {
        this.images.splice(index, 1);
        this.previewUrls.splice(index, 1);
    },
}));

// ===== Distance Helper =====
Alpine.data('distanceHelper', () => ({
    /**
     * แสดงระยะทางเป็นข้อความ
     */
    formatDistance(km) {
        if (km < 1) {
            return `${Math.round(km * 1000)} ม.`;
        }
        return `${km.toFixed(1)} กม.`;
    },

    /**
     * แสดงราคาเป็นรูปแบบไทย
     */
    formatPrice(price) {
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB',
            minimumFractionDigits: 0,
        }).format(price);
    },
}));

// เริ่มต้น Alpine.js (ถ้ายังไม่ได้เริ่ม)
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}
