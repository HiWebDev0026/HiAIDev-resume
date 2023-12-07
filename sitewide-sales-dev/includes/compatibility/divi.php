<?php
/**
 * Divi Compatibility
 *
 * @since 1.3.0
 */

class SWSalesDivi {

	function __construct() {

		if ( empty( $_GET['page'] ) || 'et_divi_role_editor' !== $_GET['page'] ) {
			add_filter( 'et_builder_get_parent_modules', array( __CLASS__, 'toggle' ) );
			add_filter( 'et_pb_module_content', array( __CLASS__, 'restrict_content' ), 10, 4 );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( __CLASS__, 'row_settings' ) );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', array( __CLASS__, 'section_settings' ) );			
		}

	}

	public static function toggle( $modules ) {

		if ( isset( $modules['et_pb_row'] ) && is_object( $modules['et_pb_row'] ) ) {
			$modules['et_pb_row']->settings_modal_toggles['custom_css']['toggles']['sitewide-sale-period'] = __( 'Sitewide Sales', 'sitewide-sales' );
		}

		if ( isset( $modules['et_pb_section'] ) && is_object( $modules['et_pb_section'] ) ) {
			$modules['et_pb_section']->settings_modal_toggles['custom_css']['toggles']['sitewide-sale-period'] = __( 'Sitewide Sales', 'sitewide-sales' );
		}

		return $modules;

	}

	public static function row_settings( $settings ) {

		$settings['sitewide-sale-period'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Restrict Row by Sale Period', 'sitewide-sales' ),
			'description' => __( 'Enter a sale period from these options: "pre-sale", "sale", and "post-sale".', 'sitewide-sales' ),
			'type' => 'text',
			'default' => '',
			'option_category' => 'configuration',
			'toggle_slug' => 'sitewide-sale-period',
		);

		return $settings;

	}

	public static function section_settings( $settings ) {

		$settings['sitewide-sale-period'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Restrict Section by Sale Period', 'sitewide-sales' ),
			'description' => __( 'Enter a sale period from these options: "pre-sale", "sale", and "post-sale".', 'sitewide-sales' ),
			'type' => 'text',
			'default' => '',
			'option_category' => 'configuration',
			'toggle_slug' => 'sitewide-sale-period',
		);

		return $settings;

	}

	public static function restrict_content( $output, $props, $attrs, $slug ) {

		if ( et_fb_is_enabled() ) {
			return $output;
		}

		if ( ! isset( $props['sitewide-sale-period'] ) ){
			return $output;
		}
		
		$sale_period_setting = $props['sitewide-sale-period'];
		
		if ( empty( trim( $sale_period_setting ) ) || trim( $sale_period_setting ) === '0' ) {
			return $output;
		}

		// Assume content is visible.
		$is_visible = true;

		// Is this post a sale landing page? If so, load the sale.
		$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'swsales_sitewide_sale_id', true );
		if ( ! empty( $sitewide_sale_id ) ) {
			$sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
			$sale_found = $sitewide_sale->get_sitewide_sale( $sitewide_sale_id );
			$sitewide_sale = $sale_found;
		}

		// Or, try to load the active sale.
		if ( empty( $sale_found ) ) {
			$sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
			$sale_found = $sitewide_sale->get_active_sitewide_sale();
			$sitewide_sale = $sale_found;
		}

		// Still no sale? Return nothing and don't render the content.
		if ( ! $sale_found ) {
			return;
		}

		// Get the time period for the sale based on sale settings and current date.
		$sale_period = $sitewide_sale->get_time_period();

		// Allow admins to preview the sale period using a URL attribute.
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_time_period'] ) ) {
			$sale_period = $_REQUEST['swsales_preview_time_period'];
		}

		// If the builder's setting for period does not match the sale period, set to false.
		if ( $sale_period != $sale_period_setting ) {
			$is_visible = false;
		}

		if ( ! empty( $is_visible ) ) {
			return $output;
		} else {
			return '';
		}
	}

}
new SWSalesDivi();
