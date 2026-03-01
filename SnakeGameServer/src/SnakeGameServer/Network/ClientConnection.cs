using System.Collections.Concurrent;
using System.Net.WebSockets;
using System.Text;

namespace SnakeGameServer.Network;

/// <summary>
/// จัดการ WebSocket connection ของแต่ละ client
/// </summary>
public class ClientConnection : IDisposable
{
    private readonly WebSocket _ws;
    private readonly SemaphoreSlim _sendLock = new(1, 1);
    private readonly CancellationTokenSource _cts = new();

    /// <summary>
    /// Connection ID (unique)
    /// </summary>
    public string Id { get; }

    /// <summary>
    /// Player ID (set หลัง join)
    /// </summary>
    public string? PlayerId { get; set; }

    /// <summary>
    /// Room ID (set หลัง join)
    /// </summary>
    public string? RoomId { get; set; }

    /// <summary>
    /// IP Address ของ client
    /// </summary>
    public string RemoteEndpoint { get; }

    /// <summary>
    /// เวลาที่เชื่อมต่อ
    /// </summary>
    public DateTime ConnectedAt { get; }

    /// <summary>
    /// เวลา ping ล่าสุด
    /// </summary>
    public DateTime LastPingTime { get; set; }

    /// <summary>
    /// สถานะ connection
    /// </summary>
    public bool IsAlive => _ws.State == WebSocketState.Open;

    /// <summary>
    /// Queue สำหรับ input messages (game loop dequeue)
    /// </summary>
    public ConcurrentQueue<string> InputQueue { get; } = new();

    public ClientConnection(WebSocket ws, string remoteEndpoint)
    {
        _ws = ws;
        RemoteEndpoint = remoteEndpoint;
        Id = Guid.NewGuid().ToString("N")[..12];
        ConnectedAt = DateTime.UtcNow;
        LastPingTime = DateTime.UtcNow;
    }

    /// <summary>
    /// ส่ง JSON message ไปยัง client (thread-safe)
    /// </summary>
    public async Task SendAsync(string json)
    {
        if (_ws.State != WebSocketState.Open) return;

        await _sendLock.WaitAsync(_cts.Token);
        try
        {
            var bytes = Encoding.UTF8.GetBytes(json);
            await _ws.SendAsync(
                new ArraySegment<byte>(bytes),
                WebSocketMessageType.Text,
                true,
                _cts.Token);
        }
        catch (Exception)
        {
            // Connection อาจปิดแล้ว — ignore
        }
        finally
        {
            _sendLock.Release();
        }
    }

    /// <summary>
    /// Loop รับข้อความจาก client
    /// </summary>
    public async Task ReceiveLoopAsync(Action<ClientConnection, string> onMessage)
    {
        var buffer = new byte[4096];
        var messageBuffer = new StringBuilder();

        try
        {
            while (_ws.State == WebSocketState.Open && !_cts.IsCancellationRequested)
            {
                var result = await _ws.ReceiveAsync(
                    new ArraySegment<byte>(buffer),
                    _cts.Token);

                if (result.MessageType == WebSocketMessageType.Close)
                    break;

                if (result.MessageType == WebSocketMessageType.Text)
                {
                    messageBuffer.Append(Encoding.UTF8.GetString(buffer, 0, result.Count));

                    if (result.EndOfMessage)
                    {
                        var message = messageBuffer.ToString();
                        messageBuffer.Clear();

                        if (!string.IsNullOrWhiteSpace(message))
                        {
                            onMessage(this, message);
                        }
                    }
                }
            }
        }
        catch (WebSocketException)
        {
            // Client ตัดการเชื่อมต่อ
        }
        catch (OperationCanceledException)
        {
            // Server ปิด connection
        }
    }

    /// <summary>
    /// ปิด connection
    /// </summary>
    public async Task CloseAsync(string reason = "Server closing")
    {
        try
        {
            if (_ws.State == WebSocketState.Open)
            {
                await _ws.CloseAsync(
                    WebSocketCloseStatus.NormalClosure,
                    reason,
                    CancellationToken.None);
            }
        }
        catch
        {
            // Ignore close errors
        }
    }

    public void Dispose()
    {
        _cts.Cancel();
        _sendLock.Dispose();
        _cts.Dispose();
        _ws.Dispose();
    }
}
