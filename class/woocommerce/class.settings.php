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

class Settings {
	
	/**
	 * @var null|Settings
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the WooCommerce Settings class
	 *
	 * @return Settings|null
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
	}
	
	/**
	 * Add License Manager to the WooCommerce settings page
	 *
	 * @param array $sections
	 *
	 * @return mixed
	 */
	public function section( $sections ) {
		
		$sections['e20rlm'] = __( 'Software Licenses', 'e20r-add-license-on-purchase' );
		
		return $sections;
	}
	
	/**
	 * Configure settings in WooCommerce for License Manager
	 *
	 * @param array  $settings
	 * @param string $current_section
	 *
	 * @return array
	 */
	public function settings( $settings, $current_section ) {
		
		if ( $current_section == 'e20rlm' ) {
			
			$settings_slm = array();
			
			// Add Title to the Settings
			$settings_slm[] = array(
				'name' => __( 'Software License Manager Settings', 'e20r-add-license-on-purchase' ),
				'type' => 'title',
				'desc' => __('The following options are used to connect to the license manager software/plugin.', 'e20r-add-license-on-purchase' ),
				'id'   => 'wcslider',
			);
			
			// API URL Option filed
			$settings_slm[] = array(
				'name'     => __( 'API URL', 'e20r-add-license-on-purchase' ),
				'desc_tip' => __( 'Add the URL to the server where the Software Manager plugin is installed and configured', 'e20r-add-license-on-purchase' ),
				'id'       => 'e20rlm_api_url',
				'css'      => 'min-width: 300px;',
				'type'     => 'text',
				'desc'     => '',
			);
			
			// Secret Key for Creating new license
			$settings_slm[] = array(
				'name'     => __( "Create License key", 'e20r-add-license-on-purchase' ),
				'desc_tip' => __( 'The secret key used to securely connect to the License Manager software, and create a new license', 'e20r-add-license-on-purchase'),
				'id'       => 'e20rlm_api_create_secret',
				'type'     => 'password',
				'css'      => 'min-width: 300px;',
				'desc'     => '',
			);
			
			$settings_slm[] = array(
				'name'     => __( "Verify License key", 'e20r-add-license-on-purchase' ),
				'desc_tip' => __( 'The secret key used to securely connect to the License Manager software, and verify a license', 'e20r-add-license-on-purchase'),
				'id'       => 'e20rlm_api_verify_secret',
				'type'     => 'password',
				'css'      => 'min-width: 300px;',
				'desc'     => '',
			);
			
			$settings_slm[] = array(
				'type' => 'sectionend',
				'id'   => 'wcslider',
			);
			
			return $settings_slm;
			
			/**
			 * If not, return the standard settings
			 **/
		} else {
			return $settings;
		}
	}
	
	/**
	 * Add input fields for Product metabox
	 */
	public function fields() {
		
		global $woocommerce;
		global $post;
		
		$utils = Utilities::get_instance();
		
		$license_settings = get_post_meta( $post->ID, '_e20rlm_license', true );
		
		$enabled        = ( isset( $license_settings['enabled'] ) ? $license_settings['enabled'] : null );
		$sites_allowed  = trim( ( isset( $license_settings['site_count'] ) ? $license_settings['site_count'] : null ) );
		$renewal_period = trim( ( isset( $license_settings['renewal_period'] ) ? $license_settings['renewal_period'] : null ) );
		$license_stub   = trim( ( isset( $license_settings['key_stub'] ) ? $license_settings['key_stub'] : null ) );
		
		$utils->log( "Settings for {$post->ID}: " . print_r( $license_settings, true ) );
		
		woocommerce_wp_checkbox(
            array(
                'id' => 'e20rlm_licensing_enabled',
                'name' => 'e20rlm_licensing_enabled',
                'wrapper_class' => 'show_if_downloadable',
                'value' => empty( $enabled ) ? 'no' : 'yes',
                'label' => __( 'Create license', 'e20r-add-license-on-purchase' ),
                'description' => __('Create & activate a license key for the user?', 'e20r-add-license-on-purchase')
            )
        );
  
		woocommerce_wp_text_input(
            array(
                'id' => 'e20rlm_key_stub',
                'name' => 'e20rlm_key_stub',
                'value' => $license_stub,
                'label' => __( "Client license prefix", 'e20r-add-license-on-purchase' ),
                'description' => __( 'Product specific license prefix', 'e20r-add-license-on-purchase' ),
                'wrapper_class' => 'e20r-toggled-hide',
		    )
        );
		
		woocommerce_wp_text_input(
            array(
                'id' =>     'e20rlm_renewal_period',
                'name' => 'e20rlm_renewal_period',
                'value' => $renewal_period,
                'label' => __( "License renewal period",'e20r-add-license-on-purchase' ),
                'description' => __( "No. of years. Use 0 as the 'lifetime' value.", 'e20r-add-license-on-purchase' ),
                'wrapper_class' => 'e20r-toggled-hide',
            )
        );
		
		woocommerce_wp_text_input(
        array(
		        'id' => 'e20rlm_sites_allowed',
                'name' => 'e20rlm_sites_allowed',
                'value' => $sites_allowed,
                'label' => __( 'Sites per license', 'e20r-add-license-on-purchase' ),
                'description' => __( 'How many sites does a key activate?', 'e20r-add-license-on-purchase' ),
		        'wrapper_class' => 'e20r-toggled-hide',
            )
        );
	}
	
	/**
	 * Save product specific licensing settings
	 *
	 * @param $post_id
	 */
	public function save( $post_id ) {
		
		$utils          = Utilities::get_instance();
		$enabled        = $utils->get_variable( 'e20rlm_licensing_enabled', false );
		$sites_allowed  = $utils->get_variable( 'e20rlm_sites_allowed', 0 );
		$renewal_period = $utils->get_variable( 'e20rlm_renewal_period', 1 );
		$key_stub       = $utils->get_variable( 'e20rlm_key_stub', 'e20r_dl' );
		
		// Reset settings
		if ( false == $enabled ) {
			$sites_allowed  = null;
			$renewal_period = null;
			$key_stub       = null;
		}
  
		$license_settings = array(
            'enabled'        => $enabled,
            'site_count'     => $sites_allowed,
            'renewal_period' => $renewal_period,
            'key_stub'       => $key_stub,
        );
        
        $utils->log( "Saving: " . print_r( $license_settings, true ));
        update_post_meta( $post_id, '_e20rlm_license', $license_settings );
		
	}
}