using SnakeGameServer.Data;
using SnakeGameServer.Game;
using SnakeGameServer.Network;
using SnakeGameServer.Network.Protocol;

namespace SnakeGameServer.Core;

/// <summary>
/// Top-level orchestrator — จัดการทุกอย่างของ game server
/// รองรับระบบ Channel, API Key, และ Client Status
/// </summary>
public class GameServer
{
    private readonly ServerConfig _config;
    private readonly WebSocketServer _wsServer;
    private readonly MessageRouter _router;
    private readonly RoomManager _rooms;
    private readonly GameLoop _gameLoop;
    private readonly DatabaseWriter _dbWriter;

    private Task? _wsTask;
    private DateTime _startTime;

    // === Player → Room mapping ===
    private readonly Dictionary<string, (string roomId, string playerId)> _clientPlayerMap = new();
    private readonly object _mapLock = new();

    // === Client Status Tracking ===
    private readonly Dictionary<string, ClientStatusInfo> _clientStatusMap = new();
    private readonly object _statusLock = new();

    /// <summary>
    /// Event: log message
    /// </summary>
    public event Action<string>? OnLog;

    /// <summary>
    /// Event: server state changed
    /// </summary>
    public event Action? OnStateChanged;

    // === Properties ===
    public bool IsRunning { get; private set; }
    public ServerConfig Config => _config;
    public RoomManager Rooms => _rooms;
    public WebSocketServer Network => _wsServer;
    public GameLoop Loop => _gameLoop;
    public DatabaseWriter Database => _dbWriter;

    // === Statistics ===
    public int TotalConnections { get; private set; }
    public int PeakPlayers { get; private set; }
    public int RejectedConnections { get; private set; }
    public TimeSpan Uptime => IsRunning ? DateTime.UtcNow - _startTime : TimeSpan.Zero;

    public GameServer(ServerConfig? config = null)
    {
        _config = config ?? new ServerConfig();

        _rooms = new RoomManager(_config);
        _wsServer = new WebSocketServer();
        _router = new MessageRouter();
        _gameLoop = new GameLoop(_rooms, _config.TickRate);
        _dbWriter = new DatabaseWriter(_config.DbProvider, _config.ConnectionString);

        WireEvents();
    }

    /// <summary>
    /// เริ่ม server
    /// </summary>
    public async Task StartAsync()
    {
        if (IsRunning) return;

        Log($"Starting Snake Game Server on port {_config.Port}...");

        // 1. Start database
        _dbWriter.Start();

        // 2. Start game loop
        _gameLoop.Start();

        // 3. Start WebSocket server (non-blocking)
        _wsTask = Task.Run(() => _wsServer.StartAsync(_config.Port));

        IsRunning = true;
        _startTime = DateTime.UtcNow;

        Log($"Server started! Listening on port {_config.Port}");
        Log($"  Tick Rate: {_config.TickRate}/sec");
        Log($"  World Size: {_config.WorldSize}x{_config.WorldSize}");
        Log($"  Max Players/Room: {_config.MaxPlayersPerRoom}");
        Log($"  Channels: {_config.MaxChannels} ({string.Join(", ", _config.GetChannelNames())})");
        Log($"  API Key: {_config.ApiKey}");
        Log($"  Require API Key: {_config.RequireApiKey}");
        Log($"  Database: {_config.DbProvider}");

        OnStateChanged?.Invoke();
    }

    /// <summary>
    /// หยุด server
    /// </summary>
    public async Task StopAsync()
    {
        if (!IsRunning) return;

        Log("Stopping server...");

        // 1. Stop game loop
        _gameLoop.Stop();

        // 2. Stop WebSocket server
        await _wsServer.StopAsync();

        // 3. Stop database writer
        _dbWriter.Dispose();

        // 4. Clear client status
        lock (_statusLock)
        {
            _clientStatusMap.Clear();
        }

        IsRunning = false;

        Log("Server stopped.");
        OnStateChanged?.Invoke();
    }

    /// <summary>
    /// Kick ผู้เล่น
    /// </summary>
    public async Task KickPlayerAsync(string playerId)
    {
        lock (_mapLock)
        {
            var entry = _clientPlayerMap.FirstOrDefault(kv => kv.Value.playerId == playerId);
            if (entry.Key != null)
            {
                HandlePlayerLeave(entry.Key);
            }
        }
    }

    /// <summary>
    /// สุ่ม API Key ใหม่
    /// </summary>
    public void RegenerateApiKey()
    {
        _config.RegenerateApiKey();
        Log($"API Key regenerated: {_config.ApiKey}");
        OnStateChanged?.Invoke();
    }

    /// <summary>
    /// อัพเดท config (ต้อง restart)
    /// </summary>
    public void UpdateConfig(Action<ServerConfig> update)
    {
        update(_config);
        OnStateChanged?.Invoke();
    }

    /// <summary>
    /// ดึงข้อมูลสถานะ client ทั้งหมด (สำหรับ UI)
    /// </summary>
    public List<ClientStatusInfo> GetClientStatuses()
    {
        lock (_statusLock)
        {
            return _clientStatusMap.Values.ToList();
        }
    }

    // ==================== Event Wiring ====================

    private void WireEvents()
    {
        // WebSocket events
        _wsServer.OnClientConnected += HandleClientConnected;
        _wsServer.OnClientDisconnected += HandleClientDisconnected;
        _wsServer.OnMessageReceived += HandleMessage;
        _wsServer.OnLog += Log;

        // Router events
        _router.OnJoin += HandleJoin;
        _router.OnInput += HandleInput;
        _router.OnBoost += HandleBoost;
        _router.OnPing += HandlePing;
        _router.OnLeave += (client) => HandlePlayerLeave(client.Id);
        _router.OnLog += Log;

        // Game loop events
        _gameLoop.OnLog += Log;

        // Room manager events
        _rooms.OnLog += Log;

        // Database events
        _dbWriter.OnLog += Log;
    }

    // ==================== Connection Handlers ====================

    private void HandleClientConnected(ClientConnection client)
    {
        TotalConnections++;

        // Track client status
        lock (_statusLock)
        {
            _clientStatusMap[client.Id] = new ClientStatusInfo
            {
                ClientId = client.Id,
                IpAddress = client.RemoteEndpoint,
                ConnectedAt = client.ConnectedAt,
                Status = "Connected",
                IsAuthenticated = false
            };
        }

        // ส่ง channel list ไปให้ client ทันทีหลังเชื่อมต่อ
        SendChannelList(client);

        Log($"Client connected: {client.Id} from {client.RemoteEndpoint}");
        OnStateChanged?.Invoke();
    }

    private void HandleClientDisconnected(ClientConnection client, string reason)
    {
        HandlePlayerLeave(client.Id);

        // Remove client status
        lock (_statusLock)
        {
            _clientStatusMap.Remove(client.Id);
        }

        OnStateChanged?.Invoke();
    }

    private void HandleMessage(ClientConnection client, string rawJson)
    {
        _router.Route(client, rawJson);
    }

    // ==================== Channel List ====================

    /// <summary>
    /// ส่งรายการ channel ไปให้ client (หลังเชื่อมต่อ)
    /// </summary>
    private void SendChannelList(ClientConnection client)
    {
        var channels = _config.GetChannelNames().Select(ch => new ChannelInfoDto
        {
            Name = ch,
            PlayerCount = _rooms.GetChannelPlayerCount(ch),
            RoomCount = _rooms.GetChannelRoomCount(ch),
            MaxPlayersPerRoom = _config.MaxPlayersPerRoom
        }).ToList();

        var msg = new ChannelListMessage
        {
            Channels = channels,
            RequireApiKey = _config.RequireApiKey
        };

        var json = MessageSerializer.Serialize(msg);
        _ = client.SendAsync(json);
    }

    // ==================== Game Handlers ====================

    private void HandleJoin(ClientConnection client, JoinMessage msg)
    {
        // === ตรวจสอบ API Key ===
        if (_config.RequireApiKey)
        {
            if (string.IsNullOrWhiteSpace(msg.ApiKey) ||
                !string.Equals(msg.ApiKey.Trim(), _config.ApiKey, StringComparison.OrdinalIgnoreCase))
            {
                RejectedConnections++;
                Log($"REJECTED: Client {client.Id} ({client.RemoteEndpoint}) — Invalid API Key: '{msg.ApiKey ?? "(empty)"}'");

                var authError = MessageSerializer.Serialize(new AuthErrorMessage
                {
                    Message = "API Key ไม่ถูกต้อง กรุณาตรวจสอบ API Key จาก Server Admin",
                    Code = "INVALID_API_KEY"
                });
                _ = client.SendAsync(authError);

                // อัพเดท client status
                lock (_statusLock)
                {
                    if (_clientStatusMap.TryGetValue(client.Id, out var status))
                    {
                        status.Status = "Rejected (Bad API Key)";
                    }
                }

                OnStateChanged?.Invoke();
                return;
            }
        }

        // Validate name
        var name = string.IsNullOrWhiteSpace(msg.PlayerName)
            ? "Player"
            : msg.PlayerName.Trim();
        if (name.Length > 20) name = name[..20];

        // Validate skin (รองรับทั้ง preset เช่น "classic" และ custom hex เช่น "#ff0000,#00ff00,#0000ff")
        var skin = string.IsNullOrWhiteSpace(msg.Skin) ? "classic" : msg.Skin.Trim();
        if (skin.Length > 50) skin = "classic"; // ป้องกัน payload ยาวเกิน

        // Validate channel
        var channel = _rooms.ValidateChannel(msg.Channel);

        // Find or create room ใน channel ที่เลือก
        var room = _rooms.FindOrCreateRoom(msg.RoomId, channel);

        // Add player to room
        var player = room.AddPlayer(name, skin);
        if (player == null)
        {
            var errorJson = MessageSerializer.Serialize(new ErrorMessage
            {
                Message = "ห้องเต็ม ไม่สามารถเข้าร่วมได้"
            });
            _ = client.SendAsync(errorJson);
            return;
        }

        // Link connection ↔ player
        player.Connection = client;
        client.PlayerId = player.Id;
        client.RoomId = room.RoomId;

        lock (_mapLock)
        {
            _clientPlayerMap[client.Id] = (room.RoomId, player.Id);
        }

        // อัพเดท client status
        lock (_statusLock)
        {
            if (_clientStatusMap.TryGetValue(client.Id, out var status))
            {
                status.Status = "Playing";
                status.IsAuthenticated = true;
                status.PlayerName = name;
                status.Channel = channel;
                status.RoomCode = room.RoomCode;
            }
        }

        // Wire room events สำหรับส่ง message
        room.OnSendToPlayer -= SendToPlayer;
        room.OnSendToPlayer += SendToPlayer;
        room.OnPlayerDied -= HandlePlayerDiedEvent;
        room.OnPlayerDied += HandlePlayerDiedEvent;
        room.OnLog -= Log;
        room.OnLog += Log;

        // Send welcome (เพิ่ม channel info)
        var welcome = room.CreateWelcomeMessage(player);
        welcome.Channel = channel;
        var welcomeJson = MessageSerializer.Serialize(welcome);
        _ = client.SendAsync(welcomeJson);

        Log($"Player '{name}' joined {channel}/{room.RoomCode} (API Key: OK)");

        // Update peak
        var totalPlayers = _rooms.TotalPlayers;
        if (totalPlayers > PeakPlayers) PeakPlayers = totalPlayers;

        OnStateChanged?.Invoke();
    }

    private void HandleInput(ClientConnection client, InputMessage msg)
    {
        var player = FindPlayer(client.Id);
        if (player == null) return;

        // Validate direction
        var dir = msg.Direction.Normalized();
        if (double.IsNaN(dir.X) || double.IsNaN(dir.Z)) return;

        // Rate limit check
        var now = DateTime.UtcNow;
        if ((now - player.InputCountResetTime).TotalSeconds >= 1)
        {
            player.InputCountThisSecond = 0;
            player.InputCountResetTime = now;
        }
        player.InputCountThisSecond++;

        if (player.InputCountThisSecond > _config.MaxInputsPerSecond)
            return; // Rate limited

        // Apply direction (game loop จะ process ใน tick ถัดไป)
        player.Snake.TargetDirection = dir;
        player.LastInputTime = now;
    }

    private void HandleBoost(ClientConnection client, BoostMessage msg)
    {
        var player = FindPlayer(client.Id);
        if (player == null) return;

        player.Snake.IsBoosting = msg.IsBoosting;
    }

    private void HandlePing(ClientConnection client)
    {
        client.LastPingTime = DateTime.UtcNow;

        // อัพเดท last ping ใน client status
        lock (_statusLock)
        {
            if (_clientStatusMap.TryGetValue(client.Id, out var status))
            {
                status.LastPingAt = DateTime.UtcNow;
            }
        }

        var pong = MessageSerializer.Serialize(new PongMessage
        {
            ServerTime = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()
        });
        _ = client.SendAsync(pong);
    }

    private void HandlePlayerLeave(string clientId)
    {
        string? roomId;
        string? playerId;

        lock (_mapLock)
        {
            if (!_clientPlayerMap.TryGetValue(clientId, out var entry))
                return;

            roomId = entry.roomId;
            playerId = entry.playerId;
            _clientPlayerMap.Remove(clientId);
        }

        // อัพเดท client status
        lock (_statusLock)
        {
            if (_clientStatusMap.TryGetValue(clientId, out var status))
            {
                status.Status = "Disconnected";
            }
        }

        var room = _rooms.GetRoom(roomId);
        if (room == null) return;

        // ดึงข้อมูลผู้เล่นก่อนลบ (สำหรับ save stats)
        if (room.Players.TryGetValue(playerId, out var player))
        {
            var duration = (int)player.PlayDuration.TotalSeconds;
            _dbWriter.SaveGameHistory(room.RoomCode, player.Name, player.Snake.Score,
                player.Snake.Length, duration, player.Kills, "left");
            _dbWriter.UpdatePlayerStats(player.Name, player.Snake.Score,
                player.Snake.Length, player.Kills, duration);
        }

        room.RemovePlayer(playerId);
        OnStateChanged?.Invoke();
    }

    // ==================== Event Handlers ====================

    private void SendToPlayer(string playerId, string json)
    {
        ClientConnection? conn = null;
        lock (_mapLock)
        {
            var entry = _clientPlayerMap.FirstOrDefault(kv => kv.Value.playerId == playerId);
            if (entry.Key != null)
            {
                conn = _wsServer.Clients.FirstOrDefault(c => c.Id == entry.Key);
            }
        }

        if (conn != null && conn.IsAlive)
        {
            _ = conn.SendAsync(json);
        }
    }

    private void HandlePlayerDiedEvent(Player player, string? killerId, int score, int length)
    {
        // Find room code
        string roomCode = "UNKNOWN";
        lock (_mapLock)
        {
            var entry = _clientPlayerMap.FirstOrDefault(kv => kv.Value.playerId == player.Id);
            if (entry.Key != null)
            {
                var room = _rooms.GetRoom(entry.Value.roomId);
                if (room != null) roomCode = room.RoomCode;
            }
        }

        var duration = (int)player.PlayDuration.TotalSeconds;

        // Save to database
        _dbWriter.SaveGameHistory(roomCode, player.Name, score, length,
            duration, player.Kills, player.DeathReason ?? "unknown");
        _dbWriter.UpdatePlayerStats(player.Name, score, length, player.Kills, duration);
        _dbWriter.TryAddToLeaderboard(player.Name, score, length, player.Kills, duration);
    }

    // ==================== Helpers ====================

    private Player? FindPlayer(string clientId)
    {
        lock (_mapLock)
        {
            if (!_clientPlayerMap.TryGetValue(clientId, out var entry))
                return null;

            var room = _rooms.GetRoom(entry.roomId);
            if (room == null) return null;

            room.Players.TryGetValue(entry.playerId, out var player);
            return player;
        }
    }

    private void Log(string message)
    {
        var timestamp = DateTime.Now.ToString("HH:mm:ss");
        OnLog?.Invoke($"[{timestamp}] {message}");
    }
}

// ==================== Client Status Info ====================

/// <summary>
/// ข้อมูลสถานะ client ที่เชื่อมต่อ (สำหรับแสดงใน Admin UI)
/// </summary>
public class ClientStatusInfo
{
    public string ClientId { get; set; } = "";
    public string IpAddress { get; set; } = "";
    public DateTime ConnectedAt { get; set; }
    public DateTime? LastPingAt { get; set; }
    public string Status { get; set; } = "Connected";
    public bool IsAuthenticated { get; set; }
    public string? PlayerName { get; set; }
    public string? Channel { get; set; }
    public string? RoomCode { get; set; }

    /// <summary>
    /// ระยะเวลาที่เชื่อมต่อ
    /// </summary>
    public TimeSpan ConnectionDuration => DateTime.UtcNow - ConnectedAt;

    /// <summary>
    /// ออนไลน์อยู่หรือไม่ (ดูจาก last ping ภายใน 10 วินาที)
    /// </summary>
    public bool IsOnline => LastPingAt.HasValue
        ? (DateTime.UtcNow - LastPingAt.Value).TotalSeconds < 10
        : (DateTime.UtcNow - ConnectedAt).TotalSeconds < 10;
}
