<?php
/**
 * Enhanced Schema Markup Class
 * 
 * Comprehensive Schema.org structured data for WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Schema {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = get_option('ewog_settings', array());
        $this->init_hooks();
    }
    
    private function init_hooks() {
        if (!empty($this->settings['enable_schema'])) {
            add_action('wp_head', array($this, 'output_schema_markup'), 5);
        }
    }
    
    /**
     * Output schema markup
     */
    public function output_schema_markup() {
        if (!$this->should_add_schema()) {
            return;
        }
        
        $schemas = array();
        
        // Get page-specific schema
        if (is_product()) {
            global $post;
            $schemas['product'] = $this->get_product_schema($post);
            
            if (!empty($this->settings['enable_breadcrumb_schema'])) {
                $schemas['breadcrumb'] = $this->get_breadcrumb_schema();
            }
        } elseif (is_product_category()) {
            $schemas['category'] = $this->get_category_schema();
        } elseif (is_shop()) {
            $schemas['shop'] = $this->get_shop_schema();
        }
        
        // Add organization schema globally
        if (!empty($this->settings['enable_organization_schema'])) {
            $schemas['organization'] = $this->get_organization_schema();
        }
        
        // Output schemas
        foreach ($schemas as $type => $schema_data) {
            if (!empty($schema_data)) {
                echo "\n<!-- Enhanced Woo Open Graph Schema: {$type} -->\n";
                echo '<script type="application/ld+json">' . "\n";
                echo wp_json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                echo "\n" . '</script>' . "\n";
            }
        }
    }
    
    /**
     * Check if schema should be added
     */
    private function should_add_schema() {
        if (!function_exists('is_woocommerce')) {
            return false;
        }
        
        return is_product() || is_product_category() || is_shop() || is_woocommerce();
    }
    
    /**
     * Get enhanced product schema
     */
    private function get_product_schema($post) {
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return array();
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => $this->get_product_description($product),
            'url' => get_permalink($post->ID),
            'image' => $this->get_product_images($product),
            'sku' => $product->get_sku(),
            'brand' => $this->get_enhanced_brand_schema($product),
            'category' => $this->get_category_hierarchy($product),
            'offers' => $this->get_enhanced_offers_schema($product),
            'aggregateRating' => $this->get_enhanced_rating_schema($product),
            'review' => $this->get_detailed_reviews_schema($product)
        );
        
        // Enhanced product properties
        if (!empty($this->settings['enable_enhanced_schema'])) {
            $enhanced_props = array(
                'productID' => $product->get_id(),
                'gtin' => $this->get_gtin($product),
                'mpn' => $this->get_mpn($product),
                'manufacturer' => $this->get_manufacturer_schema($product),
                'model' => $this->get_product_model($product),
                'color' => $this->get_product_colors($product),
                'size' => $this->get_product_sizes($product),
                'material' => $this->get_product_materials($product),
                'additionalProperty' => $this->get_product_specifications($product),
                'hasVariant' => $this->get_product_variants($product)
            );
            
            $schema = array_merge($schema, array_filter($enhanced_props));
        }
        
        // Add weight and dimensions
        if ($product->get_weight()) {
            $schema['weight'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $product->get_weight(),
                'unitCode' => get_option('woocommerce_weight_unit', 'kg')
            );
        }
        
        if ($product->get_dimensions(false)) {
            $dimensions = $product->get_dimensions(false);
            $unit = get_option('woocommerce_dimension_unit', 'cm');
            
            if (!empty($dimensions['length'])) {
                $schema['depth'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => $dimensions['length'],
                    'unitCode' => $unit
                );
            }
            
            if (!empty($dimensions['width'])) {
                $schema['width'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => $dimensions['width'],
                    'unitCode' => $unit
                );
            }
            
            if (!empty($dimensions['height'])) {
                $schema['height'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => $dimensions['height'],
                    'unitCode' => $unit
                );
            }
        }
        
        return array_filter($schema);
    }
    
    /**
     * Get enhanced offers schema
     */
    private function get_enhanced_offers_schema($product) {
        if ($product->is_type('variable')) {
            return $this->get_variable_offers_schema($product);
        }
        
        $offers = array(
            '@type' => 'Offer',
            'url' => get_permalink($product->get_id()),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $this->get_availability_schema($product),
            'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
            'seller' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ),
            'itemCondition' => 'https://schema.org/NewCondition'
        );
        
        if ($product->get_price()) {
            $offers['price'] = $product->get_price();
        }
        
        // Add sale price information
        if ($product->is_on_sale() && $product->get_regular_price()) {
            $offers['priceSpecification'] = array(
                '@type' => 'UnitPriceSpecification',
                'price' => $product->get_regular_price(),
                'priceCurrency' => get_woocommerce_currency()
            );
            
            // Add sale dates if available
            $sale_dates = $this->get_sale_dates($product);
            if ($sale_dates) {
                $offers['priceValidFrom'] = $sale_dates['from'];
                $offers['priceValidUntil'] = $sale_dates['to'];
            }
        }
        
        // Add shipping information
        $shipping = $this->get_shipping_schema($product);
        if ($shipping) {
            $offers['shippingDetails'] = $shipping;
        }
        
        // Add return policy
        $return_policy = $this->get_return_policy_schema($product);
        if ($return_policy) {
            $offers['hasMerchantReturnPolicy'] = $return_policy;
        }
        
        return $offers;
    }
    
    /**
     * Get variable product offers schema
     */
    private function get_variable_offers_schema($product) {
        $variations = $product->get_children();
        $offers = array();
        
        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation || !$variation->is_purchasable()) {
                continue;
            }
            
            $offer = array(
                '@type' => 'Offer',
                'url' => get_permalink($variation_id),
                'price' => $variation->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => $this->get_availability_schema($variation),
                'sku' => $variation->get_sku(),
                'seller' => array(
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name')
                )
            );
            
            // Add variation attributes
            $attributes = $variation->get_variation_attributes();
            if (!empty($attributes)) {
                $offer['hasVariant'] = $this->format_variation_attributes($attributes);
            }
            
            $offers[] = $offer;
        }
        
        return $offers;
    }
    
    /**
     * Get breadcrumb schema
     */
    private function get_breadcrumb_schema() {
        if (!is_product()) {
            return null;
        }
        
        global $post;
        $product = wc_get_product($post->ID);
        $categories = get_the_terms($product->get_id(), 'product_cat');
        
        if (!$categories) {
            return null;
        }
        
        $breadcrumbs = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        $position = 1;
        
        // Add home
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Home',
            'item' => home_url()
        );
        
        // Add shop
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Shop',
            'item' => get_permalink(wc_get_page_id('shop'))
        );
        
        // Add category hierarchy
        $category = $categories[0];
        $ancestors = get_ancestors($category->term_id, 'product_cat');
        $ancestors = array_reverse($ancestors);
        
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            $breadcrumbs['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $ancestor->name,
                'item' => get_term_link($ancestor)
            );
        }
        
        // Add current category
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $category->name,
            'item' => get_term_link($category)
        );
        
        // Add current product
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title($post->ID),
            'item' => get_permalink($post->ID)
        );
        
        return $breadcrumbs;
    }
    
    /**
     * Get organization schema
     */
    private function get_organization_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'logo' => $this->get_site_logo(),
            'sameAs' => $this->get_social_profiles()
        );
        
        // Add contact information
        $phone = get_option('woocommerce_store_phone', '');
        $email = get_option('woocommerce_store_email', get_option('admin_email'));
        
        if ($phone || $email) {
            $contact_point = array('@type' => 'ContactPoint');
            
            if ($phone) {
                $contact_point['telephone'] = $phone;
            }
            
            if ($email) {
                $contact_point['email'] = $email;
            }
            
            $contact_point['contactType'] = 'customer service';
            $schema['contactPoint'] = $contact_point;
        }
        
        // Add store address
        $address = $this->get_store_address_schema();
        if ($address) {
            $schema['address'] = $address;
        }
        
        return $schema;
    }
    
    /**
     * Helper methods
     */
    
    private function get_product_description($product) {
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = $product->get_description();
        }
        return wp_strip_all_tags($description);
    }
    
    private function get_product_images($product) {
        $images = array();
        
        // Featured image
        if ($product->get_image_id()) {
            $image_url = wp_get_attachment_image_src($product->get_image_id(), 'full');
            if ($image_url) {
                $images[] = $image_url[0];
            }
        }
        
        // Gallery images
        $gallery_images = $product->get_gallery_image_ids();
        foreach ($gallery_images as $image_id) {
            $image_url = wp_get_attachment_image_src($image_id, 'full');
            if ($image_url) {
                $images[] = $image_url[0];
            }
        }
        
        return !empty($images) ? $images : array(wc_placeholder_img_src('full'));
    }
    
    private function get_gtin($product) {
        $gtin_fields = array('_gtin', '_upc', '_ean', '_isbn', '_gtin8', '_gtin12', '_gtin13', '_gtin14');
        
        foreach ($gtin_fields as $field) {
            $gtin = get_post_meta($product->get_id(), $field, true);
            if (!empty($gtin)) {
                return $gtin;
            }
        }
        
        return '';
    }
    
    private function get_mpn($product) {
        $mpn = get_post_meta($product->get_id(), '_mpn', true);
        if (empty($mpn)) {
            $mpn = get_post_meta($product->get_id(), '_manufacturer_part_number', true);
        }
        return $mpn;
    }
    
    private function get_enhanced_brand_schema($product) {
        $brand_name = $this->get_product_brand($product);
        
        if (empty($brand_name)) {
            return null;
        }
        
        return array(
            '@type' => 'Brand',
            'name' => $brand_name
        );
    }
    
    private function get_product_brand($product) {
        // Check for common brand taxonomies
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand');
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_the_terms($product->get_id(), $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    return $terms[0]->name;
                }
            }
        }
        
        // Check for brand meta field
        $brand = get_post_meta($product->get_id(), '_brand', true);
        if (!empty($brand)) {
            return $brand;
        }
        
        return '';
    }
    
    private function get_category_hierarchy($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        
        if (!$categories || is_wp_error($categories)) {
            return '';
        }
        
        // Get the primary category (first one)
        $primary_category = $categories[0];
        
        // Build category hierarchy
        $hierarchy = array();
        $ancestors = get_ancestors($primary_category->term_id, 'product_cat');
        
        foreach (array_reverse($ancestors) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            $hierarchy[] = $ancestor->name;
        }
        
        $hierarchy[] = $primary_category->name;
        
        return implode(' > ', $hierarchy);
    }
    
    private function get_availability_schema($product) {
        if ($product->is_in_stock()) {
            if ($product->managing_stock() && $product->get_stock_quantity() > 0) {
                return 'https://schema.org/InStock';
            } elseif (!$product->managing_stock()) {
                return 'https://schema.org/InStock';
            } else {
                return 'https://schema.org/OutOfStock';
            }
        } else {
            return 'https://schema.org/OutOfStock';
        }
    }
    
    private function get_enhanced_rating_schema($product) {
        $average_rating = $product->get_average_rating();
        $review_count = $product->get_review_count();
        
        if (!$average_rating || !$review_count) {
            return null;
        }
        
        return array(
            '@type' => 'AggregateRating',
            'ratingValue' => $average_rating,
            'reviewCount' => $review_count,
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }
    
    private function get_detailed_reviews_schema($product) {
        $reviews = get_comments(array(
            'post_id' => $product->get_id(),
            'status' => 'approve',
            'type' => 'review',
            'number' => 5,
            'meta_query' => array(
                array(
                    'key' => 'rating',
                    'value' => 0,
                    'compare' => '>'
                )
            )
        ));
        
        if (empty($reviews)) {
            return null;
        }
        
        $schema_reviews = array();
        
        foreach ($reviews as $review) {
            $rating = get_comment_meta($review->comment_ID, 'rating', true);
            
            if (!$rating) {
                continue;
            }
            
            $schema_reviews[] = array(
                '@type' => 'Review',
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => $rating,
                    'bestRating' => '5',
                    'worstRating' => '1'
                ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => $review->comment_author
                ),
                'reviewBody' => $review->comment_content,
                'datePublished' => get_comment_date('c', $review->comment_ID)
            );
        }
        
        return $schema_reviews;
    }
    
    private function get_site_logo() {
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo) {
                return $logo[0];
            }
        }
        
        return '';
    }
    
    private function get_social_profiles() {
        $profiles = array();
        
        // Get social media URLs from common locations
        $social_fields = array(
            'facebook_url',
            'twitter_url', 
            'instagram_url',
            'linkedin_url',
            'youtube_url'
        );
        
        foreach ($social_fields as $field) {
            $url = get_option($field);
            if (!empty($url)) {
                $profiles[] = $url;
            }
        }
        
        return $profiles;
    }
    
    private function get_store_address_schema() {
        $address_fields = array(
            'street' => get_option('woocommerce_store_address'),
            'city' => get_option('woocommerce_store_city'),
            'state' => get_option('woocommerce_default_state'),
            'postalCode' => get_option('woocommerce_store_postcode'),
            'country' => get_option('woocommerce_default_country')
        );
        
        $address_fields = array_filter($address_fields);
        
        if (empty($address_fields)) {
            return null;
        }
        
        $address = array('@type' => 'PostalAddress');
        
        if (!empty($address_fields['street'])) {
            $address['streetAddress'] = $address_fields['street'];
        }
        
        if (!empty($address_fields['city'])) {
            $address['addressLocality'] = $address_fields['city'];
        }
        
        if (!empty($address_fields['state'])) {
            $address['addressRegion'] = $address_fields['state'];
        }
        
        if (!empty($address_fields['postalCode'])) {
            $address['postalCode'] = $address_fields['postalCode'];
        }
        
        if (!empty($address_fields['country'])) {
            $address['addressCountry'] = $address_fields['country'];
        }
        
        return $address;
    }
    
    // Additional helper methods for enhanced schema
    private function get_manufacturer_schema($product) {
        $manufacturer = get_post_meta($product->get_id(), '_manufacturer', true);
        if (empty($manufacturer)) {
            return null;
        }
        
        return array(
            '@type' => 'Organization',
            'name' => $manufacturer
        );
    }
    
    private function get_product_model($product) {
        return get_post_meta($product->get_id(), '_model', true);
    }
    
    private function get_product_colors($product) {
        $color_attribute = $product->get_attribute('pa_color');
        if (!empty($color_attribute)) {
            return $color_attribute;
        }
        
        return get_post_meta($product->get_id(), '_color', true);
    }
    
    private function get_product_sizes($product) {
        $size_attribute = $product->get_attribute('pa_size');
        if (!empty($size_attribute)) {
            return $size_attribute;
        }
        
        return get_post_meta($product->get_id(), '_size', true);
    }
    
    private function get_product_materials($product) {
        $material_attribute = $product->get_attribute('pa_material');
        if (!empty($material_attribute)) {
            return $material_attribute;
        }
        
        return get_post_meta($product->get_id(), '_material', true);
    }
    
    private function get_product_specifications($product) {
        $specifications = array();
        
        // Get all product attributes
        $attributes = $product->get_attributes();
        
        foreach ($attributes as $attribute) {
            if ($attribute->is_taxonomy()) {
                $terms = get_the_terms($product->get_id(), $attribute->get_name());
                if ($terms && !is_wp_error($terms)) {
                    $specifications[] = array(
                        '@type' => 'PropertyValue',
                        'name' => wc_attribute_label($attribute->get_name()),
                        'value' => implode(', ', wp_list_pluck($terms, 'name'))
                    );
                }
            } else {
                $specifications[] = array(
                    '@type' => 'PropertyValue',
                    'name' => $attribute->get_name(),
                    'value' => implode(', ', $attribute->get_options())
                );
            }
        }
        
        return $specifications;
    }
    
    private function get_product_variants($product) {
        if (!$product->is_type('variable')) {
            return null;
        }
        
        $variations = $product->get_children();
        $variants = array();
        
        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;
            
            $variants[] = array(
                '@type' => 'ProductModel',
                'name' => $variation->get_name(),
                'sku' => $variation->get_sku(),
                'url' => get_permalink($variation_id)
            );
        }
        
        return $variants;
    }
    
    private function get_sale_dates($product) {
        $from = get_post_meta($product->get_id(), '_sale_price_dates_from', true);
        $to = get_post_meta($product->get_id(), '_sale_price_dates_to', true);
        
        if (!$from && !$to) {
            return null;
        }
        
        return array(
            'from' => $from ? date('c', $from) : null,
            'to' => $to ? date('c', $to) : null
        );
    }
    
    private function get_shipping_schema($product) {
        // Basic shipping information - can be extended
        $shipping_class = $product->get_shipping_class();
        
        if (empty($shipping_class)) {
            return null;
        }
        
        return array(
            '@type' => 'OfferShippingDetails',
            'shippingRate' => array(
                '@type' => 'MonetaryAmount',
                'currency' => get_woocommerce_currency()
            ),
            'deliveryTime' => array(
                '@type' => 'ShippingDeliveryTime'
            )
        );
    }
    
    private function get_return_policy_schema($product) {
        // Basic return policy - can be customized
        return array(
            '@type' => 'MerchantReturnPolicy',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 30
        );
    }
    
    private function format_variation_attributes($attributes) {
        $formatted = array();
        
        foreach ($attributes as $name => $value) {
            $formatted[] = array(
                '@type' => 'PropertyValue',
                'name' => str_replace('attribute_', '', $name),
                'value' => $value
            );
        }
        
        return $formatted;
    }
    
    private function get_category_schema() {
        $category = get_queried_object();
        
        if (!$category) {
            return array();
        }
        
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $category->description,
            'url' => get_term_link($category)
        );
    }
    
    private function get_shop_schema() {
        $shop_page_id = wc_get_page_id('shop');
        
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => get_the_title($shop_page_id),
            'description' => get_post_meta($shop_page_id, '_yoast_wpseo_metadesc', true) ?: get_bloginfo('description'),
            'url' => get_permalink($shop_page_id)
        );
    }
}