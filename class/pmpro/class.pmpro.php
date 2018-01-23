<?php
/**
 * Copyright (c) 2017-2018 - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Licensing\Purchase\PMPro;

use E20R\Licensing\Purchase\Controller;
use E20R\Utilities\Utilities;

class PMPro {
	
	/**
	 * @var null|PMPro
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the PMPro class
	 *
	 * @return PMPro|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load required PMPro Hooks
	 */
	public function loadHooks() {
		
	    add_action( 'admin_menu', array( Settings::getInstance(), 'addSLMSettings' ), 11 );
        add_action( 'admin_bar_menu', array( Settings::getInstance(), 'loadMenuBarEntry' ), 1001 );
        add_action( 'admin_enqueue_scripts', array( Settings::getInstance(), 'loadScripts' ) );
        add_action( 'wp_ajax_e20r_save_pmpro_settings', array( Settings::getInstance(), 'save' ) );
        
		add_filter( 'e20r-license-server-slm-settings', array( Settings::getInstance(), 'fieldDefs'), 10, 2 );
		
		add_action( 'pmpro_save_membership_level', array( $this, 'saveSettings' ), 10, 1 );
		add_action( 'pmpro_after_change_membership_level', array( $this, 'levelChanged' ), 10, 3 );
		add_action( 'pmpro_membership_level_after_other_settings', array( $this, 'licenseSettings', ), 10, 1 );
		
		add_filter( 'e20r-license-server-txn-id', array( $this, 'getTransactionId' ), 10, 5 );
		add_filter( 'e20r-license-server-billing-info', array( $this, 'getBillingInfo' ), 10, 3 );
		add_filter( 'e20r-licensing-server-expiration-date', array( $this, 'expires' ), 10, 3 );
		add_filter( 'e20r-licensing-server-client-key', array( $this, 'createLicenseKey' ), 10, 4 );
		add_filter( 'e20r-license-server-domains-per-license', array( $this, 'domains' ), 10, 3 );
		add_filter( 'e20r-license-server-license-name', array( $this, 'licenseName' ), 10, 3 );
  
	}
 
	/**
	 * Update the license if the membership level is changed to something positive (not 0)
	 *
	 * @param int $level_id
	 * @param int $user_id
	 * @param int $cancel_level
	 */
	public function levelChanged( $level_id, $user_id, $cancel_level ) {
		
		$utils = Utilities::get_instance();
		
		global $pmpro_checkout_levels;
		
		$utils->log( "In level_changed: {$user_id} -> {$level_id}" );
		
		if ( $level_id !== 0 && $cancel_level !== $level_id ) {
			
			$order = new \MemberOrder();
			$order->getLastMemberOrder( $user_id, 'active', $level_id );
			
			// Handle situations where the level is assigned to the user
			if ( empty( $pmpro_checkout_levels ) ) {
				
				if ( ! is_array( $pmpro_checkout_levels ) ) {
					$pmpro_checkout_levels = array();
				}
			}
			
			$pmpro_checkout_levels[] = pmpro_getLevel( $level_id );
			
			$utils->log( "Found a preexisting order? " . ( isset( $order->membership_id ) ? "Yes" : "No" ) );
			
			$this->checkout( $user_id, $order, $level_id );
		}
		
		// TODO: Handle membership cancellations
		if ( $level_id === 0 && $cancel_level > 0 ) {
			$license_config = get_user_meta( $user_id, '', true );
		}
	}
	
	/**
	 * Generate HTML for the level settings
	 *
	 * @param bool $echo - Whether to echo or return the HTML
	 *
	 * @return string - The HTML
	 */
	public function licenseSettings( $echo = true ) {
		
		$utils = Utilities::get_instance();
		
		$level_id  = $utils->get_variable( 'edit', - 1 );
		$value_map = get_option( 'e20r_pmpro_license_server', array() );
		
		?>
        <div class="e20r-license-settings">
            <h3 class="e20r-license-settings-header topborder"><?php _e( "License Settings", "e20r-add-license-on-purchase" ); ?></h3>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row" valign="top" class="e20r-license">
                        <label for="e20r-license-key"><?php _e( 'License level (product) key', 'e20r-add-license-on-purchase' ); ?></label>:
                    </th>
                    <td>
                        <input id="e20r-license-key" name="e20r-license-key" type="text"
                               value="<?php echo( ! empty( $value_map[ $level_id ]['license_key'] ) ? $value_map[ $level_id ]['license_key'] : null ); ?>"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="e20r-license">
                        <label for="e20r-license-name"><?php _e( 'License Name', 'e20r-add-license-on-purchase' ); ?></label>:
                    </th>
                    <td>
                        <input id="e20r-license-name" name="e20r-license-name" type="text"
                               style="min-width: 250px; max-width: 400px; width: 100%;"
                               value="<?php echo( ! empty( $value_map[ $level_id ]['license_name'] ) ? $value_map[ $level_id ]['license_name'] : null ); ?>"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="e20r-license">
                        <label for="e20r-license-sites"><?php _e( 'Number of sites included', 'e20r-add-license-on-purchase' ); ?></label>:
                    </th>
                    <td>
                        <input id="e20r-license-sites" name="e20r-license-sites" type="number"
                               style="min-width: 250px; max-width: 400px; width: 100%;"
                               value="<?php echo( ! empty( $value_map[ $level_id ]['site_count'] ) ? $value_map[ $level_id ]['site_count'] : null ); ?>"/>
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
		
		$utils = Utilities::get_instance();
		
		$license_key  = $utils->get_variable( 'e20r-license-key', null );
		$license_name = $utils->get_variable( 'e20r-license-name', null );
		$sites        = $utils->get_variable( 'e20r-license-sites', 1 );
		
		if ( is_null( $license_key ) || is_null( $license_name ) ) {
			
			$utils->add_message( __( 'License Name or Key missing in level definition', "e20r-add-license-on-purchase" ), 'error', 'backend' );
			
			return false;
		}
		
		$key_map = get_option( 'e20r_pmpro_license_server', array() );
		
		if ( ! is_array( $key_map ) ) {
			$key_map = array();
		}
		
		
		$key_map[ $level_id ] = array(
			'license_key'  => $license_key,
			'license_name' => $license_name,
			'site_count'   => $sites,
		);
		
		update_option( 'e20r_pmpro_license_server', $key_map, false );
	}
	
	/**
	 * Add the license for the specified user_id to the license server.
	 *
	 * @param int                    $user_id
	 * @param \MemberOrder|\WC_Order $order
	 * @param int|null               $level_id
	 *
	 * @return bool
	 */
	public function checkout( $user_id, $order, $level_id = null ) {
		
		global $pmpro_pages;
		global $pmpro_checkout_levels;
		global $e20rlm_level;
		global $e20rlm_order;
		
		$e20rlm_order = $order;
		$utils        = Utilities::get_instance();
		$controller   = Controller::getInstance();
		
		$licenses = get_user_meta( $user_id, "e20r_license_user_settings", true );
		
		if ( empty( $licenses ) ) {
			$licenses = array();
		}
		
		// Process each of the levels supplied.
		foreach ( $pmpro_checkout_levels as $level ) {
			
			$e20rlm_level = $level;
			
			// Load settings
			$license_keys = get_option( 'e20r_pmpro_license_server' );
			
			// Skip this if the level doesn't have a license requirement
			if ( empty( $license_keys[ $level->id ]['license_key'] ) ) {
				
				$utils->log( "Skipping {$level->id} since it doesn't have a license to activate/create" );
				continue;
			}
			
			// Load membership order & user info for the checkout
			$user_settings = $controller->addLicense( $level->id, $user_id, 'pmpro' );
			$licenses[]    = $user_settings;
			
			if ( false !== $user_settings ) {
				
				$email           = new \PMProEmail();
				$email->email    = $user_settings[ $level->id ]['email'];
				$email->from     = pmpro_getOption( "from_email" );
				$email->fromname = pmpro_getOption( "from_name" );
				
				if ( file_exists( plugin_dir_path( __FILE__ ) . "../email/e20r_license.html" ) ) {
					
					$email->body = file_get_contents( E20R_ALOP_DIR . "/email/e20r_license.html" );
				} else {
					
					$utils->log( "License: Couldn't find the license email template" );
					
					return false;
				}
				
				$email->template = 'e20r_license';
				$email->subject  = sprintf( __( "License: %s", "e20r-add-license-on-purchase" ), $user_settings[ $level->id ]['item_reference'] );
				$email->data     = array(
					'license_name'    => $user_settings[ $level->id ]['item_reference'],
					'license_key'     => $user_settings[ $level->id ]['license_key'],
					'email_address'   => $user_settings[ $level->id ]['email'],
					'expiration_date' => $user_settings[ $level->id ]['date_expiry'],
					'domains'         => $user_settings[ $level->id ]['max_allowed_domains'],
					'account_link'    => wp_login_url( get_permalink( $pmpro_pages['account'] ) ),
				);
				
				if ( false == $email->sendEmail() ) {
					
					$msg = sprintf( __( "Unable to send license information email to %s. Please %scontact Support%s!", "e20r-add-license-on-purchase" ), $user_settings[ $level->id ]['email'], '<a href="https://eighty20results.com/need-something-else//">', '</a>' );
					$utils->add_message( $msg, 'error', 'frontend' );
					$utils->log( $msg );
					
					return false;
				}
				
				update_user_meta( $user_id, "e20r_license_user_settings", $licenses );
			} else {
				
				$msg = sprintf( __( "Unable to generate license. Please %scontact Support%s!", "e20r-add-license-on-purchase" ), '<a href="https://eighty20results.com/need-something-else//">', '</a>' );
				$utils->add_message( $msg, 'error', 'frontend' );
				$utils->log( $msg );
				
				return false;
			}
			
		}
		
		return true;
	}
	
	/**
	 * Create the client key for the license (PMPro Specific)
	 *
	 * @param string $key    Default Key name
	 * @param int    $id     The ID for the product/level being processed
	 * @param string $source 'pmpro'|'woocommerce'
	 * @param string $random Generated random string
	 *
	 * @return string
	 */
	public function createLicenseKey( $key, $id, $source, $random ) {
		
		global $e20rlm_level;
		
		if ( 'pmpro' != $source ) {
			return $key;
		}
		
		$license_keys = get_option( 'e20r_pmpro_license_server' );
		
		if ( ! empty( $license_keys[ $id ] ) ) {
			$key = "{$license_keys[$id]['license_key']}_{$random}";
		}
		
		return $key;
	}
	
	/**
	 * Return the transaction ID for the license (is purchase plugin dependent)
	 *
	 * @param string                 $txn_id
	 * @param string                 $source - PMPro or WooCommerce
	 * @param \MemberOrder|\WC_Order $order  The order object for PMPro or WooCommerce
	 *
	 * @return null
	 */
	public function getTransactionId( $txn_id, $product_id, $user_id, $source, $order = null ) {
		
		if ( 'pmpro' != $source ) {
			return null;
		}
		
		if ( is_null( $order ) ) {
			global $e20rlm_order;
			$order = $e20rlm_order;
		}
		
		if ( $source == 'pmpro' ) {
			
			$txn_id = ! empty( $order->payment_transaction_id ) ? $order->payment_transaction_id : null;
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
		
		if ( 'pmpro' !== $source ) {
			return $info;
		}
		
		$user_meta = get_user_meta( $user->ID );
		
		$info = array(
			'first_name' => isset( $user_meta['pmpro_bfirstname'][0] ) ? $user_meta['pmpro_bfirstname'][0] : $user->first_name,
			'last_name'  => isset( $user_meta['pmpro_blastname'][0] ) ? $user_meta['pmpro_blastname'][0] : $user->last_name,
			'email'      => isset( $user_meta['pmpro_bemail'][0] ) ? $user_meta['pmpro_bemail'][0] : $user->user_email,
			'company'    => isset( $user_meta['pmpro_company'][0] ) ? $user_meta['pmpro_company'][0] : null,
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
	public function expires( $expiration_date, $level_id, $source ) {
		
		$utils = Utilities::get_instance();
		
		if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) || ! is_user_logged_in() || 'pmpro' != $source ) {
			return $expiration_date;
		}
		
		global $current_user;
		$level = pmpro_getMembershipLevelForUser( $current_user->ID );
		
		if ( $level->id != $level_id ) {
			$utils->log( "Unable to calculate the expiration date for this license!" );
			
			return $expiration_date;
		}
		
		if ( isset( $level->cycle_number ) && 1 <= $level->cycle_number ) {
			$expiration_date = date_i18n( 'Y-m-d', strtotime( "+{$level->cycle_number} {$level->cycle_period}", current_time( 'timestamp' ) ) );
		}
		
		if ( ! empty( $level->enddate ) ) {
			$expiration_date = date_i18n( 'Y-m-d', $level->enddate );
		}
		
		return $expiration_date;
	}
	
	/**
	 * Return the level name as the license name
	 *
	 * @param string $name
	 * @param int    $id
	 * @param string $source
	 *
	 * @return string
	 */
	public function licenseName( $name, $id, $source ) {
		
		if ( 'pmpro' != $source ) {
			return $name;
		}
		
		$license_settings = get_option( 'e20r_pmpro_license_server' );
		
		$level = pmpro_getLevel( $id );
		
		return ( ! empty( $license_settings[ $id ]['license_name'] ) ? $license_settings[ $id ]['license_name'] : $level->name );
	}
	
	/**
	 * Fetch info on the number of allowed domains per license from settings (PMPro)
	 *
	 * @param int    $count
	 * @param int    $level_id
	 * @param string $source
	 *
	 * @return bool|int
	 */
	public function domains( $count, $level_id, $source ) {
		
		if ( 'pmpro' != $source ) {
			return $count;
		}
		
		$license_settings = get_option( 'e20r_pmpro_license_server', array() );
		
		if ( empty( $license_settings[ $level_id ]['site_count'] ) ) {
			return false;
		}
		
		// For WooCommerce, the $count variable contains the quantity of the license ordered
		return ( $count * absint( $license_settings[ $level_id ]['site_count'] ) );
	}
	
}