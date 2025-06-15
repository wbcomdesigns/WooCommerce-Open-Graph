/**
 * Enhanced Woo Open Graph - Admin JavaScript (Improved)
 * File: assets/js/admin.js
 * 
 * Enhanced version maintaining existing jQuery structure
 */

(function($) {
    'use strict';

    /**
     * Main admin functionality
     */
    const ewogAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initPlatformCards();
            this.initCollapsibles();
            this.initAccessibility();
        },
        
        bindEvents: function() {
            // Platform card interactions
            $(document).on('click', '.ewog-platform-card', this.handlePlatformClick);
            
            // Quick action buttons
            $(document).on('click', '.ewog-quick-test', this.handleQuickTest);
            $(document).on('click', '.ewog-clear-cache', this.handleClearCache);
            
            // Tool buttons
            $(document).on('click', '.ewog-test-facebook', this.handleFacebookTest);
            $(document).on('click', '.ewog-test-twitter', this.handleTwitterTest);
            $(document).on('click', '.ewog-test-schema', this.handleSchemaTest);
            
            // Sitemap tools
            $(document).on('click', '.ewog-generate-sitemap', this.handleGenerateSitemap);
            $(document).on('click', '.ewog-test-sitemap', this.handleTestSitemap);
            
            // Notice dismiss
            $(document).on('click', '.ewog-message .notice-dismiss', this.dismissNotice);
            
            // Form improvements
            $('.ewog-settings-form').on('submit', this.handleFormSubmit);
            
            // Enhanced toggle interactions
            $(document).on('change', '.ewog-toggle-input', this.handleToggleChange);
        },
        
        initPlatformCards: function() {
            $('.ewog-platform-card').each(function() {
                const $card = $(this);
                const $checkbox = $card.find('input[type="checkbox"]');
                
                if ($checkbox.prop('checked')) {
                    $card.addClass('active');
                }
                
                // Add ARIA attributes
                $card.attr({
                    'role': 'button',
                    'tabindex': '0',
                    'aria-pressed': $checkbox.prop('checked')
                });
            });
        },
        
        initCollapsibles: function() {
            $('.ewog-collapsible summary').on('click', function(e) {
                const $details = $(this).closest('details');
                const $icon = $(this).find('.dashicons');
                
                setTimeout(function() {
                    if ($details.prop('open')) {
                        $icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    } else {
                        $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                    }
                }, 10);
            });
        },
        
        initAccessibility: function() {
            // Keyboard navigation for platform cards
            $(document).on('keydown', '.ewog-platform-card', function(e) {
                if (e.which === 13 || e.which === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
            
            // Escape to dismiss messages
            $(document).on('keydown', function(e) {
                if (e.which === 27) { // Escape
                    $('.ewog-message .notice-dismiss').trigger('click');
                }
            });
            
            // Focus management
            $('body').on('mousedown', function() {
                $('body').removeClass('ewog-keyboard-nav');
            }).on('keydown', function(e) {
                if (e.which === 9) { // Tab
                    $('body').addClass('ewog-keyboard-nav');
                }
            });
        },
        
        handlePlatformClick: function(e) {
            e.preventDefault();
            
            const $card = $(this);
            const $checkbox = $card.find('input[type="checkbox"]');
            const isChecked = $checkbox.prop('checked');
            
            // Toggle checkbox and card state
            $checkbox.prop('checked', !isChecked);
            $card.toggleClass('active', !isChecked);
            $card.attr('aria-pressed', !isChecked);
            
            // Visual feedback
            $card.addClass('ewog-clicked');
            setTimeout(function() {
                $card.removeClass('ewog-clicked');
            }, 200);
            
            // Announce to screen readers
            const platform = $card.find('strong').text();
            const status = !isChecked ? 'enabled' : 'disabled';
            ewogAdmin.announceToScreenReader(`${platform} ${status}`);
        },
        
        handleQuickTest: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            ewogAdmin.setButtonLoading($button, ewogAdmin.getLocalizedText('testing'));
            
            $.ajax({
                url: ewogAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ewog_quick_test',
                    nonce: ewogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ewogAdmin.showMessage('success', response.data.message);
                        ewogAdmin.displayTestResults(response.data);
                    } else {
                        ewogAdmin.showMessage('error', response.data.message || 'Test failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EWOG Quick Test Error:', error);
                    ewogAdmin.showMessage('error', 'Test failed: ' + error);
                },
                complete: function() {
                    ewogAdmin.setButtonNormal($button, originalText);
                }
            });
        },
        
        handleClearCache: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            ewogAdmin.setButtonLoading($button, ewogAdmin.getLocalizedText('clearing'));
            
            $.ajax({
                url: ewogAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ewog_clear_cache',
                    nonce: ewogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ewogAdmin.showMessage('success', response.data.message);
                    } else {
                        ewogAdmin.showMessage('error', response.data.message || 'Clear cache failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EWOG Clear Cache Error:', error);
                    ewogAdmin.showMessage('error', 'Clear cache failed: ' + error);
                },
                complete: function() {
                    ewogAdmin.setButtonNormal($button, originalText);
                }
            });
        },
        
        handleFacebookTest: function(e) {
            e.preventDefault();
            ewogAdmin.openValidationWindow('https://developers.facebook.com/tools/debug/', 'Facebook Debugger');
        },
        
        handleTwitterTest: function(e) {
            e.preventDefault();
            ewogAdmin.openValidationWindow('https://cards-dev.twitter.com/validator', 'Twitter Card Validator');
        },
        
        handleSchemaTest: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            ewogAdmin.setButtonLoading($button, ewogAdmin.getLocalizedText('testing'));
            
            $.ajax({
                url: ewogAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ewog_validate_schema',
                    nonce: ewogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ewogAdmin.openValidationWindow(response.data.test_url, 'Google Rich Results Test');
                        ewogAdmin.showMessage('info', response.data.message);
                    } else {
                        ewogAdmin.showMessage('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EWOG Schema Test Error:', error);
                    ewogAdmin.showMessage('error', 'Schema validation failed: ' + error);
                },
                complete: function() {
                    ewogAdmin.setButtonNormal($button, originalText);
                }
            });
        },
        
        handleGenerateSitemap: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            ewogAdmin.setButtonLoading($button, ewogAdmin.getLocalizedText('generating'));
            
            $.ajax({
                url: ewogAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ewog_generate_sitemap',
                    nonce: ewogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ewogAdmin.showMessage('success', response.data.message);
                        ewogAdmin.updateSitemapInfo(response.data);
                    } else {
                        ewogAdmin.showMessage('error', response.data.message || 'Sitemap generation failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EWOG Generate Sitemap Error:', error);
                    ewogAdmin.showMessage('error', 'Sitemap generation failed: ' + error);
                },
                complete: function() {
                    ewogAdmin.setButtonNormal($button, originalText);
                }
            });
        },
        
        handleTestSitemap: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            ewogAdmin.setButtonLoading($button, ewogAdmin.getLocalizedText('testing'));
            
            $.ajax({
                url: ewogAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ewog_test_sitemap',
                    nonce: ewogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ewogAdmin.showMessage('success', response.data.message);
                    } else {
                        ewogAdmin.showMessage('error', response.data.message || 'Sitemap test failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EWOG Test Sitemap Error:', error);
                    ewogAdmin.showMessage('error', 'Sitemap test failed: ' + error);
                },
                complete: function() {
                    ewogAdmin.setButtonNormal($button, originalText);
                }
            });
        },
        
        handleFormSubmit: function(e) {
            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');
            const originalValue = $submitButton.val();
            
            // Validate form
            if (!ewogAdmin.validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Show saving state
            $submitButton.val(ewogAdmin.getLocalizedText('saving')).prop('disabled', true);
            
            // Re-enable after delay (form will reload page normally)
            setTimeout(function() {
                $submitButton.val(originalValue).prop('disabled', false);
            }, 3000);
        },
        
        handleToggleChange: function(e) {
            const $toggle = $(this);
            const $wrapper = $toggle.closest('.ewog-toggle-wrapper');
            
            if ($toggle.prop('checked')) {
                $wrapper.addClass('active');
            } else {
                $wrapper.removeClass('active');
            }
        },
        
        validateForm: function($form) {
            let isValid = true;
            const errors = [];
            
            // Check if at least one feature is enabled
            const checkedFeatures = $form.find('.ewog-toggle-input:checked, .ewog-platform-card input:checked').length;
            
            if (checkedFeatures === 0) {
                errors.push('Please enable at least one feature or platform.');
                isValid = false;
            }
            
            if (!isValid) {
                ewogAdmin.showMessage('error', 'Please fix the following errors:<br>' + errors.join('<br>'));
            }
            
            return isValid;
        },
        
        // Utility functions
        setButtonLoading: function($button, text) {
            $button.addClass('ewog-loading')
                   .prop('disabled', true)
                   .data('original-text', $button.text())
                   .text(text || 'Loading...');
        },
        
        setButtonNormal: function($button, text) {
            $button.removeClass('ewog-loading')
                   .prop('disabled', false)
                   .text(text || $button.data('original-text') || 'Button');
        },
        
        showMessage: function(type, message) {
            // Remove existing messages
            $('.ewog-message').fadeOut(300, function() {
                $(this).remove();
            });
            
            // Map type to WordPress notice classes
            const noticeClass = type === 'success' ? 'notice-success' : 
                               type === 'error' ? 'notice-error' : 
                               type === 'warning' ? 'notice-warning' : 
                               'notice-info';
            
            // Create message HTML
            const messageHtml = `
                <div class="notice ${noticeClass} is-dismissible ewog-message">
                    <p>${ewogAdmin.escapeHtml(message)}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            // Insert message
            $('.ewog-admin-wrap h1').after(messageHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.ewog-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to message smoothly
            $('html, body').animate({
                scrollTop: $('.ewog-message').offset().top - 50
            }, 300);
        },
        
        displayTestResults: function(data) {
            const resultsHtml = `
                <div class="ewog-test-results-content">
                    <h4>Test Results</h4>
                    <p><strong>Test Product:</strong> <a href="${data.product_url}" target="_blank">View Product Page</a></p>
                    <div class="ewog-test-links">
                        <a href="${data.facebook_test}" target="_blank" class="button button-primary">
                            <span class="dashicons dashicons-facebook"></span> Test on Facebook
                        </a>
                        <a href="${data.twitter_test}" target="_blank" class="button button-primary">
                            <span class="dashicons dashicons-twitter"></span> Test on Twitter
                        </a>
                        <a href="${data.schema_test}" target="_blank" class="button button-primary">
                            <span class="dashicons dashicons-search"></span> Test Schema
                        </a>
                    </div>
                </div>
            `;
            
            $('.ewog-test-results').html(resultsHtml).addClass('show');
        },
        
        updateSitemapInfo: function(data) {
            if (data.timestamp) {
                const timestampText = `<strong>Last Generated:</strong><br>${data.timestamp}`;
                $('.ewog-sitemap-status p:first').html(timestampText);
            }
        },
        
        openValidationWindow: function(url, title) {
            const width = 1000;
            const height = 700;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;
            
            const features = `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no`;
            
            window.open(url, title.replace(/\s+/g, '_'), features);
            
            ewogAdmin.announceToScreenReader(`Opening ${title} in new window`);
        },
        
        dismissNotice: function(e) {
            e.preventDefault();
            $(this).closest('.ewog-message').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },
        
        announceToScreenReader: function(message) {
            const $announcement = $('<div>')
                .attr({
                    'aria-live': 'polite',
                    'aria-atomic': 'true'
                })
                .addClass('screen-reader-text')
                .text(message);
            
            $('body').append($announcement);
            
            setTimeout(function() {
                $announcement.remove();
            }, 1000);
        },
        
        getLocalizedText: function(key) {
            const texts = {
                testing: 'Testing...',
                generating: 'Generating...',
                clearing: 'Clearing...',
                saving: 'Saving...'
            };
            
            // Try to get from localized vars if available
            if (typeof ewogAdmin.i18n !== 'undefined' && ewogAdmin.i18n[key]) {
                return ewogAdmin.i18n[key];
            }
            
            return texts[key] || key;
        },
        
        // Properties for AJAX (will be set by PHP)
        ajaxUrl: ajaxurl || '/wp-admin/admin-ajax.php',
        nonce: '',
        i18n: {}
    };
    
    // Enhanced error handling
    const errorHandler = {
        init: function() {
            // Global AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
                    console.error('EWOG AJAX Error:', {
                        url: settings.url,
                        status: xhr.status,
                        error: thrownError,
                        response: xhr.responseText
                    });
                    
                    // Show user-friendly error based on status
                    let errorMessage = 'An error occurred. Please try again.';
                    
                    if (xhr.status === 0) {
                        errorMessage = 'Network error. Please check your connection.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page.';
                    }
                    
                    ewogAdmin.showMessage('error', errorMessage);
                }
            });
            
            // Global JavaScript error handler
            window.addEventListener('error', function(e) {
                if (e.filename && e.filename.includes('admin.js')) {
                    console.error('EWOG JavaScript Error:', e.error);
                }
            });
        }
    };
    
    // Performance monitoring
    const performanceMonitor = {
        init: function() {
            if (window.performance && window.performance.timing) {
                $(window).on('load', function() {
                    setTimeout(function() {
                        const perfData = window.performance.timing;
                        const loadTime = perfData.loadEventEnd - perfData.navigationStart;
                        
                        if (loadTime > 3000) {
                            console.warn('EWOG Admin: Page load time is high:', loadTime + 'ms');
                        }
                    }, 1000);
                });
            }
        }
    };
    
    // Responsive handling
    const responsiveHandler = {
        init: function() {
            this.handleResponsiveTables();
            $(window).on('resize', this.debounce(this.handleResponsiveTables, 250));
        },
        
        handleResponsiveTables: function() {
            $('.form-table').each(function() {
                const $table = $(this);
                if ($table.width() > $table.parent().width()) {
                    $table.addClass('ewog-responsive-table');
                } else {
                    $table.removeClass('ewog-responsive-table');
                }
            });
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Set AJAX URL and nonce if provided by localized script
        if (typeof ewogAdminVars !== 'undefined') {
            ewogAdmin.ajaxUrl = ewogAdminVars.ajaxUrl;
            ewogAdmin.nonce = ewogAdminVars.nonce;
            ewogAdmin.i18n = ewogAdminVars;
        }
        
        // Initialize all components
        ewogAdmin.init();
        errorHandler.init();
        performanceMonitor.init();
        responsiveHandler.init();
        
        // Add enhanced class for styling hooks
        $('body').addClass('ewog-admin-enhanced');
        
        // Smooth scrolling for internal links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 50
                }, 300);
            }
        });
        
        console.log('EWOG Admin Enhanced: Initialized successfully');
    });
    
    // Make admin object available globally
    window.ewogAdmin = ewogAdmin;

})(jQuery);