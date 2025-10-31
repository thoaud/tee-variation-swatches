<?php
/**
 * Cache Manager with per-product versioning
 *
 * @package TEE\Variation_Swatches
 */

namespace TEE\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cache_Manager {
    public const GROUP = 'tee_variation_swatches';

    private static ?Cache_Manager $instance = null;

    public static function instance(): Cache_Manager {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->register_invalidation_hooks();
        }
        return self::$instance;
    }

    public function get( string $key, $default = false ) {
        $value = wp_cache_get( $key, self::GROUP );
        return ( false === $value ) ? $default : $value;
    }

    public function set( string $key, $value, int $expiration = HOUR_IN_SECONDS ): bool {
        return wp_cache_set( $key, $value, self::GROUP, $expiration );
    }

    public function delete( string $key ): bool {
        return wp_cache_delete( $key, self::GROUP );
    }

    public function flush_all(): void {
        // Best effort group flush by bumping a global nonce.
        $nonce = (int) wp_cache_get( 'global_nonce', self::GROUP );
        wp_cache_set( 'global_nonce', $nonce + 1, self::GROUP, 12 * HOUR_IN_SECONDS );
    }

    public function get_product_version( int $product_id ): int {
        $v = wp_cache_get( "version_{$product_id}", self::GROUP );
        return is_int( $v ) ? $v : 1;
    }

    public function bump_product_version( int $product_id ): void {
        $v = $this->get_product_version( $product_id ) + 1;
        wp_cache_set( "version_{$product_id}", $v, self::GROUP, 12 * HOUR_IN_SECONDS );
    }

    public function versioned_key( string $base_key, int $product_id ): string {
        $v = $this->get_product_version( $product_id );
        return sprintf( '%s_v%d', $base_key, $v );
    }

    private function register_invalidation_hooks(): void {
        // Stock and status changes on products and variations.
        add_action( 'woocommerce_product_set_stock', array( $this, 'on_stock_change' ) );
        add_action( 'woocommerce_variation_set_stock_status', array( $this, 'on_stock_change' ) );

        // Relevant post meta updates: stock, stock_status, thumbnail.
        add_action( 'updated_post_meta', array( $this, 'on_post_meta_update' ), 10, 4 );
        add_action( 'added_post_meta', array( $this, 'on_post_meta_update' ), 10, 4 );
        add_action( 'deleted_post_meta', array( $this, 'on_post_meta_update' ), 10, 4 );

        // Attribute term edits (narrowed hooks are preferable if registered by WC/attributes).
        add_action( 'edited_terms', array( $this, 'on_terms_edited' ), 10, 2 );
    }

    public function on_stock_change( $wc_stock ): void {
        if ( method_exists( $wc_stock, 'get_product_id' ) ) {
            $product_id = (int) $wc_stock->get_product_id();
            if ( $product_id > 0 ) {
                $this->bump_product_version( $product_id );
            }
        }
    }

    public function on_post_meta_update( $meta_id, $object_id, $meta_key, $_meta_value ): void { // phpcs:ignore
        $relevant = array( '_stock', '_stock_status', '_thumbnail_id' );
        if ( in_array( $meta_key, $relevant, true ) ) {
            $product_id = (int) $object_id;
            $this->bump_product_version( $product_id );
        }
    }

    public function on_terms_edited( $term_ids, $taxonomy ): void { // phpcs:ignore
        // Mark a lightweight stale flag per term; readers should refresh lazily.
        if ( is_array( $term_ids ) ) {
            foreach ( $term_ids as $term_id ) {
                wp_cache_set( "term_meta_stale_{$term_id}", time(), self::GROUP, 2 * HOUR_IN_SECONDS );
            }
        }
    }
}
