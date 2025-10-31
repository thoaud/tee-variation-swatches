<?php
/**
 * AJAX API for stock and variation images metadata
 *
 * @package TEE\Variation_Swatches
 */

namespace TEE\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Swatches_API {
    private static ?Swatches_API $instance = null;

    public static function instance(): Swatches_API {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_routes(): void {
        add_action( 'wp_ajax_tee_vs_get_stock', array( $this, 'get_stock_variations' ) );
        add_action( 'wp_ajax_nopriv_tee_vs_get_stock', array( $this, 'get_stock_variations' ) );

        add_action( 'wp_ajax_tee_vs_get_images_meta', array( $this, 'get_variation_images_meta' ) );
        add_action( 'wp_ajax_nopriv_tee_vs_get_images_meta', array( $this, 'get_variation_images_meta' ) );
    }

    public function get_stock_variations(): void {
        $product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0; // phpcs:ignore
        if ( $product_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ), 400 );
        }

        $cache   = Cache_Manager::instance();
        $key     = $cache->versioned_key( 'variation_stock_' . $product_id, $product_id );
        $payload = $cache->get( $key );
        if ( false === $payload ) {
            $payload = $this->build_stock_payload( $product_id );
            $cache->set( $key, $payload, HOUR_IN_SECONDS );
        }

        wp_send_json_success( $payload );
    }

    private function build_stock_payload( int $product_id ): array {
        $product = wc_get_product( $product_id );
        $result  = array();
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return $result;
        }
        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation ) {
                continue;
            }
            $result[ $variation_id ] = array(
                'attributes'      => $variation->get_attributes(),
                'stock_status'    => $variation->get_stock_status(),
                'stock_quantity'  => $variation->get_stock_quantity(),
            );
        }
        return $result;
    }

    public function get_variation_images_meta(): void {
        $product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0; // phpcs:ignore
        if ( $product_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ), 400 );
        }

        $cache   = Cache_Manager::instance();
        $key     = $cache->versioned_key( 'variation_images_meta_' . $product_id, $product_id );
        $payload = $cache->get( $key );
        if ( false === $payload ) {
            $payload = $this->build_images_meta_payload( $product_id );
            $cache->set( $key, $payload, 12 * HOUR_IN_SECONDS );
        }

        wp_send_json_success( $payload );
    }

    private function build_images_meta_payload( int $product_id ): array {
        $product = wc_get_product( $product_id );
        $result  = array();
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return $result;
        }
        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation ) {
                continue;
            }
            $image_id     = $variation->get_image_id();
            $image_src    = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
            $result[ $variation_id ] = array(
                'image_id'  => $image_id,
                'image_url' => $image_src,
            );
        }
        return $result;
    }
}
