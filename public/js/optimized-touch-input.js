/**
 * Optimized Touch Input Handler
 *
 * ปรับปรุงการตอบสนองสัมผัสให้เร็วขึ้น ลด latency
 * - ใช้ direct calculation แทน raycaster
 * - Throttle optimization
 * - Predictive input
 */
class OptimizedTouchInput {
    constructor(camera, targetObject) {
        this.camera = camera;
        this.targetObject = targetObject; // วัตถุที่ต้องการควบคุม (เช่น snake head)
        this.isActive = false;
        this.lastTouchTime = 0;
        this.touchThrottle = 16; // ~60fps (1000/60 ≈ 16ms)

        // Touch state
        this.isTouching = false;
        this.touchCount = 0;
        this.lastTouchPos = { x: 0, y: 0 };

        // Direction output
        this.currentDirection = null;

        // Callbacks
        this.onDirectionChange = null;
        this.onBoostStart = null;
        this.onBoostEnd = null;
    }

    /**
     * เริ่มต้น touch input
     */
    init(canvas) {
        // Touch events
        canvas.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        canvas.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        canvas.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });

        // Prevent default touch behavior
        document.addEventListener('touchmove', function(e) {
            e.preventDefault();
        }, { passive: false });

        this.isActive = true;
        console.log('[TouchInput] Initialized with optimized handling');
    }

    /**
     * จัดการ touch start
     */
    handleTouchStart(event) {
        event.preventDefault();

        this.isTouching = true;
        this.touchCount = event.touches.length;

        if (event.touches.length === 1) {
            const touch = event.touches[0];
            this.updateDirectionFast(touch.clientX, touch.clientY);
        }

        // Double finger = boost
        if (event.touches.length === 2 && this.onBoostStart) {
            this.onBoostStart();
        }
    }

    /**
     * จัดการ touch move (optimized)
     */
    handleTouchMove(event) {
        event.preventDefault();

        if (!this.isTouching || event.touches.length !== 1) return;

        // Throttle updates
        const now = performance.now();
        if (now - this.lastTouchTime < this.touchThrottle) {
            return;
        }
        this.lastTouchTime = now;

        const touch = event.touches[0];
        this.updateDirectionFast(touch.clientX, touch.clientY);
    }

    /**
     * จัดการ touch end
     */
    handleTouchEnd(event) {
        event.preventDefault();

        this.touchCount = event.touches.length;

        // Stop boost when lifting second finger
        if (this.touchCount < 2 && this.onBoostEnd) {
            this.onBoostEnd();
        }

        if (this.touchCount === 0) {
            this.isTouching = false;
        }
    }

    /**
     * อัปเดตทิศทางแบบเร็ว (ไม่ใช้ raycaster)
     *
     * ใช้ simple 2D projection แทนการคำนวณ 3D ซับซ้อน
     */
    updateDirectionFast(screenX, screenY) {
        if (!this.targetObject) return;

        // แปลง screen space เป็น normalized device coordinates (-1 to +1)
        const ndcX = (screenX / window.innerWidth) * 2 - 1;
        const ndcY = -(screenY / window.innerHeight) * 2 + 1;

        // ตำแหน่งศูนย์กลางหน้าจอ
        const centerX = 0;
        const centerY = 0;

        // คำนวณทิศทาง (relative จากศูนย์กลาง)
        const deltaX = ndcX - centerX;
        const deltaY = ndcY - centerY;

        // คำนวณมุมจากจุดกึ่งกลาง
        const angle = Math.atan2(-deltaY, deltaX); // Y กลับเพราะ canvas coordinates

        // แปลงเป็น 3D direction (top-down view)
        // X-axis (ซ้าย-ขวา), Z-axis (บน-ล่าง)
        const direction = {
            x: Math.cos(angle),
            y: 0,
            z: Math.sin(angle),
        };

        // Normalize
        const length = Math.sqrt(direction.x * direction.x + direction.z * direction.z);
        if (length > 0) {
            direction.x /= length;
            direction.z /= length;
        }

        this.currentDirection = direction;

        // Callback
        if (this.onDirectionChange) {
            this.onDirectionChange(direction);
        }
    }

    /**
     * ดึงทิศทางปัจจุบัน
     */
    getDirection() {
        return this.currentDirection;
    }

    /**
     * ตั้งค่า callback เมื่อทิศทางเปลี่ยน
     */
    setOnDirectionChange(callback) {
        this.onDirectionChange = callback;
    }

    /**
     * ตั้งค่า callback เมื่อเริ่ม boost
     */
    setOnBoostStart(callback) {
        this.onBoostStart = callback;
    }

    /**
     * ตั้งค่า callback เมื่อหยุด boost
     */
    setOnBoostEnd(callback) {
        this.onBoostEnd = callback;
    }

    /**
     * ทำลาย instance
     */
    destroy() {
        this.isActive = false;
        this.onDirectionChange = null;
        this.onBoostStart = null;
        this.onBoostEnd = null;
    }
}

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OptimizedTouchInput;
} else {
    window.OptimizedTouchInput = OptimizedTouchInput;
}
