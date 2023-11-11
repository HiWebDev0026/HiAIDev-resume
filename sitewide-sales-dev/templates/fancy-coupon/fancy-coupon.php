<?php
namespace Sitewide_Sales\templates\fancy_coupon;

/**
 * Fancy Coupon Template for Sitewide Sales
 *
 */

/**
 * Add template to list.
 */
function swsales_templates( $templates ) {
	$templates['fancy_coupon'] = 'Fancy Coupon';

	return $templates;
}
add_filter( 'swsales_templates', __NAMESPACE__ . '\swsales_templates' );

/**
 * Load our landing page and banner CSS/JS if needed.
 */
function wp_enqueue_scripts() {
	// Load landing page CSS if needed.
	if ( swsales_landing_page_template() == 'fancy_coupon' ) {
		wp_register_style( 'swsales_fancy_coupon_landing_page', plugins_url( 'templates/fancy-coupon/landing-page.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_fancy_coupon_landing_page' ); 
	}

	// Load banner CSS if needed.
	if ( swsales_banner_template() == 'fancy_coupon' ) {
		wp_register_style( 'swsales_fancy_coupon_banner', plugins_url( 'templates/fancy-coupon/banner.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_fancy_coupon_banner' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

/**
 * Filter to add the fancy coupon template wrapper for this banner template.
 *
 */
function swsales_banner_content_fancy_coupon( $content ) {
	$content_before = '<div id="swsales-banner-wrap-fancy_coupon" class="swsales-banner-wrap">';
	$content_after = '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_banner_content_fancy_coupon', __NAMESPACE__ . '\swsales_banner_content_fancy_coupon' );

/**
 * Filter to add the coupon into the banner content.
 *
 */
function swsales_banner_text_fancy_coupon( $content, $location, $active_sitewide_sale ) {

	// Get the active or preview sale's banner template.
	$swsales_banner_template = $active_sitewide_sale->swsales_banner_template;

	// Filter the content if the template is Fancy Coupon.
	if ( $swsales_banner_template === 'fancy_coupon' ) {
		$content_after = '<div class="swsales-banner-fancy_coupon-coupon">';
		$coupon = $active_sitewide_sale->get_coupon();
		if ( ! empty( $coupon ) ) {
			$content_after .= '<span class="swsales-coupon">' . $active_sitewide_sale->get_coupon() . '</span>';
		}
		$content_after .= '</div>';		
		$content = $content . $content_after;
	}
	return $content;
}
add_filter( 'swsales_banner_text', __NAMESPACE__ . '\swsales_banner_text_fancy_coupon', 10, 3 ); 

/**
 * Filter to add the fancy coupon template wrapper for this landing page template.
 *
 */
function swsales_landing_page_content_fancy_coupon( $content, $sitewide_sale ) {
	$content_before = '<div id="swsales-landing-page-wrap-fancy_coupon" class="swsales-landing-page-wrap">';
	// Add the coupon to template if it is set for this sale.
	$coupon = $sitewide_sale->get_coupon();
	if ( ! empty( $coupon ) ) {
		$content_before .= '<div class="swsales-landing-page-fancy_coupon-coupon">';
		$content_before .= '<h3><small>' . esc_html( 'USE CODE', 'sitewide-sales' ) . '</small><br />';
		$content_before .= $coupon;
		$content_before .= '</h3></div>';
	}
	$content_after = '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_landing_page_content_fancy_coupon', __NAMESPACE__ . '\swsales_landing_page_content_fancy_coupon', 10, 2 );
