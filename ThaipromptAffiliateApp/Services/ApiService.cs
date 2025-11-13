using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text;
using System.Text.Json;
using ThaipromptAffiliateApp.Helpers;
using ThaipromptAffiliateApp.Models;

namespace ThaipromptAffiliateApp.Services
{
    /// <summary>
    /// Main API service for communicating with the backend
    /// </summary>
    public interface IApiService
    {
        Task<LoginResponse> LoginAsync(string email, string password);
        Task<bool> LogoutAsync();
        Task<User?> GetCurrentUserAsync();
        Task<DashboardStats?> GetDashboardStatsAsync();
        Task<PaginatedCommissions?> GetCommissionsAsync(int page = 1);
        Task<ReferralsData?> GetReferralsAsync();
        bool IsAuthenticated { get; }
    }

    public class ApiService : IApiService
    {
        private readonly HttpClient _httpClient;
        private string? _authToken;

        public bool IsAuthenticated => !string.IsNullOrEmpty(_authToken);

        public ApiService()
        {
            _httpClient = new HttpClient
            {
                BaseAddress = new Uri(Constants.ApiBaseUrl),
                Timeout = TimeSpan.FromSeconds(Constants.ApiTimeout)
            };

            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));

            // Load stored auth token
            LoadAuthTokenAsync().ConfigureAwait(false);
        }

        #region Authentication

        /// <summary>
        /// Login with email and password
        /// </summary>
        public async Task<LoginResponse> LoginAsync(string email, string password)
        {
            try
            {
                var loginData = new LoginRequest
                {
                    Email = email,
                    Password = password,
                    Remember = true
                };

                var response = await _httpClient.PostAsJsonAsync(
                    Constants.LoginEndpoint,
                    loginData);

                var result = await response.Content
                    .ReadFromJsonAsync<LoginResponse>();

                if (result?.Success == true && !string.IsNullOrEmpty(result.Data?.Token))
                {
                    await SetAuthTokenAsync(result.Data.Token);

                    // Store user data
                    if (result.Data.User != null)
                    {
                        await StoreUserDataAsync(result.Data.User);
                    }
                }

                return result ?? new LoginResponse
                {
                    Success = false,
                    Message = "Invalid response from server"
                };
            }
            catch (HttpRequestException ex)
            {
                Console.WriteLine($"Network error during login: {ex.Message}");
                return new LoginResponse
                {
                    Success = false,
                    Message = Constants.ErrorNetworkMessage
                };
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Login error: {ex.Message}");
                return new LoginResponse
                {
                    Success = false,
                    Message = Constants.ErrorServerMessage
                };
            }
        }

        /// <summary>
        /// Logout and clear stored data
        /// </summary>
        public async Task<bool> LogoutAsync()
        {
            try
            {
                if (IsAuthenticated)
                {
                    // Call logout endpoint
                    var response = await _httpClient.PostAsync(
                        Constants.LogoutEndpoint,
                        null);

                    // Even if server logout fails, clear local data
                }

                await ClearAuthDataAsync();
                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Logout error: {ex.Message}");
                // Clear local data anyway
                await ClearAuthDataAsync();
                return true;
            }
        }

        /// <summary>
        /// Get current authenticated user
        /// </summary>
        public async Task<User?> GetCurrentUserAsync()
        {
            try
            {
                await EnsureAuthenticatedAsync();

                var response = await _httpClient.GetFromJsonAsync<ApiResponse<User>>(
                    Constants.MeEndpoint);

                return response?.Data;
            }
            catch (HttpRequestException ex)
            {
                Console.WriteLine($"Network error getting user: {ex.Message}");
                throw;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get user error: {ex.Message}");
                throw;
            }
        }

        #endregion

        #region Dashboard

        /// <summary>
        /// Get dashboard statistics
        /// </summary>
        public async Task<DashboardStats?> GetDashboardStatsAsync()
        {
            try
            {
                await EnsureAuthenticatedAsync();

                var response = await _httpClient.GetFromJsonAsync<ApiResponse<DashboardStats>>(
                    Constants.DashboardStatsEndpoint);

                return response?.Data;
            }
            catch (HttpRequestException ex)
            {
                Console.WriteLine($"Network error getting dashboard stats: {ex.Message}");
                throw;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get dashboard stats error: {ex.Message}");
                throw;
            }
        }

        #endregion

        #region Commissions

        /// <summary>
        /// Get paginated commissions
        /// </summary>
        public async Task<PaginatedCommissions?> GetCommissionsAsync(int page = 1)
        {
            try
            {
                await EnsureAuthenticatedAsync();

                var response = await _httpClient.GetFromJsonAsync<CommissionsResponse>(
                    $"{Constants.CommissionsEndpoint}?page={page}");

                return response?.Data;
            }
            catch (HttpRequestException ex)
            {
                Console.WriteLine($"Network error getting commissions: {ex.Message}");
                throw;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get commissions error: {ex.Message}");
                throw;
            }
        }

        #endregion

        #region Referrals

        /// <summary>
        /// Get referrals data
        /// </summary>
        public async Task<ReferralsData?> GetReferralsAsync()
        {
            try
            {
                await EnsureAuthenticatedAsync();

                var response = await _httpClient.GetFromJsonAsync<ReferralsResponse>(
                    Constants.ReferralsEndpoint);

                return response?.Data;
            }
            catch (HttpRequestException ex)
            {
                Console.WriteLine($"Network error getting referrals: {ex.Message}");
                throw;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get referrals error: {ex.Message}");
                throw;
            }
        }

        #endregion

        #region Helper Methods

        /// <summary>
        /// Set authentication token
        /// </summary>
        private async Task SetAuthTokenAsync(string token)
        {
            _authToken = token;
            _httpClient.DefaultRequestHeaders.Authorization =
                new AuthenticationHeaderValue("Bearer", token);

            await SecureStorage.SetAsync(Constants.AuthTokenKey, token);
        }

        /// <summary>
        /// Load auth token from secure storage
        /// </summary>
        private async Task LoadAuthTokenAsync()
        {
            try
            {
                _authToken = await SecureStorage.GetAsync(Constants.AuthTokenKey);

                if (!string.IsNullOrEmpty(_authToken))
                {
                    _httpClient.DefaultRequestHeaders.Authorization =
                        new AuthenticationHeaderValue("Bearer", _authToken);
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error loading auth token: {ex.Message}");
            }
        }

        /// <summary>
        /// Store user data in secure storage
        /// </summary>
        private async Task StoreUserDataAsync(User user)
        {
            try
            {
                await SecureStorage.SetAsync(Constants.UserIdKey, user.Id.ToString());
                await SecureStorage.SetAsync(Constants.UserNameKey, user.Name);
                await SecureStorage.SetAsync(Constants.UserEmailKey, user.Email);

                if (!string.IsNullOrEmpty(user.ReferralCode))
                {
                    await SecureStorage.SetAsync(Constants.ReferralCodeKey, user.ReferralCode);
                }

                var json = JsonSerializer.Serialize(user);
                await SecureStorage.SetAsync(Constants.UserDataKey, json);
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error storing user data: {ex.Message}");
            }
        }

        /// <summary>
        /// Clear all authentication data
        /// </summary>
        private async Task ClearAuthDataAsync()
        {
            _authToken = null;
            _httpClient.DefaultRequestHeaders.Authorization = null;

            SecureStorage.Remove(Constants.AuthTokenKey);
            SecureStorage.Remove(Constants.UserDataKey);
            SecureStorage.Remove(Constants.UserIdKey);
            SecureStorage.Remove(Constants.UserNameKey);
            SecureStorage.Remove(Constants.UserEmailKey);
            SecureStorage.Remove(Constants.ReferralCodeKey);

            await Task.CompletedTask;
        }

        /// <summary>
        /// Ensure user is authenticated
        /// </summary>
        private async Task EnsureAuthenticatedAsync()
        {
            if (string.IsNullOrEmpty(_authToken))
            {
                await LoadAuthTokenAsync();
            }

            if (string.IsNullOrEmpty(_authToken))
            {
                throw new UnauthorizedAccessException("User is not authenticated");
            }
        }

        #endregion
    }
}
