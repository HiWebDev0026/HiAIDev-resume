<?php
/**
 * Admin Area additional functions.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds the Sitewide Sales branded header to the Sitewide Sales settings pages.
 *
 * @since 1.3
 */
function swsales_admin_header() {
	if ( ! swsales_is_admin_page( '', '', false ) ) {
		return;
	}
	?>
	<div class="sitewide_sales_banner">
		<div class="sitewide_sales_banner_wrapper">
			<div class="sitewide_sales_logo">
				<span class="screen-reader-text"><?php esc_html_e( 'Sitewide Sales', 'sitewide-sales' ); ?></span>
				<h1><a target="_blank" href="https://sitewidesales.com/?utm_source=plugin&utm_medium=sitewide-sales-reports&utm_campaign=homepage"><img src="<?php echo esc_url( plugins_url( 'images/Sitewide-Sales.png', SWSALES_BASENAME ) ); ?>" width="250" border="0" alt="<?php esc_attr_e( 'Sitewide Sales(c) - All Rights Reserved', 'sitewide-sales' ); ?>" /></a></h1>
				<span class="sitewide_sales_version">v<?php echo SWSALES_VERSION; ?></span>
			</div> <!-- end sitewide_sales_logo -->
			<div class="sitewide_sales_meta">
				<a href="https://sitewidesales.com/documentation/?utm_source=plugin&utm_medium=swsales-admin-header&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Documentation', 'sitewide-sales' ); ?>"><?php esc_html_e( 'Documentation', 'sitewide-sales' ); ?></a>
				<a href="https://sitewidesales.com/support/?utm_source=plugin&utm_medium=swsales-admin-header&utm_campaign=support" target="_blank" title="<?php esc_attr_e( 'Get Support', 'sitewide-sales' );?>"><?php esc_html_e( 'Get Support', 'sitewide-sales' );?></a>
				<?php if ( swsales_license_is_valid() ) { ?>
					<?php printf(__( '<a class="swsales_license_tag swsales_license_tag-valid" href="%s">Valid License</a>', 'sitewide-sales' ), admin_url( 'edit.php?post_type=sitewide_sale&page=sitewide_sales_license' ) ); ?>
				<?php } elseif ( ! defined( 'SWSALES_LICENSE_NAG' ) || SWSALES_LICENSE_NAG == true ) { ?>
					<?php printf(__( '<a class="swsales_license_tag swsales_license_tag-invalid" href="%s">No License</a>', 'sitewide-sales' ), admin_url('edit.php?post_type=sitewide_sale&page=sitewide_sales_license' ) ); ?>
				<?php } ?>
			</div> <!-- end sitewide_sales_meta -->
		</div> <!-- end sitewide_sales_banner_wrapper -->
	</div> <!-- end sitewide_sales_banner -->
	<?php
}
add_action( 'admin_notices', 'swsales_admin_header', 1 );

/**
 * Determines whether the current admin page is a specific Sitewide Sales admin page.
 *
 * @since 1.3
 *
 * @param string $passed_page Optional. Main page's slug
 * @param string $passed_view Optional. Page view ( ex: `edit` or `delete` )
 *
 * @return bool True if SWS admin page we're looking for or an SWS page or if $page is empty, any SWS page
 */
function swsales_is_admin_page( $passed_page = '', $passed_view = '' ) {

	global $pagenow, $typenow;

	$found      = false;
	$post_type  = isset( $_GET['post_type'] )  ? strtolower( $_GET['post_type'] )  : false;
	$action     = isset( $_GET['action'] )     ? strtolower( $_GET['action'] )     : false;
	$page       = isset( $_GET['page'] )       ? strtolower( $_GET['page'] )       : false;

	switch ( $passed_page ) {
		default:
			$admin_pages = array( 'sitewide_sales_reports', 'sitewide_sales_about', 'sitewide_sales_license' );
			if ( 'sitewide_sale' == $typenow ) {
				$found = true;
			} elseif ( in_array( $pagenow, $admin_pages ) ) {
				$found = true;
			}
			break;
	}

	return (bool) apply_filters( 'swsales_is_admin_page', $found, $page,$passed_page, $passed_view );
}
