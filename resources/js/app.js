import './bootstrap';
import Alpine from 'alpinejs';

// Import Alpine.js components
import { heroCarousel } from './components/hero-carousel.js';
import { costCalculator } from './components/cost-calculator.js';

// Register Alpine.js components globally
window.Alpine = Alpine;

// Register components
Alpine.data('heroCarousel', heroCarousel);
Alpine.data('costCalculator', costCalculator);

// Global Alpine.js data and utilities
Alpine.data('navigation', () => ({
    mobileMenuOpen: false,
    cartSidebarOpen: false,
    cartCount: 0,
    
    init() {
        this.loadCartCount();
    },
    
    toggleMobileMenu() {
        this.mobileMenuOpen = !this.mobileMenuOpen;
    },
    
    toggleCartSidebar() {
        this.cartSidebarOpen = !this.cartSidebarOpen;
        if (this.cartSidebarOpen) {
            this.loadCartItems();
        }
    },
    
    async loadCartCount() {
        try {
            const response = await fetch('/api/v1/cart');
            const data = await response.json();
            
            if (data.success) {
                this.cartCount = data.data.total_items || 0;
            }
        } catch (error) {
            console.error('Error loading cart count:', error);
        }
    },
    
    async loadCartItems() {
        // Load cart items for sidebar
        try {
            const response = await fetch('/api/v1/cart');
            const data = await response.json();
            
            if (data.success) {
                this.cartItems = data.data.items || [];
                this.cartTotal = data.data.total || 0;
            }
        } catch (error) {
            console.error('Error loading cart items:', error);
        }
    }
}));

// Global notification system
Alpine.data('notifications', () => ({
    notifications: [],
    
    show(message, type = 'info', duration = 3000) {
        const id = Date.now();
        const notification = {
            id,
            message,
            type,
            visible: true
        };
        
        this.notifications.push(notification);
        
        setTimeout(() => {
            this.remove(id);
        }, duration);
    },
    
    remove(id) {
        const index = this.notifications.findIndex(n => n.id === id);
        if (index > -1) {
            this.notifications[index].visible = false;
            setTimeout(() => {
                this.notifications.splice(index, 1);
            }, 300); // Allow for fade out animation
        }
    }
}));

// Global utilities
Alpine.data('utils', () => ({
    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        return new Date(date).toLocaleDateString('en-US', {
            ...defaultOptions,
            ...options
        });
    },
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}));

// Start Alpine.js
Alpine.start();

// Global error handling
window.addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection:', event.reason);
    
    // Show user-friendly error message
    if (window.Alpine && window.Alpine.store) {
        const notifications = document.querySelector('[x-data*="notifications"]');
        if (notifications) {
            notifications._x_dataStack[0].show('An unexpected error occurred. Please try again.', 'error');
        }
    }
});

// CSRF token setup for all AJAX requests
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// Service Worker registration (for PWA features if needed later)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // navigator.serviceWorker.register('/sw.js')
        //     .then(registration => console.log('SW registered'))
        //     .catch(registrationError => console.log('SW registration failed'));
    });
}
