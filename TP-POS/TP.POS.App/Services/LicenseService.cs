using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับจัดการ License และการลงทะเบียน POS
///
/// Flow การตั้งค่าครั้งแรก (ขั้นตอนเดียว):
/// 1. กรอก Server URL + Shop Code + API Key
/// 2. ระบบ Activate และเริ่มใช้งานได้ทันที
///
/// ระบบตรวจสอบ:
/// - ตรวจสอบสถานะเป็นระยะ (ทุก 1 นาที)
/// - แจ้งเตือนเมื่อ API Key ถูกบล็อก
/// - ส่ง heartbeat ไป Server เพื่อแสดงสถานะ Online
/// </summary>
public class LicenseService : ILicenseService
{
    #region Constants

    /// <summary>
    /// Prefix สำหรับ Product Key
    /// </summary>
    private const string PRODUCT_KEY_PREFIX = "TP-POS";

    /// <summary>
    /// Storage Keys
    /// </summary>
    private const string PREF_LICENSE_DATA = "license_data_encrypted";
    private const string PREF_PRODUCT_KEY = "product_key";
    private const string PREF_DEVICE_ID = "device_id";
    private const string PREF_API_KEY = "api_key";
    private const string PREF_SERVER_URL = "server_url";
    private const string PREF_SHOP_CODE = "shop_code";
    private const string PREF_TERMINAL_ID = "terminal_id";
    private const string PREF_SECRET_KEY = "pos_secret_key";

    /// <summary>
    /// Interval สำหรับตรวจสอบสถานะ (มิลลิวินาที)
    /// </summary>
    private const int STATUS_CHECK_INTERVAL = 60000; // 1 นาที

    /// <summary>
    /// Default Secret Key (ใช้เมื่อยังไม่ได้ตั้งค่า)
    /// ⚠️ ควรตั้งค่าผ่าน SetSecretKey() หรือ Environment Variable
    /// </summary>
    private const string DEFAULT_SECRET_KEY = "";

    #endregion

    #region Secret Key Management

    /// <summary>
    /// ดึง Secret Key จาก settings หรือ environment variable
    /// </summary>
    private string GetSecretKey()
    {
        // 1. ลองดึงจาก Preferences (ที่ตั้งค่าไว้ในแอป)
        var savedKey = Preferences.Get(PREF_SECRET_KEY, string.Empty);
        if (!string.IsNullOrEmpty(savedKey))
            return savedKey;

        // 2. ลองดึงจาก Environment Variable
        var envKey = Environment.GetEnvironmentVariable("POS_SECRET_KEY");
        if (!string.IsNullOrEmpty(envKey))
            return envKey;

        // 3. ใช้ Default (ควรตั้งค่าก่อนใช้งาน)
        return DEFAULT_SECRET_KEY;
    }

    /// <summary>
    /// ตั้งค่า Secret Key (เรียกจาก Setup Page)
    /// </summary>
    public void SetSecretKey(string secretKey)
    {
        if (!string.IsNullOrWhiteSpace(secretKey))
        {
            Preferences.Set(PREF_SECRET_KEY, secretKey);
        }
    }

    /// <summary>
    /// ตรวจสอบว่า Secret Key ถูกตั้งค่าแล้วหรือยัง
    /// </summary>
    public bool HasSecretKey => !string.IsNullOrEmpty(GetSecretKey());

    #endregion

    #region Properties

    /// <summary>
    /// ข้อมูล License ปัจจุบัน
    /// </summary>
    public LicenseInfo? CurrentLicense { get; private set; }

    /// <summary>
    /// สถานะ License ปัจจุบัน
    /// </summary>
    public LicenseStatus Status => CurrentLicense?.Status ?? LicenseStatus.NotRegistered;

    /// <summary>
    /// License ใช้งานได้หรือไม่
    /// </summary>
    public bool IsActivated => CurrentLicense?.IsValid ?? false;

    /// <summary>
    /// ต้องกรอก API Key หรือไม่
    /// </summary>
    public bool NeedsApiKey => CurrentLicense?.Status == LicenseStatus.PendingApiKey;

    /// <summary>
    /// API Key ถูกบล็อกหรือไม่
    /// </summary>
    public bool IsBlocked => CurrentLicense?.Status == LicenseStatus.Blocked;

    /// <summary>
    /// ข้อความแจ้งเตือนเมื่อถูกบล็อก
    /// </summary>
    public string? BlockedMessage => CurrentLicense?.BlockedMessage;

    /// <summary>
    /// Timer สำหรับตรวจสอบสถานะเป็นระยะ
    /// </summary>
    private Timer? _statusCheckTimer;

    /// <summary>
    /// Event เมื่อ API Key ถูกบล็อก
    /// </summary>
    public event EventHandler<BlockedEventArgs>? OnApiKeyBlocked;

    #endregion

    #region Constructor

    public LicenseService()
    {
        // โหลด License ที่บันทึกไว้ (ถ้ามี)
        _ = LoadSavedLicenseAsync();
    }

    #endregion

    #region Product Key Generation & Validation

    /// <summary>
    /// สร้าง Product Key ใหม่สำหรับเครื่องนี้
    /// Format: TP-POS-XXXX-XXXX-XXXX-XXXX
    /// </summary>
    public string GenerateProductKey()
    {
        // ดึง Device ID
        var deviceId = GetDeviceId();

        // สร้าง unique string จาก device + timestamp
        var timestamp = DateTime.UtcNow.Ticks.ToString();
        var rawData = $"{deviceId}|{timestamp}|{GetSecretKey()}";

        // Hash ด้วย SHA256
        using var sha256 = SHA256.Create();
        var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(rawData));
        var hashString = Convert.ToBase64String(hashBytes)
            .Replace("+", "")
            .Replace("/", "")
            .Replace("=", "")
            .ToUpper();

        // แปลงเป็น format TP-POS-XXXX-XXXX-XXXX-XXXX
        var key = $"{PRODUCT_KEY_PREFIX}-{hashString[..4]}-{hashString[4..8]}-{hashString[8..12]}-{hashString[12..16]}";

        // บันทึก Product Key
        Preferences.Set(PREF_PRODUCT_KEY, key);

        return key;
    }

    /// <summary>
    /// ตรวจสอบ Product Key ว่าถูกต้องหรือไม่
    /// </summary>
    public bool ValidateProductKey(string productKey)
    {
        if (string.IsNullOrWhiteSpace(productKey))
            return false;

        // ตรวจสอบ format
        if (!productKey.StartsWith(PRODUCT_KEY_PREFIX))
            return false;

        // ตรวจสอบ pattern: TP-POS-XXXX-XXXX-XXXX-XXXX
        var parts = productKey.Split('-');
        if (parts.Length != 6)
            return false;

        // ตรวจสอบว่าทุกส่วนเป็น alphanumeric
        for (int i = 2; i < parts.Length; i++)
        {
            if (parts[i].Length != 4 || !parts[i].All(char.IsLetterOrDigit))
                return false;
        }

        return true;
    }

    /// <summary>
    /// ดึง Device ID ของเครื่องนี้
    /// </summary>
    public string GetDeviceId()
    {
        // ดึง Device ID ที่บันทึกไว้
        var savedDeviceId = Preferences.Get(PREF_DEVICE_ID, string.Empty);
        if (!string.IsNullOrEmpty(savedDeviceId))
            return savedDeviceId;

        // สร้าง Device ID ใหม่จาก hardware info
        string deviceId;

        try
        {
            // ใช้ข้อมูลจาก Device
            var deviceName = DeviceInfo.Name;
            var deviceModel = DeviceInfo.Model;
            var deviceManufacturer = DeviceInfo.Manufacturer;
            var platform = DeviceInfo.Platform.ToString();

            // รวมข้อมูลแล้ว hash
            var rawData = $"{deviceName}|{deviceModel}|{deviceManufacturer}|{platform}|{GetSecretKey()}";

            using var sha256 = SHA256.Create();
            var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(rawData));
            deviceId = Convert.ToHexString(hashBytes)[..32]; // ใช้ 32 characters
        }
        catch
        {
            // ถ้าเกิดข้อผิดพลาด ใช้ GUID แทน
            deviceId = Guid.NewGuid().ToString("N");
        }

        // บันทึก Device ID
        Preferences.Set(PREF_DEVICE_ID, deviceId);

        return deviceId;
    }

    #endregion

    #region Single Step Activation (ขั้นตอนเดียว)

    /// <summary>
    /// Activate POS ด้วย Server URL + Shop Code + API Key (ขั้นตอนเดียว)
    ///
    /// นี่คือ method หลักสำหรับการตั้งค่าครั้งแรก
    /// </summary>
    public async Task<LicenseActivationResult> ActivateAsync(string serverUrl, string shopCode, string apiKey)
    {
        try
        {
            // ตรวจสอบ input
            if (string.IsNullOrWhiteSpace(serverUrl))
            {
                return new LicenseActivationResult
                {
                    Success = false,
                    Message = "กรุณาระบุ Server URL",
                    ErrorCode = "MISSING_SERVER_URL"
                };
            }

            if (string.IsNullOrWhiteSpace(shopCode))
            {
                return new LicenseActivationResult
                {
                    Success = false,
                    Message = "กรุณาระบุรหัสร้านค้า",
                    ErrorCode = "MISSING_SHOP_CODE"
                };
            }

            if (string.IsNullOrWhiteSpace(apiKey))
            {
                return new LicenseActivationResult
                {
                    Success = false,
                    Message = "กรุณาระบุ API Key",
                    ErrorCode = "MISSING_API_KEY"
                };
            }

            // ดึง/สร้าง Product Key
            var productKey = Preferences.Get(PREF_PRODUCT_KEY, string.Empty);
            if (string.IsNullOrEmpty(productKey))
            {
                productKey = GenerateProductKey();
            }

            // ดึง Device ID
            var deviceId = GetDeviceId();

            // เตรียมข้อมูล Activate
            var activateData = new
            {
                product_key = productKey,
                shop_code = shopCode,
                api_key = apiKey,
                device_id = deviceId,
                device_name = DeviceInfo.Name,
                device_model = DeviceInfo.Model,
                platform = DeviceInfo.Platform.ToString(),
                app_version = AppInfo.Current.VersionString
            };

            // ส่งข้อมูลไป Server
            using var httpClient = new HttpClient();
            httpClient.Timeout = TimeSpan.FromSeconds(30);

            var json = JsonSerializer.Serialize(activateData);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            var activateUrl = serverUrl.TrimEnd('/') + "/api/pos/activate";
            var response = await httpClient.PostAsync(activateUrl, content);
            var responseBody = await response.Content.ReadAsStringAsync();

            System.Diagnostics.Debug.WriteLine($"Activate response: {responseBody}");

            if (response.IsSuccessStatusCode)
            {
                // Parse response
                var result = JsonSerializer.Deserialize<ActivateResponse>(responseBody, new JsonSerializerOptions
                {
                    PropertyNameCaseInsensitive = true
                });

                if (result?.Success == true)
                {
                    // สร้าง License Info สถานะ "Active"
                    var license = new LicenseInfo
                    {
                        ProductKey = productKey,
                        ApiKey = apiKey,
                        DeviceId = deviceId,
                        ShopName = result.Data?.ShopName ?? "",
                        ShopId = result.Data?.ShopId ?? 0,
                        PosTerminalId = result.Data?.TerminalId ?? 0,
                        Status = LicenseStatus.Activated,
                        RegisteredAt = DateTime.UtcNow,
                        VerifiedAt = DateTime.UtcNow,
                        ServerUrl = serverUrl
                    };

                    // บันทึก License
                    await SaveLicenseAsync(license);
                    CurrentLicense = license;

                    // บันทึกข้อมูลอื่นๆ
                    Preferences.Set(PREF_SERVER_URL, serverUrl);
                    Preferences.Set(PREF_SHOP_CODE, shopCode);
                    Preferences.Set(PREF_API_KEY, apiKey);
                    Preferences.Set(PREF_TERMINAL_ID, license.PosTerminalId);
                    if (!string.IsNullOrEmpty(license.ShopName))
                    {
                        Preferences.Set("shop_name", license.ShopName);
                    }

                    // เริ่ม timer ตรวจสอบสถานะ
                    StartStatusCheckTimer();

                    return new LicenseActivationResult
                    {
                        Success = true,
                        Message = "Activate สำเร็จ พร้อมใช้งาน",
                        License = license,
                        NeedsApiKey = false
                    };
                }
                else
                {
                    return new LicenseActivationResult
                    {
                        Success = false,
                        Message = result?.Message ?? "การ Activate ล้มเหลว",
                        ErrorCode = result?.ErrorCode ?? "ACTIVATE_FAILED"
                    };
                }
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.Forbidden)
            {
                // API Key ถูกบล็อก
                var result = JsonSerializer.Deserialize<ActivateResponse>(responseBody, new JsonSerializerOptions
                {
                    PropertyNameCaseInsensitive = true
                });

                return new LicenseActivationResult
                {
                    Success = false,
                    Message = result?.Message ?? "API Key ถูกบล็อก กรุณาติดต่อ Admin",
                    ErrorCode = result?.ErrorCode ?? "API_KEY_BLOCKED",
                    IsBlocked = true,
                    BlockedReason = result?.BlockedReason
                };
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.Unauthorized)
            {
                return new LicenseActivationResult
                {
                    Success = false,
                    Message = "API Key ไม่ถูกต้อง",
                    ErrorCode = "INVALID_API_KEY"
                };
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.NotFound)
            {
                return new LicenseActivationResult
                {
                    Success = false,
                    Message = "ไม่พบร้านค้าจากรหัสที่ระบุ หรือ API Key ไม่ตรงกับร้านค้า",
                    ErrorCode = "SHOP_NOT_FOUND"
                };
            }
            else
            {
                return new LicenseActivationResult
                {
                    Success = false,
                    Message = $"เกิดข้อผิดพลาด: {response.StatusCode}",
                    ErrorCode = response.StatusCode.ToString()
                };
            }
        }
        catch (TaskCanceledException)
        {
            return new LicenseActivationResult
            {
                Success = false,
                Message = "Server ไม่ตอบสนอง (Timeout)",
                ErrorCode = "TIMEOUT"
            };
        }
        catch (HttpRequestException ex)
        {
            return new LicenseActivationResult
            {
                Success = false,
                Message = $"ไม่สามารถเชื่อมต่อ Server ได้: {ex.Message}",
                ErrorCode = "CONNECTION_ERROR"
            };
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Activate error: {ex}");
            return new LicenseActivationResult
            {
                Success = false,
                Message = $"เกิดข้อผิดพลาด: {ex.Message}",
                ErrorCode = "UNKNOWN_ERROR"
            };
        }
    }

    #endregion

    #region Legacy Methods (สำหรับ backward compatibility)

    /// <summary>
    /// ลงทะเบียน POS กับ Server (Legacy - ใช้ ActivateAsync แทน)
    /// </summary>
    public async Task<LicenseActivationResult> RegisterAsync(string serverUrl, string shopCode)
    {
        // Redirect ไปใช้ ActivateAsync แต่ไม่มี API Key
        return new LicenseActivationResult
        {
            Success = false,
            Message = "กรุณาใช้ ActivateAsync พร้อม API Key แทน",
            ErrorCode = "USE_ACTIVATE_INSTEAD",
            NeedsApiKey = true
        };
    }

    /// <summary>
    /// ยืนยัน POS ด้วย API Key (Legacy - ใช้ ActivateAsync แทน)
    /// </summary>
    public async Task<LicenseActivationResult> VerifyApiKeyAsync(string apiKey)
    {
        // ถ้ามี License อยู่แล้ว ให้ใช้ข้อมูลเดิม
        var serverUrl = Preferences.Get(PREF_SERVER_URL, string.Empty);
        var shopCode = Preferences.Get(PREF_SHOP_CODE, string.Empty);

        if (string.IsNullOrEmpty(serverUrl) || string.IsNullOrEmpty(shopCode))
        {
            return new LicenseActivationResult
            {
                Success = false,
                Message = "กรุณาลงทะเบียน POS ก่อน",
                ErrorCode = "NOT_REGISTERED"
            };
        }

        return await ActivateAsync(serverUrl, shopCode, apiKey);
    }

    #endregion

    #region License Validation & Status Check

    /// <summary>
    /// ตรวจสอบสถานะ License กับ Server
    /// </summary>
    public async Task<bool> ValidateLicenseAsync()
    {
        if (CurrentLicense == null || !CurrentLicense.IsValid)
            return false;

        try
        {
            using var httpClient = new HttpClient();
            httpClient.Timeout = TimeSpan.FromSeconds(10);
            httpClient.DefaultRequestHeaders.Add("X-API-Key", CurrentLicense.ApiKey);
            httpClient.DefaultRequestHeaders.Add("X-Product-Key", CurrentLicense.ProductKey);
            httpClient.DefaultRequestHeaders.Add("X-Device-ID", CurrentLicense.DeviceId);

            var validateUrl = CurrentLicense.ServerUrl.TrimEnd('/') + "/api/pos/validate";
            var response = await httpClient.GetAsync(validateUrl);

            if (response.IsSuccessStatusCode)
            {
                var responseBody = await response.Content.ReadAsStringAsync();
                var result = JsonSerializer.Deserialize<ValidationResponse>(responseBody, new JsonSerializerOptions
                {
                    PropertyNameCaseInsensitive = true
                });

                if (result?.Valid == true)
                {
                    // อัพเดทวันหมดอายุ (ถ้ามี)
                    if (result.ExpiresAt.HasValue)
                    {
                        CurrentLicense.ExpiresAt = result.ExpiresAt;
                        await SaveLicenseAsync(CurrentLicense);
                    }
                    return true;
                }
                else
                {
                    // ตรวจสอบว่าถูกบล็อกหรือไม่
                    if (result?.Blocked == true)
                    {
                        CurrentLicense.Status = LicenseStatus.Blocked;
                        CurrentLicense.BlockedMessage = result.Message ?? "API Key ถูกบล็อก";
                        await SaveLicenseAsync(CurrentLicense);

                        // Trigger event
                        OnApiKeyBlocked?.Invoke(this, new BlockedEventArgs
                        {
                            Message = CurrentLicense.BlockedMessage,
                            Reason = result.BlockedReason
                        });

                        return false;
                    }

                    CurrentLicense.Status = LicenseStatus.Invalid;
                    CurrentLicense.ErrorMessage = result?.Message ?? "License ไม่ถูกต้อง";
                    await SaveLicenseAsync(CurrentLicense);
                    return false;
                }
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.Forbidden)
            {
                // API Key ถูกบล็อกหรือระงับ
                var responseBody = await response.Content.ReadAsStringAsync();
                var result = JsonSerializer.Deserialize<ValidationResponse>(responseBody, new JsonSerializerOptions
                {
                    PropertyNameCaseInsensitive = true
                });

                CurrentLicense.Status = LicenseStatus.Blocked;
                CurrentLicense.BlockedMessage = result?.Message ?? "API Key ถูกบล็อก กรุณาติดต่อ Admin";
                CurrentLicense.BlockedReason = result?.BlockedReason;
                await SaveLicenseAsync(CurrentLicense);

                // Trigger event
                OnApiKeyBlocked?.Invoke(this, new BlockedEventArgs
                {
                    Message = CurrentLicense.BlockedMessage,
                    Reason = CurrentLicense.BlockedReason
                });

                return false;
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.Unauthorized)
            {
                CurrentLicense.Status = LicenseStatus.Invalid;
                CurrentLicense.ErrorMessage = "API Key ไม่ถูกต้อง";
                await SaveLicenseAsync(CurrentLicense);
                return false;
            }

            // Network error - ยังอนุญาตให้ใช้งานต่อ (offline mode)
            return CurrentLicense.IsValid;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"License validation error: {ex}");
            // Network error - ยังอนุญาตให้ใช้งานต่อ (offline mode)
            return CurrentLicense.IsValid;
        }
    }

    /// <summary>
    /// ตรวจสอบสถานะเป็นระยะ (สำหรับแจ้งเตือนเมื่อถูกบล็อก และส่ง heartbeat)
    /// </summary>
    public async Task<StatusCheckResult> CheckStatusAsync()
    {
        try
        {
            var apiKey = Preferences.Get(PREF_API_KEY, string.Empty);
            var productKey = Preferences.Get(PREF_PRODUCT_KEY, string.Empty);
            var serverUrl = Preferences.Get(PREF_SERVER_URL, string.Empty);

            if (string.IsNullOrEmpty(apiKey) || string.IsNullOrEmpty(productKey) || string.IsNullOrEmpty(serverUrl))
            {
                return new StatusCheckResult
                {
                    Success = true,
                    HasIssue = false
                };
            }

            using var httpClient = new HttpClient();
            httpClient.Timeout = TimeSpan.FromSeconds(10);
            httpClient.DefaultRequestHeaders.Add("X-API-Key", apiKey);
            httpClient.DefaultRequestHeaders.Add("X-Product-Key", productKey);

            var statusUrl = serverUrl.TrimEnd('/') + "/api/pos/status";
            var response = await httpClient.GetAsync(statusUrl);
            var responseBody = await response.Content.ReadAsStringAsync();

            var result = JsonSerializer.Deserialize<StatusCheckResponse>(responseBody, new JsonSerializerOptions
            {
                PropertyNameCaseInsensitive = true
            });

            if (result?.HasIssue == true)
            {
                // มีปัญหา - อัพเดทสถานะ
                if (CurrentLicense != null && result.ApiKeyStatus?.IsBlocked == true)
                {
                    CurrentLicense.Status = LicenseStatus.Blocked;
                    CurrentLicense.BlockedMessage = result.IssueMessage ?? "API Key ถูกบล็อก";
                    CurrentLicense.BlockedReason = result.ApiKeyStatus?.BlockedReason;
                    await SaveLicenseAsync(CurrentLicense);

                    // Trigger event
                    OnApiKeyBlocked?.Invoke(this, new BlockedEventArgs
                    {
                        Message = CurrentLicense.BlockedMessage,
                        Reason = CurrentLicense.BlockedReason
                    });
                }

                return new StatusCheckResult
                {
                    Success = true,
                    HasIssue = true,
                    IssueMessage = result.IssueMessage,
                    ContactAdmin = result.ContactAdmin ?? false
                };
            }

            return new StatusCheckResult
            {
                Success = true,
                HasIssue = false
            };
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Status check error: {ex}");
            return new StatusCheckResult
            {
                Success = false,
                HasIssue = false
            };
        }
    }

    /// <summary>
    /// เริ่ม timer สำหรับตรวจสอบสถานะเป็นระยะ
    /// </summary>
    private void StartStatusCheckTimer()
    {
        StopStatusCheckTimer();

        _statusCheckTimer = new Timer(async _ =>
        {
            await CheckStatusAsync();
        }, null, STATUS_CHECK_INTERVAL, STATUS_CHECK_INTERVAL);
    }

    /// <summary>
    /// หยุด timer
    /// </summary>
    private void StopStatusCheckTimer()
    {
        _statusCheckTimer?.Dispose();
        _statusCheckTimer = null;
    }

    #endregion

    #region License Storage

    /// <summary>
    /// โหลด License ที่บันทึกไว้
    /// </summary>
    public async Task<bool> LoadSavedLicenseAsync()
    {
        try
        {
            var encryptedData = Preferences.Get(PREF_LICENSE_DATA, string.Empty);
            if (string.IsNullOrEmpty(encryptedData))
            {
                CurrentLicense = null;
                return false;
            }

            // Decrypt และ deserialize
            var json = Decrypt(encryptedData);
            var license = JsonSerializer.Deserialize<LicenseInfo>(json);

            if (license != null)
            {
                // ตรวจสอบว่า Device ID ตรงกันหรือไม่
                var currentDeviceId = GetDeviceId();
                if (license.DeviceId != currentDeviceId)
                {
                    // Device ID ไม่ตรง - License ไม่ถูกต้อง
                    System.Diagnostics.Debug.WriteLine("License device ID mismatch");
                    CurrentLicense = null;
                    await ClearLicenseAsync();
                    return false;
                }

                CurrentLicense = license;

                // ถ้า License ยืนยันแล้ว เริ่ม status check timer
                if (license.Status == LicenseStatus.Activated)
                {
                    StartStatusCheckTimer();

                    // ตรวจสอบกับ Server (background)
                    _ = ValidateLicenseAsync();
                }

                return true;
            }

            return false;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Load license error: {ex}");
            CurrentLicense = null;
            return false;
        }
    }

    /// <summary>
    /// บันทึก License
    /// </summary>
    private async Task SaveLicenseAsync(LicenseInfo license)
    {
        try
        {
            var json = JsonSerializer.Serialize(license);
            var encrypted = Encrypt(json);
            Preferences.Set(PREF_LICENSE_DATA, encrypted);
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Save license error: {ex}");
        }
    }

    /// <summary>
    /// ล้าง License (สำหรับ Admin เท่านั้น)
    /// </summary>
    public async Task ClearLicenseAsync()
    {
        Preferences.Remove(PREF_LICENSE_DATA);
        Preferences.Remove(PREF_PRODUCT_KEY);
        Preferences.Remove(PREF_API_KEY);
        Preferences.Remove(PREF_SERVER_URL);
        Preferences.Remove(PREF_SHOP_CODE);
        Preferences.Remove(PREF_TERMINAL_ID);
        Preferences.Remove("shop_name");
        Preferences.Remove("shop_id");

        CurrentLicense = null;
        StopStatusCheckTimer();

        await Task.CompletedTask;
    }

    #endregion

    #region Encryption Helpers

    /// <summary>
    /// เข้ารหัสข้อมูล
    /// </summary>
    private string Encrypt(string plainText)
    {
        try
        {
            var key = DeriveKey(GetSecretKey());
            using var aes = Aes.Create();
            aes.Key = key;
            aes.GenerateIV();

            using var encryptor = aes.CreateEncryptor();
            var plainBytes = Encoding.UTF8.GetBytes(plainText);
            var encryptedBytes = encryptor.TransformFinalBlock(plainBytes, 0, plainBytes.Length);

            // รวม IV + encrypted data
            var result = new byte[aes.IV.Length + encryptedBytes.Length];
            Buffer.BlockCopy(aes.IV, 0, result, 0, aes.IV.Length);
            Buffer.BlockCopy(encryptedBytes, 0, result, aes.IV.Length, encryptedBytes.Length);

            return Convert.ToBase64String(result);
        }
        catch
        {
            // Fallback to base64 if encryption fails
            return Convert.ToBase64String(Encoding.UTF8.GetBytes(plainText));
        }
    }

    /// <summary>
    /// ถอดรหัสข้อมูล
    /// </summary>
    private string Decrypt(string encryptedText)
    {
        try
        {
            var key = DeriveKey(GetSecretKey());
            var fullData = Convert.FromBase64String(encryptedText);

            using var aes = Aes.Create();
            aes.Key = key;

            // แยก IV และ encrypted data
            var iv = new byte[16];
            var encryptedBytes = new byte[fullData.Length - 16];
            Buffer.BlockCopy(fullData, 0, iv, 0, 16);
            Buffer.BlockCopy(fullData, 16, encryptedBytes, 0, encryptedBytes.Length);

            aes.IV = iv;

            using var decryptor = aes.CreateDecryptor();
            var decryptedBytes = decryptor.TransformFinalBlock(encryptedBytes, 0, encryptedBytes.Length);

            return Encoding.UTF8.GetString(decryptedBytes);
        }
        catch
        {
            // Fallback - try base64 decode
            return Encoding.UTF8.GetString(Convert.FromBase64String(encryptedText));
        }
    }

    /// <summary>
    /// สร้าง key จาก password
    /// </summary>
    private byte[] DeriveKey(string password)
    {
        using var sha256 = SHA256.Create();
        return sha256.ComputeHash(Encoding.UTF8.GetBytes(password + "_TP_POS_KEY"));
    }

    #endregion

    #region Response Models

    private class ActivateResponse
    {
        public bool Success { get; set; }
        public string? Message { get; set; }
        public string? ErrorCode { get; set; }
        public string? BlockedReason { get; set; }
        public ActivateData? Data { get; set; }
    }

    private class ActivateData
    {
        public string? ShopName { get; set; }
        public int ShopId { get; set; }
        public int TerminalId { get; set; }
        public string? Status { get; set; }
        public bool IsVerified { get; set; }
    }

    private class ValidationResponse
    {
        public bool Success { get; set; }
        public bool Valid { get; set; }
        public string? Message { get; set; }
        public DateTime? ExpiresAt { get; set; }
        public bool Blocked { get; set; }
        public string? BlockedReason { get; set; }
        public bool NeedsApiKey { get; set; }
        public bool ContactAdmin { get; set; }
    }

    private class StatusCheckResponse
    {
        public bool Success { get; set; }
        public bool HasIssue { get; set; }
        public string? IssueMessage { get; set; }
        public bool? ContactAdmin { get; set; }
        public ApiKeyStatusData? ApiKeyStatus { get; set; }
        public TerminalStatusData? TerminalStatus { get; set; }
    }

    private class ApiKeyStatusData
    {
        public bool IsValid { get; set; }
        public bool IsBlocked { get; set; }
        public string? BlockedReason { get; set; }
        public string? BlockedAt { get; set; }
        public string? StatusText { get; set; }
    }

    private class TerminalStatusData
    {
        public string? Status { get; set; }
        public bool IsVerified { get; set; }
        public string? StatusText { get; set; }
    }

    #endregion
}
