/* ========================================
   KABAL SOLAR SYSTEM - INTERACTIVE JAVASCRIPT
   ======================================== */

// ========== MOBILE MENU TOGGLE ==========
const navToggle = document.getElementById('nav-toggle');
const navMenu = document.getElementById('nav-menu');
const navClose = document.getElementById('nav-close');
const navLinks = document.querySelectorAll('.nav__link');

// Open mobile menu
if (navToggle) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.add('show-menu');
    });
}

// Close mobile menu
if (navClose) {
    navClose.addEventListener('click', () => {
        navMenu.classList.remove('show-menu');
    });
}

// Close menu when clicking nav links
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('show-menu');
    });
});

// ========== ACTIVE LINK HIGHLIGHTING ==========
const sections = document.querySelectorAll('section[id]');

function scrollActive() {
    const scrollY = window.pageYOffset;

    sections.forEach(current => {
        const sectionHeight = current.offsetHeight;
        const sectionTop = current.offsetTop - 100;
        const sectionId = current.getAttribute('id');
        const link = document.querySelector('.nav__link[href*=' + sectionId + ']');

        if (link) {
            if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                link.classList.add('active-link');
            } else {
                link.classList.remove('active-link');
            }
        }
    });
}

window.addEventListener('scroll', scrollActive);

// ========== HEADER SHADOW ON SCROLL ==========
function scrollHeader() {
    const header = document.getElementById('header');
    if (this.scrollY >= 50) {
        header.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.15)';
    } else {
        header.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
    }
}

window.addEventListener('scroll', scrollHeader);

// ========== SCROLL TO TOP BUTTON ==========
const scrollTopBtn = document.getElementById('scrollTop');

function toggleScrollTop() {
    if (this.scrollY >= 400) {
        scrollTopBtn.classList.add('show');
    } else {
        scrollTopBtn.classList.remove('show');
    }
}

window.addEventListener('scroll', toggleScrollTop);

if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ========== SMOOTH SCROLLING FOR ANCHOR LINKS ==========
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        
        // Don't prevent default for just "#" (could be used for modals, etc.)
        if (href === '#') {
            e.preventDefault();
            return;
        }

        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            const headerHeight = 80;
            const targetPosition = target.offsetTop - headerHeight;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// ========== SCROLL REVEAL ANIMATIONS ==========
const revealElements = document.querySelectorAll('.service, .usp__card, .testimonial, .contact__card');

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
});

revealElements.forEach(element => {
    element.style.opacity = '0';
    element.style.transform = 'translateY(30px)';
    element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    revealObserver.observe(element);
});

// ========== FORM VALIDATION & SUBMISSION ==========
const quoteForm = document.getElementById('quoteForm');
const formMessage = document.getElementById('formMessage');

// Phone number formatting for Pakistan
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    // If starts with 92, format as international
    if (value.startsWith('92')) {
        value = '+' + value.slice(0, 2) + ' ' + value.slice(2, 5) + ' ' + value.slice(5);
    }
    // If starts with 03, add Pakistan code
    else if (value.startsWith('03')) {
        value = '+92 ' + value.slice(1, 4) + ' ' + value.slice(4);
    }
    
    input.value = value.trim();
}

const phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('blur', function() {
        formatPhoneNumber(this);
    });
}

// Form validation
function validateForm(formData) {
    const errors = [];
    
    // Name validation
    if (formData.get('name').trim().length < 3) {
        errors.push('Please enter your full name (minimum 3 characters)');
    }
    
    // Phone validation
    const phone = formData.get('phone').replace(/\D/g, '');
    if (phone.length < 10) {
        errors.push('Please enter a valid phone number');
    }
    
    // Email validation (optional but if provided, must be valid)
    const email = formData.get('email').trim();
    if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        errors.push('Please enter a valid email address');
    }
    
    // Address validation
    if (formData.get('address').trim().length < 10) {
        errors.push('Please enter your complete address');
    }
    
    // Service type validation
    if (!formData.get('service')) {
        errors.push('Please select a service type');
    }
    
    // Property type validation
    if (!formData.get('property')) {
        errors.push('Please select a property type');
    }
    
    return errors;
}

// Show message to user
function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = 'form__message ' + type;
    formMessage.style.display = 'block';
    
    // Scroll to message
    formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-hide success message after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }
}

// Form submission
if (quoteForm) {
    quoteForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Validate form
        const errors = validateForm(formData);
        if (errors.length > 0) {
            showMessage(errors.join('. '), 'error');
            return;
        }
        
        // Disable submit button
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
        
        try {
            // Send data to PHP backend
            const response = await fetch('submit_quote.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showMessage('Thank you! Your quote request has been submitted successfully. We will contact you within 24 hours.', 'success');
                quoteForm.reset();
                
                // Optional: Track conversion (Google Analytics, Facebook Pixel, etc.)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submit', {
                        'event_category': 'Quote Request',
                        'event_label': formData.get('service')
                    });
                }
            } else {
                showMessage('Error: ' + (result.message || 'Something went wrong. Please try again or call us directly.'), 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showMessage('Connection error. Please check your internet connection and try again, or contact us directly at +92 346 3499302', 'error');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
}

// ========== WHATSAPP QUICK MESSAGE ==========
const whatsappLinks = document.querySelectorAll('a[href*="wa.me"]');
whatsappLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        const service = new URLSearchParams(window.location.search).get('service') || 'solar system';
        const message = encodeURIComponent(`Hi! I'm interested in learning more about your ${service} services in Swat.`);
        
        // Add message to WhatsApp link if not already present
        if (!this.href.includes('?text=')) {
            this.href += `?text=${message}`;
        }
    });
});

// ========== CTA CLICK TRACKING ==========
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const btnText = this.textContent.trim();
        const btnHref = this.getAttribute('href');
        
        // Track button clicks (ready for analytics integration)
        console.log('CTA Click:', {
            text: btnText,
            href: btnHref,
            timestamp: new Date().toISOString()
        });
        
        // If you're using Google Analytics:
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                'event_category': 'CTA',
                'event_label': btnText
            });
        }
    });
});

// ========== TESTIMONIAL ROTATION (Optional Enhancement) ==========
// Uncomment if you want auto-rotating testimonials
/*
const testimonials = document.querySelectorAll('.testimonial');
let currentTestimonial = 0;

function rotateTestimonials() {
    if (testimonials.length > 1) {
        testimonials.forEach((testimonial, index) => {
            testimonial.style.display = index === currentTestimonial ? 'block' : 'none';
        });
        
        currentTestimonial = (currentTestimonial + 1) % testimonials.length;
    }
}

// Rotate every 5 seconds
setInterval(rotateTestimonials, 5000);
*/

// ========== LAZY LOADING IMAGES ==========
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
            observer.unobserve(img);
        }
    });
});

// For future optimization: use data-src for lazy loading
document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
});

// ========== PERFORMANCE OPTIMIZATION ==========
// Debounce scroll events
function debounce(func, wait) {
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

// Apply debouncing to scroll handlers
window.addEventListener('scroll', debounce(() => {
    scrollActive();
    scrollHeader();
    toggleScrollTop();
}, 10));

// ========== ACCESSIBILITY ENHANCEMENTS ==========
// Trap focus in mobile menu when open
const focusableElements = navMenu ? navMenu.querySelectorAll(
    'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
) : [];

if (focusableElements.length > 0) {
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    
    navMenu.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey && document.activeElement === firstFocusable) {
                e.preventDefault();
                lastFocusable.focus();
            } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                e.preventDefault();
                firstFocusable.focus();
            }
        }
        
        // Close menu on Escape key
        if (e.key === 'Escape') {
            navMenu.classList.remove('show-menu');
        }
    });
}

// ========== CONSOLE WELCOME MESSAGE ==========
console.log('%c🌞 Kabal Solar System', 'font-size: 24px; font-weight: bold; color: #F39C12;');
console.log('%cWebsite developed for sustainable energy solutions in Swat, Pakistan', 'font-size: 14px; color: #0A3D62;');
console.log('%cFor inquiries: +92 346 3499302', 'font-size: 12px; color: #27AE60;');

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', () => {
    console.log('✓ Kabal Solar System website loaded successfully');
    
    // Initial scroll position check
    scrollHeader();
    toggleScrollTop();
    scrollActive();
});
