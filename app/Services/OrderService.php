<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create order from cart.
     */
    public function createOrderFromCart(Cart $cart, array $orderData): Order
    {
        return DB::transaction(function () use ($cart, $orderData) {
            // Create the order
            $order = Order::create($orderData);

            // Create order items from cart items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_variant_id' => $cartItem->service_variant_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->serviceVariant->price,
                    'subtotal' => $cartItem->quantity * $cartItem->serviceVariant->price,
                    'item_meta' => $cartItem->meta_json,
                ]);
            }

            // Calculate and update order totals
            $order->calculateTotals();

            return $order;
        });
    }

    /**
     * Update order status with logging.
     */
    public function updateOrderStatus(Order $order, string $status, ?string $notes = null, ?int $editorId = null): Order
    {
        $updateData = ['status' => $status];

        if ($notes) {
            $timestamp = now()->format('Y-m-d H:i:s');
            $currentNotes = $order->notes ?? '';
            $updateData['notes'] = $currentNotes . "\n[{$timestamp}] Status updated to {$status}: {$notes}";
        }

        if ($editorId) {
            $updateData['assigned_editor_id'] = $editorId;
        }

        if ($status === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        $order->update($updateData);

        return $order->fresh();
    }

    /**
     * Assign editor to order.
     */
    public function assignEditor(Order $order, int $editorId): Order
    {
        $order->update([
            'assigned_editor_id' => $editorId,
            'notes' => ($order->notes ?? '') . "\n[" . now()->format('Y-m-d H:i:s') . "] Editor assigned (ID: {$editorId})",
        ]);

        return $order->fresh();
    }

    /**
     * Get order statistics for dashboard.
     */
    public function getOrderStatistics(array $filters = []): array
    {
        $query = Order::query();

        // Apply date filters
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Get basic counts
        $totalOrders = $query->count();
        $paidOrders = (clone $query)->paid()->count();
        $pendingOrders = (clone $query)->withStatus('created')->count();
        $completedOrders = (clone $query)->withStatus('completed')->count();

        // Revenue calculations
        $totalRevenue = (clone $query)->paid()->sum('total_amount');
        $averageOrderValue = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;

        // Status breakdown
        $statusBreakdown = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrders,
            'pending_orders' => $pendingOrders,
            'completed_orders' => $completedOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
            'conversion_rate' => $totalOrders > 0 ? ($paidOrders / $totalOrders) * 100 : 0,
            'status_breakdown' => $statusBreakdown,
        ];
    }
}
