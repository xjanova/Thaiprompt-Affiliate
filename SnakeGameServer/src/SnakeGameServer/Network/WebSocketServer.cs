using System.Collections.Concurrent;
using System.Net;
using System.Text;

namespace SnakeGameServer.Network;

/// <summary>
/// WebSocket Server — รับ connections จาก browser clients
/// </summary>
public class WebSocketServer
{
    private HttpListener? _listener;
    private readonly ConcurrentDictionary<string, ClientConnection> _clients = new();
    private CancellationTokenSource? _cts;

    /// <summary>
    /// Event: client เชื่อมต่อใหม่
    /// </summary>
    public event Action<ClientConnection>? OnClientConnected;

    /// <summary>
    /// Event: client ตัดการเชื่อมต่อ
    /// </summary>
    public event Action<ClientConnection, string>? OnClientDisconnected;

    /// <summary>
    /// Event: ได้รับข้อความจาก client
    /// </summary>
    public event Action<ClientConnection, string>? OnMessageReceived;

    /// <summary>
    /// Event: log message
    /// </summary>
    public event Action<string>? OnLog;

    /// <summary>
    /// จำนวน clients ที่เชื่อมต่ออยู่
    /// </summary>
    public int ClientCount => _clients.Count;

    /// <summary>
    /// รายการ clients ทั้งหมด
    /// </summary>
    public IReadOnlyCollection<ClientConnection> Clients => _clients.Values.ToList();

    /// <summary>
    /// เริ่ม listen บน port ที่กำหนด
    /// </summary>
    public async Task StartAsync(int port)
    {
        _cts = new CancellationTokenSource();

        _listener = new HttpListener();
        _listener.Prefixes.Add($"http://+:{port}/");

        try
        {
            _listener.Start();
            OnLog?.Invoke($"WebSocket Server listening on port {port}");
        }
        catch (HttpListenerException ex)
        {
            // ถ้าไม่มีสิทธิ์ bind http://+: ลอง localhost
            OnLog?.Invoke($"Cannot bind to all interfaces: {ex.Message}. Trying localhost...");
            _listener = new HttpListener();
            _listener.Prefixes.Add($"http://localhost:{port}/");
            _listener.Start();
            OnLog?.Invoke($"WebSocket Server listening on localhost:{port}");
        }

        // Accept loop
        while (!_cts.IsCancellationRequested)
        {
            try
            {
                var context = await _listener.GetContextAsync();

                if (context.Request.IsWebSocketRequest)
                {
                    _ = Task.Run(() => HandleWebSocketAsync(context));
                }
                else
                {
                    HandleHttpRequest(context);
                }
            }
            catch (HttpListenerException) when (_cts.IsCancellationRequested)
            {
                break; // Server กำลังปิด
            }
            catch (ObjectDisposedException)
            {
                break; // Listener ถูก dispose
            }
            catch (Exception ex)
            {
                OnLog?.Invoke($"Accept error: {ex.Message}");
            }
        }
    }

    /// <summary>
    /// จัดการ WebSocket connection ใหม่
    /// </summary>
    private async Task HandleWebSocketAsync(HttpListenerContext context)
    {
        System.Net.WebSockets.HttpListenerWebSocketContext wsContext;

        try
        {
            wsContext = await context.AcceptWebSocketAsync(null);
        }
        catch (Exception ex)
        {
            OnLog?.Invoke($"WebSocket upgrade failed: {ex.Message}");
            context.Response.StatusCode = 400;
            context.Response.Close();
            return;
        }

        var remoteEndpoint = context.Request.RemoteEndPoint?.ToString() ?? "unknown";
        var client = new ClientConnection(wsContext.WebSocket, remoteEndpoint);

        _clients.TryAdd(client.Id, client);
        OnLog?.Invoke($"Client connected: {client.Id} from {remoteEndpoint}");
        OnClientConnected?.Invoke(client);

        try
        {
            await client.ReceiveLoopAsync((conn, message) =>
            {
                OnMessageReceived?.Invoke(conn, message);
            });
        }
        catch (Exception ex)
        {
            OnLog?.Invoke($"Client {client.Id} error: {ex.Message}");
        }
        finally
        {
            _clients.TryRemove(client.Id, out _);
            OnLog?.Invoke($"Client disconnected: {client.Id}");
            OnClientDisconnected?.Invoke(client, "disconnected");
            client.Dispose();
        }
    }

    /// <summary>
    /// จัดการ HTTP request ปกติ (CORS preflight, health check)
    /// </summary>
    private void HandleHttpRequest(HttpListenerContext context)
    {
        var response = context.Response;

        // CORS headers สำหรับ browser
        response.Headers.Add("Access-Control-Allow-Origin", "*");
        response.Headers.Add("Access-Control-Allow-Methods", "GET, OPTIONS");
        response.Headers.Add("Access-Control-Allow-Headers", "*");

        if (context.Request.HttpMethod == "OPTIONS")
        {
            response.StatusCode = 204;
            response.Close();
            return;
        }

        // Health check / status page
        var statusJson = $"{{\"status\":\"running\",\"clients\":{_clients.Count},\"time\":\"{DateTime.UtcNow:O}\"}}";
        var buffer = Encoding.UTF8.GetBytes(statusJson);

        response.ContentType = "application/json";
        response.ContentLength64 = buffer.Length;
        response.StatusCode = 200;
        response.OutputStream.Write(buffer, 0, buffer.Length);
        response.Close();
    }

    /// <summary>
    /// ส่ง message ไปยัง client เฉพาะ
    /// </summary>
    public async Task SendToClientAsync(string clientId, string json)
    {
        if (_clients.TryGetValue(clientId, out var client))
        {
            await client.SendAsync(json);
        }
    }

    /// <summary>
    /// ตัดการเชื่อมต่อ client
    /// </summary>
    public async Task DisconnectClientAsync(string clientId, string reason = "Kicked by admin")
    {
        if (_clients.TryGetValue(clientId, out var client))
        {
            await client.CloseAsync(reason);
        }
    }

    /// <summary>
    /// หยุด server
    /// </summary>
    public async Task StopAsync()
    {
        _cts?.Cancel();

        // ปิด connections ทั้งหมด
        var closeTasks = _clients.Values.Select(c => c.CloseAsync("Server shutting down"));
        await Task.WhenAll(closeTasks);

        _clients.Clear();

        try
        {
            _listener?.Stop();
            _listener?.Close();
        }
        catch
        {
            // Ignore
        }

        OnLog?.Invoke("WebSocket Server stopped");
    }
}
