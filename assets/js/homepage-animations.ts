// Homepage animations controller
// Handles interactive animations and effects for the homepage

import { Controller } from '@hotwired/stimulus';

interface AnimationOptions {
    duration?: number;
    delay?: number;
    easing?: string;
}

export default class extends Controller<HTMLElement> {
    static targets = [
        'typingText',
        'card',
        'featureCard',
        'stepCard'
    ];

    declare readonly typingTextTarget: HTMLElement;
    declare readonly cardTargets: HTMLElement[];
    declare readonly featureCardTargets: HTMLElement[];
    declare readonly stepCardTargets: HTMLElement[];

    private typingInterval: number | null = null;
    private observerOptions: IntersectionObserverInit = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    private observer: IntersectionObserver | null = null;

    connect(): void {
        this.initializeAnimations();
        this.setupIntersectionObserver();
        this.startTypingAnimation();
        this.animateCardsOnLoad();
    }

    disconnect(): void {
        if (this.typingInterval) {
            clearInterval(this.typingInterval);
        }
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    // Initialize all animations
    private initializeAnimations(): void {
        // Add initial states for elements that will be animated
        this.featureCardTargets.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
        });

        this.stepCardTargets.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
        });
    }

    // Setup intersection observer for scroll animations
    private setupIntersectionObserver(): void {
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.animateElement(entry.target as HTMLElement);
                }
            });
        }, this.observerOptions);

        // Observe feature cards
        this.featureCardTargets.forEach(card => {
            this.observer?.observe(card);
        });

        // Observe step cards
        this.stepCardTargets.forEach(card => {
            this.observer?.observe(card);
        });
    }

    // Animate element when it comes into view
    private animateElement(element: HTMLElement): void {
        const isFeatureCard = this.featureCardTargets.includes(element);
        const isStepCard = this.stepCardTargets.includes(element);

        if (isFeatureCard || isStepCard) {
            const cards = isFeatureCard ? this.featureCardTargets : this.stepCardTargets;
            const index = cards.indexOf(element);

            setTimeout(() => {
                element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 150); // Stagger animation
        }
    }

    // Typing animation for hero text
    private startTypingAnimation(): void {
        if (!this.hasTypingTextTarget) return;

        const text = 'Make Planning Poker';
        const element = this.typingTextTarget;
        let index = 0;

        // Clear existing text
        element.textContent = '';

        this.typingInterval = window.setInterval(() => {
            if (index < text.length) {
                element.textContent += text.charAt(index);
                index++;
            } else {
                if (this.typingInterval) {
                    clearInterval(this.typingInterval);
                }
            }
        }, 100);
    }

    // Animate poker cards on page load
    private animateCardsOnLoad(): void {
        this.cardTargets.forEach((card, index) => {
            // Initial state
            card.style.opacity = '0';
            card.style.transform = 'translateY(100px) rotate(0deg)';

            // Animate in with delay
            setTimeout(() => {
                card.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) rotate(0deg)';
            }, 500 + (index * 200));
        });
    }

    // Handle create room button click
    createRoom(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLButtonElement;

        // Add loading state
        button.style.transform = 'scale(0.95)';
        button.textContent = 'Creating...';

        // Simulate room creation (replace with actual logic)
        setTimeout(() => {
            button.style.transform = 'scale(1)';
            button.textContent = '🚀 Create New Room';

            // Here you would typically redirect to room creation
            // window.location.href = '/room/create';
        }, 1000);
    }

    // Add hover effects to cards
    private addCardHoverEffects(): void {
        this.cardTargets.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) rotate(5deg) scale(1.05)';
                card.style.zIndex = '10';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) rotate(0deg) scale(1)';
                card.style.zIndex = 'auto';
            });
        });
    }

    // Parallax effect for floating shapes
    private initParallaxEffect(): void {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const shapes = document.querySelectorAll('.shape');

            shapes.forEach((shape, index) => {
                const speed = 0.5 + (index * 0.1);
                const yPos = -(scrolled * speed);
                (shape as HTMLElement).style.transform = `translateY(${yPos}px)`;
            });
        });
    }

    // Utility method to animate element with custom options
    private animateElementWithOptions(
        element: HTMLElement,
        properties: Partial<CSSStyleDeclaration>,
        options: AnimationOptions = {}
    ): Promise<void> {
        return new Promise((resolve) => {
            const {
                duration = 300,
                delay = 0,
                easing = 'ease'
            } = options;

            setTimeout(() => {
                element.style.transition = `all ${duration}ms ${easing}`;

                Object.assign(element.style, properties);

                setTimeout(() => {
                    resolve();
                }, duration);
            }, delay);
        });
    }

    // Add pulse effect to buttons
    private addButtonPulseEffect(): void {
        const buttons = document.querySelectorAll('.hero__btn, .join-form__btn');

        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                const ripple = document.createElement('span');
                const rect = (button as HTMLElement).getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = (e as MouseEvent).clientX - rect.left - size / 2;
                const y = (e as MouseEvent).clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;

                button.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // Initialize all effects when controller connects
    private initializeAllEffects(): void {
        this.addCardHoverEffects();
        this.initParallaxEffect();
        this.addButtonPulseEffect();
    }
}

// Add ripple animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .hero__btn, .join-form__btn {
        position: relative;
        overflow: hidden;
    }
`;
document.head.appendChild(style);
