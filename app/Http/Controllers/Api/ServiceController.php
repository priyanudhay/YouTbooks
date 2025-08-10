<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::active()->with(['activeVariants']);
        
        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }
        
        // Filter by featured
        if ($request->boolean('featured')) {
            $query->featured();
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort
        $sortBy = $request->get('sort', 'sort_order');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        $services = $query->paginate($request->get('per_page', 12));
        
        return response()->json([
            'success' => true,
            'data' => $services->items(),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ]
        ]);
    }
    
    /**
     * Display the specified service.
     */
    public function show(Service $service): JsonResponse
    {
        $service->load(['activeVariants' => function ($query) {
            $query->orderBy('sort_order');
        }]);
        
        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }
    
    /**
     * Get service variants.
     */
    public function variants(Service $service): JsonResponse
    {
        $variants = $service->activeVariants()
            ->orderBy('sort_order')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $variants
        ]);
    }
    
    /**
     * Calculate price for a service configuration.
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'service_variant_id' => 'required|exists:service_variants,id',
            'quantity' => 'required|integer|min:1',
            'add_ons' => 'array',
            'add_ons.*' => 'string',
            'turnaround_tier' => 'string|in:standard,rush,express',
        ]);
        
        $variant = ServiceVariant::active()->findOrFail($request->service_variant_id);
        
        if (!$variant->isValidQuantity($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid quantity for this service variant.',
                'errors' => [
                    'quantity' => ['Quantity must be between ' . $variant->min_quantity . ' and ' . ($variant->max_quantity ?? 'unlimited')]
                ]
            ], 422);
        }
        
        // Base price calculation
        $basePrice = $variant->calculatePrice($request->quantity);
        
        // Turnaround tier multiplier
        $turnaroundMultiplier = match($request->get('turnaround_tier', 'standard')) {
            'rush' => 1.5,      // 50% extra for rush delivery
            'express' => 2.0,   // 100% extra for express delivery
            default => 1.0,     // Standard pricing
        };
        
        // Add-ons pricing (you can customize this logic)
        $addOnsPrice = 0;
        $addOns = $request->get('add_ons', []);
        foreach ($addOns as $addOn) {
            $addOnsPrice += match($addOn) {
                'priority_support' => 25.00,
                'additional_revision' => 15.00,
                'expedited_review' => 35.00,
                'style_guide_creation' => 50.00,
                default => 0,
            };
        }
        
        $subtotal = ($basePrice * $turnaroundMultiplier) + $addOnsPrice;
        
        // Calculate estimated delivery date
        $baseTurnaround = $variant->turnaround_days;
        $adjustedTurnaround = match($request->get('turnaround_tier', 'standard')) {
            'rush' => max(1, ceil($baseTurnaround * 0.5)),
            'express' => max(1, ceil($baseTurnaround * 0.25)),
            default => $baseTurnaround,
        };
        
        $estimatedDelivery = now()->addDays($adjustedTurnaround);
        
        return response()->json([
            'success' => true,
            'data' => [
                'service_variant_id' => $variant->id,
                'service_title' => $variant->service->title,
                'variant_title' => $variant->title,
                'quantity' => $request->quantity,
                'base_price' => $basePrice,
                'turnaround_multiplier' => $turnaroundMultiplier,
                'add_ons_price' => $addOnsPrice,
                'subtotal' => $subtotal,
                'formatted_subtotal' => '$' . number_format($subtotal, 2),
                'estimated_delivery' => $estimatedDelivery->format('Y-m-d'),
                'estimated_delivery_formatted' => $estimatedDelivery->format('M j, Y'),
                'turnaround_days' => $adjustedTurnaround,
                'breakdown' => [
                    'base' => [
                        'label' => $variant->full_title,
                        'quantity' => $request->quantity,
                        'unit_price' => $variant->price,
                        'total' => $basePrice,
                    ],
                    'turnaround' => [
                        'tier' => $request->get('turnaround_tier', 'standard'),
                        'multiplier' => $turnaroundMultiplier,
                        'additional_cost' => ($basePrice * $turnaroundMultiplier) - $basePrice,
                    ],
                    'add_ons' => array_map(function ($addOn) {
                        $price = match($addOn) {
                            'priority_support' => 25.00,
                            'additional_revision' => 15.00,
                            'expedited_review' => 35.00,
                            'style_guide_creation' => 50.00,
                            default => 0,
                        };
                        return [
                            'name' => ucwords(str_replace('_', ' ', $addOn)),
                            'price' => $price,
                        ];
                    }, $addOns),
                ]
            ]
        ]);
    }
}
