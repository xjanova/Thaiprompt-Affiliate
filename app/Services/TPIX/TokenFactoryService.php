<?php

namespace App\Services\TPIX;

use App\Models\TPIXToken;
use App\Models\User;
use App\Services\Crypto\TPIXBlockchainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Token Factory Service
 *
 * Service for creating and deploying ERC20 tokens on TPIX blockchain
 */
class TokenFactoryService
{
    protected TPIXBlockchainService $blockchain;

    public function __construct(TPIXBlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Create a new token (Draft)
     */
    public function createToken(User $creator, array $data): TPIXToken
    {
        DB::beginTransaction();
        try {
            // Generate referral code
            $referralCode = TPIXToken::generateReferralCode();

            // Create token record
            $token = TPIXToken::create([
                'creator_id' => $creator->id,
                'name' => $data['name'],
                'symbol' => strtoupper($data['symbol']),
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,
                'website' => $data['website'] ?? null,
                'whitepaper_url' => $data['whitepaper_url'] ?? null,

                // Token parameters
                'decimals' => $data['decimals'] ?? 18,
                'total_supply' => $data['total_supply'],
                'initial_supply' => $data['initial_supply'] ?? $data['total_supply'],

                // Features
                'is_mintable' => $data['is_mintable'] ?? false,
                'is_burnable' => $data['is_burnable'] ?? true,
                'is_pausable' => $data['is_pausable'] ?? false,
                'is_freezable' => $data['is_freezable'] ?? false,
                'has_max_supply' => $data['has_max_supply'] ?? true,
                'max_supply' => $data['max_supply'] ?? $data['total_supply'],

                // Pricing
                'initial_price_tpix' => $data['initial_price_tpix'] ?? null,
                'current_price_tpix' => $data['initial_price_tpix'] ?? null,

                // Distribution
                'distribution_plan' => $data['distribution_plan'] ?? null,
                'team_allocation_percent' => $data['team_allocation_percent'] ?? null,
                'public_sale_percent' => $data['public_sale_percent'] ?? null,
                'liquidity_percent' => $data['liquidity_percent'] ?? null,
                'marketing_percent' => $data['marketing_percent'] ?? null,

                // Vesting
                'has_vesting' => $data['has_vesting'] ?? false,
                'vesting_schedule' => $data['vesting_schedule'] ?? null,

                // Social links
                'telegram' => $data['telegram'] ?? null,
                'twitter' => $data['twitter'] ?? null,
                'discord' => $data['discord'] ?? null,
                'github' => $data['github'] ?? null,

                // Referral
                'referral_code' => $referralCode,
                'referred_by' => $data['referred_by'] ?? null,

                // Security
                'kyc_required' => $data['kyc_required'] ?? false,

                // Status
                'status' => 'draft',
            ]);

            DB::commit();

            Log::info('Token created', [
                'token_id' => $token->id,
                'symbol' => $token->symbol,
                'creator_id' => $creator->id
            ]);

            return $token;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Token creation failed', [
                'creator_id' => $creator->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Deploy token to TPIX blockchain
     */
    public function deployToken(TPIXToken $token): array
    {
        if ($token->status !== 'draft') {
            throw new Exception('Token is not in draft status');
        }

        DB::beginTransaction();
        try {
            // Update status to deploying
            $token->update(['status' => 'deploying']);

            // Generate Solidity contract code
            $contractCode = $this->generateContractCode($token);

            // In production, this would:
            // 1. Compile the contract
            // 2. Deploy to TPIX blockchain
            // 3. Get contract address and tx hash
            // For now, simulate deployment
            $deploymentResult = $this->simulateDeployment($token, $contractCode);

            // Update token with deployment info
            $token->update([
                'contract_address' => $deploymentResult['contract_address'],
                'deployment_tx_hash' => $deploymentResult['tx_hash'],
                'status' => 'deployed',
            ]);

            // Mint initial supply to creator if specified
            if ($token->initial_supply > 0) {
                $this->mintToCreator($token);
            }

            DB::commit();

            Log::info('Token deployed successfully', [
                'token_id' => $token->id,
                'contract_address' => $token->contract_address
            ]);

            return [
                'success' => true,
                'contract_address' => $token->contract_address,
                'tx_hash' => $token->deployment_tx_hash,
                'explorer_url' => config('crypto.networks.tpix.explorer') . '/address/' . $token->contract_address
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $token->update(['status' => 'failed']);

            Log::error('Token deployment failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate ERC20 contract code
     */
    protected function generateContractCode(TPIXToken $token): string
    {
        $features = [];

        if ($token->is_mintable) {
            $features[] = 'Mintable';
        }
        if ($token->is_burnable) {
            $features[] = 'Burnable';
        }
        if ($token->is_pausable) {
            $features[] = 'Pausable';
        }

        $contractTemplate = <<<SOLIDITY
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "./TPIXERC20.sol";

/**
 * @title {$token->name}
 * @symbol {$token->symbol}
 * @dev Custom ERC20 token on TPIX blockchain
 */
contract {$token->symbol}Token is TPIXERC20 {
    address public owner;
    bool public paused = false;

    modifier onlyOwner() {
        require(msg.sender == owner, "Not owner");
        _;
    }

    modifier whenNotPaused() {
        require(!paused, "Contract is paused");
        _;
    }

    constructor() TPIXERC20("{$token->name}", "{$token->symbol}", {$token->decimals}) {
        owner = msg.sender;
SOLIDITY;

        if ($token->initial_supply > 0) {
            $initialSupplyWei = $this->blockchain->toWei($token->initial_supply);
            $contractTemplate .= "\n        _mint(msg.sender, {$initialSupplyWei});";
        }

        $contractTemplate .= "\n    }\n";

        // Add mint function if mintable
        if ($token->is_mintable) {
            $contractTemplate .= <<<SOLIDITY

    function mint(address to, uint256 amount) public onlyOwner {
        require(totalSupply() + amount <= {$token->max_supply}, "Exceeds max supply");
        _mint(to, amount);
    }
SOLIDITY;
        }

        // Add burn function if burnable
        if ($token->is_burnable) {
            $contractTemplate .= <<<SOLIDITY

    function burn(uint256 amount) public {
        _burn(msg.sender, amount);
    }
SOLIDITY;
        }

        // Add pause functions if pausable
        if ($token->is_pausable) {
            $contractTemplate .= <<<SOLIDITY

    function pause() public onlyOwner {
        paused = true;
    }

    function unpause() public onlyOwner {
        paused = false;
    }

    function _transfer(address from, address to, uint256 amount) internal override whenNotPaused {
        super._transfer(from, to, amount);
    }
SOLIDITY;
        }

        $contractTemplate .= "\n}\n";

        return $contractTemplate;
    }

    /**
     * Simulate deployment (In production, use actual deployment)
     */
    protected function simulateDeployment(TPIXToken $token, string $contractCode): array
    {
        // Generate fake contract address
        $contractAddress = '0x' . bin2hex(random_bytes(20));

        // Generate fake tx hash
        $txHash = '0x' . bin2hex(random_bytes(32));

        // In production, this would:
        // 1. Compile contract with solc
        // 2. Send deployment transaction via RPC
        // 3. Wait for confirmation
        // 4. Return real contract address and tx hash

        return [
            'contract_address' => $contractAddress,
            'tx_hash' => $txHash,
        ];
    }

    /**
     * Mint initial supply to creator
     */
    protected function mintToCreator(TPIXToken $token)
    {
        // Create balance record for creator
        $token->balances()->updateOrCreate(
            [
                'user_id' => $token->creator_id,
                'wallet_address' => $token->creator->cryptoWallets()
                    ->where('currency_id', $this->getTpixCurrencyId())
                    ->first()->address ?? '0x0000000000000000000000000000000000000000'
            ],
            [
                'balance' => $token->initial_supply,
                'available_balance' => $token->initial_supply,
            ]
        );

        // Record mint action
        $token->controlActions()->create([
            'admin_id' => $token->creator_id,
            'action_type' => 'mint',
            'target_address' => $token->creator->cryptoWallets()->first()->address ?? null,
            'amount' => $token->initial_supply,
            'status' => 'success',
            'reason' => 'Initial supply minting',
        ]);

        $token->increment('total_minted', $token->initial_supply);
    }

    /**
     * Get TPIX currency ID
     */
    protected function getTpixCurrencyId()
    {
        return \App\Models\CryptoCurrency::where('code', 'TPIX')->first()->id ?? null;
    }

    /**
     * Verify contract on block explorer
     */
    public function verifyContract(TPIXToken $token, string $sourceCode): bool
    {
        if (!$token->isDeployed()) {
            throw new Exception('Token is not deployed yet');
        }

        try {
            // In production, submit to block explorer for verification
            // For now, just mark as verified

            $token->update([
                'is_verified' => true,
                'status' => 'verified',
            ]);

            Log::info('Token contract verified', [
                'token_id' => $token->id,
                'contract_address' => $token->contract_address
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Contract verification failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get token deployment cost estimate
     */
    public function estimateDeploymentCost(): array
    {
        try {
            // Estimate gas for contract deployment
            $estimatedGas = 2000000; // Average for ERC20
            $gasPrice = hexdec($this->blockchain->getGasPrice());

            $costWei = $estimatedGas * $gasPrice;
            $costTpix = $this->blockchain->fromWei((string)$costWei);

            return [
                'estimated_gas' => $estimatedGas,
                'gas_price_wei' => $gasPrice,
                'gas_price_gwei' => $gasPrice / 1000000000,
                'total_cost_wei' => $costWei,
                'total_cost_tpix' => $costTpix,
            ];

        } catch (Exception $e) {
            Log::error('Failed to estimate deployment cost', [
                'error' => $e->getMessage()
            ]);

            return [
                'estimated_gas' => 2000000,
                'total_cost_tpix' => '0.002', // Fallback estimate
            ];
        }
    }
}
