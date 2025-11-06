<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreeController extends Controller
{
    /**
     * Get tree data for current user
     */
    public function getUserTree(Request $request)
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่มีสิทธิ์ในระบบแอฟฟิลิเอท'
            ], 404);
        }

        // Get max depth from commission settings
        $maxDepth = (int) Setting::get('commission_depth', 10);

        // Allow override from request (for admin)
        if ($request->has('max_depth') && $user->isSuperAdmin()) {
            $maxDepth = (int) $request->get('max_depth', 10);
        }

        // Build tree data
        $treeData = $this->buildTreeNode($affiliate, 0, $maxDepth, false);

        return response()->json([
            'success' => true,
            'data' => $treeData,
            'max_depth' => $maxDepth,
            'stats' => [
                'direct_referrals' => $affiliate->children->count(),
                'total_network' => $this->countTotalNetwork($affiliate),
                'total_earnings' => $this->sumNetworkEarnings($affiliate),
                'max_level' => $this->getMaxLevel($affiliate),
            ]
        ]);
    }

    /**
     * Get tree data for admin (any affiliate)
     */
    public function getAdminTree(Request $request, $affiliateId = null)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_affiliates')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
            ], 403);
        }

        // Get all root affiliates if no specific affiliate is requested
        if (!$affiliateId) {
            $rootAffiliates = Affiliate::whereNull('parent_id')
                ->with(['user', 'rank'])
                ->get();

            $trees = [];
            foreach ($rootAffiliates as $affiliate) {
                $maxDepth = (int) $request->get('max_depth', 100);
                $trees[] = $this->buildTreeNode($affiliate, 0, $maxDepth, true);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'name' => 'All Networks',
                    'children' => $trees
                ],
                'is_admin' => true
            ]);
        }

        // Get specific affiliate tree
        $affiliate = Affiliate::with(['user', 'rank'])->find($affiliateId);

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลแอฟฟิลิเอท'
            ], 404);
        }

        $maxDepth = (int) $request->get('max_depth', 100);
        $treeData = $this->buildTreeNode($affiliate, 0, $maxDepth, true);

        return response()->json([
            'success' => true,
            'data' => $treeData,
            'is_admin' => true,
            'stats' => [
                'direct_referrals' => $affiliate->children->count(),
                'total_network' => $this->countTotalNetwork($affiliate),
                'total_earnings' => $this->sumNetworkEarnings($affiliate),
                'max_level' => $this->getMaxLevel($affiliate),
            ]
        ]);
    }

    /**
     * Build tree node recursively
     */
    protected function buildTreeNode($affiliate, $currentDepth = 0, $maxDepth = 10, $isAdmin = false)
    {
        // Load necessary relationships
        $affiliate->load(['user', 'rank', 'children' => function ($query) {
            $query->with(['user', 'rank'])->orderBy('created_at', 'desc');
        }]);

        $node = [
            'id' => $affiliate->id,
            'name' => $affiliate->user->name,
            'email' => $affiliate->user->email,
            'avatar' => $this->getAvatar($affiliate->user),
            'referral_code' => $affiliate->referral_code,
            'level' => $affiliate->level,
            'depth' => $currentDepth,
            'status' => $affiliate->status,
            'total_earnings' => $affiliate->total_earnings,
            'total_referrals' => $affiliate->total_referrals,
            'direct_children' => $affiliate->children->count(),
            'created_at' => $affiliate->created_at->format('d/m/Y'),
            'rank' => $affiliate->rank ? [
                'name' => $affiliate->rank->display_name,
                'color' => $affiliate->rank->color,
                'level' => $affiliate->rank->level,
            ] : null,
        ];

        // Add admin-specific data
        if ($isAdmin) {
            $node['rank_points'] = $affiliate->rank_points;
            $node['monthly_sales'] = $affiliate->monthly_sales;
            $node['team_sales'] = $affiliate->team_sales;
        }

        // Always initialize children array
        $node['children'] = [];

        // Recursively add children if not at max depth
        if ($currentDepth < $maxDepth && $affiliate->children->count() > 0) {
            foreach ($affiliate->children as $child) {
                $node['children'][] = $this->buildTreeNode($child, $currentDepth + 1, $maxDepth, $isAdmin);
            }
        }

        // Store collapsed children count if there are hidden children
        if ($affiliate->children->count() > 0 && $currentDepth >= $maxDepth) {
            $node['_children'] = $affiliate->children->count();
        }

        return $node;
    }

    /**
     * Get user avatar (first letter or profile picture)
     */
    protected function getAvatar($user)
    {
        if ($user->profile_picture) {
            return [
                'type' => 'image',
                'url' => asset($user->profile_picture)
            ];
        }

        return [
            'type' => 'text',
            'text' => strtoupper(substr($user->name, 0, 1))
        ];
    }

    /**
     * Count total network size (all downlines)
     */
    protected function countTotalNetwork($affiliate)
    {
        $count = $affiliate->children->count();

        foreach ($affiliate->children as $child) {
            $count += $this->countTotalNetwork($child);
        }

        return $count;
    }

    /**
     * Sum total network earnings (all downlines)
     */
    protected function sumNetworkEarnings($affiliate)
    {
        $sum = $affiliate->total_earnings;

        foreach ($affiliate->children as $child) {
            $sum += $this->sumNetworkEarnings($child);
        }

        return $sum;
    }

    /**
     * Get maximum level depth in referral tree
     */
    protected function getMaxLevel($affiliate, $level = 1)
    {
        $children = $affiliate->children;

        if ($children->isEmpty()) {
            return $level;
        }

        $maxChildLevel = $level;
        foreach ($children as $child) {
            $childLevel = $this->getMaxLevel($child, $level + 1);
            $maxChildLevel = max($maxChildLevel, $childLevel);
        }

        return $maxChildLevel;
    }

    /**
     * ========================================
     * Binary Tree Methods
     * ========================================
     */

    /**
     * Get binary tree data for current user
     */
    public function getUserBinaryTree(Request $request)
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่มีสิทธิ์ในระบบแอฟฟิลิเอท'
            ], 404);
        }

        // Get max depth from request or use default
        $maxDepth = (int) $request->get('max_depth', 10);

        // Build binary tree data
        $treeData = $this->buildBinaryTreeNode($affiliate, 0, $maxDepth, false);

        return response()->json([
            'success' => true,
            'data' => $treeData,
            'max_depth' => $maxDepth,
            'stats' => [
                'binary_left_count' => $affiliate->binary_left_count,
                'binary_right_count' => $affiliate->binary_right_count,
                'total_binary_network' => $this->countTotalBinaryNetwork($affiliate),
                'max_binary_level' => $this->getMaxBinaryLevel($affiliate),
            ]
        ]);
    }

    /**
     * Get binary tree data for admin
     */
    public function getAdminBinaryTree(Request $request, $affiliateId = null)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_affiliates')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
            ], 403);
        }

        // Get all root binary affiliates if no specific affiliate is requested
        if (!$affiliateId) {
            $rootAffiliates = Affiliate::whereNull('binary_parent_id')
                ->with(['user', 'rank'])
                ->get();

            $trees = [];
            foreach ($rootAffiliates as $affiliate) {
                $maxDepth = (int) $request->get('max_depth', 100);
                $trees[] = $this->buildBinaryTreeNode($affiliate, 0, $maxDepth, true);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'name' => 'All Binary Networks',
                    'children' => $trees
                ],
                'is_admin' => true
            ]);
        }

        // Get specific affiliate binary tree
        $affiliate = Affiliate::with(['user', 'rank'])->find($affiliateId);

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลแอฟฟิลิเอท'
            ], 404);
        }

        $maxDepth = (int) $request->get('max_depth', 100);
        $treeData = $this->buildBinaryTreeNode($affiliate, 0, $maxDepth, true);

        return response()->json([
            'success' => true,
            'data' => $treeData,
            'is_admin' => true,
            'stats' => [
                'binary_left_count' => $affiliate->binary_left_count,
                'binary_right_count' => $affiliate->binary_right_count,
                'total_binary_network' => $this->countTotalBinaryNetwork($affiliate),
                'max_binary_level' => $this->getMaxBinaryLevel($affiliate),
            ]
        ]);
    }

    /**
     * Build binary tree node recursively
     */
    protected function buildBinaryTreeNode($affiliate, $currentDepth = 0, $maxDepth = 10, $isAdmin = false)
    {
        // Load necessary relationships
        $affiliate->load(['user', 'rank', 'binaryLeftChild' => function ($query) {
            $query->with(['user', 'rank']);
        }, 'binaryRightChild' => function ($query) {
            $query->with(['user', 'rank']);
        }]);

        $node = [
            'id' => $affiliate->id,
            'name' => $affiliate->user->name,
            'email' => $affiliate->user->email,
            'avatar' => $this->getAvatar($affiliate->user),
            'referral_code' => $affiliate->referral_code,
            'binary_level' => $affiliate->binary_level,
            'binary_position' => $affiliate->binary_position,
            'depth' => $currentDepth,
            'status' => $affiliate->status,
            'total_earnings' => $affiliate->total_earnings,
            'binary_left_count' => $affiliate->binary_left_count,
            'binary_right_count' => $affiliate->binary_right_count,
            'created_at' => $affiliate->created_at->format('d/m/Y'),
            'rank' => $affiliate->rank ? [
                'name' => $affiliate->rank->display_name,
                'color' => $affiliate->rank->color,
                'level' => $affiliate->rank->level,
            ] : null,
        ];

        // Add admin-specific data
        if ($isAdmin) {
            $node['rank_points'] = $affiliate->rank_points;
            $node['monthly_sales'] = $affiliate->monthly_sales;
            $node['team_sales'] = $affiliate->team_sales;
        }

        // Initialize children array with placeholders for left and right
        $node['children'] = [];

        // Add left child
        if ($currentDepth < $maxDepth) {
            $leftChild = $affiliate->binaryLeftChild;
            if ($leftChild) {
                $node['children'][] = $this->buildBinaryTreeNode($leftChild, $currentDepth + 1, $maxDepth, $isAdmin);
            } else {
                // Empty left slot
                $node['children'][] = [
                    'id' => null,
                    'position' => 'left',
                    'empty' => true,
                    'name' => 'ว่าง (ซ้าย)',
                    'depth' => $currentDepth + 1,
                ];
            }

            // Add right child
            $rightChild = $affiliate->binaryRightChild;
            if ($rightChild) {
                $node['children'][] = $this->buildBinaryTreeNode($rightChild, $currentDepth + 1, $maxDepth, $isAdmin);
            } else {
                // Empty right slot
                $node['children'][] = [
                    'id' => null,
                    'position' => 'right',
                    'empty' => true,
                    'name' => 'ว่าง (ขวา)',
                    'depth' => $currentDepth + 1,
                ];
            }
        }

        return $node;
    }

    /**
     * Count total binary network size
     */
    protected function countTotalBinaryNetwork($affiliate)
    {
        return $affiliate->binary_left_count + $affiliate->binary_right_count;
    }

    /**
     * Get maximum binary level depth
     */
    protected function getMaxBinaryLevel($affiliate, $level = 1)
    {
        $leftChild = $affiliate->binaryLeftChild;
        $rightChild = $affiliate->binaryRightChild;

        if (!$leftChild && !$rightChild) {
            return $level;
        }

        $maxLevel = $level;

        if ($leftChild) {
            $leftLevel = $this->getMaxBinaryLevel($leftChild, $level + 1);
            $maxLevel = max($maxLevel, $leftLevel);
        }

        if ($rightChild) {
            $rightLevel = $this->getMaxBinaryLevel($rightChild, $level + 1);
            $maxLevel = max($maxLevel, $rightLevel);
        }

        return $maxLevel;
    }
}
