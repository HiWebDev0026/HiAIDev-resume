<?php
/**
 * Sets up the Countdown Timer block.
 *
 * @package blocks/countdown-timer
 **/

namespace SWSales\blocks\countdown_timer;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// Only load if Gutenberg is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

add_action( 'init', __NAMESPACE__ . '\register_dynamic_block' );
/**
 * Register the dynamic block.
 *
 * @return void
 */
function register_dynamic_block() {
	// Hook server side rendering into render callback.
	register_block_type( 'swsales/countdown-timer', [
		'api_version' => 2,
		'render_callback' => __NAMESPACE__ . '\render_dynamic_block',
		'supports' => array(
			'text' => true,
            'background' => true,
            'gradients' => true,
		),
	] );
}

/**
 * Server rendering for the sale content block.
 *
 * @param array $attributes.
 * @return string
 **/
function render_dynamic_block( $attributes, $content ) {
	$anchor = preg_match( '/id="([^"]*)"/', $content, $anchor_match );
	if ( ! empty( $anchor ) ) {
		$countdown_timer_id = ' id="' . esc_attr( $anchor_match[1] ) . '"';
	} else {
		$countdown_timer_id = '';
	}

	$class = preg_match( '/class="([^"]*)"/', $content, $class_match );
	if ( ! empty( $class ) ) {
		$countdown_timer_class = ' class="' . esc_attr( $class_match[1] ) . '"';
	} else {
		$countdown_timer_class = '';
	}

	$has_inline_styles = array();

	// Get text color if set and add to inline styles array.
	if ( isset( $attributes['style']['color']['text'] ) && preg_match('/^#[a-f0-9]{6}$/i', $attributes['style']['color']['text'], $color_match ) ) {
		$has_inline_styles[] = 'color: ' . $color_match[0];
	}

	// Get background color if set and add to inline styles array.
	if ( isset( $attributes['style']['color']['background'] ) && preg_match('/^#[a-f0-9]{6}$/i', $attributes['style']['color']['background'], $background_match ) ) {
		$has_inline_styles[] = 'background-color: ' . $background_match[0];
	}

	if ( ! empty( $has_inline_styles ) ) {
		$inline_styles = 'style="' . esc_attr( implode( ';', $has_inline_styles ) ) . '"';
	} else {
		$inline_styles = '';
	}

	return sprintf(
		'<div%1$s%2$s%3$s>' . do_shortcode( '[sitewide_sale_countdown]' ) . '</div>',
		$countdown_timer_id,
		$countdown_timer_class,
		$inline_styles,
	);
}
