<?php
/*
Plugin Name: Local Shipping with Pickup Locations for WooCommerce
Plugin URI: http://github.com/szelpe/woocommerce-shipping-pickup-locations
Description: Adds the ability to WooCommerce to select the pickup location for local shipping.
Version: 0.0.1
Author: Peter Szel <szelpeter@szelpeter.hu>
Author URI: http://szelpeter.hu
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_action('plugins_loaded', 'woocommerce_shipping_pickup_locations_init', 0);
add_action('woocommerce_after_shipping_rate', 'woocommerce_shipping_pickup_locations_after_shipping_rate', 10, 2);
add_action('woocommerce_checkout_update_order_meta', 'woocommerce_shipping_pickup_locations_checkout_update_order_meta', 10, 2);
        
            
function woocommerce_shipping_pickup_locations_checkout_update_order_meta($order_id, $posted) {
    $m = new WC_Shipping_Local_Pickup_With_Locations();
    $m->process_payment($order_id, $posted);
}
        
function woocommerce_shipping_pickup_locations_after_shipping_rate($method, $index) {
    $m = new WC_Shipping_Local_Pickup_With_Locations();
    $m->add_locations($method, $index);
}

function woocommerce_shipping_pickup_locations_init() {
    if (!class_exists('WC_Shipping_Local_Pickup'))
        return;
    
    class WC_Shipping_Local_Pickup_With_Locations extends WC_Shipping_Local_Pickup {

        public function __construct() {
            $this->id                 = 'local_pickup_with_locations';
            $this->method_title       = __('Local Pickup With Locations', 'woocommerce');
            $this->method_description = __('Local pickup is a simple method which allows customers to pick up orders themselves.', 'woocommerce');
            
            $this->init();
        }

        public function init() {
            parent::init();
            $this->pickup_locations	= $this->get_option('locations');
        }
        
        public function process_payment($order_id, $posted) {
            $order = new WC_Order($order_id);
            update_post_meta($order_id, 'pickup location', $_POST['pickup_location']);
        }
        
        public function add_locations($method, $index) {
            if($method->id != $this->id)
                return;
            
            $chosen_method = isset(WC()->session->chosen_shipping_methods[$index]) ? WC()->session->chosen_shipping_methods[$index] : '';
            
            if($chosen_method != $this->id)
                return;
            
            $locations = explode("\n", $this->pickup_locations);
            
            echo "<div><select name=\"pickup_location\">";
            foreach($locations as $location) {
                echo "<option>$location</option>";
            }
            
            echo "</select></div>";
        }

        public function init_form_fields() {
            parent::init_form_fields();
            
            $this->form_fields['locations'] = array(
                    'title'             => __('Pickup Locations', 'woocommerce'),
                    'type'              => 'textarea',
                    'default'           => '',
                    'description'       => __('Add pickup locations line-by-line. Every line will become a selectable item in the dropdown list.', 'woocommerce'),
                    'css'               => 'height: 250px;'
                );
        }
    }

    
    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_local_pickup_with_locations_shipping_method($methods) {
        $methods[] = 'WC_Shipping_Local_Pickup_With_Locations';
        return $methods;
    }
    
    add_filter('woocommerce_shipping_methods', 'woocommerce_add_local_pickup_with_locations_shipping_method' );
}