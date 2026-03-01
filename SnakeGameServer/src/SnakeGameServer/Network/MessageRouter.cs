using System.Text.Json;
using SnakeGameServer.Network.Protocol;

namespace SnakeGameServer.Network;

/// <summary>
/// Route messages จาก client ไปยัง handler ที่ถูกต้อง
/// </summary>
public class MessageRouter
{
    /// <summary>
    /// Event: ผู้เล่นขอเข้าร่วมเกม
    /// </summary>
    public event Action<ClientConnection, JoinMessage>? OnJoin;

    /// <summary>
    /// Event: ผู้เล่นส่งทิศทาง
    /// </summary>
    public event Action<ClientConnection, InputMessage>? OnInput;

    /// <summary>
    /// Event: ผู้เล่นกดบูสท์
    /// </summary>
    public event Action<ClientConnection, BoostMessage>? OnBoost;

    /// <summary>
    /// Event: ผู้เล่นส่ง ping
    /// </summary>
    public event Action<ClientConnection>? OnPing;

    /// <summary>
    /// Event: ผู้เล่นออกจากเกม
    /// </summary>
    public event Action<ClientConnection>? OnLeave;

    /// <summary>
    /// Event: log
    /// </summary>
    public event Action<string>? OnLog;

    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true
    };

    /// <summary>
    /// Route ข้อความ JSON ไปยัง handler ที่ถูกต้อง
    /// </summary>
    public void Route(ClientConnection client, string rawJson)
    {
        try
        {
            var type = MessageSerializer.GetMessageType(rawJson);
            if (type == null)
            {
                OnLog?.Invoke($"Invalid message (no type): {rawJson[..Math.Min(100, rawJson.Length)]}");
                return;
            }

            switch (type)
            {
                case ClientMessageType.Join:
                    var join = JsonSerializer.Deserialize<JoinMessage>(rawJson, JsonOptions);
                    if (join != null) OnJoin?.Invoke(client, join);
                    break;

                case ClientMessageType.Input:
                    var input = JsonSerializer.Deserialize<InputMessage>(rawJson, JsonOptions);
                    if (input != null) OnInput?.Invoke(client, input);
                    break;

                case ClientMessageType.Boost:
                    var boost = JsonSerializer.Deserialize<BoostMessage>(rawJson, JsonOptions);
                    if (boost != null) OnBoost?.Invoke(client, boost);
                    break;

                case ClientMessageType.Ping:
                    OnPing?.Invoke(client);
                    break;

                case ClientMessageType.Leave:
                    OnLeave?.Invoke(client);
                    break;

                default:
                    OnLog?.Invoke($"Unknown message type: {type}");
                    break;
            }
        }
        catch (JsonException ex)
        {
            OnLog?.Invoke($"JSON parse error from {client.Id}: {ex.Message}");
        }
    }
}
