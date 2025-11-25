/**
 * Web GPS Tracker Module
 *
 * ระบบติดตาม GPS ผ่าน Web Browser (รองรับทั้ง Desktop และ Mobile)
 * ใช้ HTML5 Geolocation API - ไม่ต้องลงแอพ
 *
 * @version 1.0.0
 * @requires Alpine.js
 */

// ตรวจสอบว่า Browser รองรับ Geolocation หรือไม่
const isGeolocationSupported = 'geolocation' in navigator;

// ค่า Config สำหรับ Geolocation
const GEO_CONFIG = {
    enableHighAccuracy: true,   // ใช้ GPS จริง (แม่นยำกว่า)
    timeout: 30000,             // รอสูงสุด 30 วินาที
    maximumAge: 0,              // ไม่ใช้ cache
};

// Config สำหรับ Background tracking (ถ้า Browser รองรับ)
const TRACKING_INTERVAL = 10000;  // 10 วินาที
const SEND_INTERVAL = 30000;      // ส่งข้อมูลทุก 30 วินาที

/**
 * Web GPS Tracker Class
 */
class WebGpsTracker {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: options.apiEndpoint || '/api/v1/service-bookings',
            bookingId: options.bookingId || null,
            entityType: options.entityType || 'user', // 'user' หรือ 'provider'
            onLocationUpdate: options.onLocationUpdate || null,
            onError: options.onError || null,
            onStatusChange: options.onStatusChange || null,
            autoStart: options.autoStart || false,
            debug: options.debug || false,
        };

        // สถานะ
        this.isTracking = false;
        this.watchId = null;
        this.lastPosition = null;
        this.locationBuffer = [];
        this.sendIntervalId = null;

        // สถิติ
        this.stats = {
            totalUpdates: 0,
            successfulSends: 0,
            failedSends: 0,
            lastSendTime: null,
        };

        // ตรวจสอบ Browser Support
        this.checkSupport();

        // Auto start ถ้ากำหนด
        if (this.options.autoStart && this.options.bookingId) {
            this.startTracking();
        }
    }

    /**
     * ตรวจสอบว่า Browser รองรับ GPS หรือไม่
     */
    checkSupport() {
        this.isSupported = isGeolocationSupported;

        if (!this.isSupported) {
            this.log('error', 'Geolocation is not supported by this browser');
            this.updateStatus('unsupported', 'Browser ไม่รองรับ GPS');
        }

        return this.isSupported;
    }

    /**
     * ขอ Permission เข้าถึง GPS
     */
    async requestPermission() {
        if (!this.isSupported) {
            return { granted: false, reason: 'unsupported' };
        }

        try {
            // ลองขอ permission ผ่าน Permissions API (ถ้ารองรับ)
            if ('permissions' in navigator) {
                const result = await navigator.permissions.query({ name: 'geolocation' });

                if (result.state === 'granted') {
                    return { granted: true };
                } else if (result.state === 'denied') {
                    return { granted: false, reason: 'denied' };
                }
                // 'prompt' - ต้องขอจริงๆ
            }

            // ลองขอ GPS จริง (จะ trigger permission dialog)
            return new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    () => resolve({ granted: true }),
                    (error) => {
                        if (error.code === error.PERMISSION_DENIED) {
                            resolve({ granted: false, reason: 'denied' });
                        } else {
                            resolve({ granted: false, reason: error.message });
                        }
                    },
                    { timeout: 10000 }
                );
            });
        } catch (error) {
            this.log('error', 'Permission request failed:', error);
            return { granted: false, reason: error.message };
        }
    }

    /**
     * เริ่มการติดตาม GPS
     */
    async startTracking() {
        if (!this.isSupported) {
            this.updateStatus('unsupported', 'Browser ไม่รองรับ GPS');
            return false;
        }

        if (this.isTracking) {
            this.log('warn', 'Already tracking');
            return true;
        }

        // ขอ permission
        const permission = await this.requestPermission();
        if (!permission.granted) {
            this.updateStatus('permission_denied', 'ไม่ได้รับอนุญาตให้เข้าถึง GPS');
            if (this.options.onError) {
                this.options.onError({
                    type: 'permission',
                    message: 'กรุณาอนุญาตให้เว็บไซต์เข้าถึงตำแหน่งของคุณ',
                    reason: permission.reason,
                });
            }
            return false;
        }

        this.log('info', 'Starting GPS tracking...');
        this.updateStatus('starting', 'กำลังเริ่มติดตาม GPS...');

        // เริ่ม watch position
        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handlePositionUpdate(position),
            (error) => this.handlePositionError(error),
            GEO_CONFIG
        );

        this.isTracking = true;

        // เริ่ม interval สำหรับส่งข้อมูลไปยัง server
        this.startSendInterval();

        this.updateStatus('tracking', 'กำลังติดตาม GPS');
        return true;
    }

    /**
     * หยุดการติดตาม GPS
     */
    stopTracking() {
        if (!this.isTracking) {
            return;
        }

        this.log('info', 'Stopping GPS tracking...');

        // หยุด watch
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        // หยุด send interval
        this.stopSendInterval();

        // ส่งข้อมูลที่เหลือ
        if (this.locationBuffer.length > 0) {
            this.sendLocationBuffer();
        }

        this.isTracking = false;
        this.updateStatus('stopped', 'หยุดติดตาม GPS');
    }

    /**
     * จัดการเมื่อได้รับตำแหน่งใหม่
     */
    handlePositionUpdate(position) {
        const locationData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude,
            altitudeAccuracy: position.coords.altitudeAccuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            timestamp: position.timestamp,
            recorded_at: new Date().toISOString(),
        };

        this.lastPosition = locationData;
        this.stats.totalUpdates++;

        // เพิ่มเข้า buffer
        this.locationBuffer.push(locationData);

        this.log('debug', 'Position updated:', locationData);

        // Callback
        if (this.options.onLocationUpdate) {
            this.options.onLocationUpdate(locationData);
        }

        // อัพเดทสถานะ
        this.updateStatus('tracking', `ติดตามอยู่ (${this.stats.totalUpdates} ครั้ง)`);
    }

    /**
     * จัดการเมื่อเกิด error
     */
    handlePositionError(error) {
        let errorMessage = '';
        let errorType = 'unknown';

        switch (error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'ไม่ได้รับอนุญาตให้เข้าถึง GPS';
                errorType = 'permission';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'ไม่สามารถระบุตำแหน่งได้';
                errorType = 'unavailable';
                break;
            case error.TIMEOUT:
                errorMessage = 'หมดเวลาในการขอตำแหน่ง';
                errorType = 'timeout';
                break;
            default:
                errorMessage = 'เกิดข้อผิดพลาดในการขอตำแหน่ง';
                errorType = 'unknown';
        }

        this.log('error', 'Position error:', error);

        // Callback
        if (this.options.onError) {
            this.options.onError({
                type: errorType,
                message: errorMessage,
                code: error.code,
                originalError: error,
            });
        }

        this.updateStatus('error', errorMessage);
    }

    /**
     * เริ่ม interval สำหรับส่งข้อมูล
     */
    startSendInterval() {
        this.sendIntervalId = setInterval(() => {
            this.sendLocationBuffer();
        }, SEND_INTERVAL);
    }

    /**
     * หยุด interval สำหรับส่งข้อมูล
     */
    stopSendInterval() {
        if (this.sendIntervalId) {
            clearInterval(this.sendIntervalId);
            this.sendIntervalId = null;
        }
    }

    /**
     * ส่งข้อมูลใน buffer ไปยัง server
     */
    async sendLocationBuffer() {
        if (this.locationBuffer.length === 0 || !this.options.bookingId) {
            return;
        }

        const dataToSend = [...this.locationBuffer];
        this.locationBuffer = []; // Clear buffer

        try {
            const response = await fetch(
                `${this.options.apiEndpoint}/${this.options.bookingId}/log-location`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        locations: dataToSend,
                        entity_type: this.options.entityType,
                        source: 'web_browser',
                    }),
                }
            );

            if (response.ok) {
                this.stats.successfulSends++;
                this.stats.lastSendTime = new Date();
                this.log('info', `Sent ${dataToSend.length} locations to server`);
            } else {
                throw new Error(`Server responded with ${response.status}`);
            }
        } catch (error) {
            this.stats.failedSends++;
            this.log('error', 'Failed to send locations:', error);

            // เก็บข้อมูลกลับเข้า buffer ถ้าส่งไม่สำเร็จ
            this.locationBuffer = [...dataToSend, ...this.locationBuffer];

            // จำกัดขนาด buffer
            if (this.locationBuffer.length > 100) {
                this.locationBuffer = this.locationBuffer.slice(-100);
            }
        }
    }

    /**
     * ขอตำแหน่งปัจจุบันครั้งเดียว
     */
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!this.isSupported) {
                reject(new Error('Geolocation is not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const locationData = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp,
                    };
                    resolve(locationData);
                },
                (error) => {
                    reject(error);
                },
                GEO_CONFIG
            );
        });
    }

    /**
     * อัพเดทสถานะ
     */
    updateStatus(status, message) {
        if (this.options.onStatusChange) {
            this.options.onStatusChange({ status, message });
        }
    }

    /**
     * Log message
     */
    log(level, ...args) {
        if (this.options.debug || level === 'error') {
            console[level]('[WebGpsTracker]', ...args);
        }
    }

    /**
     * ดึงสถิติ
     */
    getStats() {
        return {
            ...this.stats,
            isTracking: this.isTracking,
            lastPosition: this.lastPosition,
            bufferSize: this.locationBuffer.length,
        };
    }
}

/**
 * Alpine.js Component สำหรับ GPS Tracking
 */
function webGpsTrackerComponent(config = {}) {
    return {
        // State
        isSupported: isGeolocationSupported,
        isTracking: false,
        hasPermission: null,
        status: 'idle',
        statusMessage: '',
        lastLocation: null,
        accuracy: null,
        errorMessage: null,
        tracker: null,

        // Config
        bookingId: config.bookingId || null,
        entityType: config.entityType || 'user',
        apiEndpoint: config.apiEndpoint || '/api/v1/service-bookings',
        debug: config.debug || false,

        // Stats
        updateCount: 0,
        lastUpdateTime: null,

        /**
         * Initialize component
         */
        init() {
            // ตรวจสอบ support
            if (!this.isSupported) {
                this.status = 'unsupported';
                this.statusMessage = 'Browser ของคุณไม่รองรับ GPS';
                this.errorMessage = 'กรุณาใช้ Browser ที่รองรับ เช่น Chrome, Firefox, Safari';
                return;
            }

            // สร้าง tracker
            this.tracker = new WebGpsTracker({
                bookingId: this.bookingId,
                entityType: this.entityType,
                apiEndpoint: this.apiEndpoint,
                debug: this.debug,
                onLocationUpdate: (location) => this.handleLocationUpdate(location),
                onError: (error) => this.handleError(error),
                onStatusChange: (data) => this.handleStatusChange(data),
            });

            // เช็ค permission
            this.checkPermission();
        },

        /**
         * ตรวจสอบ permission
         */
        async checkPermission() {
            if ('permissions' in navigator) {
                try {
                    const result = await navigator.permissions.query({ name: 'geolocation' });
                    this.hasPermission = result.state === 'granted';

                    // Listen สำหรับการเปลี่ยนแปลง permission
                    result.onchange = () => {
                        this.hasPermission = result.state === 'granted';
                    };
                } catch (e) {
                    // ไม่รองรับ permissions API
                    this.hasPermission = null;
                }
            }
        },

        /**
         * เริ่มติดตาม
         */
        async startTracking() {
            if (!this.tracker) return;

            this.errorMessage = null;
            const started = await this.tracker.startTracking();

            if (started) {
                this.isTracking = true;
                this.hasPermission = true;
            }
        },

        /**
         * หยุดติดตาม
         */
        stopTracking() {
            if (!this.tracker) return;

            this.tracker.stopTracking();
            this.isTracking = false;
        },

        /**
         * Toggle tracking
         */
        toggleTracking() {
            if (this.isTracking) {
                this.stopTracking();
            } else {
                this.startTracking();
            }
        },

        /**
         * ขอตำแหน่งปัจจุบัน
         */
        async getMyLocation() {
            if (!this.tracker) return;

            this.status = 'locating';
            this.statusMessage = 'กำลังค้นหาตำแหน่ง...';

            try {
                const location = await this.tracker.getCurrentPosition();
                this.handleLocationUpdate(location);
                this.status = 'located';
                this.statusMessage = 'พบตำแหน่งของคุณแล้ว';
            } catch (error) {
                this.handleError({
                    type: 'position',
                    message: 'ไม่สามารถระบุตำแหน่งได้',
                });
            }
        },

        /**
         * จัดการเมื่อได้รับตำแหน่ง
         */
        handleLocationUpdate(location) {
            this.lastLocation = location;
            this.accuracy = location.accuracy;
            this.updateCount++;
            this.lastUpdateTime = new Date();

            // Dispatch event
            this.$dispatch('location-updated', { location });
        },

        /**
         * จัดการ error
         */
        handleError(error) {
            this.errorMessage = error.message;
            this.status = 'error';

            if (error.type === 'permission') {
                this.hasPermission = false;
            }

            // Dispatch event
            this.$dispatch('location-error', { error });
        },

        /**
         * จัดการการเปลี่ยนสถานะ
         */
        handleStatusChange(data) {
            this.status = data.status;
            this.statusMessage = data.message;
        },

        /**
         * Format coordinates
         */
        formatCoords(location) {
            if (!location) return '-';
            return `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`;
        },

        /**
         * Format accuracy
         */
        formatAccuracy(accuracy) {
            if (!accuracy) return '-';
            if (accuracy < 10) return `±${accuracy.toFixed(0)}m (แม่นยำมาก)`;
            if (accuracy < 50) return `±${accuracy.toFixed(0)}m (แม่นยำ)`;
            if (accuracy < 100) return `±${accuracy.toFixed(0)}m (ปานกลาง)`;
            return `±${accuracy.toFixed(0)}m (ต่ำ)`;
        },

        /**
         * Get status color
         */
        getStatusColor() {
            switch (this.status) {
                case 'tracking': return 'green';
                case 'located': return 'blue';
                case 'error': return 'red';
                case 'unsupported': return 'gray';
                case 'locating':
                case 'starting': return 'yellow';
                default: return 'gray';
            }
        },

        /**
         * Get status icon
         */
        getStatusIcon() {
            switch (this.status) {
                case 'tracking': return 'fa-satellite-dish';
                case 'located': return 'fa-map-marker-alt';
                case 'error': return 'fa-exclamation-triangle';
                case 'unsupported': return 'fa-times-circle';
                case 'locating':
                case 'starting': return 'fa-spinner fa-spin';
                default: return 'fa-location-arrow';
            }
        },
    };
}

// Export for use
window.WebGpsTracker = WebGpsTracker;
window.webGpsTrackerComponent = webGpsTrackerComponent;

// Register with Alpine.js if available
document.addEventListener('alpine:init', () => {
    Alpine.data('webGpsTracker', webGpsTrackerComponent);
});
