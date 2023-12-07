<?php
/**
 * Helper functions, often wrapping some functionality burried in a class.
 */
 
 /**
  * What is the active sitewide sale id?
  */
 function swsales_active_sitewide_sale_id() {
	 $options = \Sitewide_Sales\classes\SWSales_Settings::get_options();
	 return $options['active_sitewide_sale_id'];
 }
 
 /**
  * What is the template for this landing page?
  * Returns false if we're not even on a landing page.
  */
 function swsales_landing_page_template( $post_id = null ) {
	 // Default to queried object.
	 if ( empty( $post_id ) ) {
		 $post_id = get_queried_object_id();
	 }

	 // Return false if no post.
	 if ( empty( $post_id ) ) {
		 return false;
	 }
	 
	 // See if any Sitewide Sale CPTs have this post ID set as the Landing Page.
	 $sitewide_sale_id = get_post_meta( $post_id, 'swsales_sitewide_sale_id', true );

	 // Return false if not a landing page.
	 if ( empty( $sitewide_sale_id ) ) {
		 return false;
	 }

	 // Get the landing page template for the specific sale.
	 $template = get_post_meta( $sitewide_sale_id, 'swsales_landing_page_template', true );
	 return $template;
 }

 /**
  * What is the template for the active sitewide sale banner?
  * Returns false if there is no sale or banner.
  */
 function swsales_banner_template( ) {

	// Get the active sitewide sale or the sale being previewed.
	if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
		$sitewide_sale_id = intval( $_REQUEST['swsales_preview_sale_banner'] );
	} else {
		$sitewide_sale_id = swsales_active_sitewide_sale_id();
	}

	 // Return false if no sale.
	 if ( empty( $sitewide_sale_id ) ) {
		 return false;
	 }
	 
	 // Get the banner template for the specific sale.
	 $template = get_post_meta( $sitewide_sale_id, 'swsales_banner_template', true );
	 return $template;
 }

/**
 * Gets the coupon for a given sitewide sale.
 * Filterable by modules using "swsales_coupon" filter.
 *
 * @param int|SWSales_Sitewide_Sale $sitewide_sale to get coupon for. Defaults to active.
 * @return string
 */
function swsales_coupon( $sitewide_sale = null ) {
	if ( 'object' !== gettype( $sitewide_sale ) || 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' !== get_class( $sitewide_sale ) ) {
		$sitewide_sale_id = swsales_active_sitewide_sale_id();
		if ( is_numeric( $sitewide_sale ) ) {
			$sitewide_sale_id = intval( $sitewide_sale );
		} elseif ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$sitewide_sale_id = intval( $_REQUEST['swsales_preview_sale_banner'] );
		}
		$sitewide_sale = \Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_sitewide_sale( $sitewide_sale_id );
	}

	// Return null if no sale.
	if ( empty( $sitewide_sale ) ) {
		// Note when this happens we don't run the filter in $sitewide_sale->get_coupon().
		return null;
	}

	return $sitewide_sale->get_coupon();
}

/**
 * Set a cron event if there's no cron available. Wrapper for wp_schedule_event.
 * 
 * @param string $timestamp When to next run the event
 * @param string $recurrence the frequency of the cron job.
 * @param string $hook Action hook of the event.
 * @param array $args Arguments for hook callback.
 * 
 * Ref: https://developer.wordpress.org/reference/functions/wp_schedule_event/
 */
function swsales_maybe_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
	$next = wp_next_scheduled( $hooks, $args );

	if ( empty( $next ) ) {
		return wp_schedule_event( $timestamp, $recurrence, $hook, $args );
	} else {
		return false;
	}
}

/**
 * Check if certain plugins or themes are installed and activated
 * and if found dynamically load the relevant /includes/compatibility/ files.
 */
function swsales_compatibility_checker() {
	$compat_checks = [
		[
			'file'		=> 'elementor.php',
			'check_type'  => 'constant',
			'check_value' => 'ELEMENTOR_VERSION',
		],
        [
			'file'		=> 'divi.php',
			'check_type'  => 'constant',
			'check_value' => 'ET_BUILDER_PLUGIN_DIR',
		],
	];

	foreach ( $compat_checks as $value ) {
		if ( swsales_compatibility_checker_is_requirement_met( $value ) ) {
			include_once( SWSALES_DIR . '/includes/compatibility/' . $value['file'] ) ;
		}
	}
}
add_action( 'plugins_loaded', 'swsales_compatibility_checker' );

/**
 * Check whether the requirement is met.
 *
 * @since 1.3.0
 *
 * @param array $requirement The requirement config (check_type, check_value, check_constant_true).
 *
 * @return bool Whether the requirement is met.
 */
function swsales_compatibility_checker_is_requirement_met( $requirement ) {
	// Make sure we have the keys that we expect.
	if ( ! isset( $requirement['check_type'], $requirement['check_value'] ) ) {
		return false;
	}

	// Check for a constant and maybe check if the constant is true-ish.
	if ( 'constant' === $requirement['check_type'] ) {
		return (
			defined( $requirement['check_value'] )
			&& (
				empty( $requirement['check_constant_true'] )
				|| constant( $requirement['check_value'] )
			)
		);
	}

	// Check for a function.
	if ( 'function' === $requirement['check_type'] ) {
		return function_exists( $requirement['check_value'] );
	}

	// Check for a class.
	if ( 'class' === $requirement['check_type'] ) {
		return class_exists( $requirement['check_value'] );
	}

	return false;
}

function swsales_compatibility_checker_themes() {
	$compat_checks = array(
		array(
			'file' => 'divi.php',
			'check_type' => 'constant',
			'check_value' => 'ET_BUILDER_THEME' //Adds support for the Divi theme.
		)
	);

	foreach ( $compat_checks as $key => $value ) {
		if ( swsales_compatibility_checker_is_requirement_met( $value ) ) {
			include_once( SWSALES_DIR . '/includes/compatibility/' . $value['file'] ) ;
		}
	}
}
add_action( 'after_setup_theme', 'swsales_compatibility_checker_themes' );
