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
 * Batch load swatch data for many terms, cached per taxonomy and product version
 *
 * @param array  $term_ids  Term IDs.
 * @param string $taxonomy  Taxonomy slug.
 * @param int    $product_id Product context for versioned key.
 * @return array<int,array>
 */
function tee_vs_get_term_swatch_data_batch( array $term_ids, string $taxonomy, int $product_id = 0 ): array {
    $term_ids = array_values( array_unique( array_filter( array_map( 'intval', $term_ids ) ) ) );
    if ( empty( $term_ids ) ) {
        return array();
    }

    $cache     = \TEE\Variation_Swatches\Cache_Manager::instance();
    $base_key  = sprintf( 'term_meta_batch_%s_%s', $taxonomy, $product_id > 0 ? (string) $product_id : 'global' );
    $cache_key = $product_id > 0 ? $cache->versioned_key( $base_key, $product_id ) : $base_key;

    $batched = tee_vs_cache_get( $cache_key );
    if ( false === $batched ) {
        global $wpdb; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $batched = array();
        $placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
        $meta_keys    = array( 'tee_vs_swatch_type', 'tee_vs_swatch_color', 'tee_vs_swatch_image_id', 'tee_vs_swatch_description' );
        $meta_ph      = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

        $sql = $wpdb->prepare(
            "SELECT tm.term_id, tm.meta_key, tm.meta_value
             FROM {$wpdb->termmeta} tm
             WHERE tm.term_id IN ($placeholders)
               AND tm.meta_key IN ($meta_ph)",
            array_merge( $term_ids, $meta_keys )
        );
        $rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching

        foreach ( $term_ids as $tid ) {
            $batched[ $tid ] = array(
                'swatch_type'        => '',
                'swatch_color'       => '',
                'swatch_image_id'    => '',
                'swatch_description' => '',
            );
        }

        foreach ( (array) $rows as $row ) {
            $tid = (int) $row->term_id;
            if ( ! isset( $batched[ $tid ] ) ) {
                $batched[ $tid ] = array();
            }
            switch ( $row->meta_key ) {
                case 'tee_vs_swatch_type':
                    $batched[ $tid ]['swatch_type'] = $row->meta_value;
                    break;
                case 'tee_vs_swatch_color':
                    $batched[ $tid ]['swatch_color'] = $row->meta_value;
                    break;
                case 'tee_vs_swatch_image_id':
                    $batched[ $tid ]['swatch_image_id'] = $row->meta_value;
                    break;
                case 'tee_vs_swatch_description':
                    $batched[ $tid ]['swatch_description'] = $row->meta_value;
                    break;
            }
        }

        // Cache 2 hours.
        tee_vs_cache_set( $cache_key, $batched, 2 * HOUR_IN_SECONDS );
    }

    // Return subset for requested IDs.
    $result = array();
    foreach ( $term_ids as $tid ) {
        if ( isset( $batched[ $tid ] ) ) {
            $result[ $tid ] = $batched[ $tid ];
        }
    }
    return $result;
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

