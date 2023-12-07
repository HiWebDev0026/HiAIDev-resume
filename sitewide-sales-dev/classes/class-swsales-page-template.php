<?php
namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Adds a blank page template.
 * Based off the Blank Slate plugin by Aaron Reimann at
 * https://wordpress.org/plugins/blank-slate/.
 */
class SWSales_Page_Template {

	/**
	 * Adds actions
	 */
	public static function init() {
		add_filter( 'theme_page_templates', array( __CLASS__, 'add_template_to_dropdown' ) );
		add_filter( 'template_include', array( __CLASS__, 'include_template_frontend' ) );
	}

	public static function add_template_to_dropdown( $templates ) {
		$templates['swsales-page-template.php'] = __( 'Sitewide Sale Page', 'sitewide-sales' );
		return $templates;
	}

	public static function include_template_frontend( $template ) {
		if ( is_singular() ) {

			$assigned_template = get_post_meta( get_the_ID(), '_wp_page_template', true );

			if ( 'swsales-page-template.php' === $assigned_template ) {

				if ( file_exists( 'swsales-page-template.php' ) ) {
					return 'swsales-page-template.php';
				}

				$file = wp_normalize_path( SWSALES_DIR . '/includes/page-templates/swsales-page-template.php' );

				if ( file_exists( $file ) ) {
					return $file;
				}
			}
		}

		return $template;
	}
}
