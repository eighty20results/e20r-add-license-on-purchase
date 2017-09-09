<?php
/*
Plugin Name: E20R: Add License to Membership Level for Paid Memberships Pro
Plugin URI: https://eighty20results.com/wordpress-plugins/e20r-add-license-for-level/
Description: Create a level specific license on checkout (uses Level Name as 'item_reference')
Version: 1.8.1
Author: Thomas Sjolshagen - Wicked Strong Chicks, LLC <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
License: GPL2
Text Domain: e20r-add-license-on-purchase
Domain Path: /languages
*/

/**
 * Copyright (c) 2016-2017 - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Licensing\Server;

use E20R\Utilities\Utilities;

defined( 'ABSPATH' ) or die( 'Cannot access plugin sources directly' );

if ( ! defined( 'E20R_LICENSE_SERVER_URL' ) ) {
	define( 'E20R_LICENSE_SERVER_URL', 'https://eighty20results.com' );
}

if ( ! defined( 'E20R_LICENSE_SECRET_VERIFY_KEY' ) ) {
	define( 'E20R_LICENSE_SECRET_VERIFY_KEY', '5687dc27b50520.33717427' );
}

if ( ! defined( 'E20R_LICENSE_SECRET_CREATE_KEY' ) ) {
	define( 'E20R_LICENSE_SECRET_CREATE_KEY', '5687dc27b50479.13170123' );
}

class Add_License_On_Purchase {
	
	
	/**
	 * Add the license for the specified user_id to the license server.
	 *
	 * @param              $user_id
	 * @param \MemberOrder $order
	 * @param int          $level_id
	 *
	 * @return bool
	 */
	public function addLicense( $user_id, $order, $level_id = null ) {
		
		global $pmpro_pages;
		global $pmpro_msg;
		global $pmpro_msgt;
		global $pmpro_checkout_levels;
		
		$utils = Utilities::get_instance();
		
		$this->checkPrereqs();
		
		// Process each of the levels supplied.
		foreach ( $pmpro_checkout_levels as $level ) {
			
			// No license needed for free membership level(s).
			/*
			if ( pmpro_isLevelFree( $level ) ) {
				
				if (WP_DEBUG) {
					error_log("Skipping for free level(s).. {$level->name}" );
				}
				continue;
			}
			*/
			// Load settings
			$license_keys = get_option( 'e20r_license_settings' );
			
			// Skip this if the level doesn't have a license requirement
			if ( empty( $license_keys[ $level->id ]['key'] ) ) {
				if ( WP_DEBUG ) {
					error_log( "Skipping {$level->id} since it doesn't have a license to activate/create" );
				}
				continue;
			}
			
			// Load membership order & user info for the checkout
			$user = get_userdata( $user_id );
			
			$action = 'slm_create_new';
			$txn_id = ! empty( $order->payment_transaction_id ) ? $order->payment_transaction_id : null;
			
			$allowed_domains = apply_filters( 'e20r_licensing_domains_per_license', 1 );
			$first_name      = $user->user_firstname;
			$last_name       = $user->user_lastname;
			$email_address   = $user->user_email;
			$status          = 'pending';
			$date_created    = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
			$date_expiry     = date_i18n( 'Y-m-d', strtotime( "+{$level->cycle_number} {$level->cycle_period}", current_time( 'timestamp' ) ) );
			
			// Random string of uppercase letters & numbers
			$random      = $utils->randomStr( 8, '01234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$license_key = "e20r_dl_{$random}";
			
			if ( ! empty( $keys[ $level->id ] ) ) {
				$license_key = "{$keys[$level->id]['key']}_{$random}";
			}
			
			$item_reference = "{$keys[$level->id]['name']} - {$email_address}";
			
			$settings = array(
				'license_key'         => $license_key,
				'registered_domain'   => null,
				'status'              => $status,
				'first_name'          => urlencode( $first_name ),
				'last_name'           => urlencode( $last_name ),
				'email'               => urlencode( $email_address ),
				'txn_id'              => urlencode( $txn_id ),
				'max_allowed_domains' => $allowed_domains,
				'date_created'        => $date_created,
				'date_expiry'         => $date_expiry,
			);
			
			$user_settings = get_user_meta( $user_id, 'e20r_license_user_settings', true );
			
			if ( ! is_array( $user_settings ) ) {
				$all_settings = array();
			} else {
				$all_settings = $user_settings;
			}
			
			$all_settings[ $level->id ]                   = $settings;
			$all_settings[ $level->id ]['item_reference'] = $item_reference;
			
			// Add the new license (with a license key).
			if ( true === $this->contactServer( $action, $settings ) ) {
				
				$action   = 'slm_activate';
				$settings = array(
					'license_key'       => $license_key,
					'registered_domain' => 'mytest.example.com',
					'item_reference'    => urlencode( $item_reference ),
				);
				// Activate the license (to set the Level info)
				
				if ( true === $this->contactServer( $action, $settings ) ) {
					
					// Deactivate the license (to pending) so the user can load it manually later.
					$action   = 'slm_deactivate';
					$settings = array(
						'license_key'       => $license_key,
						'registered_domain' => 'mytest.example.com',
					);
					
					// Deactivate license so the server where the product is installed can connect & activate us
					if ( true === $this->contactServer( $action, $settings ) ) {
						
						$em           = new \PMProEmail();
						$em->email    = $email_address;
						$em->from     = pmpro_getOption( "from_email" );
						$em->fromname = pmpro_getOption( "from_name" );
						
						if ( file_exists( plugin_dir_path( __FILE__ ) . "/email/e20r_license.html" ) ) {
							
							$em->body = file_get_contents( plugin_dir_path( __FILE__ ) . "/email/e20r_license.html" );
						} else {
							
							$utils->log( "License: Couldn't find the license email template" );
							
							return false;
						}
						
						$em->template = 'e20r_license';
						$em->subject  = sprintf( __( "License: %s", "e20r-add-license-on-purchase" ), $keys[ $level->id ]['name'] );
						$em->data     = array(
							'license_name'    => $keys[ $level->id ]['name'],
							'license_key'     => $license_key,
							'email_address'   => $email_address,
							'expiration_date' => $date_expiry,
							'domains'         => $allowed_domains,
							'account_link'    => wp_login_url( get_permalink( $pmpro_pages['account'] ) ),
						);
						
						if ( false == $em->sendEmail() ) {
							
							$pmpro_msg  = sprintf( __( "Unable to send license information email to %s. Please contact Support!", "e20r-add-license-on-purchase" ), $settings['email'] );
							$pmpro_msgt = "error";
							
							return false;
						} else {
							update_user_meta( $user_id, "e20r_license_user_settings", $all_settings );
						}
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Update the license if the membership level is changed to something positive (not 0)
	 *
	 * @param int $level_id
	 * @param int $user_id
	 * @param int $cancel_level
	 */
	function levelChanged( $level_id, $user_id, $cancel_level ) {
		
		global $pmpro_checkout_levels;
		
		if ( WP_DEBUG ) {
			error_log( "In level_changed: {$user_id} -> {$level_id}" );
		}
		
		if ( $level_id !== 0 && $cancel_level !== $level_id ) {
			
			$order = new \MemberOrder();
			$order->getLastMemberOrder( $user_id, 'active', $level_id );
			
			// Handle situations where the level is assigned to the user
			if ( empty( $pmpro_checkout_levels ) ) {
				$pmpro_checkout_levels = array( pmpro_getLevel( $level_id ) );
			}
			
			if ( WP_DEBUG ) {
				error_log( "Found an order? " . ( isset( $order->membership_id ) ? "Yes" : "No" ) );
			}
			
			$this->addLicense( $user_id, $order );
		}
		
		// TOOD: Handle cancellations?
	}
	
	public function checkPrereqs() {
		
		$retval = true;
		
		$active_plugins   = get_option( 'active_plugins', array() );
		$required_plugins = array(
			'software-license-manager/slm_bootstrap.php'    => array(
				'name' => __( 'Software License Manager', 'e20r-add-license-on-purchase' ),
				'url'  => 'https://wordpress.org/plugins/software-license-manager/',
			),
			'paid-memberships-pro/paid-memberships-pro.php' => array(
				'name' => __( 'Paid Memberships Pro', 'e20r-add-license-on-purchase' ),
				'url'  => 'https://wordpress.org/plugins/paid-memberships-pro/',
			),
		);
		
		foreach ( $required_plugins as $plugin_file => $info ) {
			
			$is_active = in_array( $plugin_file, $active_plugins );
			
			if ( is_multisite() ) {
				
				$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
				
				$is_active =
					$is_active ||
					key_exists( $plugin_file, $active_sitewide_plugins );
			}
			
			if ( false === $is_active ) {
				
				global $msg;
				global $msgt;
				global $pmpro_msg;
				global $pmpro_msgt;
				
				$msg = $pmpro_msg = sprintf(
					__(
						'Required for \'%1$s\': Please install and/or activate <a href="%2$s" target="_blank">%3$s</a> on your site',
						'e20r-add-license-on-purchase'
					),
					__( 'E20R Add License For Level', 'e20r-add-license-on-purchase' ),
					$info['url'],
					$info['name']
				);
				
				$msgt = $pmpro_msgt = 'warning';
			}
			
			$retval = $retval && $is_active;
		}
		
		return $retval;
	}
	
	public function contactServer( $action, $settings ) {
		
		if ( ! $this->checkPrereqs() ) {
			return false;
		}
		
		$utils = Utilities::get_instance();
		
		$api_params = array(
			'slm_action'        => $action,
			'license_key'       => $settings['license_key'],
			'secret_key'        => ( $action !== 'slm_create_new' ? E20R_LICENSE_SECRET_VERIFY_KEY : E20R_LICENSE_SECRET_CREATE_KEY ),
			'registered_domain' => ! empty( $settings['registered_domain'] ) ? $settings['registered_domain'] : null,
		);
		
		switch ( $action ) {
			
			case 'slm_create_new':
				$api_params = array_merge( $api_params, array(
					'item_reference'      => ! empty( $settings['item_reference'] ) ? urlencode( $settings['item_reference'] ) : null,
					'first_name'          => ! empty( $settings['first_name'] ) ? $settings['first_name'] : null,
					'last_name'           => ! empty( $settings['last_name'] ) ? $settings['last_name'] : null,
					'email'               => ! empty( $settings['email'] ) ? $settings['email'] : null,
					'txn_id'              => ! empty( $settings['txn_id'] ) ? $settings['txn_id'] : null,
					'max_allowed_domains' => ! empty( $settings['max_allowed_domains'] ) ? $settings['max_allowed_domains'] : null,
					'date_created'        => ! empty( $settings['date_created'] ) ? $settings['date_created'] : null,
					'date_expiry'         => ! empty( $settings['date_expiry'] ) ? $settings['date_expiry'] : null,
				) );
				break;
			
			case 'slm_activate':
				
				$api_params = array_merge( $api_params, array(
					'item_reference' => ! empty( $settings['item_reference'] ) ? urlencode( $settings['item_reference'] ) : null,
				) );
				
				break;
			
			case 'slm_deactivate':
				// All required settings are already present
				break;
		}
		
		if ( ! empty( $api_params ) ) {
			
			$utils->log( "Transmitting for action {$action}...: " . print_r( $api_params, true ) );
			
			
			// Send query to the license manager server
			$response = wp_remote_get(
				add_query_arg( $api_params, E20R_LICENSE_SERVER_URL ),
				array(
					'timeout'     => apply_filters( 'e20r-license-server-timeout', 60 ),
					'sslverify'   => true,
					'httpversion' => '1.1',
				)
			);
			
			// Check for error in the response
			if ( is_wp_error( $response ) ) {
				
				$utils->log( "License server error: " . $response->get_error_message() );
				
				return false;
			} else {
				
				$server = json_decode( wp_remote_retrieve_body( $response ) );
				
				if ( 'success' !== $server->result ) {
					global $pmpro_msg;
					global $pmpro_msgt;
					
					$pmpro_msg  = $server->message;
					$pmpro_msgt = 'error';
					
					return false;
				}
			}
			
			return true;
		}
		
		return false;
	}

// ToDo: Generate shortcode for the user specific user license information.
	
	public function licenseShortcode( $attrs = array() ) {
		
		if ( ! $this->checkPrereqs() ) {
			return null;
		}
		
		global $current_user;
		
		if ( ! is_user_logged_in() ) {
			return null;
		}
		
		$license_config = get_user_meta( $current_user->ID, "e20r_license_user_settings", true );
		
		// No license settings saved for this user.
		if ( empty( $license_config ) ) {
			return null;
		}
		
		$license_map = get_option( 'e20r_license_settings' );
		
		ob_start(); ?>
        <h2><?php ?></h2>
        <div class="e20r-license-list">
            <table class="e20r-license-list">
                <thead>
                <tr class="e20r-license-thead">
                    <th><?php _e( "License Name", "e20r-add-license-on-purchase" ); ?></th>
                    <th><?php _e( "License Key", "e20r-add-license-on-purchase" ); ?></th>
                    <th><?php _e( "Expires", "e20r-add-license-on-purchase" ); ?></th>
                    <th><?php _e( "License Email", "e20r-add-license-on-purchase" ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php
				foreach ( $license_config as $license_id => $settings ) { ?>
                    <tr class="e20r-license-row">
                        <td class="e20r-license-name"><?php echo isset( $license_map[ $id ]['name'] ) ? esc_attr( $license_map[ $license_id ]['name'] ) : null; ?></td>
                        <td class="e20r-license-key"><?php echo isset( $settings['license_key'] ) ? esc_attr( $settings['license_key'] ) : null; ?></td>
                        <td class="e20r-license-expiry"><?php echo isset( $settings['date_expiry'] ) ? esc_attr( $settings['date_expiry'] ) : null; ?></td>
                        <td class="e20r-license-email"><?php echo isset( $settings['email'] ) ? esc_attr( urldecode( $settings['email'] ) ) : null; ?></td>
                    </tr>
					<?php
				} ?>
                </tbody>
            </table>
        </div>
		<?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * Generate HTML for the level settings
	 *
	 * @param bool $echo - Whether to echo or return the HTML
	 *
	 * @return string - The HTML
	 */
	public function licenseSettings( $echo = true ) {
		
		
		if ( ! $this->checkPrereqs() ) {
			return;
		}
		
		$level_id  = isset( $_REQUEST['edit'] ) ? intval( $_REQUEST['edit'] ) : - 1;
		$value_map = get_option( 'e20r_license_settings' );
		
		$level = pmpro_getLevel( $level_id );
		
		if ( pmpro_isLevelFree( $level ) ) {
			return;
		}
		?>
        <div class="e20r-license-settings">
            <h3 class="e20r-license-settings-header topborder"><?php echo __( "License Settings", "e20r-add-license-on-purchase" ); ?></h3>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row" valign="top" class="e20r-license">
                        <label for="e20r-license-key"><?php _e( 'License level (product) key', 'e20r-add-license-on-purchase' ); ?></label>:
                    </th>
                    <td>
                        <input id="e20r-license-key" name="e20r-license-key" type="text"
                               value="<?php echo( ! empty( $value_map[ $level_id ]['key'] ) ? $value_map[ $level_id ]['key'] : null ); ?>"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="e20r-license">
                        <label for="e20r-license-name"><?php _e( 'License Name', 'e20r-add-license-on-purchase' ); ?></label>:
                    </th>
                    <td>
                        <input id="e20r-license-name" name="e20r-license-name" type="text"
                               style="min-width: 250px; max-width: 400px; width: 100%;"
                               value="<?php echo( ! empty( $value_map[ $level_id ]['name'] ) ? $value_map[ $level_id ]['name'] : null ); ?>"/>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
		<?php
	}
	
	/**
	 * Save the license key value for the membership level
	 *
	 * @return bool
	 */
	public function saveSettings( $level_id ) {
		
		if ( ! $this->checkPrereqs() ) {
			return false;
		}
		
		$utils = Utilities::get_instance();
		
		$license_key  = $utils->get_variable( 'e20r-license-key', null );
		$license_name = $utils->get_variable( 'e20r-license-name', null );
		
		if ( is_null( $license_key ) || is_null( $level_id ) || is_null( $license_name ) ) {
			
			$utils->add_message( __( 'License Name or Key missing in level definition', "e20r-add-license-on-purchase" ), 'error', 'backend' );
			
			return false;
		}
		
		$key_map = get_option( 'e20r_license_settings' );
		
		if ( ! is_array( $key_map ) ) {
			$key_map = array();
		}
		
		
		$key_map[ $level_id ] = array(
			'key'  => $license_key,
			'name' => $license_name,
		);
		
		update_option( 'e20r_license_settings', $key_map, false );
	}
	
}

add_action( 'pmpro_save_membership_level', array( Add_License_On_Purchase::get_instance(), 'saveSettings' ), 10, 1 );
add_action( 'pmpro_after_change_membership_level', array( Add_License_On_Purchase::get_instance(), 'levelChanged' ), 10, 3 );
add_action( 'pmpro_membership_level_after_other_settings', array( Add_License_On_Purchase::get_instance(), 'licenseSettings', ), 10, 0 );
// add_action( 'pmpro_after_checkout', array( Add_License_On_Purchase::get_instance(), 'addLicense' ), 10, 2 );

add_shortcode( 'e20r_user_licenses', array( Add_License_On_Purchase::get_instance(), 'licenseShortcode' ) );

add_action( 'http_api_curl', array( Utilities::get_instance(), 'force_tls_12') );

// One-click update handler
if ( ! class_exists( '\\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-add-license-on-purchase/metadata.json',
	__FILE__,
	'e20r-add-license-on-purchase'
);
