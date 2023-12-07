<?php
namespace Sitewide_Sales;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * This autoloader looks for class declarations as follows:
 *
 * Sitewide_Sales\classes\SWSales_Settings
 * ^          ^   ^
 * ^          ^   class-swsales-settings.php
 * ^          in the /classes/ folder
 * Project root
 */
function autoload( $class ) {

	// project-specific namespace prefix.
	$prefix = 'Sitewide_Sales\\';

	// base directory for the namespace prefix.
	$base_dir = __DIR__ . '/';

	// does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( 0 !== strncmp( $prefix, $class, $len ) ) {
		// no, move to the next registered autoloader.
		return;
	}

	// get the relative class name.
	$relative_class = substr( $class, $len );
	$relative_class = strtolower( $relative_class );
	$relative_class = str_replace( '_', '-', $relative_class );
	$relative_class = prepend_class( $relative_class );

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php.
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// if the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
}

function prepend_class( $string ) {
	$parts    = explode( '\\', $string );
	$new_name = '';

	for ( $i = 0; $i < count( $parts ); $i++ ) {

		if ( count( $parts ) - 1 === $i ) {
			$new_name .= 'class-';
			$new_name .= $parts[ $i ];
		} else {
			$new_name .= $parts[ $i ];
			$new_name .= '\\';
		}
	}

	return $new_name;
}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( __NAMESPACE__ . '\\autoload' );
}
