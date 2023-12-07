<?php
namespace Sitewide_Sales\templates\photo;

/**
 * photo Template for Sitewide Sales
 *
 */

/**
 * Add template to list.
 */
function swsales_templates( $templates ) {
	$templates['photo'] = 'Photo';

	return $templates;
}
add_filter( 'swsales_templates', __NAMESPACE__ . '\swsales_templates' );

/**
 * Load our landing page and banner CSS/JS if needed.
 */
function wp_enqueue_scripts() {
	// Load landing page CSS if needed.
	if ( swsales_landing_page_template() == 'photo' ) {
		wp_register_style( 'swsales_photo_landing_page', plugins_url( 'templates/photo/landing-page.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_photo_landing_page' ); 
	}

	// Load banner CSS if needed.
	if ( swsales_banner_template() == 'photo' ) {
		wp_register_style( 'swsales_photo_banner', plugins_url( 'templates/photo/banner.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_photo_banner' );
	} 
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

/**
 * Filter to add the photo template wrapper for this banner template.
 *
 */
function swsales_banner_content_photo( $content ) {
	$content_before = '<div id="swsales-banner-wrap-photo" class="swsales-banner-wrap">';
	$content_after = '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_banner_content_photo', __NAMESPACE__ . '\swsales_banner_content_photo' );

/**
 * Filter to add the photo template wrapper for this landing page template.
 *
 */
function swsales_landing_page_content_photo( $content ) {
	$content_before = '<div id="swsales-landing-page-wrap-photo" class="swsales-landing-page-wrap">';
	$content_after = '';

	$background_image = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'full' );
	if ( ! empty( $background_image[0] ) ) {
		$content_after .= '<div class="swsales-landing-page-background-image" style="background-image: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45) ), url(' . $background_image[0] . ')"></div>';
	}
	$content_after .= '</div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_landing_page_content_photo', __NAMESPACE__ . '\swsales_landing_page_content_photo' );
