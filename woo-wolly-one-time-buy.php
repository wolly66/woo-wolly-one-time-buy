<?php
/**
 * @package woo wolly one time buy
 * @author Paolo Valenti
 * @version 1.1 first release
 */
/*
Plugin Name: Woo Wolly One Time Buy
Plugin URI: https://paolovalenti.org/my-plugins/woo-wolly-one-time-buy/
Description: This plugin has all the utility for insight room
Author: Paolo Valenti aka Wolly for WordPress Italy
Version: 1.1
Author URI: https://paolovalenti.info
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woo-wolly-one-time-buy
Domain Path: /languages
*/
/*
	Copyright 2017  Paolo Valenti aka Wolly  (email : wolly66@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

function woo_wolly_one_time_buy_init() {
	
  load_plugin_textdomain( 'woo-wolly-one-time-buy', false, dirname( plugin_basename( __FILE__ ) ) ); 
  
}

add_action( 'plugins_loaded', 'woo_wolly_one_time_buy_init' );



/**
 * Woo_Wolly_One_Time_Buy class.
 */
class Woo_Wolly_One_Time_Buy {
	
	public function __construct(){
		
		// Display One time buy Field 
		add_action( 'woocommerce_product_options_general_product_data', array( $this,  'add_one_time_field' ) );

		// Save One time buy Field
		add_action( 'woocommerce_process_product_meta',  array( $this, 'add_one_time_field_save' ) );
			
		//check if product is a one time buy and if it's already purchased	
		add_filter('woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ),20, 2);
		
		
		//remove from cart
		add_action( 'template_redirect',  array( $this, 'remove_from_cart' ) );
			
			
					
		
	}
	
	
	/**
	 * add_one_time_field function.
	 * 
	 * @access public
	 * @echo
	 * @since version 1.0
	 */
	public function add_one_time_field(){

		global $woocommerce, $post;

		echo '<div class="options_group">';

		/**
		 * Create one_time_buy chechbox
		 *
		 * @since version 1.0
		 *
		 */
		if ( function_exists( 'woocommerce_wp_checkbox' ) ){
		woocommerce_wp_checkbox(
  								array(
  									'id'            => '_one_time_buy_checkbox',
  									'wrapper_class' => 'one-time',
  									'label'         => __('One time buy', 'wolly-content-newsletter-management' ),
  									'desc_tip'    => 'true',
  									'description'   => __( 'Check if this product is a one time buy!', 'woo-wolly-one-time-buy' ),
  									)
  								);
  		}
  		
  		echo '</div>';


	}

	
	/**
	 * add_one_time_field_save function.
	 * 
	 * @access public
	 * @since version 1.0
	 * @param mixed $post_id
	 * @save
	 */
	public function add_one_time_field_save( $post_id ){

		
		/**
		 * one_time_buy_checkbox
		 * 
		 * (default value: isset( $_POST['_one_time_buy_checkbox'] ) ? 'yes' : 'no')
		 * @since version 1.0
		 * @var string
		 * @access public
		 */
		$one_time_buy_checkbox = isset( $_POST['_one_time_buy_checkbox'] ) ? 'yes' : 'no';

			if ( 'yes' == $one_time_buy_checkbox  ){
			
				update_post_meta( $post_id, '_one_time_buy_checkbox', $one_time_buy_checkbox );
		
				} else {

					delete_post_meta( $post_id, '_one_time_buy_checkbox' );
			}
	}
		
		
	
	/**
	 * add_to_cart_validation function.
	 * 
	 * @access public
	 * @since version 1.0
	 * @param mixed $valid
	 * @param mixed $product_id
	 * @return $valid (bool)
	 */
	public function add_to_cart_validation( $valid, $product_id ){
	
    	$current_user = wp_get_current_user();
    
		$is_one_time_buy = get_post_meta( $product_id, '_one_time_buy_checkbox', true );
    
		if ( ! empty( $is_one_time_buy ) ){
    
    		if ( wc_customer_bought_product( $current_user->user_email, $current_user->ID, $product_id ) ) {
	    	
				wc_add_notice( __( 'You\'ve already purchased this product! It can only be purchased once.', 'woo-wolly-one-time-buy' ), 'error' );
         
				$valid = false;
    
    		}
    	}
    
		return $valid;
		
	}

	
	/**
	 * remove_from_cart function.
	 * 
	 * @access public
	 * @return void
	 */
	public function remove_from_cart(){
		
		// Run only in the Cart or Checkout Page
		if( is_cart() || is_checkout() ) {
        
        $current_user = wp_get_current_user();

        // Cycle through each product in the cart
        foreach( WC()->cart->cart_contents as $prod_in_cart ) {
	        
	    	$prod_id = ( isset( $prod_in_cart['variation_id'] ) && $prod_in_cart['variation_id'] != 0 ) ? $prod_in_cart['variation_id'] : $prod_in_cart['product_id'];
	        
			$is_one_time_buy = get_post_meta( $prod_id, '_one_time_buy_checkbox', true );
	        
			if ( ! empty( $is_one_time_buy ) ){
		        
				if ( wc_customer_bought_product( $current_user->user_email, $current_user->ID, $prod_id ) ) {
	    	
					wc_add_notice( __( 'You\'ve already purchased this product! It can only be purchased once.', 'woo-wolly-one-time-buy' ), 'error' );

					// Get it's unique ID within the Cart
					$prod_unique_id = WC()->cart->generate_cart_id( $prod_id );
					// Remove it from the cart by un-setting it
					unset( WC()->cart->cart_contents[$prod_unique_id] );
                
                }
		        
		    }
            
        }

    	}
		
		
	}

	
}


/**
 * woo_wolly_one_time_buy
 * 
 * (default value: new Woo_Wolly_One_Time_Buy())
 * 
 * @instantiate the class
 * @access public
 */
$woo_wolly_one_time_buy = new Woo_Wolly_One_Time_Buy();
