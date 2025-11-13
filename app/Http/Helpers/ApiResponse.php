<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * API Response Helper
 *
 * Standardized JSON responses for Food Passport API
 */
class ApiResponse
{
    /**
     * Success response.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response.
     */
    public static function error(
        string $message = 'Error',
        int $statusCode = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Created response (201).
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    /**
     * Deleted response (200).
     */
    public static function deleted(
        string $message = 'Resource deleted successfully'
    ): JsonResponse {
        return self::success(null, $message, 200);
    }

    /**
     * Updated response (200).
     */
    public static function updated(
        mixed $data = null,
        string $message = 'Resource updated successfully'
    ): JsonResponse {
        return self::success($data, $message, 200);
    }

    /**
     * Not found response (404).
     */
    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return self::error($message, 404);
    }

    /**
     * Unauthorized response (401).
     */
    public static function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return self::error($message, 401);
    }

    /**
     * Forbidden response (403).
     */
    public static function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return self::error($message, 403);
    }

    /**
     * Validation error response (422).
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error($message, 422, $errors);
    }

    /**
     * Server error response (500).
     */
    public static function serverError(
        string $message = 'Internal server error'
    ): JsonResponse {
        return self::error($message, 500);
    }

    /**
     * Paginated response.
     */
    public static function paginated(
        $paginator,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Resource response (with Resource class).
     */
    public static function resource(
        JsonResource|ResourceCollection $resource,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource,
        ], $statusCode);
    }

    /**
     * Collection response.
     */
    public static function collection(
        array|ResourceCollection $collection,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return self::success($collection, $message, 200, $meta);
    }

    /**
     * TPIX blockchain response (with transaction details).
     */
    public static function blockchain(
        string $txHash,
        mixed $data = null,
        string $message = 'Transaction recorded on TPIX blockchain'
    ): JsonResponse {
        return self::success([
            'blockchain' => [
                'tx_hash' => $txHash,
                'explorer_url' => \App\Helpers\TpixHelper::getTransactionUrl($txHash),
                'network' => \App\Helpers\TpixHelper::getNetworkName(),
                'chain_id' => \App\Helpers\TpixHelper::getChainId(),
            ],
            'data' => $data,
        ], $message);
    }

    /**
     * Carbon credit response with environmental impact.
     */
    public static function carbonCredit(
        $credit,
        string $message = 'Carbon credit operation successful'
    ): JsonResponse {
        $impact = \App\Helpers\TpixHelper::getEnvironmentalImpact($credit->credit_amount);

        return self::success([
            'credit' => $credit,
            'environmental_impact' => $impact,
            'tpix_value' => \App\Helpers\TpixHelper::tpixToTHB($credit->current_value),
        ], $message);
    }

    /**
     * NFT minted response.
     */
    public static function nftMinted(
        int $tokenId,
        string $metadataUrl,
        string $explorerUrl,
        string $message = 'NFT minted successfully on TPIX Chain'
    ): JsonResponse {
        return self::success([
            'nft' => [
                'token_id' => $tokenId,
                'metadata_url' => $metadataUrl,
                'explorer_url' => $explorerUrl,
                'network' => 'TPIX Chain',
            ],
        ], $message, 201);
    }

    /**
     * Gas fee information response.
     */
    public static function gasFee(
        int $gasLimit,
        float $gasFeeTPIX,
        float $gasFeeTHB,
        bool $subsidized = false
    ): array {
        return [
            'gas' => [
                'limit' => $gasLimit,
                'fee_tpix' => \App\Helpers\TpixHelper::formatTPIX($gasFeeTPIX),
                'fee_thb' => '฿' . number_format($gasFeeTHB, 2),
                'subsidized' => $subsidized,
                'network' => 'TPIX Chain',
            ],
        ];
    }
}
