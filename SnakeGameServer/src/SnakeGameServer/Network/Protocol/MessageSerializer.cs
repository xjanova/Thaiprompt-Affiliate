using System.Text.Json;
using System.Text.Json.Serialization;

namespace SnakeGameServer.Network.Protocol;

/// <summary>
/// JSON serializer สำหรับ game messages
/// </summary>
public static class MessageSerializer
{
    private static readonly JsonSerializerOptions Options = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.SnakeCaseLower,
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull,
        WriteIndented = false
    };

    /// <summary>
    /// Serialize object เป็น JSON string
    /// </summary>
    public static string Serialize<T>(T obj)
    {
        return JsonSerializer.Serialize(obj, Options);
    }

    /// <summary>
    /// Deserialize JSON string เป็น object
    /// </summary>
    public static T? Deserialize<T>(string json)
    {
        return JsonSerializer.Deserialize<T>(json, Options);
    }

    /// <summary>
    /// อ่านค่า "type" จาก JSON โดยไม่ต้อง deserialize ทั้งหมด
    /// </summary>
    public static string? GetMessageType(string json)
    {
        try
        {
            using var doc = JsonDocument.Parse(json);
            if (doc.RootElement.TryGetProperty("type", out var typeElement))
            {
                return typeElement.GetString();
            }
        }
        catch
        {
            // JSON parse error
        }
        return null;
    }
}
