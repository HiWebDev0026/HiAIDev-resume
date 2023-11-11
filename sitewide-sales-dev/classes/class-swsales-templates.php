<?php
namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Handles template loading.
 */
class SWSales_Templates {

	/**
	 * Load templates and setup hooks.
	 */
	public static function init() {
	      require_once( SWSALES_DIR . '/templates/fancy-coupon/fancy-coupon.php' );
          require_once( SWSALES_DIR . '/templates/gradient/gradient.php' );
          require_once( SWSALES_DIR . '/templates/neon/neon.php' );
          require_once( SWSALES_DIR . '/templates/ocean/ocean.php' );
          require_once( SWSALES_DIR . '/templates/photo/photo.php' );
          require_once( SWSALES_DIR . '/templates/scroll/scroll.php' );
          require_once( SWSALES_DIR . '/templates/vintage/vintage.php' );
	}
    
    /**
     * Get a list of available banner and landing page templates.
     * Assumes banners and landing pages have the same list of templates.
     * @return array of templates
     */
    public static function get_templates() {
        $templates = apply_filters( 'swsales_templates', array() );
		
		asort( $templates );
        
        return $templates;
    }
}