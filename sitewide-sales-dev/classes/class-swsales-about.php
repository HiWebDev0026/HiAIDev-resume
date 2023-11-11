<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_About {

	/**
	 * Adds actions for class
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_about_page' ) );
	}

	public static function add_about_page() {
		add_submenu_page(
			'edit.php?post_type=sitewide_sale',
			__( 'About', 'sitewide-sales' ),
			__( 'About', 'sitewide-sales' ),
			'manage_options',
			'sitewide_sales_about',
			array( __CLASS__, 'show_about_page' )
		);
	}

	public static function show_about_page() { ?>
		<div class="wrap sitewide_sales_admin">
			<div class="swsales-wrap">
				<h1><?php esc_html_e( 'About Sitewide Sales', 'sitewide-sales' ); ?></h1>
				<?php
					$allowed_html = array (
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
						),
						'strong' => array(),
						'em' => array(),		);
				?>
				<p><?php esc_html_e( 'Sitewide Sales helps you run Black Friday, Cyber Monday, or other flash sales on your WordPress-powered eCommerce or membership site.', 'sitewide-sales' ); ?></p>
				<p><?php echo sprintf( wp_kses( __( 'We currently offer integration for <a href="%s" title="Paid Memberships Pro" target="_blank">Paid Memberships Pro</a>, <a href="%s" title="WooCommerce" target="_blank">WooCommerce</a>, and <a href="%s" title="Easy Digital Downloads" target="_blank">Easy Digital Downloads</a>.', 'sitewide-sales' ), $allowed_html ), 'https://sitewidesales.com/modules/paid-memberships-pro/?utm_source=sitewide-sales&utm_medium=about&utm_campaign=module-pmpro', 'https://sitewidesales.com/modules/woocommerce/?utm_source=sitewide-sales&utm_medium=about&utm_campaign=module-wc', 'https://sitewidesales.com/modules/easy-digital-downloads/?utm_source=sitewide-sales&utm_medium=about&utm_campaign=module-edd' ); ?></p>
				<p><?php echo sprintf( wp_kses( __( 'There is also a <a href="%s" title="Custom Module" target="_blank">custom module</a> you can use to track performance with any other platforms you choose.', 'sitewide-sales' ), $allowed_html ), 'https://sitewidesales.com/modules/custom-module/?utm_source=sitewide-sales&utm_medium=about&utm_campaign=module-custom' ); ?></p>
				<h2><?php esc_html_e( 'Getting Started', 'sitewide-sales' ); ?></h2>
				<p><?php echo wp_kses( __( 'This plugin handles your banners, notification bars, landing pages, and reporting. Running a sale like this used to require three or more separate plugins. Now you can run your sale with a single tool. At the same time, the Sitewide Sales plugin is flexible enough that you can use specific banner and landing page plugins if wanted.', 'sitewide-sales' ), $allowed_html ); ?></p>
				<p><?php esc_html_e( 'Check out the Sitewide Sales documentation site for additional setup instructions, sample landing page and banner content, as well as developer documentation to further extend the templates, reporting, and integration options.', 'sitewide-sales' ); ?></p>

				<p><a href="https://sitewidesales.com/documentation/?utm_source=plugin&utm_medium=swsales-about&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Documentation', 'sitewide-sales' ); ?>"><?php esc_html_e( 'Documentation', 'sitewide-sales' ); ?></a> | <a href="https://sitewidesales.com/support/?utm_source=plugin&utm_medium=swsales-about&utm_campaign=support" target="_blank" title="<?php esc_attr_e( 'View Support Options &raquo;', 'sitewide-sales' ); ?>"><?php esc_html_e( 'View Support Options &raquo;', 'sitewide-sales' ); ?></a></p>
				
				<?php do_action( 'swsales_about_text_bottom' ); ?>
			</div> <!-- end swsales-wrap -->
		</div> <!-- sitewide-sales_admin -->
		<?php
	}
}
