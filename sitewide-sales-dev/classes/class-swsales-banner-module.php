<?php

abstract class SWSales_Banner_Module {
	/**
	 * Returns a human-readable name for this module.
	 *
	 * @return string
	 */
	abstract protected static function get_module_label();

	/**
	 * Returns whether the plugin associaited with this module is active.
	 *
	 * @return bool
	 */
	abstract protected static function is_module_active();

	/**
	 * Echos the HTML for the settings that should be displayed
	 * if this module is active and selected while editing a
	 * sitewide sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sale being edited.
	 */
	abstract protected static function echo_banner_settings_html_inner( $sitewide_sale );

	/**
	 * Saves settings shown by echo_banner_settings_html_inner().
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post The post being saved.
	 */
	abstract protected static function save_banner_settings( $post_id, $post );

	/**
	 * Set up registering the module.
	 */
	public static function init() {
		add_action( 'swsales_banner_modules', array( get_called_class(), 'register_module' ) );
	}

	/**
	 * Registers the banner module so that it shows on the settings page.
	 *
	 * @param array $modules The modules registered so far.
	 * @return array The modules registered so far, with this module added.
	 */
	final public static function register_module( $modules ) {
		$modules[ static::get_module_label() ] = get_called_class();
		return $modules;
	}

	/**
	 * Echos the HTML for the settings that should be displayed when editing a sitewide sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sale being edited.
	 */
	final public static function echo_banner_settings_html( $sitewide_sale ) {
		if ( ! static::is_module_active() ) {
			?>
			<tr>
				<th></th>
				<td>
					<div class="sitewide_sales_message sitewide_sales_error">
						<p><?php printf( esc_html( 'The %s plugin is not active.', 'sitewide-sales' ),  static::get_module_label() ); ?></p>
					</div>
				</td>
			</tr>
			<?
		} else {
			?>
			<div class="swsales-banner-module-settings">
				<?php static::echo_banner_settings_html_inner( $sitewide_sale ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Saves settings shown by echo_banner_settings_html().
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post The post being saved.
	 */
	final public static function save_banner_settings_if_module_active( $post_id, $post ) {
		if ( static::is_module_active() ) {
			static::save_banner_settings( $post_id, $post );
		}
	}

	/**
	 * Returns whether this module is being used by the current active sitewide sale.
	 *
	 * @return SWSales_Sitewide_Sale|null The sitewide sale that is using this module, or null if the active sale does not use this module.
	 */
	final protected static function is_used_by_active_sitewide_sale() {
		$active_sitewide_sale = Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( empty( $active_sitewide_sale ) ) {
			return;
		}

		if ( $active_sitewide_sale->swsales_banner_module === get_called_class() ) {
			return $active_sitewide_sale;
		}
	}

	/**
	 * Whether the banner for the given sitewide sale should be shown.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sitewide sale to check.
	 *
	 * @return bool
	 */
	final protected static function banner_should_be_shown( $sitewide_sale ) {
		// Check if the sale is active and running.
		if ( ! $sitewide_sale->is_running() ) {
			return false;
		}
	
		// Check if there is a landing page set for this sale and that we are not on the landing page.
		$landing_page_post_id = $sitewide_sale->get_landing_page_post_id();
		if ( ! empty( $landing_page_post_id ) && is_page( $landing_page_post_id ) ) {
			return false;
		}

		// Check if we are on a checkout page.
		if ( $sitewide_sale->get_hide_on_checkout() && apply_filters( 'swsales_is_checkout_page', false, $sitewide_sale ) ) {
			return false;
		}

		// Check if we should hide the banner for this user's role.
		$hide_for_roles = json_decode( $sitewide_sale->get_meta_value( 'swsales_hide_banner_by_role', '[]' ) );
		if ( ! is_user_logged_in() ) {
			$user_roles = array( 'logged_out' );
		} else {
			$user = wp_get_current_user();
			$user_roles = ( array ) $user->roles;
		}

		if ( ! empty( array_intersect( $hide_for_roles, $user_roles ) ) ) {
			return false;
		}

		// Give E-Commerce modules a chance to hide the banner.
		return apply_filters( 'swsales_show_banner', true, $sitewide_sale );
	}
}