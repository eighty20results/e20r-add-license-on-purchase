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

namespace E20R\Licensing\Purchase\PMPro;

use E20R\Utilities\Utilities;

class Settings {
	
	/**
	 * @var null|Settings
	 */
	private static $instance = null;
	
	/**
	 * The current instance of the PMProSettings class
	 *
	 * @return Settings|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function addSLMSettings() {
		
		add_submenu_page(
			'pmpro-membershiplevels',
			__( 'Software Licenses', 'e20r-add-license-on-purchase' ),
			__( 'Software Licenses', 'e20r-add-license-on-purchase' ),
			'manage_options',
			'e20rlm',
			array( $this, 'loadSLMPage' )
		);
	}
	
	public function loadMenuBarEntry() {
		global $wp_admin_bar;
		
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$wp_admin_bar->add_menu( array(
				'id'     => 'e20rlm',
				'parent' => 'paid-memberships-pro',
				'title'  => __( 'Software Licenses', 'e20r-add-license-on-purchase' ),
				'href'   => add_query_arg( 'page', 'e20rlm', admin_url( 'admin.php' ) ),
			)
		);
	}
	
	public function loadScripts() {
	    
	    wp_enqueue_style( 'e20rlm-pmpro', E20R_ALOP_URL . "/css/e20r-add-license-pmpro-admin.css", null, E20R_LICENSE_SERVER_VERSION );
	    wp_enqueue_script( 'e20rlm-pmpro', E20R_ALOP_URL . "/js/e20r-add-license-on-purchase-pmpro.js", array( 'jquery' ), E20R_LICENSE_SERVER_VERSION );
    }
    
	public function save() {
	   
	    $utils = Utilities::get_instance();
	    wp_verify_nonce( 'e20r_alp_save', 'save_settings' );
	    
	    $create_key = $utils->get_variable( 'e20rlm_api_create_secret', null );
	    $verify_key = $utils->get_variable( 'e20rlm_api_verify_secret', null );
	    $api_url = $utils->get_variable( 'e20rlm_api_url', null );
     
	    $retval = false;
	    
	    if ( false !== get_option( 'e20rlm_api_create_secret' ) ) {
		    update_option( 'e20rlm_api_create_secret', $create_key );
	    } else {
	        if ( false === add_option( 'e20rlm_api_create_secret', $create_key, null, 'no' )) {
	            wp_send_json_error( __( "Unable to save the secret Create key!", 'e20r-add-license-on-purchase'));
	            wp_die();
	        }
        }
		
		if ( false !== get_option( 'e20rlm_api_verify_secret' ) ) {
			update_option( 'e20rlm_api_verify_secret', $verify_key );
		} else {
			if ( false === add_option( 'e20rlm_api_verify_secret', $verify_key, null, 'no' )) {
				wp_send_json_error( __( "Unable to save the secret Verify key!", 'e20r-add-license-on-purchase' ));
				wp_die();
			}
		}
		
		if ( false !== get_option( 'e20rlm_api_url' ) ) {
			update_option( 'e20rlm_api_url', $api_url );
		} else {
			if ( false === add_option( 'e20rlm_api_url', $api_url, null, 'no' )) {
				wp_send_json_error( __( "Unable to save the URL to the Software License Server!", 'e20r-add-license-on-purchase' ));
				wp_die();
			}
		}
        
        wp_send_json_success();
	    wp_die();
    }
    
	public function loadSLMPage() {
		
		if ( file_exists( PMPRO_DIR . "/adminpages/admin_header.php" ) ) {
			require_once( PMPRO_DIR . '/adminpages/admin_header.php' );
		}
		
		$settings = apply_filters( 'e20r-license-server-slm-settings', array(), 'pmpro' );
		
		?>
        <form action="e20r_alp_save" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'save_settings', 'e20r_alp_save' ); ?>
            <h2><?php _e( "License Manager Settings", "e20r-add-license-on-purchase" ); ?></h2>
            <div class="e20r-hide" id="e20r-save-status">
                <span class="e20r-closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            </div>
            <table class="form-table">
                <tbody>
				<?php foreach ( $settings as $key => $entry ) { ?>
                    <tr>
                        <th scope="row" valign="top">
							<?php $this->printLabel( $entry ) ?>
                        </th>
                        <td>
							<?php $this->printField( $entry ); ?>
                        </td>
                    </tr>
				<?php } ?>
                </tbody>
            </table>
            <p class="submit">
                <input name="e20rlm_save_settings" type="submit" class="button button-primary" value="<?php _e('Save Settings', 'e20r-add-license-on-purchase' );?>" />
                <span class="e20r-hide" id="e20r-save-spinner"><img src="<?php echo includes_url( 'images/wpspin.gif' ); ?>"</span>
            </p>
        </form>
		<?php
		if ( file_exists( PMPRO_DIR . "/adminpages/admin_footer.php" ) ) {
			require_once( PMPRO_DIR . '/adminpages/admin_footer.php' );
		}
	}
	
	public function printLabel( $field_def, $echo = true ) {
		
		$content   = array();
		$content[] = sprintf( '<label for="%s">%s</label>', $field_def['id'], $field_def['name'] );
		
		if ( ! empty( $field_def['desc_tip'] ) ) {
			$content[] = sprintf( '<span class="pmpro-help-tip"></span>');
		}
		
		$content = implode( '', $content );
		
		if ( true === $echo ) {
			
			echo $content;
			
			return null;
		}
		
		return $content;
	}
	
	/**
	 * @param array $field_def
	 * @param bool  $echo
	 *
	 * @return null|string
	 */
	public function printField( $field_def, $echo = true ) {
		
		$content = array();
		$utils   = Utilities::get_instance();
		
		$saved_value = get_option( $field_def['id'], null );
		
		switch ( $field_def['type'] ) {
			
			case 'select':
				$content[] = sprintf(
					'<select %1$s %2$s class="e20r-save-select %3$s" %4$s>',
					isset( $field_def['id'] ) ? "id=\"" . esc_attr( $field_def['id'] ) . "\"" : null,
					isset( $field_def['id'] ) ? "name=\"" . esc_attr( $field_def['id'] ) . "\"" : null,
					isset( $field_def['css_class'] ) ? esc_attr( $field_def['css_class'] ) : null,
					isset( $field_def['css'] ) ? "style=\"" . esc_attr( $field_def['css'] ) . "\"" : null
				);
				
				foreach ( $field_def['options'] as $key => $option_settings ) {
					$content[] = sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$option_settings['value'],
						selected( $saved_value, $option_settings['value'], false ),
						$option_settings['value_label']
					);
				}
				
				$content[] = '</select>';
				
				break;
			case 'textarea':
				// TODO: Add this
				break;
			default:
				
				$content[] = sprintf(
					'<input type="%1$s" name="%2$s" id="%2$s" value="%3$s" class="e20r-save-input %4$s" %5$s/>',
					$field_def['type'],
					$field_def['id'],
					$saved_value,
					isset( $field_def['css_class'] ) ? esc_attr( $field_def['css_class'] ) : null,
                    isset( $field_def['css'] ) ? "style=\"" . esc_attr( $field_def['css'] ) . "\"" : null
				);
		}
		
		if ( ! empty( $field_def['desc'] ) ) {
		
		}
		
		$content = implode( '\n', $content );
		
		if ( true === $echo ) {
			echo $content;
			
			return null;
		}
		
		return $content;
	}
	
	public function fieldDefs( $settings, $source ) {
		
		if ( 'pmpro' != $source ) {
			return $settings;
		}
		
		// API URL Option filed
		$settings[] = array(
			'name'     => __( 'API URL', 'e20r-add-license-on-purchase' ),
			'desc_tip' => __( 'Add the URL to the server where the Software Manager plugin is installed and configured', 'e20r-add-license-on-purchase' ),
			'id'       => 'e20rlm_api_url',
			'css'      => 'min-width: 300px;',
			'type'     => 'text',
			'desc'     => '',
		);
		
		// Secret Key for Creating new license
		$settings[] = array(
			'name'     => __( "Create License key", 'e20r-add-license-on-purchase' ),
			'desc_tip' => __( 'The secret key used to securely connect to the License Manager software, and create a new license', 'e20r-add-license-on-purchase' ),
			'id'       => 'e20rlm_api_create_secret',
			'type'     => 'password',
			'css'      => 'min-width: 300px;',
			'desc'     => '',
		);
		
		$settings[] = array(
			'name'     => __( "Verify License key", 'e20r-add-license-on-purchase' ),
			'desc_tip' => __( 'The secret key used to securely connect to the License Manager software, and verify a license', 'e20r-add-license-on-purchase' ),
			'id'       => 'e20rlm_api_verify_secret',
			'type'     => 'password',
			'css'      => 'min-width: 300px;',
			'desc'     => '',
		);
		
		return $settings;
	}
}