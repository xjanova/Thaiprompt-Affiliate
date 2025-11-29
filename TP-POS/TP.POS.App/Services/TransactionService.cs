using TP.POS.Core.Entities;
using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Api;
using TP.POS.Infrastructure.Data;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับจัดการรายการขาย
/// </summary>
public class TransactionService : ITransactionService
{
    private readonly PosDatabase _database;
    private readonly TpAffiliateApiClient _apiClient;

    public TransactionService(PosDatabase database, TpAffiliateApiClient apiClient)
    {
        _database = database;
        _apiClient = apiClient;
    }

    public async Task<Transaction> CreateAsync(Transaction transaction, List<TransactionItem> items)
    {
        await _database.SaveTransactionAsync(transaction);
        await _database.SaveTransactionItemsAsync(items);
        return transaction;
    }

    public async Task<Transaction?> GetByIdAsync(int id)
    {
        var transaction = await _database.GetTransactionByIdAsync(id);
        if (transaction != null)
        {
            transaction.Items = await _database.GetTransactionItemsAsync(id);
        }
        return transaction;
    }

    public async Task<Transaction?> GetByReceiptNumberAsync(string receiptNumber)
    {
        // จะ implement เพิ่มเติม
        return null;
    }

    public async Task<List<Transaction>> GetTodayTransactionsAsync()
    {
        return await _database.GetTodayTransactionsAsync();
    }

    public async Task<List<Transaction>> GetByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        // จะ implement เพิ่มเติม
        return new List<Transaction>();
    }

    public async Task<List<Transaction>> GetRecentAsync(int count = 20)
    {
        var transactions = await GetTodayTransactionsAsync();
        return transactions.Take(count).ToList();
    }

    public async Task<List<Transaction>> SearchAsync(string keyword, DateTime? startDate = null, DateTime? endDate = null)
    {
        // จะ implement เพิ่มเติม
        return new List<Transaction>();
    }

    public async Task<List<TransactionItem>> GetItemsAsync(int transactionId)
    {
        return await _database.GetTransactionItemsAsync(transactionId);
    }

    public async Task<List<TransactionItem>> GetItemsByUuidAsync(string transactionUuid)
    {
        // จะ implement เพิ่มเติม
        return new List<TransactionItem>();
    }

    public async Task<bool> CancelAsync(int transactionId, string reason)
    {
        var transaction = await GetByIdAsync(transactionId);
        if (transaction != null)
        {
            transaction.Status = Core.Enums.TransactionStatus.Cancelled;
            transaction.Notes = reason;
            await _database.SaveTransactionAsync(transaction);
            return true;
        }
        return false;
    }

    public async Task<Transaction> RefundAsync(int transactionId, List<TransactionItem> itemsToRefund, string reason)
    {
        // จะ implement เพิ่มเติม
        throw new NotImplementedException();
    }

    public async Task<string> GenerateReceiptNumberAsync()
    {
        var today = DateTime.Now.ToString("yyyyMMdd");
        var todayTransactions = await GetTodayTransactionsAsync();
        var sequence = todayTransactions.Count + 1;
        return $"INV{today}{sequence:D4}";
    }

    public async Task<List<Transaction>> GetUnsyncedAsync()
    {
        return await _database.GetUnsyncedTransactionsAsync();
    }

    public async Task<bool> MarkAsSyncedAsync(int transactionId, int serverId)
    {
        var transaction = await GetByIdAsync(transactionId);
        if (transaction != null)
        {
            transaction.IsSynced = true;
            transaction.ServerId = serverId;
            transaction.LastSyncedAt = DateTime.UtcNow;
            await _database.SaveTransactionAsync(transaction);
            return true;
        }
        return false;
    }

    public async Task<SyncResult> SyncToServerAsync()
    {
        var result = new SyncResult { Success = true };

        try
        {
            var unsyncedTransactions = await GetUnsyncedAsync();

            foreach (var transaction in unsyncedTransactions)
            {
                var items = await GetItemsAsync(transaction.Id);
                var response = await _apiClient.SyncTransactionAsync(transaction, items);

                if (response?.Success == true && response.Data != null)
                {
                    await MarkAsSyncedAsync(transaction.Id, response.Data.ServerId);
                    result.SyncedCount++;
                }
                else
                {
                    result.FailedCount++;
                    result.Errors.Add($"Failed to sync {transaction.ReceiptNumber}");
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
}
