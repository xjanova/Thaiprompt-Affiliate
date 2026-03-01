/**
 * Snake.io WebSocket Client
 *
 * เชื่อมต่อกับ C# Game Server ผ่าน WebSocket
 * Server-authoritative: client ส่งแค่ direction, server คำนวณทุกอย่าง
 * รับ state 30 tick/sec แล้ว interpolate สำหรับ 60fps render
 */
class SnakeWebSocketClient {
    constructor() {
        this.ws = null;
        this.connected = false;
        this.playerId = null;
        this.roomId = null;
        this.worldSize = 200;

        // State storage
        this._otherPlayers = new Map();     // id -> { current, previous, lastUpdate }
        this._myState = null;               // server-authoritative state ของตัวเอง
        this._foods = new Map();            // id -> { position, value, type }
        this._leaderboard = [];

        // Interpolation
        this._tickIntervalMs = 33.33;       // 30 tick/sec
        this._lastStateTime = 0;

        // Callbacks
        this.onConnected = null;
        this.onDisconnected = null;
        this.onError = null;
        this.onWelcome = null;              // (data) => {} เมื่อเข้าเกมสำเร็จ
        this.onStateUpdate = null;          // (players, tick) => {} ทุก tick
        this.onFoodSpawn = null;            // (foods) => {}
        this.onFoodCollected = null;        // (foodId, playerId) => {}
        this.onPlayerJoined = null;         // (playerData) => {}
        this.onPlayerLeft = null;           // (playerId) => {}
        this.onPlayerDied = null;           // (playerId, killerId, droppedFood) => {}
        this.onMyDeath = null;              // (killerId) => {} เมื่อตัวเราตาย

        // Ping tracking
        this._pingStart = 0;
        this._latencyMs = 0;
        this._pingInterval = null;

        // Reconnect
        this._reconnectAttempts = 0;
        this._maxReconnectAttempts = 5;
        this._reconnectDelay = 2000;
        this._serverUrl = '';
        this._playerName = '';
        this._skin = '';
        this._autoReconnect = true;
    }

    // ==================== Connection ====================

    /**
     * เชื่อมต่อกับ game server
     * @param {string} ip - IP address ของ server
     * @param {number} port - Port ของ server
     * @returns {Promise<boolean>}
     */
    async connect(ip, port) {
        return new Promise((resolve, reject) => {
            try {
                const url = `ws://${ip}:${port}/`;
                this._serverUrl = url;

                if (this.ws) {
                    this.ws.onclose = null;
                    this.ws.close();
                }

                this.ws = new WebSocket(url);
                this.ws.binaryType = 'arraybuffer';

                const timeout = setTimeout(() => {
                    if (this.ws && this.ws.readyState !== WebSocket.OPEN) {
                        this.ws.close();
                        reject(new Error('Connection timeout'));
                    }
                }, 5000);

                this.ws.onopen = () => {
                    clearTimeout(timeout);
                    this.connected = true;
                    this._reconnectAttempts = 0;
                    console.log('[WS] Connected to', url);

                    this._startPingLoop();

                    if (this.onConnected) this.onConnected();
                    resolve(true);
                };

                this.ws.onmessage = (event) => {
                    this._handleMessage(event.data);
                };

                this.ws.onerror = (err) => {
                    clearTimeout(timeout);
                    console.error('[WS] Error:', err);
                    if (this.onError) this.onError(err);
                };

                this.ws.onclose = (event) => {
                    clearTimeout(timeout);
                    this.connected = false;
                    this._stopPingLoop();
                    console.log('[WS] Disconnected:', event.code, event.reason);

                    if (this.onDisconnected) this.onDisconnected(event.code, event.reason);

                    // Auto-reconnect
                    if (this._autoReconnect && this._reconnectAttempts < this._maxReconnectAttempts) {
                        this._reconnectAttempts++;
                        const delay = this._reconnectDelay * this._reconnectAttempts;
                        console.log(`[WS] Reconnecting in ${delay}ms (attempt ${this._reconnectAttempts})`);
                        setTimeout(() => this._tryReconnect(), delay);
                    }

                    if (this.ws && this.ws.readyState !== WebSocket.OPEN) {
                        reject(new Error('Connection closed'));
                    }
                };
            } catch (err) {
                reject(err);
            }
        });
    }

    /**
     * เข้าร่วมเกม — ส่ง join message ไปที่ server
     * @param {string} playerName
     * @param {string} skin
     * @param {string|null} roomId - room code (optional)
     * @returns {Promise<object>} welcome data
     */
    join(playerName, skin, roomId = null) {
        return new Promise((resolve, reject) => {
            if (!this.connected) {
                reject(new Error('Not connected'));
                return;
            }

            this._playerName = playerName;
            this._skin = skin;

            // Listen for welcome message
            const prevOnWelcome = this.onWelcome;
            this.onWelcome = (data) => {
                this.onWelcome = prevOnWelcome;
                if (prevOnWelcome) prevOnWelcome(data);
                resolve(data);
            };

            // Send join
            this._send({
                type: 'join',
                player_name: playerName,
                skin: skin,
                room_id: roomId
            });

            // Timeout
            setTimeout(() => {
                if (!this.playerId) {
                    reject(new Error('Join timeout'));
                }
            }, 5000);
        });
    }

    // ==================== Input (Client → Server) ====================

    /**
     * ส่งทิศทาง (input หลักเพียงอย่างเดียวที่ client ส่ง)
     * @param {{x:number, y:number, z:number}} direction
     */
    sendDirection(direction) {
        if (!this.connected || !this.playerId) return;

        this._send({
            type: 'input',
            direction: {
                x: direction.x || 0,
                y: direction.y || 0,
                z: direction.z || 0
            }
        });
    }

    /**
     * ส่งสถานะ boost
     * @param {boolean} isBoosting
     */
    sendBoost(isBoosting) {
        if (!this.connected || !this.playerId) return;

        this._send({
            type: 'boost',
            is_boosting: !!isBoosting
        });
    }

    /**
     * ออกจากเกม
     */
    leave() {
        this._autoReconnect = false;
        if (this.connected) {
            this._send({ type: 'leave' });
        }
        this.disconnect();
    }

    /**
     * ตัดการเชื่อมต่อ
     */
    disconnect() {
        this._autoReconnect = false;
        this._stopPingLoop();

        if (this.ws) {
            this.ws.onclose = null;
            this.ws.close();
            this.ws = null;
        }

        this.connected = false;
        this.playerId = null;
        this.roomId = null;
        this._otherPlayers.clear();
        this._foods.clear();
        this._myState = null;
    }

    // ==================== State Getters ====================

    /**
     * ดึงข้อมูลผู้เล่นอื่นทั้งหมด (interpolated)
     * @returns {Array<object>}
     */
    getOtherPlayers() {
        const now = performance.now();
        const result = [];

        for (const [id, data] of this._otherPlayers) {
            if (id === this.playerId) continue;
            if (!data.current) continue;

            // Interpolate ระหว่าง previous กับ current state
            const player = this._interpolatePlayer(data, now);
            if (player) {
                result.push(player);
            }
        }

        return result;
    }

    /**
     * ดึง server-authoritative state ของตัวเราเอง
     * @returns {object|null}
     */
    getMyState() {
        if (!this.playerId || !this._otherPlayers.has(this.playerId)) {
            return this._myState;
        }

        const data = this._otherPlayers.get(this.playerId);
        if (!data || !data.current) return this._myState;

        return {
            id: this.playerId,
            position: data.current.position,
            direction: data.current.direction,
            score: data.current.score,
            length: data.current.length,
            alive: data.current.alive,
            boosting: data.current.boosting,
            segments: data.current.segments || null
        };
    }

    /**
     * ดึงรายการอาหารทั้งหมด
     * @returns {Map}
     */
    getFoods() {
        return this._foods;
    }

    /**
     * ตรวจสอบว่าออนไลน์อยู่
     * @returns {boolean}
     */
    isOnline() {
        return this.connected && this.playerId != null;
    }

    /**
     * ดึง latency ปัจจุบัน
     * @returns {number} ms
     */
    getLatency() {
        return this._latencyMs;
    }

    // ==================== Message Handling ====================

    _handleMessage(rawData) {
        try {
            const msg = JSON.parse(rawData);

            switch (msg.type) {
                case 'welcome':
                    this._handleWelcome(msg);
                    break;
                case 'state':
                    this._handleState(msg);
                    break;
                case 'food_spawn':
                    this._handleFoodSpawn(msg);
                    break;
                case 'food_collected':
                    this._handleFoodCollected(msg);
                    break;
                case 'player_join':
                    this._handlePlayerJoin(msg);
                    break;
                case 'player_leave':
                    this._handlePlayerLeave(msg);
                    break;
                case 'player_died':
                    this._handlePlayerDied(msg);
                    break;
                case 'pong':
                    this._handlePong(msg);
                    break;
                case 'error':
                    console.error('[WS] Server error:', msg.message);
                    if (this.onError) this.onError(new Error(msg.message));
                    break;
                default:
                    console.warn('[WS] Unknown message type:', msg.type);
            }
        } catch (err) {
            console.error('[WS] Parse error:', err);
        }
    }

    _handleWelcome(msg) {
        this.playerId = msg.player_id;
        this.roomId = msg.room_id;
        this.worldSize = msg.world_size || 200;

        // Server ส่ง tick_rate (30) → คำนวณ interval (33.33ms)
        if (msg.tick_rate) {
            this._tickIntervalMs = 1000 / msg.tick_rate;
        } else {
            this._tickIntervalMs = msg.tick_interval_ms || 33.33;
        }

        console.log(`[WS] Welcome! Player: ${this.playerId}, Room: ${this.roomId}, World: ${this.worldSize}`);

        // Initialize food from welcome (server ส่ง food_list)
        const foods = msg.food_list || msg.foods || [];
        if (foods.length > 0) {
            this._foods.clear();
            for (const f of foods) {
                this._foods.set(f.id, {
                    id: f.id,
                    position: f.position,
                    value: f.value || 1,
                    type: f.type || 'normal'
                });
            }
        }

        // Initialize players from welcome (server ส่ง player_list)
        const players = msg.player_list || msg.players || [];
        if (players.length > 0) {
            for (const p of players) {
                this._otherPlayers.set(p.id, {
                    current: this._normalizePlayerState(p),
                    previous: null,
                    lastUpdate: performance.now()
                });
            }
        }

        if (this.onWelcome) {
            this.onWelcome({
                playerId: this.playerId,
                roomId: this.roomId,
                worldSize: this.worldSize,
                foods: Array.from(this._foods.values()),
                players: players
            });
        }
    }

    _handleState(msg) {
        const now = performance.now();
        this._lastStateTime = now;

        if (!msg.players) return;

        // อัพเดท state ทุกผู้เล่น
        const currentIds = new Set();

        for (const p of msg.players) {
            currentIds.add(p.id);
            const normalized = this._normalizePlayerState(p);

            if (this._otherPlayers.has(p.id)) {
                const existing = this._otherPlayers.get(p.id);
                existing.previous = existing.current;
                existing.current = normalized;
                existing.lastUpdate = now;
            } else {
                this._otherPlayers.set(p.id, {
                    current: normalized,
                    previous: null,
                    lastUpdate: now
                });
            }
        }

        // ลบผู้เล่นที่ไม่อยู่ใน state แล้ว
        for (const [id] of this._otherPlayers) {
            if (!currentIds.has(id) && id !== this.playerId) {
                this._otherPlayers.delete(id);
            }
        }

        // อัพเดท myState
        if (this.playerId && this._otherPlayers.has(this.playerId)) {
            const myData = this._otherPlayers.get(this.playerId);
            this._myState = myData.current;
        }

        if (this.onStateUpdate) {
            this.onStateUpdate(msg.players, msg.tick);
        }
    }

    _handleFoodSpawn(msg) {
        if (msg.foods) {
            for (const f of msg.foods) {
                this._foods.set(f.id, {
                    id: f.id,
                    position: f.position,
                    value: f.value || 1,
                    type: f.type || 'normal'
                });
            }
        }
        if (this.onFoodSpawn) this.onFoodSpawn(msg.foods);
    }

    _handleFoodCollected(msg) {
        this._foods.delete(msg.food_id);
        if (this.onFoodCollected) this.onFoodCollected(msg.food_id, msg.player_id);
    }

    _handlePlayerJoin(msg) {
        // Server ส่ง { type: "player_join", player: { id, name, ... } }
        const playerData = msg.player || msg;
        console.log(`[WS] Player joined: ${playerData.name} (${playerData.id})`);
        if (this.onPlayerJoined) this.onPlayerJoined(playerData);
    }

    _handlePlayerLeave(msg) {
        const id = msg.player_id;
        this._otherPlayers.delete(id);
        console.log(`[WS] Player left: ${id}`);
        if (this.onPlayerLeft) this.onPlayerLeft(id);
    }

    _handlePlayerDied(msg) {
        const id = msg.player_id;
        console.log(`[WS] Player died: ${id}, killer: ${msg.killer_id}`);

        // ถ้าเราตาย
        if (id === this.playerId) {
            if (this.onMyDeath) this.onMyDeath(msg.killer_id);
        }

        // เพิ่ม dropped food
        if (msg.dropped_food) {
            for (const f of msg.dropped_food) {
                this._foods.set(f.id, {
                    id: f.id,
                    position: f.position,
                    value: f.value || 2,
                    type: 'dropped'
                });
            }
        }

        if (this.onPlayerDied) this.onPlayerDied(id, msg.killer_id, msg.dropped_food);
    }

    _handlePong(msg) {
        this._latencyMs = performance.now() - this._pingStart;
    }

    // ==================== Interpolation ====================

    /**
     * Interpolate ตำแหน่งผู้เล่นระหว่าง 2 state เพื่อ smooth rendering
     */
    _interpolatePlayer(data, now) {
        const { current, previous, lastUpdate } = data;
        if (!current) return null;

        // ถ้าไม่มี previous state ใช้ current ตรงๆ
        if (!previous) {
            return {
                id: current.id,
                name: current.name,
                skin: current.skin,
                position: { ...current.position },
                direction: { ...current.direction },
                score: current.score,
                length: current.length,
                alive: current.alive,
                boosting: current.boosting,
                segments: current.segments
            };
        }

        // คำนวณ interpolation factor (0.0 - 1.0+)
        const elapsed = now - lastUpdate;
        const t = Math.min(elapsed / this._tickIntervalMs, 2.0); // cap ที่ 2.0 เพื่อป้องกัน overshoot

        // Lerp position
        const position = {
            x: previous.position.x + (current.position.x - previous.position.x) * t,
            y: previous.position.y + (current.position.y - previous.position.y) * t,
            z: previous.position.z + (current.position.z - previous.position.z) * t
        };

        // ใช้ direction ล่าสุด
        const direction = { ...current.direction };

        // Interpolate segments ถ้ามี
        let segments = current.segments;
        if (previous.segments && current.segments &&
            previous.segments.length === current.segments.length) {
            segments = current.segments.map((seg, i) => {
                const prevSeg = previous.segments[i];
                if (!prevSeg) return seg;
                return {
                    x: prevSeg.x + (seg.x - prevSeg.x) * t,
                    y: prevSeg.y + (seg.y - prevSeg.y) * t,
                    z: prevSeg.z + (seg.z - prevSeg.z) * t
                };
            });
        }

        return {
            id: current.id,
            name: current.name,
            skin: current.skin,
            position,
            direction,
            score: current.score,
            length: current.length,
            alive: current.alive,
            boosting: current.boosting,
            segments
        };
    }

    /**
     * Normalize player state จาก server format
     * Server ส่ง is_alive, is_boosting (snake_case) → normalize เป็น alive, boosting
     */
    _normalizePlayerState(p) {
        return {
            id: p.id,
            name: p.name || '',
            skin: p.skin || 'classic',
            position: p.position || p.pos || { x: 0, y: 0, z: 0 },
            direction: p.direction || p.dir || { x: 1, y: 0, z: 0 },
            score: p.score || 0,
            length: p.length || p.len || 5,
            alive: (p.is_alive !== undefined ? p.is_alive : p.alive) !== false,
            boosting: p.is_boosting || p.boosting || p.boost || false,
            segments: p.segments || null
        };
    }

    // ==================== Internal ====================

    _send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        }
    }

    _startPingLoop() {
        this._stopPingLoop();
        this._pingInterval = setInterval(() => {
            if (this.connected) {
                this._pingStart = performance.now();
                this._send({ type: 'ping' });
            }
        }, 3000);
    }

    _stopPingLoop() {
        if (this._pingInterval) {
            clearInterval(this._pingInterval);
            this._pingInterval = null;
        }
    }

    async _tryReconnect() {
        if (this.connected) return;

        try {
            const url = new URL(this._serverUrl);
            await this.connect(url.hostname, url.port || 8080);

            // Re-join ถ้ามีข้อมูลเดิม
            if (this._playerName) {
                await this.join(this._playerName, this._skin, this.roomId);
                console.log('[WS] Reconnected and rejoined');
            }
        } catch (err) {
            console.warn('[WS] Reconnect failed:', err.message);
        }
    }
}

// Export สำหรับ browser
if (typeof window !== 'undefined') {
    window.SnakeWebSocketClient = SnakeWebSocketClient;
}
