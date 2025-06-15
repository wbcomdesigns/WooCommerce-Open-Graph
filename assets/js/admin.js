/**
 * Enhanced Woo Open Graph - JavaScript functionality
 * Modern ES6+ code with proper error handling and accessibility
 */

class EWOGSocialShare {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initAccessibility();
    }

    bindEvents() {
        // Copy link functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.ewog-share-copy')) {
                e.preventDefault();
                this.copyLink(e.target.closest('.ewog-share-copy'));
            }
        });

        // Track social share clicks
        document.addEventListener('click', (e) => {
            const shareBtn = e.target.closest('.ewog-share-btn:not(.ewog-share-copy)');
            if (shareBtn) {
                this.trackShare(shareBtn);
            }
        });

        // Handle keyboard navigation
        document.addEventListener('keydown', (e) => {
            const shareBtn = e.target.closest('.ewog-share-btn');
            if (shareBtn && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                shareBtn.click();
            }
        });
    }

    async copyLink(button) {
        const url = button.dataset.url;
        
        if (!url) return;

        try {
            // Modern clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                // Fallback for older browsers
                this.fallbackCopyText(url);
            }

            this.showCopySuccess(button);
            this.announceToScreenReader(ewogShare.copied);
            
        } catch (err) {
            console.error('Failed to copy link:', err);
            this.showCopyError(button);
            this.announceToScreenReader(ewogShare.copyFailed);
        }
    }

    fallbackCopyText(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
        } finally {
            document.body.removeChild(textArea);
        }
    }

    showCopySuccess(button) {
        const originalText = button.querySelector('.ewog-share-text');
        const originalContent = originalText ? originalText.textContent : '';
        
        button.classList.add('copied');
        
        if (originalText) {
            originalText.textContent = ewogShare.copied;
        }

        setTimeout(() => {
            button.classList.remove('copied');
            if (originalText) {
                originalText.textContent = originalContent;
            }
        }, 2000);
    }

    showCopyError(button) {
        button.style.background = '#dc3545';
        button.style.borderColor = '#dc3545';
        button.style.color = 'white';

        setTimeout(() => {
            button.style.background = '';
            button.style.borderColor = '';
            button.style.color = '';
        }, 2000);
    }

    trackShare(button) {
        const platform = button.dataset.platform;
        
        if (!platform) return;

        // Google Analytics 4 tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', 'share', {
                method: platform,
                content_type: 'product',
                item_id: this.getCurrentProductId()
            });
        }

        // Custom tracking event
        if (typeof jQuery !== 'undefined') {
            jQuery(document).trigger('ewog_social_share', {
                platform: platform,
                url: window.location.href,
                product_id: this.getCurrentProductId()
            });
        }

        // Console log for debugging
        console.log(`Shared on ${platform}:`, window.location.href);
    }

    getCurrentProductId() {
        // Try to get product ID from various sources
        const bodyClasses = document.body.className;
        const productIdMatch = bodyClasses.match(/postid-(\d+)/);
        
        if (productIdMatch) {
            return productIdMatch[1];
        }

        // Try to get from form data
        const form = document.querySelector('form.cart');
        if (form) {
            const productIdInput = form.querySelector('input[name="product_id"]');
            if (productIdInput) {
                return productIdInput.value;
            }
        }

        return null;
    }

    initAccessibility() {
        // Add ARIA labels to share buttons
        document.querySelectorAll('.ewog-share-btn').forEach(button => {
            const platform = button.dataset.platform;
            const text = button.querySelector('.ewog-share-text');
            
            if (platform && !button.getAttribute('aria-label')) {
                const label = text ? 
                    `Share on ${text.textContent}` : 
                    `Share on ${platform}`;
                button.setAttribute('aria-label', label);
            }
        });

        // Add role and ARIA attributes to container
        document.querySelectorAll('.ewog-social-share').forEach(container => {
            container.setAttribute('role', 'region');
            container.setAttribute('aria-label', 'Social sharing options');
        });
    }

    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.style.position = 'absolute';
        announcement.style.left = '-10000px';
        announcement.style.width = '1px';
        announcement.style.height = '1px';
        announcement.style.overflow = 'hidden';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
}

class EWOGAdmin {
    constructor() {
        if (document.body.classList.contains('woocommerce_page_enhanced-woo-open-graph')) {
            this.init();
        }
    }

    init() {
        this.initImageUpload();
        this.initFormValidation();
        this.initSettingsToggles();
    }

    initImageUpload() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('ewog-upload-image')) {
                e.preventDefault();
                this.openMediaUploader(e.target);
            }
            
            if (e.target.classList.contains('ewog-remove-image')) {
                e.preventDefault();
                this.removeImage(e.target);
            }
        });
    }

    openMediaUploader(button) {
        const container = button.closest('.ewog-image-field');
        const input = container.querySelector('.ewog-image-url');
        const preview = container.querySelector('.ewog-image-preview');

        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress media library is not available.');
            return;
        }

        const mediaUploader = wp.media({
            title: ewogAdmin.chooseImage,
            button: {
                text: ewogAdmin.useImage
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        mediaUploader.on('select', () => {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            input.value = attachment.url;
            this.updateImagePreview(container, attachment.url);
            
            // Trigger change event for form validation
            input.dispatchEvent(new Event('change'));
        });

        mediaUploader.open();
    }

    removeImage(button) {
        const container = button.closest('.ewog-image-field');
        const input = container.querySelector('.ewog-image-url');
        const preview = container.querySelector('.ewog-image-preview');

        input.value = '';
        if (preview) {
            preview.style.display = 'none';
        }
        
        // Trigger change event for form validation
        input.dispatchEvent(new Event('change'));
    }

    updateImagePreview(container, imageUrl) {
        let preview = container.querySelector('.ewog-image-preview');
        
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'ewog-image-preview';
            container.appendChild(preview);
        }

        preview.innerHTML = `
            <img src="${imageUrl}" style="max-width: 200px; height: auto; margin-top: 10px;" />
            <br><button type="button" class="button ewog-remove-image">Remove Image</button>
        `;
        
        preview.style.display = 'block';
    }

    initFormValidation() {
        const form = document.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const errors = this.validateForm();
            
            if (errors.length > 0) {
                e.preventDefault();
                this.showValidationErrors(errors);
            }
        });

        // Real-time validation
        form.addEventListener('input', (e) => {
            if (e.target.type === 'url') {
                this.validateUrl(e.target);
            }
        });
    }

    validateForm() {
        const errors = [];
        
        // Validate URLs
        document.querySelectorAll('input[type="url"]').forEach(input => {
            if (input.value && !this.isValidUrl(input.value)) {
                errors.push(`Invalid URL: ${input.previousElementSibling.textContent}`);
            }
        });

        // Validate Twitter username format
        const twitterInput = document.querySelector('input[name="ewog_settings[twitter_username]"]');
        if (twitterInput && twitterInput.value) {
            const username = twitterInput.value.trim();
            if (username.startsWith('@')) {
                twitterInput.value = username.substring(1);
            }
            if (!/^[A-Za-z0-9_]{1,15}$/.test(twitterInput.value)) {
                errors.push('Twitter username must be 1-15 characters (letters, numbers, underscore only)');
            }
        }

        return errors;
    }

    validateUrl(input) {
        const isValid = !input.value || this.isValidUrl(input.value);
        
        input.style.borderColor = isValid ? '' : '#dc3545';
        
        let errorMsg = input.parentNode.querySelector('.url-error');
        if (!isValid && !errorMsg) {
            errorMsg = document.createElement('span');
            errorMsg.className = 'url-error description';
            errorMsg.style.color = '#dc3545';
            errorMsg.textContent = 'Please enter a valid URL';
            input.parentNode.appendChild(errorMsg);
        } else if (isValid && errorMsg) {
            errorMsg.remove();
        }
    }

    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    showValidationErrors(errors) {
        // Remove existing error messages
        document.querySelectorAll('.ewog-validation-error').forEach(el => el.remove());

        // Create error container
        const errorContainer = document.createElement('div');
        errorContainer.className = 'ewog-message error ewog-validation-error';
        errorContainer.innerHTML = `
            <strong>Please fix the following errors:</strong>
            <ul>${errors.map(error => `<li>${error}</li>`).join('')}</ul>
        `;

        // Insert at top of form
        const form = document.querySelector('form');
        form.insertBefore(errorContainer, form.firstChild);

        // Scroll to errors
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    initSettingsToggles() {
        // Show/hide dependent settings
        this.toggleDependentSettings();

        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox') {
                this.toggleDependentSettings();
            }
        });
    }

    toggleDependentSettings() {
        // Twitter username field visibility
        const twitterEnabled = document.querySelector('input[name="ewog_settings[enable_twitter]"]');
        const twitterUsername = document.querySelector('input[name="ewog_settings[twitter_username]"]');
        
        if (twitterEnabled && twitterUsername) {
            const row = twitterUsername.closest('tr');
            row.style.display = twitterEnabled.checked ? '' : 'none';
        }

        // Facebook App ID field visibility
        const facebookEnabled = document.querySelector('input[name="ewog_settings[enable_facebook]"]');
        const facebookAppId = document.querySelector('input[name="ewog_settings[facebook_app_id]"]');
        
        if (facebookEnabled && facebookAppId) {
            const row = facebookAppId.closest('tr');
            row.style.display = facebookEnabled.checked ? '' : 'none';
        }

        // Social share settings visibility
        const shareEnabled = document.querySelector('input[name="ewog_settings[enable_social_share]"]');
        const shareSettings = [
            'share_button_style',
            'share_button_position'
        ];
        
        shareSettings.forEach(setting => {
            const field = document.querySelector(`select[name="ewog_settings[${setting}]"]`);
            if (field && shareEnabled) {
                const row = field.closest('tr');
                row.style.display = shareEnabled.checked ? '' : 'none';
            }
        });
    }
}

// Utility Functions
const EWOGUtils = {
    debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    },

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    sanitizeText(text) {
        const element = document.createElement('div');
        element.textContent = text;
        return element.innerHTML;
    },

    formatUrl(url) {
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            return 'https://' + url;
        }
        return url;
    }
};

// Performance Optimization
const EWOGPerformance = {
    init() {
        this.lazyLoadImages();
        this.preloadCriticalResources();
    },

    lazyLoadImages() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    },

    preloadCriticalResources() {
        // Preload critical CSS and JS for better performance
        const criticalResources = [
            { rel: 'preload', href: '/wp-content/plugins/enhanced-woo-open-graph/assets/css/social-share.css', as: 'style' }
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            Object.assign(link, resource);
            document.head.appendChild(link);
        });
    }
};

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new EWOGSocialShare();
    new EWOGAdmin();
    EWOGPerformance.init();
});

// Handle page visibility changes for analytics
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Page became visible - could track engagement
        console.log('EWOG: Page became visible');
    }
});

// Export for potential external use
window.EWOG = {
    SocialShare: EWOGSocialShare,
    Admin: EWOGAdmin,
    Utils: EWOGUtils,
    Performance: EWOGPerformance
};