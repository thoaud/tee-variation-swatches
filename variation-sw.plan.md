# WooCommerce Variation Swatches Plugin - Implementation Plan

## Core Architecture

**Plugin Structure:**

```
tee-variation-swatches/
├── tee-variation-swatches.php
├── includes/
│   ├── class-plugin.php
│   ├── class-assets-manager.php  
│   ├── class-swatches-renderer.php
│   ├── class-swatches-api.php
│   ├── class-cache-manager.php (WordPress object cache)
│   ├── class-admin-settings.php
│   └── helpers.php
├── assets/
│   ├── css/ (Tailwind compiled + admin)
│   └── js/ (frontend + admin)
├── templates/ (swatch type templates)
└── package.json, tailwind.config.js, postcss.config.js
```

## 1. Object Cache Support - REQUIRED

**File: `includes/class-cache-manager.php`**

Full WordPress object cache API support (Redis/Memcached compatible):

```php
class Cache_Manager {
    const GROUP = 'tee_variation_swatches';
    
    public function get($key, $default = false) {
        return wp_cache_get($key, self::GROUP);
    }
    
    public function set($key, $value, $expiration = HOUR_IN_SECONDS) {
        return wp_cache_set($key, $value, self::GROUP, $expiration);
    }
    
    public function flush_product($product_id) {
        $this->delete("variation_stock_{$product_id}");
        $this->delete("variation_images_{$product_id}");
    }
}
```

**Cache Keys:**

- `variation_stock_{product_id}` - Stock + availability data (1 hour)
- `variation_images_{product_id}` - Variation images (12 hours)
- `term_meta_{term_id}` - Swatch color/image/description (12 hours)

**Invalidation:** Hook `woocommerce_variation_set_stock`, `edited_term`, `updated_post_meta`

## 2. Tailwind CSS Integration

Based on `tee-acf-blocks`:

- `package.json` with tailwindcss, @tailwindcss/forms, autoprefixer
- `tailwind.config.js`: content paths, safelist, `preflight: false`, Blocksy colors
- Compile: `npm run build` → `assets/css/tailwind.css`
- Custom components: `.tee-swatch-base`, `.tee-swatch-active`, `.tee-swatch-disabled`, `.tee-swatch-unavailable`

## 3. Swatch Types

### Color Swatch

- Circular/square color block
- Border for light colors
- **States:** active (border/checkmark), disabled (grayscale), unavailable (opacity + cursor-not-allowed)

### Image Swatch

- Thumbnail (32x32, 48x48, 64x64)
- Lazy loading
- **States:** active (border), disabled (grayscale filter), unavailable (opacity 0.4 + no-pointer)

### Image (Variation) Swatch

- Uses variation's own image
- Lazy load via AJAX (cached)

### Button Swatch

- Text in bordered button
- **States:** active (filled), disabled (grayed), unavailable (faded + no-click)

### Button with Description

- Two lines: label + description
- Description from term meta `swatch_description`
- Same states as button

### Dropdown

- Styled native dropdown (Tailwind forms)

**Template Files:**

- `templates/color-swatch.php`
- `templates/image-swatch.php`
- `templates/image-variation-swatch.php`
- `templates/button-swatch.php`
- `templates/button-description-swatch.php`
- `templates/dropdown.php`

## 4. Unavailable Option Styling - CRITICAL

**Behavior:**

When user selects Size: S, and Red is not available in Size S:

- Red swatch gets `.tee-swatch-unavailable` class
- Visual: Reduced opacity, crossed-out, or faded styling
- Interaction: `pointer-events: none` or disabled state
- Different from out-of-stock (which is `.tee-swatch-disabled`)

**Implementation:**

Frontend JS listens to WooCommerce variation events:

```javascript
$('.variations_form').on('woocommerce_update_variation_values', function() {
    // Get available combinations from WooCommerce data
    // Mark unavailable swatches with .tee-swatch-unavailable
    // Mark out-of-stock swatches with .tee-swatch-disabled
});
```

**CSS Styling:**

```css
.tee-swatch-disabled {
    /* Out of stock */
    filter: grayscale(1);
    opacity: 0.5;
    cursor: not-allowed;
}

.tee-swatch-unavailable {
    /* Not available in current selection */
    opacity: 0.3;
    pointer-events: none;
    position: relative;
}

.tee-swatch-unavailable::after {
    /* Optional diagonal line */
    content: '';
    position: absolute;
    /* diagonal strike-through */
}
```

## 5. Renderer & Stock Check Logic

**File: `includes/class-swatches-renderer.php`**

```php
class Swatches_Renderer {
    private function should_check_stock($product) {
        // Check object cache first
        $cache_key = "variation_stock_{$product->get_id()}";
        $stock_data = tee_vs_cache_get($cache_key);
        
        if ($stock_data === false) {
            // Single query for all variation stock levels
            $stock_data = $this->query_variation_stock($product);
            tee_vs_cache_set($cache_key, $stock_data, HOUR_IN_SECONDS);
        }
        
        // Return true if ANY variation has stock < threshold
        $threshold = get_option('tee_vs_low_stock_threshold', 10);
        foreach ($stock_data as $var) {
            if (!$var['manages_stock'] || $var['stock'] < $threshold) {
                return true;
            }
        }
        return false;
    }
    
    public function add_data_attributes($product) {
        $check_stock = $this->should_check_stock($product);
        echo 'data-trigger-stock-check="' . ($check_stock ? 'true' : 'false') . '" ';
        echo 'data-product-id="' . esc_attr($product->get_id()) . '"';
    }
}
```

**Data Attributes on Form:**

```html
<form class="variations_form" 
      data-trigger-stock-check="true"
      data-product-id="123">
```

## 6. Frontend JavaScript

**File: `assets/js/frontend.js`**

### Core Logic

```javascript
class TEEVariationSwatches {
    init() {
        this.bindSwatchClicks();
        this.setupVariationListeners();
        this.conditionalStockCheck();
    }
    
    bindSwatchClicks() {
        // Click swatch → update hidden select → trigger WC variation check
    }
    
    setupVariationListeners() {
        $('.variations_form').on('woocommerce_update_variation_values', (e) => {
            this.updateUnavailableStates(e.target);
        });
    }
    
    updateUnavailableStates(form) {
        // Get available variations from WC data
        // Loop through swatches
        // Add .tee-swatch-unavailable to incompatible options
        // Remove from compatible options
    }
    
    conditionalStockCheck() {
        const shouldCheck = form.dataset.triggerStockCheck === 'true';
        if (!shouldCheck) return; // Skip AJAX
        
        const productId = form.dataset.productId;
        this.fetchStockData(productId); // Cached in backend
    }
    
    fetchStockData(productId) {
        // Check sessionStorage first
        const cached = sessionStorage.getItem(`stock_${productId}`);
        if (cached) return JSON.parse(cached);
        
        // AJAX to backend (hits object cache)
        // Store in sessionStorage
        // Apply .tee-swatch-disabled to out-of-stock
    }
}
```

## 7. AJAX API

**File: `includes/class-swatches-api.php`**

### Get Stock Variations

```php
public function get_stock_variations() {
    $product_id = intval($_POST['product_id']);
    
    // Try object cache (Redis/Memcached)
    $cache_key = "variation_stock_{$product_id}";
    $cached = tee_vs_cache_get($cache_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
    }
    
    // Single optimized SQL query
    global $wpdb;
    $results = $wpdb->get_results("...");
    
    // Format: [variation_id => [attributes, stock_status, stock_quantity]]
    $data = $this->format_stock_data($results);
    
    // Cache 1 hour
    tee_vs_cache_set($cache_key, $data, HOUR_IN_SECONDS);
    
    wp_send_json_success($data);
}
```

**Trigger Logic:**

- Only called if `data-trigger-stock-check="true"`
- True = ANY variation has stock < threshold at page load
- False = All variations well-stocked, skip AJAX entirely

### Get Variation Images

Same pattern: check cache → query → cache → return

## 8. Admin Settings

### Global Settings (WooCommerce > Settings > Swatches)

- Default swatch type
- Image size (32x32, 48x48, 64x64)
- Shape (circle, square, rounded)
- Tooltip (on/off, delay)
- Out-of-stock behavior (disable, hide, fade)
- **Unavailable styling** (opacity, strikethrough, custom CSS)
- **Low Stock Threshold** (default: 10)
- Cache flush button

### Term Settings (Edit Attribute Term)

- Color picker (for color type)
- Image uploader (for image type)
- **Description** (for button-description type)
- Preview

### Product Settings (Product Data > Swatches)

- Enable/disable per product
- Per-attribute overrides

## 9. Performance Checklist

### Caching

- ✅ WordPress object cache API (Redis/Memcached support)
- ✅ Persistent cache across users/requests
- ✅ Auto-invalidation on stock changes
- ✅ Frontend sessionStorage (same-page)
- ✅ Backend object cache (cross-page)

### Database

- ✅ Single query for variation stock (not per-variation loops)
- ✅ Term meta (not options table)
- ✅ Cache-first: check cache before every query

### Frontend

- ✅ Conditional stock AJAX (skip if all well-stocked)
- ✅ Event delegation for clicks
- ✅ Unavailable states via WC events (no extra queries)
- ✅ Lazy load variation images
- ✅ Debounced AJAX

### Assets

- ✅ Conditional enqueue (only pages with variable products)
- ✅ Defer JS loading
- ✅ Minified CSS
- ✅ File hash versioning

## 10. Implementation Order

1. Core plugin + cache manager
2. Tailwind setup
3. Admin settings (global + term fields)
4. Renderer with stock check logic
5. Basic swatch types (color, button) with all 3 states
6. Frontend JS: clicks + unavailable states
7. AJAX API with object cache
8. Image swatches
9. Variation image + button-description types
10. Asset manager conditional loading
11. Cache invalidation hooks
12. Testing: unavailable states, stock threshold, cache

## Critical Success Factors

1. **Unavailable vs Disabled States** - Clear visual distinction between out-of-stock and incompatible options
2. **Object Cache** - Full Redis/Memcached support, cache-first architecture
3. **Variation Stock Check** - Check variation levels (not parent), trigger AJAX only if low stock
4. **Simplified Data Attributes** - Just `data-trigger-stock-check` and `data-product-id`
5. **WooCommerce Integration** - Use native variation events for unavailable state updates
6. **Performance** - 80%+ requests skip AJAX, 100% cache hit rate with Redis