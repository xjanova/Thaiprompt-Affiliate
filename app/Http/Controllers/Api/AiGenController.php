<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiGen\AiGenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiGenController extends Controller
{
    protected AiGenService $aiGenService;

    public function __construct(AiGenService $aiGenService)
    {
        $this->aiGenService = $aiGenService;
    }

    /**
     * Get user dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $data = $this->aiGenService->getUserDashboard($request->user());

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate image or video.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'type' => 'required|in:image,video',
            'prompt' => 'required|string|min:3|max:1000',
            'parameters' => 'nullable|array',
        ]);

        try {
            $result = $this->aiGenService->generate(
                $request->user(),
                $validated['provider'],
                $validated['type'],
                $validated['prompt'],
                $validated['parameters'] ?? []
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check generation status.
     */
    public function checkStatus(Request $request, int $generationId): JsonResponse
    {
        try {
            $result = $this->aiGenService->checkGenerationStatus(
                $generationId,
                $request->user()
            );

            if (!$result['success']) {
                return response()->json($result, 404);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user generations.
     */
    public function myGenerations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|in:image,video',
            'status' => 'nullable|in:pending,processing,completed,failed',
            'provider_id' => 'nullable|integer',
            'is_favorite' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $generations = $this->aiGenService->getUserGenerations(
                $request->user(),
                $validated
            );

            return response()->json([
                'success' => true,
                'data' => $generations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single generation.
     */
    public function getGeneration(Request $request, int $generationId): JsonResponse
    {
        try {
            $generation = \App\Models\AiGenGeneration::where('id', $generationId)
                ->where('user_id', $request->user()->id)
                ->with(['provider', 'usageLog'])
                ->first();

            if (!$generation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $generation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Request $request, int $generationId): JsonResponse
    {
        try {
            $generation = \App\Models\AiGenGeneration::where('id', $generationId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$generation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation not found',
                ], 404);
            }

            $generation->toggleFavorite();

            return response()->json([
                'success' => true,
                'is_favorite' => $generation->is_favorite,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete generation.
     */
    public function deleteGeneration(Request $request, int $generationId): JsonResponse
    {
        try {
            $generation = \App\Models\AiGenGeneration::where('id', $generationId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$generation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation not found',
                ], 404);
            }

            $generation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Generation deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
