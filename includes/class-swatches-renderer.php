<?php
/**
 * Swatches renderer and stock signal logic
 *
 * @package TEE\Variation_Swatches
 */

namespace TEE\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Swatches_Renderer {
    private static ?Swatches_Renderer $instance = null;

    public static function instance(): Swatches_Renderer {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // Add data attributes to variations form.
        add_action( 'woocommerce_before_variations_form', array( $this, 'before_variations_form' ) );

        // TODO: Integrate templates into attribute rendering if overriding Woo templates in future iteration.
    }

    public function before_variations_form(): void {
        global $product;
        if ( ! $product || ! is_a( $product, '\\WC_Product_Variable' ) ) {
            return;
        }

        $should_check = $this->should_check_stock( $product );
        $attr = sprintf( ' data-trigger-stock-check="%s" data-product-id="%d" ', $should_check ? 'true' : 'false', (int) $product->get_id() );

        // Output attributes via small script to attach to the existing form without template overrides.
        echo '<script>(function(){var f=document.querySelector("form.variations_form"); if(f){f.setAttribute("data-trigger-stock-check","' . ( $should_check ? 'true' : 'false' ) . '"); f.setAttribute("data-product-id","' . (int) $product->get_id() . '");}})();</script>';
    }

    public function should_check_stock( \WC_Product $product ): bool { // phpcs:ignore
        $product_id = (int) $product->get_id();
        $cache      = Cache_Manager::instance();
        $key        = $cache->versioned_key( 'variation_stock_' . $product_id, $product_id );
        $cached     = $cache->get( $key );
        if ( false !== $cached ) {
            return (bool) $cached;
        }

        $threshold = (int) get_option( 'tee_vs_low_stock_threshold', 10 );
        $should    = false;

        if ( $product->is_type( 'variable' ) ) {
            $children = $product->get_children();
            foreach ( $children as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( ! $variation || ! $variation->managing_stock() ) {
                    $should = true; // No stock management -> need runtime stock checks.
                    break;
                }
                $qty = (int) $variation->get_stock_quantity();
                if ( $qty <= $threshold ) {
                    $should = true;
                    break;
                }
            }
        }

        $cache->set( $key, $should, HOUR_IN_SECONDS );
        return $should;
    }
}
