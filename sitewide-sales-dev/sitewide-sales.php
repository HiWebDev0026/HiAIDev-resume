<?php
/**
 * Plugin Name: Sitewide Sales
 * Plugin URI: https://sitewidesales.com
 * Description: Run Black Friday, Cyber Monday, or other flash sales on your WordPress-powered eCommerce or membership site.
 * Author: Stranger Studios
 * Author URI: https://www.strangerstudios.com
 * Version: 1.4
 * Plugin URI:
 * License: GNU GPLv2+
 * Text Domain: sitewide-sales
 *
 * @package sitewide-sales
 */
namespace Sitewide_Sales;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

define( 'SWSALES_VERSION', '1.4' );
define( 'SWSALES_BASE_FILE', __FILE__ );
define( 'SWSALES_DIR', dirname( __FILE__ ) );
define( 'SWSALES_BASENAME', plugin_basename( __FILE__ ) );

require 'autoload.php';

// Sets up shortcode [sitewide_sales] and landing page-related code.
classes\SWSales_Landing_Pages::init();

// Handles displaying/saving metaboxes for Sitewide Sale CPT and
// returning from editing a discount code/landing page associated
// with Sitewide Sale.
classes\SWSales_MetaBoxes::init();

// Sets up Sitewide Sale CPT and associated menu.
classes\SWSales_Post_Types::init();

// Generates report pages and enqueues JS to track interaction
// with Sitewide Sale.
classes\SWSales_Reports::init();

// Sets up pmpro_sitewide_sale option.
classes\SWSales_Settings::init();

// Enqueues scripts and does other administrative things.
classes\SWSales_Setup::init();

// Enqueues settings for privacy policy page
classes\SWSales_Privacy::init();

// Handle templates
classes\SWSales_Templates::init();

// Add blank page template
classes\SWSales_Page_Template::init();

// Add a general About admin page.
classes\SWSales_About::init();

// Add a license admin page.
classes\SWSales_License::init();

// Helper functions
require_once ( SWSALES_DIR . '/blocks/blocks.php' );
require_once ( SWSALES_DIR . '/includes/admin.php' );
require_once ( SWSALES_DIR . '/includes/functions.php' );
require_once ( SWSALES_DIR . '/includes/license.php' );

// Load Ecommerce Modules
function swsales_load_modules() {
	require_once SWSALES_DIR . '/modules/ecommerce/pmpro/class-swsales-module-pmpro.php';
	require_once SWSALES_DIR . '/modules/ecommerce/wc/class-swsales-module-wc.php';
	require_once SWSALES_DIR . '/modules/ecommerce/custom/class-swsales-module-custom.php';
	require_once SWSALES_DIR . '/modules/ecommerce/edd/class-swsales-module-edd.php';

	require_once SWSALES_DIR . '/classes/class-swsales-banner-module.php';
    require_once SWSALES_DIR . '/modules/banner/blocks/class-swsales-banner-module-blocks.php';
	require_once SWSALES_DIR . '/modules/banner/swsales/class-swsales-banner-module-swsales.php';
	require_once SWSALES_DIR . '/modules/banner/pum/class-swsales-banner-module-pum.php';
}
add_action( 'init', 'Sitewide_Sales\\swsales_load_modules', 1 );
