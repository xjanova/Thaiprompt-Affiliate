using System.Text.Json.Serialization;
using SnakeGameServer.Game;

namespace SnakeGameServer.Network.Protocol;

// === Data Transfer Objects ===

/// <summary>
/// ข้อมูลผู้เล่นแบบย่อ (ส่งตอน welcome/join)
/// </summary>
public class PlayerDto
{
    [JsonPropertyName("id")]
    public string Id { get; set; } = "";

    [JsonPropertyName("name")]
    public string Name { get; set; } = "";

    [JsonPropertyName("skin")]
    public string Skin { get; set; } = "classic";

    [JsonPropertyName("position")]
    public Vec3 Position { get; set; }

    [JsonPropertyName("direction")]
    public Vec3 Direction { get; set; }

    [JsonPropertyName("score")]
    public int Score { get; set; }

    [JsonPropertyName("length")]
    public int Length { get; set; }

    [JsonPropertyName("is_alive")]
    public bool IsAlive { get; set; } = true;
}

/// <summary>
/// ข้อมูลผู้เล่นที่ส่งทุก tick
/// </summary>
public class PlayerStateDto
{
    [JsonPropertyName("id")]
    public string Id { get; set; } = "";

    [JsonPropertyName("name")]
    public string Name { get; set; } = "";

    [JsonPropertyName("skin")]
    public string Skin { get; set; } = "classic";

    [JsonPropertyName("position")]
    public Vec3 Position { get; set; }

    [JsonPropertyName("direction")]
    public Vec3 Direction { get; set; }

    [JsonPropertyName("score")]
    public int Score { get; set; }

    [JsonPropertyName("length")]
    public int Length { get; set; }

    [JsonPropertyName("is_alive")]
    public bool IsAlive { get; set; }

    [JsonPropertyName("is_boosting")]
    public bool IsBoosting { get; set; }

    /// <summary>
    /// Segment positions (ส่งทุก N tick สำหรับ full body sync)
    /// </summary>
    [JsonPropertyName("segments")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public List<Vec3>? Segments { get; set; }
}

/// <summary>
/// ข้อมูลอาหาร
/// </summary>
public class FoodDto
{
    [JsonPropertyName("id")]
    public string Id { get; set; } = "";

    [JsonPropertyName("position")]
    public Vec3 Position { get; set; }

    [JsonPropertyName("value")]
    public int Value { get; set; } = 1;
}

// === Server Messages ===

/// <summary>
/// ส่งเมื่อผู้เล่นเข้าเกมสำเร็จ
/// </summary>
public class WelcomeMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.Welcome;

    [JsonPropertyName("player_id")]
    public string PlayerId { get; set; } = "";

    [JsonPropertyName("room_id")]
    public string RoomId { get; set; } = "";

    [JsonPropertyName("world_size")]
    public int WorldSize { get; set; }

    [JsonPropertyName("tick_rate")]
    public int TickRate { get; set; }

    [JsonPropertyName("food_list")]
    public List<FoodDto> FoodList { get; set; } = new();

    [JsonPropertyName("player_list")]
    public List<PlayerDto> PlayerList { get; set; } = new();
}

/// <summary>
/// Game state broadcast ทุก tick (30 ครั้ง/วินาที)
/// </summary>
public class StateMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.State;

    [JsonPropertyName("players")]
    public List<PlayerStateDto> Players { get; set; } = new();

    [JsonPropertyName("tick")]
    public long Tick { get; set; }
}

/// <summary>
/// อาหารใหม่ spawn
/// </summary>
public class FoodSpawnMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.FoodSpawn;

    [JsonPropertyName("foods")]
    public List<FoodDto> Foods { get; set; } = new();
}

/// <summary>
/// อาหารถูกเก็บ
/// </summary>
public class FoodCollectedMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.FoodCollected;

    [JsonPropertyName("food_id")]
    public string FoodId { get; set; } = "";

    [JsonPropertyName("player_id")]
    public string PlayerId { get; set; } = "";
}

/// <summary>
/// ผู้เล่นใหม่เข้าห้อง
/// </summary>
public class PlayerJoinMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.PlayerJoin;

    [JsonPropertyName("player")]
    public PlayerDto Player { get; set; } = new();
}

/// <summary>
/// ผู้เล่นออกจากห้อง
/// </summary>
public class PlayerLeaveMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.PlayerLeave;

    [JsonPropertyName("player_id")]
    public string PlayerId { get; set; } = "";
}

/// <summary>
/// ผู้เล่นตาย
/// </summary>
public class PlayerDiedMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.PlayerDied;

    [JsonPropertyName("player_id")]
    public string PlayerId { get; set; } = "";

    [JsonPropertyName("killer_id")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public string? KillerId { get; set; }

    [JsonPropertyName("final_score")]
    public int FinalScore { get; set; }

    [JsonPropertyName("final_length")]
    public int FinalLength { get; set; }

    [JsonPropertyName("dropped_food")]
    public List<FoodDto> DroppedFood { get; set; } = new();
}

/// <summary>
/// ตอบ ping
/// </summary>
public class PongMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.Pong;

    [JsonPropertyName("server_time")]
    public long ServerTime { get; set; }
}

/// <summary>
/// แจ้ง error
/// </summary>
public class ErrorMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.Error;

    [JsonPropertyName("message")]
    public string Message { get; set; } = "";
}

/// <summary>
/// ข้อมูลห้อง
/// </summary>
public class RoomInfoMessage
{
    [JsonPropertyName("type")]
    public string Type => ServerMessageType.RoomInfo;

    [JsonPropertyName("room_id")]
    public string RoomId { get; set; } = "";

    [JsonPropertyName("room_code")]
    public string RoomCode { get; set; } = "";

    [JsonPropertyName("player_count")]
    public int PlayerCount { get; set; }

    [JsonPropertyName("max_players")]
    public int MaxPlayers { get; set; }
}
