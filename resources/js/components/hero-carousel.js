// Hero Carousel Component for Alpine.js
export function heroCarousel() {
    return {
        currentSlide: 0,
        isPlaying: true,
        autoplayInterval: null,
        slides: [
            {
                title: 'Professional Book Editing',
                description: 'Transform your manuscript with expert line editing, copy editing, and proofreading services.',
                benefit: '✓ Improve clarity, flow, and readability',
                color: 'blue',
                icon: 'edit'
            },
            {
                title: 'Interior Layout & Formatting',
                description: 'Professional print and eBook formatting that makes your content shine.',
                benefit: '✓ Print-ready layouts and eBook optimization',
                color: 'green',
                icon: 'document'
            },
            {
                title: 'Book Cover Design',
                description: 'Eye-catching covers that make readers want to pick up your book.',
                benefit: '✓ Custom designs that capture your story',
                color: 'purple',
                icon: 'image'
            },
            {
                title: 'Custom Illustrations',
                description: 'Bring your story to life with custom artwork and illustrations.',
                benefit: '✓ Original artwork tailored to your vision',
                color: 'orange',
                icon: 'brush'
            }
        ],

        init() {
            this.startAutoplay();
        },

        goToSlide(index) {
            this.currentSlide = index;
            this.resetAutoplay();
        },

        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        },

        prevSlide() {
            this.currentSlide = this.currentSlide === 0 ? this.slides.length - 1 : this.currentSlide - 1;
        },

        startAutoplay() {
            if (this.isPlaying) {
                this.autoplayInterval = setInterval(() => {
                    this.nextSlide();
                }, 5000); // 5 seconds
            }
        },

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        },

        toggleAutoplay() {
            this.isPlaying = !this.isPlaying;
            if (this.isPlaying) {
                this.startAutoplay();
            } else {
                this.stopAutoplay();
            }
        },

        resetAutoplay() {
            this.stopAutoplay();
            if (this.isPlaying) {
                this.startAutoplay();
            }
        },

        scrollToCalculator() {
            document.getElementById('cost-calculator').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }
    };
}
