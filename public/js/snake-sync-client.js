/**
 * Snake Game Sync Client
 *
 * Client สำหรับเชื่อมต่อกับ SnakeGameSyncService (Cache-based)
 * - Lightweight และรวดเร็ว (ไม่เขียน database)
 * - รองรับทั้ง Redis และ File cache driver
 * - ✅ รองรับ room_id เพื่อแยกผู้เล่นตามห้อง
 * - Sync ทุก 1 วินาที (ปรับจาก 1.5 ให้เร็วขึ้น)
 * - ส่ง update + fetch พร้อมกัน (parallel) แทน sequential
 * - มี fail-safe - ถ้า error ก็ไม่กระทบเกม
 * - เก็บรายชื่อผู้เล่นอื่น สำหรับ rendering
 */
class SnakeSyncClient {
    constructor() {
        this.apiBaseUrl = '/api/snake-sync';
        this.playerId = null;
        this.playerName = null;
        this.skin = null;
        this.roomId = null; // ✅ เก็บ room_id
        this.isConnected = false;
        this.lastSyncTime = 0;
        this.syncInterval = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // ✅ เก็บสถานะปัจจุบันของผู้เล่น สำหรับส่งใน sync loop
        this.currentState = null;

        // ✅ เก็บรายชื่อผู้เล่นอื่นที่ active (สำหรับ rendering)
        this.otherPlayersMap = new Map();

        // Callbacks
        this.onPlayersUpdate = null; // เรียกเมื่อได้ข้อมูลผู้เล่นคนอื่น
        this.onConnectionChange = null; // เรียกเมื่อสถานะการเชื่อมต่อเปลี่ยน
        this.onError = null; // เรียกเมื่อเกิด error

        // Config
        this.syncIntervalMs = 1000; // ✅ sync ทุก 1 วินาที (ลดจาก 1.5 ให้ responsive ขึ้น)
        this.maxRetries = 5;
        this.retryCount = 0;
    }

    /**
     * เข้าร่วมเกม (สร้าง session)
     *
     * @param {string} playerName ชื่อผู้เล่น
     * @param {string} skin สกินที่ใช้
     * @param {string} roomId ห้องที่ต้องการเข้า (optional)
     * @returns {Promise<boolean>} สำเร็จหรือไม่
     */
    async join(playerName, skin, roomId) {
        try {
            const body = {
                player_name: playerName,
                skin: skin || 'classic',
            };

            // ✅ ส่ง room_id ถ้ามี
            if (roomId) {
                body.room_id = roomId;
            }

            const response = await fetch(`${this.apiBaseUrl}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.player_id) {
                this.playerId = data.player_id;
                this.playerName = playerName;
                this.skin = skin;
                this.roomId = data.room_id || roomId || 'default'; // ✅ เก็บ room_id
                this.isConnected = true;
                this.retryCount = 0;

                console.log('[SnakeSync] เข้าร่วมสำเร็จ:', this.playerId, 'ห้อง:', this.roomId);

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
     * ตั้งค่าสถานะปัจจุบันของผู้เล่น (เรียกจาก game loop ทุก frame)
     * ✅ ไม่ส่ง HTTP request - แค่เก็บข้อมูลไว้ให้ sync loop ส่งทีหลัง
     *
     * @param {Object} state {position, direction, score, length, is_alive}
     */
    setCurrentState(state) {
        this.currentState = {
            position: state.position || { x: 0, y: 0, z: 0 },
            direction: state.direction || { x: 1, y: 0, z: 0 },
            score: state.score || 0,
            length: state.length || 5,
            is_alive: state.is_alive !== undefined ? state.is_alive : true,
        };
    }

    /**
     * ส่งสถานะปัจจุบันไปยัง server (เรียกจาก sync loop)
     *
     * @returns {Promise<boolean>}
     */
    async sendStateUpdate() {
        if (!this.isConnected || !this.playerId || !this.currentState) {
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
                    position: this.currentState.position,
                    direction: this.currentState.direction,
                    score: this.currentState.score,
                    length: this.currentState.length,
                    is_alive: this.currentState.is_alive,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.retryCount = 0;
            return data.success;
        } catch (error) {
            console.warn('[SnakeSync] อัปเดตสถานะล้มเหลว:', error.message);
            this.handleError(error);
            return false;
        }
    }

    /**
     * ดึงผู้เล่น active ทั้งหมดจาก server (เฉพาะห้องเดียวกัน)
     *
     * @returns {Promise<Array>} รายการผู้เล่น
     */
    async fetchActivePlayers() {
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
            this.retryCount = 0;

            if (data.success && data.players) {
                // ✅ อัปเดต otherPlayersMap สำหรับ rendering
                this.updateOtherPlayersMap(data.players);

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
     * ✅ อัปเดต Map ของผู้เล่นอื่น (สำหรับ rendering)
     * แปลง server data ให้อยู่ในรูปแบบที่ renderOtherPlayers() ใช้ได้
     */
    updateOtherPlayersMap(players) {
        const newPlayerIds = new Set();

        players.forEach(p => {
            const pid = p.player_id;
            newPlayerIds.add(pid);

            this.otherPlayersMap.set(pid, {
                id: pid,
                name: p.player_name || 'Player',
                skin: p.skin || 'classic',
                position: p.position || { x: 0, y: 0, z: 0 },
                direction: p.direction || { x: 1, y: 0, z: 0 },
                score: p.score || 0,
                length: p.length || 5,
                is_alive: p.is_alive !== false,
            });
        });

        // ลบผู้เล่นที่ออกไปแล้ว
        for (const [playerId] of this.otherPlayersMap) {
            if (!newPlayerIds.has(playerId)) {
                this.otherPlayersMap.delete(playerId);
            }
        }
    }

    /**
     * ✅ ดึงรายการผู้เล่นอื่น (สำหรับ rendering - compatible กับ multiplayerManager.getOtherPlayers())
     * @returns {Array} รายการผู้เล่น
     */
    getOtherPlayers() {
        return Array.from(this.otherPlayersMap.values());
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
            // หยุด sync loop ก่อน
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
            this.otherPlayersMap.clear();
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
     * ✅ เริ่ม sync loop (ทุก 1 วินาที)
     * ✅ FIX: ส่ง update + fetch พร้อมกัน (parallel) แทน sequential
     */
    startSyncLoop() {
        // หยุด loop เดิม (ถ้ามี)
        this.stopSyncLoop();

        console.log('[SnakeSync] เริ่ม sync loop ทุก', this.syncIntervalMs / 1000, 'วินาที');

        this.syncInterval = setInterval(async () => {
            try {
                // ✅ FIX: ส่ง update + fetch พร้อมกัน (ลด latency ลงครึ่งหนึ่ง)
                const promises = [];

                // 1. ส่งสถานะปัจจุบัน (ถ้ามี)
                if (this.currentState) {
                    promises.push(this.sendStateUpdate());
                }

                // 2. ดึงผู้เล่นคนอื่น (ทำพร้อมกัน)
                promises.push(this.fetchActivePlayers());

                await Promise.allSettled(promises);

            } catch (error) {
                console.warn('[SnakeSync] Sync loop error:', error.message);
            }
        }, this.syncIntervalMs);

        // ✅ Ping แยก loop ทุก 10 วินาที (ไม่ต้อง ping ทุก sync)
        this.pingInterval = setInterval(async () => {
            try {
                await this.ping();
            } catch (error) {
                // silent fail
            }
        }, 10000);
    }

    /**
     * หยุด sync loop
     */
    stopSyncLoop() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
        console.log('[SnakeSync] หยุด sync loop');
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
        this.roomId = null;
        this.otherPlayersMap.clear();
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

    /**
     * ดึง room ID
     */
    getRoomId() {
        return this.roomId;
    }

    /**
     * ดึงจำนวนผู้เล่นอื่นที่เห็น
     */
    getOtherPlayerCount() {
        return this.otherPlayersMap.size;
    }
}
