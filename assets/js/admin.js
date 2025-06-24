/**
 * Enhanced Woo Open Graph - Final Clean Social Share JavaScript
 * Only the essential functionality with working copy button
 */

(function() {
    'use strict';
    
    class EWOGSocialShare {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
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
            // Copy link functionality
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
        }
        
        async copyLink(button) {
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
                } else {
                    // Fallback for older browsers or non-HTTPS
                    this.fallbackCopyText(url);
                }
                
                this.showCopySuccess(button);
                
            } catch (err) {
                console.error('EWOG: Failed to copy link:', err);
                this.showCopyError(button);
            } finally {
                this.showButtonLoading(button, false);
            }
        }
        
        fallbackCopyText(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            textArea.style.opacity = '0';
            textArea.setAttribute('readonly', '');
            textArea.setAttribute('tabindex', '-1');
            
            document.body.appendChild(textArea);
            
            try {
                textArea.focus();
                textArea.select();
                textArea.setSelectionRange(0, 99999);
                
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
            } else {
                button.classList.remove('loading');
                button.disabled = false;
            }
        }
        
        showCopySuccess(button) {
            const originalText = button.querySelector('.ewog-share-text');
            const originalContent = originalText ? originalText.textContent : '';
            
            // Add success class
            button.classList.add('copied');
            
            // Update button text
            if (originalText) {
                originalText.textContent = this.getTranslation('copied', 'Copied!');
            }
            
            // Reset after 2 seconds
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
        
        getTranslation(key, fallback) {
            // Check if translations are available
            if (typeof ewogShare !== 'undefined' && ewogShare[key]) {
                return ewogShare[key];
            }
            return fallback;
        }
    }
    
    // Initialize when DOM is ready
    function init() {
        new EWOGSocialShare();
        console.log('EWOG: Social Share initialized');
    }
    
    // Multiple initialization methods to ensure it runs
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Fallback initialization
    window.addEventListener('load', () => {
        if (!window.ewogInitialized) {
            init();
            window.ewogInitialized = true;
        }
    });
    
})();