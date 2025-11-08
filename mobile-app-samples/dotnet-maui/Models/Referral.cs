using System.Text.Json.Serialization;

namespace ThaipromptAffiliate.Models
{
    /// <summary>
    /// Referral model
    /// </summary>
    public class Referral
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("referral_code")]
        public string ReferralCode { get; set; } = string.Empty;

        [JsonPropertyName("user")]
        public ReferralUser? User { get; set; }

        [JsonPropertyName("status")]
        public string Status { get; set; } = string.Empty;

        [JsonPropertyName("total_earnings")]
        public decimal TotalEarnings { get; set; }

        [JsonPropertyName("level")]
        public int Level { get; set; }

        [JsonPropertyName("created_at")]
        public DateTime CreatedAt { get; set; }

        // UI helper properties
        public string FormattedEarnings => $"฿{TotalEarnings:N2}";

        public string StatusColor => Status switch
        {
            "active" => "#10B981",
            "inactive" => "#6B7280",
            "blocked" => "#EF4444",
            _ => "#F59E0B"
        };

        public string StatusText => Status switch
        {
            "active" => "ใช้งานอยู่",
            "inactive" => "ไม่ได้ใช้งาน",
            "blocked" => "ถูกบล็อก",
            _ => "ไม่ทราบสถานะ"
        };

        public string LevelText => $"ระดับ {Level}";
    }

    /// <summary>
    /// Referral user (simplified user info)
    /// </summary>
    public class ReferralUser
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("name")]
        public string Name { get; set; } = string.Empty;

        [JsonPropertyName("email")]
        public string Email { get; set; } = string.Empty;

        [JsonPropertyName("avatar")]
        public string? Avatar { get; set; }
    }

    /// <summary>
    /// Referrals response model
    /// </summary>
    public class ReferralsResponse
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("data")]
        public ReferralsData? Data { get; set; }
    }

    /// <summary>
    /// Referrals data
    /// </summary>
    public class ReferralsData
    {
        [JsonPropertyName("referrals")]
        public List<Referral> Referrals { get; set; } = new();

        [JsonPropertyName("referral_link")]
        public string ReferralLink { get; set; } = string.Empty;

        [JsonPropertyName("referral_code")]
        public string ReferralCode { get; set; } = string.Empty;

        [JsonPropertyName("total_referrals")]
        public int TotalReferrals { get; set; }

        [JsonPropertyName("active_referrals")]
        public int ActiveReferrals { get; set; }
    }

    /// <summary>
    /// Referral tree node (for MLM structure)
    /// </summary>
    public class ReferralTreeNode
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("name")]
        public string Name { get; set; } = string.Empty;

        [JsonPropertyName("email")]
        public string Email { get; set; } = string.Empty;

        [JsonPropertyName("level")]
        public int Level { get; set; }

        [JsonPropertyName("total_earnings")]
        public decimal TotalEarnings { get; set; }

        [JsonPropertyName("children")]
        public List<ReferralTreeNode> Children { get; set; } = new();

        [JsonPropertyName("created_at")]
        public DateTime CreatedAt { get; set; }
    }
}
