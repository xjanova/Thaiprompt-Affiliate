using System.Net.Http.Json;
using System.Text;
using Newtonsoft.Json;
using TP.POS.Core.Entities;

namespace TP.POS.Infrastructure.Api;

/// <summary>
/// API Client สำหรับเชื่อมต่อ TP-Affiliate Server
/// </summary>
public class TpAffiliateApiClient
{
    private readonly HttpClient _httpClient;
    private readonly string _baseUrl;
    private string? _authToken;

    /// <summary>
    /// สร้าง API Client
    /// </summary>
    public TpAffiliateApiClient(string baseUrl)
    {
        _baseUrl = baseUrl.TrimEnd('/');
        _httpClient = new HttpClient
        {
            BaseAddress = new Uri(_baseUrl),
            Timeout = TimeSpan.FromSeconds(30)
        };
    }

    /// <summary>
    /// ตั้งค่า Auth Token
    /// </summary>
    public void SetAuthToken(string token)
    {
        _authToken = token;
        _httpClient.DefaultRequestHeaders.Authorization =
            new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
    }

    /// <summary>
    /// ตรวจสอบการเชื่อมต่อ
    /// จะลองเรียก /pos/api/health ก่อน ถ้าไม่สำเร็จจะลองเรียก root URL แทน
    /// </summary>
    public async Task<bool> CheckConnectivityAsync()
    {
        try
        {
            // ลองเรียก /pos/api/health ก่อน (ต้อง deploy แล้ว)
            var response = await _httpClient.GetAsync("/pos/api/health");
            if (response.IsSuccessStatusCode)
            {
                return true;
            }

            // ถ้า health endpoint ไม่พร้อม ลองเรียก root URL แทน
            // เพื่อตรวจสอบว่า server online อยู่หรือไม่
            var rootResponse = await _httpClient.GetAsync("/");
            return rootResponse.IsSuccessStatusCode;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"CheckConnectivity error: {ex.Message}");
            return false;
        }
    }

    /// <summary>
    /// ทดสอบการเชื่อมต่อด้วย API Key
    /// </summary>
    public async Task<bool> TestConnectionAsync(string apiKey)
    {
        try
        {
            var request = new HttpRequestMessage(HttpMethod.Get, "/pos/api/health");
            request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", apiKey);

            var response = await _httpClient.SendAsync(request);
            return response.IsSuccessStatusCode;
        }
        catch
        {
            return false;
        }
    }

    #region Authentication

    /// <summary>
    /// ดึงข้อมูล User และร้านค้า
    /// </summary>
    public async Task<ApiResponse<UserInfoData>?> GetUserInfoAsync()
    {
        try
        {
            var response = await _httpClient.GetAsync("/pos/api/user/info");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<UserInfoData>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetUserInfo error: {ex.Message}");
        }
        return null;
    }

    /// <summary>
    /// Login
    /// </summary>
    public async Task<LoginResponse?> LoginAsync(string email, string password, string? deviceId = null)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("/api/login", new
            {
                email,
                password,
                device_name = deviceId ?? "pos-device"
            });

            if (response.IsSuccessStatusCode)
            {
                var result = await response.Content.ReadFromJsonAsync<LoginResponse>();
                if (result?.Token != null)
                {
                    SetAuthToken(result.Token);
                }
                return result;
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Login error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Products

    /// <summary>
    /// ดึงสินค้าทั้งหมด
    /// </summary>
    public async Task<ApiResponse<List<Product>>?> GetAllProductsAsync()
    {
        try
        {
            var response = await _httpClient.GetAsync("/pos/api/products/all");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<List<Product>>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetAllProducts error: {ex.Message}");
        }
        return null;
    }

    /// <summary>
    /// ดึงสินค้าที่อัพเดทตั้งแต่ timestamp
    /// </summary>
    public async Task<ApiResponse<List<Product>>?> GetUpdatedProductsAsync(DateTime since)
    {
        try
        {
            var timestamp = since.ToString("yyyy-MM-ddTHH:mm:ss");
            var response = await _httpClient.GetAsync($"/pos/api/products/updated?since={timestamp}");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<List<Product>>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetUpdatedProducts error: {ex.Message}");
        }
        return null;
    }

    /// <summary>
    /// ค้นหาสินค้า
    /// </summary>
    public async Task<ApiResponse<List<Product>>?> SearchProductsAsync(string query)
    {
        try
        {
            var response = await _httpClient.GetAsync($"/pos/api/products/search?q={Uri.EscapeDataString(query)}");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<List<Product>>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"SearchProducts error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Categories

    /// <summary>
    /// ดึงหมวดหมู่ทั้งหมด
    /// </summary>
    public async Task<ApiResponse<List<Category>>?> GetAllCategoriesAsync()
    {
        try
        {
            var response = await _httpClient.GetAsync("/pos/api/categories/all");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<List<Category>>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetAllCategories error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Customers

    /// <summary>
    /// ดึงลูกค้าทั้งหมด
    /// </summary>
    public async Task<ApiResponse<List<Customer>>?> GetAllCustomersAsync()
    {
        try
        {
            var response = await _httpClient.GetAsync("/pos/api/customers/all");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<List<Customer>>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetAllCustomers error: {ex.Message}");
        }
        return null;
    }

    /// <summary>
    /// สร้างลูกค้าใหม่
    /// </summary>
    public async Task<ApiResponse<Customer>?> CreateCustomerAsync(Customer customer)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("/pos/api/customers", customer);
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<Customer>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"CreateCustomer error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Transactions

    /// <summary>
    /// Sync รายการขาย
    /// </summary>
    public async Task<ApiResponse<SyncTransactionResult>?> SyncTransactionAsync(Transaction transaction, List<TransactionItem> items)
    {
        try
        {
            var payload = new
            {
                transaction,
                items
            };

            var response = await _httpClient.PostAsJsonAsync("/pos/api/transactions/sync", payload);
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<SyncTransactionResult>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"SyncTransaction error: {ex.Message}");
        }
        return null;
    }

    /// <summary>
    /// Sync หลายรายการขาย
    /// </summary>
    public async Task<ApiResponse<BulkSyncResult>?> BulkSyncTransactionsAsync(List<TransactionSyncData> transactions)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("/pos/api/transactions/bulk-sync", new
            {
                transactions
            });
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<BulkSyncResult>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"BulkSyncTransactions error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Stock

    /// <summary>
    /// Sync การเคลื่อนไหวสต็อก
    /// </summary>
    public async Task<ApiResponse<SyncStockResult>?> SyncStockMovementsAsync(List<StockMovement> movements)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("/pos/api/stock/sync", new
            {
                movements
            });
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<SyncStockResult>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"SyncStockMovements error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Reports

    /// <summary>
    /// ดึงสรุปยอดขายวันนี้
    /// </summary>
    public async Task<ApiResponse<DailySummaryData>?> GetTodaySummaryAsync()
    {
        try
        {
            var response = await _httpClient.GetAsync("/pos/api/reports/today");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<DailySummaryData>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetTodaySummary error: {ex.Message}");
        }
        return null;
    }

    #endregion

    #region Device

    /// <summary>
    /// ลงทะเบียนอุปกรณ์
    /// </summary>
    public async Task<ApiResponse<DeviceInfo>?> RegisterDeviceAsync(string deviceName, string deviceType)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("/pos/api/device/register", new
            {
                device_name = deviceName,
                device_type = deviceType,
                platform = Environment.OSVersion.Platform.ToString(),
                os_version = Environment.OSVersion.VersionString
            });
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<DeviceInfo>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"RegisterDevice error: {ex.Message}");
        }
        return null;
    }

    /// <summary>
    /// Heartbeat
    /// </summary>
    public async Task<bool> SendHeartbeatAsync(string deviceId)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("/pos/api/device/heartbeat", new
            {
                device_id = deviceId
            });
            return response.IsSuccessStatusCode;
        }
        catch
        {
            return false;
        }
    }

    #endregion

    #region Settings

    /// <summary>
    /// ดึงการตั้งค่า POS
    /// </summary>
    public async Task<ApiResponse<PosSettings>?> GetSettingsAsync()
    {
        try
        {
            var response = await _httpClient.GetAsync("/pos/api/settings");
            if (response.IsSuccessStatusCode)
            {
                return await response.Content.ReadFromJsonAsync<ApiResponse<PosSettings>>();
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetSettings error: {ex.Message}");
        }
        return null;
    }

    #endregion
}

#region API Response Models

/// <summary>
/// Response มาตรฐานจาก API
/// </summary>
public class ApiResponse<T>
{
    public bool Success { get; set; }
    public T? Data { get; set; }
    public string? Message { get; set; }
    public List<string>? Errors { get; set; }
}

/// <summary>
/// Login Response
/// </summary>
public class LoginResponse
{
    public string? Token { get; set; }
    public int UserId { get; set; }
    public string? UserName { get; set; }
    public string? Email { get; set; }
}

/// <summary>
/// ข้อมูล User และร้านค้า (จาก /pos/api/user/info)
/// </summary>
public class UserInfoData
{
    public int UserId { get; set; }
    public string Email { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Phone { get; set; }
    public string? Avatar { get; set; }
    public int? ShopId { get; set; }
    public string? ShopName { get; set; }
    public bool IsAdmin { get; set; }
    public string Role { get; set; } = "user";
    public List<string> Permissions { get; set; } = new();
    public string? MemberCode { get; set; }
    public decimal Points { get; set; }
}

/// <summary>
/// ข้อมูลสำหรับ Sync Transaction
/// </summary>
public class TransactionSyncData
{
    public Transaction Transaction { get; set; } = new();
    public List<TransactionItem> Items { get; set; } = new();
}

/// <summary>
/// ผลลัพธ์ Sync Transaction
/// </summary>
public class SyncTransactionResult
{
    public bool Success { get; set; }
    public int ServerId { get; set; }
    public string? ServerReceiptNumber { get; set; }
}

/// <summary>
/// ผลลัพธ์ Bulk Sync
/// </summary>
public class BulkSyncResult
{
    public int TotalCount { get; set; }
    public int SuccessCount { get; set; }
    public int FailedCount { get; set; }
    public List<SyncTransactionResult> Results { get; set; } = new();
}

/// <summary>
/// ผลลัพธ์ Sync Stock
/// </summary>
public class SyncStockResult
{
    public bool Success { get; set; }
    public int SyncedCount { get; set; }
}

/// <summary>
/// สรุปยอดขายจาก Server
/// </summary>
public class DailySummaryData
{
    public decimal TotalSales { get; set; }
    public int TransactionCount { get; set; }
    public int ItemsSold { get; set; }
    public decimal AverageTransaction { get; set; }
}

/// <summary>
/// ข้อมูลอุปกรณ์
/// </summary>
public class DeviceInfo
{
    public int Id { get; set; }
    public string DeviceId { get; set; } = string.Empty;
    public string DeviceName { get; set; } = string.Empty;

    public static DeviceInfo Current { get; } = new();
}

/// <summary>
/// การตั้งค่า POS จาก Server
/// </summary>
public class PosSettings
{
    public string StoreName { get; set; } = "TP-POS";
    public string? StoreAddress { get; set; }
    public string? StorePhone { get; set; }
    public string? TaxId { get; set; }
    public decimal TaxRate { get; set; } = 7;
    public string? ReceiptFooter { get; set; }
    public string? LogoUrl { get; set; }
    public string? Currency { get; set; } = "THB";
    public string? CurrencySymbol { get; set; } = "฿";
}

#endregion
