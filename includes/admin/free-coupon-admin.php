<?php

defined('ABSPATH') or die();

if(class_exists('BFP_Free_Coupon_Admin')) {
    return;
}

class BFP_Free_Coupon_Admin {
    public function __construct() {
        $this->init_admin_hook();
    }

    public function init_admin_hook() {
        add_filter('woocommerce_coupon_discount_types', array($this, 'custom_discount_type'), 10, 1);
        add_filter('woocommerce_coupon_data_tabs', array($this, 'custom_coupon_options_tabs'), 10, 1);
        add_action('woocommerce_coupon_data_panels', array($this, 'custom_coupon_data_panels'), 10, 1);
        add_action('woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 10, 2 );
    }

    public function custom_discount_type($discount_types) {
        $discount_types[FREE_PRODUCT_COUPON_TYPE] = __('Free Product', 'woocommerce');
        return $discount_types;
    }

    public function custom_coupon_options_tabs($tabs) {
        $tabs['custom_coupon_products'] = array(
            'label' => __('Free Product settings', 'woocommerce-free-coupons'),
            'target' => 'free_products_coupondata_products',
            'class' => 'free_products_coupondata_products'
        );

        return $tabs;
    }

    public function custom_coupon_data_panels() {
        global $thepostid, $post;
        $thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
        ?>
            <div id="free_products_coupondata_products" class="panel woocommerce_options_panel">
                <?php
                woocommerce_wp_select(array(
                        'id' => '_free_products_type',
                        'label' => __('Type of discount', 'woocommerce'),
                        'options' => array(
                          'cheapest' => 'Cheapest product(s) for free',
                          'buy_x_get_y' => 'Buy X get Y of the same product for free',
                          'gift' => 'Gift',
                  ))
                );

                woocommerce_wp_text_input( array(
                        'id' => '_free_products_min_matching_product_qty',
                        'label' => __( 'Quantity of matching products', 'woocommerce-free-coupons' ),
                        'placeholder' => __( 'No quantity', 'woocommerce' ),
                        'description' => __( 'Maximum quantity of the products that match the given product or category restrictions (see tab \'usage restriction\'). If no product or category restrictions are specified, the total number of products is used.', 'woocommerce-jos-autocoupon' ),
                        'data_type' => 'decimal',
                        'desc_tip' => true,
                ) );

                woocommerce_wp_checkbox( array(
                        'id' => '_free_products_auto',
                        'label' => __( 'Apply automatically', 'woocommerce-free-coupons' ),
                        'description' => __( 'Coupon applied automatically', 'woocommerce-jos-autocoupon' ),
                        'data_type' => 'decimal',
                        'desc_tip' => true,
                ) );

                woocommerce_wp_text_input( array(
                        'id' => '_free_products_free_product_qty',
                        'label' => __('Quantity of free products', 'woocommerce-free-coupons'),
                        'placeholder' => __( 'No quantity', 'woocommerce' ),
                        'description' => __( '', 'woocommerce-free-coupons'),
                        'data_type' => 'decimal',
                        'desc_tip' => true,
                ) );

                woocommerce_wp_text_input( array(
                        'id' => '_free_products_gift_id',
                        'label' => __('ID of the product that will be added as gift', 'woocommerce-free-coupons'),
                        'placeholder' => __( '', 'woocommerce' ),
                        'description' => __( '', 'woocommerce-free-coupons'),
                        'data_type' => 'decimal',
                        'desc_tip' => true,
                ) );

                ?>
<!--
                                <p class="form-field"><label><?php _e( 'Products', 'woocommerce' ); ?></label>
                                <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="product_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                                        $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, '_free_products_gift_id', true ) ) ) );
                                        $json_ids    = array();

                                        foreach ( $product_ids as $product_id ) {
                                                $product = wc_get_product( $product_id );
                                                if ( is_object( $product ) ) {
                                                        $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                                                }
                                        }

                                        echo esc_attr( json_encode( $json_ids ) );
                                        ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'Products which need to be in the cart to use this coupon or, for "Product Discounts", which products are discounted.', 'woocommerce' ) ); ?></p>
-->
              </div>
            </div>
        <?php
    }

    public function process_shop_coupon_meta($post_id, $post) {
        if($_POST['discount_type'] === FREE_PRODUCT_COUPON_TYPE) {
            $free_products_auto = isset($_POST['_free_products_auto'])
				? $_POST['_free_products_auto'] : '';
            update_post_meta($post_id, '_free_products_auto', sanitize_text_field($free_products_auto));

            $free_products_min_matching_product_qty = isset($_POST['_free_products_min_matching_product_qty']) 
                ? $_POST['_free_products_min_matching_product_qty'] : '';
            update_post_meta($post_id, '_free_products_min_matching_product_qty', intval($free_products_min_matching_product_qty));

            $free_products_free_product_qty = isset($_POST['_free_products_free_product_qty']) 
                ? $_POST['_free_products_free_product_qty'] : '';
            update_post_meta($post_id, '_free_products_free_product_qty', intval($free_products_free_product_qty));

            $free_products_type = isset($_POST['_free_products_type'])
                ? $_POST['_free_products_type'] : ''; 
            update_post_meta($post_id, '_free_products_type', sanitize_text_field($free_products_type)); 

            $free_products_gift_id = isset($_POST['_free_products_gift_id'])
                ? $_POST['_free_products_gift_id'] : '';
            update_post_meta($post_id, '_free_products_gift_id', intval($free_products_gift_id));
        }
    }

    public function admin_action_menu() {

    }
}
