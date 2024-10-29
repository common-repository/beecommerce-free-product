<?php
/**
* Plugin Name: Beecommerce 3+1 Free Product
* Plugin URI: http://beecommerce.pl/do-pobrania/
* Description: Plugin adds ability to create automatic coupons with additional features.
* Version: 1.1
* Author: Beecommerce.pl team
* Author URI: http://beecommerce.pl/
* License: GPL2

* Beecommerce 3+1 Free Product is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* any later version.
* Beecommerce 3+1 Free Product is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
* You should have received a copy of the GNU General Public License
* along with Beecommerce 3+1 Free Product. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
*/



// enalble error reporting
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// detecting paths and url inside plugin folder
 if ( ! defined( 'ABSPATH' ) ) exit;
define('BFP_FREE_PRODUCT_VERSION', '1.0');
define('BFP_FREE_PRODUCT_DIR', plugin_dir_path(__FILE__));
define('BFP_FREE_PRODUCT_URL', plugin_dir_url(__FILE__));


//activate plugin and create custom table
function bfp_free_product_activation() {
    //actions to perform once on plugin activation

    //register uninstaller
    register_uninstall_hook(__FILE__, 'bfp_free_product_uninstall');
}

//deactivate plugin
function bfp_free_product_deactivation() {
	// actions to perform once on plugin deactivation go here
}

//uninstall plugin
function bfp_free_product_uninstall(){
  //actions to perform once on plugin uninstall go here
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 

if(is_plugin_active('woocommerce/woocommerce.php')) { 
    require_once('includes/free-coupon-front.php');
    require_once('includes/admin/free-coupon-admin.php');

    function bfp_admin_load_scripts() {
        wp_enqueue_script('free-product-js', plugins_url('includes/admin/js/free-product.js', __FILE__));
    }
    add_action('admin_enqueue_scripts', 'bfp_admin_load_scripts');

    function bfp_front_enqueue_scripts() { 
        wp_enqueue_script('front-free-product-js', plugins_url('js/free-product.js', __FILE__)); 
        wp_enqueue_style('front-free-product-css', plugins_url('css/free-product.css', __FILE__));
    }
    add_action('wp_enqueue_scripts', 'bfp_front_enqueue_scripts');

    if(is_admin()) { 
        $free_coupon_admin = new BFP_Free_Coupon_Admin();
    }
    $free_coupon_front = new BFP_Free_Coupon_Front();
}
?>
