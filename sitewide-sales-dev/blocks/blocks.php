<?php
/**
 * Enqueues blocks in editor and dynamic blocks
 *
 * @package blocks
 */
defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Dynamic Block Requires
 */
require_once( 'countdown-timer/block.php' );
require_once( 'sale-content/block.php' );
require_once( 'sale-period-setting/block.php' );

/**
 * Add Sitewide Sales block category
 * This callback is used with the block_categories_all filter.
 */
function swsales_place_blocks_in_panel( $categories, $post_or_context ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'swsales',
				'title' => __( 'Sitewide Sales', 'sitewide-sales' ),
			),
		)
	);
}

// Use the correct filter based on WP version.
if ( function_exists( 'get_default_block_categories' ) ) {
	// 5.8+, context is 2nd parameter.
	add_filter( 'block_categories_all', 'swsales_place_blocks_in_panel', 10, 2 );
} else {
	// Pre-5.8, post is 2nd parameter.
	add_filter( 'block_categories', 'swsales_place_blocks_in_panel', 10, 2 );
}

/**
 * Register a dashed border block style
 */
$dashed_border_block_types = array( 'core/heading', 'core/paragraph' );
foreach ( $dashed_border_block_types as $dashed_border_block_type ) {
	register_block_style(
		$dashed_border_block_type,
		array(
			'name'         => 'dashed-border',
			'label'        => __( 'Dashed Border', 'sitewide-sales' ),
			'inline_style' => '.is-style-dashed-border { border: 2px dashed; padding: 1px 3px; }',
		)
	);
}

/**
 * Register a highlighted background block style
 */
$highlight_block_types = array( 'core/heading', 'core/paragraph' );
foreach ( $highlight_block_types as $highlight_block_type ) {
	register_block_style(
		$highlight_block_type,
		array(
			'name'         => 'highlight',
			'label'        => __( 'Highlight', 'sitewide-sales' ),
			'inline_style' => '.is-style-highlight { background: rgba( 255, 255, 255, 0.3 ); padding: 1px 3px; }',
		)
	);
}

/**
 * Enqueue block editor only JavaScript and CSS
 */
function swsales_block_editor_scripts() {
	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'swsales-blocks-editor-js',
		plugins_url( 'js/blocks.build.js', SWSALES_BASE_FILE ),
		[
			'wp-i18n',
			'wp-element',
			'wp-blocks',
			'wp-components',
			'wp-api',
			'wp-block-editor',
		],
		SWSALES_VERSION
	);

	// Enqueue optional editor only styles.
	wp_enqueue_style(
		'swsales-blocks-editor-css',
		plugins_url( 'css/blocks.editor.css', SWSALES_BASE_FILE ),
		array(),
		SWSALES_VERSION
	);

	// Adding translation functionality to Gutenberg blocks/JS.
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'swsales-blocks-editor-js', 'sitewide-sales' );
	}
}
add_action( 'enqueue_block_editor_assets', 'swsales_block_editor_scripts' );
