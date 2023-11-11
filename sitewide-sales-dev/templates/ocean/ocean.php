<?php
namespace Sitewide_Sales\templates\ocean;

/**
 * Ocean Template for Sitewide Sales
 *
 */

/**
 * Add template to list.
 */
function swsales_templates( $templates ) {
	$templates['ocean'] = 'Ocean';

	return $templates;
}
add_filter( 'swsales_templates', __NAMESPACE__ . '\swsales_templates' );

/**
 * Load our landing page and banner CSS/JS if needed.
 */
function wp_enqueue_scripts() {
	// Load landing page CSS if needed.
	if ( swsales_landing_page_template() == 'ocean' ) {
		wp_register_style( 'swsales_ocean_landing_page', plugins_url( 'templates/ocean/landing-page.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_ocean_landing_page' ); 
	}

	// Load banner CSS if needed.
	if ( swsales_banner_template() == 'ocean' ) {
		wp_register_style( 'swsales_ocean_banner', plugins_url( 'templates/ocean/banner.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_ocean_banner' );
	} 
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

/**
 * Filter to add the ocean template wrapper for this banner template.
 *
 */
function swsales_banner_content_ocean( $content, $location ) {
	$content_before = '<div id="swsales-banner-wrap-ocean" class="swsales-banner-wrap">';
	$content_before .= '<div class="swsales-banner-wrap-ocean-' . $location . '">';
	$content_after = '<div class="swsales-banner-wrap-ocean-animation"><svg class="swsales-banner-wrap-ocean-waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto"><defs><path id="swsales-gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" fill="rgba(74,177,173,0.4)" /></defs><g class="swsales-banner-wrap-ocean-waves-parallax"><use xlink:href="#swsales-gentle-wave" x="48" y="0" fill="rgba(74,177,173,0.8" /><use xlink:href="#swsales-gentle-wave" x="48" y="3" fill="rgba(74,177,173,0.4)" /><use xlink:href="#swsales-gentle-wave" x="48" y="5" fill="rgba(74,177,173,0.1)" /><use xlink:href="#swsales-gentle-wave" x="48" y="7" fill="rgba(74,177,173,1.0)" /></g></svg></div>';
	$content_after .= '</div></div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_banner_content_ocean', __NAMESPACE__ . '\swsales_banner_content_ocean', 10, 2 );

/**
 * Filter to add the ocean template wrapper for this landing page template.
 *
 */
function swsales_landing_page_content_ocean( $content ) {
	$content_before = '<div id="swsales-landing-page-wrap-ocean" class="swsales-landing-page-wrap">';
	$content_after = '<div class="swsales-landing-page-wrap-ocean-animation"><svg class="swsales-landing-page-wrap-ocean-waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto"><defs><path id="swsales-gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" fill="rgba(74,177,173,0.8)" /></defs><g class="swsales-landing-page-wrap-ocean-waves-parallax"><use xlink:href="#swsales-gentle-wave" x="48" y="0" fill="rgba(74,177,173,0.8" /><use xlink:href="#swsales-gentle-wave" x="48" y="3" fill="rgba(74,177,173,0.4)" /><use xlink:href="#swsales-gentle-wave" x="48" y="5" fill="rgba(74,177,173,0.1)" /><use xlink:href="#swsales-gentle-wave" x="48" y="7" fill="rgba(74,177,173,1.0)" /></g></svg></div>';
	$content_after .= '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_landing_page_content_ocean', __NAMESPACE__ . '\swsales_landing_page_content_ocean' );
