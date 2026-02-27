/**
 * Snake.io Multiplayer Manager (Polling Edition)
 *
 * จัดการการเชื่อมต่อ multiplayer ผ่าน HTTP Polling
 * - ใช้ polling ทุก 1.5 วินาทีเพื่อดึงสถานะห้อง
 * - throttle การอัปเดตสถานะเพื่อไม่ให้เซิร์ฟเวอร์โอเวอร์โหลด
 * - รองรับ custom server config
 */
class SnakeMultiplayerManager {
    constructor(apiBaseUrl = '/api/games/snake-io', serverConfig = null) {
        // ✅ รองรับการตั้งค่า server แบบ custom (IP + Port)
        if (serverConfig && serverConfig.ip && serverConfig.port) {
            this.apiBaseUrl = `http://${serverConfig.ip}:${serverConfig.port}/api/games/snake-io`;
            console.log('[Multiplayer] ใช้ custom server:', this.apiBaseUrl);
        } else {
            this.apiBaseUrl = apiBaseUrl;
        }

        this.roomId = null;
        this.playerId = null;
        this.roomCode = null;
        this.otherPlayers = new Map(); // Map<playerId, playerData>
        this.serverItems = new Map(); // Map<itemId, itemData>

        // ✅ Throttle: ป้องกันการส่ง HTTP request ถี่เกินไป
        this.lastUpdateTime = 0;
        this.updateThrottleMs = 200; // ส่งได้ทุก 200ms (5 ครั้ง/วินาที สูงสุด)

        // Callbacks สำหรับการรับ events
        this.onPlayerJoinedCallback = null;
        this.onPlayerLeftCallback = null;
        this.onPlayerMovedCallback = null;
        this.onPlayerDiedCallback = null;
        this.onItemSpawnedCallback = null;
        this.onItemCollectedCallback = null;

        // CSRF Token
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    /**
     * เข้าร่วมเกม
     */
    async joinGame(playerName, skinSlug = 'classic') {
        try {
            const response = await fetch(`${this.apiBaseUrl}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_name: playerName,
                    skin_slug: skinSlug,
                }),
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'เข้าร่วมเกมไม่สำเร็จ');
            }

            this.roomId = data.room_id;
            this.playerId = data.player_id;
            this.roomCode = data.room_code;

            console.log('[Multiplayer] เข้าร่วมห้อง:', this.roomCode, 'Player ID:', this.playerId);

            // เริ่ม polling
            this.connectWebSocket();

            return data;
        } catch (error) {
            console.error('[Multiplayer] เข้าร่วมเกมล้มเหลว:', error);
            throw error;
        }
    }

    /**
     * เริ่ม polling สำหรับ room state
     */
    connectWebSocket() {
        console.log('[Multiplayer] เริ่ม polling สำหรับ room:', this.roomId);

        // ✅ Polling ทุก 1.5 วินาที (ลดจาก 2 วินาที)
        this.pollingInterval = setInterval(async () => {
            try {
                await this.pollRoomState();
            } catch (error) {
                // Silent fail - ไม่ให้ error ซ้ำเยอะ
            }
        }, 1500);
    }

    /**
     * โพลสถานะห้อง (ดึงข้อมูลผู้เล่นคนอื่น)
     */
    async pollRoomState() {
        if (!this.roomId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/room-state/${this.roomId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) return;

            const data = await response.json();
            if (!data.success) return;

            const roomState = data.room_state;

            // อัปเดตผู้เล่นคนอื่น (ไม่รวมตัวเอง)
            const newPlayerIds = new Set();

            if (roomState.players && Array.isArray(roomState.players)) {
                roomState.players.forEach(playerData => {
                    // ✅ FIX: ใช้ 'id' ที่ server ส่งมา (ไม่ใช่ 'player_id')
                    const pid = playerData.id || playerData.player_id;

                    // ข้ามตัวเอง
                    if (pid === this.playerId) {
                        return;
                    }

                    newPlayerIds.add(pid);

                    // อัปเดตหรือสร้างผู้เล่นใหม่
                    if (!this.otherPlayers.has(pid)) {
                        // ผู้เล่นใหม่เข้ามา
                        const pname = playerData.name || playerData.player_name || 'Player';
                        console.log('[Multiplayer] ผู้เล่นใหม่เข้าร่วม:', pname);
                    }

                    this.otherPlayers.set(pid, {
                        id: pid,
                        // ✅ FIX: ใช้ 'name' ที่ server ส่งมา (ไม่ใช่ 'player_name')
                        name: playerData.name || playerData.player_name || 'Player',
                        skin: playerData.skin || 'classic',
                        position: playerData.position || { x: 0, y: 0, z: 0 },
                        direction: playerData.direction || { x: 1, y: 0, z: 0 },
                        score: playerData.score || 0,
                        length: playerData.length || 5,
                        is_alive: playerData.is_alive !== false,
                    });
                });
            }

            // ลบผู้เล่นที่ออกไปแล้ว
            for (const [playerId, playerData] of this.otherPlayers) {
                if (!newPlayerIds.has(playerId)) {
                    console.log('[Multiplayer] ผู้เล่นออก:', playerData.name);
                    this.otherPlayers.delete(playerId);
                }
            }

        } catch (error) {
            // Silent fail
        }
    }

    /**
     * ยกเลิก polling
     */
    disconnectWebSocket() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('[Multiplayer] หยุด polling');
        }
    }


    /**
     * ออกจากเกม
     */
    async leaveGame() {
        try {
            this.disconnectWebSocket();

            if (!this.playerId) return;

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

            console.log('[Multiplayer] ออกจากห้อง');
        } catch (error) {
            console.error('[Multiplayer] ออกจากเกมล้มเหลว:', error);
        }
    }

    /**
     * อัปเดตสถานะผู้เล่น (ส่งไปยัง server)
     * ✅ มี throttle ป้องกันการส่งถี่เกินไป (ทุก 200ms)
     */
    async updatePlayerState(position, direction, score, length) {
        if (!this.playerId) return;

        // ✅ Throttle: ส่งได้ทุก 200ms เท่านั้น
        const now = Date.now();
        if (now - this.lastUpdateTime < this.updateThrottleMs) {
            return; // ข้าม - ยังไม่ถึงเวลาส่ง
        }
        this.lastUpdateTime = now;

        try {
            await fetch(`${this.apiBaseUrl}/update-state`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    position: {
                        x: position.x,
                        y: position.y,
                        z: position.z,
                    },
                    direction: {
                        x: direction.x,
                        y: direction.y,
                        z: direction.z,
                    },
                    score: score,
                    length: length,
                }),
            });
        } catch (error) {
            // Silent fail - ไม่บล็อก game loop
        }
    }

    /**
     * แจ้งว่าผู้เล่นตาย
     */
    async playerDied() {
        if (!this.playerId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/player-died`, {
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
            console.log('[Multiplayer] ผู้เล่นตาย - คะแนนสุดท้าย:', data.final_score);

            this.disconnectWebSocket();
            return data;
        } catch (error) {
            console.error('[Multiplayer] แจ้งการตายล้มเหลว:', error);
        }
    }

    /**
     * เก็บไอเทม
     */
    async collectItem(itemId) {
        if (!this.playerId) return null;

        try {
            const response = await fetch(`${this.apiBaseUrl}/collect-item`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    item_id: itemId,
                }),
            });

            const data = await response.json();

            if (data.success) {
                console.log('[Multiplayer] เก็บไอเทม:', data.item.type);
                return data.item;
            }

            return null;
        } catch (error) {
            console.error('[Multiplayer] เก็บไอเทมล้มเหลว:', error);
            return null;
        }
    }

    /**
     * บันทึกคะแนน (ต้อง auth + wallet)
     */
    async saveScore(score, length) {
        if (!this.playerId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/save-score`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    score: score,
                    length: length,
                }),
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[Multiplayer] บันทึกคะแนนล้มเหลว:', error);
            throw error;
        }
    }

    /**
     * ตรวจสอบ wallet
     */
    async checkWallet() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/check-wallet`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[Multiplayer] ตรวจสอบ wallet ล้มเหลว:', error);
            return { success: false, authenticated: false };
        }
    }

    /**
     * ดึงผู้เล่นคนอื่นทั้งหมด
     */
    getOtherPlayers() {
        return Array.from(this.otherPlayers.values());
    }

    /**
     * ดึงไอเทมทั้งหมด
     */
    getServerItems() {
        return Array.from(this.serverItems.values());
    }

    /**
     * ตั้งค่า callback สำหรับเหตุการณ์ต่างๆ
     */
    setOnPlayerJoined(callback) {
        this.onPlayerJoinedCallback = callback;
    }

    setOnPlayerLeft(callback) {
        this.onPlayerLeftCallback = callback;
    }

    setOnPlayerMoved(callback) {
        this.onPlayerMovedCallback = callback;
    }

    setOnPlayerDied(callback) {
        this.onPlayerDiedCallback = callback;
    }

    setOnItemSpawned(callback) {
        this.onItemSpawnedCallback = callback;
    }

    setOnItemCollected(callback) {
        this.onItemCollectedCallback = callback;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SnakeMultiplayerManager;
} else {
    window.SnakeMultiplayerManager = SnakeMultiplayerManager;
}
