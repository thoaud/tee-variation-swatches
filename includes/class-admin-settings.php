<?php
/**
 * Admin Settings
 *
 * @package TEE\Variation_Swatches
 */

namespace TEE\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Settings {
    private static ?Admin_Settings $instance = null;

    public static function instance(): Admin_Settings {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
    }

    public function add_settings_page( array $pages ): array {
        $pages[] = new class() extends \WC_Settings_Page {
            public function __construct() {
                $this->id    = 'tee_vs';
                $this->label = __( 'Swatches', 'tee-variation-swatches' );
                parent::__construct();
            }
            public function get_settings() {
                $settings   = array();
                $settings[] = array(
                    'title' => __( 'Variation Swatches', 'tee-variation-swatches' ),
                    'type'  => 'title',
                    'id'    => 'tee_vs_section_start',
                );
                $settings[] = array(
                    'title'   => __( 'Low Stock Threshold', 'tee-variation-swatches' ),
                    'id'      => 'tee_vs_low_stock_threshold',
                    'default' => '10',
                    'type'    => 'number',
                );
                $settings[] = array( 'type' => 'sectionend', 'id' => 'tee_vs_section_end' );
                return $settings;
            }
        };
        return $pages;
    }
}
