using System.Text.Json.Serialization;
using SnakeGameServer.Game;

namespace SnakeGameServer.Network.Protocol;

/// <summary>
/// Base class สำหรับทุก client message
/// </summary>
public class BaseClientMessage
{
    [JsonPropertyName("type")]
    public string Type { get; set; } = "";
}

/// <summary>
/// Client ขอเข้าร่วมเกม
/// </summary>
public class JoinMessage : BaseClientMessage
{
    [JsonPropertyName("player_name")]
    public string PlayerName { get; set; } = "Player";

    [JsonPropertyName("skin")]
    public string Skin { get; set; } = "classic";

    [JsonPropertyName("room_id")]
    public string? RoomId { get; set; }
}

/// <summary>
/// Client ส่งทิศทาง (input เดียวที่ client ส่งระหว่างเล่น)
/// </summary>
public class InputMessage : BaseClientMessage
{
    [JsonPropertyName("direction")]
    public Vec3 Direction { get; set; }
}

/// <summary>
/// Client กดบูสท์
/// </summary>
public class BoostMessage : BaseClientMessage
{
    [JsonPropertyName("is_boosting")]
    public bool IsBoosting { get; set; }
}
