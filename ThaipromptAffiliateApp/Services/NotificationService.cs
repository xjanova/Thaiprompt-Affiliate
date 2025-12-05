namespace ThaipromptAffiliateApp.Services;

/// <summary>
/// บริการจัดการ Push Notification
/// รองรับการลงทะเบียน FCM/APNs และ Local Notifications
/// </summary>
public class NotificationService : INotificationService
{
    // ============================================
    // CONSTANTS
    // ============================================

    /// <summary>
    /// Key สำหรับเก็บ Device Token
    /// </summary>
    private const string DeviceTokenKey = "device_push_token";

    /// <summary>
    /// Key สำหรับเก็บสถานะการลงทะเบียน
    /// </summary>
    private const string IsRegisteredKey = "is_push_registered";

    // ============================================
    // EVENTS
    // ============================================

    /// <summary>
    /// Event เมื่อได้รับ Push Notification
    /// </summary>
    public event EventHandler<NotificationEventArgs>? NotificationReceived;

    /// <summary>
    /// Event เมื่อผู้ใช้กดที่ Notification
    /// </summary>
    public event EventHandler<NotificationEventArgs>? NotificationTapped;

    // ============================================
    // PRIVATE FIELDS
    // ============================================

    private string? _deviceToken;
    private bool _isRegistered;
    private readonly IApiService? _apiService;

    // ============================================
    // CONSTRUCTOR
    // ============================================

    public NotificationService()
    {
        // โหลดค่าจาก Storage
        LoadStoredValues();
    }

    public NotificationService(IApiService apiService) : this()
    {
        _apiService = apiService;
    }

    /// <summary>
    /// โหลดค่าที่เก็บไว้จาก Preferences
    /// </summary>
    private void LoadStoredValues()
    {
        _deviceToken = Preferences.Default.Get<string?>(DeviceTokenKey, null);
        _isRegistered = Preferences.Default.Get(IsRegisteredKey, false);
    }

    // ============================================
    // REGISTRATION METHODS
    // ============================================

    /// <summary>
    /// ลงทะเบียนเพื่อรับ Push Notifications
    /// </summary>
    /// <returns>Device Token สำหรับ Push</returns>
    public async Task<string?> RegisterForPushNotificationsAsync()
    {
        try
        {
            // ตรวจสอบ/ขอสิทธิ์
            var hasPermission = await RequestNotificationPermissionAsync();
            if (!hasPermission)
            {
                System.Diagnostics.Debug.WriteLine("ไม่ได้รับอนุญาตให้แจ้งเตือน");
                return null;
            }

            // ลงทะเบียนกับ Platform (FCM/APNs)
            _deviceToken = await GetPlatformTokenAsync();

            if (!string.IsNullOrEmpty(_deviceToken))
            {
                // บันทึก Token
                Preferences.Default.Set(DeviceTokenKey, _deviceToken);
                Preferences.Default.Set(IsRegisteredKey, true);
                _isRegistered = true;

                // ส่ง Token ไปยัง Server
                await RegisterTokenWithServerAsync(_deviceToken);

                System.Diagnostics.Debug.WriteLine($"ลงทะเบียน Push สำเร็จ: {_deviceToken}");
            }

            return _deviceToken;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ลงทะเบียน Push ผิดพลาด: {ex.Message}");
            return null;
        }
    }

    /// <summary>
    /// ยกเลิกการลงทะเบียน Push Notifications
    /// </summary>
    public async Task UnregisterPushNotificationsAsync()
    {
        try
        {
            if (!string.IsNullOrEmpty(_deviceToken))
            {
                // แจ้ง Server ว่ายกเลิกการลงทะเบียน
                await UnregisterTokenFromServerAsync(_deviceToken);
            }

            // ล้างค่าที่เก็บไว้
            Preferences.Default.Remove(DeviceTokenKey);
            Preferences.Default.Set(IsRegisteredKey, false);
            _deviceToken = null;
            _isRegistered = false;

            System.Diagnostics.Debug.WriteLine("ยกเลิกการลงทะเบียน Push สำเร็จ");
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ยกเลิกการลงทะเบียน Push ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// ดึง Platform Token (FCM/APNs)
    /// </summary>
    private async Task<string?> GetPlatformTokenAsync()
    {
        // TODO: ใช้ Firebase Cloud Messaging Plugin หรือ Native APIs
        // สำหรับตอนนี้จะสร้าง mock token

        await Task.Delay(100); // Simulate async operation

#if ANDROID
        // Android: ใช้ Firebase Cloud Messaging
        // var token = await FirebaseMessaging.Instance.GetTokenAsync();
        return $"fcm_token_{Guid.NewGuid():N}";
#elif IOS
        // iOS: ใช้ Apple Push Notification Service
        // var token = await UIApplication.SharedApplication.RegisterForRemoteNotificationsAsync();
        return $"apns_token_{Guid.NewGuid():N}";
#else
        // Other platforms
        return $"mock_token_{Guid.NewGuid():N}";
#endif
    }

    /// <summary>
    /// ส่ง Token ไปลงทะเบียนที่ Server
    /// </summary>
    private async Task RegisterTokenWithServerAsync(string token)
    {
        try
        {
            // TODO: เรียก API เพื่อลงทะเบียน Token
            // await _apiService?.RegisterDeviceTokenAsync(token);

            System.Diagnostics.Debug.WriteLine($"ส่ง Token ไปยัง Server: {token}");
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ลงทะเบียน Token กับ Server ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// แจ้ง Server ว่ายกเลิก Token
    /// </summary>
    private async Task UnregisterTokenFromServerAsync(string token)
    {
        try
        {
            // TODO: เรียก API เพื่อยกเลิก Token
            // await _apiService?.UnregisterDeviceTokenAsync(token);

            System.Diagnostics.Debug.WriteLine($"ยกเลิก Token จาก Server: {token}");
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ยกเลิก Token จาก Server ผิดพลาด: {ex.Message}");
        }
    }

    // ============================================
    // PERMISSION METHODS
    // ============================================

    /// <summary>
    /// ตรวจสอบว่าได้รับอนุญาตให้แจ้งเตือนหรือไม่
    /// </summary>
    public async Task<bool> CheckNotificationPermissionAsync()
    {
        try
        {
            var status = await Permissions.CheckStatusAsync<Permissions.PostNotifications>();
            return status == PermissionStatus.Granted;
        }
        catch
        {
            // บางแพลตฟอร์มอาจไม่รองรับ PostNotifications permission
            return true;
        }
    }

    /// <summary>
    /// ขอสิทธิ์การแจ้งเตือน
    /// </summary>
    public async Task<bool> RequestNotificationPermissionAsync()
    {
        try
        {
            var status = await Permissions.CheckStatusAsync<Permissions.PostNotifications>();

            if (status == PermissionStatus.Granted)
                return true;

            if (status == PermissionStatus.Denied)
            {
                // แสดง dialog อธิบายเหตุผล
                System.Diagnostics.Debug.WriteLine("สิทธิ์การแจ้งเตือนถูกปฏิเสธ");
            }

            // ขอสิทธิ์
            status = await Permissions.RequestAsync<Permissions.PostNotifications>();
            return status == PermissionStatus.Granted;
        }
        catch
        {
            // บางแพลตฟอร์มอาจไม่รองรับ
            return true;
        }
    }

    // ============================================
    // LOCAL NOTIFICATION METHODS
    // ============================================

    /// <summary>
    /// แสดง Local Notification
    /// </summary>
    /// <param name="title">หัวข้อ</param>
    /// <param name="body">เนื้อหา</param>
    /// <param name="data">ข้อมูลเพิ่มเติม</param>
    public async Task ShowLocalNotificationAsync(string title, string body, Dictionary<string, string>? data = null)
    {
        try
        {
            // TODO: ใช้ Plugin.LocalNotification หรือ Native APIs
            // var notification = new NotificationRequest
            // {
            //     NotificationId = new Random().Next(1000, 9999),
            //     Title = title,
            //     Description = body,
            //     ReturningData = JsonSerializer.Serialize(data ?? new Dictionary<string, string>())
            // };
            // await LocalNotificationCenter.Current.Show(notification);

            System.Diagnostics.Debug.WriteLine($"แสดง Local Notification: {title} - {body}");
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"แสดง Local Notification ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// ยกเลิก Notification ทั้งหมด
    /// </summary>
    public async Task CancelAllNotificationsAsync()
    {
        try
        {
            // TODO: ยกเลิก notifications ทั้งหมด
            // LocalNotificationCenter.Current.CancelAll();

            System.Diagnostics.Debug.WriteLine("ยกเลิก Notifications ทั้งหมด");
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ยกเลิก Notifications ผิดพลาด: {ex.Message}");
        }
    }

    // ============================================
    // UTILITY METHODS
    // ============================================

    /// <summary>
    /// ดึง Device Token ปัจจุบัน
    /// </summary>
    public string? GetDeviceToken()
    {
        return _deviceToken;
    }

    /// <summary>
    /// อัพเดท Badge Count
    /// </summary>
    /// <param name="count">จำนวน Badge</param>
    public async Task UpdateBadgeCountAsync(int count)
    {
        try
        {
#if IOS
            // iOS: อัพเดท badge บน app icon
            // UIApplication.SharedApplication.ApplicationIconBadgeNumber = count;
#endif
            System.Diagnostics.Debug.WriteLine($"อัพเดท Badge Count: {count}");
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"อัพเดท Badge Count ผิดพลาด: {ex.Message}");
        }
    }

    // ============================================
    // NOTIFICATION HANDLERS (เรียกจาก Platform)
    // ============================================

    /// <summary>
    /// เรียกเมื่อได้รับ Notification (จาก Platform-specific code)
    /// </summary>
    public void OnNotificationReceived(string title, string body, Dictionary<string, string>? data)
    {
        var args = new NotificationEventArgs
        {
            Title = title,
            Body = body,
            Data = data ?? new Dictionary<string, string>(),
            Type = ParseNotificationType(data),
            ReceivedAt = DateTime.Now
        };

        NotificationReceived?.Invoke(this, args);

        System.Diagnostics.Debug.WriteLine($"ได้รับ Notification: {title}");
    }

    /// <summary>
    /// เรียกเมื่อผู้ใช้กดที่ Notification (จาก Platform-specific code)
    /// </summary>
    public void OnNotificationTapped(string title, string body, Dictionary<string, string>? data)
    {
        var args = new NotificationEventArgs
        {
            Title = title,
            Body = body,
            Data = data ?? new Dictionary<string, string>(),
            Type = ParseNotificationType(data),
            ReceivedAt = DateTime.Now
        };

        NotificationTapped?.Invoke(this, args);

        System.Diagnostics.Debug.WriteLine($"กด Notification: {title}");

        // Navigate ไปยังหน้าที่เกี่ยวข้อง
        HandleNotificationNavigation(args);
    }

    /// <summary>
    /// แปลงประเภท Notification จาก data
    /// </summary>
    private static NotificationType ParseNotificationType(Dictionary<string, string>? data)
    {
        if (data == null || !data.TryGetValue("type", out var typeStr))
            return NotificationType.General;

        return typeStr.ToLowerInvariant() switch
        {
            "order" or "order_update" => NotificationType.OrderUpdate,
            "commission" => NotificationType.Commission,
            "promotion" or "promo" => NotificationType.Promotion,
            "news" => NotificationType.News,
            "system" => NotificationType.System,
            _ => NotificationType.General
        };
    }

    /// <summary>
    /// จัดการ Navigation เมื่อกด Notification
    /// </summary>
    private void HandleNotificationNavigation(NotificationEventArgs args)
    {
        MainThread.BeginInvokeOnMainThread(async () =>
        {
            try
            {
                switch (args.Type)
                {
                    case NotificationType.OrderUpdate:
                        // ไปหน้า Orders
                        if (args.Data.TryGetValue("order_id", out var orderId))
                        {
                            // Navigate ไปหน้ารายละเอียด Order
                        }
                        break;

                    case NotificationType.Commission:
                        // ไปหน้า Commissions
                        Application.Current!.MainPage = new AppShell();
                        await Shell.Current.GoToAsync("//commissions");
                        break;

                    case NotificationType.Promotion:
                        // ไปหน้า Shopping พร้อม promotion
                        if (args.Data.TryGetValue("promo_url", out var promoUrl))
                        {
                            // เปิด URL โปรโมชั่น
                        }
                        break;

                    case NotificationType.News:
                        // ไปหน้า News/Wiki
                        // Application.Current!.MainPage = WebViewPage.CreateNewsPage();
                        break;

                    default:
                        // ไปหน้าหลัก
                        // Application.Current!.MainPage = new MainMenuPage();
                        break;
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Navigation ผิดพลาด: {ex.Message}");
            }
        });
    }
}
