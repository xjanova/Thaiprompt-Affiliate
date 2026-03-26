using System.Collections.Concurrent;
using SnakeGameServer.Core;

namespace SnakeGameServer.Game;

/// <summary>
/// จัดการห้องทั้งหมด — สร้าง/ค้นหา/ลบห้อง (รองรับระบบ Channel)
/// </summary>
public class RoomManager
{
    private readonly ConcurrentDictionary<string, GameRoom> _rooms = new();
    private readonly ServerConfig _config;
    private readonly Random _rng = new();

    /// <summary>
    /// Event: log
    /// </summary>
    public event Action<string>? OnLog;

    /// <summary>
    /// จำนวนห้องทั้งหมด
    /// </summary>
    public int TotalRooms => _rooms.Count;

    /// <summary>
    /// จำนวนห้องที่มีผู้เล่น
    /// </summary>
    public int ActiveRooms => _rooms.Values.Count(r => r.Players.Count > 0);

    /// <summary>
    /// จำนวนผู้เล่นทั้งหมด
    /// </summary>
    public int TotalPlayers => _rooms.Values.Sum(r => r.Players.Count);

    public RoomManager(ServerConfig config)
    {
        _config = config;
    }

    // ==================== Channel System ====================

    /// <summary>
    /// นับจำนวนผู้เล่นใน channel
    /// </summary>
    public int GetChannelPlayerCount(string channel)
    {
        return _rooms.Values
            .Where(r => r.Channel == channel)
            .Sum(r => r.Players.Count);
    }

    /// <summary>
    /// นับจำนวนห้องใน channel
    /// </summary>
    public int GetChannelRoomCount(string channel)
    {
        return _rooms.Values
            .Count(r => r.Channel == channel && r.Players.Count > 0);
    }

    // ==================== Room Management ====================

    /// <summary>
    /// ค้นหาห้องที่มีที่ว่างใน channel ที่กำหนด หรือสร้างใหม่
    /// </summary>
    public GameRoom FindOrCreateRoom(string? requestedRoomId = null, string? channel = null)
    {
        // ตั้งค่า channel default ถ้าไม่ได้ระบุ
        var targetChannel = ValidateChannel(channel);

        // ถ้าระบุ room id มา → หาห้องนั้น (ไม่สนใจ channel)
        if (!string.IsNullOrEmpty(requestedRoomId))
        {
            var found = _rooms.Values.FirstOrDefault(r =>
                (r.RoomId == requestedRoomId || r.RoomCode == requestedRoomId)
                && r.Status != RoomStatus.Finished
                && r.Players.Count < _config.MaxPlayersPerRoom);

            if (found != null) return found;
        }

        // หาห้องที่มีที่ว่างใน channel ที่เลือก (เลือกห้องที่มีคนเยอะสุดก่อน)
        var available = _rooms.Values
            .Where(r => r.Channel == targetChannel
                     && r.Status != RoomStatus.Finished
                     && r.Players.Count < _config.MaxPlayersPerRoom)
            .OrderByDescending(r => r.Players.Count)
            .FirstOrDefault();

        if (available != null) return available;

        // สร้างห้องใหม่ใน channel นี้
        return CreateRoom(targetChannel);
    }

    /// <summary>
    /// สร้างห้องใหม่ใน channel ที่กำหนด
    /// </summary>
    public GameRoom CreateRoom(string? channel = null)
    {
        var targetChannel = ValidateChannel(channel);
        var room = new GameRoom(GenerateRoomCode(), _config)
        {
            Channel = targetChannel
        };
        _rooms.TryAdd(room.RoomId, room);
        OnLog?.Invoke($"Room created: {room.RoomCode} in {targetChannel} (ID: {room.RoomId})");
        return room;
    }

    /// <summary>
    /// ตรวจสอบ channel ว่าถูกต้องหรือไม่ ถ้าไม่ถูก → ใช้ channel แรก
    /// </summary>
    public string ValidateChannel(string? channel)
    {
        var validChannels = _config.GetChannelNames();
        if (!string.IsNullOrWhiteSpace(channel) && validChannels.Contains(channel))
            return channel;
        return validChannels.FirstOrDefault() ?? "CH-1";
    }

    /// <summary>
    /// ดึงห้องตาม ID
    /// </summary>
    public GameRoom? GetRoom(string roomId)
    {
        _rooms.TryGetValue(roomId, out var room);
        return room;
    }

    /// <summary>
    /// ดึงห้องที่มีผู้เล่น (สำหรับ game loop tick)
    /// </summary>
    public IReadOnlyList<GameRoom> GetActiveRooms()
    {
        return _rooms.Values
            .Where(r => r.Players.Count > 0)
            .ToList();
    }

    /// <summary>
    /// ดึงห้องทั้งหมด (สำหรับ admin)
    /// </summary>
    public IReadOnlyList<GameRoom> GetAllRooms()
    {
        return _rooms.Values.ToList();
    }

    /// <summary>
    /// ลบห้องที่ว่างเปล่า (เรียกทุก N วินาที)
    /// </summary>
    public int CleanupEmptyRooms()
    {
        var toRemove = _rooms.Values
            .Where(r => r.Status == RoomStatus.Finished
                     || (r.Players.IsEmpty && (DateTime.UtcNow - r.CreatedAt).TotalMinutes > 1))
            .Select(r => r.RoomId)
            .ToList();

        int removed = 0;
        foreach (var id in toRemove)
        {
            if (_rooms.TryRemove(id, out _))
                removed++;
        }

        if (removed > 0)
            OnLog?.Invoke($"Cleaned up {removed} empty rooms. Active: {ActiveRooms}");

        return removed;
    }

    /// <summary>
    /// สร้าง room code 6 ตัวอักษร
    /// </summary>
    private string GenerateRoomCode()
    {
        const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        string code;
        int attempts = 0;
        do
        {
            code = new string(Enumerable.Range(0, 6)
                .Select(_ => chars[_rng.Next(chars.Length)])
                .ToArray());
            attempts++;
        }
        while (_rooms.Values.Any(r => r.RoomCode == code) && attempts < 100);

        return code;
    }
}
