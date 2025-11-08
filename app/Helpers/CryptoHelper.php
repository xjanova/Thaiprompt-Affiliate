<?php

namespace App\Helpers;

use kornrunner\Keccak;

/**
 * Cryptocurrency Helper Functions
 *
 * Provides utility functions for cryptocurrency operations including
 * address validation, format conversion, and security checks.
 */
class CryptoHelper
{
    /**
     * Validate Ethereum-compatible address (Ethereum, BSC, Polygon)
     */
    public static function isValidEthereumAddress(string $address): bool
    {
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return false;
        }

        // Check if it's an all lowercase or all uppercase address
        $address = substr($address, 2);
        if (preg_match('/^[a-f0-9]{40}$/', $address) || preg_match('/^[A-F0-9]{40}$/', $address)) {
            return true;
        }

        // Otherwise, verify checksum (EIP-55)
        return self::verifyEthereumAddressChecksum('0x' . $address);
    }

    /**
     * Verify Ethereum address checksum (EIP-55)
     */
    public static function verifyEthereumAddressChecksum(string $address): bool
    {
        $address = substr($address, 2);
        $hash = Keccak::hash(strtolower($address), 256);

        for ($i = 0; $i < 40; $i++) {
            $char = $address[$i];
            if (ctype_alpha($char)) {
                $isUpperCase = $char === strtoupper($char);
                $hashValue = intval($hash[$i], 16);

                if (($isUpperCase && $hashValue <= 7) || (!$isUpperCase && $hashValue > 7)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Convert Ethereum address to checksum format (EIP-55)
     */
    public static function toChecksumAddress(string $address): string
    {
        $address = strtolower(str_replace('0x', '', $address));
        $hash = Keccak::hash($address, 256);
        $checksum = '0x';

        for ($i = 0; $i < 40; $i++) {
            $checksum .= intval($hash[$i], 16) > 7
                ? strtoupper($address[$i])
                : $address[$i];
        }

        return $checksum;
    }

    /**
     * Validate Bitcoin address format
     */
    public static function isValidBitcoinAddress(string $address): bool
    {
        // P2PKH: starts with 1, P2SH: starts with 3, Bech32: starts with bc1
        // SegWit: starts with bc1 (mainnet) or tb1 (testnet)
        return preg_match('/^(1|3|bc1)[a-zA-Z0-9]{25,62}$/', $address) === 1;
    }

    /**
     * Detect network from address
     */
    public static function detectNetworkFromAddress(string $address): ?string
    {
        if (self::isValidEthereumAddress($address)) {
            // Ethereum-compatible address (could be ETH, BSC, or Polygon)
            // Need additional context to determine exact network
            return 'evm'; // EVM-compatible
        }

        if (self::isValidBitcoinAddress($address)) {
            return 'bitcoin';
        }

        return null;
    }

    /**
     * Convert Wei to Ether with precision
     */
    public static function fromWei(string $wei, int $decimals = 18): string
    {
        // Handle negative values
        $isNegative = str_starts_with($wei, '-');
        $wei = ltrim($wei, '-');

        $divisor = bcpow('10', (string)$decimals, 0);
        $value = bcdiv($wei, $divisor, $decimals);

        return $isNegative ? '-' . $value : $value;
    }

    /**
     * Convert Ether to Wei with precision
     */
    public static function toWei(string $ether, int $decimals = 18): string
    {
        // Handle negative values
        $isNegative = str_starts_with($ether, '-');
        $ether = ltrim($ether, '-');

        $multiplier = bcpow('10', (string)$decimals, 0);
        $value = bcmul($ether, $multiplier, 0);

        return $isNegative ? '-' . $value : $value;
    }

    /**
     * Format cryptocurrency amount for display
     */
    public static function formatAmount(float $amount, string $currency, int $maxDecimals = 8): string
    {
        // Remove unnecessary trailing zeros
        $formatted = rtrim(rtrim(number_format($amount, $maxDecimals, '.', ''), '0'), '.');

        return $formatted . ' ' . strtoupper($currency);
    }

    /**
     * Format cryptocurrency amount in THB
     */
    public static function formatAmountTHB(float $amount): string
    {
        return '฿' . number_format($amount, 2, '.', ',');
    }

    /**
     * Calculate percentage change
     */
    public static function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }

    /**
     * Truncate address for display (show first and last chars)
     */
    public static function truncateAddress(string $address, int $startChars = 6, int $endChars = 4): string
    {
        if (strlen($address) <= ($startChars + $endChars)) {
            return $address;
        }

        return substr($address, 0, $startChars) . '...' . substr($address, -$endChars);
    }

    /**
     * Get block explorer URL for transaction
     */
    public static function getExplorerTxUrl(string $network, string $txHash): string
    {
        $explorers = [
            'ethereum' => 'https://etherscan.io/tx/',
            'bsc' => 'https://bscscan.com/tx/',
            'polygon' => 'https://polygonscan.com/tx/',
            'bitcoin' => 'https://blockchain.info/tx/',
        ];

        $baseUrl = $explorers[$network] ?? $explorers['ethereum'];
        return $baseUrl . $txHash;
    }

    /**
     * Get block explorer URL for address
     */
    public static function getExplorerAddressUrl(string $network, string $address): string
    {
        $explorers = [
            'ethereum' => 'https://etherscan.io/address/',
            'bsc' => 'https://bscscan.com/address/',
            'polygon' => 'https://polygonscan.com/address/',
            'bitcoin' => 'https://blockchain.info/address/',
        ];

        $baseUrl = $explorers[$network] ?? $explorers['ethereum'];
        return $baseUrl . $address;
    }

    /**
     * Check if transaction hash format is valid
     */
    public static function isValidTxHash(string $txHash, string $network = 'ethereum'): bool
    {
        if (in_array($network, ['ethereum', 'bsc', 'polygon'])) {
            // EVM transaction hash: 0x + 64 hex characters
            return preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash) === 1;
        }

        if ($network === 'bitcoin') {
            // Bitcoin transaction hash: 64 hex characters
            return preg_match('/^[a-fA-F0-9]{64}$/', $txHash) === 1;
        }

        return false;
    }

    /**
     * Generate secure random hex string
     */
    public static function generateRandomHex(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Sanitize user input for blockchain operations
     */
    public static function sanitizeAddress(string $address): string
    {
        // Remove whitespace
        $address = trim($address);

        // Ensure lowercase for consistency (except checksum)
        if (str_starts_with($address, '0x')) {
            return $address; // Keep original case for checksum validation
        }

        return strtolower($address);
    }

    /**
     * Check if amount is valid
     */
    public static function isValidAmount(float $amount, float $min = 0.00000001, float $max = PHP_FLOAT_MAX): bool
    {
        return $amount >= $min && $amount <= $max && is_finite($amount);
    }

    /**
     * Calculate transaction fee in native currency
     */
    public static function calculateGasFee(int $gasLimit, string $gasPrice, int $decimals = 18): string
    {
        $fee = bcmul((string)$gasLimit, $gasPrice, 0);
        return self::fromWei($fee, $decimals);
    }

    /**
     * Get network name for display
     */
    public static function getNetworkDisplayName(string $network): string
    {
        return match($network) {
            'ethereum' => 'Ethereum',
            'bsc' => 'Binance Smart Chain',
            'polygon' => 'Polygon',
            'bitcoin' => 'Bitcoin',
            default => ucfirst($network),
        };
    }

    /**
     * Get native currency code for network
     */
    public static function getNativeCurrencyCode(string $network): string
    {
        return match($network) {
            'ethereum' => 'ETH',
            'bsc' => 'BNB',
            'polygon' => 'MATIC',
            'bitcoin' => 'BTC',
            default => 'ETH',
        };
    }

    /**
     * Get recommended gas limit for transaction type
     */
    public static function getRecommendedGasLimit(string $txType): int
    {
        return match($txType) {
            'native_transfer' => 21000,
            'erc20_transfer' => 100000,
            'erc20_approve' => 50000,
            'contract_interaction' => 200000,
            default => 100000,
        };
    }

    /**
     * Check if gas price is reasonable (prevent overpaying)
     */
    public static function isReasonableGasPrice(string $gasPrice, string $network): bool
    {
        $gasPriceGwei = bcdiv($gasPrice, '1000000000', 2);

        // Maximum reasonable gas prices in Gwei
        $maxGasPrices = [
            'ethereum' => 500, // 500 Gwei
            'bsc' => 20,       // 20 Gwei
            'polygon' => 500,  // 500 Gwei
        ];

        $maxAllowed = $maxGasPrices[$network] ?? 500;

        return bccomp($gasPriceGwei, (string)$maxAllowed) <= 0;
    }
}
