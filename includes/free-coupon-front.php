<?php

defined('ABSPATH') or die();

define('FREE_PRODUCT_COUPON_TYPE', 'free_product');

class BFP_Free_Coupon_Front {
    private $_free_coupons = null;

    public function __construct() { 
        $this->init_hook();
    }

    public function init_hook() {
        add_filter('woocommerce_product_coupon_types', array($this, 'custom_product_coupon_type'), 10, 1);

        add_action('woocommerce_before_calculate_totals', array(&$this, 'filter_update_matched_free_coupons' ) );
        add_action('woocommerce_after_calculate_totals', array( &$this, 'update_matched_free_coupons' ) );

        add_action('woocommerce_check_cart_items',  array( &$this, 'remove_unmatched_free_coupons' ) , 0, 0 );
        add_filter('woocommerce_cart_totals_coupon_label', array( &$this, 'coupon_label' ), 10, 2 );
        add_filter('woocommerce_cart_totals_coupon_html', array( &$this, 'coupon_html' ), 10, 2 );
        add_filter('woocommerce_coupon_get_discount_amount', array(&$this, 'free_product_get_discount_amount'), 99, 5);
        add_filter('woocommerce_coupon_is_valid_for_product', array(&$this, 'free_product_coupon_is_valid_for_product'), 10, 4);
        add_action('woocommerce_after_cart_table', array(&$this, 'free_product_after_cart'), 20, 0);
        add_action('woocommerce_before_cart', array(&$this, 'free_product_before_cart'));
        add_filter('woocommerce_cart_item_subtotal', array(&$this, 'free_product_cart_item_subtotal'), 99, 3);
    }

    public function filter_update_matched_free_coupons() { 
        if(is_ajax()) { 
            $this->update_matched_free_coupons(); 
        }
    }

    public function custom_product_coupon_type($discount_types) {
        $discount_types[] = FREE_PRODUCT_COUPON_TYPE;
        return $discount_types;
    }

    private $_update_matched_free_coupons_executed = false;

    public function update_matched_free_coupons() {
        if($this->_update_matched_free_coupons_executed) {
            return;
        }
        $this->_update_matched_free_coupons_exectuted = true;

        $valid_coupons = $this->get_valid_free_coupons();

        foreach($valid_coupons as $coupon) {
            if(!WC()->cart->has_discount($coupon->code)) {
                WC()->cart->add_discount($coupon->code);
            }
        }
    }

    public function remove_unmatched_free_coupons($valid_coupon_codes = null) {
        if($valid_coupon_codes === null) {
            $valid_coupons = $this->get_valid_free_coupons();

            $valid_coupon_codes = array();

            foreach ($valid_coupons as $coupon) {
                $valid_coupon_codes[] = $coupon->code;
            }
        }

        $calc_needed = false;

        foreach ($this->get_all_free_coupons() as $coupon) {
            $auto = (get_post_meta($coupon->id, '_free_products_auto', true) === 'yes') ? true : false; 

            if (WC()->cart->has_discount( $coupon->code) && !in_array($coupon->code, $valid_coupon_codes) && $auto) {
                WC()->cart->remove_coupon($coupon->code);
                $calc_needed = true;
            }
        }

        return $calc_needed;
    }

    public function coupon_label($originaltext, $coupon) {
        return $originaltext;
    }

    public function coupon_html($originaltext, $coupon) {
        return $originaltext;
    }

    public function get_valid_free_coupons() {
        $valid_coupons = array();
        foreach($this->get_all_free_coupons() as $coupon) {
            if($this->coupon_can_be_applied($coupon)) {
                $valid_coupons[] = $coupon;
            }
        }

        return $valid_coupons;
    }

    private $_discounts = null; 

    public function coupon_can_be_applied($coupon) {
        $can_be_applied = false;
        $auto = (get_post_meta($coupon->id, '_free_products_auto', true) === 'yes') ? true : false; 

        $this->_valid_products_cart[$coupon->id] = $this->get_valid_products_for_coupon($coupon);

		if(!empty($this->_valid_products_cart[$coupon->id])) { 
			if(is_array($this->_discounts[$coupon->id])) { 
				$this->_discounts[$coupon->id] = array_merge($this->_discounts[$coupon->id], $this->calculate_discount($coupon));
			} else { 
				$this->_discounts[$coupon->id] = $this->calculate_discount($coupon);
			}

			if($this->_discounts[$coupon->id] && $auto) {
				$can_be_applied = true;
			}
		}

        return $can_be_applied;
    }

    private $_valid_products_cart = [];

    public function get_valid_products_for_coupon($coupon) {
        $valid_products_cart = [];

        if(!WC()->cart->is_empty()) {
            foreach(WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if($coupon->is_valid_for_product($cart_item['data'], $cart_item)) {
                    $valid_products_cart[$cart_item_key] = $cart_item;
                }
            }
        }

        return $valid_products_cart;
    }

    public function get_all_free_coupons() {
        if(!is_array($this->_free_coupons)) {
            $this->_free_coupons = array();

            $query_args = array(
                'posts_per_page' => -1,
                'post_type' => 'shop_coupon',
                'post_status' => 'publish',
                'orderby' => array('title' => 'ASC'),
                'meta_query' => array(
                    array(
                        'key' => 'discount_type',
                        'compare' => '=',
                        'value' => FREE_PRODUCT_COUPON_TYPE,
                    )
                )
            );

            $query = new WP_Query($query_args);
            foreach($query->posts as $post) {
                $coupon = new WC_Coupon($post->post_title);
                $this->_free_coupons[$coupon->code] = $coupon;
            }
        }

        return $this->_free_coupons;
    }

    public function calculate_discount($coupon) {
        $free_products_type = get_post_meta($coupon->id, '_free_products_type', true);

        $discount = false;

        switch($free_products_type) { 
            case 'cheapest':
                $discounts = $this->discounts_for_cheapest($coupon); 
                break;
            case 'buy_x_get_y':
                $discounts = $this->discounts_for_buy_x_get_y($coupon);
                break;
            case 'gift':
                $discounts = $this->discounts_for_gift($coupon); 
                break;
            default: 
                break;
        }

        return $discounts;
    }

    private function discounts_for_cheapest($coupon) {
        $products_needed = get_post_meta($coupon->id, '_free_products_min_matching_product_qty', true);
        $free_products_left = get_post_meta($coupon->id, '_free_products_free_product_qty', true);

        uasort($this->_valid_products_cart[$coupon->id], array(&$this, cmp_cart_line_product_price));

        foreach($this->_valid_products_cart[$coupon->id] as $k => $product) {
            $this->_valid_products_cart[$coupon->id][$k]['orig_quantity'] = $this->_valid_products_cart[$coupon->id][$k]['quantity'];
            while($this->_valid_products_cart[$coupon->id][$k]['quantity'] && $products_needed) {
                $this->_valid_products_cart[$coupon->id][$k]['quantity']--;
                $products_needed--;
            }
        }

        $this->_valid_products_cart[$coupon->id] = array_reverse($this->_valid_products_cart[$coupon->id]);

        foreach($this->_valid_products_cart[$coupon->id] as $k => $product) {
            while($this->_valid_products_cart[$coupon->id][$k]['quantity'] && $free_products_left && !$products_needed) {
                $discounts[spl_object_hash($this->_valid_products_cart[$coupon->id][$k]['data'])] += ($coupon->coupon_amount/100)
                    * $product['data']->get_price()/$product['orig_quantity'];
                $this->_valid_products_cart[$coupon->id][$k]['quantity']--;
                $free_products_left--;
            }
        }

        return $discounts; 
    } 

    private function discounts_for_buy_x_get_y($coupon) { 
        $products_needed = get_post_meta($coupon->id, '_free_products_min_matching_product_qty', true);
        $free_products_left = get_post_meta($coupon->id, '_free_products_free_product_qty', true);

        foreach($this->_valid_products_cart[$coupon->id] as $k => $product) {
            $product['orig_quantity'] = $product['quantity'];
            $curr_products_needed = $products_needed;
            $curr_free_products_left = $free_products_left;

            while($this->_valid_products_cart[$coupon->id][$k]['quantity'] && $curr_products_needed) {
                $this->_valid_products_cart[$coupon->id][$k]['quantity']--;
                $curr_products_needed--;
            }
            while($this->_valid_products_cart[$coupon->id][$k]['quantity'] && $curr_free_products_left && !$curr_products_needed) {
                $discounts[spl_object_hash($product['data'])] += ($coupon->coupon_amount/100)
                    * $product['data']->get_price()/$product['orig_quantity'];
                $this->_valid_products_cart[$coupon->id][$k]['quantity']--;
                $curr_free_products_left--;
            }

            if(!$curr_products_needed && $curr_free_products_left) {
                if(!$discounts) $discounts = true;

                $new_quantity = WC()->cart->get_cart_item($k)['quantity'] + $free_products_left;
                WC()->cart->set_quantity($k, $new_quantity);
            }
        }

        return $discounts; 
    }

    private $_free_products = null;

    private $_free_products_total; 
    private $_free_products_left; 

    private function discounts_for_gift($coupon) { 
        $products_needed = get_post_meta($coupon->id, '_free_products_min_matching_product_qty', true);
        $free_products_left = get_post_meta($coupon->id, '_free_products_free_product_qty', true);
        $gift_product_id = get_post_meta($coupon->id, '_free_products_gift_id', true); 
        $minimum_amount = get_post_meta($coupon->id, 'minimum_amount', true); 

        if(empty($minimum_amount)) { 
            $minimum_amount = 0; 
        }

        $this->_free_products_total = $free_products_left;

        foreach($this->_valid_products_cart[$coupon->id] as $k => $product) {
            $this->_valid_products_cart[$coupon->id][$k]['orig_quantity'] = $this->_valid_products_cart[$coupon->id][$k]['quantity'];
            while($this->_valid_products_cart[$coupon->id][$k]['quantity'] && $products_needed) {
                if(!$product['free_product_gift']) { 
                    $products_needed--;
                }
                $this->_valid_products_cart[$coupon->id][$k]['quantity']--;
            }
        }
        foreach($this->_valid_products_cart[$coupon->id] as $k => $product) { 
            if($product['free_product_gift']) { 
				$this->_free_products[$k] = $product['data'];

                while($free_products_left && $product['quantity']) { 
                    $discounts[spl_object_hash($product['data'])] += ($coupon->coupon_amount/100)
                        * $product['data']->get_price()/$product['orig_quantity'];

                    
                    $free_products_left--;
                    $product['quantity']--;
                }
            }
        }

        if(!$products_needed && $free_products_left && (WC()->cart->subtotal >= $minimum_amount)) {
            if(!$discounts) $discounts = true;

            if(WC()->cart->has_discount($coupon->code)) {
                $gift_product = WC()->product_factory->get_product($gift_product_id);
                $this->_free_products_avail[$gift_product_id] = $gift_product; 
            }   
        }

        $this->_free_products_left = $free_products_left; 

        return $discounts; 
    }

    public function cmp_cart_line_product_price($line_a, $line_b) { 
        return $line_a['data']->get_price() < $line_b['data']->get_price() ? 1 : -1;
    }

    public function free_product_get_discount_amount($discount, $discounting_amount, $cart_item, $single, $coupon) {
        if($coupon->discount_type === FREE_PRODUCT_COUPON_TYPE) { 
            return $this->_discounts[$coupon->id][spl_object_hash($cart_item['data'])];
        } else { 
            return $discount; 
        }
    }

    public function free_product_coupon_is_valid_for_product($valid, $product, $coupon, $values) { 
        if($coupon->discount_type === FREE_PRODUCT_COUPON_TYPE) { 
            $gift_product_id = get_post_meta($coupon->id, '_free_products_gift_id', true); 
            
            if($gift_product_id == $product->id) $valid = true;
        }

        return $valid; 
    }

    public function free_product_before_cart() { 
        if(isset($_POST['product_id'])) { 
            $product_id     = intval($_POST['product_id']);
            $quantity       = intval($_POST['quantity']);
            $variation_id   = intval($_POST['variation_id']);

            $variation = array(); 

            if($variation_id !== 0) { 
                foreach($_POST as $k => $v) { 
                    if(preg_match('/attribute_/', $k)) { 
                        $attribute_name     = sanitize_text_field($k);
                        $attribute_value    = sanitize_text_field($v);

                        $variation[$attribute_name] = $attribute_value;
                    }
                }
            }
            WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation,
                array('free_product_gift' => true));
        }
    }

    public function free_product_after_cart() { 
        ?>
        <div id="wc-freeproduct" class="wc-freeproduct">
        <?php if(!empty($this->_free_products_avail)): ?>
            <p class="freeproduct_header"><a data-toggle="modal" data-target="#freeproduct_modal" class="btn" aria-label="<?php printf(__("Add free product", 'beecommerce-free-product')) ?>"><i class="fa fa-gift" aria-hidden="true"></i> <?php printf(__("Add free product (%d items left)", 'beecommerce-free-product'), $this->_free_products_left) ?></a></p>
            <?php 
            foreach($this->_free_products_avail as $k => $product) { 
                if($product->get_type() === 'variable') { 
                    $available_variations = $product->get_available_variations(); 
                    $attributes = $product->get_variation_attributes(); 

                    include('variation-form-template.php'); 
                } else { 
                    include('simple-form-template.php');   
                }
            }
            ?>
        <?php endif ?>
        </div>
        <?php 
        wp_enqueue_script( 'wc-add-to-cart-variation' );
    }

    public function free_product_cart_item_subtotal($subtotal, $cart_item, $cart_item_key) { 
        if((abs($cart_item['line_total'] - $cart_item['line_subtotal'])/$cart_item['line_subtotal']) > 0.01) { 
            $subtotal = '<s>' . $subtotal . '</s> ' . wc_price($cart_item['line_total']);
        }

        return $subtotal;
    }
          
}
