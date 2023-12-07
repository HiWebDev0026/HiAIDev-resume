<?php
namespace Sitewide_Sales\templates\gradient;

/**
 * Gradient Template for Sitewide Sales
 *
 */

/**
 * Add template to list.
 */
function swsales_templates( $templates ) {
	$templates['gradient'] = 'Gradient';

	return $templates;
}
add_filter( 'swsales_templates', __NAMESPACE__ . '\swsales_templates' );

/**
 * Load our landing page and banner CSS/JS if needed.
 */
function wp_enqueue_scripts() {
	// Load landing page CSS if needed.
	if ( swsales_landing_page_template() == 'gradient' ) {
		wp_register_style( 'swsales_gradient_landing_page', plugins_url( 'templates/gradient/landing-page.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_gradient_landing_page' ); 
	}

	// Load banner CSS if needed.
	if ( swsales_banner_template() == 'gradient' ) {
		wp_register_style( 'swsales_gradient_banner', plugins_url( 'templates/gradient/banner.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_gradient_banner' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

/**
 * Filter to add the gradient template wrapper for this banner template.
 *
 */
function swsales_banner_content_gradient( $content ) {
	$content_before = '<div id="swsales-banner-wrap-gradient" class="swsales-banner-wrap">';
	$content_after = '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_banner_content_gradient', __NAMESPACE__ . '\swsales_banner_content_gradient' );

/**
 * Filter to add the gradient template wrapper for this landing page template.
 *
 */
function swsales_landing_page_content_gradient( $content ) {
	$content_before = '<div id="swsales-landing-page-wrap-gradient" class="swsales-landing-page-wrap">';
	$content_after = '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_landing_page_content_gradient', __NAMESPACE__ . '\swsales_landing_page_content_gradient' );
