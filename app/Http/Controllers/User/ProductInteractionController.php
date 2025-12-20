<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Models\ProductLike;
use App\Models\ProductView;
use App\Models\StoreFollower;
use App\Models\VendorStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ProductInteractionController
 *
 * จัดการการโต้ตอบของผู้ใช้กับสินค้าและร้านค้า
 * - ถูกใจ (Like)
 * - รายการโปรด (Favorite/Wishlist)
 * - ติดตามร้าน (Follow Store)
 */
class ProductInteractionController extends Controller
{
    /**
     * แสดงหน้ารายการโปรด (Wishlist)
     *
     * @param Request $request
     * @return View
     */
    public function wishlist(Request $request): View
    {
        $user = $request->user();
        $collection = $request->get('collection');

        $query = ProductFavorite::where('user_id', $user->id)
            ->with(['product.images', 'product.seller']);

        if ($collection) {
            $query->where('collection_name', $collection);
        }

        $favorites = $query->latest()->paginate(20);
        $collections = ProductFavorite::getCollections($user->id);

        return view('user.wishlist.index', [
            'favorites' => $favorites,
            'collections' => $collections,
            'currentCollection' => $collection,
            'pageTitle' => 'รายการโปรด',
        ]);
    }

    /**
     * แสดงสินค้าที่ดูล่าสุด
     *
     * @param Request $request
     * @return View
     */
    public function recentlyViewed(Request $request): View
    {
        $user = $request->user();
        $products = ProductView::getRecentlyViewed($user->id, 50);

        return view('user.products.recently-viewed', [
            'products' => $products,
            'pageTitle' => 'สินค้าที่ดูล่าสุด',
        ]);
    }

    /**
     * แสดงร้านค้าที่ติดตาม
     *
     * @param Request $request
     * @return View
     */
    public function followingStores(Request $request): View
    {
        $user = $request->user();

        $following = StoreFollower::where('user_id', $user->id)
            ->with('store')
            ->latest()
            ->paginate(20);

        return view('user.stores.following', [
            'following' => $following,
            'pageTitle' => 'ร้านค้าที่ติดตาม',
        ]);
    }

    /**
     * Toggle ถูกใจสินค้า
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function toggleLike(Request $request, Product $product): JsonResponse
    {
        $result = ProductLike::toggle($request->user()->id, $product->id);

        return response()->json([
            'success' => true,
            'liked' => $result['liked'],
            'count' => $result['count'],
            'message' => $result['liked'] ? 'ถูกใจสินค้าแล้ว' : 'ยกเลิกถูกใจแล้ว',
        ]);
    }

    /**
     * Toggle รายการโปรด
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function toggleFavorite(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'collection_name' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
            'notify_price_drop' => 'nullable|boolean',
            'target_price' => 'nullable|numeric|min:0',
        ]);

        $result = ProductFavorite::toggle($request->user()->id, $product->id, $data);

        return response()->json([
            'success' => true,
            'favorited' => $result['favorited'],
            'count' => $result['count'],
            'message' => $result['favorited'] ? 'เพิ่มในรายการโปรดแล้ว' : 'นำออกจากรายการโปรดแล้ว',
        ]);
    }

    /**
     * อัพเดทรายการโปรด
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function updateFavorite(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'collection_name' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
            'notify_price_drop' => 'nullable|boolean',
            'target_price' => 'nullable|numeric|min:0',
        ]);

        $favorite = ProductFavorite::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสินค้าในรายการโปรด',
            ], 404);
        }

        $favorite->update($data);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Toggle ติดตามร้านค้า
     *
     * @param Request $request
     * @param VendorStore $store
     * @return JsonResponse
     */
    public function toggleFollow(Request $request, VendorStore $store): JsonResponse
    {
        $settings = $request->validate([
            'notify_new_products' => 'nullable|boolean',
            'notify_promotions' => 'nullable|boolean',
            'notify_live' => 'nullable|boolean',
        ]);

        $result = StoreFollower::toggle($request->user()->id, $store->id, $settings);

        return response()->json([
            'success' => true,
            'following' => $result['following'],
            'count' => $result['count'],
            'message' => $result['following'] ? 'ติดตามร้านค้าแล้ว' : 'ยกเลิกติดตามแล้ว',
        ]);
    }

    /**
     * อัพเดทการตั้งค่าการติดตามร้านค้า
     *
     * @param Request $request
     * @param VendorStore $store
     * @return JsonResponse
     */
    public function updateFollowSettings(Request $request, VendorStore $store): JsonResponse
    {
        $settings = $request->validate([
            'notify_new_products' => 'required|boolean',
            'notify_promotions' => 'required|boolean',
            'notify_live' => 'required|boolean',
        ]);

        $updated = StoreFollower::updateSettings($request->user()->id, $store->id, $settings);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่ได้ติดตามร้านค้านี้',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทการตั้งค่าเรียบร้อยแล้ว',
        ]);
    }

    /**
     * ดึงสถานะการโต้ตอบของผู้ใช้กับสินค้า
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductStatus(Request $request, Product $product): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'success' => true,
            'liked' => ProductLike::isLiked($userId, $product->id),
            'favorited' => ProductFavorite::isFavorited($userId, $product->id),
            'likes_count' => $product->likes_count,
            'favorites_count' => $product->favorites_count,
        ]);
    }

    /**
     * ดึงสถานะการติดตามร้านค้า
     *
     * @param Request $request
     * @param VendorStore $store
     * @return JsonResponse
     */
    public function getStoreStatus(Request $request, VendorStore $store): JsonResponse
    {
        $userId = $request->user()->id;
        $follower = StoreFollower::where('user_id', $userId)
            ->where('store_id', $store->id)
            ->first();

        return response()->json([
            'success' => true,
            'following' => $follower !== null,
            'settings' => $follower ? [
                'notify_new_products' => $follower->notify_new_products,
                'notify_promotions' => $follower->notify_promotions,
                'notify_live' => $follower->notify_live,
            ] : null,
            'followers_count' => $store->followers_count,
        ]);
    }
}
