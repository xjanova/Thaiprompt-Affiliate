/**
 * Snake Game Sync Client
 *
 * Client สำหรับเชื่อมต่อกับ SnakeGameSyncService
 * - Lightweight และรวดเร็ว
 * - ใช้ Cache/Redis แทน database
 * - มี fail-safe - ถ้า error ก็ไม่กระทบเกม
 * - Optional multiplayer - เล่นแบบ offline ได้เสมอ
 */
class SnakeSyncClient {
    constructor() {
        this.apiBaseUrl = '/api/snake-sync';
        this.playerId = null;
        this.playerName = null;
        this.skin = null;
        this.isConnected = false;
        this.lastSyncTime = 0;
        this.syncInterval = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Callbacks
        this.onPlayersUpdate = null; // เรียกเมื่อได้ข้อมูลผู้เล่นคนอื่น
        this.onConnectionChange = null; // เรียกเมื่อสถานะการเชื่อมต่อเปลี่ยน
        this.onError = null; // เรียกเมื่อเกิด error

        // Config
        this.syncIntervalMs = 3000; // sync ทุก 3 วินาที (ช้ากว่าเดิมเพื่อไม่ให้เกมค้าง)
        this.maxRetries = 2; // พยายาม 2 ครั้งแล้วปิด
        this.retryCount = 0;
    }

    /**
     * เข้าร่วมเกม (สร้าง session)
     *
     * @param {string} playerName ชื่อผู้เล่น
     * @param {string} skin สกินที่ใช้
     * @returns {Promise<boolean>} สำเร็จหรือไม่
     */
    async join(playerName, skin) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_name: playerName,
                    skin: skin || 'classic',
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.player_id) {
                this.playerId = data.player_id;
                this.playerName = playerName;
                this.skin = skin;
                this.isConnected = true;
                this.retryCount = 0;

                console.log('[SnakeSync] เข้าร่วมสำเร็จ:', this.playerId);

                // เริ่ม sync loop
                this.startSyncLoop();

                // Notify connection change
                this.notifyConnectionChange(true);

                return true;
            }

            throw new Error('Invalid response');
        } catch (error) {
            console.warn('[SnakeSync] เข้าร่วมล้มเหลว:', error.message);
            this.handleError(error);
            return false;
        }
    }

    /**
     * อัปเดตสถานะผู้เล่น (เรียกจากเกม)
     *
     * @param {Object} state {position, direction, score, length, is_alive}
     * @returns {Promise<boolean>}
     */
    async updateState(state) {
        if (!this.isConnected || !this.playerId) {
            return false;
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    position: state.position || { x: 0, y: 0, z: 0 },
                    direction: state.direction || { x: 1, y: 0, z: 0 },
                    score: state.score || 0,
                    length: state.length || 5,
                    is_alive: state.is_alive !== undefined ? state.is_alive : true,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.retryCount = 0; // Reset retry count on success
            return data.success;
        } catch (error) {
            console.warn('[SnakeSync] อัปเดตสถานะล้มเหลว:', error.message);
            this.handleError(error);
            return false;
        }
    }

    /**
     * ดึงผู้เล่น active ทั้งหมด
     *
     * @returns {Promise<Array>} รายการผู้เล่น
     */
    async getActivePlayers() {
        if (!this.isConnected || !this.playerId) {
            return [];
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/players/${this.playerId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.retryCount = 0; // Reset retry count on success

            if (data.success && data.players) {
                // Notify callback
                if (this.onPlayersUpdate) {
                    this.onPlayersUpdate(data.players);
                }
                return data.players;
            }

            return [];
        } catch (error) {
            console.warn('[SnakeSync] ดึงผู้เล่นล้มเหลว:', error.message);
            this.handleError(error);
            return [];
        }
    }

    /**
     * แจ้งว่าผู้เล่นตาย
     */
    async playerDied() {
        if (!this.isConnected || !this.playerId) {
            return;
        }

        try {
            await fetch(`${this.apiBaseUrl}/died`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                }),
            });

            console.log('[SnakeSync] แจ้งการตาย');
        } catch (error) {
            console.warn('[SnakeSync] แจ้งการตายล้มเหลว:', error.message);
        }
    }

    /**
     * ออกจากเกม
     */
    async leave() {
        if (!this.isConnected || !this.playerId) {
            return;
        }

        try {
            // หยุด sync loop
            this.stopSyncLoop();

            await fetch(`${this.apiBaseUrl}/leave`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                }),
            });

            console.log('[SnakeSync] ออกจากเกม');

            this.isConnected = false;
            this.notifyConnectionChange(false);
        } catch (error) {
            console.warn('[SnakeSync] ออกจากเกมล้มเหลว:', error.message);
        }
    }

    /**
     * Ping session เพื่อรักษาการเชื่อมต่อ
     */
    async ping() {
        if (!this.isConnected || !this.playerId) {
            return false;
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/ping`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                }),
            });

            const data = await response.json();
            return data.success;
        } catch (error) {
            console.warn('[SnakeSync] Ping ล้มเหลว:', error.message);
            return false;
        }
    }

    /**
     * เริ่ม sync loop (ดึงผู้เล่นคนอื่นทุก 3 วินาที)
     */
    startSyncLoop() {
        // หยุด loop เดิม (ถ้ามี)
        this.stopSyncLoop();

        console.log('[SnakeSync] เริ่ม sync loop ทุก', this.syncIntervalMs / 1000, 'วินาที');

        this.syncInterval = setInterval(async () => {
            try {
                // Ping เพื่อรักษา session
                await this.ping();

                // ดึงผู้เล่นคนอื่น
                await this.getActivePlayers();
            } catch (error) {
                console.warn('[SnakeSync] Sync loop error:', error.message);
            }
        }, this.syncIntervalMs);
    }

    /**
     * หยุด sync loop
     */
    stopSyncLoop() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
            console.log('[SnakeSync] หยุด sync loop');
        }
    }

    /**
     * จัดการ error - ถ้า fail บ่อยก็ปิด multiplayer
     */
    handleError(error) {
        this.retryCount++;

        if (this.retryCount >= this.maxRetries) {
            console.warn('[SnakeSync] Error บ่อยเกินไป - ปิด multiplayer');
            this.disconnect();
        }

        // Notify error callback
        if (this.onError) {
            this.onError(error);
        }
    }

    /**
     * ตัดการเชื่อมต่อ (เมื่อ error หรือไม่ต้องการ multiplayer)
     */
    disconnect() {
        this.stopSyncLoop();
        this.isConnected = false;
        this.playerId = null;
        this.notifyConnectionChange(false);
        console.log('[SnakeSync] ตัดการเชื่อมต่อ - เล่นแบบ offline');
    }

    /**
     * แจ้งเปลี่ยนสถานะการเชื่อมต่อ
     */
    notifyConnectionChange(connected) {
        if (this.onConnectionChange) {
            this.onConnectionChange(connected);
        }
    }

    /**
     * ตั้งค่า callback สำหรับอัปเดตผู้เล่น
     */
    setOnPlayersUpdate(callback) {
        this.onPlayersUpdate = callback;
    }

    /**
     * ตั้งค่า callback สำหรับเปลี่ยนสถานะการเชื่อมต่อ
     */
    setOnConnectionChange(callback) {
        this.onConnectionChange = callback;
    }

    /**
     * ตั้งค่า callback สำหรับ error
     */
    setOnError(callback) {
        this.onError = callback;
    }

    /**
     * ตรวจสอบว่าเชื่อมต่ออยู่หรือไม่
     */
    isOnline() {
        return this.isConnected;
    }

    /**
     * ดึง player ID
     */
    getPlayerId() {
        return this.playerId;
    }
}
