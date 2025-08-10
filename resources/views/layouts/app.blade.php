<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'YouTbooks Studio - Professional Book Editing & Design Services')</title>
    <meta name="description" content="@yield('description', 'Transform your manuscript into a masterpiece with our professional book editing, interior layout, cover design, and illustration services.')">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 h-full">
    <div id="app" class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200" x-data="{ mobileMenuOpen: false, cartOpen: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <a href="{{ route('home') }}" class="flex items-center">
                            <img class="h-8 w-auto" src="{{ asset('images/logo.svg') }}" alt="YouTbooks Studio">
                            <span class="ml-2 text-xl font-bold text-gray-900">YouTbooks Studio</span>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="{{ route('home') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors {{ request()->routeIs('home') ? 'text-blue-600' : '' }}">
                                Home
                            </a>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors flex items-center">
                                    Services
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                    <a href="{{ route('services.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Services</a>
                                    <a href="{{ route('services.editing') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Book Editing</a>
                                    <a href="{{ route('services.formatting') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Interior Layout</a>
                                    <a href="{{ route('services.design') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cover Design</a>
                                    <a href="{{ route('services.illustration') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Illustrations</a>
                                </div>
                            </div>
                            <a href="{{ route('pricing') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                Pricing
                            </a>
                            <a href="{{ route('packages') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                Packages
                            </a>
                            <a href="{{ route('blog.index') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                Blog
                            </a>
                            <a href="{{ route('faq') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                FAQ
                            </a>
                        </div>
                    </div>

                    <!-- Right side buttons -->
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <input type="search" placeholder="Search services..." class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Cart -->
                        <button @click="cartOpen = true" class="relative p-2 text-gray-600 hover:text-blue-600 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6M20 13v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2-2v4"></path>
                            </svg>
                            <span class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" x-text="cartCount">0</span>
                        </button>

                        <!-- Auth buttons -->
                        @guest
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                Login
                            </a>
                            <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Register
                            </a>
                        @else
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->avatar ?? asset('images/default-avatar.png') }}" alt="{{ auth()->user()->name }}">
                                    <span class="ml-2 text-gray-700">{{ auth()->user()->name }}</span>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                    <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Account</a>
                                    <a href="{{ route('account.orders') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Orders</a>
                                    <a href="{{ route('account.wishlist') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Wishlist</a>
                                    @if(auth()->user()->isAdmin())
                                        <div class="border-t border-gray-100"></div>
                                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Admin Panel</a>
                                    @endif
                                    <div class="border-t border-gray-100"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endguest
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div x-show="mobileMenuOpen" x-transition class="md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">Home</a>
                    <a href="{{ route('services.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">Services</a>
                    <a href="{{ route('pricing') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">Pricing</a>
                    <a href="{{ route('packages') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">Packages</a>
                    <a href="{{ route('blog.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">Blog</a>
                    <a href="{{ route('faq') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">FAQ</a>
                    @guest
                        <div class="border-t border-gray-200 pt-4">
                            <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">Login</a>
                            <a href="{{ route('register') }}" class="block px-3 py-2 rounded-md text-base font-medium bg-blue-600 text-white hover:bg-blue-700">Register</a>
                        </div>
                    @else
                        <div class="border-t border-gray-200 pt-4">
                            <a href="{{ route('account.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">My Account</a>
                            <a href="{{ route('account.orders') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:text-blue-600">My Orders</a>
                        </div>
                    @endguest
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Company Info -->
                    <div class="col-span-1 md:col-span-2">
                        <div class="flex items-center mb-4">
                            <img class="h-8 w-auto" src="{{ asset('images/logo-white.svg') }}" alt="YouTbooks Studio">
                            <span class="ml-2 text-xl font-bold">YouTbooks Studio</span>
                        </div>
                        <p class="text-gray-300 mb-4">
                            Transform your manuscript into a masterpiece with our professional book editing, design, and publishing services.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-300 hover:text-white transition-colors">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Services -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Services</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('services.editing') }}" class="text-gray-300 hover:text-white transition-colors">Book Editing</a></li>
                            <li><a href="{{ route('services.formatting') }}" class="text-gray-300 hover:text-white transition-colors">Interior Layout</a></li>
                            <li><a href="{{ route('services.design') }}" class="text-gray-300 hover:text-white transition-colors">Cover Design</a></li>
                            <li><a href="{{ route('services.illustration') }}" class="text-gray-300 hover:text-white transition-colors">Illustrations</a></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Support</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                            <li><a href="{{ route('faq') }}" class="text-gray-300 hover:text-white transition-colors">FAQ</a></li>
                            <li><a href="{{ route('privacy') }}" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                            <li><a href="{{ route('terms') }}" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-300 text-sm">
                        © {{ date('Y') }} YouTbooks Studio. All rights reserved.
                    </p>
                    <p class="text-gray-300 text-sm mt-2 md:mt-0">
                        Transforming manuscripts into masterpieces, one edit at a time.
                    </p>
                </div>
            </div>
        </footer>

        <!-- Cart Sidebar -->
        @include('components.cart-sidebar')
    </div>

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>
