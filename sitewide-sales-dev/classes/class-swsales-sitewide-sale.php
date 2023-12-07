<?php
namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Sitewide_Sale {

	/**
	 * ID of SWSales_Sitewide_Sale
	 *
	 * @var int
	 */
	 protected $id = 0;

	/**
	 * Name of sale
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Text on banner
	 *
	 * @var string
	 */
	protected $banner_text = 'Save during our Sitewide Sale!';

	/**
	 * Associative array conatining all post meta
	 *
	 * @var array()
	 */
	protected $post_meta = array();

	/**
	 * True if this is the active Sitewide Sale
	 *
	 * @var bool
	 */
	protected $is_active_sitewide_sale = false;

	/**
	 * Constructor for the Sitewide Sale class.
	 */
	public function __construct() {
		// Set default post meta.
		$default_post_meta = array(
			'swsales_start_date'            => $this->get_start_date( 'Y-m-d H:i:s' ),
			'swsales_end_date'              => $this->get_end_date( 'Y-m-d H:i:s' ),
			'swsales_sale_type'             => $this->get_sale_type(),
			'swsales_automatic_discount'    => $this->get_automatic_discount(),
			'swsales_landing_page_post_id'  => $this->get_landing_page_post_id(),
			'swsales_landing_page_template' => $this->get_landing_page_template(),
			'swsales_pre_sale_content'      => $this->get_pre_sale_content(),
			'swsales_sale_content'          => $this->get_sale_content(),
			'swsales_post_sale_content'     => $this->get_post_sale_content(),
			'swsales_hide_on_checkout'      => $this->get_hide_on_checkout(),
			'swsales_banner_impressions'    => $this->get_banner_impressions(),
			'swsales_landing_page_visits'   => $this->get_landing_page_visits(),
		);

		// Filter to add default post meta.
		$this->post_meta = apply_filters( 'swsales_default_post_meta', $default_post_meta, $this->get_id() );
	}

	/**
	 * Set all information for Sitewide Sale from database
	 *
	 * @param int $sitewide_sale_id to load.
	 * @return bool $success
	 */
	public function load_sitewide_sale( $sitewide_sale_id ) {
		$raw_post = get_post( $sitewide_sale_id );

		// Check if $sitewide_sale_id is a valid sitewide sale.
		if ( null === $raw_post || 'sitewide_sale' !== $raw_post->post_type ) {
			return false;
		}

		// Load raw info from WP_Post object.
		$this->id          = $raw_post->ID;
		$this->name        = $raw_post->post_title;
		$this->banner_text = $raw_post->post_content;

		// Determine if this Sitewide Sale is active.
		$options                       = SWSales_Settings::get_options();
		$this->is_active_sitewide_sale = ( $options['active_sitewide_sale_id'] == $this->id ? true : false );

		// Merge post meta.
		$raw_post_meta = get_post_meta( $raw_post->ID );
		foreach ( $raw_post_meta as $key => $value ) {
			$raw_post_meta[ $key ] = $value[0];
		}
		$this->post_meta = array_merge( $this->post_meta, $raw_post_meta );

		return true;
	}

	/**
	 * Returns the corresponding Sitewide Sale object.
	 *
	 * @param int $id of sale to get.
	 * @return SWSales_Sitewide_Sale active sale
	 */
	public static function get_sitewide_sale( $id ) {
		static $swsales_sitewide_sales = array();

		if ( ! isset( $swsales_sitewide_sales[ $id ] ) ) {
			$sitewide_sale                 = new SWSales_Sitewide_Sale();
			$valid_sale                    = $sitewide_sale->load_sitewide_sale( $id );
			$swsales_sitewide_sales[ $id ] = $valid_sale ? $sitewide_sale : null;
		}

		return $swsales_sitewide_sales[ $id ];
	}

	/**
	 * Returns the active SWSales_Sitewide_Sale object
	 *
	 * @return SWSales_Sitewide_Sale active sale
	 */
	public static function get_active_sitewide_sale() {
		static $swsales_active_sitewide_sale = null;

		if ( isset( $swsales_active_sitewide_sale ) ) {
			return $swsales_active_sitewide_sale;
		}

		$options = SWSales_Settings::get_options();
		if ( empty( $options['active_sitewide_sale_id'] ) ) {
			$swsales_active_sitewide_sale = null;
		} else {
			$swsales_active_sitewide_sale = self::get_sitewide_sale( $options['active_sitewide_sale_id'] );
		}
		return $swsales_active_sitewide_sale;
	}

	/**
	 * Returns the SWSales_Sitewide_Sale object for a given landing page ID
	 *
	 * @param int $landing_page_id to get Sitewide Sale for.
	 * @return SWSales_Sitewide_Sale for landing page or null if not found
	 */
	public static function get_sitewide_sale_for_landing_page( $landing_page_id ) {
		static $swsales_sitewide_sales = array();

		if ( ! isset( $swsales_sitewide_sales[ $landing_page_id ] ) ) {
			$sitewide_sale_id                           = get_post_meta( $landing_page_id, 'swsales_sitewide_sale_id', true );
			$swsales_sitewide_sales[ $landing_page_id ] = self::get_sitewide_sale( $sitewide_sale_id );
		}
		return $swsales_sitewide_sales[ $landing_page_id ];
	}

	/**
	 * -----------------------------
	 * GETTER FUNCTIONS (POST META)
	 * -----------------------------
	 */

	/**
	 * Returns ID of Sitewide sale
	 * or 0 if not set.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the sale name
	 *
	 * @return string
	 */
	public function get_name() {
		$sale_name = $this->name ? $this->name : __( '(no title)', 'sitewide-sales' );
		return $sale_name;
	}

	/**
	 * Returns the entire start date in yyyy-mm-dd format
	 *
	 * @param string $dateformatstring date_i18n format.
	 * @return string
	 */
	public function get_start_date( $dateformatstring = null ) {
		if ( null === $dateformatstring ) {
			$dateformatstring = get_option( 'date_format' );
		}

		if ( 
			isset( $this->post_meta['swsales_start_day'] ) &&
			isset( $this->post_meta['swsales_start_month'] ) &&
			isset( $this->post_meta['swsales_start_year'] )
		) {
			// Using deprecated date format. Convert, save new meta, and delete deprecated meta.
			$start_date = $this->post_meta['swsales_start_year'] . '-' . $this->post_meta['swsales_start_month'] . '-' . $this->post_meta['swsales_start_day'] . ' 00:00:00' ;
			update_post_meta( $this->id, 'swsales_start_date', $start_date );
			$this->post_meta['swsales_start_date'] = $start_date;
			delete_post_meta( $this->id, 'swsales_start_day' );
			delete_post_meta( $this->id, 'swsales_start_month' );
			delete_post_meta( $this->id, 'swsales_start_year' );
		}
		elseif ( isset( $this->post_meta['swsales_start_date'] ) ) {
			// We already have a date.
			$start_date = $this->post_meta['swsales_start_date'];
		} else {
			// Get a new date.
			$start_date = date( 'Y-m-d', strtotime( '+1 week', current_time( 'timestamp' ) ) ) . ' 00:00:00';
		}

		return date_i18n( $dateformatstring, strtotime( $start_date ) );
	}

	/**
	 * Returns the entire end date in yyyy-mm-dd format
	 *
	 * @param string $dateformatstring date_i18n format.
	 * @return string
	 */
	public function get_end_date( $dateformatstring = null ) {
		if ( null === $dateformatstring ) {
			$dateformatstring = get_option( 'date_format' );
		}

		if ( 
			isset( $this->post_meta['swsales_end_day'] ) &&
			isset( $this->post_meta['swsales_end_month'] ) &&
			isset( $this->post_meta['swsales_end_year'] )
		) {
			// Using deprecated date format. Convert, save new meta, and delete deprecated meta.
			$end_date = $this->post_meta['swsales_end_year'] . '-' . $this->post_meta['swsales_end_month'] . '-' . $this->post_meta['swsales_end_day'] . ' 00:00:00' ;
			update_post_meta( $this->id, 'swsales_end_date', $end_date );
			$this->post_meta['swsales_end_date'] = $end_date;
			delete_post_meta( $this->id, 'swsales_end_day' );
			delete_post_meta( $this->id, 'swsales_end_month' );
			delete_post_meta( $this->id, 'swsales_end_year' );
		}
		elseif ( isset( $this->post_meta['swsales_end_date'] ) ) {
			// We already have a date.
			$end_date = $this->post_meta['swsales_end_date'];
		} else {
			// Get a new date.
			$end_date = date( 'Y-m-d', strtotime( '+2 weeks', current_time( 'timestamp' ) ) ) . ' 23:59:00';
		}

		return date_i18n( $dateformatstring, strtotime( $end_date ) );
	}

	/**
	 * Returns 'past', 'current' or 'future' based
	 * on the sale start/end dates and the current date.
	 * If start date is after end date, returns 'error'
	 *
	 * @return string
	 */
	public function get_time_period() {
		$current_date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

		if ( $this->get_start_date( 'Y-m-d H:i:s' ) > $this->get_end_date( 'Y-m-d H:i:s' ) ) {
			return 'error';
		} elseif ( $current_date < $this->get_start_date( 'Y-m-d H:i:s' ) ) {
			return 'pre-sale';
		} elseif ( $current_date > $this->get_end_date( 'Y-m-d H:i:s' ) ) {
			return 'post-sale';
		}
		return 'sale';
	}

	/**
	 * Returns the sale type (module)
	 *
	 * @return string
	 */
	public function get_sale_type() {
		if ( isset( $this->post_meta['swsales_sale_type'] ) ) {
			return $this->post_meta['swsales_sale_type'];
		} else {
			return '';
		}
	}

	/**
	 * Returns whether to apply discount automatically.
	 * Options are 'none', 'landing','all'.
	 * 'none' if no users should get automatic discount
	 * 'landing' if users who have seen the landing page should get automatic discount
	 * 'all' if all users should get automatic discount
	 *
	 * @return string
	 */
	public function get_automatic_discount() {
		if ( isset( $this->post_meta['swsales_automatic_discount'] ) ) {
			return $this->post_meta['swsales_automatic_discount'];
		} else {
			return 'none';
		}
	}

	/**
	 * Returns whether the current user should have the sitewide sale's discount
	 * automatically applied.
	 *
	 * @return bool
	 */
	public function should_apply_automatic_discount() {
		switch ( $this->get_automatic_discount() ) {
			case 'all':
				return $this->is_running();
			case 'landing':
				$cookie_name = 'swsales_' . $this->get_id() . '_tracking';
				return ( $this->is_running() && isset( $_COOKIE[ $cookie_name ] ) && false !== strpos( $_COOKIE[ $cookie_name ], ';1' ) );
			default:
				return false;
		}
	}

	/**
	 * Returns the ID of the sale landing page
	 * or 0 if it is not set.
	 *
	 * @return int
	 */
	public function get_landing_page_post_id() {
		if ( isset( $this->post_meta['swsales_landing_page_post_id'] ) ) {
			return $this->post_meta['swsales_landing_page_post_id'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns landing page template
	 *
	 * @return string
	 */
	public function get_landing_page_template() {
		if ( isset( $this->post_meta['swsales_landing_page_template'] ) ) {
			return $this->post_meta['swsales_landing_page_template'];
		} else {
			return '';
		}
	}

	/**
	 * Returns pre-sale content
	 *
	 * @return string
	 */
	public function get_pre_sale_content() {
		if ( isset( $this->post_meta['swsales_pre_sale_content'] ) ) {
			return $this->post_meta['swsales_pre_sale_content'];
		} else {
			return '';
		}
	}

	/**
	 * Returns sale content
	 *
	 * @return string
	 */
	public function get_sale_content() {
		if ( isset( $this->post_meta['swsales_sale_content'] ) ) {
			return $this->post_meta['swsales_sale_content'];
		} else {
			return '';
		}
	}

	/**
	 * Returns post-sale content
	 *
	 * @return string
	 */
	public function get_post_sale_content() {
		if ( isset( $this->post_meta['swsales_post_sale_content'] ) ) {
			return $this->post_meta['swsales_post_sale_content'];
		} else {
			return '';
		}
	}

	/**
	 * Returns the appropriate sale content
	 * based on passed time period
	 *
	 * @return string
	 */
	public function get_sale_content_for_time_period( $time_period ) {
		switch ( $time_period ) {
			case 'post-sale':
				return $this->get_post_sale_content();
			case 'sale':
				return $this->get_sale_content();
			case 'pre-sale':
				return $this->get_pre_sale_content();
			default:
				return '';
		}
	}

	/**
	 * Returns the appropriate sale content
	 * based on if the sale is prior, current,
	 * or future.
	 *
	 * @return string
	 */
	public function get_current_sale_content() {
		$this->get_sale_content_for_time_period( $this->get_time_period() );
	}

	/**
	 * Returns the banner text
	 *
	 * @return string
	 */
	public function get_banner_text() {
		return $this->banner_text;
	}

	/**
	 * Returns how the banner should be handled after closing
	 *
	 * @return bool
	 */
	public function get_banner_close_behavior() {
		if ( isset( $this->post_meta['swsales_banner_close_behavior'] ) ) {
			return $this->post_meta['swsales_banner_close_behavior'];
		} else {
			return 'refresh';
		}
	}

	/**
	 * Returns if banner should be hidden on checkout
	 *
	 * @return bool
	 */
	public function get_hide_on_checkout() {
		if ( isset( $this->post_meta['swsales_hide_on_checkout'] ) ) {
			return $this->post_meta['swsales_hide_on_checkout'];
		} else {
			return true;
		}
	}

	/**
	 * Returns if this is the active Sitewide Sale
	 *
	 * @return bool
	 */
	public function is_active_sitewide_sale() {
		return $this->is_active_sitewide_sale;
	}

	/**
	 * Whether the current sitewide sale should be hidden.
	 *
	 * @return string
	 */
	public function hide_sale() {
		// Assume sale is visible to everyone.
		$hide_sale = false;

		// Get the meta value for roles this sale should be hidden for.
		$hide_for_roles = json_decode( $this->get_meta_value( 'swsales_hide_for_roles', '' ) );

		// If the hidden roles is an empty string, convert to an array.
		$hide_for_roles = empty( $hide_for_roles ) ? array() : $hide_for_roles;

		// Get the current user roles or if logged out, set the 'ghost' role.
		if ( ! is_user_logged_in() ) {
			$user_roles = array( 'logged_out' );
		} else {
			$user = wp_get_current_user();
			$user_roles = ( array ) $user->roles;
		}
		// If this sale is hidden by role, check if the current user should see it.
		if ( ! empty( $hide_for_roles ) && ! empty( array_intersect( $hide_for_roles, $user_roles ) ) ) {
			$hide_sale = true;
		}

		return apply_filters( 'swsales_hide', $hide_sale, $this );
	}

	/**
	 * Returns the number of times this sale's banner has been shown to unique users.
	 *
	 * @return int
	 */
	public function get_banner_impressions() {
		if ( isset( $this->post_meta['swsales_banner_impressions'] ) ) {
			return $this->post_meta['swsales_banner_impressions'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns the number of times this sale's landing page has been shown to unique users.
	 *
	 * @return int
	 */
	public function get_landing_page_visits() {
		if ( isset( $this->post_meta['swsales_landing_page_visits'] ) ) {
			return $this->post_meta['swsales_landing_page_visits'];
		} else {
			return 0;
		}
	}

	/**
	 * Gets the coupon code for the curent sitewide sale.
	 * Must be filtered by module.
	 *
	 * @return string
	 */
	public function get_coupon() {
		return apply_filters( 'swsales_coupon', null, $this );
	}

	/**
	 * Gets specific piece of post meta.
	 *
	 * @param  string $meta_key of data.
	 * @param  mixed  $default data to return if metadata not found.
	 * @return mixed metadata
	 */
	public function get_meta_value( $meta_key, $default = null ) {
		if ( isset( $this->post_meta[ $meta_key ] ) ) {
			return $this->post_meta[ $meta_key ];
		}
		return $default;
	}

	/**
	 * Magic method.
	 * If you get $this->key for any property that isn't
	 * set yet, this will return the cooresponding post meta.
	 *
	 * @param string $key to get.
	 */
	public function __get( $key ) {
		if ( isset( $this->data->$key ) ) {
			$value = $this->data->$key;
		} else {
			$value = get_post_meta( $this->id, $key, true );
		}
		return $value;
	}

	/**
	 * Returns whether this is the active sitewide sale and if the sale is currently running.
	 *
	 * @return boolean
	 */
	public function is_running() {
		// Don't check if the sale is hidden in the admin.
		if ( is_admin() ) {
			return ( $this->is_active_sitewide_sale() && 'sale' === $this->get_time_period() );
		}

		// Allow admins to preview the sale period and banners.
		// This logic shows banner or landing page content regardless of whether sale is 'active' or in the 'sale' period.
		if ( current_user_can( 'administrator' ) && ( isset( $_REQUEST['swsales_preview_time_period'] ) || isset( $_REQUEST['swsales_preview_sale_banner'] ) ) ) {
			return true;
		}

		// If the sale is hidden for this user, return.
		if ( $this->hide_sale()) {
			return;
		}

		return ( $this->is_active_sitewide_sale() && 'sale' === $this->get_time_period() );
	}

	/**
	 * -----------------------------
	 * GETTER FUNCTIONS (MODULES)
	 * -----------------------------
	 */

	/**
	 * Returns the number of checkouts which used the sale's discount code/coupon.
	 * Must be filtered by the sale's module.
	 *
	 * @param bool formatted whether to format the revenue.
	 * @return string number of checkouts using sale code.
	 */
	public function get_checkout_conversions($formatted = false) {
		return apply_filters( 'swsales_get_checkout_conversions', '0', $this, $formatted );
	}

	/**
	 * Returns the revenue generated during the sale period.
	 * Must be filtered by the sale's module.
	 *
	 * @param bool formatted whether to format the revenue.
	 * @return string revenue from sale.
	 *
	 * @since 1.4
	 */
	public function get_sale_revenue($formatted = false) {
		return apply_filters( 'swsales_get_revenue', '&#8212;', $this, $formatted );
	}

	/**
	 * Returns the revenue generated during the sale period from sales using the sale's discount code/coupon.
	 * Must be filtered by the sale's module.
	 *
	 * @param bool formatted whether to format the revenue.
	 * @return string revenue from sale code.
	 *
	 * @since 1.4
	 */
	public function get_other_revenue($formatted = false) {
		return apply_filters( 'swsales_get_other_revenue', '&#8212;', $this, $formatted );
	}

	/**
	 * Return revenue from renewals during the sale period.
	 * Must be filtered by the sale's module.
	 *
	 * @param bool formatted whether to format the revenue.
	 * @return string revenue from renewals.
	 *
	 * @since 1.4
	 */
	public function get_renewal_revenue($formatted = false) {
		return apply_filters( 'swsales_get_renewal_revenue', '&#8212;', $this, $formatted );
	}

	/**
	 * Gets total revenue from the sale period.
	 *
	 * @param bool formatted whether to format the revenue.
	 * @return string total revenue
	 *
	 * @since 1.4
	 */
	public function get_total_revenue($formatted = false) {
		return apply_filters( 'swsales_get_total_revenue', '&#8212;', $this, $formatted );
	}

	/**
	 * Gets an array with revenue by day.
	 *
	 * @param bool formatted whether to format the revenue.
	 * @return array revenue by day
	 *
	 * @since 1.4
	 */
	public function get_daily_sale_revenue() {
		// Daily Revenue Chart.
		// Build an array with each day of sale as a key to store revenue data in.
		$date_array_all = array();
		$period = new \DatePeriod(
			new \DateTime( $this->get_start_date( 'Y-m-d' ) ),
			new \DateInterval('P1D'),
			new \DateTime( $this->get_end_date( 'Y-m-d' ) . ' + 1 day' )
		);
		foreach ($period as $key => $value) {
			$date_array_all[ $value->format('Y-m-d') ] = 0.0;
		}

		// Get revenue data from module.
		return apply_filters( 'swsales_daily_revenue_chart_data', $date_array_all, $this );
	}

	/**
	 * -----------------------------
	 * DEPRECATED
	 * -----------------------------
	 */
	/**
	 * Returns the 'day' element of the start date
	 *
	 * @deprecated since 1.2
	 * @return string
	 */
	public function get_start_day() {
		return $this->get_start_date('d');

	}

	/**
	 * Returns the 'month' element of the start date
	 *
	 * @deprecated since 1.2
	 * @return string
	 */
	public function get_start_month() {
		return $this->get_start_date('m');

	}

	/**
	 * Returns the 'month' element of the start date
	 *
	 * @deprecated since 1.2
	 * @return string
	 */
	public function get_start_year() {
		return $this->get_start_date('Y');
	}

	/**
	 * Returns the 'day' element of the end date
	 *
	 * @deprecated since 1.2
	 * @return string
	 */
	public function get_end_day() {
		return $this->get_end_date('d');
	}

	/**
	 * Returns the 'month' element of the end date
	 *
	 * @deprecated since 1.2
	 * @return string
	 */
	public function get_end_month() {
		return $this->get_end_date('m');

	}

	/**
	 * Returns the 'month' element of the end date
	 *
	 * @deprecated since 1.2
	 * @return string
	 */
	public function get_end_year() {
		return $this->get_end_date('Y');
	}
}
