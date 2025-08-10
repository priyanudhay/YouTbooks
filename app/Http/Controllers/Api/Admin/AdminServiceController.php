<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminServiceController extends Controller
{
    /**
     * Get all services with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $type = $request->input('type');
            $status = $request->input('status');

            $query = Service::with(['variants']);

            // Apply filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }

            $services = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'services' => $services->items(),
                    'pagination' => [
                        'current_page' => $services->currentPage(),
                        'last_page' => $services->lastPage(),
                        'per_page' => $services->perPage(),
                        'total' => $services->total()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services'
            ], 500);
        }
    }

    /**
     * Create a new service
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:editing,formatting,design,illustration',
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'variants' => 'array',
            'variants.*.title' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.unit_type' => 'required|string|in:fixed,per_word,per_page,per_hour',
            'variants.*.turnaround_days' => 'required|integer|min:1',
            'variants.*.min_quantity' => 'nullable|integer|min:1',
            'variants.*.max_quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate slug
            $slug = Str::slug($request->input('title'));
            $originalSlug = $slug;
            $counter = 1;

            while (Service::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Create service
            $service = Service::create([
                'title' => $request->input('title'),
                'slug' => $slug,
                'description' => $request->input('description'),
                'type' => $request->input('type'),
                'base_price' => $request->input('base_price'),
                'is_active' => $request->input('is_active', true),
                'is_featured' => $request->input('is_featured', false),
            ]);

            // Create variants if provided
            $variants = $request->input('variants', []);
            foreach ($variants as $variantData) {
                $service->variants()->create([
                    'title' => $variantData['title'],
                    'price' => $variantData['price'],
                    'unit_type' => $variantData['unit_type'],
                    'turnaround_days' => $variantData['turnaround_days'],
                    'min_quantity' => $variantData['min_quantity'] ?? null,
                    'max_quantity' => $variantData['max_quantity'] ?? null,
                    'is_active' => true,
                ]);
            }

            $service->load('variants');

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service'
            ], 500);
        }
    }

    /**
     * Get a specific service
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = Service::with(['variants'])->find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service'
            ], 500);
        }
    }

    /**
     * Update a service
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:editing,formatting,design,illustration',
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            // Update slug if title changed
            $slug = $service->slug;
            if ($service->title !== $request->input('title')) {
                $slug = Str::slug($request->input('title'));
                $originalSlug = $slug;
                $counter = 1;

                while (Service::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            $service->update([
                'title' => $request->input('title'),
                'slug' => $slug,
                'description' => $request->input('description'),
                'type' => $request->input('type'),
                'base_price' => $request->input('base_price'),
                'is_active' => $request->input('is_active', $service->is_active),
                'is_featured' => $request->input('is_featured', $service->is_featured),
            ]);

            $service->load('variants');

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service'
            ], 500);
        }
    }

    /**
     * Delete a service
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            // Check if service has active orders
            $hasActiveOrders = $service->variants()
                                     ->whereHas('orderItems.order', function ($query) {
                                         $query->whereNotIn('status', ['completed', 'cancelled']);
                                     })
                                     ->exists();

            if ($hasActiveOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete service with active orders'
                ], 400);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service'
            ], 500);
        }
    }

    /**
     * Toggle service status
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            $service->update([
                'is_active' => !$service->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service status updated successfully',
                'data' => [
                    'id' => $service->id,
                    'is_active' => $service->is_active
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service status'
            ], 500);
        }
    }

    /**
     * Get service variants
     *
     * @param int $serviceId
     * @return JsonResponse
     */
    public function variants(int $serviceId): JsonResponse
    {
        try {
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            $variants = $service->variants()->orderBy('created_at', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $variants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service variants'
            ], 500);
        }
    }

    /**
     * Create a service variant
     *
     * @param Request $request
     * @param int $serviceId
     * @return JsonResponse
     */
    public function createVariant(Request $request, int $serviceId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit_type' => 'required|string|in:fixed,per_word,per_page,per_hour',
            'turnaround_days' => 'required|integer|min:1',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            $variant = $service->variants()->create([
                'title' => $request->input('title'),
                'price' => $request->input('price'),
                'unit_type' => $request->input('unit_type'),
                'turnaround_days' => $request->input('turnaround_days'),
                'min_quantity' => $request->input('min_quantity'),
                'max_quantity' => $request->input('max_quantity'),
                'is_active' => $request->input('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service variant created successfully',
                'data' => $variant
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service variant'
            ], 500);
        }
    }

    /**
     * Update a service variant
     *
     * @param Request $request
     * @param int $serviceId
     * @param int $variantId
     * @return JsonResponse
     */
    public function updateVariant(Request $request, int $serviceId, int $variantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit_type' => 'required|string|in:fixed,per_word,per_page,per_hour',
            'turnaround_days' => 'required|integer|min:1',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $variant = ServiceVariant::where('service_id', $serviceId)
                                   ->where('id', $variantId)
                                   ->first();

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service variant not found'
                ], 404);
            }

            $variant->update([
                'title' => $request->input('title'),
                'price' => $request->input('price'),
                'unit_type' => $request->input('unit_type'),
                'turnaround_days' => $request->input('turnaround_days'),
                'min_quantity' => $request->input('min_quantity'),
                'max_quantity' => $request->input('max_quantity'),
                'is_active' => $request->input('is_active', $variant->is_active),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service variant updated successfully',
                'data' => $variant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service variant'
            ], 500);
        }
    }

    /**
     * Delete a service variant
     *
     * @param int $serviceId
     * @param int $variantId
     * @return JsonResponse
     */
    public function deleteVariant(int $serviceId, int $variantId): JsonResponse
    {
        try {
            $variant = ServiceVariant::where('service_id', $serviceId)
                                   ->where('id', $variantId)
                                   ->first();

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service variant not found'
                ], 404);
            }

            // Check if variant has active orders
            $hasActiveOrders = $variant->orderItems()
                                     ->whereHas('order', function ($query) {
                                         $query->whereNotIn('status', ['completed', 'cancelled']);
                                     })
                                     ->exists();

            if ($hasActiveOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete variant with active orders'
                ], 400);
            }

            $variant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service variant deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service variant'
            ], 500);
        }
    }
}
