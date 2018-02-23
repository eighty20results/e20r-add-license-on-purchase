<?php
/**
 * Copyright (c) 2018 - Eighty / 20 Results by Wicked Strong Chicks.
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

class Subscriptions {
	
	/**
	 * @var null|Subscriptions
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the Subscriptions class
	 *
	 * @return Subscriptions|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Action handler when user purchases a WooCommerce subscription
	 *
	 * @param \WC_Subscription $subscription
	 */
	public function activate( $subscription ) {
		
		$utils         = Utilities::get_instance();
		$items         = $subscription->get_items();
		$sub_user_id   = $subscription->get_user_id();
		$controller    = Controller::getInstance();
		$wc_controller = WooCommerce::getInstance();
		
		if ( ! empty( $items ) && ! empty( $sub_user_id ) ) {
			
			$utils->log( "Found " . count( $items ) . " order items in subscription for {$sub_user_id}" );
			global $e20rlm_order;
			
			/**
			 * @var \WC_Order_Item $order_item
			 */
			foreach ( $items as $order_item ) {
				
				$order = $this->getOrderFromSub( $subscription );
				$e20rlm_order = $order;
				
				if ( ! empty( $order ) && $wc_controller->isLicdProduct( wcs_get_canonical_product_id( $order_item ) ) ) {
					
					$quantity = $this->getOrderItemQty( $order_item, $order );
					
					$utils->log( "Adding {$quantity} licenses for {$sub_user_id} and product: " . $order_item->get_id() );
					$controller->addLicense( $order_item->get_id(), $sub_user_id, 'woocommerce', $quantity );
					$e20rlm_order = null;
				}
			}
		}
	}
	
	/**
	 * Return the order object for the subscription in question
	 *
	 * @param \WC_Subscription $subscription
	 *
	 * @return \WC_Order;
	 */
	private function getOrderFromSub( $subscription ) {
		
		return method_exists( $subscription, 'get_parent' ) ? $subscription->get_parent() : $subscription->order;
	}
	
	/**
	 * Return the Quantity value for the provided product in the order
	 *
	 * @param \WC_Order_Item $order_item
	 * @param \WC_Order      $order
	 *
	 * @return int
	 */
	private function getOrderItemQty( $order_item, $order ) {
		
		$quantity   = 1;
		$product_id = wcs_get_canonical_product_id( $order_item );
		
		/**
		 * @var \WC_Order_Item $item_data
		 */
		foreach ( $order->get_items() as $item_id => $item_data ) {
			
			$o_product_id = intval( method_exists( $item_data, 'get_product_id' ) ? $item_data->get_product_id() : $item_data['product_id'] );
			
			if ( $o_product_id === $product_id ) {
				$quantity = intval( method_exists( $item_data, 'get_quantity' ) ) ? $item_data->get_quantity() : $order->get_item_meta( $item_id, '_qty', true );;
			}
		}
		
		return $quantity;
		
	}
	
	/**
	 * Action handler for when the user stops payment (or payment is stopped/cancelled) for a WooCommerce subscription
	 *
	 * @param \WC_Subscription $subscription
	 */
	public function cancel( $subscription ) {
		
		$utils         = Utilities::get_instance();
		$items         = $subscription->get_items();
		$sub_user_id   = $subscription->get_user_id();
		$controller    = Controller::getInstance();
		$wc_controller = WooCommerce::getInstance();
		
		if ( ! empty( $items ) && ! empty( $sub_user_id ) ) {
			
			$utils->log( "Found " . count( $items ) . " order items in subscription we've cancelled for {$sub_user_id}" );
			
			/**
			 * @var \WC_Order_Item $order_item
			 */
			foreach ( $items as $order_item ) {
				
				$order = $this->getOrderFromSub( $subscription );
				
				if ( ! empty( $order ) && $wc_controller->isLicdProduct( wcs_get_canonical_product_id( $order_item ) ) ) {
					
					$quantity = $this->getOrderItemQty( $order_item, $order );
					
					$utils->log( "Cancelling {$quantity} licenses for {$sub_user_id} and product: " . $order_item->get_id() );
					$controller->removeLicense( $order_item->get_id(), $sub_user_id, 'woocommerce', $quantity );
				}
			}
		}
		
		return;
	}
}