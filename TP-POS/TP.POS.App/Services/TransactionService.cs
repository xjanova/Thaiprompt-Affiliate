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

    public async Task<SalesSummary> GetSalesSummaryAsync(DateTime startDate, DateTime endDate)
    {
        var transactions = await _database.GetTransactionsByDateRangeAsync(startDate, endDate);
        var completedTransactions = transactions
            .Where(t => t.Status == Core.Enums.TransactionStatus.Completed)
            .ToList();

        return new SalesSummary
        {
            TotalSales = completedTransactions.Sum(t => t.TotalAmount),
            TransactionCount = completedTransactions.Count,
            AverageSale = completedTransactions.Count > 0
                ? completedTransactions.Average(t => t.TotalAmount)
                : 0,
            TotalDiscount = completedTransactions.Sum(t => t.DiscountAmount),
            TotalTax = completedTransactions.Sum(t => t.TaxAmount)
        };
    }

    public async Task<List<PaymentSummary>> GetPaymentBreakdownAsync(DateTime startDate, DateTime endDate)
    {
        var transactions = await _database.GetTransactionsByDateRangeAsync(startDate, endDate);
        var completedTransactions = transactions
            .Where(t => t.Status == Core.Enums.TransactionStatus.Completed)
            .ToList();

        var totalAmount = completedTransactions.Sum(t => t.TotalAmount);

        return completedTransactions
            .GroupBy(t => t.PaymentMethod)
            .Select(g => new PaymentSummary
            {
                PaymentMethod = g.Key,
                PaymentMethodName = g.Key.ToThaiName(),
                TotalAmount = g.Sum(t => t.TotalAmount),
                TransactionCount = g.Count(),
                Percentage = totalAmount > 0
                    ? Math.Round(g.Sum(t => t.TotalAmount) / totalAmount * 100, 2)
                    : 0
            })
            .OrderByDescending(p => p.TotalAmount)
            .ToList();
    }

    public async Task<List<TopProductSummary>> GetTopProductsAsync(DateTime startDate, DateTime endDate, int limit = 10)
    {
        var transactions = await _database.GetTransactionsByDateRangeAsync(startDate, endDate);
        var completedTransactionIds = transactions
            .Where(t => t.Status == Core.Enums.TransactionStatus.Completed)
            .Select(t => t.Id)
            .ToList();

        var allItems = new List<TransactionItem>();
        foreach (var transactionId in completedTransactionIds)
        {
            var items = await _database.GetTransactionItemsAsync(transactionId);
            allItems.AddRange(items);
        }

        return allItems
            .GroupBy(i => new { i.ProductId, i.ProductName, i.Sku })
            .Select(g => new TopProductSummary
            {
                ProductId = g.Key.ProductId,
                ProductName = g.Key.ProductName,
                Sku = g.Key.Sku,
                QuantitySold = g.Sum(i => i.Quantity),
                TotalRevenue = g.Sum(i => i.LineTotal)
            })
            .OrderByDescending(p => p.QuantitySold)
            .Take(limit)
            .ToList();
    }

    public async Task<List<DailySalesSummary>> GetDailySalesAsync(DateTime startDate, DateTime endDate)
    {
        var transactions = await _database.GetTransactionsByDateRangeAsync(startDate, endDate);
        var completedTransactions = transactions
            .Where(t => t.Status == Core.Enums.TransactionStatus.Completed)
            .ToList();

        return completedTransactions
            .GroupBy(t => t.TransactionDate.Date)
            .Select(g => new DailySalesSummary
            {
                Date = g.Key,
                TotalSales = g.Sum(t => t.TotalAmount),
                TransactionCount = g.Count()
            })
            .OrderBy(d => d.Date)
            .ToList();
    }
}
