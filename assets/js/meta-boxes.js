/**
 * Enhanced WooCommerce Open Graph - Meta Boxes JavaScript
 * Provides interactive functionality for the admin meta boxes
 */

class EWOGMetaBoxes {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initCharacterCounters();
        this.initImageUploader();
        this.initSocialPreview();
        this.initCustomTags();
    }

    bindEvents() {
        // Character counter updates
        document.addEventListener('input', (e) => {
            if (e.target.matches('.ewog-og-title, .ewog-og-description')) {
                this.updateCharacterCount(e.target);
            }
        });

        // Preview tab switching
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-preview-tab')) {
                e.preventDefault();
                this.switchPreviewTab(e.target);
            }
        });

        // Preview refresh
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-refresh-preview')) {
                e.preventDefault();
                this.refreshPreview();
            }
        });

        // Image validation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-validate-image')) {
                e.preventDefault();
                this.validateImage();
            }
        });

        // Test sharing
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-test-sharing')) {
                e.preventDefault();
                this.testSharing();
            }
        });

        // Auto-refresh preview when fields change
        let previewTimeout;
        document.addEventListener('input', (e) => {
            if (e.target.matches('#ewog_og_title, #ewog_og_description')) {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(() => {
                    this.refreshPreview();
                }, 1000);
            }
        });
    }

    initCharacterCounters() {
        document.querySelectorAll('.ewog-og-title, .ewog-og-description').forEach(field => {
            this.updateCharacterCount(field);
        });
    }

    updateCharacterCount(field) {
        const maxLength = parseInt(field.getAttribute('maxlength')) || 0;
        const currentLength = field.value.length;
        const counter = document.querySelector(`[data-target="${field.id}"]`);
        
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            
            // Add warning class if approaching limit
            if (currentLength > maxLength * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }
        }
    }

    initImageUploader() {
        // Upload image button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-upload-image')) {
                e.preventDefault();
                this.openMediaUploader();
            }
        });

        // Remove image button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-remove-image')) {
                e.preventDefault();
                this.removeImage();
            }
        });
    }

    openMediaUploader() {
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress media library is not available.');
            return;
        }

        const mediaUploader = wp.media({
            title: ewogMetaBoxes.strings.chooseImage,
            button: {
                text: ewogMetaBoxes.strings.useImage
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        mediaUploader.on('select', () => {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update hidden field
            document.getElementById('ewog_og_image').value = attachment.url;
            
            // Update preview
            this.updateImagePreview(attachment.url);
            
            // Show/hide buttons
            document.querySelector('.ewog-remove-image').style.display = 'inline-block';
            document.querySelector('.ewog-validate-image').style.display = 'inline-block';
            
            // Auto-validate the new image
            this.validateImage();
            
            // Refresh social preview
            this.refreshPreview();
        });

        mediaUploader.open();
    }

    removeImage() {
        // Clear hidden field
        document.getElementById('ewog_og_image').value = '';
        
        // Clear preview
        const previewContainer = document.querySelector('.ewog-image-preview');
        previewContainer.innerHTML = '';
        
        // Hide buttons
        document.querySelector('.ewog-remove-image').style.display = 'none';
        document.querySelector('.ewog-validate-image').style.display = 'none';
        
        // Clear validation results
        document.querySelector('.ewog-image-validation-result').innerHTML = '';
        
        // Refresh social preview
        this.refreshPreview();
    }

    updateImagePreview(imageUrl) {
        const previewContainer = document.querySelector('.ewog-image-preview');
        previewContainer.innerHTML = `<img src="${imageUrl}" style="max-width: 300px; height: auto;" />`;
    }

    validateImage() {
        const imageUrl = document.getElementById('ewog_og_image').value;
        
        if (!imageUrl) {
            return;
        }

        const button = document.querySelector('.ewog-validate-image');
        const resultContainer = document.querySelector('.ewog-image-validation-result');
        
        // Show loading state
        button.textContent = ewogMetaBoxes.strings.validating;
        button.disabled = true;
        
        // Perform AJAX validation
        fetch(ewogMetaBoxes.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'ewog_validate_og_image',
                nonce: ewogMetaBoxes.nonce,
                image_url: imageUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayValidationResult(data.data, resultContainer);
            } else {
                resultContainer.innerHTML = `<div class="notice notice-error"><p>Validation failed: ${data.data}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Validation error:', error);
            resultContainer.innerHTML = '<div class="notice notice-error"><p>Validation request failed</p></div>';
        })
        .finally(() => {
            button.textContent = 'Validate Image';
            button.disabled = false;
        });
    }

    displayValidationResult(result, container) {
        let html = '';
        
        if (result.valid) {
            html += `<div class="notice notice-success"><p>✓ ${result.message}</p></div>`;
        } else {
            html += `<div class="notice notice-error"><p>⚠ ${result.message}</p></div>`;
        }
        
        // Add details
        if (result.details) {
            html += '<div class="ewog-validation-details">';
            html += `<p><strong>Type:</strong> ${result.details.content_type}</p>`;
            html += `<p><strong>Size:</strong> ${result.details.file_size}</p>`;
            html += `<p><strong>Dimensions:</strong> ${result.details.dimensions}</p>`;
            html += '</div>';
        }
        
        // Add issues
        if (result.issues && result.issues.length > 0) {
            html += '<div class="ewog-validation-issues">';
            html += '<strong>Issues:</strong>';
            html += '<ul>';
            result.issues.forEach(issue => {
                html += `<li style="color: #d63384;">• ${issue}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        
        // Add warnings
        if (result.warnings && result.warnings.length > 0) {
            html += '<div class="ewog-validation-warnings">';
            html += '<strong>Recommendations:</strong>';
            html += '<ul>';
            result.warnings.forEach(warning => {
                html += `<li style="color: #856404;">• ${warning}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        
        container.innerHTML = html;
    }

    initSocialPreview() {
        // Generate initial preview for active tab
        setTimeout(() => {
            this.refreshPreview();
        }, 500);
    }

    switchPreviewTab(tab) {
        const platform = tab.dataset.platform;
        
        // Update tab states
        document.querySelectorAll('.ewog-preview-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        // Update preview content
        document.querySelectorAll('.ewog-preview-result').forEach(preview => {
            preview.style.display = preview.id === `ewog-preview-${platform}` ? 'block' : 'none';
        });
        
        // Refresh preview for the selected platform
        this.refreshPreview(platform);
    }

    refreshPreview(platform = null) {
        const activeTab = document.querySelector('.ewog-preview-tab.active');
        const targetPlatform = platform || (activeTab ? activeTab.dataset.platform : 'facebook');
        
        const postId = this.getPostId();
        const title = document.getElementById('ewog_og_title').value || document.getElementById('title').value;
        const description = document.getElementById('ewog_og_description').value;
        const image = document.getElementById('ewog_og_image').value;
        
        // Show loading state
        const loadingElement = document.querySelector('.ewog-preview-loading');
        const resultElement = document.getElementById(`ewog-preview-${targetPlatform}`);
        
        if (loadingElement) loadingElement.style.display = 'block';
        if (resultElement) resultElement.style.display = 'none';
        
        // Generate preview via AJAX
        fetch(ewogMetaBoxes.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'ewog_generate_og_preview',
                nonce: ewogMetaBoxes.nonce,
                post_id: postId,
                platform: targetPlatform,
                title: title,
                description: description,
                image: image
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && resultElement) {
                resultElement.innerHTML = data.data.html;
            } else {
                if (resultElement) {
                    resultElement.innerHTML = '<p>Preview could not be generated.</p>';
                }
            }
        })
        .catch(error => {
            console.error('Preview generation error:', error);
            if (resultElement) {
                resultElement.innerHTML = '<p>Preview generation failed.</p>';
            }
        })
        .finally(() => {
            if (loadingElement) loadingElement.style.display = 'none';
            if (resultElement) resultElement.style.display = 'block';
        });
    }

    testSharing() {
        const button = document.querySelector('.ewog-test-sharing');
        const originalText = button.textContent;
        
        button.textContent = ewogMetaBoxes.strings.testing;
        button.disabled = true;
        
        // Open Facebook debugger in new window
        const productUrl = this.getProductUrl();
        const debuggerUrl = `https://developers.facebook.com/tools/debug/?q=${encodeURIComponent(productUrl)}`;
        
        window.open(debuggerUrl, '_blank', 'width=800,height=600');
        
        // Reset button after delay
        setTimeout(() => {
            button.textContent = originalText;
            button.disabled = false;
        }, 2000);
    }

    initCustomTags() {
        // Add custom tag button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-add-custom-tag')) {
                e.preventDefault();
                this.addCustomTagRow();
            }
        });

        // Remove custom tag button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-remove-custom-tag')) {
                e.preventDefault();
                this.removeCustomTagRow(e.target);
            }
        });
    }

    addCustomTagRow() {
        const container = document.querySelector('.ewog-custom-tags-container');
        const noTagsMessage = container.querySelector('.ewog-no-custom-tags');
        
        // Remove "no tags" message if present
        if (noTagsMessage) {
            noTagsMessage.remove();
        }
        
        // Get next index
        const existingRows = container.querySelectorAll('.ewog-custom-tag-row');
        const nextIndex = existingRows.length;
        
        // Create new row
        const row = document.createElement('div');
        row.className = 'ewog-custom-tag-row';
        row.innerHTML = `
            <input type="text" 
                   name="ewog_custom_tags[${nextIndex}][property]" 
                   placeholder="Property (e.g., og:brand)" />
            <input type="text" 
                   name="ewog_custom_tags[${nextIndex}][content]" 
                   placeholder="Content" />
            <button type="button" class="button ewog-remove-custom-tag">Remove</button>
        `;
        
        container.appendChild(row);
        
        // Focus on the first input
        row.querySelector('input[type="text"]').focus();
    }

    removeCustomTagRow(button) {
        const row = button.closest('.ewog-custom-tag-row');
        const container = document.querySelector('.ewog-custom-tags-container');
        
        row.remove();
        
        // If no rows left, show "no tags" message
        const remainingRows = container.querySelectorAll('.ewog-custom-tag-row');
        if (remainingRows.length === 0) {
            container.innerHTML = '<p class="ewog-no-custom-tags">No custom tags added yet.</p>';
        }
    }

    getPostId() {
        // Try to get post ID from various sources
        const postIdInput = document.getElementById('post_ID');
        if (postIdInput) {
            return postIdInput.value;
        }
        
        // Try to get from URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('post') || 0;
    }

    getProductUrl() {
        const postId = this.getPostId();
        
        // Try to get preview link
        const previewLink = document.getElementById('preview-action')?.querySelector('a');
        if (previewLink) {
            return previewLink.href;
        }
        
        // Fallback to constructed URL
        const baseUrl = window.location.origin;
        return `${baseUrl}/?p=${postId}&preview=true`;
    }
}

// Enhanced functionality for better UX
class EWOGMetaBoxesEnhanced extends EWOGMetaBoxes {
    constructor() {
        super();
        this.initAdvancedFeatures();
    }

    initAdvancedFeatures() {
        this.initAutoComplete();
        this.initFieldDependencies();
        this.initKeyboardShortcuts();
        this.initFormValidation();
    }

    initAutoComplete() {
        // Auto-complete for custom tag properties
        const commonProperties = [
            'og:brand',
            'og:color',
            'og:size',
            'product:material',
            'product:pattern',
            'product:gender',
            'product:age_group',
            'twitter:label1',
            'twitter:data1',
            'twitter:label2',
            'twitter:data2'
        ];

        document.addEventListener('input', (e) => {
            if (e.target.name && e.target.name.includes('[property]')) {
                this.showAutoComplete(e.target, commonProperties);
            }
        });
    }

    showAutoComplete(input, suggestions) {
        const value = input.value.toLowerCase();
        const matches = suggestions.filter(prop => 
            prop.toLowerCase().includes(value) && prop !== value
        );

        // Remove existing autocomplete
        const existingList = input.parentNode.querySelector('.ewog-autocomplete');
        if (existingList) {
            existingList.remove();
        }

        if (matches.length === 0 || value.length < 2) {
            return;
        }

        // Create autocomplete list
        const list = document.createElement('ul');
        list.className = 'ewog-autocomplete';
        list.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
            margin: 0;
            padding: 0;
            list-style: none;
        `;

        matches.slice(0, 5).forEach(match => {
            const item = document.createElement('li');
            item.textContent = match;
            item.style.cssText = `
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
            `;
            
            item.addEventListener('click', () => {
                input.value = match;
                list.remove();
                input.focus();
            });
            
            item.addEventListener('mouseenter', () => {
                item.style.backgroundColor = '#f0f0f0';
            });
            
            item.addEventListener('mouseleave', () => {
                item.style.backgroundColor = 'white';
            });
            
            list.appendChild(item);
        });

        input.parentNode.appendChild(list);

        // Remove autocomplete when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function removeAutoComplete(e) {
                if (!list.contains(e.target) && e.target !== input) {
                    list.remove();
                    document.removeEventListener('click', removeAutoComplete);
                }
            });
        }, 100);
    }

    initFieldDependencies() {
        // Show/hide fields based on og:type selection
        document.addEventListener('change', (e) => {
            if (e.target.id === 'ewog_og_type') {
                this.updateFieldVisibility(e.target.value);
            }
        });

        // Initial update
        const ogTypeField = document.getElementById('ewog_og_type');
        if (ogTypeField) {
            this.updateFieldVisibility(ogTypeField.value);
        }
    }

    updateFieldVisibility(ogType) {
        const productFields = document.querySelectorAll('.ewog-product-only');
        const articleFields = document.querySelectorAll('.ewog-article-only');

        productFields.forEach(field => {
            field.style.display = ogType === 'product' ? 'table-row' : 'none';
        });

        articleFields.forEach(field => {
            field.style.display = ogType === 'article' ? 'table-row' : 'none';
        });
    }

    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Shift + P = Refresh Preview
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                this.refreshPreview();
            }

            // Ctrl/Cmd + Shift + V = Validate Image
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'V') {
                e.preventDefault();
                const validateButton = document.querySelector('.ewog-validate-image');
                if (validateButton && validateButton.style.display !== 'none') {
                    this.validateImage();
                }
            }
        });
    }

    initFormValidation() {
        // Real-time validation
        document.addEventListener('blur', (e) => {
            if (e.target.matches('#ewog_og_title, #ewog_og_description')) {
                this.validateField(e.target);
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        if (field.id === 'ewog_og_title') {
            if (value.length > 60) {
                isValid = false;
                message = 'Title is too long. Consider shortening for better social sharing.';
            } else if (value.length < 10 && value.length > 0) {
                isValid = false;
                message = 'Title might be too short. Consider adding more descriptive text.';
            }
        }

        if (field.id === 'ewog_og_description') {
            if (value.length > 155) {
                isValid = false;
                message = 'Description is too long. Consider shortening for better social sharing.';
            } else if (value.length < 20 && value.length > 0) {
                isValid = false;
                message = 'Description might be too short. Consider adding more details.';
            }
        }

        // Show/hide validation message
        this.showFieldValidation(field, isValid, message);
    }

    showFieldValidation(field, isValid, message) {
        // Remove existing validation
        const existingMessage = field.parentNode.querySelector('.ewog-field-validation');
        if (existingMessage) {
            existingMessage.remove();
        }

        if (!isValid && message) {
            const validationDiv = document.createElement('div');
            validationDiv.className = 'ewog-field-validation';
            validationDiv.style.cssText = `
                color: #d63384;
                font-size: 12px;
                margin-top: 5px;
            `;
            validationDiv.textContent = message;
            
            field.parentNode.appendChild(validationDiv);
            field.style.borderColor = '#d63384';
        } else {
            field.style.borderColor = '';
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('post-php') || document.body.classList.contains('post-new-php')) {
        if (document.getElementById('ewog_og_title')) {
            new EWOGMetaBoxesEnhanced();
            console.log('EWOG Meta Boxes initialized');
        }
    }
});