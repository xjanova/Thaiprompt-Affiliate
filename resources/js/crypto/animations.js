/**
 * Premium Animations Module
 * GSAP-powered animations for crypto wallet pages
 */

import { gsap } from 'gsap';

/**
 * Initialize page entrance animations
 */
export function initPageAnimations() {
    // Fade in page content
    gsap.from('.animate-fade-in', {
        opacity: 0,
        y: 30,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power2.out'
    });

    // Scale in cards
    gsap.from('.animate-scale-in', {
        scale: 0.9,
        opacity: 0,
        duration: 0.6,
        stagger: 0.1,
        ease: 'back.out(1.4)'
    });

    // Slide in from left
    gsap.from('.animate-slide-left', {
        x: -100,
        opacity: 0,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power3.out'
    });

    // Slide in from right
    gsap.from('.animate-slide-right', {
        x: 100,
        opacity: 0,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power3.out'
    });
}

/**
 * Animate number counter (for prices, balances)
 */
export function animateCounter(element, endValue, duration = 1.5, decimals = 2) {
    const obj = { value: 0 };

    gsap.to(obj, {
        value: endValue,
        duration: duration,
        ease: 'power2.out',
        onUpdate: function() {
            element.textContent = obj.value.toLocaleString('th-TH', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }
    });
}

/**
 * Price pulse animation (when price updates)
 */
export function pricePulse(element, isPositive = true) {
    const color = isPositive ? '#22c55e' : '#ef4444';

    gsap.timeline()
        .to(element, {
            scale: 1.1,
            color: color,
            duration: 0.2,
            ease: 'power2.out'
        })
        .to(element, {
            scale: 1,
            duration: 0.3,
            ease: 'elastic.out(1, 0.5)'
        });
}

/**
 * Success celebration animation
 */
export function celebrateSuccess(element) {
    gsap.timeline()
        .to(element, {
            scale: 1.2,
            duration: 0.3,
            ease: 'back.out(2)'
        })
        .to(element, {
            scale: 1,
            duration: 0.4,
            ease: 'elastic.out(1, 0.3)'
        });

    // Create confetti effect
    createConfetti(element);
}

/**
 * Create confetti particles
 */
function createConfetti(element) {
    const rect = element.getBoundingClientRect();
    const colors = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'];

    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: fixed;
            width: 8px;
            height: 8px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            left: ${rect.left + rect.width / 2}px;
            top: ${rect.top + rect.height / 2}px;
        `;
        document.body.appendChild(particle);

        gsap.to(particle, {
            x: (Math.random() - 0.5) * 300,
            y: (Math.random() - 0.5) * 300 - 100,
            opacity: 0,
            duration: 1 + Math.random(),
            ease: 'power2.out',
            onComplete: () => particle.remove()
        });
    }
}

/**
 * Shake animation (for errors)
 */
export function shake(element) {
    gsap.timeline()
        .to(element, { x: -10, duration: 0.1 })
        .to(element, { x: 10, duration: 0.1 })
        .to(element, { x: -10, duration: 0.1 })
        .to(element, { x: 10, duration: 0.1 })
        .to(element, { x: 0, duration: 0.1 });
}

/**
 * Loading pulse animation
 */
export function loadingPulse(element) {
    gsap.to(element, {
        opacity: 0.5,
        duration: 0.8,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
    });
}

/**
 * Card flip animation
 */
export function flipCard(element, onComplete) {
    gsap.timeline()
        .to(element, {
            rotateY: 90,
            duration: 0.3,
            ease: 'power2.in'
        })
        .call(() => {
            if (onComplete) onComplete();
        })
        .to(element, {
            rotateY: 0,
            duration: 0.3,
            ease: 'power2.out'
        });
}

/**
 * Smooth scroll to element
 */
export function smoothScrollTo(element, offset = 100) {
    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset - offset;

    gsap.to(window, {
        scrollTo: {
            y: targetPosition,
            autoKill: false
        },
        duration: 1,
        ease: 'power3.inOut'
    });
}

/**
 * Glowing effect animation
 */
export function glowEffect(element, color = '#8b5cf6') {
    gsap.timeline({ repeat: -1, yoyo: true })
        .to(element, {
            boxShadow: `0 0 20px ${color}, 0 0 40px ${color}`,
            duration: 1.5,
            ease: 'power1.inOut'
        });
}

/**
 * Number ticker animation (for live price updates)
 */
export function tickerUpdate(element, newValue, oldValue) {
    const isIncrease = newValue > oldValue;
    const color = isIncrease ? '#22c55e' : '#ef4444';

    // Highlight change
    gsap.timeline()
        .to(element, {
            backgroundColor: color + '20',
            duration: 0.2
        })
        .to(element, {
            backgroundColor: 'transparent',
            duration: 0.5,
            delay: 0.3
        });

    // Update number with animation
    animateCounter(element, newValue, 0.5);
}

/**
 * Stagger animation for lists
 */
export function staggerList(selector, delay = 0.1) {
    gsap.from(selector, {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: delay,
        ease: 'power2.out'
    });
}

/**
 * Parallax scroll effect
 */
export function parallaxScroll(element, speed = 0.5) {
    window.addEventListener('scroll', () => {
        const scrollY = window.pageYOffset;
        gsap.to(element, {
            y: scrollY * speed,
            duration: 0.5,
            ease: 'power1.out'
        });
    });
}

/**
 * Morphing gradient background
 */
export function morphGradient(element) {
    const gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    ];

    let currentIndex = 0;

    setInterval(() => {
        currentIndex = (currentIndex + 1) % gradients.length;
        gsap.to(element, {
            background: gradients[currentIndex],
            duration: 2,
            ease: 'power2.inOut'
        });
    }, 5000);
}

/**
 * Floating animation
 */
export function floatAnimation(element, distance = 20) {
    gsap.to(element, {
        y: -distance,
        duration: 2,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
    });
}

/**
 * Reveal animation with mask
 */
export function revealMask(element) {
    gsap.from(element, {
        clipPath: 'inset(0 100% 0 0)',
        duration: 1,
        ease: 'power3.inOut'
    });
}

/**
 * Trading button press effect
 */
export function buttonPress(button, callback) {
    gsap.timeline()
        .to(button, {
            scale: 0.95,
            duration: 0.1,
            ease: 'power2.in'
        })
        .to(button, {
            scale: 1,
            duration: 0.2,
            ease: 'elastic.out(1, 0.5)',
            onComplete: callback
        });
}

/**
 * Initialize all animations when DOM is ready
 */
export function initAllAnimations() {
    // Run page entrance animations
    initPageAnimations();

    // Add hover animations to cards
    document.querySelectorAll('.crypto-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                y: -5,
                scale: 1.02,
                duration: 0.3,
                ease: 'power2.out'
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                y: 0,
                scale: 1,
                duration: 0.3,
                ease: 'power2.in'
            });
        });
    });

    // Add button press effects
    document.querySelectorAll('.btn-animated').forEach(button => {
        button.addEventListener('click', function(e) {
            buttonPress(this);
        });
    });

    console.log('✨ Premium animations initialized');
}

// Auto-initialize when imported
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllAnimations);
    } else {
        initAllAnimations();
    }
}

// Export all functions
export default {
    initPageAnimations,
    animateCounter,
    pricePulse,
    celebrateSuccess,
    shake,
    loadingPulse,
    flipCard,
    smoothScrollTo,
    glowEffect,
    tickerUpdate,
    staggerList,
    parallaxScroll,
    morphGradient,
    floatAnimation,
    revealMask,
    buttonPress,
    initAllAnimations
};
