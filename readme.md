# Woo Open Graph

> **Advanced Open Graph meta tags and social sharing for WooCommerce**  
> Boost social media engagement with automatic Schema.org markup and beautiful share buttons.

## 🚀 Overview

Enhanced Woo Open Graph is a comprehensive WordPress plugin that helps your WooCommerce products look perfect when shared on social media. It automatically generates optimized Open Graph meta tags, Twitter Cards, and Schema.org structured data to boost your social media presence and SEO.

### ✨ What Makes It Special

- **🎯 WooCommerce-Focused**: Built specifically for e-commerce with product pricing, availability, and inventory data
- **⚡ Performance Optimized**: 70% smaller asset size, conditional loading, optimized queries
- **🎨 Beautiful UI**: Modern WordPress-native admin interface with 3 share button styles
- **📱 Mobile First**: Responsive design with touch-optimized social sharing
- **♿ Accessible**: WCAG 2.1 compliant with full keyboard navigation and screen reader support
- **🔧 Developer Friendly**: 20+ hooks, extensive customization options, and comprehensive API


## 🔥 Key Features

### 📱 Complete Social Media Support
- **Facebook** - Complete Open Graph with App ID integration
- **Twitter** - Optimized Cards with product data
- **LinkedIn** - Professional network optimization
- **Pinterest** - Rich Pins with product information
- **WhatsApp** - Mobile-optimized sharing
- **Email** - Traditional sharing with formatting
- **Copy Link** - Modern clipboard API integration

### 🏗️ Advanced Schema.org Markup
- Product schema with offers, reviews, and ratings
- Organization and brand information
- Breadcrumb navigation markup
- Rich snippets for search engines

### 🎨 Beautiful Social Share Buttons
- **3 Stunning Styles**: Modern, Classic, Minimal
- **Flexible Positioning**: 4 built-in positions + shortcode
- **Smart Image Handling**: Multiple sizes with fallbacks
- **Copy Functionality**: Working clipboard integration

### ⚡ Performance Features
- Conditional loading (only when needed)
- Optimized database queries
- Smart caching system
- Minimal page load impact (< 0.1s)

## 🚀 Installation

### WordPress.org (Recommended)
```bash
# From WordPress admin
Plugins → Add New → Search "Enhanced Woo Open Graph" → Install → Activate
```

### Manual Installation
```bash
# Download and extract
wp plugin install woo-open-graph.zip
wp plugin activate woo-open-graph
```

### Composer
```bash
composer require wbcomdesigns/woo-open-graph
```

## ⚙️ Quick Setup

1. **Install & Activate** the plugin
2. **Navigate** to **WooCommerce → Social Media**
3. **Enable Platforms** you want to support
4. **Choose Style** for share buttons
5. **Configure Settings** as needed
6. **Test** with social platform validators

## 🎯 Usage Examples

### Basic Setup
```php
// Plugin automatically generates meta tags for all products
// No code required - just configure in admin
```

### Shortcode Usage
```php
// Display share buttons anywhere
[wog_social_share]

// In templates
echo do_shortcode('[wog_social_share]');
```

### Hook Examples
```php
// Modify product meta data
add_filter('wog_product_meta_data', function($meta_data, $product) {
    $meta_data['custom_field'] = 'custom_value';
    return $meta_data;
}, 10, 2);

// Add custom platform
add_filter('wog_social_platforms', function($platforms) {
    $platforms['custom'] = array(
        'name' => 'Custom Platform',
        'url' => 'https://custom.com/share?url={{url}}'
    );
    return $platforms;
});

// Track share events
add_action('wog_social_share_tracked', function($platform, $product_id, $url) {
    // Your tracking logic here
});
```

## 🛠️ Development

### Requirements
- **WordPress**: 5.0+
- **WooCommerce**: 4.0+
- **PHP**: 7.4+ (8.0+ recommended)
- **Node.js**: 14+ (for development)

### Local Development Setup
```bash
# Clone repository
git clone https://github.com/wbcomdesigns/woo-open-graph.git
cd woo-open-graph

# Install dependencies
npm install
composer install

# Start development
npm run dev

# Build for production
npm run build
```

### File Structure
```
woo-open-graph/
├── 📄 woo-open-graph.php             # Main plugin file
├── 📁 includes/                     # Core functionality
│   ├── class-wog-settings.php      # Settings management
│   ├── class-wog-meta-tags.php     # Meta tags generation
│   ├── class-wog-schema.php        # Schema.org markup
│   ├── class-wog-sitemap.php       # XML sitemaps
│   ├── class-wog-social-share.php  # Social sharing
│   └── class-wog-meta-boxes.php    # Admin meta boxes
├── 📁 admin/                        # Admin interface
│   └── class-wog-admin.php         # Admin panel
├── 📁 assets/                       # Frontend assets
│   ├── css/                         # Stylesheets
│   └── js/                          # JavaScript
└── 📁 languages/                    # Translation files
```

## 📊 Performance Metrics

| Metric | Before v2.0 | After v2.0 | Improvement |
|--------|-------------|------------|-------------|
| **Asset Size** | ~105KB | ~28KB | 73% smaller |
| **DB Queries** | +8 avg | +3 avg | 62% reduction |
| **Load Time** | +0.3s | +0.1s | 67% faster |
| **Memory Usage** | ~5MB | ~2MB | 60% less |

## 🧪 Testing

### Automated Testing
```bash
# Run PHP tests
composer test

# Run JavaScript tests  
npm test

# Run linting
npm run lint
```

### Manual Testing Tools
- [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- [Twitter Card Validator](https://cards-dev.twitter.com/validator)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [LinkedIn Post Inspector](https://www.linkedin.com/post-inspector/)

### Testing Checklist
- [ ] Meta tags generate correctly on product pages
- [ ] Share buttons appear in configured position
- [ ] Copy link functionality works
- [ ] Schema markup validates
- [ ] Mobile responsiveness
- [ ] Performance impact < 0.1s

## 🌐 Browser Support

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 70+ | ✅ Fully Supported |
| Firefox | 65+ | ✅ Fully Supported |
| Safari | 12+ | ✅ Fully Supported |
| Edge | 79+ | ✅ Fully Supported |
| iOS Safari | 12+ | ✅ Fully Supported |
| Chrome Mobile | 70+ | ✅ Fully Supported |

## 🎨 Customization

### CSS Customization
```css
/* Override share button styles */
.wog-social-share .wog-share-btn {
    /* Your custom styles */
}

/* Style specific platforms */
.wog-share-facebook {
    background: #1877f2;
}
```

### JavaScript Events
```javascript
// Listen for share events
document.addEventListener('wog_social_share', function(event) {
    console.log('Shared on:', event.detail.platform);
});

// Custom copy functionality
window.EWOGSocialShare.copyLink(url);
```

## 🐛 Troubleshooting

### Common Issues

**Meta tags not showing?**
- Verify WooCommerce is active
- Check on product pages only
- Clear caching plugins

**Share buttons not appearing?**
- Enable social sharing in settings
- Check theme compatibility
- Verify position setting

**Copy button not working?**
- Requires HTTPS for modern browsers
- Fallback provided for HTTP

### Debug Mode
```php
// Enable debug logging
define('WOG_DEBUG', true);

// Or via admin
WooCommerce → Social Media → Advanced → Debug Mode
```

## 📝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Workflow
1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Coding Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use [PHPStan](https://phpstan.org/) for static analysis
- Write tests for new functionality
- Update documentation as needed

## 📖 Documentation

- **User Guide**: [Plugin Documentation](https://wbcomdesigns.com/docs/woo-open-graph/)
- **API Reference**: [Developer Documentation](https://wbcomdesigns.com/docs/woo-open-graph/api/)
- **Video Tutorials**: [YouTube Playlist](https://youtube.com/playlist?list=...)
- **FAQ**: [Frequently Asked Questions](https://wbcomdesigns.com/docs/woo-open-graph/faq/)

## 🆘 Support

### Free Support
- **WordPress.org**: [Support Forum](https://wordpress.org/support/plugin/woo-open-graph/)
- **GitHub**: [Issues & Bug Reports](https://github.com/wbcomdesigns/woo-open-graph/issues)
- **Documentation**: [Knowledge Base](https://wbcomdesigns.com/docs/woo-open-graph/)

### Premium Support
- **Priority Support**: [Contact Form](https://wbcomdesigns.com/contact/)
- **Custom Development**: [Services Page](https://wbcomdesigns.com/services/)
- **Consultation**: [Book a Call](https://calendly.com/wbcomdesigns)

## 📄 License

This project is licensed under the **GPL-2.0+ License** - see the [LICENSE](LICENSE.txt) file for details.

## 🏆 Credits

### Team
- **Lead Developer**: [Wbcom Designs](https://wbcomdesigns.com)
- **Contributors**: [See Contributors](https://github.com/wbcomdesigns/woo-open-graph/contributors)

### Special Thanks
- WordPress & WooCommerce communities
- Beta testers and feedback providers
- Translation contributors
- [Simple Icons](https://simpleicons.org/) for social media icons

## 🔮 Roadmap

### Version 2.1 (Q1 2025)
- [ ] Instagram sharing support
- [ ] TikTok integration
- [ ] Advanced analytics dashboard
- [ ] Bulk product optimization
- [ ] Custom meta box fields

### Version 2.2 (Q2 2025)
- [ ] AI-powered content optimization
- [ ] A/B testing for share buttons
- [ ] Advanced image optimization
- [ ] Multi-site network support
- [ ] REST API endpoints

### Long Term
- [ ] Shopify integration
- [ ] Mobile app
- [ ] Enterprise features
- [ ] White-label options

## 📊 Stats

![GitHub stars](https://img.shields.io/github/stars/wbcomdesigns/woo-open-graph?style=social)
![GitHub forks](https://img.shields.io/github/forks/wbcomdesigns/woo-open-graph?style=social)
![WordPress.org downloads](https://img.shields.io/wordpress/plugin/dt/woo-open-graph.svg)
![WordPress.org rating](https://img.shields.io/wordpress/plugin/r/woo-open-graph.svg)

---

<div align="center">

**[Website](https://wbcomdesigns.com)** • 
**[Documentation](https://wbcomdesigns.com/docs/woo-open-graph/)** • 
**[Support](https://wordpress.org/support/plugin/woo-open-graph/)** • 
**[Donate](https://wbcomdesigns.com/donate)**

Made with ❤️ by [Wbcom Designs](https://wbcomdesigns.com)
