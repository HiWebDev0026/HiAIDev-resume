<?php
/**
 * Conditionally renders each block based on the sale period setting.
 */

namespace SWSales\blocks\sale_period_setting;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Check if the given block has sale period settings.
 *
 * @since 1.3.0
 *
 * @param array $block The block info and attributes.
 * @return boolean     Are there sale period settings or not.
 */
function has_visibility_settings( $block ) {
	if ( isset( $block['attrs']['sale_period_visibility'] ) ) {
		return true;
	}

	return false;
}

/**
 * Check if the given block should be visible based on sale period.
 *
 * @since 1.3.0
 *
 * @param array $settings   The plugin settings.
 * @param array $attributes The block attributes.
 * @return boolean          Should the block be visible or not.
 */
function is_visible( $attributes ) {
	// Assume block is visible.
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

	// Still no sale? Return nothing and don't render the inner blocks.
	if ( ! $sale_found ) {
		return;
	}

	// Get the time period for the sale based on sale settings and current date.
	$sale_period = $sitewide_sale->get_time_period();

	// Allow admins to preview the sale period using a URL attribute.
	if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_time_period'] ) ) {
		$sale_period = $_REQUEST['swsales_preview_time_period'];
	}
	// If the block attributes period does not match the sale period, set to false.
	if ( $sale_period != $attributes['sale_period_visibility'] ) {
		$is_visible = false;
	}
	return $is_visible;
}

/**
 * Check if the given block has sale period settings.
 *
 * @since 1.0.0
 *
 * @param string $block_content The block frontend output.
 * @param array  $block         The block info and attributes.
 * @return mixed                Return either the $block_content or nothing depending on sale period settings.
 */
function render_with_visibility( $block_content, $block ) {

	// Get the block attribute setting.
	$attributes = isset( $block['attrs'] )
		? $block['attrs']
		: null;

	// If there are no attributes to check, just return the block.
	if ( ! isset( $attributes['sale_period_visibility'] ) ) {
		return $block_content;
	}

	// If the block is visible, add custom classes as needed.
	if ( is_visible( $attributes ) ) {
		return $block_content;
	} else {
		return '';
	}
}
add_filter( 'render_block', __NAMESPACE__ . '\render_with_visibility', 10, 3 );
