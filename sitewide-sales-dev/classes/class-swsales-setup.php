<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Setup {
	/**
	 *
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/includes
	 */
	public static function init() {
		register_activation_hook( SWSALES_BASENAME, array( __CLASS__, 'swsales_admin_notice_activation_hook' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'swsales_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'swsales_frontend_scripts' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'swsales_plugin_row_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . SWSALES_BASENAME, array( __CLASS__, 'swsales_plugin_action_links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'swsales_admin_notice' ) );
	}

	/**
	 * Enqueues selectWoo
	 */
	public static function swsales_admin_scripts() {
		$screen = get_current_screen();

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'selectWoo', plugins_url( 'js/selectWoo.full' . $suffix . '.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
		wp_enqueue_script( 'selectWoo' );
		wp_register_style( 'selectWooCSS', plugins_url( 'css/selectWoo' . $suffix . '.css', SWSALES_BASENAME ) );
		wp_enqueue_style( 'selectWooCSS' );

		wp_register_style( 'swsales_admin', plugins_url( 'css/admin.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_admin' );
	}

	/**
	 * Enqueues frontend stylesheet.
	 */
	public static function swsales_frontend_scripts() {
		wp_register_style( 'swsales_frontend', plugins_url( 'css/frontend.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_frontend' );
	}

	/**
	 * Runs only when the plugin is activated.
	 *
	 * @since 0.0.0
	 */
	public static function swsales_admin_notice_activation_hook() {
		// Create transient data.
		set_transient( 'swsales-admin-notice', true, 5 );
	}

	/**
	 * Returns if the user is on the login page (currently works for TML)
	 * Can probably switch to is_login_page from PMPro core
	 */
	public static function is_login_page() {
		global $post;
		$slug = get_site_option( 'tml_login_slug' );
		if ( false === $slug ) {
			$slug = 'login';
		}
		return ( ( ! empty( $post->post_name ) && $slug === $post->post_name ) || is_page( 'login' ) || in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) || ( function_exists( 'pmpro_is_login_page' ) && pmpro_is_login_page() ) || ( function_exists( 'is_account_page' ) && ! is_user_logged_in() && is_account_page() ) );
	}

	/**
	 * Returns true of there are any posts of type sitewide_sale, false otherwise.
	 */
	public static function has_sitewide_sales() {
		global $wpdb;
		$sale_id = $wpdb->get_var(
			"SELECT *
									FROM $wpdb->posts
									WHERE post_type = 'sitewide_sale'
										AND post_status <> 'auto-draft'
									LIMIT 1"
		);
		if ( ! empty( $sale_id ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Admin Notice on Activation.
	 *
	 * @since 0.1.0
	 */
	public static function swsales_admin_notice() {
		// Check transient, if available display notice.
		if ( get_transient( 'swsales-admin-notice' ) ) { ?>
			<div class="updated notice is-dismissible">
				<p>
				<?php
					global $wpdb;
					$has_swsales_post = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'sitewide_sale' LIMIT 1" );
				if ( $has_swsales_post ) {
					printf( __( 'Thank you for activating. You can <a href="%s">view your Sitewide Sales here</a>.', 'sitewide-sales' ), get_admin_url( null, 'edit.php?post_type=sitewide_sale' ) );
				} else {
					printf( __( 'Thank you for activating. You can <a href="%s">create your first Sitewide Sale here</a>.', 'sitewide-sales' ), get_admin_url( null, 'post-new.php?post_type=sitewide_sale' ) );
				}
				?>
				</p>
			</div>
			<?php
			// Delete transient, only display this notice once.
			delete_transient( 'swsales-admin-notice' );
		}
	}

	/**
	 * Function to add links to the plugin action links
	 *
	 * @param array $links Array of links to be shown in plugin action links.
	 */
	public static function swsales_plugin_action_links( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			$new_links = array(
				'<a href="' . get_admin_url( null, 'edit.php?post_type=sitewide_sale' ) . '">' . __( 'View Sitewide Sales', 'sitewide-sales' ) . '</a>',
			);

			$links = array_merge( $new_links, $links );
		}
		return $links;
	}

	/**
	 * Function to add links to the plugin row meta
	 *
	 * @param array  $links Array of links to be shown in plugin meta.
	 * @param string $file Filename of the plugin meta is being shown for.
	 */
	public static function swsales_plugin_row_meta( $links, $file ) {
		if ( strpos( $file, 'sitewide-sales.php' ) !== false ) {
			$new_links = array(
				'<a href="' . esc_url( 'https://sitewidesales.com/documentation/' ) . '" title="' . esc_attr( __( 'View Documentation', 'sitewide-sales' ) ) . '">' . __( 'Docs', 'sitewide-sales' ) . '</a>',
				'<a href="' . esc_url( 'https://sitewidesales.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'sitewide-sales' ) ) . '">' . __( 'Support', 'sitewide-sales' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}

}
