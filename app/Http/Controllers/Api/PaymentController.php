<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create Stripe payment intent.
     */
    public function createStripeIntent(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Verify order belongs to authenticated user or is a guest order
        $user = $request->user();
        if ($user && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if (!$user && !$order->guest_email) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order.'
            ], 422);
        }

        // Check if order is already paid
        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order is already paid.'
            ], 422);
        }

        $result = $this->paymentService->createStripePaymentIntent($order);

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'client_secret' => $result['client_secret'],
                'payment_id' => $result['payment_id'],
                'publishable_key' => config('services.stripe.key'),
            ]
        ]);
    }

    /**
     * Create PayPal order.
     */
    public function createPayPalOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Verify order ownership
        $user = $request->user();
        if ($user && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order is already paid.'
            ], 422);
        }

        $result = $this->paymentService->createPayPalOrder($order);

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'paypal_order_id' => $result['paypal_order_id'],
                'approval_url' => $result['approval_url'],
                'payment_id' => $result['payment_id'],
            ]
        ]);
    }

    /**
     * Create Razorpay order.
     */
    public function createRazorpayOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Verify order ownership
        $user = $request->user();
        if ($user && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order is already paid.'
            ], 422);
        }

        $result = $this->paymentService->createRazorpayOrder($order);

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'razorpay_order_id' => $result['razorpay_order_id'],
                'payment_id' => $result['payment_id'],
                'key' => config('services.razorpay.key'),
            ]
        ]);
    }

    /**
     * Handle Stripe webhook.
     */
    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            Log::warning('Stripe webhook: Missing signature');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        $success = $this->paymentService->handleStripeWebhook($payload, $signature);

        if (!$success) {
            return response()->json(['error' => 'Webhook handling failed'], 400);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle PayPal webhook.
     */
    public function paypalWebhook(Request $request): JsonResponse
    {
        // PayPal webhook implementation
        Log::info('PayPal webhook received', $request->all());

        // Verify webhook signature and process event
        // This is a simplified implementation
        $eventType = $request->input('event_type');
        
        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handlePayPalPaymentCompleted($request->input('resource'));
                break;
            case 'PAYMENT.CAPTURE.DENIED':
                $this->handlePayPalPaymentFailed($request->input('resource'));
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Razorpay webhook.
     */
    public function razorpayWebhook(Request $request): JsonResponse
    {
        // Razorpay webhook implementation
        Log::info('Razorpay webhook received', $request->all());

        $event = $request->input('event');
        
        switch ($event) {
            case 'payment.captured':
                $this->handleRazorpayPaymentCaptured($request->input('payload.payment.entity'));
                break;
            case 'payment.failed':
                $this->handleRazorpayPaymentFailed($request->input('payload.payment.entity'));
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Get payment status.
     */
    public function status(Request $request, Payment $payment): JsonResponse
    {
        // Verify payment belongs to user's order
        $user = $request->user();
        if ($user && $payment->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_label' => $payment->status_label,
                'gateway' => $payment->gateway,
                'gateway_display_name' => $payment->gateway_display_name,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->formatted_amount,
                'processed_at' => $payment->processed_at,
                'order' => [
                    'id' => $payment->order->id,
                    'order_number' => $payment->order->order_number,
                    'status' => $payment->order->status,
                    'status_label' => $payment->order->status_label,
                ]
            ]
        ]);
    }

    /**
     * Handle PayPal payment completed.
     */
    private function handlePayPalPaymentCompleted(array $resource): void
    {
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        
        if ($paypalOrderId) {
            $payment = Payment::where('gateway_payment_id', $paypalOrderId)->first();
            
            if ($payment) {
                $payment->markAsCompleted();
            }
        }
    }

    /**
     * Handle PayPal payment failed.
     */
    private function handlePayPalPaymentFailed(array $resource): void
    {
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        
        if ($paypalOrderId) {
            $payment = Payment::where('gateway_payment_id', $paypalOrderId)->first();
            
            if ($payment) {
                $payment->markAsFailed('Payment denied by PayPal');
            }
        }
    }

    /**
     * Handle Razorpay payment captured.
     */
    private function handleRazorpayPaymentCaptured(array $paymentData): void
    {
        $razorpayOrderId = $paymentData['order_id'] ?? null;
        
        if ($razorpayOrderId) {
            $payment = Payment::where('gateway_payment_id', $razorpayOrderId)->first();
            
            if ($payment) {
                $payment->markAsCompleted();
            }
        }
    }

    /**
     * Handle Razorpay payment failed.
     */
    private function handleRazorpayPaymentFailed(array $paymentData): void
    {
        $razorpayOrderId = $paymentData['order_id'] ?? null;
        
        if ($razorpayOrderId) {
            $payment = Payment::where('gateway_payment_id', $razorpayOrderId)->first();
            
            if ($payment) {
                $payment->markAsFailed($paymentData['error_description'] ?? 'Payment failed');
            }
        }
    }
}
