@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50" x-data="adminDashboard()">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                    <p class="text-sm text-gray-600">YouTbooks Studio Management</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <select x-model="selectedPeriod" @change="loadDashboard" 
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                    
                    <button @click="refreshDashboard" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading dashboard...</p>
        </div>

        <!-- Dashboard Content -->
        <div x-show="!loading" class="space-y-8">
            
            <!-- Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900" x-text="overview.total_users || 0"></dd>
                            </dl>
                        </div>
                        <div class="text-sm text-green-600" x-show="overview.new_users > 0">
                            +<span x-text="overview.new_users"></span> new
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Orders</dt>
                                <dd class="text-lg font-medium text-gray-900" x-text="overview.total_orders || 0"></dd>
                            </dl>
                        </div>
                        <div class="text-sm text-green-600" x-show="overview.new_orders > 0">
                            +<span x-text="overview.new_orders"></span> new
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Services</dt>
                                <dd class="text-lg font-medium text-gray-900" x-text="overview.total_services || 0"></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900" x-text="formatCurrency(overview.total_revenue || 0)"></dd>
                            </dl>
                        </div>
                        <div class="text-sm text-green-600" x-show="overview.period_revenue > 0">
                            +<span x-text="formatCurrency(overview.period_revenue)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Order Status Chart -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Status Breakdown</h3>
                    <div class="space-y-3">
                        <template x-for="(count, status) in orderStatusBreakdown" :key="status">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3" 
                                         :class="getStatusColor(status)"></div>
                                    <span class="text-sm font-medium text-gray-900 capitalize" x-text="status.replace('_', ' ')"></span>
                                </div>
                                <span class="text-sm text-gray-600" x-text="count"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Revenue by Gateway -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Revenue by Payment Gateway</h3>
                    <div class="space-y-3">
                        <template x-for="(amount, gateway) in revenueByGateway" :key="gateway">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3" 
                                         :class="getGatewayColor(gateway)"></div>
                                    <span class="text-sm font-medium text-gray-900 capitalize" x-text="gateway"></span>
                                </div>
                                <span class="text-sm text-gray-600" x-text="formatCurrency(amount)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Popular Services & Recent Orders -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Popular Services -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Popular Services</h3>
                    <div class="space-y-3">
                        <template x-for="service in popularServices" :key="service.title">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900" x-text="service.title"></span>
                                <span class="text-sm text-gray-600">
                                    <span x-text="service.total_quantity"></span> orders
                                </span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Orders</h3>
                        <a href="/admin/orders" class="text-sm text-blue-600 hover:text-blue-800">View all</a>
                    </div>
                    <div class="space-y-3">
                        <template x-for="order in recentOrders" :key="order.id">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900" x-text="order.order_number"></p>
                                    <p class="text-xs text-gray-500" x-text="order.customer"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900" x-text="formatCurrency(order.total)"></p>
                                    <p class="text-xs" :class="getStatusTextColor(order.status)" x-text="order.status"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="/admin/services/create" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Service
                    </a>
                    
                    <a href="/admin/orders" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Manage Orders
                    </a>
                    
                    <a href="/admin/users" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        Manage Users
                    </a>
                    
                    <button @click="exportData" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m-6 4H6a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v9a2 2 0 01-2 2z"/>
                        </svg>
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function adminDashboard() {
    return {
        loading: false,
        selectedPeriod: '30',
        overview: {},
        orderStatusBreakdown: {},
        revenueByGateway: {},
        popularServices: [],
        recentOrders: [],

        init() {
            this.loadDashboard();
        },

        async loadDashboard() {
            this.loading = true;
            
            try {
                const response = await fetch(`/api/v1/admin/dashboard/overview?period=${this.selectedPeriod}`);
                const data = await response.json();

                if (data.success) {
                    this.overview = data.data.overview;
                    this.orderStatusBreakdown = data.data.order_status_breakdown;
                    this.revenueByGateway = data.data.revenue_by_gateway;
                    this.popularServices = data.data.popular_services;
                    this.recentOrders = data.data.recent_orders;
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
                this.$dispatch('show-notification', { 
                    message: 'Failed to load dashboard data', 
                    type: 'error' 
                });
            } finally {
                this.loading = false;
            }
        },

        refreshDashboard() {
            this.loadDashboard();
        },

        async exportData() {
            try {
                const response = await fetch('/api/v1/admin/dashboard/export?type=orders');
                const data = await response.json();

                if (data.success) {
                    // Create and download file
                    const blob = new Blob([JSON.stringify(data.data, null, 2)], { 
                        type: 'application/json' 
                    });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `youtbooks-orders-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    this.$dispatch('show-notification', { 
                        message: 'Data exported successfully', 
                        type: 'success' 
                    });
                }
            } catch (error) {
                console.error('Error exporting data:', error);
                this.$dispatch('show-notification', { 
                    message: 'Failed to export data', 
                    type: 'error' 
                });
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },

        getStatusColor(status) {
            const colors = {
                pending: 'bg-yellow-400',
                confirmed: 'bg-blue-400',
                in_progress: 'bg-purple-400',
                completed: 'bg-green-400',
                cancelled: 'bg-red-400'
            };
            return colors[status] || 'bg-gray-400';
        },

        getStatusTextColor(status) {
            const colors = {
                pending: 'text-yellow-600',
                confirmed: 'text-blue-600',
                in_progress: 'text-purple-600',
                completed: 'text-green-600',
                cancelled: 'text-red-600'
            };
            return colors[status] || 'text-gray-600';
        },

        getGatewayColor(gateway) {
            const colors = {
                stripe: 'bg-blue-400',
                paypal: 'bg-yellow-400',
                razorpay: 'bg-purple-400'
            };
            return colors[gateway] || 'bg-gray-400';
        }
    };
}
</script>
@endsection
