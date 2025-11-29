using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Data;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับจัดการตะกร้าสินค้า
/// </summary>
public class CartService : ICartService
{
    private readonly PosDatabase _database;
    private readonly IProductService _productService;
    private decimal _discountPercent;
    private readonly List<HeldCart> _heldCarts = new();

    public event EventHandler<CartChangedEventArgs>? CartChanged;

    public CartService(PosDatabase database, IProductService productService)
    {
        _database = database;
        _productService = productService;
    }

    public async Task<List<CartItem>> GetAllItemsAsync()
    {
        return await _database.GetCartItemsAsync();
    }

    public async Task<CartItem> AddItemAsync(Product product, int quantity = 1)
    {
        var items = await GetAllItemsAsync();
        var existingItem = items.FirstOrDefault(i => i.ProductId == product.Id);

        if (existingItem != null)
        {
            existingItem.Quantity += quantity;
            await _database.SaveCartItemAsync(existingItem);
            RaiseCartChanged();
            return existingItem;
        }

        var newItem = CartItem.FromProduct(product, quantity);
        await _database.SaveCartItemAsync(newItem);
        RaiseCartChanged();
        return newItem;
    }

    public async Task<CartItem?> AddByBarcodeAsync(string barcode, int quantity = 1)
    {
        var product = await _productService.GetByBarcodeAsync(barcode);
        if (product != null)
        {
            return await AddItemAsync(product, quantity);
        }
        return null;
    }

    public async Task<CartItem?> UpdateQuantityAsync(int cartItemId, int quantity)
    {
        var items = await GetAllItemsAsync();
        var item = items.FirstOrDefault(i => i.Id == cartItemId);

        if (item != null)
        {
            if (quantity <= 0)
            {
                await RemoveItemAsync(cartItemId);
                return null;
            }

            item.Quantity = quantity;
            await _database.SaveCartItemAsync(item);
            RaiseCartChanged();
        }
        return item;
    }

    public async Task<CartItem?> IncrementQuantityAsync(int cartItemId)
    {
        var items = await GetAllItemsAsync();
        var item = items.FirstOrDefault(i => i.Id == cartItemId);

        if (item != null)
        {
            item.Quantity++;
            await _database.SaveCartItemAsync(item);
            RaiseCartChanged();
        }
        return item;
    }

    public async Task<CartItem?> DecrementQuantityAsync(int cartItemId)
    {
        var items = await GetAllItemsAsync();
        var item = items.FirstOrDefault(i => i.Id == cartItemId);

        if (item != null)
        {
            if (item.Quantity <= 1)
            {
                await RemoveItemAsync(cartItemId);
                return null;
            }

            item.Quantity--;
            await _database.SaveCartItemAsync(item);
            RaiseCartChanged();
        }
        return item;
    }

    public async Task<bool> RemoveItemAsync(int cartItemId)
    {
        var result = await _database.DeleteCartItemAsync(cartItemId);
        RaiseCartChanged();
        return result > 0;
    }

    public async Task ClearAsync()
    {
        await _database.ClearCartAsync();
        _discountPercent = 0;
        RaiseCartChanged();
    }

    public Task ApplyDiscountPercentAsync(decimal percent)
    {
        _discountPercent = percent;
        RaiseCartChanged();
        return Task.CompletedTask;
    }

    public Task ApplyDiscountAmountAsync(decimal amount)
    {
        // คำนวณ % จาก amount
        // จะ implement เพิ่มเติม
        RaiseCartChanged();
        return Task.CompletedTask;
    }

    public async Task ApplyItemDiscountAsync(int cartItemId, decimal discountPerUnit)
    {
        var items = await GetAllItemsAsync();
        var item = items.FirstOrDefault(i => i.Id == cartItemId);

        if (item != null)
        {
            item.DiscountPerUnit = discountPerUnit;
            await _database.SaveCartItemAsync(item);
            RaiseCartChanged();
        }
    }

    public async Task<CartSummary> GetSummaryAsync()
    {
        var items = await GetAllItemsAsync();

        var subtotal = items.Sum(i => i.Subtotal);
        var itemDiscount = items.Sum(i => i.TotalDiscount);
        var percentDiscount = subtotal * (_discountPercent / 100);
        var totalDiscount = itemDiscount + percentDiscount;
        var afterDiscount = subtotal - totalDiscount;
        var tax = afterDiscount * 0.07m; // 7% VAT
        var total = afterDiscount + tax;

        return new CartSummary
        {
            ItemCount = items.Count,
            TotalQuantity = items.Sum(i => i.Quantity),
            Subtotal = subtotal,
            DiscountAmount = totalDiscount,
            DiscountPercent = _discountPercent,
            TaxAmount = tax,
            TotalAmount = total
        };
    }

    public async Task<int> GetItemCountAsync()
    {
        var items = await GetAllItemsAsync();
        return items.Count;
    }

    public async Task<Transaction> CheckoutAsync(
        PaymentMethod paymentMethod,
        decimal receivedAmount,
        Customer? customer = null,
        string? paymentReference = null,
        string? notes = null)
    {
        var items = await GetAllItemsAsync();
        var summary = await GetSummaryAsync();

        var transaction = new Transaction
        {
            ReceiptNumber = await GenerateReceiptNumberAsync(),
            CustomerId = customer?.Id,
            CustomerName = customer?.FullName,
            TransactionDate = DateTime.Now,
            Subtotal = summary.Subtotal,
            DiscountAmount = summary.DiscountAmount,
            DiscountPercent = summary.DiscountPercent,
            TaxAmount = summary.TaxAmount,
            TotalAmount = summary.TotalAmount,
            ReceivedAmount = receivedAmount,
            ChangeAmount = receivedAmount - summary.TotalAmount,
            PaymentMethod = paymentMethod,
            PaymentReference = paymentReference,
            Notes = notes,
            Status = TransactionStatus.Completed,
            IsOffline = true // จะถูก sync ทีหลัง
        };

        // บันทึก Transaction
        await _database.SaveTransactionAsync(transaction);

        // บันทึก Transaction Items
        var transactionItems = items.Select(i => new TransactionItem
        {
            TransactionId = transaction.Id,
            TransactionUuid = transaction.Uuid,
            ProductId = i.ProductId,
            Sku = i.Sku,
            Barcode = i.Barcode,
            ProductName = i.ProductName,
            Quantity = i.Quantity,
            UnitPrice = i.UnitPrice,
            CostPrice = i.CostPrice,
            DiscountPerUnit = i.DiscountPerUnit,
            TaxRate = i.TaxRate
        }).ToList();

        foreach (var item in transactionItems)
        {
            item.CalculateLineTotal();
        }

        await _database.SaveTransactionItemsAsync(transactionItems);

        // อัพเดทสต็อก
        foreach (var item in items)
        {
            await _productService.UpdateStockAsync(item.ProductId, -item.Quantity);
        }

        // ล้างตะกร้า
        await ClearAsync();

        transaction.Items = transactionItems;
        return transaction;
    }

    private async Task<string> GenerateReceiptNumberAsync()
    {
        var today = DateTime.Now.ToString("yyyyMMdd");
        var todayTransactions = await _database.GetTodayTransactionsAsync();
        var sequence = todayTransactions.Count + 1;
        return $"INV{today}{sequence:D4}";
    }

    public async Task<string> HoldAsync(string? notes = null)
    {
        var items = await GetAllItemsAsync();
        var summary = await GetSummaryAsync();

        var holdId = Guid.NewGuid().ToString("N").Substring(0, 8).ToUpper();

        _heldCarts.Add(new HeldCart
        {
            HoldId = holdId,
            Items = items.ToList(),
            Summary = summary,
            Notes = notes,
            HeldAt = DateTime.Now
        });

        await ClearAsync();
        return holdId;
    }

    public Task<List<HeldCart>> GetHeldCartsAsync()
    {
        return Task.FromResult(_heldCarts.ToList());
    }

    public async Task<bool> RecallHeldCartAsync(string holdId)
    {
        var heldCart = _heldCarts.FirstOrDefault(h => h.HoldId == holdId);
        if (heldCart == null) return false;

        await ClearAsync();

        foreach (var item in heldCart.Items)
        {
            await _database.SaveCartItemAsync(item);
        }

        _heldCarts.Remove(heldCart);
        RaiseCartChanged();
        return true;
    }

    public Task<bool> DeleteHeldCartAsync(string holdId)
    {
        var heldCart = _heldCarts.FirstOrDefault(h => h.HoldId == holdId);
        if (heldCart != null)
        {
            _heldCarts.Remove(heldCart);
            return Task.FromResult(true);
        }
        return Task.FromResult(false);
    }

    private async void RaiseCartChanged()
    {
        var summary = await GetSummaryAsync();
        CartChanged?.Invoke(this, new CartChangedEventArgs
        {
            Summary = summary,
            ChangeType = "update"
        });
    }
}
