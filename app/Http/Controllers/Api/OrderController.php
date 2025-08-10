<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected CartService $cartService;
    protected OrderService $orderService;

    public function __construct(CartService $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    /**
     * Get user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Order::forUser($user->id)
            ->with(['items.serviceVariant.service', 'payment'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        $orders = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Get specific order details.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Ensure user can only access their own orders
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $order->load([
            'items.serviceVariant.service',
            'payment',
            'files',
            'assignedEditor:id,name,email'
        ]);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Create order from cart (checkout).
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'billing_details' => 'required|array',
            'billing_details.name' => 'required|string|max:255',
            'billing_details.email' => 'required|email',
            'billing_details.phone' => 'nullable|string|max:20',
            'billing_details.company' => 'nullable|string|max:255',
            'billing_details.address' => 'nullable|string',
            'billing_details.city' => 'nullable|string|max:100',
            'billing_details.state' => 'nullable|string|max:100',
            'billing_details.country' => 'nullable|string|max:100',
            'billing_details.postal_code' => 'nullable|string|max:20',
            'requirements' => 'nullable|array',
            'coupon_code' => 'nullable|string|max:50',
            'payment_method' => 'required|string|in:stripe,paypal,razorpay',
        ]);

        $user = $request->user();
        $cart = $this->cartService->getCart($user);

        if ($cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate totals with coupon
            $totals = $this->cartService->calculateCartTotals($user, $request->coupon_code);

            // Create order
            $orderData = [
                'user_id' => $user?->id,
                'guest_email' => $user ? null : $request->billing_details['email'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total'],
                'billing_details' => $request->billing_details,
                'requirements_json' => $request->requirements,
            ];

            $order = $this->orderService->createOrderFromCart($cart, $orderData);

            // Clear cart after successful order creation
            $cart->clear();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'payment_required' => true,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Add message/note to order.
     */
    public function addMessage(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Ensure user can only message their own orders
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // Add message to order notes (you might want a separate messages table)
        $currentNotes = $order->notes ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] {$user->name}: {$request->message}";
        
        $order->update([
            'notes' => $currentNotes . "\n" . $newNote
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message added successfully.'
        ]);
    }

    /**
     * Get order files.
     */
    public function files(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Ensure user can only access their own order files
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $files = $order->files()->get()->groupBy('type');

        return response()->json([
            'success' => true,
            'data' => [
                'requirement_files' => $files->get('requirement', []),
                'deliverable_files' => $files->get('deliverable', []),
                'sample_files' => $files->get('sample', []),
            ]
        ]);
    }

    /**
     * Get orders assigned to editor (for editor role).
     */
    public function assignedOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isEditor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $query = Order::where('assigned_editor_id', $user->id)
            ->with(['items.serviceVariant.service', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        $orders = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Update order status (for editors).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Only assigned editor or admin can update status
        if ($order->assigned_editor_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|string|in:in_progress,revisions,completed',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order->update([
            'status' => $request->status,
            'notes' => $request->notes ? ($order->notes . "\n" . now()->format('Y-m-d H:i:s') . " - Status updated to {$request->status}: {$request->notes}") : $order->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'status' => $order->status,
                'status_label' => $order->status_label,
            ]
        ]);
    }
}
