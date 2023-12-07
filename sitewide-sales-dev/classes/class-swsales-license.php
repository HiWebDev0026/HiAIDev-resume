<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_License{

    public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_license_page' ) );
    }

    public static function add_license_page() {
		// Check License Key for Correct Link Color
		$key = get_option( 'swsales_license_key', '' );
		if ( swsales_license_is_valid( $key, NULL ) ) {
			$span_color = '#33FF00';
		} else {
			$span_color = '#FCD34D';
		}
        add_submenu_page(
			'edit.php?post_type=sitewide_sale',
			__( 'License', 'sitewide-sales' ),
			__( '<span style="color: ' . $span_color . '">License</span>', 'sitewide-sales' ),
			'manage_options',
			'sitewide_sales_license',
			array( __CLASS__, 'show_license_page' )
		);
    }

    public static function show_license_page() {

        //only let admins get here
        if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'swsales_license') ) ) {
            die( __( 'You do not have permissions to perform this action.', 'sitewide-sales' ) );
        } ?>
        <div class="wrap sitewide_sales_admin">
            <div class="swsales-wrap">
			<?php
				if ( ! empty( $_REQUEST['swsales-verify-submit'] ) && wp_verify_nonce( $_POST['swsales-key-nonce'], 'swsales-key-nonce' ) ) {
                
					$key = preg_replace("/[^a-zA-Z0-9]/", "", $_REQUEST['swsales-license-key']);

					delete_option( 'swsales_license_key' );

					$valid = swsales_license_is_valid( $key, NULL, true );
    
					update_option( 'swsales_license_key', $key, 'no' );
                }

				$key = get_option( 'swsales_license_key', '' );
				$swsales_license_check = get_option( 'swsales_license_check', array( 'license' => false, 'enddate' => 0 ) );
            ?>
            <h1><?php esc_html_e( 'Sitewide Sales Support License', 'sitewide-sales' ); ?></h1>
            <?php if(!swsales_license_is_valid() && empty($key)) { ?>
                <p class="sitewide_sales_message sitewide_sales_alert"><strong><?php _e('Enter your support license key.</strong> Your license key can be found in your purchase confirmation email or in your <a href="https://www.strangerstudios.com/login/?redirect_to=%2Faccount%2F%3Futm_source%3Dplugin%26utm_medium%3Dswsales-license%26utm_campaign%3Daccount%26utm_content%3Dno-key" target="_blank">account area</a>.', 'sitewide-sales' );?></p>
            <?php } elseif(!swsales_license_is_valid()) { ?>
                <p class="sitewide_sales_message sitewide_sales_error"><strong><?php _e('Your license is invalid or expired.', 'sitewide-sales' );?></strong> <?php _e('Visit the <a href="https://www.strangerstudios.com/login/?redirect_to=%2Faccount%2F%3Futm_source%3Dplugin%26utm_medium%3Dswsales-license%26utm_campaign%3Daccount%26utm_content%3Dkey-not-valid" target="_blank">account area</a> to confirm that your account is active and to find your license key.', 'sitewide-sales' );?></p>
            <?php } else { ?>													
                <p class="sitewide_sales_message sitewide_sales_success"><?php _e('<strong>Thank you!</strong> A valid license key has been used to activate your support license on this site.', 'sitewide-sales');?></p>
            <?php } ?>

            <form action="" method="post">
			<table class="form-table">
				<tbody>
					<tr id="swsales-settings-key-box">
						<td>
							<input type="password" name="swsales-license-key" id="swsales-license-key" value="<?php echo esc_attr($key);?>" placeholder="<?php _e('Enter license key here...', 'sitewide-sales' );?>" size="40"  />
							<?php wp_nonce_field( 'swsales-key-nonce', 'swsales-key-nonce' ); ?>
							<?php submit_button( __( 'Validate Key', 'sitewide-sales' ), 'primary', 'swsales-verify-submit', false ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			</form>

            <p>
				<?php if ( ! swsales_license_is_valid() ) { ?>
					<a class="button button-primary button-hero" href="https://www.strangerstudios.com/account/checkout/?level=2&utm_source=plugin&utm_medium=swsales-license&utm_campaign=swsales-checkout&utm_content=buy-support-license" target="_blank"><?php echo esc_html( 'Buy Support License', 'sitewide-sales' ); ?></a>
					<a class="button button-hero" href="https://sitewidesales.com/pricing/?utm_source=plugin&utm_medium=swsales-license&utm_campaign=swsales-checkout&utm_content=view-license-details" target="_blank"><?php echo esc_html( 'View Support License Details', 'sitewide-sales' ); ?></a>
				<?php } else { ?>
					<a class="button button-primary button-hero" href="https://www.strangerstudios.com/login/?redirect_to=%2Faccount%2F%3Futm_source%3Dplugin%26utm_medium%swsales-license%26utm_campaign%3Daccount%26utm_content%3Dview-account" target="_blank"><?php echo esc_html( 'Manage My Account', 'sitewide-sales' ); ?></a>
					<a class="button button-hero" href="https://www.strangerstudios.com/login/?redirect_to=%2Fwordpress-plugins%2Fsitewide-sales%2Fsupport%2F%3Futm_source%3Dplugin%26utm_medium%3Dswsales-license%26utm_campaign%3Dsupport%26utm_content%3Dsupport" target="_blank"><?php echo esc_html( 'Open Support Ticket', 'sitewide-sales' ); ?></a>
				<?php } ?>
			</p>

			<hr />

            <div class="clearfix"></div>

            <img class="swsales_icon alignright" src="<?php echo esc_url( plugins_url( 'images/Sitewide-Sales_icon.png', SWSALES_BASENAME ) ); ?>" border="0" alt="Sitewide Sales Logo" />
            <?php
                $allowed_swsales_license_strings_html = array (
                    'a' => array (
                        'href' => array(),
                        'target' => array(),
                        'title' => array(),
                    ),
                    'strong' => array(),
                    'em' => array(),		);
            ?>

            <?php
                echo '<p>' . sprintf( wp_kses( __( 'Sitewide Sales is distributed under the GPLv2 license. This means, among other things, that you may use the software on this site or any other site free of charge.', 'sitewide-sales' ), $allowed_swsales_license_strings_html ), '#' ) . '</p>';
            ?>

            <?php
                echo '<p>' . wp_kses( __( '<strong>Stranger Studios, the author of this plugin, offers a plan for automatic updates and premium support.</strong> Your Sitewide Sales purchase includes a support license key which we recommend for all public websites running Sitewide Sales. A support license key allows you to automatically update when a new security, bug fix, or feature enhancement is released.' ), $allowed_swsales_license_strings_html ) . '</p>';
            ?>

            <?php
                echo '<p>' . wp_kses( __( '<strong>Need help?</strong> Your license allows you to open new tickets in our private support area. Purchases are backed by a 30 day, no questions asked refund policy.' ), $allowed_swsales_license_strings_html ) . '</p>';
            ?>

            <p><a href="https://sitewidesales.com/pricing/?utm_source=plugin&utm_medium=swsales-license&utm_campaign=swsales-checkout&utm_content=view-license-option" target="_blank"><?php echo esc_html( 'View Support License Options &raquo;', 'sitewide-sales' ); ?></a></p>
			</div> <!-- end swsales-wrap -->
		</div> <!-- end sitewide_sales_admin -->
        <?php
            
    }

} // End of class

