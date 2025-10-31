<?php
/**
 * Assets manager
 *
 * @package TEE\Variation_Swatches
 */

namespace TEE\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Assets_Manager {
    private static ?Assets_Manager $instance = null;

    public static function instance(): Assets_Manager {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function enqueue_frontend(): void {
        $suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $version  = defined( 'TEE_VS_VERSION' ) ? TEE_VS_VERSION : '1.0.0';
        $css_path = TEE_VS_URL . 'assets/css/tailwind.css';
        $js_path  = TEE_VS_URL . 'assets/js/frontend' . $suffix . '.js';

        // CSS
        wp_register_style( 'tee-vs-frontend', $css_path, array(), $version );
        wp_enqueue_style( 'tee-vs-frontend' );

        // JS
        wp_register_script( 'tee-vs-frontend', $js_path, array( 'jquery' ), $version, true );
        wp_enqueue_script( 'tee-vs-frontend' );
    }

    public function enqueue_admin(): void {
        $version  = defined( 'TEE_VS_VERSION' ) ? TEE_VS_VERSION : '1.0.0';
        $js_path  = TEE_VS_URL . 'assets/js/admin.js';
        wp_register_script( 'tee-vs-admin', $js_path, array( 'jquery' ), $version, true );
        wp_enqueue_script( 'tee-vs-admin' );
    }
}
