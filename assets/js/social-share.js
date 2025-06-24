(function($) {
    'use strict';
    
    /**
     * Woo Open Graph Social Share Handler
     * Fixes broken share URLs and implements working copy functionality
     */
    class WOGSocialShare {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initializeButtons();
        }
        
        initializeButtons() {
            // Ensure all share buttons have proper event handlers
            $('.wog-share-btn').each((index, button) => {
                const $button = $(button);
                if (!$button.hasClass('wog-initialized')) {
                    $button.addClass('wog-initialized');
                }
            });
        }
        
        bindEvents() {
            // Handle social share clicks (not copy button)
            $(document).on('click', '.wog-share-btn:not(.wog-share-copy)', this.handleSocialShare.bind(this));
            
            // Handle copy button clicks
            $(document).on('click', '.wog-share-copy', this.handleCopyClick.bind(this));
            
            // Prevent form submission on copy button
            $(document).on('submit', 'form', function(e) {
                if ($(e.target).hasClass('wog-share-copy')) {
                    e.preventDefault();
                }
            });
        }
        
        handleSocialShare(e) {
            const $button = $(e.currentTarget);
            const platform = $button.data('platform');
            const url = $button.attr('href');
            
            // Get product ID from parent container
            const productId = $button.closest('.wog-social-share').data('product-id') || 0;
            
            // Validate URL
            if (!url || url === '#' || url === 'javascript:void(0)') {
                console.warn('Invalid share URL:', url);
                return false;
            }
            
            // Track the share
            this.trackShare(platform, productId, url);
            
            // Open share window
            e.preventDefault();
            this.openShareWindow(url, platform);
            
            return false;
        }
        
        handleCopyClick(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const url = $button.data('url');
            
            if (!url) {
                console.error('No URL found for copy button');
                return false;
            }
            
            // Get product ID from parent container
            const productId = $button.closest('.wog-social-share').data('product-id') || 0;
            
            // Copy to clipboard
            this.copyToClipboard(url, $button);
            
            // Track the copy action
            this.trackShare('copy', productId, url);
            
            return false;
        }
        
        async copyToClipboard(text, $button) {
            const $textSpan = $button.find('.wog-share-text');
            const originalText = $textSpan.text();
            const copiedText = wogShare?.copied || 'Copied!';
            const failedText = wogShare?.copyFailed || 'Copy failed';
            
            try {
                // Try modern clipboard API first
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    // Fallback for older browsers or non-HTTPS
                    this.fallbackCopyToClipboard(text);
                }
                
                // Show success feedback
                this.showCopyFeedback($button, $textSpan, originalText, copiedText, true);
                
            } catch (err) {
                console.error('Failed to copy text:', err);
                
                // Try fallback method
                try {
                    this.fallbackCopyToClipboard(text);
                    this.showCopyFeedback($button, $textSpan, originalText, copiedText, true);
                } catch (fallbackErr) {
                    console.error('Fallback copy also failed:', fallbackErr);
                    this.showCopyFeedback($button, $textSpan, originalText, failedText, false);
                }
            }
        }
        
        fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (!successful) {
                    throw new Error('Copy command failed');
                }
            } finally {
                document.body.removeChild(textArea);
            }
        }
        
        showCopyFeedback($button, $textSpan, originalText, feedbackText, success) {
            // Add/remove CSS classes
            $button.toggleClass('copied', success);
            $button.toggleClass('copy-failed', !success);
            
            // Update text
            $textSpan.text(feedbackText);
            
            // Reset after 2 seconds
            setTimeout(() => {
                $button.removeClass('copied copy-failed');
                $textSpan.text(originalText);
            }, 2000);
        }
        
        openShareWindow(url, platform) {
            // Window dimensions based on platform
            const dimensions = {
                facebook: { width: 600, height: 500 },
                twitter: { width: 600, height: 400 },
                linkedin: { width: 600, height: 500 },
                pinterest: { width: 600, height: 600 },
                default: { width: 600, height: 500 }
            };
            
            const size = dimensions[platform] || dimensions.default;
            const left = Math.round((window.screen.width / 2) - (size.width / 2));
            const top = Math.round((window.screen.height / 2) - (size.height / 2));
            
            const windowFeatures = [
                `width=${size.width}`,
                `height=${size.height}`,
                `left=${left}`,
                `top=${top}`,
                'resizable=yes',
                'scrollbars=yes',
                'status=yes',
                'menubar=no',
                'toolbar=no',
                'location=no'
            ].join(',');
            
            const shareWindow = window.open(url, `${platform}_share_${Date.now()}`, windowFeatures);
            
            // Focus the new window
            if (shareWindow) {
                shareWindow.focus();
            }
            
            return shareWindow;
        }
        
        trackShare(platform, productId, url) {
            // Only track if AJAX URL and nonce are available
            if (!wogShare?.ajaxUrl || !wogShare?.nonce) {
                if (wogShare?.debug) {
                    console.log('Share tracking skipped - missing AJAX URL or nonce');
                }
                return;
            }
            
            $.ajax({
                url: wogShare.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wog_track_share',
                    platform: platform,
                    product_id: productId,
                    url: url,
                    nonce: wogShare.nonce
                },
                timeout: 5000,
                success: function(response) {
                    if (wogShare.debug) {
                        console.log('Share tracked successfully:', {
                            platform: platform,
                            productId: productId,
                            response: response
                        });
                    }
                },
                error: function(xhr, status, error) {
                    if (wogShare.debug) {
                        console.warn('Share tracking failed:', {
                            platform: platform,
                            productId: productId,
                            status: status,
                            error: error
                        });
                    }
                }
            });
        }
        
        // Public method to manually trigger share
        share(platform, url, title, description) {
            const shareUrls = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
                twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
                linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
                pinterest: `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(url)}&description=${encodeURIComponent(title)}`,
                whatsapp: `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`
            };
            
            if (shareUrls[platform]) {
                this.openShareWindow(shareUrls[platform], platform);
            }
        }
        
        // Public method to copy URL
        copyUrl(url) {
            return this.copyToClipboard(url, $('<div><span class="wog-share-text">Copy</span></div>'));
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Create global instance
        window.WOGSocialShare = new WOGSocialShare();
        
        // Reinitialize on AJAX content updates (for themes that load content via AJAX)
        $(document).on('updated_wc_div', function() {
            if (window.WOGSocialShare) {
                window.WOGSocialShare.initializeButtons();
            }
        });
        
        // Debug mode logging
        if (wogShare?.debug) {
            console.log('WOG Social Share initialized', {
                buttonsFound: $('.wog-share-btn').length,
                ajaxUrl: wogShare.ajaxUrl,
                hasNonce: !!wogShare.nonce
            });
        }
    });
    
    // Expose methods for external access
    window.wogShare = window.wogShare || {};
    window.wogShare.copyLink = function(url) {
        if (window.WOGSocialShare) {
            return window.WOGSocialShare.copyUrl(url);
        }
    };
    
    window.wogShare.openShare = function(platform, url, title, description) {
        if (window.WOGSocialShare) {
            return window.WOGSocialShare.share(platform, url, title, description);
        }
    };
    
})(jQuery);