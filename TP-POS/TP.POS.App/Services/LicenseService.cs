using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับจัดการ License และการลงทะเบียน POS
/// ใช้ระบบ 2 กุญแจ: Product Key + API Key
/// </summary>
public class LicenseService : ILicenseService
{
    #region Constants

    /// <summary>
    /// Secret Key สำหรับสร้าง/ตรวจสอบ Product Key
    /// </summary>
    private const string SECRET_KEY = "thaipromp123";

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
        var rawData = $"{deviceId}|{timestamp}|{SECRET_KEY}";

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

        // ตรวจสอบด้วย checksum (optional - เพิ่มความปลอดภัย)
        // สามารถเพิ่ม logic ตรวจสอบเพิ่มเติมได้

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
            var rawData = $"{deviceName}|{deviceModel}|{deviceManufacturer}|{platform}|{SECRET_KEY}";

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

    #region License Registration & Validation

    /// <summary>
    /// ลงทะเบียนเครื่อง POS กับ Server
    /// Flow: POS ส่ง shop_code → Server สร้าง API Key ให้อัตโนมัติ → POS เก็บ API Key ไว้ใช้
    /// </summary>
    public async Task<LicenseActivationResult> RegisterAsync(string serverUrl, string shopCode)
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

            // ดึง/สร้าง Product Key
            var productKey = Preferences.Get(PREF_PRODUCT_KEY, string.Empty);
            if (string.IsNullOrEmpty(productKey))
            {
                productKey = GenerateProductKey();
            }

            // ดึง Device ID
            var deviceId = GetDeviceId();

            // เตรียมข้อมูลลงทะเบียน (ส่ง shop_code แทน api_key)
            var registrationData = new
            {
                product_key = productKey,
                shop_code = shopCode,
                device_id = deviceId,
                device_name = DeviceInfo.Name,
                device_model = DeviceInfo.Model,
                platform = DeviceInfo.Platform.ToString(),
                app_version = AppInfo.Current.VersionString
            };

            // ส่งข้อมูลไป Server (ไม่ต้องส่ง API Key เพราะ Server จะสร้างให้)
            using var httpClient = new HttpClient();
            httpClient.Timeout = TimeSpan.FromSeconds(30);

            var json = JsonSerializer.Serialize(registrationData);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            var registerUrl = serverUrl.TrimEnd('/') + "/api/pos/register";
            var response = await httpClient.PostAsync(registerUrl, content);
            var responseBody = await response.Content.ReadAsStringAsync();

            if (response.IsSuccessStatusCode)
            {
                // Parse response
                var result = JsonSerializer.Deserialize<RegistrationResponse>(responseBody, new JsonSerializerOptions
                {
                    PropertyNameCaseInsensitive = true
                });

                if (result?.Success == true)
                {
                    // Server ส่ง API Key กลับมา
                    var apiKey = result.Data?.ApiKey;
                    if (string.IsNullOrEmpty(apiKey))
                    {
                        return new LicenseActivationResult
                        {
                            Success = false,
                            Message = "Server ไม่ได้ส่ง API Key กลับมา",
                            ErrorCode = "NO_API_KEY_RETURNED"
                        };
                    }

                    // สร้าง License Info
                    var license = new LicenseInfo
                    {
                        ProductKey = productKey,
                        ApiKey = apiKey,
                        DeviceId = deviceId,
                        ShopName = result.Data?.ShopName ?? "",
                        ShopId = result.Data?.ShopId ?? 0,
                        PosTerminalId = result.Data?.PosTerminalId ?? 0,
                        Status = LicenseStatus.Activated,
                        RegisteredAt = DateTime.UtcNow,
                        ExpiresAt = result.Data?.ExpiresAt,
                        ServerUrl = serverUrl
                    };

                    // บันทึก License
                    await SaveLicenseAsync(license);
                    CurrentLicense = license;

                    // บันทึก Server URL และ API Key (ที่ได้รับจาก Server)
                    Preferences.Set("server_url", serverUrl);
                    Preferences.Set("api_key", apiKey);
                    Preferences.Set("shop_code", shopCode);
                    Preferences.Set("shop_name", license.ShopName);
                    Preferences.Set("shop_id", license.ShopId);

                    return new LicenseActivationResult
                    {
                        Success = true,
                        Message = "ลงทะเบียนสำเร็จ",
                        License = license
                    };
                }
                else
                {
                    return new LicenseActivationResult
                    {
                        Success = false,
                        Message = result?.Message ?? "การลงทะเบียนล้มเหลว",
                        ErrorCode = result?.ErrorCode ?? "REGISTRATION_FAILED"
                    };
                }
            }
            else
            {
                // Handle error responses
                var errorMessage = response.StatusCode switch
                {
                    System.Net.HttpStatusCode.BadRequest => "Product Key ไม่ถูกต้อง",
                    System.Net.HttpStatusCode.NotFound => "ไม่พบร้านค้าจากรหัสที่ระบุ",
                    System.Net.HttpStatusCode.Conflict => "Product Key นี้ถูกใช้งานแล้ว",
                    System.Net.HttpStatusCode.InternalServerError => "Server เกิดข้อผิดพลาด",
                    _ => $"เกิดข้อผิดพลาด: {response.StatusCode}"
                };

                return new LicenseActivationResult
                {
                    Success = false,
                    Message = errorMessage,
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
            System.Diagnostics.Debug.WriteLine($"Registration error: {ex}");
            return new LicenseActivationResult
            {
                Success = false,
                Message = $"เกิดข้อผิดพลาด: {ex.Message}",
                ErrorCode = "UNKNOWN_ERROR"
            };
        }
    }

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
                    // License ถูกเพิกถอน
                    CurrentLicense.Status = LicenseStatus.Invalid;
                    CurrentLicense.ErrorMessage = result?.Message ?? "License ไม่ถูกต้อง";
                    await SaveLicenseAsync(CurrentLicense);
                    return false;
                }
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.Unauthorized ||
                     response.StatusCode == System.Net.HttpStatusCode.Forbidden)
            {
                CurrentLicense.Status = LicenseStatus.Suspended;
                CurrentLicense.ErrorMessage = "License ถูกระงับการใช้งาน";
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

                // ตรวจสอบกับ Server (background)
                _ = ValidateLicenseAsync();

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
        Preferences.Remove("server_url");
        Preferences.Remove("api_key");
        Preferences.Remove("shop_name");
        Preferences.Remove("shop_id");
        CurrentLicense = null;
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
            var key = DeriveKey(SECRET_KEY);
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
            var key = DeriveKey(SECRET_KEY);
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

    private class RegistrationResponse
    {
        public bool Success { get; set; }
        public string? Message { get; set; }
        public string? ErrorCode { get; set; }
        public RegistrationData? Data { get; set; }
    }

    private class RegistrationData
    {
        public string? ApiKey { get; set; }
        public string? ShopName { get; set; }
        public int ShopId { get; set; }
        public int PosTerminalId { get; set; }
        public DateTime? ExpiresAt { get; set; }
    }

    private class ValidationResponse
    {
        public bool Valid { get; set; }
        public string? Message { get; set; }
        public DateTime? ExpiresAt { get; set; }
    }

    #endregion
}
