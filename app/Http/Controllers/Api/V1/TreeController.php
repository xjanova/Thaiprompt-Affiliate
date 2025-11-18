<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Tree Controller (DEPRECATED)
 *
 * ⚠️ WARNING: This controller is DEPRECATED and will be removed in a future version.
 *
 * This controller was designed for the old Affiliate tree system which has been replaced
 * by the MLM Genealogy system.
 *
 * **Please use the MLM Genealogy endpoints instead:**
 * - GET /api/v1/mlm/genealogy - Get MLM genealogy tree
 * - GET /api/v1/mlm/genealogy/binary - Get binary tree
 * - GET /api/v1/mlm/genealogy/unilevel - Get unilevel tree
 *
 * The Affiliate model and Commission model have been removed. All tree functionality
 * is now available through the MLM system with better features:
 * - Binary tree visualization
 * - Unilevel tree visualization
 * - Real-time PV tracking
 * - Commission history
 * - Team statistics
 *
 * @deprecated Since version 2.204.0
 * @see \App\Http\Controllers\Admin\MlmGenealogyController
 */
class TreeController extends Controller
{
    /**
     * Get tree data for current user (DEPRECATED)
     *
     * @deprecated Use MlmGenealogyController::unilevelTree() instead
     */
    public function getUserTree(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint has been deprecated. Please use MLM Genealogy endpoints instead.',
            'deprecated' => true,
            'migration_guide' => [
                'old_endpoint' => '/api/v1/tree',
                'new_endpoint' => '/api/v1/mlm/genealogy',
                'alternative' => 'Use admin genealogy controller for full tree visualization',
            ],
        ], 410); // 410 Gone
    }

    /**
     * Get tree data for admin (DEPRECATED)
     *
     * @deprecated Use Admin/MlmGenealogyController instead
     */
    public function getAdminTree(Request $request, $affiliateId = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint has been deprecated. Please use admin MLM genealogy instead.',
            'deprecated' => true,
            'migration_guide' => [
                'old_endpoint' => '/api/v1/admin/tree',
                'new_endpoint' => '/admin/mlm/genealogy',
            ],
        ], 410);
    }

    /**
     * Get binary tree data for current user (DEPRECATED)
     *
     * @deprecated Use MlmGenealogyController::binaryTree() instead
     */
    public function getUserBinaryTree(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint has been deprecated. Binary tree is available through MLM genealogy.',
            'deprecated' => true,
            'migration_guide' => [
                'old_endpoint' => '/api/v1/tree/binary',
                'new_endpoint' => '/admin/mlm/genealogy (select binary view)',
            ],
        ], 410);
    }

    /**
     * Get binary tree data for admin (DEPRECATED)
     *
     * @deprecated Use Admin/MlmGenealogyController instead
     */
    public function getAdminBinaryTree(Request $request, $affiliateId = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint has been deprecated. Binary tree is available through admin MLM genealogy.',
            'deprecated' => true,
            'migration_guide' => [
                'old_endpoint' => '/api/v1/admin/tree/binary',
                'new_endpoint' => '/admin/mlm/genealogy (select binary view)',
            ],
        ], 410);
    }
}
