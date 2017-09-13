<?php
/*
Plugin Name: E20R: Add license on purchase
Plugin URI: https://eighty20results.com/wordpress-plugins/e20r-add-license-for-level/
Description: Sell software licenses from WooCommerce or Paid Memberships Pro
Version: 1.0
Author: Eighty / 20 results by Wicked Strong Chicks, LLC <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
Developer: Thomas Sjolshagen <thomas@eighty20results.com>
Developer URI: http://www.eighty20results.com/thomas-sjolshagen/
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

namespace E20R\Licensing\Purchase;

use E20R\Licensing\Purchase\PMPro\PMPro;
use E20R\Licensing\Purchase\WooCommerce\WooCommerce;
use E20R\Utilities\Utilities;

defined( 'ABSPATH' ) or die( 'Cannot access plugin sources directly' );

if ( ! defined( 'E20R_LICENSE_SERVER_VERSION' ) ) {
	define( 'E20R_LICENSE_SERVER_VERSION', '1.0' );
}

if ( ! defined( 'E20R_ALOP_URL' ) ) {
	define( 'E20R_ALOP_URL', trailingslashit( plugins_url( null, __FILE__ ) ) );
}

if ( ! defined( 'E20R_ALOP_DIR' ) ) {
	define( 'E20R_ALOP_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

global $e20rlm_level;
global $e20rlm_order;

class Controller {
	
	/**
	 * @var Controller|null
	 */
	private static $instance = null;
	
	/**
	 * Controller constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * The current instance of the Controller class
	 *
	 * @return Controller|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load payment processing hooks as needed
	 */
	public function loadHooks() {
		
		$utils = Utilities::get_instance();
		
		if ( ! defined( 'E20R_LICENSE_SECRET_VERIFY_KEY' ) ) {
			define( 'E20R_LICENSE_SECRET_VERIFY_KEY', get_option( 'e20rlm_api_verify_secret', null ) );
		}
		
		if ( ! defined( 'E20R_LICENSE_SECRET_CREATE_KEY' ) ) {
			define( 'E20R_LICENSE_SECRET_CREATE_KEY', get_option( 'e20rlm_api_create_secret', null ) );
		}
		
		if ( ! defined( 'E20R_LICENSE_SERVER_URL' ) ) {
			define( 'E20R_LICENSE_SERVER_URL', get_option( 'e20rlm_api_url', 'https://eighty20results.com' ) );
		}
		
		if ( true === $utils->plugin_is_active( null, 'WC' ) ) {
			add_action( 'init', array( WooCommerce::getInstance(), 'loadHooks' ), 10 );
		}
		
		if ( true === $utils->plugin_is_active( null, 'pmpro_getAllLevels' ) ) {
			add_action( 'init', array( PMPro::getInstance(), 'loadHooks' ), 10 );
		}
		
		add_shortcode( 'e20r_user_licenses', array( $this, 'licenseShortcode' ) );
	}
	
	/**
	 * Verify that the prerequisite plugins are active on this server
	 *
	 * @return bool
	 */
	public function checkPrereqs() {
		
		$retval = true;
		
		$utils = Utilities::get_instance();
		
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
				
				$msg = sprintf(
					__(
						'Required for \'%1$s\': Please install and/or activate <a href="%2$s" target="_blank">%3$s</a> on your site',
						'e20r-add-license-on-purchase'
					),
					__( 'E20R Add License On Purchase', 'e20r-add-license-on-purchase' ),
					$info['url'],
					$info['name']
				);
				
				$utils->add_message( $msg, 'error', 'backend' );
			}
			
			$retval = $retval && $is_active;
		}
		
		return $retval;
	}
	
	/**
	 * Update the License server with the required license info
	 *
	 * @param $action
	 * @param $settings
	 *
	 * @return bool
	 */
	public function contactServer( $action, $settings ) {
		
		$utils = Utilities::get_instance();
		
		/*
		if ( ! $this->checkPrereqs() ) {
			return false;
		}
		*/
		
		$api_params = array(
			'slm_action'        => $action,
			'license_key'       => $settings['license_key'],
			'secret_key'        => ( $action !== 'slm_create_new' ? E20R_LICENSE_SECRET_VERIFY_KEY : E20R_LICENSE_SECRET_CREATE_KEY ),
			'registered_domain' => ! empty( $settings['registered_domain'] ) ? $settings['registered_domain'] : null,
		);
		
		switch ( $action ) {
			
			case 'slm_create_new':
				$api_params['item_reference']      = ! empty( $settings['item_reference'] ) ? urlencode( $settings['item_reference'] ) : null;
				$api_params['first_name']          = ! empty( $settings['first_name'] ) ? $settings['first_name'] : null;
				$api_params['last_name']           = ! empty( $settings['last_name'] ) ? $settings['last_name'] : null;
				$api_params['email']               = ! empty( $settings['email'] ) ? $settings['email'] : null;
				$api_params['company_name']        = ! empty( $settings['company'] ) ? $settings['company'] : null;
				$api_params['txn_id']              = ! empty( $settings['txn_id'] ) ? $settings['txn_id'] : null;
				$api_params['max_allowed_domains'] = ! empty( $settings['max_allowed_domains'] ) ? $settings['max_allowed_domains'] : null;
				$api_params['date_created']        = ! empty( $settings['date_created'] ) ? $settings['date_created'] : null;
				$api_params['date_expiry']         = ! empty( $settings['date_expiry'] ) ? $settings['date_expiry'] : null;
				break;
			
			case 'slm_activate':
				$api_params['item_reference'] = ! empty( $settings['item_reference'] ) ? urlencode( $settings['item_reference'] ) : null;
				break;
			
			case 'slm_deactivate':
				// All required settings are already present
				break;
		}
		
		if ( ! empty( $api_params ) ) {
			
			$server_url = add_query_arg( $api_params, E20R_LICENSE_SERVER_URL );
			$utils->log( "Transmitting for action {$action}...: " . print_r( $api_params, true ) );
			$utils->log( "Sending to: {$server_url}" );
			
			// Send query to the license manager server
			$response = wp_remote_get(
				$server_url,
				array(
					'timeout'     => apply_filters( 'e20r-license-server-timeout', 60 ),
					'sslverify'   => true,
					'httpversion' => '1.1',
				)
			);
			
			$code = wp_remote_retrieve_response_code( $response );
			
			// Check for error in the response
			if ( 200 > $code || 399 < $code ) {
				
				$body = $utils->decode_response( wp_remote_retrieve_body( $response ) );
				$utils->log( "Remote license server error {$code}: " . print_r( $body, true ) );
				
				return false;
			} else {
				
				$server = $utils->decode_response( wp_remote_retrieve_body( $response ) );
				$utils->log( "Server response: " . print_r( $server, true ) );
				
				if ( 'success' !== $server->result ) {
					
					$utils->add_message( $server->message, 'error', 'backend' );
					$utils->log( "{$server->message}" );
					
					return false;
				}
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Display purchased license(s) for a given user account
	 *
	 * @param array $attrs
	 *
	 * @return null|string
	 */
	public function licenseShortcode( $attrs = array() ) {
		
		$utils = Utilities::get_instance();
		
		global $current_user;
		
		if ( ! is_user_logged_in() ) {
			return null;
		}
		
		$license_config = get_user_meta( $current_user->ID, "e20r_license_user_settings", true );
		
		// No license settings saved for this user.
		if ( empty( $license_config ) ) {
			return null;
		}
		
		ob_start(); ?>
        <h2><?php __( "Active Licenses", "e20r-add-license-on-purchase" ) ?></h2>
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
				foreach ( $license_config as $key => $order ) {
					foreach ( $order as $product_id => $settings ) {
						if ( ! empty( $settings['license_key'] ) ) {
							?>
                            <tr class="e20r-license-row">
                                <td class="e20r-license-name"><?php echo isset( $settings['item_reference'] ) ? esc_attr( $settings['item_reference'] ) : null; ?></td>
                                <td class="e20r-license-key"><?php echo isset( $settings['license_key'] ) ? esc_attr( $settings['license_key'] ) : null; ?></td>
                                <td class="e20r-license-expiry"><?php echo isset( $settings['date_expiry'] ) ? esc_attr( $settings['date_expiry'] ) : null; ?></td>
                                <td class="e20r-license-email"><?php echo isset( $settings['email'] ) ? esc_attr( urldecode( $settings['email'] ) ) : null; ?></td>
                            </tr>
							<?php
						}
					}
				} ?>
                </tbody>
            </table>
        </div>
		<?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * Add the license for the specified user_id to the license server.
	 *
	 * @param int    $user_id
	 * @param string $source
	 * @param int    $id
	 * @param int    $quantity
	 *
	 * @return bool|array
	 */
	public
	function addLicense(
		$id, $user_id, $source, $quantity = 1
	) {
		
		$controller = Controller::getInstance();
		$utils      = Utilities::get_instance();
		
		$lm_url        = E20R_LICENSE_SERVER_URL;
		$lm_key        = E20R_LICENSE_SECRET_CREATE_KEY;
		$user_settings = array();
		
		if ( empty( $lm_url ) || empty( $lm_key ) ) {
			
			$utils->log( "Missing server URL or key loaded" );
			$settings_url = add_query_arg(
				array(
					'page'    => 'wc-settings',
					'tab'     => 'products',
					'section' => 'e20rlm',
				),
				admin_url( 'admin.php' )
			);
			
			$utils->add_message(
				sprintf(
					__( 'Missing Server URL or secure key! Please %1$supdate your settings%2$s.', 'e20r-add-license-on-purchase' ),
					sprintf( '<a href="%s">', $settings_url ),
					'</a>' ),
				'error',
				'backend'
			);
		}
		
		$utils->log( "Adding license for {$id}/{$user_id}/{$source}/{$quantity}" );
		
		$user   = get_userdata( $user_id );
		$action = 'slm_create_new';
		
		$user_meta       = apply_filters( 'e20r-license-server-billing-info', array(), $user, $source );
		$txn_id          = apply_filters( 'e20r-license-server-txn-id', null, $source );
		$date_expiry     = apply_filters( 'e20r-licensing-server-expiration-date', null, $id, $source );
		$random          = $utils->random_string( 8, '01234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
		$license_key     = apply_filters( 'e20r-licensing-server-client-key', "e20r_dl_{$random}", $id, $source, $random );
		$domains_allowed = apply_filters( 'e20r-license-server-domains-per-license', $quantity, $id, $source );
		
		$utils->log( "Txn ID: {$txn_id}, Expiry: {$date_expiry}, Key: {$license_key}, No Domains: {$domains_allowed}" );
		
		if ( empty( $domains_allowed ) ) {
			$utils->log( "Unable to determine the number of domains to allow" );
			
			return false;
		}
		
		$settings = array(
			'license_key'         => $license_key,
			'registered_domain'   => null,
			'status'              => 'pending',
			'first_name'          => urlencode( $user_meta['first_name'] ),
			'last_name'           => urlencode( $user_meta['last_name'] ),
			'email'               => urlencode( $user_meta['email'] ),
			'company_name'        => urlencode( $user_meta['company'] ),
			'txn_id'              => urlencode( $txn_id ),
			'max_allowed_domains' => $domains_allowed,
			'date_created'        => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'date_expiry'         => $date_expiry,
		);
		
		if ( ! isset( $user_settings[ $id ] ) ) {
			$user_settings[ $id ] = array();
		}
		
		$user_settings[ $id ]                   = $settings;
		$user_settings[ $id ]['item_reference'] = apply_filters( 'e20r-license-server-license-name', null, $id, $source );
		$user_settings[ $id ]['item_reference'] .= " for {$user_meta['email']}";
		
		$utils->log( "Action {$action} settings for {$user_id}: " . print_r( $user_settings, true ) );
		
		// Add the new license (with a license key).
		if ( true === $controller->contactServer( $action, $settings ) ) {
			
			$utils->log( "Initial license created on remote server" );
			
			$action   = 'slm_activate';
			$settings = array(
				'license_key'       => $license_key,
				'registered_domain' => 'mytest.example.com',
				'item_reference'    => urlencode( $user_settings[ $id ]['item_reference'] ),
			);
			
			// Activate the license (to set the Level info)
			if ( true === $controller->contactServer( $action, $settings ) ) {
				
				// Deactivate the license (to pending) so the user can load it manually later.
				$action   = 'slm_deactivate';
				$settings = array(
					'license_key'       => $license_key,
					'registered_domain' => 'mytest.example.com',
				);
				
				// Deactivate license so the server where the product is installed can connect & activate us
				if ( true === $controller->contactServer( $action, $settings ) ) {
					
					return $user_settings;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Return HTML table with License Information
	 *
	 * @param array $licenses
	 *
	 * @return string
	 */
	public
	function licenseInfo(
		$licenses
	) {
		
		$utils = Utilities::get_instance();
		
		$content = sprintf( '<H3>%s</H3>', __( "License information", "e20r-add-license-on-purchase" ) );
		$content .= sprintf( '<table>' );
		$content .= sprintf( '	<thead>' );
		$content .= sprintf( '	<tr>' );
		$content .= sprintf( '      <th class="td">%s</th>', __( "License", "e20r-add-license-on-purchase" ) );
		$content .= sprintf( '      <th class="td">%s</th>', __( "License Key", "e20r-add-license-on-purchase" ) );
		$content .= sprintf( '      <th class="td">%s</th>', __( "Expiration date", "e20r-add-license-on-purchase" ) );
		$content .= sprintf( '   </tr>' );
		$content .= sprintf( '	</thead>' );
		$content .= sprintf( '  <tbody>' );
		
		foreach ( $licenses as $key => $license_info ) {
			
			foreach ( $license_info as $id => $detail ) {
				$utils->log( "Processing license for product {$id}: " . print_r( $detail, true ) );
				
				$content .= sprintf( '  <tr>' );
				
				if ( ! empty( $detail['item_reference'] ) ) {
					$content .= sprintf( '      <td class="td">%s</td>', esc_attr( $detail['item_reference'] ) );
				} else {
					$content .= sprintf( '      <td class="td">%s</td>', __( "N/A", 'e20r-add-license-on-purchase' ) );
				}
				
				if ( ! empty( $detail['license_key'] ) ) {
					$content .= sprintf( '      <td class="td">%s</td>', esc_attr( $detail['license_key'] ) );
				} else {
					$content .= sprintf( '      <td class="td">%s</td>', __( "N/A", 'e20r-add-license-on-purchase' ) );
				}
				
				if ( ! empty( $detail['date_expiry'] ) ) {
					$content .= sprintf( '      <td class="td">%s</td>', esc_attr( $detail['date_expiry'] ) );
				} else {
					$content .= sprintf( '      <td class="td">%s</td>', __( "N/A", 'e20r-add-license-on-purchase' ) );
				}
				
				$content .= sprintf( '  </tr>' );
			}
		}
		
		$content .= sprintf( '  </tbody>' );
		$content .= sprintf( '</table>' );
		
		$utils->log( $content );
		
		return $content;
	}
	
	/**
	 * Class auto-loader for the Add License on Purchase plugin
	 *
	 * @param string $class_name Name of the class to auto-load
	 *
	 * @since  1.0
	 * @access public static
	 */
	public
	static function autoLoader(
		$class_name
	) {
		
		if ( false === stripos( $class_name, 'e20r' ) ) {
			return;
		}
		
		$parts     = explode( '\\', $class_name );
		$c_name    = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
		$base_path = plugin_dir_path( __FILE__ ) . 'classes/';
		
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'class/' ) ) {
			$base_path = plugin_dir_path( __FILE__ ) . 'class/';
		}
		
		$filename = "class.{$c_name}.php";
		$iterator = new \RecursiveDirectoryIterator( $base_path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveIteratorIterator::SELF_FIRST | \RecursiveIteratorIterator::CATCH_GET_CHILD | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS );
		
		/**
		 * Loate class member files, recursively
		 */
		$filter = new \RecursiveCallbackFilterIterator( $iterator, function ( $current, $key, $iterator ) use ( $filename ) {
			
			$file_name = $current->getFilename();
			
			// Skip hidden files and directories.
			if ( $file_name[0] == '.' || $file_name == '..' ) {
				return false;
			}
			
			if ( $current->isDir() ) {
				// Only recurse into intended subdirectories.
				return $file_name() === $filename;
			} else {
				// Only consume files of interest.
				return strpos( $file_name, $filename ) === 0;
			}
		} );
		
		foreach ( new \ RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {
			
			$class_path = $f_file->getPath() . "/" . $f_file->getFilename();
			
			if ( $f_file->isFile() && false !== strpos( $class_path, $filename ) ) {
				require_once( $class_path );
			}
		}
	}
}

spl_autoload_register( 'E20R\\Licensing\\Purchase\\Controller::autoLoader' );

add_action( 'plugins_loaded', array( Controller::getInstance(), 'loadHooks' ) );
add_action( 'http_api_curl', array( Utilities::get_instance(), 'force_tls_12' ) );


// One-click update handler
if ( ! class_exists( '\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-add-license-on-purchase/metadata.json',
	__FILE__,
	'e20r-add-license-on-purchase'
);
