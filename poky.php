<?php

/**
 * Plugin Name: POKY - Product Importer
 * Plugin URI: https://poky.app
 * Description: Use POKY Plugin to import products from multi Platforms to your store with one Click.
 * Version: 2.2.0
 * Author: POKY
 * Author URI: https://poky.app
 * Requires at least: 4.4
 * Tested up to: 6.6.2
 * WC requires at least: 2.2
 * WC tested up to: 9.3.3
 * @package Poky
 * @category Products
 * @author Poky
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Check if WooCommerce is active
 **/

require_once('includes/poky-import.php');
$PokyImport = new PokyImport;

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // Define WC_PLUGIN_FILE.
    if (!defined('POKY_PLUGIN_FILE')) {
        define('POKY_PLUGIN_FILE', __FILE__);
    }

    // Include the main PokyApp class.
    if (!class_exists('PokyApp')) {
        include_once dirname( __FILE__ ) . '/includes/class-poky.php';
    }

    register_uninstall_hook( __FILE__, 'poky_plugin_uninstall' );


    function poky_plugin_uninstall() {

        $url = POKY_APP_URL.'uninstall';

        $data['poky_token'] = get_option( 'poky_token');
        $data['email'] =  get_option( 'admin_email' );

        $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'body'   => $data,
            )
        );
    }

    add_filter( 'plugin_row_meta', 'poky_plugin_row_meta', 10, 2 );

    function poky_plugin_row_meta( $links, $file ) {

        if ( plugin_basename( __FILE__ ) == $file ) {
            unset($links[2]);

            if (get_option( 'poky_token') != "") {
                $poky_token =  get_option( 'poky_token');
                $pokyDashboardUrl = POKY_APP_URL."?key=".$poky_token;
                $row_meta = array(
                    'pokydashboard'    => '<a href="' . esc_url( $pokyDashboardUrl ) . '" target="_blank" aria-label="' . esc_attr__( 'Plugin Additional Links', 'domain' ) . '" style="color:green;">' . esc_html__( 'Dashboard', 'domain' ) . '</a>'
                );
                return array_merge( $links, $row_meta );
            }
        }
        return (array) $links;
    }

    PokyApp::instance();

}


add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );