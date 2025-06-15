/**
 * Enhanced Woo Open Graph - Fixed Copy Button JavaScript
 * Complete working solution for copy functionality
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
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.attachEventListeners());
        } else {
            this.attachEventListeners();
        }
    }

    attachEventListeners() {
        // Copy link functionality - using event delegation for dynamic content
        document.addEventListener('click', (e) => {
            const copyButton = e.target.closest('.ewog-share-copy');
            if (copyButton) {
                e.preventDefault();
                this.copyLink(copyButton);
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
        // Get URL from data attribute or current page URL
        const url = button.dataset.url || window.location.href;
        
        if (!url) {
            console.warn('EWOG: No URL found for copy button');
            return;
        }

        // Show loading state
        this.showButtonLoading(button, true);

        try {
            // Try modern clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
                console.log('EWOG: URL copied using Clipboard API');
            } else {
                // Fallback for older browsers or non-HTTPS
                this.fallbackCopyText(url);
                console.log('EWOG: URL copied using fallback method');
            }

            this.showCopySuccess(button);
            this.announceToScreenReader(this.getTranslation('copied', 'Link copied!'));
            
        } catch (err) {
            console.error('EWOG: Failed to copy link:', err);
            this.showCopyError(button);
            this.announceToScreenReader(this.getTranslation('copyFailed', 'Failed to copy link'));
        } finally {
            this.showButtonLoading(button, false);
        }
    }

    fallbackCopyText(text) {
        // Create temporary textarea
        const textArea = document.createElement('textarea');
        textArea.value = text;
        
        // Style to make it invisible
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        textArea.style.opacity = '0';
        textArea.style.pointerEvents = 'none';
        textArea.setAttribute('readonly', '');
        textArea.setAttribute('tabindex', '-1');
        
        document.body.appendChild(textArea);
        
        try {
            // Focus and select
            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, 99999); // For mobile devices
            
            // Execute copy command
            const successful = document.execCommand('copy');
            
            if (!successful) {
                throw new Error('execCommand copy failed');
            }
        } finally {
            document.body.removeChild(textArea);
        }
    }

    showButtonLoading(button, isLoading) {
        if (isLoading) {
            button.classList.add('loading');
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }

    showCopySuccess(button) {
        const originalText = button.querySelector('.ewog-share-text');
        const originalContent = originalText ? originalText.textContent : '';
        const originalIcon = button.innerHTML;
        
        // Add success class
        button.classList.add('copied');
        
        // Update button text and icon
        if (originalText) {
            originalText.textContent = this.getTranslation('copied', 'Copied!');
        }
        
        // Add checkmark icon
        const icon = button.querySelector('svg');
        if (icon) {
            icon.innerHTML = '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>';
        }

        // Reset after 2 seconds
        setTimeout(() => {
            button.classList.remove('copied');
            if (originalText) {
                originalText.textContent = originalContent;
            }
            button.innerHTML = originalIcon;
        }, 2000);
    }

    showCopyError(button) {
        // Show error state
        button.style.background = '#dc3545';
        button.style.borderColor = '#dc3545';
        button.style.color = 'white';

        const originalText = button.querySelector('.ewog-share-text');
        if (originalText) {
            const originalContent = originalText.textContent;
            originalText.textContent = this.getTranslation('copyFailed', 'Failed');
            
            setTimeout(() => {
                originalText.textContent = originalContent;
            }, 2000);
        }

        // Reset styles after 2 seconds
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
        console.log(`EWOG: Shared on ${platform}:`, window.location.href);
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

        // Try to get from add to cart button
        const addToCartBtn = document.querySelector('.single_add_to_cart_button');
        if (addToCartBtn) {
            const productId = addToCartBtn.getAttribute('data-product_id') || 
                             addToCartBtn.getAttribute('value');
            if (productId) {
                return productId;
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
                let label;
                if (platform === 'copy') {
                    label = this.getTranslation('copyLink', 'Copy link');
                } else {
                    label = text ? 
                        `Share on ${text.textContent}` : 
                        `Share on ${platform}`;
                }
                button.setAttribute('aria-label', label);
            }
        });

        // Add role and ARIA attributes to container
        document.querySelectorAll('.ewog-social-share').forEach(container => {
            container.setAttribute('role', 'region');
            container.setAttribute('aria-label', this.getTranslation('shareRegion', 'Social sharing options'));
        });
    }

    announceToScreenReader(message) {
        // Create announcement element
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only ewog-sr-announcement';
        announcement.style.cssText = `
            position: absolute !important;
            left: -10000px !important;
            width: 1px !important;
            height: 1px !important;
            overflow: hidden !important;
            clip: rect(1px, 1px, 1px, 1px) !important;
            white-space: nowrap !important;
        `;
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        // Remove after announcement
        setTimeout(() => {
            if (announcement.parentNode) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }

    getTranslation(key, fallback) {
        // Check if translations are available
        if (typeof ewogShare !== 'undefined' && ewogShare[key]) {
            return ewogShare[key];
        }
        return fallback;
    }
}

// Enhanced Admin functionality
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
        this.initSitemapManagement();
    }

    initSitemapManagement() {
        // Enhanced sitemap management with copy functionality
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('ewog-generate-sitemap')) {
                e.preventDefault();
                this.generateSitemap(e.target);
            }
            
            if (e.target.classList.contains('ewog-test-sitemap')) {
                e.preventDefault();
                this.testSitemap(e.target);
            }
            
            if (e.target.classList.contains('ewog-debug-sitemap')) {
                e.preventDefault();
                this.debugSitemap(e.target);
            }
            
            if (e.target.classList.contains('ewog-force-regenerate')) {
                e.preventDefault();
                this.forceRegenerate(e.target);
            }
        });
    }

    generateSitemap(button) {
        this.performAjaxAction(button, 'ewog_generate_sitemap', 'Generating...');
    }

    testSitemap(button) {
        this.performAjaxAction(button, 'ewog_test_sitemap', 'Testing...');
    }

    debugSitemap(button) {
        this.performAjaxAction(button, 'ewog_debug_sitemap', 'Debugging...');
    }

    forceRegenerate(button) {
        if (!confirm('This will flush rewrite rules and regenerate all sitemaps. Continue?')) {
            return;
        }
        this.performAjaxAction(button, 'ewog_force_regenerate', 'Regenerating...');
    }

    performAjaxAction(button, action, loadingText) {
        const resultsDiv = document.querySelector('.ewog-sitemap-results');
        const originalText = button.textContent;
        
        button.disabled = true;
        button.textContent = loadingText;
        
        fetch(ewogAdmin.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: action,
                nonce: ewogAdmin.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = data.data.message;
                if (data.data.debug_html) {
                    message += data.data.debug_html;
                }
                resultsDiv.innerHTML = `<div class="notice notice-success"><p>${message}</p></div>`;
            } else {
                resultsDiv.innerHTML = `<div class="notice notice-error"><p>${data.data.message || 'Action failed'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('EWOG Admin Error:', error);
            resultsDiv.innerHTML = '<div class="notice notice-error"><p>Request failed</p></div>';
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = originalText;
        });
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

        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress media library is not available.');
            return;
        }

        const mediaUploader = wp.media({
            title: ewogAdmin.chooseImage || 'Choose Image',
            button: {
                text: ewogAdmin.useImage || 'Use this Image'
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
            
            // Trigger change event
            input.dispatchEvent(new Event('change', { bubbles: true }));
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
        
        // Trigger change event
        input.dispatchEvent(new Event('change', { bubbles: true }));
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
                const label = input.closest('tr')?.querySelector('th')?.textContent || 'URL field';
                errors.push(`Invalid URL: ${label}`);
            }
        });

        // Validate Twitter username
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
        this.toggleDependentSettings();

        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox') {
                this.toggleDependentSettings();
            }
        });
    }

    toggleDependentSettings() {
        // Twitter settings
        const twitterEnabled = document.querySelector('input[name="ewog_settings[enable_twitter]"]');
        const twitterUsername = document.querySelector('input[name="ewog_settings[twitter_username]"]');
        
        if (twitterEnabled && twitterUsername) {
            const row = twitterUsername.closest('tr');
            if (row) {
                row.style.display = twitterEnabled.checked ? '' : 'none';
            }
        }

        // Facebook settings
        const facebookEnabled = document.querySelector('input[name="ewog_settings[enable_facebook]"]');
        const facebookAppId = document.querySelector('input[name="ewog_settings[facebook_app_id]"]');
        
        if (facebookEnabled && facebookAppId) {
            const row = facebookAppId.closest('tr');
            if (row) {
                row.style.display = facebookEnabled.checked ? '' : 'none';
            }
        }

        // Social share settings
        const shareEnabled = document.querySelector('input[name="ewog_settings[enable_social_share]"]');
        const shareSettings = ['share_button_style', 'share_button_position'];
        
        shareSettings.forEach(setting => {
            const field = document.querySelector(`select[name="ewog_settings[${setting}]"]`);
            if (field && shareEnabled) {
                const row = field.closest('tr');
                if (row) {
                    row.style.display = shareEnabled.checked ? '' : 'none';
                }
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

// Initialize everything when DOM is ready
function initEWOG() {
    new EWOGSocialShare();
    new EWOGAdmin();
    
    // Debug info
    console.log('EWOG: JavaScript initialized successfully');
}

// Multiple initialization methods to ensure it runs
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEWOG);
} else {
    initEWOG();
}

// Fallback initialization
window.addEventListener('load', () => {
    if (!window.ewogInitialized) {
        initEWOG();
        window.ewogInitialized = true;
    }
});

// Export for external use
window.EWOG = {
    SocialShare: EWOGSocialShare,
    Admin: EWOGAdmin,
    Utils: EWOGUtils,
    init: initEWOG
};