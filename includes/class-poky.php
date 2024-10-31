<?php

/**
 * Poky setup
 *
 * @author   Poky
 * @category API
 * @package  Poky
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main Poky Class.
 *
 * @class PokyApp
 * @version 0.0.1
 */
final class PokyApp {

    protected static $_instance = null;
    /**
     * PokyApp version.
     *
     * @var string
     */
    public $version = '0.0.1';

    /**
     * PokyApp Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }


    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Hook into actions and filters.
     *
     */
    private function init_hooks() {
        register_activation_hook( POKY_PLUGIN_FILE, array( 'Poky_Install', 'install' ) );
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        include_once( POKY_ABSPATH . 'includes/poky-core-functions.php' );
        include_once( POKY_ABSPATH . 'includes/class-poky-install.php' );
    }

    private function define_constants() {
        $this->define( 'POKY_ABSPATH', dirname( POKY_PLUGIN_FILE ) . '/' );
        $this->define( 'POKY_APP_URL', 'https://woo.poky.app/' );
    }

    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }
}