<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get cart contents.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $cartSummary = $this->cartService->getCartSummary($user);

        return response()->json([
            'success' => true,
            'data' => $cartSummary
        ]);
    }

    /**
     * Add item to cart.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'service_variant_id' => 'required|exists:service_variants,id',
            'quantity' => 'required|integer|min:1',
            'meta' => 'array',
        ]);

        $user = $request->user();
        $result = $this->cartService->addItem(
            $user,
            $request->service_variant_id,
            $request->quantity,
            $request->get('meta', [])
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result, 201);
    }

    /**
     * Update cart item.
     */
    public function update(Request $request, CartItem $item): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $cart = $this->cartService->getCart($user);

        // Verify the item belongs to the current cart
        if ($item->cart_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.'
            ], 404);
        }

        $result = $this->cartService->updateItem(
            $user,
            $item->service_variant_id,
            $request->quantity
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, CartItem $item): JsonResponse
    {
        $user = $request->user();
        $cart = $this->cartService->getCart($user);

        // Verify the item belongs to the current cart
        if ($item->cart_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.'
            ], 404);
        }

        $result = $this->cartService->removeItem($user, $item->service_variant_id);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Clear entire cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->cartService->clearCart($user);

        return response()->json($result);
    }

    /**
     * Calculate cart totals with coupon.
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $couponCode = $request->get('coupon_code');

        $totals = $this->cartService->calculateCartTotals($user, $couponCode);
        $cartSummary = $this->cartService->getCartSummary($user);

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cartSummary,
                'totals' => $totals,
            ]
        ]);
    }
}
