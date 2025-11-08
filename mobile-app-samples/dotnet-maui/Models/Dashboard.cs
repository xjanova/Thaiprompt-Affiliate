using System.Text.Json.Serialization;

namespace ThaipromptAffiliate.Models
{
    /// <summary>
    /// Dashboard statistics model
    /// </summary>
    public class DashboardStats
    {
        [JsonPropertyName("total_earnings")]
        public decimal TotalEarnings { get; set; }

        [JsonPropertyName("pending_earnings")]
        public decimal PendingEarnings { get; set; }

        [JsonPropertyName("approved_earnings")]
        public decimal ApprovedEarnings { get; set; }

        [JsonPropertyName("total_referrals")]
        public int TotalReferrals { get; set; }

        [JsonPropertyName("active_referrals")]
        public int ActiveReferrals { get; set; }

        [JsonPropertyName("total_commissions")]
        public int TotalCommissions { get; set; }

        [JsonPropertyName("recent_commissions")]
        public List<Commission> RecentCommissions { get; set; } = new();

        [JsonPropertyName("monthly_earnings")]
        public decimal MonthlyEarnings { get; set; }

        [JsonPropertyName("growth_percentage")]
        public decimal GrowthPercentage { get; set; }
    }

    /// <summary>
    /// Commission model
    /// </summary>
    public class Commission
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("amount")]
        public decimal Amount { get; set; }

        [JsonPropertyName("status")]
        public string Status { get; set; } = string.Empty;

        [JsonPropertyName("description")]
        public string Description { get; set; } = string.Empty;

        [JsonPropertyName("type")]
        public string? Type { get; set; }

        [JsonPropertyName("level")]
        public int? Level { get; set; }

        [JsonPropertyName("created_at")]
        public DateTime CreatedAt { get; set; }

        [JsonPropertyName("approved_at")]
        public DateTime? ApprovedAt { get; set; }

        // UI helper properties
        public string StatusColor => Status switch
        {
            "approved" => "#10B981",
            "pending" => "#F59E0B",
            "rejected" => "#EF4444",
            _ => "#6B7280"
        };

        public string StatusText => Status switch
        {
            "approved" => "อนุมัติแล้ว",
            "pending" => "รอดำเนินการ",
            "rejected" => "ถูกปฏิเสธ",
            _ => "ไม่ทราบสถานะ"
        };

        public string FormattedAmount => $"฿{Amount:N2}";
    }

    /// <summary>
    /// Paginated commissions response
    /// </summary>
    public class CommissionsResponse
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("data")]
        public PaginatedCommissions? Data { get; set; }
    }

    /// <summary>
    /// Paginated data wrapper
    /// </summary>
    public class PaginatedCommissions
    {
        [JsonPropertyName("data")]
        public List<Commission> Data { get; set; } = new();

        [JsonPropertyName("current_page")]
        public int CurrentPage { get; set; }

        [JsonPropertyName("per_page")]
        public int PerPage { get; set; }

        [JsonPropertyName("total")]
        public int Total { get; set; }

        [JsonPropertyName("last_page")]
        public int LastPage { get; set; }

        public bool HasNextPage => CurrentPage < LastPage;
        public bool HasPreviousPage => CurrentPage > 1;
    }
}
