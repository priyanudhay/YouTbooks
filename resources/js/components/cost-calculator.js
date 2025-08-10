// Cost Calculator Component for Alpine.js
export function costCalculator() {
    return {
        selectedService: '',
        selectedVariant: '',
        quantity: 1,
        turnaroundTier: 'standard',
        addOns: [],
        variants: [],
        estimatedPrice: 0,
        estimatedDelivery: '',
        loading: false,
        quantityLabel: 'Quantity',

        serviceVariants: {
            editing: [
                { id: 1, title: 'Line Editing', price: 0.015, unit_type: 'per_word', formatted_price: '$0.015 per word' },
                { id: 2, title: 'Copy Editing', price: 0.012, unit_type: 'per_word', formatted_price: '$0.012 per word' },
                { id: 3, title: 'Proofreading', price: 0.008, unit_type: 'per_word', formatted_price: '$0.008 per word' }
            ],
            formatting: [
                { id: 4, title: 'Print Interior', price: 3.50, unit_type: 'per_page', formatted_price: '$3.50 per page' },
                { id: 5, title: 'eBook Formatting', price: 299, unit_type: 'fixed', formatted_price: '$299 fixed' },
                { id: 6, title: 'Print + eBook Combo', price: 449, unit_type: 'fixed', formatted_price: '$449 fixed' }
            ],
            design: [
                { id: 7, title: 'Premade Cover', price: 199, unit_type: 'fixed', formatted_price: '$199 fixed' },
                { id: 8, title: 'Custom Cover', price: 399, unit_type: 'fixed', formatted_price: '$399 fixed' },
                { id: 9, title: 'Premium Custom', price: 599, unit_type: 'fixed', formatted_price: '$599 fixed' }
            ],
            illustration: [
                { id: 10, title: 'Simple Illustration', price: 75, unit_type: 'per_hour', formatted_price: '$75 per hour' },
                { id: 11, title: 'Detailed Illustration', price: 125, unit_type: 'per_hour', formatted_price: '$125 per hour' },
                { id: 12, title: 'Character Design', price: 150, unit_type: 'per_hour', formatted_price: '$150 per hour' }
            ]
        },

        updateVariants() {
            if (this.selectedService && this.serviceVariants[this.selectedService]) {
                this.variants = this.serviceVariants[this.selectedService];
                this.selectedVariant = '';
                this.estimatedPrice = 0;
                this.estimatedDelivery = '';
            } else {
                this.variants = [];
            }
        },

        updatePricing() {
            if (!this.selectedVariant || !this.quantity) {
                this.estimatedPrice = 0;
                this.estimatedDelivery = '';
                return;
            }

            this.loading = true;

            // Find the selected variant
            const variant = this.variants.find(v => v.id == this.selectedVariant);
            if (!variant) {
                this.loading = false;
                return;
            }

            // Update quantity label based on unit type
            this.quantityLabel = this.getQuantityLabel(variant.unit_type);

            // Calculate base price
            let basePrice = variant.price * this.quantity;

            // Apply turnaround multiplier
            const turnaroundMultiplier = this.getTurnaroundMultiplier();
            const turnaroundPrice = basePrice * turnaroundMultiplier;

            // Calculate add-ons
            const addOnsPrice = this.calculateAddOnsPrice();

            // Total price
            this.estimatedPrice = turnaroundPrice + addOnsPrice;

            // Calculate delivery date
            this.estimatedDelivery = this.calculateDeliveryDate(variant);

            this.loading = false;
        },

        getQuantityLabel(unitType) {
            switch (unitType) {
                case 'per_word':
                    return 'Word Count';
                case 'per_page':
                    return 'Page Count';
                case 'per_hour':
                    return 'Hours Needed';
                case 'fixed':
                    return 'Quantity';
                default:
                    return 'Quantity';
            }
        },

        getTurnaroundMultiplier() {
            switch (this.turnaroundTier) {
                case 'rush':
                    return 1.5; // 50% extra
                case 'express':
                    return 2.0; // 100% extra
                default:
                    return 1.0; // Standard
            }
        },

        calculateAddOnsPrice() {
            let total = 0;
            this.addOns.forEach(addOn => {
                switch (addOn) {
                    case 'priority_support':
                        total += 25;
                        break;
                    case 'additional_revision':
                        total += 15;
                        break;
                    case 'expedited_review':
                        total += 35;
                        break;
                    case 'style_guide_creation':
                        total += 50;
                        break;
                }
            });
            return total;
        },

        calculateDeliveryDate(variant) {
            const baseDays = 7; // Default turnaround
            let adjustedDays;

            switch (this.turnaroundTier) {
                case 'rush':
                    adjustedDays = Math.max(1, Math.ceil(baseDays * 0.5));
                    break;
                case 'express':
                    adjustedDays = Math.max(1, Math.ceil(baseDays * 0.25));
                    break;
                default:
                    adjustedDays = baseDays;
            }

            const deliveryDate = new Date();
            deliveryDate.setDate(deliveryDate.getDate() + adjustedDays);
            
            return deliveryDate.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },

        async addToCart() {
            if (!this.selectedVariant || !this.quantity) {
                this.showNotification('Please select a service and quantity', 'error');
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/api/v1/cart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        service_variant_id: this.selectedVariant,
                        quantity: this.quantity,
                        meta: {
                            turnaround_tier: this.turnaroundTier,
                            add_ons: this.addOns
                        }
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Item added to cart successfully!', 'success');
                    this.updateCartCount();
                    
                    // Optional: Reset form or redirect to cart
                    // this.resetForm();
                } else {
                    this.showNotification(data.message || 'Failed to add item to cart', 'error');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                this.showNotification('An error occurred. Please try again.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async requestSample() {
            if (!this.selectedService) {
                this.showNotification('Please select a service first', 'error');
                return;
            }

            // Redirect to sample request page or open modal
            window.location.href = `/services/${this.selectedService}/sample`;
        },

        resetForm() {
            this.selectedService = '';
            this.selectedVariant = '';
            this.quantity = 1;
            this.turnaroundTier = 'standard';
            this.addOns = [];
            this.variants = [];
            this.estimatedPrice = 0;
            this.estimatedDelivery = '';
        },

        showNotification(message, type = 'info') {
            // Create and show notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        },

        async updateCartCount() {
            try {
                const response = await fetch('/api/v1/cart');
                const data = await response.json();
                
                if (data.success) {
                    // Update cart count in navigation
                    const cartCountElement = document.querySelector('[x-text="cartCount"]');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.data.total_items || 0;
                    }
                }
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }
    };
}
