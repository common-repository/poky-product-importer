<?php
/**
 * Poky Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @author      Poky
 * @category    Core
 * @package     Poky/Functions
 * @version     0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function poky_maybe_define_constant( $name, $value ) {
    if ( ! defined( $name ) ) {
        define( $name, $value );
    }
}

add_action('admin_menu', 'poky_dashboard');
function poky_dashboard() {
    global $submenu;

    if (get_option( 'poky_token') != "") {
        $poky_token =  get_option( 'poky_token');
        $pokyDashboardUrl = POKY_APP_URL."?key=".$poky_token;
        $submenu['woocommerce'][] = array(
            '<div id="pokyDashboard">POKY</div>', 'manage_options', $pokyDashboardUrl);
    }
}

add_action( 'admin_footer', 'poky_dashboard_blank' );
function poky_dashboard_blank()
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#pokyDashboard').parent().attr('target','_blank');

        });
    </script>
    <?php
}

add_action( 'rest_api_init', function ( $server ) {
    $server->register_route( 'poky', '/poky', array(
        'methods'  => 'GET',
        'callback' => function () {
            return '813';
        },
    ) );
} );


add_action( 'wp_ajax_poky_create_product', 'poky_create_product' );
add_action( 'wp_ajax_nopriv_poky_create_product', 'poky_create_product' );

function poky_create_product() {
    global $PokyImport;

    $poky_token =  get_option( 'poky_token');
    if ($_POST['pokyToken']===$poky_token) {
        $product=json_decode(stripslashes($_POST['product']), true);
            $resp=$PokyImport->insert_product( $product );

            $permalink='';

            if ($resp)
                $permalink=get_permalink($resp);
            echo $permalink;
    }
    exit();
}


add_action( 'wp_ajax_poky_load_products', 'poky_load_products' );
add_action( 'wp_ajax_nopriv_poky_load_products', 'poky_load_products' );

function poky_load_products() {
    $poky_token = get_option('poky_token');
    $productsData = array();

    if ($_POST['pokyToken'] === $poky_token) {
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
        foreach ($products as $product) {
            $title = $product->get_title();
            $image_id = $product->get_image_id();

            if (!$image_id) {
                $attachment_ids = $product->get_gallery_image_ids();
                if (sizeof($attachment_ids) > 0) {
                    $image_id = reset($attachment_ids);
                }
            }

            if ($image_id) {
                $image_url = wp_get_attachment_image_src($image_id, 'full')[0];
                $productsData[] = array('title' => $title, 'image' => $image_url);
            }
        }
    }

    echo json_encode($productsData);
    exit();
}