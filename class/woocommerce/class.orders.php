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

use E20R\Licensing\Purchase\Controller;
use E20R\Utilities\Utilities;

class Orders {
	/**
	 * @var null|Orders
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the WooCommerce Orders class
	 *
	 * @return Orders|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Create/Add license for the product(s) when order payment is complete
	 *
	 * @param int $order_id
	 */
	public function complete( $order_id ) {
		
		$utils = Utilities::get_instance();
		
		global $e20rlm_order;
		
		$controller    = Controller::getInstance();
		$wc_controller = WooCommerce::getInstance();
		
		$order   = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		$o_items = $order->get_items();
		
		// Cache order for filters/hooks
		$e20rlm_order = $order;
		$product_id   = null;
		
		$licenses = get_user_meta( $user_id, "e20r_license_user_settings", true );
		
		if ( empty( $licenses ) ) {
			$licenses = array();
		}
		
		foreach ( $o_items as $item => $config ) {
			
			$product_id = $config['product_id'];
			$product    = new \WC_Product( $product_id );
			
			$utils->log( "Processing product {$product_id} for {$order_id}" );
			
			if ( $wc_controller->isLicdProduct( $product_id ) && $product->is_downloadable() ) {
				
				$utils->log( "{$product_id} is a licensed product" );
				
				$quantity = absint( $config['qty'] );
				
				$utils->log( "Attempting to add a license for {$product_id}/{$user_id}" );
				
				$user_settings = $controller->addLicense( $product_id, $user_id, 'woocommerce', $quantity );
				
				if ( false !== $user_settings ) {
					$licenses[] = $user_settings;
				}
			}
		}
		
		$utils->log( "Saving " . count( $licenses ) . " new licenses for {$user_id}" );
		
		update_user_meta( $user_id, "e20r_license_user_settings", $licenses );
		
		$this->addOrderNote( $order_id, $licenses, $product_id );
	}
	
	/**
	 * Create/Add license for the product(s) when order payment plan is cancelled
	 *
	 * @param int $order_id
	 */
	public function cancelled( $order_id ) {
		
		$utils = Utilities::get_instance();
		
		global $e20rlm_order;
		
		$controller    = Controller::getInstance();
		$wc_controller = WooCommerce::getInstance();
		
		$order   = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		$o_items = $order->get_items();
		
		// Cache order for filters/hooks
		$e20rlm_order = $order;
		$product_id   = null;
		
		$licenses = get_user_meta( $user_id, "e20r_license_user_settings", true );
		
		if ( empty( $licenses ) ) {
			$licenses = array();
		}
		
		foreach ( $o_items as $item => $config ) {
			
			$product_id = $config['product_id'];
			$product    = new \WC_Product( $product_id );
			
			$utils->log( "Processing product {$product_id} for {$order_id}" );
			
			if ( $wc_controller->isLicdProduct( $product_id ) && $product->is_downloadable() ) {
				
				$utils->log( "{$product_id} is a licensed product" );
				
				$quantity = absint( $config['qty'] );
				
				$utils->log( "Attempting to remove the license for {$product_id}/{$user_id}" );
				
				$user_settings = $controller->removeLicense( $product_id, $user_id, 'woocommerce', $quantity );
				
				if ( false !== $user_settings ) {
					$licenses[] = $user_settings;
				}
			}
		}
		
		$utils->log( "Saving " . count( $licenses ) . " new licenses for {$user_id}" );
		
		update_user_meta( $user_id, "e20r_license_user_settings", $licenses );
		
		$this->addOrderNote( $order_id, $licenses, $product_id );
	}
	
	/**
	 * Add an Order Note containing license information for the specified Order ID
	 *
	 * @param int   $order_id
	 * @param array $licenses
	 */
	public function addOrderNote( $order_id, $licenses, $product_id = null ) {
		
		if ( ! empty( $licenses ) && is_array( $licenses ) ) {
			
			$text = __( "Generated licenses", 'e20r-add-license-on-purchase' );
			
			foreach ( $licenses as $order_id => $license ) {
				$text .= __( '%1$s%2$s:%3$s', '<br />', $license[ $product_id ]['item_reference'], $license[ $product_id ]['license_key'] );
			}
		} else {
			$text = __( "Unable to create License keys for order!", 'e20r-add-license-on-purchase' );
		}
		
		$order = new \WC_Order( $order_id );
		$order->add_order_note( $text );
	}
	
	/**
	 * Generate License specific metadata for the order
	 *
	 * @param \WC_Order $order
	 */
	public function metadata( $order ) {
		
		$controller = Controller::getInstance();
		
		$content  = null;
		$order_id = $order->get_id();
		$user_id  = $order->get_user_id();
		
		$licenses = get_user_meta( $user_id, 'e20r_license_user_settings', true );
		
		if ( ! empty( $licenses ) && is_array( $licenses ) ) {
			$content = $controller->licenseInfo( $licenses );
		}
		
		if ( ! empty( $content ) ) {
			echo $content;
		}
	}
	
	/**
	 * Return the product name as the license name
	 *
	 * @param string $name
	 * @param int    $id
	 * @param string $source
	 *
	 * @return string
	 */
	public function licenseName( $name, $id, $source ) {
		
		if ( 'woocommerce' != $source ) {
			return $name;
		}
		
		$product = new \WC_Product( $id );
		
		return $product->get_title();
	}
	
	/**
	 * Create the client key for the license (WooCommerce Specific)
	 *
	 * @param string $key    Default Key name
	 * @param int    $id     The ID for the product/level being processed
	 * @param string $source 'pmpro'|'woocommerce'
	 * @param string $random Generated random string
	 *
	 * @return string
	 */
	public function create( $key, $id, $source, $random ) {
		
		if ( 'woocommerce' != $source ) {
			return $key;
		}
		
		$license_settings = get_post_meta( $id, '_e20rlm_license', true );
		
		if ( ! empty( $license_settings['key_stub'] ) ) {
			$key = "{$license_settings[ 'key_stub' ]}_{$random}";
		}
		
		return $key;
	}
	
	/**
	 * Return the transaction ID for the license (is purchase plugin dependent)
	 *
	 * @param string                 $txn_id
	 * @param int                    $product_id
	 * @param int                    $user_id
	 * @param string                 $source - PMPro or WooCommerce
	 * @param \MemberOrder|\WC_Order $order  The order object for PMPro or WooCommerce
	 *
	 * @return null
	 */
	public function getTransactionId( $txn_id, $product_id, $user_id, $source, $order = null ) {
		
		$utils = Utilities::get_instance();
		
		if ( is_null( $order ) ) {
			global $e20rlm_order;
			
			$order = $e20rlm_order;
		}
		
		if ( 'woocommerce' == $source ) {
			$txn_id = $order->get_id();
		}
		
		return $txn_id;
	}
	
	/**
	 * Return the billing info for the license (is purchase plugin dependent)
	 *
	 * @param array    $info
	 * @param \WP_User $user
	 * @param string   $source
	 *
	 * @return array
	 */
	public function getBillingInfo( $info, $user, $source ) {
		
		if ( 'woocommerce' !== $source ) {
			return $info;
		}
		
		$user_meta = get_user_meta( $user->ID );
		
		$info = array(
			'first_name' => isset( $user_meta['billing_first_name'][0] ) ? $user_meta['billing_first_name'][0] : $user->first_name,
			'last_name'  => isset( $user_meta['billing_last_name'][0] ) ? $user_meta['billing_last_name'][0] : $user->last_name,
			'email'      => isset( $user_meta['billing_email'][0] ) ? $user_meta['billing_email'][0] : $user->user_email,
			'company'    => isset( $user_meta['billing_company'][0] ) ? $user_meta['billing_company'][0] : null,
		);
		
		return apply_filters( "e20r-license-server-add-{$source}-billing", $info, $user->ID );
	}
	
	/**
	 * Configure the expiration date for the license
	 *
	 * @param string $expiration_date
	 * @param string $source
	 *
	 * @return string
	 */
	public function expires( $expiration_date, $product_id, $source ) {
		
		if ( 'woocommerce' != $source ) {
			return $expiration_date;
		}
		
		$license_settings = get_post_meta( $product_id, '_e20rlm_license', true );
		
		if ( empty( $license_settings ) && empty( $license_settings['renewal_period'] ) ) {
			return '0000-00-00';
		}
		
		$years           = absint( $license_settings['renewal_period'] );
		$expiration_date = date_i18n( 'Y-m-d', strtotime( "+{$years} years", current_time( 'timestamp' ) ) );
		
		return $expiration_date;
	}
	
	/**
	 * Return the number of allowed domains the license can be applied to
	 *
	 * @param int    $count
	 * @param int    $product_id
	 * @param string $source
	 *
	 * @return mixed
	 */
	public function domains( $count, $product_id, $source ) {
		
		global $e20rlm_order_id;
		
		if ( 'woocommerce' != $source ) {
			return $count;
		}
		
		$license_settings = get_post_meta( $product_id, '_e20rlm_license', true );
		
		if ( empty( $license_settings['site_count'] ) ) {
			return false;
		}
		
		// For WooCommerce, the $count variable contains the quantity of the license ordered
		return ( $count * absint( $license_settings['site_count'] ) );
	}
}