<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Crypto\TPIXBlockchainService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * TPIX Blockchain API Controller
 *
 * Provides API endpoints for interacting with the TPIX native blockchain.
 *
 * @package App\Http\Controllers\Api
 */
class TPIXBlockchainController extends Controller
{
    /**
     * @var TPIXBlockchainService
     */
    protected TPIXBlockchainService $tpixService;

    /**
     * Constructor
     */
    public function __construct(TPIXBlockchainService $tpixService)
    {
        $this->tpixService = $tpixService;
    }

    /**
     * Get TPIX network information
     *
     * @return JsonResponse
     */
    public function getNetworkInfo(): JsonResponse
    {
        try {
            $info = $this->tpixService->getNetworkInfo();

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get network info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get balance for a TPIX address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBalance(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/'
        ]);

        try {
            $balance = $this->tpixService->getBalance($request->address);

            return response()->json([
                'success' => true,
                'data' => [
                    'address' => $request->address,
                    'balance' => $balance,
                    'symbol' => 'TPIX',
                    'decimals' => 18
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current block number
     *
     * @return JsonResponse
     */
    public function getBlockNumber(): JsonResponse
    {
        try {
            $blockNumber = $this->tpixService->getBlockNumber();

            return response()->json([
                'success' => true,
                'data' => [
                    'blockNumber' => $blockNumber
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get block number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction by hash
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'txHash' => 'required|string|regex:/^0x[a-fA-F0-9]{64}$/'
        ]);

        try {
            $transaction = $this->tpixService->getTransaction($request->txHash);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transaction: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get transaction receipt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactionReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'txHash' => 'required|string|regex:/^0x[a-fA-F0-9]{64}$/'
        ]);

        try {
            $receipt = $this->tpixService->getTransactionReceipt($request->txHash);

            return response()->json([
                'success' => true,
                'data' => $receipt
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transaction receipt: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Send raw transaction
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendRawTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'signedTx' => 'required|string|regex:/^0x[a-fA-F0-9]+$/'
        ]);

        try {
            $txHash = $this->tpixService->sendRawTransaction($request->signedTx);

            return response()->json([
                'success' => true,
                'data' => [
                    'txHash' => $txHash
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estimate gas for a transaction
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estimateGas(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|string|regex:/^0x[a-fA-F0-9]{40}$/',
            'to' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/',
            'value' => 'nullable|string',
            'data' => 'nullable|string'
        ]);

        try {
            $transaction = array_filter([
                'from' => $request->from,
                'to' => $request->to,
                'value' => $request->value ?? '0x0',
                'data' => $request->data ?? '0x'
            ]);

            $gasEstimate = $this->tpixService->estimateGas($transaction);

            return response()->json([
                'success' => true,
                'data' => [
                    'gasEstimate' => $gasEstimate,
                    'gasEstimateDecimal' => hexdec($gasEstimate)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to estimate gas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current gas price
     *
     * @return JsonResponse
     */
    public function getGasPrice(): JsonResponse
    {
        try {
            $gasPrice = $this->tpixService->getGasPrice();

            return response()->json([
                'success' => true,
                'data' => [
                    'gasPrice' => $gasPrice,
                    'gasPriceDecimal' => hexdec($gasPrice),
                    'gasPriceGwei' => hexdec($gasPrice) / 1000000000
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get gas price: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction count (nonce) for an address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactionCount(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/',
            'block' => 'nullable|string'
        ]);

        try {
            $count = $this->tpixService->getTransactionCount(
                $request->address,
                $request->block ?? 'latest'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'address' => $request->address,
                    'transactionCount' => $count,
                    'nonce' => $count
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transaction count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate TPIX address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateAddress(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string'
        ]);

        $isValid = $this->tpixService->isValidAddress($request->address);

        return response()->json([
            'success' => true,
            'data' => [
                'address' => $request->address,
                'isValid' => $isValid
            ]
        ]);
    }

    /**
     * Convert TPIX to wei
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toWei(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        $wei = $this->tpixService->toWei($request->amount);

        return response()->json([
            'success' => true,
            'data' => [
                'tpix' => $request->amount,
                'wei' => $wei
            ]
        ]);
    }

    /**
     * Convert wei to TPIX
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fromWei(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|string'
        ]);

        $tpix = $this->tpixService->fromWei($request->amount);

        return response()->json([
            'success' => true,
            'data' => [
                'wei' => $request->amount,
                'tpix' => $tpix
            ]
        ]);
    }
}
