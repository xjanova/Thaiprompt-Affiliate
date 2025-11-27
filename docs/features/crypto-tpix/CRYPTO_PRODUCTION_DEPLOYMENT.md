# 🚀 Cryptocurrency Gateway - Production Deployment Guide

## ⚠️ CRITICAL SECURITY WARNINGS

### 1. Private Key Management

**NEVER store private keys in plain text or encrypted with Laravel's default encryption in production!**

**Required for Production:**
- **Hardware Security Module (HSM)** - Recommended for enterprise
  - AWS CloudHSM
  - Azure Key Vault HSM
  - Google Cloud HSM
  - Thales Luna HSM

- **Key Management Service (KMS)** - Minimum requirement
  - AWS KMS
  - Azure Key Vault
  - Google Cloud KMS
  - HashiCorp Vault

**Implementation Steps:**
```php
// Example: AWS KMS Integration
use Aws\Kms\KmsClient;

class SecureKeyManager {
    private $kmsClient;

    public function encryptPrivateKey(string $privateKey): string {
        return $this->kmsClient->encrypt([
            'KeyId' => env('AWS_KMS_KEY_ID'),
            'Plaintext' => $privateKey,
        ])['CiphertextBlob'];
    }

    public function decryptPrivateKey(string $encrypted): string {
        return $this->kmsClient->decrypt([
            'CiphertextBlob' => $encrypted,
        ])['Plaintext'];
    }
}
```

### 2. Transaction Signing Libraries

The current simplified implementation in `BlockchainTransactionService.php` **WILL NOT WORK** with real blockchains.

**Required PHP Libraries:**

```bash
# Install required packages
composer require web3p/ethereum-tx
composer require simplito/elliptic-php
composer require bitwasp/bitcoin
```

**Update BlockchainTransactionService.php:**

```php
use Web3p\EthereumTx\Transaction;
use Elliptic\EC;

class BlockchainTransactionService {

    protected function signTransaction(array $transaction, string $privateKey): string {
        // Remove 0x prefix from private key
        $privateKey = str_replace('0x', '', $privateKey);

        // Create transaction object
        $tx = new Transaction([
            'nonce' => $transaction['nonce'],
            'gasPrice' => $transaction['gasPrice'],
            'gasLimit' => $transaction['gas'],
            'to' => $transaction['to'],
            'value' => $transaction['value'],
            'data' => $transaction['data'] ?? '',
        ]);

        // Sign with private key and chain ID
        $chainId = $this->web3Service->getChainId($network);
        $signedTx = '0x' . $tx->sign($privateKey, $chainId);

        return $signedTx;
    }

    protected function getAddressFromPrivateKey(string $privateKey): string {
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate(str_replace('0x', '', $privateKey));
        $publicKey = $key->getPublic('hex');

        // Hash public key with Keccak-256
        $hash = Keccak::hash(hex2bin(substr($publicKey, 2)), 256);

        // Take last 20 bytes as address
        return '0x' . substr($hash, -40);
    }
}
```

### 3. ECDSA Signature Recovery

**Update Web3Service.php:**

```php
use Elliptic\EC;
use kornrunner\Keccak;

protected function recoverAddressFromSignature(
    string $messageHash,
    string $r,
    string $s,
    int $v
): string {
    $ec = new EC('secp256k1');

    // Prepare recovery parameter
    $recovery = $v - 27;

    // Recover public key
    $signature = [
        'r' => $r,
        's' => $s,
    ];

    $messageHash = str_replace('0x', '', $messageHash);

    $publicKey = $ec->recoverPubKey(
        $messageHash,
        $signature,
        $recovery,
        'hex'
    );

    $publicKeyHex = $publicKey->encode('hex');

    // Remove '04' prefix (uncompressed public key marker)
    $publicKeyHex = substr($publicKeyHex, 2);

    // Hash with Keccak-256 and take last 20 bytes
    $hash = Keccak::hash(hex2bin($publicKeyHex), 256);

    return '0x' . substr($hash, -40);
}
```

---

## 📋 Pre-Production Checklist

### 1. Environment Configuration

**Required .env Variables:**

```env
# Production Mode
APP_ENV=production
APP_DEBUG=false

# Blockchain RPC Endpoints (Use Dedicated Nodes!)
ETHEREUM_RPC_URL=https://your-dedicated-ethereum-node.com
BSC_RPC_URL=https://your-dedicated-bsc-node.com
POLYGON_RPC_URL=https://your-dedicated-polygon-node.com

# Backup RPC URLs (Fallback)
ETHEREUM_RPC_URL_BACKUP=https://backup-ethereum-node.com
BSC_RPC_URL_BACKUP=https://backup-bsc-node.com
POLYGON_RPC_URL_BACKUP=https://backup-polygon-node.com

# Blockchain Explorer API Keys
ETHERSCAN_API_KEY=your_etherscan_api_key
BSCSCAN_API_KEY=your_bscscan_api_key
POLYGONSCAN_API_KEY=your_polygonscan_api_key

# Key Management (Example: AWS KMS)
AWS_KMS_KEY_ID=arn:aws:kms:region:account:key/key-id
AWS_KMS_REGION=us-east-1

# Security Settings
CRYPTO_AUTO_APPROVE_ENABLED=false  # Require manual approval in production
CRYPTO_MAX_AUTO_APPROVE_AMOUNT=10000  # Lower limit for production
CRYPTO_REQUIRE_KYC=true
CRYPTO_REQUIRE_2FA=true
CRYPTO_MAX_DAILY_WITHDRAWAL=500000

# Hot Wallet Settings (Maximum balance to keep in hot wallets)
CRYPTO_HOT_WALLET_MAX_BALANCE_ETH=10
CRYPTO_HOT_WALLET_MAX_BALANCE_BNB=100
CRYPTO_HOT_WALLET_MAX_BALANCE_MATIC=10000

# Cold Wallet Addresses (for excess funds)
CRYPTO_COLD_WALLET_ETH=0x...
CRYPTO_COLD_WALLET_BSC=0x...
CRYPTO_COLD_WALLET_POLYGON=0x...

# Monitoring & Alerts
CRYPTO_ALERT_EMAIL=security@yourcompany.com
CRYPTO_ALERT_SLACK_WEBHOOK=https://hooks.slack.com/...
CRYPTO_ALERT_LARGE_TRANSACTION=50000  # THB

# Rate Limiting (Stricter for production)
CRYPTO_WITHDRAWAL_RATE_LIMIT=3,60  # 3 per hour
CRYPTO_DEPOSIT_SCAN_RATE_LIMIT=60,1  # 60 per minute
```

### 2. Infrastructure Requirements

**Recommended Setup:**

```
┌─────────────────────────────────────────┐
│         Load Balancer (HTTPS)           │
└────────────────┬────────────────────────┘
                 │
     ┌───────────┴───────────┐
     │                       │
┌────▼────┐            ┌─────▼────┐
│ Web     │            │ Web      │
│ Server  │            │ Server   │
│ (PHP)   │            │ (PHP)    │
└────┬────┘            └─────┬────┘
     │                       │
     └───────────┬───────────┘
                 │
     ┌───────────▼───────────┐
     │                       │
┌────▼────┐            ┌─────▼────────┐
│ Queue   │            │   Database   │
│ Workers │            │ (PostgreSQL) │
│ (Redis) │            │   (Primary)  │
└────┬────┘            └──────┬───────┘
     │                        │
     │                  ┌─────▼─────┐
     │                  │ Database  │
     │                  │ (Replica) │
     │                  └───────────┘
     │
┌────▼──────────────────────────┐
│  Blockchain Node Services     │
│  - Ethereum Node              │
│  - BSC Node                   │
│  - Polygon Node               │
│  OR                           │
│  - Infura/Alchemy/QuickNode  │
└───────────────────────────────┘
```

**Minimum Server Specifications:**
- **Web Servers:** 4 vCPU, 8GB RAM, 100GB SSD (x2 for redundancy)
- **Queue Workers:** 2 vCPU, 4GB RAM, 50GB SSD (x2 for redundancy)
- **Database:** 8 vCPU, 16GB RAM, 500GB SSD (Primary + Replica)
- **Redis:** 2 vCPU, 4GB RAM, 50GB SSD

### 3. Database Optimization

**Required Indexes:**

```sql
-- Critical indexes for performance
CREATE INDEX idx_crypto_transactions_tx_hash ON crypto_transactions(tx_hash);
CREATE INDEX idx_crypto_transactions_status ON crypto_transactions(status);
CREATE INDEX idx_crypto_transactions_user_id ON crypto_transactions(user_id);
CREATE INDEX idx_crypto_transactions_created_at ON crypto_transactions(created_at);
CREATE INDEX idx_crypto_transactions_type_status ON crypto_transactions(type, status);

CREATE INDEX idx_crypto_withdrawals_status ON crypto_withdrawal_requests(status);
CREATE INDEX idx_crypto_withdrawals_user_id ON crypto_withdrawal_requests(user_id);
CREATE INDEX idx_crypto_withdrawals_created_at ON crypto_withdrawal_requests(created_at);

CREATE INDEX idx_crypto_addresses_address ON crypto_addresses(address);
CREATE INDEX idx_crypto_addresses_wallet_id ON crypto_addresses(crypto_wallet_id);

CREATE INDEX idx_crypto_balances_wallet_currency ON crypto_balances(crypto_wallet_id, crypto_currency_id);

-- Partial indexes for performance
CREATE INDEX idx_pending_deposits ON crypto_transactions(created_at)
WHERE status = 'pending' AND type = 'deposit';

CREATE INDEX idx_pending_withdrawals ON crypto_withdrawal_requests(created_at)
WHERE status IN ('pending', 'reviewing');
```

**Database Partitioning (for high volume):**

```sql
-- Partition crypto_transactions by month
CREATE TABLE crypto_transactions (
    id BIGSERIAL,
    created_at TIMESTAMP NOT NULL,
    -- ... other columns
) PARTITION BY RANGE (created_at);

-- Create partitions for each month
CREATE TABLE crypto_transactions_2025_01
    PARTITION OF crypto_transactions
    FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');

CREATE TABLE crypto_transactions_2025_02
    PARTITION OF crypto_transactions
    FOR VALUES FROM ('2025-02-01') TO ('2025-03-01');

-- etc...
```

### 4. Queue Configuration

**config/queue.php:**

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 300,
        'block_for' => null,
        'after_commit' => false,
    ],

    // Separate queue for crypto operations
    'crypto' => [
        'driver' => 'redis',
        'connection' => 'crypto',
        'queue' => 'crypto',
        'retry_after' => 600,
        'block_for' => null,
    ],
],
```

**Supervisor Configuration** (`/etc/supervisor/conf.d/crypto-workers.conf`):

```ini
[program:crypto-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work crypto --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/crypto-worker.log
stopwaitsecs=3600

[program:crypto-deposit-scanner]
command=php /path/to/project/artisan crypto:scan-deposits --continuous
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/deposit-scanner.log

[program:crypto-withdrawal-processor]
command=php /path/to/project/artisan crypto:process-withdrawals --continuous
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/withdrawal-processor.log
```

### 5. Monitoring & Alerting

**Required Monitoring:**

1. **Transaction Monitoring**
   ```php
   // Alert on stuck transactions (pending > 1 hour)
   $stuckTransactions = CryptoTransaction::where('status', 'pending')
       ->where('created_at', '<', now()->subHour())
       ->count();

   if ($stuckTransactions > 0) {
       // Send alert
   }
   ```

2. **Balance Monitoring**
   ```php
   // Alert when hot wallet balance exceeds threshold
   $ethBalance = // Get ETH balance from blockchain
   if ($ethBalance > env('CRYPTO_HOT_WALLET_MAX_BALANCE_ETH', 10)) {
       // Trigger cold wallet transfer
       // Send alert
   }
   ```

3. **Deposit Detection Health**
   ```php
   // Alert if deposit scanning hasn't run in 5 minutes
   $lastScan = Cache::get('crypto:last_deposit_scan');
   if ($lastScan < now()->subMinutes(5)) {
       // Send alert
   }
   ```

**Recommended Monitoring Tools:**
- **Uptime Monitoring:** Pingdom, UptimeRobot
- **Error Tracking:** Sentry, Rollbar
- **Log Management:** ELK Stack, Datadog, Splunk
- **Performance:** New Relic, Datadog APM
- **Blockchain Monitoring:** Tenderly, BlockNative

### 6. Security Measures

**Rate Limiting:**

```php
// routes/api.php
Route::middleware(['throttle:crypto'])->group(function () {
    Route::post('/crypto/withdraw', [CryptoWalletController::class, 'withdraw']);
});

// app/Providers/RouteServiceProvider.php
RateLimiter::for('crypto', function (Request $request) {
    return Limit::perHour(3)->by($request->user()?->id);
});
```

**IP Whitelisting for Admin Panel:**

```php
// app/Http/Middleware/CryptoAdminAccess.php
public function handle(Request $request, Closure $next) {
    $allowedIps = explode(',', env('CRYPTO_ADMIN_ALLOWED_IPS', ''));

    if (!in_array($request->ip(), $allowedIps)) {
        abort(403, 'Access denied');
    }

    return $next($request);
}
```

**2FA Requirement:**

```php
// Force 2FA for crypto operations
if ($user->cryptoWallet && !$user->hasTwoFactorEnabled()) {
    throw new \Exception('2FA required for crypto operations');
}
```

### 7. Backup Strategy

**Database Backups:**
```bash
# Daily automated backups
0 2 * * * pg_dump -U postgres crypto_production | gzip > /backups/crypto_$(date +\%Y\%m\%d).sql.gz

# Keep 30 days of backups
0 3 * * * find /backups -name "crypto_*.sql.gz" -mtime +30 -delete
```

**Private Key Backups:**
```
NEVER backup private keys to regular backup storage!
Use hardware security modules or split key shares across multiple secure locations.
```

### 8. Disaster Recovery Plan

**Recovery Time Objective (RTO):** 4 hours
**Recovery Point Objective (RPO):** 15 minutes

**Procedures:**
1. Database restoration from latest backup
2. Verify blockchain sync status
3. Reconcile on-chain vs database balances
4. Resume deposit detection
5. Manual review of pending withdrawals

---

## 🧪 Pre-Production Testing

### 1. Testnet Testing

**Deploy to testnets first:**
- Ethereum Goerli/Sepolia
- BSC Testnet
- Polygon Mumbai

```env
# Testnet Configuration
ETHEREUM_RPC_URL=https://goerli.infura.io/v3/YOUR_KEY
BSC_RPC_URL=https://data-seed-prebsc-1-s1.binance.org:8545
POLYGON_RPC_URL=https://rpc-mumbai.maticvigil.com
```

### 2. Load Testing

```bash
# Install Apache Bench or use artillery.io
npm install -g artillery

# Load test deposit scanning
artillery quick --count 100 --num 10 https://yoursite.com/api/crypto/balances

# Monitor:
# - Response times
# - Database connections
# - Memory usage
# - Queue depth
```

### 3. Security Audit

**Required audits before production:**
1. Smart contract audit (if using custom contracts)
2. Penetration testing
3. Code review by security expert
4. Compliance review (AML/KYC)

---

## 📝 Production Launch Checklist

- [ ] All `.env` variables configured
- [ ] KMS/HSM integrated for private keys
- [ ] Proper transaction signing libraries installed
- [ ] ECDSA recovery implemented
- [ ] Database indexes created
- [ ] Queue workers configured with Supervisor
- [ ] Monitoring and alerting set up
- [ ] Testnet testing completed
- [ ] Load testing completed
- [ ] Security audit completed
- [ ] Backup procedures tested
- [ ] Disaster recovery plan documented
- [ ] Hot wallet limits configured
- [ ] Cold wallet addresses set
- [ ] Rate limiting configured
- [ ] IP whitelisting enabled
- [ ] 2FA enforced
- [ ] Legal compliance verified
- [ ] Insurance obtained (recommended)

---

## 📞 Emergency Contacts

**During production issues:**

1. **Pause All Withdrawals:**
   ```bash
   php artisan tinker
   Setting::set('crypto_withdrawals_enabled', false);
   ```

2. **Pause Deposit Detection:**
   ```bash
   supervisorctl stop crypto-deposit-scanner
   ```

3. **Check System Health:**
   ```bash
   php artisan crypto:health-check
   ```

---

## 📚 Additional Resources

- **Web3.php Documentation:** https://github.com/web3p/web3.php
- **Ethereum TX Library:** https://github.com/web3p/ethereum-tx
- **Elliptic PHP:** https://github.com/simplito/elliptic-php
- **Etherscan API Docs:** https://docs.etherscan.io/
- **AWS KMS:** https://aws.amazon.com/kms/
- **HashiCorp Vault:** https://www.vaultproject.io/

---

**Last Updated:** 2025-01-08
**Version:** 1.0.0
**Status:** ⚠️ PRODUCTION DEPLOYMENT REQUIRED ACTIONS LISTED ABOVE
