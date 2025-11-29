using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Api;
using TP.POS.Infrastructure.Data;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับ Sync ข้อมูลกับ Server
/// </summary>
public class SyncService : ISyncService
{
    private readonly PosDatabase _database;
    private readonly TpAffiliateApiClient _apiClient;
    private readonly IProductService _productService;
    private readonly ITransactionService _transactionService;
    private Timer? _autoSyncTimer;

    public event EventHandler<SyncStatusEventArgs>? SyncStatusChanged;

    public bool IsOnline { get; private set; }
    public bool IsSyncing { get; private set; }
    public DateTime? LastSyncTime { get; private set; }

    public SyncService(
        PosDatabase database,
        TpAffiliateApiClient apiClient,
        IProductService productService,
        ITransactionService transactionService)
    {
        _database = database;
        _apiClient = apiClient;
        _productService = productService;
        _transactionService = transactionService;
    }

    public async Task<bool> CheckConnectivityAsync()
    {
        try
        {
            IsOnline = await _apiClient.CheckConnectivityAsync();
            RaiseStatusChanged();
            return IsOnline;
        }
        catch
        {
            IsOnline = false;
            RaiseStatusChanged();
            return false;
        }
    }

    public async Task<SyncResult> SyncAllAsync()
    {
        if (IsSyncing) return new SyncResult { Success = false, ErrorMessage = "กำลัง Sync อยู่" };

        IsSyncing = true;
        RaiseStatusChanged();

        var result = new SyncResult { Success = true };

        try
        {
            // Sync สินค้า
            var productResult = await SyncProductsAsync();
            result.SyncedCount += productResult.SyncedCount;
            result.FailedCount += productResult.FailedCount;

            // Sync หมวดหมู่
            var categoryResult = await SyncCategoriesAsync();
            result.SyncedCount += categoryResult.SyncedCount;

            // Sync รายการขาย
            var transactionResult = await SyncTransactionsAsync();
            result.SyncedCount += transactionResult.SyncedCount;
            result.FailedCount += transactionResult.FailedCount;

            LastSyncTime = DateTime.UtcNow;
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.ErrorMessage = ex.Message;
        }
        finally
        {
            IsSyncing = false;
            RaiseStatusChanged();
        }

        return result;
    }

    public async Task<SyncResult> SyncProductsAsync()
    {
        var result = new SyncResult { Success = true };
        try
        {
            var lastSync = await _database.GetSettingAsync("LastProductSync");
            var since = lastSync != null ? DateTime.Parse(lastSync) : (DateTime?)null;

            var count = await _productService.SyncFromServerAsync(since);
            result.SyncedCount = count;

            await _database.SetSettingAsync("LastProductSync", DateTime.UtcNow.ToString("O"));
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.ErrorMessage = ex.Message;
        }
        return result;
    }

    public async Task<SyncResult> SyncCategoriesAsync()
    {
        var result = new SyncResult { Success = true };
        try
        {
            var response = await _apiClient.GetAllCategoriesAsync();
            if (response?.Success == true && response.Data != null)
            {
                foreach (var category in response.Data)
                {
                    await _database.SaveCategoryAsync(category);
                }
                result.SyncedCount = response.Data.Count;
            }
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.ErrorMessage = ex.Message;
        }
        return result;
    }

    public async Task<SyncResult> SyncCustomersAsync()
    {
        var result = new SyncResult { Success = true };
        try
        {
            var response = await _apiClient.GetAllCustomersAsync();
            if (response?.Success == true && response.Data != null)
            {
                foreach (var customer in response.Data)
                {
                    await _database.SaveCustomerAsync(customer);
                }
                result.SyncedCount = response.Data.Count;
            }
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.ErrorMessage = ex.Message;
        }
        return result;
    }

    public async Task<SyncResult> SyncTransactionsAsync()
    {
        return await _transactionService.SyncToServerAsync();
    }

    public async Task<SyncResult> SyncStockMovementsAsync()
    {
        var result = new SyncResult { Success = true };
        try
        {
            var unsyncedMovements = await _database.GetUnsyncedStockMovementsAsync();
            if (unsyncedMovements.Any())
            {
                var response = await _apiClient.SyncStockMovementsAsync(unsyncedMovements);
                if (response?.Success == true)
                {
                    foreach (var movement in unsyncedMovements)
                    {
                        movement.IsSynced = true;
                        movement.LastSyncedAt = DateTime.UtcNow;
                        await _database.SaveStockMovementAsync(movement);
                    }
                    result.SyncedCount = unsyncedMovements.Count;
                }
            }
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.ErrorMessage = ex.Message;
        }
        return result;
    }

    public async Task<PendingSyncCount> GetPendingCountAsync()
    {
        var transactions = await _transactionService.GetUnsyncedAsync();
        var stockMovements = await _database.GetUnsyncedStockMovementsAsync();

        return new PendingSyncCount
        {
            Transactions = transactions.Count,
            StockMovements = stockMovements.Count,
            Customers = 0 // จะ implement เพิ่มเติม
        };
    }

    public async Task<SyncResult> FullDownloadAsync(IProgress<SyncProgress>? progress = null)
    {
        var result = new SyncResult { Success = true };

        try
        {
            // ล้างข้อมูลเก่า
            progress?.Report(new SyncProgress { CurrentStep = "กำลังล้างข้อมูลเก่า...", Percentage = 0 });
            await _database.ClearAllDataAsync();

            // ดาวน์โหลดหมวดหมู่
            progress?.Report(new SyncProgress { CurrentStep = "กำลังดาวน์โหลดหมวดหมู่...", Percentage = 20 });
            await SyncCategoriesAsync();

            // ดาวน์โหลดสินค้า
            progress?.Report(new SyncProgress { CurrentStep = "กำลังดาวน์โหลดสินค้า...", Percentage = 40 });
            await _productService.ImportFromServerAsync();

            // ดาวน์โหลดลูกค้า
            progress?.Report(new SyncProgress { CurrentStep = "กำลังดาวน์โหลดลูกค้า...", Percentage = 70 });
            await SyncCustomersAsync();

            progress?.Report(new SyncProgress { CurrentStep = "เสร็จสิ้น!", Percentage = 100 });
        }
        catch (Exception ex)
        {
            result.Success = false;
            result.ErrorMessage = ex.Message;
        }

        return result;
    }

    public async Task StartAutoSyncAsync(TimeSpan interval)
    {
        _autoSyncTimer?.Dispose();
        _autoSyncTimer = new Timer(async _ =>
        {
            if (!IsSyncing && await CheckConnectivityAsync())
            {
                await SyncAllAsync();
            }
        }, null, interval, interval);
    }

    public Task StopAutoSyncAsync()
    {
        _autoSyncTimer?.Dispose();
        _autoSyncTimer = null;
        return Task.CompletedTask;
    }

    private void RaiseStatusChanged()
    {
        SyncStatusChanged?.Invoke(this, new SyncStatusEventArgs
        {
            IsOnline = IsOnline,
            IsSyncing = IsSyncing,
            StatusMessage = IsSyncing ? "กำลัง Sync..." : (IsOnline ? "ออนไลน์" : "ออฟไลน์")
        });
    }
}
