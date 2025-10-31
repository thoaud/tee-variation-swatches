<?php
/**
 * Main Plugin bootstrap class
 *
 * @package TEE\Variation_Swatches
 */

namespace TEE\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin singleton
 */
class Plugin {
    /**
     * Instance
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Get instance
     */
    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init hooks
     */
    public function init(): void {
        // Ensure WooCommerce present.
        if ( ! class_exists( '\\WooCommerce' ) ) {
            return;
        }

        // Initialize core services early.
        Cache_Manager::instance();

        // Frontend renderer.
        add_action( 'init', array( $this, 'register_hooks' ) );
    }

    /**
     * Register WP hooks
     */
    public function register_hooks(): void {
        // Assets.
        add_action( 'wp_enqueue_scripts', array( Assets_Manager::instance(), 'enqueue_frontend' ) );

        // Admin assets and settings.
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( Assets_Manager::instance(), 'enqueue_admin' ) );
            Admin_Settings::instance()->init();
        }

        // Rendering integration with Woo templates.
        Swatches_Renderer::instance()->init();

        // AJAX endpoints.
        Swatches_API::instance()->register_routes();
    }
}
