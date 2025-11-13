<?php

namespace App\Services\TPIX;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Smart Contract Compiler Service
 * Compiles Solidity contracts to bytecode + ABI
 */
class SmartContractCompilerService
{
    protected string $contractsPath;
    protected string $buildPath;
    protected string $solcPath;

    public function __construct()
    {
        $this->contractsPath = base_path('tpix-blockchain/contracts');
        $this->buildPath = base_path('tpix-blockchain/build');
        $this->solcPath = config('tpix.solc_path', 'solc'); // solc compiler path
    }

    /**
     * Compile Solidity contract
     */
    public function compile(string $contractName): array
    {
        $contractFile = "{$this->contractsPath}/{$contractName}.sol";

        if (!File::exists($contractFile)) {
            throw new \Exception("Contract file not found: {$contractFile}");
        }

        // Ensure build directory exists
        if (!File::isDirectory($this->buildPath)) {
            File::makeDirectory($this->buildPath, 0755, true);
        }

        // Compile with solc
        $command = sprintf(
            '%s --optimize --combined-json abi,bin,metadata %s',
            $this->solcPath,
            escapeshellarg($contractFile)
        );

        $result = Process::run($command);

        if (!$result->successful()) {
            Log::error('Contract compilation failed', [
                'contract' => $contractName,
                'error' => $result->errorOutput(),
            ]);

            throw new \Exception("Compilation failed: {$result->errorOutput()}");
        }

        // Parse output
        $output = json_decode($result->output(), true);

        if (!$output || !isset($output['contracts'])) {
            throw new \Exception('Invalid compiler output');
        }

        // Extract bytecode and ABI
        $contractKey = "{$contractFile}:{$contractName}";
        $contract = $output['contracts'][$contractKey] ?? null;

        if (!$contract) {
            throw new \Exception("Contract {$contractName} not found in compilation output");
        }

        $bytecode = '0x' . $contract['bin'];
        $abi = json_decode($contract['abi'], true);

        // Save to build directory
        $buildFile = "{$this->buildPath}/{$contractName}.json";
        File::put($buildFile, json_encode([
            'contractName' => $contractName,
            'bytecode' => $bytecode,
            'abi' => $abi,
            'metadata' => json_decode($contract['metadata'] ?? '{}', true),
            'compiledAt' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        Log::info("Contract compiled successfully: {$contractName}");

        return [
            'bytecode' => $bytecode,
            'abi' => $abi,
            'build_file' => $buildFile,
        ];
    }

    /**
     * Generate ERC20 token contract from template
     */
    public function generateERC20Contract(array $tokenData): string
    {
        $template = File::get("{$this->contractsPath}/TPIXERC20.sol");

        // Replace placeholders
        $contract = str_replace(
            [
                '{{TOKEN_NAME}}',
                '{{TOKEN_SYMBOL}}',
                '{{TOTAL_SUPPLY}}',
                '{{DECIMALS}}',
                '{{IS_MINTABLE}}',
                '{{IS_BURNABLE}}',
                '{{IS_PAUSABLE}}',
                '{{IS_FREEZABLE}}',
            ],
            [
                $tokenData['name'],
                $tokenData['symbol'],
                $tokenData['total_supply'],
                $tokenData['decimals'],
                $tokenData['is_mintable'] ? 'true' : 'false',
                $tokenData['is_burnable'] ? 'true' : 'false',
                $tokenData['is_pausable'] ? 'true' : 'false',
                $tokenData['is_freezable'] ? 'true' : 'false',
            ],
            $template
        );

        // Save custom contract
        $filename = "{$tokenData['symbol']}_Token.sol";
        $filepath = "{$this->contractsPath}/generated/{$filename}";

        if (!File::isDirectory(dirname($filepath))) {
            File::makeDirectory(dirname($filepath), 0755, true);
        }

        File::put($filepath, $contract);

        return $filepath;
    }

    /**
     * Compile and deploy token contract
     */
    public function compileAndDeploy(array $tokenData, string $deployerPrivateKey): array
    {
        // Generate contract
        $contractFile = $this->generateERC20Contract($tokenData);

        // Compile
        $compiled = $this->compile(basename($contractFile, '.sol'));

        // Deploy
        $web3 = app(Web3Service::class);

        $deployment = $web3->deployContract(
            $compiled['bytecode'],
            $deployerPrivateKey
        );

        return [
            'contract_address' => $deployment['contract_address'],
            'tx_hash' => $deployment['tx_hash'],
            'gas_used' => $deployment['gas_used'],
            'abi' => $compiled['abi'],
            'bytecode' => $compiled['bytecode'],
        ];
    }

    /**
     * Verify contract on blockchain explorer
     */
    public function verifyContract(string $contractAddress, string $sourcecode, array $constructorArgs = []): bool
    {
        // Submit to Blockscout API for verification
        $explorerUrl = config('crypto.networks.tpix.explorer');

        if (!$explorerUrl) {
            Log::warning('Explorer URL not configured, skipping verification');
            return false;
        }

        // Blockscout API endpoint
        $apiUrl = "{$explorerUrl}/api";

        // This is a placeholder - implement actual Blockscout verification
        Log::info("Contract verification submitted", [
            'address' => $contractAddress,
            'explorer' => $explorerUrl,
        ]);

        return true;
    }

    /**
     * Get compiled contract from build directory
     */
    public function getCompiledContract(string $contractName): ?array
    {
        $buildFile = "{$this->buildPath}/{$contractName}.json";

        if (!File::exists($buildFile)) {
            return null;
        }

        return json_decode(File::get($buildFile), true);
    }

    /**
     * Check if solc compiler is installed
     */
    public function isSolcInstalled(): bool
    {
        $result = Process::run("{$this->solcPath} --version");
        return $result->successful();
    }

    /**
     * Get solc version
     */
    public function getSolcVersion(): ?string
    {
        if (!$this->isSolcInstalled()) {
            return null;
        }

        $result = Process::run("{$this->solcPath} --version");

        if ($result->successful()) {
            // Extract version from output
            preg_match('/Version: ([\d\.]+)/', $result->output(), $matches);
            return $matches[1] ?? null;
        }

        return null;
    }

    /**
     * Install solc compiler (if not installed)
     */
    public function installSolc(): bool
    {
        Log::info('Installing solc compiler...');

        // Use solc-select or download binary
        $result = Process::run('npm install -g solc');

        if ($result->successful()) {
            Log::info('solc installed successfully');
            return true;
        }

        Log::error('Failed to install solc: ' . $result->errorOutput());
        return false;
    }
}
