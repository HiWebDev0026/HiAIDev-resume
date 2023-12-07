<?php
//only admins can get this
if ( ! function_exists( "current_user_can" ) || ( ! current_user_can( "manage_options" ) ) ) {
	die( __( "You do not have permissions to perform this action.", 'sitewide-sales' ) );
}

//get values from form
if(isset($_REQUEST['sitewide_sale'])) {
	$id = intval($_REQUEST['sitewide_sale']);
	$sitewide_sale =  \Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_sitewide_sale( $id );
	$daily_revenue = $sitewide_sale->get_daily_sale_revenue();
	$landing_page_post_id = $sitewide_sale->get_landing_page_post_id();

}

$headers   = array();
$headers[] = "Content-Type: text/csv";
$headers[] = "Cache-Control: max-age=0, no-cache, no-store";
$headers[] = "Pragma: no-cache";
$headers[] = "Connection: close";

// Generate a filename based on the params.
$filename = $sitewide_sale->get_name() . "_" . date( "Y-m-d_H-i", current_time( 'timestamp' ) ) . ".csv";
$headers[] = "Content-Disposition: attachment; filename={$filename};";

$left_header=   array (
	"sale ID",
	"sale name",
	"landing page",
	"start date",
	"end date",
	"banner reach",
	"landing page visits",
	"checkouts using coupon " . $sitewide_sale->get_coupon(),
	"sale revenue",
	"other new revenue",
	"renewals",
	"total revenue in period"
);

$csv_file_header_array = array_merge( $left_header, array_keys( $daily_revenue ) );

$dateformat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

// Generate a temporary file to store the data in.
$tmp_dir  = apply_filters( 'swsales_report_csv_export_tmp_dir', sys_get_temp_dir() );

$filename = tempnam( $tmp_dir, 'sws_reportcsv_' );

// open in append mode
$csv_fh = fopen( $filename, 'a' );

//write the CSV header to the file
fputcsv( $csv_fh, $csv_file_header_array);

$csvoutput = array(
	$id,
	$sitewide_sale->get_name(),
	get_permalink( $landing_page_post_id ),
	$sitewide_sale->get_start_date(),
	$sitewide_sale->get_end_date(),
	$sitewide_sale->get_banner_impressions(),
	$sitewide_sale->get_landing_page_visits(),
	$sitewide_sale->get_checkout_conversions(),
	$sitewide_sale->get_sale_revenue(),
	$sitewide_sale->get_other_revenue(),
	$sitewide_sale->get_renewal_revenue(),
	$sitewide_sale->get_total_revenue()
);

$csvoutput = array_merge( $csvoutput, array_values( $daily_revenue ) );
		
fputcsv( $csv_fh, $csvoutput );

//flush the buffer
wp_cache_flush();

swsales_transmit_report_data( $csv_fh, $filename, $headers );


function swsales_transmit_report_data( $csv_fh, $filename, $headers = array() ) {

	//close the temp file
	fclose( $csv_fh );

	if ( version_compare( phpversion(), '5.3.0', '>' ) ) {

		//make sure we get the right file size
		clearstatcache( true, $filename );
	} else {
		// for any PHP version prior to v5.3.0
		clearstatcache();
	}

	//did we accidentally send errors/warnings to browser?
	if ( headers_sent() ) {
		echo str_repeat( '-', 75 ) . "<br/>\n";
		echo 'Please open a support case and paste in the warnings/errors you see above this text to\n ';
		echo '<a href="https://sitewidesales.com/support/?utm_source=plugin&utm_medium=swsales-report-csv&utm_campaign=support" target="_blank">Sitewide Sales support</a><br/>\n';
		echo str_repeat( "=", 75 ) . "<br/>\n";
		echo file_get_contents( $filename );
		echo str_repeat( "=", 75 ) . "<br/>\n";
	}

	//transmission
	if ( ! empty( $headers ) ) {
		//set the download size
		$headers[] = "Content-Length: " . filesize( $filename );

		//set headers
		foreach ( $headers as $header ) {
			header( $header . "\r\n" );
		}

		// disable compression for the duration of file download
		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		if( function_exists( 'fpassthru' ) ) {
			// use fpassthru to output the csv
			$csv_fh = fopen( $filename, 'rb' );
			fpassthru( $csv_fh );
			fclose( $csv_fh );
		} else {
			// use readfile() if fpassthru() is disabled (like on Flywheel Hosted)
			readfile( $filename );
		}

		// remove the temp file
		unlink( $filename );
	}

	exit;
}
