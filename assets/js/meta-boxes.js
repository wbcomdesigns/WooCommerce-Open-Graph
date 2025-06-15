/**
 * Ultra Modern Enhanced Meta Boxes JavaScript
 * 
 * Features:
 * - Real-time preview updates
 * - Smart character counting with visual feedback
 * - Advanced image handling and analysis
 * - Tab interface management
 * - Field validation and status indicators
 * - Custom tags management with suggestions
 * - Platform-specific social media previews
 * - Accessibility and keyboard navigation
 * 
 * @package Enhanced_Woo_Open_Graph
 * @version 2.0.0
 */

class EWOGModernMetaBox {
    constructor(options) {
        this.options = {
            postId: 0,
            defaults: {},
            ajaxUrl: '',
            nonce: '',
            updateDelay: 500,
            ...options
        };
        
        this.elements = {};
        this.timers = {};
        this.currentPlatform = 'facebook';
        this.previewCache = new Map();
        
        this.init();
    }
    
    /**
     * Initialize the meta box
     */
    init() {
        this.bindElements();
        this.bindEvents();
        this.initializeTabs();
        this.initializeCharacterCounters();
        this.initializePreview();
        this.setupAccessibility();
        
        console.log('EWOG Modern Meta Box initialized');
    }
    
    /**
     * Bind DOM elements
     */
    bindElements() {
        this.elements = {
            // Main containers
            metabox: document.querySelector('.ewog-modern-metabox'),
            content: document.getElementById('ewog-metabox-content'),
            
            // Toggle
            enableToggle: document.querySelector('input[name="ewog_disable_og"]'),
            
            // Form fields
            titleField: document.getElementById('ewog_og_title'),
            descriptionField: document.getElementById('ewog_og_description'),
            imageField: document.getElementById('ewog_og_image'),
            
            // Tabs
            tabButtons: document.querySelectorAll('.ewog-tab-btn'),
            tabContents: document.querySelectorAll('.ewog-tab-content'),
            
            // Preview
            previewContainer: document.querySelector('.ewog-live-preview-container'),
            platformButtons: document.querySelectorAll('.ewog-platform-btn'),
            refreshButton: document.querySelector('.ewog-refresh-preview'),
            previewLoading: document.querySelector('.ewog-preview-loading'),
            previewContents: document.querySelectorAll('.ewog-preview-content'),
            
            // Image handling
            imagePreview: document.querySelector('.ewog-image-preview'),
            imagePlaceholder: document.querySelector('.ewog-image-placeholder'),
            uploadButton: document.querySelector('.ewog-upload-image'),
            removeButton: document.querySelector('.ewog-remove-image'),
            analyzeButton: document.querySelector('.ewog-analyze-image'),
            useFeaturedButton: document.querySelector('.ewog-use-featured'),
            
            // Custom tags
            customTagsList: document.getElementById('ewog-custom-tags-list'),
            addTagButton: document.querySelector('.ewog-add-custom-tag'),
            suggestTagsButton: document.querySelector('.ewog-suggest-tags'),
            tagSuggestions: document.querySelector('.ewog-tag-suggestions'),
            
            // Action buttons
            defaultButtons: document.querySelectorAll('.ewog-btn-default'),
            aiButtons: document.querySelectorAll('.ewog-btn-ai'),
            testButtons: document.querySelectorAll('.ewog-action-btn'),
            resetButton: document.querySelector('.ewog-reset-defaults')
        };
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Main toggle
        if (this.elements.enableToggle) {
            this.elements.enableToggle.addEventListener('change', (e) => {
                this.toggleMetaboxContent(!e.target.checked);
            });
        }
        
        // Tab navigation
        this.elements.tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(button.dataset.tab);
            });
        });
        
        // Real-time content updates
        if (this.elements.titleField) {
            this.elements.titleField.addEventListener('input', () => {
                this.updateCharacterCounter(this.elements.titleField);
                this.schedulePreviewUpdate();
            });
        }
        
        if (this.elements.descriptionField) {
            this.elements.descriptionField.addEventListener('input', () => {
                this.updateCharacterCounter(this.elements.descriptionField);
                this.schedulePreviewUpdate();
            });
        }
        
        // Preview platform switching
        this.elements.platformButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchPreviewPlatform(button.dataset.platform);
            });
        });
        
        // Refresh preview
        if (this.elements.refreshButton) {
            this.elements.refreshButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.refreshPreview(true);
            });
        }
        
        // Image handling
        if (this.elements.uploadButton) {
            this.elements.uploadButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.openMediaUploader();
            });
        }
        
        if (this.elements.removeButton) {
            this.elements.removeButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.removeImage();
            });
        }
        
        if (this.elements.analyzeButton) {
            this.elements.analyzeButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.analyzeImage();
            });
        }
        
        if (this.elements.useFeaturedButton) {
            this.elements.useFeaturedButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.useFeaturedImage();
            });
        }
        
        // Default content buttons
        this.elements.defaultButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.useDefaultContent(button.dataset.field);
            });
        });
        
        // AI optimization buttons
        this.elements.aiButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.optimizeContent(button.dataset.field);
            });
        });
        
        // Custom tags
        if (this.elements.addTagButton) {
            this.elements.addTagButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.addCustomTagRow();
            });
        }
        
        if (this.elements.suggestTagsButton) {
            this.elements.suggestTagsButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.suggestTags();
            });
        }
        
        // Delegate event for dynamic elements
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ewog-remove-tag')) {
                e.preventDefault();
                this.removeCustomTagRow(e.target);
            }
            
            if (e.target.matches('.ewog-add-suggestion')) {
                e.preventDefault();
                this.addSuggestedTag(e.target);
            }
            
            if (e.target.matches('.ewog-action-btn')) {
                e.preventDefault();
                this.handleActionButton(e.target);
            }
        });
        
        // Reset button
        if (this.elements.resetButton) {
            this.elements.resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetToDefaults();
            });
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }
    
    /**
     * Initialize tabs
     */
    initializeTabs() {
        const activeTab = document.querySelector('.ewog-tab-btn.active');
        if (activeTab) {
            this.switchTab(activeTab.dataset.tab);
        }
    }
    
    /**
     * Switch tab
     */
    switchTab(tabId) {
        // Update tab buttons
        this.elements.tabButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabId);
        });
        
        // Update tab contents
        this.elements.tabContents.forEach(content => {
            content.classList.toggle('active', content.id === `ewog-tab-${tabId}`);
        });
        
        // Trigger events for specific tabs
        if (tabId === 'custom') {
            this.initializeCustomTags();
        }
        
        // Update URL hash for better UX
        window.location.hash = `ewog-tab-${tabId}`;
    }
    
    /**
     * Initialize character counters
     */
    initializeCharacterCounters() {
        const fields = [this.elements.titleField, this.elements.descriptionField];
        
        fields.forEach(field => {
            if (field) {
                this.updateCharacterCounter(field);
            }
        });
    }
    
    /**
     * Update character counter with visual feedback
     */
    updateCharacterCounter(field) {
        const wrapper = field.closest('.ewog-field-wrapper');
        if (!wrapper) return;
        
        const counter = wrapper.querySelector('.ewog-char-counter');
        const statusElement = wrapper.querySelector('.ewog-field-status');
        const bar = wrapper.querySelector('.ewog-char-bar');
        
        if (!counter || !statusElement || !bar) return;
        
        const maxLength = parseInt(field.getAttribute('maxlength')) || 0;
        const currentLength = field.value.length;
        const percentage = (currentLength / maxLength) * 100;
        
        // Update counter text
        const currentSpan = counter.querySelector('.ewog-char-current');
        if (currentSpan) {
            currentSpan.textContent = currentLength;
        }
        
        // Update progress bar
        bar.style.width = `${Math.min(percentage, 100)}%`;
        
        // Determine status
        let status = 'optimal';
        let statusText = ewogModernMeta.strings.optimal;
        
        const limits = ewogModernMeta.limits;
        const fieldType = field.id.includes('title') ? 'title' : 'desc';
        
        if (currentLength === 0) {
            status = 'error';
            statusText = 'Field is required';
        } else if (currentLength < limits[`${fieldType}_min`]) {
            status = 'warning';
            statusText = ewogModernMeta.strings.too_short;
        } else if (currentLength > limits[`${fieldType}_max`]) {
            status = 'error';
            statusText = ewogModernMeta.strings.too_long;
        } else if (currentLength >= limits[`${fieldType}_optimal`]) {
            status = 'optimal';
            statusText = ewogModernMeta.strings.optimal;
        } else {
            status = 'good';
            statusText = ewogModernMeta.strings.good;
        }
        
        // Apply status classes
        counter.className = `ewog-char-counter ${status}`;
        statusElement.className = `ewog-field-status ${status}`;
        field.className = field.className.replace(/\b(success|error|warning)\b/g, '') + ` ${status}`;
        
        // Update status text
        const statusTextElement = statusElement.querySelector('.ewog-status-text');
        if (statusTextElement) {
            statusTextElement.textContent = statusText;
        }
    }
    
    /**
     * Initialize preview
     */
    initializePreview() {
        this.refreshPreview();
    }
    
    /**
     * Schedule preview update (debounced)
     */
    schedulePreviewUpdate() {
        clearTimeout(this.timers.preview);
        this.timers.preview = setTimeout(() => {
            this.refreshPreview();
        }, this.options.updateDelay);
    }
    
    /**
     * Switch preview platform
     */
    switchPreviewPlatform(platform) {
        this.currentPlatform = platform;
        
        // Update platform buttons
        this.elements.platformButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.platform === platform);
        });
        
        // Show/hide preview contents
        this.elements.previewContents.forEach(content => {
            content.style.display = content.id === `ewog-preview-${platform}` ? 'block' : 'none';
        });
        
        // Refresh preview for the selected platform
        this.refreshPreview();
    }
    
    /**
     * Refresh preview
     */
    async refreshPreview(force = false) {
        if (!this.elements.previewContainer) return;
        
        const title = this.elements.titleField?.value || '';
        const description = this.elements.descriptionField?.value || '';
        const image = this.elements.imageField?.value || '';
        
        // Create cache key
        const cacheKey = `${this.currentPlatform}-${title}-${description}-${image}`;
        
        // Use cached preview if available and not forced
        if (!force && this.previewCache.has(cacheKey)) {
            this.updatePreviewContent(this.previewCache.get(cacheKey));
            return;
        }
        
        // Show loading state
        this.showPreviewLoading(true);
        
        try {
            const response = await this.makeAjaxRequest('ewog_generate_og_preview', {
                post_id: this.options.postId,
                platform: this.currentPlatform,
                title: title,
                description: description,
                image: image
            });
            
            if (response.success) {
                // Cache the result
                this.previewCache.set(cacheKey, response.data.html);
                this.updatePreviewContent(response.data.html);
            } else {
                this.showPreviewError('Failed to generate preview');
            }
        } catch (error) {
            console.error('Preview generation error:', error);
            this.showPreviewError('Preview generation failed');
        } finally {
            this.showPreviewLoading(false);
        }
    }
    
    /**
     * Update preview content
     */
    updatePreviewContent(html) {
        const activePreview = document.getElementById(`ewog-preview-${this.currentPlatform}`);
        if (activePreview) {
            activePreview.innerHTML = html;
        }
    }
    
    /**
     * Show/hide preview loading state
     */
    showPreviewLoading(show) {
        if (this.elements.previewLoading) {
            this.elements.previewLoading.style.display = show ? 'block' : 'none';
        }
    }
    
    /**
     * Show preview error
     */
    showPreviewError(message) {
        const activePreview = document.getElementById(`ewog-preview-${this.currentPlatform}`);
        if (activePreview) {
            activePreview.innerHTML = `
                <div class="ewog-preview-error">
                    <p><span class="dashicons dashicons-warning"></span> ${message}</p>
                </div>
            `;
        }
    }
    
    /**
     * Toggle metabox content
     */
    toggleMetaboxContent(disabled) {
        if (this.elements.content) {
            this.elements.content.classList.toggle('disabled', disabled);
        }
    }
    
    /**
     * Open media uploader
     */
    openMediaUploader() {
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress media library is not available.');
            return;
        }
        
        const mediaUploader = wp.media({
            title: ewogModernMeta.strings.chooseImage,
            button: {
                text: ewogModernMeta.strings.useImage
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', () => {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            this.setImage(attachment.url, attachment);
        });
        
        mediaUploader.open();
    }
    
    /**
     * Set image
     */
    setImage(imageUrl, attachment = null) {
        if (this.elements.imageField) {
            this.elements.imageField.value = imageUrl;
        }
        
        this.updateImagePreview(imageUrl, attachment);
        this.schedulePreviewUpdate();
    }
    
    /**
     * Update image preview
     */
    updateImagePreview(imageUrl, attachment = null) {
        const container = document.querySelector('.ewog-image-preview-container');
        if (!container) return;
        
        if (imageUrl) {
            // Show image preview
            if (this.elements.imagePreview) {
                this.elements.imagePreview.classList.add('active');
                const img = this.elements.imagePreview.querySelector('img');
                if (img) {
                    img.src = imageUrl;
                }
                
                // Update image specs if attachment data is available
                if (attachment) {
                    const specsElement = this.elements.imagePreview.querySelector('.ewog-image-specs');
                    if (specsElement) {
                        specsElement.innerHTML = `
                            <span class="ewog-image-dimensions">${attachment.width}×${attachment.height}</span>
                            <span class="ewog-image-size">${this.formatFileSize(attachment.filesizeInBytes)}</span>
                        `;
                    }
                }
            }
            
            // Hide placeholder
            if (this.elements.imagePlaceholder) {
                this.elements.imagePlaceholder.style.display = 'none';
            }
        } else {
            // Hide preview
            if (this.elements.imagePreview) {
                this.elements.imagePreview.classList.remove('active');
            }
            
            // Show placeholder
            if (this.elements.imagePlaceholder) {
                this.elements.imagePlaceholder.style.display = 'block';
            }
        }
    }
    
    /**
     * Remove image
     */
    removeImage() {
        this.setImage('');
        this.hideImageAnalysis();
    }
    
    /**
     * Use featured image
     */
    async useFeaturedImage() {
        try {
            const response = await this.makeAjaxRequest('ewog_get_default_content', {
                post_id: this.options.postId
            });
            
            if (response.success && response.data.image) {
                this.setImage(response.data.image);
            } else {
                alert('No featured image available');
            }
        } catch (error) {
            console.error('Error getting featured image:', error);
            alert('Failed to get featured image');
        }
    }
    
    /**
     * Analyze image
     */
    async analyzeImage() {
        const imageUrl = this.elements.imageField?.value;
        if (!imageUrl) {
            alert('No image to analyze');
            return;
        }
        
        const button = this.elements.analyzeButton;
        const originalText = button.textContent;
        button.textContent = ewogModernMeta.strings.analyzing;
        button.disabled = true;
        
        try {
            const response = await this.makeAjaxRequest('ewog_analyze_image', {
                image_url: imageUrl
            });
            
            if (response.success) {
                this.showImageAnalysis(response.data);
            } else {
                alert('Image analysis failed: ' + (response.data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Image analysis error:', error);
            alert('Image analysis failed');
        } finally {
            button.textContent = originalText;
            button.disabled = false;
        }
    }
    
    /**
     * Show image analysis results
     */
    showImageAnalysis(analysis) {
        const analysisContainer = document.querySelector('.ewog-image-analysis');
        if (!analysisContainer) return;
        
        const resultsDiv = analysisContainer.querySelector('.ewog-analysis-results');
        if (!resultsDiv) return;
        
        let html = `
            <div class="ewog-analysis-score ${analysis.rating}">
                <div class="ewog-score-value">${analysis.score}</div>
                <div class="ewog-score-info">
                    <div><strong>Overall Score</strong></div>
                    <div>Rating: ${analysis.rating}</div>
                </div>
            </div>
        `;
        
        if (analysis.issues && analysis.issues.length > 0) {
            html += `
                <div class="ewog-analysis-issues">
                    <h6>Issues to Fix:</h6>
                    <ul>
                        ${analysis.issues.map(issue => `<li>${issue}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        if (analysis.recommendations && analysis.recommendations.length > 0) {
            html += `
                <div class="ewog-analysis-recommendations">
                    <h6>Recommendations:</h6>
                    <ul>
                        ${analysis.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        resultsDiv.innerHTML = html;
        analysisContainer.style.display = 'block';
    }
    
    /**
     * Hide image analysis
     */
    hideImageAnalysis() {
        const analysisContainer = document.querySelector('.ewog-image-analysis');
        if (analysisContainer) {
            analysisContainer.style.display = 'none';
        }
    }
    
    /**
     * Use default content
     */
    async useDefaultContent(field) {
        try {
            const response = await this.makeAjaxRequest('ewog_get_default_content', {
                post_id: this.options.postId
            });
            
            if (response.success) {
                const defaults = response.data;
                
                switch (field) {
                    case 'title':
                        if (this.elements.titleField && defaults.title) {
                            this.elements.titleField.value = defaults.title;
                            this.updateCharacterCounter(this.elements.titleField);
                            this.schedulePreviewUpdate();
                        }
                        break;
                    case 'description':
                        if (this.elements.descriptionField && defaults.description) {
                            this.elements.descriptionField.value = defaults.description;
                            this.updateCharacterCounter(this.elements.descriptionField);
                            this.schedulePreviewUpdate();
                        }
                        break;
                    case 'image':
                        if (defaults.image) {
                            this.setImage(defaults.image);
                        }
                        break;
                }
            }
        } catch (error) {
            console.error('Error getting default content:', error);
        }
    }
    
    /**
     * Optimize content with AI
     */
    async optimizeContent(field) {
        // Placeholder for AI optimization
        // This would integrate with an AI service to optimize the content
        alert('AI optimization feature coming soon!');
    }
    
    /**
     * Initialize custom tags
     */
    initializeCustomTags() {
        this.updateCustomTagsDisplay();
    }
    
    /**
     * Add custom tag row
     */
    addCustomTagRow(property = '', content = '') {
        if (!this.elements.customTagsList) return;
        
        const index = this.getNextTagIndex();
        const row = document.createElement('div');
        row.className = 'ewog-custom-tag-row';
        row.innerHTML = `
            <div class="ewog-tag-fields">
                <input type="text" 
                       name="ewog_custom_tags[${index}][property]" 
                       value="${this.escapeHtml(property)}" 
                       placeholder="Property (e.g., og:brand)" 
                       class="ewog-tag-property" />
                <input type="text" 
                       name="ewog_custom_tags[${index}][content]" 
                       value="${this.escapeHtml(content)}" 
                       placeholder="Content" 
                       class="ewog-tag-content" />
            </div>
            <button type="button" class="ewog-btn-icon ewog-remove-tag" title="Remove Tag">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        `;
        
        // Remove "no tags" message if present
        const noTagsMessage = this.elements.customTagsList.querySelector('.ewog-no-tags-message');
        if (noTagsMessage) {
            noTagsMessage.remove();
        }
        
        this.elements.customTagsList.appendChild(row);
        
        // Focus on the first input
        const firstInput = row.querySelector('.ewog-tag-property');
        if (firstInput) {
            firstInput.focus();
        }
    }
    
    /**
     * Remove custom tag row
     */
    removeCustomTagRow(button) {
        const row = button.closest('.ewog-custom-tag-row');
        if (row) {
            row.remove();
            this.updateCustomTagsDisplay();
        }
    }
    
    /**
     * Update custom tags display
     */
    updateCustomTagsDisplay() {
        if (!this.elements.customTagsList) return;
        
        const rows = this.elements.customTagsList.querySelectorAll('.ewog-custom-tag-row');
        if (rows.length === 0) {
            this.elements.customTagsList.innerHTML = `
                <div class="ewog-no-tags-message">
                    <span class="dashicons dashicons-tag"></span>
                    <p>No custom tags added yet.</p>
                </div>
            `;
        }
    }
    
    /**
     * Suggest tags
     */
    async suggestTags() {
        const button = this.elements.suggestTagsButton;
        const originalText = button.textContent;
        button.textContent = 'Loading suggestions...';
        button.disabled = true;
        
        try {
            const response = await this.makeAjaxRequest('ewog_suggest_tags', {
                post_id: this.options.postId
            });
            
            if (response.success) {
                this.showTagSuggestions(response.data);
            } else {
                alert('Failed to get tag suggestions');
            }
        } catch (error) {
            console.error('Error getting tag suggestions:', error);
            alert('Failed to get tag suggestions');
        } finally {
            button.textContent = originalText;
            button.disabled = false;
        }
    }
    
    /**
     * Show tag suggestions
     */
    showTagSuggestions(suggestions) {
        if (!this.elements.tagSuggestions) return;
        
        const suggestionsList = this.elements.tagSuggestions.querySelector('.ewog-suggestion-list');
        if (!suggestionsList) return;
        
        if (suggestions.length === 0) {
            suggestionsList.innerHTML = '<p>No suggestions available</p>';
        } else {
            suggestionsList.innerHTML = suggestions.map(suggestion => `
                <div class="ewog-suggestion-item">
                    <div class="ewog-suggestion-info">
                        <div class="ewog-suggestion-property">${this.escapeHtml(suggestion.property)}</div>
                        <div class="ewog-suggestion-description">${this.escapeHtml(suggestion.description)}</div>
                    </div>
                    <button type="button" 
                            class="ewog-add-suggestion" 
                            data-property="${this.escapeHtml(suggestion.property)}"
                            data-content="${this.escapeHtml(suggestion.content)}">
                        Add
                    </button>
                </div>
            `).join('');
        }
        
        this.elements.tagSuggestions.style.display = 'block';
    }
    
    /**
     * Add suggested tag
     */
    addSuggestedTag(button) {
        const property = button.dataset.property;
        const content = button.dataset.content;
        
        this.addCustomTagRow(property, content);
        
        // Remove the suggestion
        const suggestionItem = button.closest('.ewog-suggestion-item');
        if (suggestionItem) {
            suggestionItem.remove();
        }
    }
    
    /**
     * Handle action buttons
     */
    async handleActionButton(button) {
        const action = button.classList[1]; // Get the specific action class
        
        switch (action) {
            case 'ewog-test-facebook':
                this.testSocialPlatform('facebook');
                break;
            case 'ewog-test-twitter':
                this.testSocialPlatform('twitter');
                break;
            case 'ewog-test-schema':
                this.testSchema();
                break;
            case 'ewog-copy-settings':
                this.copySettings();
                break;
            case 'ewog-export-settings':
                this.exportSettings();
                break;
        }
    }
    
    /**
     * Test social platform
     */
    testSocialPlatform(platform) {
        const currentUrl = window.location.href.replace(/&?action=edit/, '').replace('post.php?', 'post.php?action=edit&');
        const productUrl = currentUrl.replace('wp-admin/post.php?action=edit&post=', window.location.origin + '/?p=');
        
        let testUrl = '';
        switch (platform) {
            case 'facebook':
                testUrl = `https://developers.facebook.com/tools/debug/?q=${encodeURIComponent(productUrl)}`;
                break;
            case 'twitter':
                testUrl = `https://cards-dev.twitter.com/validator?url=${encodeURIComponent(productUrl)}`;
                break;
        }
        
        if (testUrl) {
            window.open(testUrl, '_blank', 'width=800,height=600');
        }
    }
    
    /**
     * Test schema
     */
    testSchema() {
        const currentUrl = window.location.href.replace(/&?action=edit/, '').replace('post.php?', 'post.php?action=edit&');
        const productUrl = currentUrl.replace('wp-admin/post.php?action=edit&post=', window.location.origin + '/?p=');
        const testUrl = `https://search.google.com/test/rich-results?url=${encodeURIComponent(productUrl)}`;
        
        window.open(testUrl, '_blank', 'width=800,height=600');
    }
    
    /**
     * Copy settings
     */
    copySettings() {
        const settings = {
            title: this.elements.titleField?.value || '',
            description: this.elements.descriptionField?.value || '',
            image: this.elements.imageField?.value || ''
        };
        
        navigator.clipboard.writeText(JSON.stringify(settings, null, 2))
            .then(() => {
                alert('Settings copied to clipboard!');
            })
            .catch(() => {
                alert('Failed to copy settings');
            });
    }
    
    /**
     * Export settings
     */
    exportSettings() {
        const settings = {
            title: this.elements.titleField?.value || '',
            description: this.elements.descriptionField?.value || '',
            image: this.elements.imageField?.value || '',
            custom_tags: this.getCustomTags()
        };
        
        const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ewog-settings-${this.options.postId}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    /**
     * Reset to defaults
     */
    async resetToDefaults() {
        if (!confirm(ewogModernMeta.strings.confirm_reset)) {
            return;
        }
        
        try {
            const response = await this.makeAjaxRequest('ewog_get_default_content', {
                post_id: this.options.postId
            });
            
            if (response.success) {
                const defaults = response.data;
                
                if (this.elements.titleField && defaults.title) {
                    this.elements.titleField.value = defaults.title;
                    this.updateCharacterCounter(this.elements.titleField);
                }
                
                if (this.elements.descriptionField && defaults.description) {
                    this.elements.descriptionField.value = defaults.description;
                    this.updateCharacterCounter(this.elements.descriptionField);
                }
                
                if (defaults.image) {
                    this.setImage(defaults.image);
                }
                
                this.schedulePreviewUpdate();
            }
        } catch (error) {
            console.error('Error resetting to defaults:', error);
            alert('Failed to reset to defaults');
        }
    }
    
    /**
     * Setup accessibility
     */
    setupAccessibility() {
        // Add ARIA labels to elements that need them
        this.elements.tabButtons.forEach(button => {
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-selected', button.classList.contains('active'));
        });
        
        this.elements.tabContents.forEach(content => {
            content.setAttribute('role', 'tabpanel');
        });
        
        // Add keyboard navigation for tabs
        this.elements.tabButtons.forEach((button, index) => {
            button.addEventListener('keydown', (e) => {
                let newIndex = index;
                
                switch (e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        newIndex = index > 0 ? index - 1 : this.elements.tabButtons.length - 1;
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        newIndex = index < this.elements.tabButtons.length - 1 ? index + 1 : 0;
                        break;
                    case 'Home':
                        e.preventDefault();
                        newIndex = 0;
                        break;
                    case 'End':
                        e.preventDefault();
                        newIndex = this.elements.tabButtons.length - 1;
                        break;
                }
                
                if (newIndex !== index) {
                    this.elements.tabButtons[newIndex].focus();
                    this.switchTab(this.elements.tabButtons[newIndex].dataset.tab);
                }
            });
        });
    }
    
    /**
     * Handle keyboard shortcuts
     */
    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + Shift + P = Refresh Preview
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'P') {
            e.preventDefault();
            this.refreshPreview(true);
        }
        
        // Ctrl/Cmd + Shift + R = Reset to defaults
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
            e.preventDefault();
            this.resetToDefaults();
        }
        
        // Ctrl/Cmd + Shift + T = Add custom tag
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
            e.preventDefault();
            this.addCustomTagRow();
        }
    }
    
    /**
     * Utility methods
     */
    
    /**
     * Make AJAX request
     */
    async makeAjaxRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', this.options.nonce);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        const response = await fetch(this.options.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Get next tag index
     */
    getNextTagIndex() {
        const existingInputs = document.querySelectorAll('input[name^="ewog_custom_tags["]');
        const indices = Array.from(existingInputs).map(input => {
            const match = input.name.match(/ewog_custom_tags\[(\d+)\]/);
            return match ? parseInt(match[1]) : 0;
        });
        
        return indices.length > 0 ? Math.max(...indices) + 1 : 0;
    }
    
    /**
     * Get custom tags
     */
    getCustomTags() {
        const tags = [];
        const rows = document.querySelectorAll('.ewog-custom-tag-row');
        
        rows.forEach(row => {
            const property = row.querySelector('.ewog-tag-property')?.value;
            const content = row.querySelector('.ewog-tag-content')?.value;
            
            if (property && content) {
                tags.push({ property, content });
            }
        });
        
        return tags;
    }
    
    /**
     * Format file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a product edit page
    if (document.body.classList.contains('post-type-product') && 
        (document.body.classList.contains('post-php') || document.body.classList.contains('post-new-php'))) {
        
        // Initialize if meta box exists
        if (document.querySelector('.ewog-modern-metabox')) {
            const options = window.ewogModernMeta || {};
            window.ewogMetaBox = new EWOGModernMetaBox(options);
        }
    }
});

// Export for global access
window.EWOGModernMetaBox = EWOGModernMetaBox;