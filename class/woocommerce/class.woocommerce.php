<?php
/**
 * Copyright (c) 2017 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Licensing\Purchase\WooCommerce;

use E20R\Utilities\Utilities;
use E20R\Licensing\Purchase\Controller;

class WooCommerce {
	
	/**
	 * @var null|WooCommerce
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the WooCommerce class
	 *
	 * @return WooCommerce|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load required WooCommerce hooks
	 */
	public function loadHooks() {
		
		$settings = Settings::getInstance();
		$orders = Orders::getInstance();
		
		add_action( 'admin_enqueue_scripts', array( $this, 'loadScripts' ), 10 );
		
		add_filter( 'woocommerce_get_sections_products', array( $settings, 'section' ), 10, 1 );
		add_filter( 'woocommerce_get_settings_products', array( $settings, 'settings' ), 10, 2 );
		
		add_action( 'woocommerce_product_options_general_product_data', array( $settings, 'fields' ), 10 );
		add_action( 'woocommerce_process_product_meta', array( $settings, 'save' ), 10 );
		
		add_action('woocommerce_email_before_order_table', array( EMail::getInstance(), 'content' ), 10, 4);
		
		add_action( 'woocommerce_order_details_after_order_table', array( $orders, 'metadata' ), 10, 1);
		// add_action( 'woocommerce_order_status_completed', array( $orders, 'complete') , 10, 2 );
		add_action( 'woocommerce_payment_complete', array( $orders, 'complete') , 10, 1 );
		
		add_filter( 'e20r-license-server-txn-id', array( $orders, 'getTransactionId' ), 10, 3 );
		add_filter( 'e20r-license-server-billing-info', array( $orders, 'getBillingInfo' ), 10, 3 );
		add_filter( 'e20r-licensing-server-expiration-date', array( $orders, 'expires' ), 10, 3 );
		add_filter( 'e20r-licensing-server-client-key', array( $orders, 'create' ), 10, 4 );
		add_filter( 'e20r-license-server-domains-per-license', array( $orders, 'domains' ), 10, 3 );
		add_filter( 'e20r-license-server-license-name', array( $orders, 'licenseName' ), 10, 3 );
	}
	
	/**
	 * Load JavaScript for the WooCommerce product edit page
	 */
	public function loadScripts() {
		
		wp_enqueue_script( 'e20rlm', E20R_ALOP_URL . 'js/e20r-add-license-on-purchase.js', array( 'jquery'), E20R_LICENSE_SERVER_VERSION );
	}
	
	/**
	 * Verify whether the specific product ID comes with a license
	 *
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public function isLicdProduct( $product_id ) {
		
		$config = get_post_meta( $product_id, '_e20rlm_license', true );
		
		if ( isset( $config['enabled'] ) ) {
			return (bool) $config['enabled'];
		}
		
		return false;
	}
}