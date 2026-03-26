namespace SnakeGameServer.Network.Protocol;

/// <summary>
/// ประเภทข้อความจาก Client → Server
/// </summary>
public static class ClientMessageType
{
    public const string Join = "join";
    public const string Input = "input";
    public const string Boost = "boost";
    public const string Ping = "ping";
    public const string Leave = "leave";
}

/// <summary>
/// ประเภทข้อความจาก Server → Client
/// </summary>
public static class ServerMessageType
{
    public const string Welcome = "welcome";
    public const string State = "state";
    public const string FoodSpawn = "food_spawn";
    public const string FoodCollected = "food_collected";
    public const string PlayerJoin = "player_join";
    public const string PlayerLeave = "player_leave";
    public const string PlayerDied = "player_died";
    public const string Pong = "pong";
    public const string Error = "error";
    public const string RoomInfo = "room_info";
    public const string ChannelList = "channel_list";
    public const string AuthError = "auth_error";
}
