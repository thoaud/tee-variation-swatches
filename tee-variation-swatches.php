<?php
/**
 * Plugin Name: TEE Variation Swatches
 * Plugin URI: https://github.com/yourusername/tee-variation-swatches
 * Description: High-performance WooCommerce variation swatches with Tailwind CSS styling and object cache support
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: TEE Development Team
 * Author URI: mailto:phpdevsec@proton.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tee-variation-swatches
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * @package TEE\Variation_Swatches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'TEE_VS_VERSION', '1.0.0' );
define( 'TEE_VS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TEE_VS_URL', plugin_dir_url( __FILE__ ) );
define( 'TEE_VS_BASENAME', plugin_basename( __FILE__ ) );
define( 'TEE_VS_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

// Check for WooCommerce.
if ( ! class_exists( 'WooCommerce' ) ) {
	add_action( 'admin_notices', 'tee_vs_woocommerce_notice' );
	return;
}

/**
 * Admin notice when WooCommerce is not active
 */
function tee_vs_woocommerce_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: Plugin name */
				esc_html__( '%s requires WooCommerce to be installed and activated.', 'tee-variation-swatches' ),
				'<strong>TEE Variation Swatches</strong>'
			);
			?>
		</p>
	</div>
	<?php
}

// Include helper functions.
require_once TEE_VS_PATH . 'includes/helpers.php';

// Autoload classes.
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'TEE\\Variation_Swatches\\';
		$base_dir = TEE_VS_PATH . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . 'class-' . str_replace( '\\', '/', strtolower( str_replace( '_', '-', $relative_class ) ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Set up cache group for multisite support.
if ( function_exists( 'wp_cache_add_global_groups' ) ) {
	wp_cache_add_global_groups( array( 'tee_variation_swatches' ) );
}

/**
 * Get main plugin instance
 *
 * @return \TEE\Variation_Swatches\Plugin
 */
function TEE_Variation_Swatches() {
	return \TEE\Variation_Swatches\Plugin::instance();
}

// Initialize plugin.
add_action( 'plugins_loaded', array( TEE_Variation_Swatches(), 'init' ), 10 );

// Activation hook.
register_activation_hook( __FILE__, 'tee_vs_activate' );

/**
 * Plugin activation
 */
function tee_vs_activate() {
	// Check for WooCommerce.
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'This plugin requires WooCommerce to be installed and activated.', 'tee-variation-swatches' ),
			esc_html__( 'Plugin Activation Error', 'tee-variation-swatches' ),
			array( 'back_link' => true )
		);
	}

	// Check PHP version.
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'This plugin requires PHP 8.0 or higher.', 'tee-variation-swatches' ),
			esc_html__( 'Plugin Activation Error', 'tee-variation-swatches' ),
			array( 'back_link' => true )
		);
	}

	// Set default options.
	add_option( 'tee_vs_low_stock_threshold', 10 );
	add_option( 'tee_vs_default_swatch_type', 'button' );
	add_option( 'tee_vs_swatch_size', '48' );
	add_option( 'tee_vs_swatch_shape', 'rounded' );
}

// Deactivation hook.
register_deactivation_hook( __FILE__, 'tee_vs_deactivate' );

/**
 * Plugin deactivation
 */
function tee_vs_deactivate() {
	// Clear all caches.
	if ( class_exists( '\TEE\Variation_Swatches\Cache_Manager' ) ) {
		$cache_manager = \TEE\Variation_Swatches\Cache_Manager::instance();
		$cache_manager->flush_all();
	}
}

