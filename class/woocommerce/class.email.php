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

class EMail {
	
	/**
	 * @var null|EMail
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the WooCommerce EMail class
	 *
	 * @return EMail|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load required WooCommerce Email hooks
	 */
	public function loadHooks() {
	
	}
	
	/**
	 * @param \WC_Order $order
	 * @param bool $sent_to_admin
	 * @param string $plain_text
	 * @param string $email
	 */
	public function content( $order, $sent_to_admin, $plain_text, $email ) {
		
		$content = null;
		$utils = Utilities::get_instance();
		$controller = Controller::getInstance();
		
		$utils->log("Sent_to_admin? {$sent_to_admin}");
		
		$status = $order->get_status();
		$order_id = $order->get_id();
		$user_id = $order->get_user_id();
		
		if ( 'completed' !== $status ) {
			$utils->log("Returning: {$order_id} is not a completed order yet: {$status}");
			return;
		}
		
		$licenses = $controller->getUserLicenses( $user_id );
		
		$utils->log( "License info: " . print_r( $licenses, true ));
		if ( ! empty( $licenses ) && is_array( $licenses ) ) {
			
			$content = $controller->licenseInfo( $licenses );
		}
		
		$utils->log("For Email: {$content} ");
		printf( "%s", $content );
	}
}