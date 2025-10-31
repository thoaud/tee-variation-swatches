<?php
/**
 * Helper Functions
 *
 * @package TEE\Variation_Swatches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get data from object cache
 *
 * @param string $key Cache key.
 * @param mixed  $default Default value if not found.
 * @return mixed
 */
function tee_vs_cache_get( $key, $default = false ) {
	return \TEE\Variation_Swatches\Cache_Manager::instance()->get( $key, $default );
}

/**
 * Set data in object cache
 *
 * @param string $key Cache key.
 * @param mixed  $value Value to cache.
 * @param int    $expiration Expiration time in seconds.
 * @return bool
 */
function tee_vs_cache_set( $key, $value, $expiration = HOUR_IN_SECONDS ) {
	return \TEE\Variation_Swatches\Cache_Manager::instance()->set( $key, $value, $expiration );
}

/**
 * Delete data from object cache
 *
 * @param string $key Cache key.
 * @return bool
 */
function tee_vs_cache_delete( $key ) {
	return \TEE\Variation_Swatches\Cache_Manager::instance()->delete( $key );
}

/**
 * Debug log helper
 *
 * @param string $message Log message.
 * @param mixed  $data Optional data to log.
 */
function tee_vs_debug_log( $message, $data = null ) {
	if ( ! TEE_VS_DEBUG ) {
		return;
	}

	$log_message = sprintf( '[TEE Variation Swatches] %s', $message );

	if ( null !== $data ) {
		$log_message .= ' | Data: ' . wp_json_encode( $data );
	}

	error_log( $log_message );
}

/**
 * Get term swatch data (cached)
 *
 * @param int $term_id Term ID.
 * @return array
 */
function tee_vs_get_term_swatch_data( $term_id ) {
	$cache_key = "term_meta_{$term_id}";
	$cached    = tee_vs_cache_get( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$data = array(
		'swatch_type'        => get_term_meta( $term_id, 'tee_vs_swatch_type', true ),
		'swatch_color'       => get_term_meta( $term_id, 'tee_vs_swatch_color', true ),
		'swatch_image_id'    => get_term_meta( $term_id, 'tee_vs_swatch_image_id', true ),
		'swatch_description' => get_term_meta( $term_id, 'tee_vs_swatch_description', true ),
	);

	// Cache for 12 hours.
	tee_vs_cache_set( $cache_key, $data, 12 * HOUR_IN_SECONDS );

	return $data;
}

/**
 * Sanitize swatch type
 *
 * @param string $type Swatch type.
 * @return string
 */
function tee_vs_sanitize_swatch_type( $type ) {
	$valid_types = array( 'color', 'image', 'image-variation', 'button', 'button-description', 'dropdown' );
	return in_array( $type, $valid_types, true ) ? $type : 'button';
}

/**
 * Get swatch template path
 *
 * @param string $type Swatch type.
 * @return string
 */
function tee_vs_get_template_path( $type ) {
	$type     = tee_vs_sanitize_swatch_type( $type );
	$template = TEE_VS_PATH . "templates/{$type}-swatch.php";

	if ( ! file_exists( $template ) ) {
		$template = TEE_VS_PATH . 'templates/button-swatch.php';
	}

	return $template;
}

