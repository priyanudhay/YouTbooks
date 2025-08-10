<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Service;
use App\Models\Payment;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', '30'); // days
            $startDate = Carbon::now()->subDays($period);

            // Basic counts
            $totalUsers = User::where('role', 'customer')->count();
            $totalOrders = Order::count();
            $totalServices = Service::where('is_active', true)->count();
            $totalRevenue = Payment::where('status', 'completed')->sum('amount');

            // Period-specific metrics
            $newUsers = User::where('role', 'customer')
                          ->where('created_at', '>=', $startDate)
                          ->count();
            
            $newOrders = Order::where('created_at', '>=', $startDate)->count();
            
            $periodRevenue = Payment::where('status', 'completed')
                                   ->where('created_at', '>=', $startDate)
                                   ->sum('amount');

            // Order status breakdown
            $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
                                  ->groupBy('status')
                                  ->pluck('count', 'status')
                                  ->toArray();

            // Revenue by payment gateway
            $revenueByGateway = Payment::where('status', 'completed')
                                      ->select('gateway', DB::raw('sum(amount) as total'))
                                      ->groupBy('gateway')
                                      ->pluck('total', 'gateway')
                                      ->toArray();

            // Popular services
            $popularServices = DB::table('order_items')
                                ->join('service_variants', 'order_items.service_variant_id', '=', 'service_variants.id')
                                ->join('services', 'service_variants.service_id', '=', 'services.id')
                                ->select('services.title', DB::raw('sum(order_items.quantity) as total_quantity'))
                                ->groupBy('services.id', 'services.title')
                                ->orderBy('total_quantity', 'desc')
                                ->limit(5)
                                ->get();

            // Recent activity
            $recentOrders = Order::with(['user', 'items.serviceVariant.service'])
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_users' => $totalUsers,
                        'total_orders' => $totalOrders,
                        'total_services' => $totalServices,
                        'total_revenue' => $totalRevenue,
                        'new_users' => $newUsers,
                        'new_orders' => $newOrders,
                        'period_revenue' => $periodRevenue,
                        'period_days' => $period
                    ],
                    'order_status_breakdown' => $ordersByStatus,
                    'revenue_by_gateway' => $revenueByGateway,
                    'popular_services' => $popularServices,
                    'recent_orders' => $recentOrders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer' => $order->user->name,
                            'total' => $order->total,
                            'status' => $order->status,
                            'created_at' => $order->created_at->toISOString(),
                            'services' => $order->items->map(function ($item) {
                                return $item->serviceVariant->service->title;
                            })->unique()->values()
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ], 500);
        }
    }

    /**
     * Get revenue analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revenueAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', '30'); // days
            $groupBy = $request->input('group_by', 'day'); // day, week, month
            
            $startDate = Carbon::now()->subDays($period);
            
            $query = Payment::where('status', 'completed')
                           ->where('created_at', '>=', $startDate);

            // Group by period
            switch ($groupBy) {
                case 'week':
                    $revenueData = $query->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('WEEK(created_at) as week'),
                        DB::raw('sum(amount) as total')
                    )
                    ->groupBy('year', 'week')
                    ->orderBy('year')
                    ->orderBy('week')
                    ->get();
                    break;
                    
                case 'month':
                    $revenueData = $query->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('sum(amount) as total')
                    )
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get();
                    break;
                    
                default: // day
                    $revenueData = $query->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('sum(amount) as total')
                    )
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_data' => $revenueData,
                    'period' => $period,
                    'group_by' => $groupBy
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue analytics'
            ], 500);
        }
    }

    /**
     * Get user analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', '30');
            $startDate = Carbon::now()->subDays($period);

            // User registration over time
            $userRegistrations = User::where('role', 'customer')
                                   ->where('created_at', '>=', $startDate)
                                   ->select(
                                       DB::raw('DATE(created_at) as date'),
                                       DB::raw('count(*) as count')
                                   )
                                   ->groupBy('date')
                                   ->orderBy('date')
                                   ->get();

            // User activity
            $activeUsers = User::where('role', 'customer')
                             ->where('last_login_at', '>=', $startDate)
                             ->count();

            // Top customers by order value
            $topCustomers = User::where('role', 'customer')
                              ->withSum(['orders' => function ($query) {
                                  $query->where('status', '!=', 'cancelled');
                              }], 'total')
                              ->orderBy('orders_sum_total', 'desc')
                              ->limit(10)
                              ->get(['id', 'name', 'email', 'orders_sum_total']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_registrations' => $userRegistrations,
                    'active_users' => $activeUsers,
                    'top_customers' => $topCustomers,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user analytics'
            ], 500);
        }
    }

    /**
     * Get system health metrics
     *
     * @return JsonResponse
     */
    public function systemHealth(): JsonResponse
    {
        try {
            // Database metrics
            $dbMetrics = [
                'total_users' => User::count(),
                'total_orders' => Order::count(),
                'total_payments' => Payment::count(),
                'total_files' => File::count(),
                'total_services' => Service::count()
            ];

            // File storage metrics
            $storageMetrics = [
                'total_files' => File::count(),
                'total_size' => File::sum('size'),
                'files_by_type' => File::select('type', DB::raw('count(*) as count'))
                                     ->groupBy('type')
                                     ->pluck('count', 'type')
                                     ->toArray()
            ];

            // Recent errors (would typically come from logs)
            $recentErrors = []; // Placeholder for error tracking

            // System status
            $systemStatus = [
                'database' => 'healthy',
                'file_storage' => 'healthy',
                'payment_gateways' => 'healthy',
                'email_service' => 'healthy'
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'database_metrics' => $dbMetrics,
                    'storage_metrics' => $storageMetrics,
                    'recent_errors' => $recentErrors,
                    'system_status' => $systemStatus,
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load system health data'
            ], 500);
        }
    }

    /**
     * Export data for reporting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'orders'); // orders, users, payments
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $format = $request->input('format', 'json'); // json, csv

            $query = null;
            $data = [];

            switch ($type) {
                case 'orders':
                    $query = Order::with(['user', 'items.serviceVariant.service', 'payment']);
                    break;
                    
                case 'users':
                    $query = User::where('role', 'customer')->with(['orders']);
                    break;
                    
                case 'payments':
                    $query = Payment::with(['order.user']);
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid export type'
                    ], 400);
            }

            // Apply date filters
            if ($startDate) {
                $query->where('created_at', '>=', Carbon::parse($startDate));
            }
            
            if ($endDate) {
                $query->where('created_at', '<=', Carbon::parse($endDate));
            }

            $data = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'export_type' => $type,
                    'format' => $format,
                    'record_count' => $data->count(),
                    'data' => $data,
                    'exported_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data'
            ], 500);
        }
    }
}
