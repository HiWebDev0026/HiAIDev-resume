<?php

namespace Sitewide_Sales\modules;

use Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Module_EDD {

	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/modules
	 */
	public static function init() {
		// Register sale type.
		add_filter( 'swsales_sale_types', array( __CLASS__, 'register_sale_type' ) );

		// Add fields to Edit Sitewide Sale page.
		add_action( 'swsales_after_choose_sale_type', array( __CLASS__, 'add_choose_coupon' ) );

		// Bail on additional functionality if EDD is not active.
		if ( ! class_exists( 'Easy_Digital_Downloads', false ) && !defined( 'EDD_VERSION' ) ) {
			return;
		}

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'save_metaboxes' ), 99, 2 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Generate coupons from editing sitewide sale.
		add_action( 'wp_ajax_swsales_edd_create_coupon', array( __CLASS__, 'create_coupon_ajax' ) );

		// Custom EDD banner rules (hide at checkout).
		add_filter( 'swsales_is_checkout_page', array( __CLASS__, 'is_checkout_page' ), 10, 2 );
		
		// For the swsales_coupon helper function
		add_filter( 'swsales_coupon', array( __CLASS__, 'swsales_coupon' ), 10, 2 );

		// Automatic coupon application.
		add_filter( 'init', array( __CLASS__, 'automatic_coupon_application' ) );
		add_filter( 'edd_download_price', array( __CLASS__, 'strike_prices' ), 10, 2 );
		add_filter( 'edd_purchase_link_args', array( __CLASS__, 'edd_purchase_link_args_strike_prices' ) );

		// EDD-specific reports.
		add_filter( 'swsales_checkout_conversions_title', array( __CLASS__, 'checkout_conversions_title' ), 10, 2 );
		add_filter( 'swsales_daily_revenue_chart_currency_format', array( __CLASS__, 'swsales_daily_revenue_chart_currency_format' ), 10, 2 );

		if( version_compare( floatval( EDD_VERSION ), 3, '>=' ) ){
			//EDD V3's Stats			
			add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'checkout_conversions' ), 10, 2 );
			add_filter( 'swsales_get_revenue', array( __CLASS__, 'sale_revenue' ), 10, 3 );
			add_filter( 'swsales_daily_revenue_chart_data', array( __CLASS__, 'daily_revenue_chart_data' ), 10, 2 );
			add_filter( 'swsales_get_other_revenue', array( __CLASS__, 'get_other_revenue' ), 10, 3 );
			add_filter( 'swsales_get_total_revenue', array( __CLASS__, 'total_revenue' ), 10, 3 );
		} else {
			//EDD Legacy Stats
			add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'legacy_checkout_conversions' ), 10, 2 );
			add_filter( 'swsales_get_revenue', array( __CLASS__, 'legacy_sale_revenue' ), 10, 3 );
			add_filter( 'swsales_daily_revenue_chart_data', array( __CLASS__, 'legacy_daily_revenue_chart_data' ), 10, 2 );
		}
		
	}

	/**
	 * Register WooCommerce module with SWSales
	 *
	 * @param  array $sale_types that are registered in SWSales.
	 * @return array
	 */
	public static function register_sale_type( $sale_types ) {
		$sale_types['edd'] = 'Easy Digital Downloads';
		return $sale_types;
	} // end register_sale_type()

	/**
	 * Adds option to choose coupon in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_choose_coupon( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-edd'>
			<?php if ( ! class_exists( 'Easy_Digital_Downloads', false ) ) { ?>
				<th></th>
				<td>
					<div class="sitewide_sales_message sitewide_sales_error">
						<p><?php echo esc_html( 'The Easy Digital Downloads plugin is not active.', 'sitewide-sales' ); ?></p>
					</div>
				</td>
				<?php
			} else {

				//Compatible with EDD 2.9 and 3.0
				$coupons = edd_get_discounts(
					array(
						'fields' => array( 'id', 'code'),
						'status' => 'active',
					)
				);
				$current_coupon = intval( $cur_sale->get_meta_value( 'swsales_edd_coupon_id', null ) );
				?>
					<th><label for="swsales_edd_coupon_id"><?php esc_html_e( 'Discount Code', 'sitewide-sales' );?></label></th>
					<td>
						<select class="coupon_select swsales_option" id="swsales_edd_coupon_select" name="swsales_edd_coupon_id">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$coupon_found = false;
							if( is_array( $coupons ) ){									
								foreach ( $coupons as $coupon ) {
									$selected_modifier = '';
									if ( (int)$coupon->id === $current_coupon ) {
										$selected_modifier = ' selected="selected"';
										$coupon_found      = $coupon;
									}
									echo '<option value="' . esc_attr( $coupon->id ) . '"' . $selected_modifier . '>' . esc_html( $coupon->code ) . '</option>';
								}
							}
							?>
						</select>
						<?php
						if ( false !== $coupon_found ) {
							$discount_object = new \EDD_Discount( $coupon_found->ID );
							if ( ! empty( $discount_object->expiration ) && $cur_sale->get_end_date( 'Y-m-d H:i:s' ) > date('Y-m-d H:i:s', strtotime( $discount_object->expiration ) ) ) {
								echo "<p id='swsales_pmpro_discount_code_error' class='sitewide_sales_message sitewide_sales_error'>" . __( "This discount code expires before the Sitewide Sale's end date.", 'sitewide-sales' ) . '</p>';
							} elseif ( ! empty( $discount_object->start ) && $cur_sale->get_start_date( 'Y-m-d H:i:s' ) < date('Y-m-d H:i:s', strtotime( $discount_object->start ) ) ) {
								echo "<p id='swsales_pmpro_discount_code_error' class='sitewide_sales_message sitewide_sales_error'>" . __( "This discount code starts after the Sitewide Sale's start date.", 'sitewide-sales' ) . '</p>';
							}
						}
						?>
						<p>
							<span id="swsales_edd_after_coupon_select">
							<?php
							if ( false !== $coupon_found ) {
								$edit_coupon_url = admin_url( 'edit.php?post_type=download&page=edd-discounts&edd-action=edit_discount&discount='.$coupon_found->ID );
							} else {
								$edit_coupon_url = '#';
							}
							
							?>
								<a target="_blank" class="button button-secondary" id="swsales_edd_edit_coupon" href="<?php echo esc_url( $edit_coupon_url ); ?>"><?php esc_html_e( 'edit discount code', 'sitewide-sales' ); ?></a>
								<?php
								esc_html_e( ' or ', 'sitewide-sales' );
								?>
							</span>
							<button type="button" id="swsales_edd_create_coupon" class="button button-secondary"><?php esc_html_e( 'create a new discount code', 'sitewide-sales' ); ?></button>
							<p class="description"><?php esc_html_e( 'Select the discount code that will be automatically applied for users when they visit your Landing Page.', 'sitewide-sales' ); ?></p>
						</p>
					</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_choose_coupon()

	/**
	 * Saves EDD module fields when saving Sitewide Sale.
	 *
	 * @param int     $post_id of the sitewide sale being edited.
	 * @param WP_Post $post object of the sitewide sale being edited.
	 */
	public static function save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_edd_coupon_id'] ) ) {
			update_post_meta( $post_id, 'swsales_edd_coupon_id', intval( $_POST['swsales_edd_coupon_id'] ) );
		}
	}

	/**
	 * Enqueues /modules/ecommerce/edd/swsales-module-edd-metaboxes.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_edd_metaboxes', plugins_url( 'modules/ecommerce/edd/swsales-module-edd-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_edd_metaboxes' );

			wp_localize_script(
				'swsales_module_edd_metaboxes',
				'swsales_edd_metaboxes',
				array(
					'create_coupon_nonce' => wp_create_nonce( 'swsales_edd_create_coupon' ),
					'admin_url'           => admin_url(),
				)
			);

		}
	} // end enqueue_scripts()

	/**
	 * AJAX callback to create a new coupon for your sale
	 */
	public static function create_coupon_ajax() {
		global $wpdb;
		check_ajax_referer( 'swsales_edd_create_coupon', 'nonce' );

		$sitewide_sale_id = intval( $_REQUEST['swsales_edd_id'] );
		if ( empty( $sitewide_sale_id ) ) {
			echo json_encode(
				array(
					'status' => 'error',
					'error'  => esc_html__(
						'No sitewide sale ID given. Try doing it manually.',
						'sitewide-sales'
					),
				)
			);
			exit;
		}

		/**
		 * Create a coupon programatically
		 */
		global $wpdb;
		while ( empty( $coupon_code ) ) {
			$scramble = md5( AUTH_KEY . current_time( 'timestamp' ) . SECURE_AUTH_KEY );
			$coupon_code = strtoupper( substr( $scramble, 0, 10 ) );			
		}

		if ( ! class_exists( 'EDD_Discount' ) ) {
			return;
		}
		
		$amount        = '50'; // Amount.
		$discount_type = 'percent'; // Type: flat, percent

		$code = new \EDD_Discount();

		$code->name = $coupon_code;
		$code->code = $coupon_code;
		$code->type = $discount_type;
		$code->amount = $amount;
		$code->start = get_gmt_from_date( sanitize_text_field( $_REQUEST['swsales_start'] ), 'Y-m-d H:i:s' );
		$code->expiration = get_gmt_from_date( sanitize_text_field( $_REQUEST['swsales_end'] ), 'Y-m-d H:i:s' );

		$code->save();

		$r = array(
			'status'      => 'success',
			'coupon_id'   => $code->ID,
			'coupon_code' => $coupon_code,
		);
		echo wp_json_encode( $r );
		exit;
	} // end create_discount_code_ajax()

	/**
	 * Returns whether the current page is the landing page
	 * for the passed Sitewide Sale.
	 *
	 * @param boolean               $is_checkout_page current value from filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being checked.
	 * @return boolean
	 */
	public static function is_checkout_page( $is_checkout_page, $sitewide_sale ) {
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $is_checkout_page;
		}
		$edd_checkout_pages = array( edd_get_option( 'purchase_page' ), edd_get_option( 'success_page' ), edd_get_option( 'failure_page' ) );
		return ( ! empty( $edd_checkout_pages ) && is_page( $edd_checkout_pages ) ) ? true : $is_checkout_page;
	}

	/**
	 * Get the coupon for a sitewide sale.
	 * Callback for the swsales_coupon filter.
	 */
	public static function swsales_coupon( $coupon, $sitewide_sale ) {
		if ( $sitewide_sale->get_sale_type() === 'edd' ) {
			$coupon_object = new \EDD_Discount( $sitewide_sale->swsales_edd_coupon_id );			
			if ( ! empty( $coupon_object ) ) {
				$coupon = $coupon_object->code;
			}
		}

		return $coupon;
	}

	public static function automatic_coupon_application() {
		
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();

		if ( null === $active_sitewide_sale || 'edd' !== $active_sitewide_sale->get_sale_type() || ! $active_sitewide_sale->should_apply_automatic_discount() ) {
			return;
		}
		
		if( empty( EDD()->session->get( 'cart_discounts' ) ) ){

			$discount_code_id = intval( $active_sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null ) );

			if ( 0 === $discount_code_id ) {
				return;
			}	

			$code = new \EDD_Discount( $discount_code_id );
	 		if ( !empty( $code ) && empty( $_REQUEST['discount'] ) ) {
 				//Set it in the $_REQUEST and EDD Session as the EDD init runs at priority 0
	 			$_REQUEST['discount'] = $code->code;		 			
	 			EDD()->session->set( 'preset_discount', $code->code );
	 		} 
		}		 		
 		
	}

	/**
	 * Strike out download price when a Sitewide Sale applies a discount.
	 *
	 * @param string $price originally being charged.
	 * @param int $download_id being shown.
	 * @return string
	 */
	public static function strike_prices( $price, $download_id ) {
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( null === $active_sitewide_sale || 'edd' !== $active_sitewide_sale->get_sale_type() || is_admin() ) {
			return $price;
		}
		$coupon_id = $active_sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		if ( null === $coupon_id ) {
			return $price;
		}

		// If it's a variable price, don't strike through.
		if ( edd_has_variable_prices( $download_id ) ) {
			return $price;
		}

		// Check if we are on the landing page
		$landing_page_post_id             = intval( $active_sitewide_sale->get_landing_page_post_id() );
		$on_landing_page                  = ! empty( $landing_page_post_id ) && is_page( $landing_page_post_id );
		$should_apply_discount_on_landing = ( 'none' !== $active_sitewide_sale->get_automatic_discount() );

		// If discount code will be applied or we are on the landing page, strike prices.
		if ( ( $on_landing_page && $should_apply_discount_on_landing ) || $active_sitewide_sale->should_apply_automatic_discount() ) {			
			$discount = new \EDD_Discount( $coupon_id );

			// Check that the discount is a percentage discount and applicable to the download being viewed.
			if (
				$discount->get_type() === 'percent' &&
				! in_array( $download_id, array_map( 'absint', $discount->get_excluded_products() ) ) &&
				(
					empty( array_filter( $discount->get_product_reqs() ) ) ||
					(
						'all' !== $discount->get_product_condition() &&
						in_array( $download_id, array_map( 'absint', $discount->get_product_reqs() ) )
					)
				)
			) {
				// Get download price.
				$download = new \EDD_Download( $download_id );
				$download_price = floatval( $download->get_price() );
				if ( ! empty( $download_price ) ) {
					// Get discounted price.
					$discounted_price = $discount->get_discounted_amount( $download_price );
					// Overwrite link text.
					$discounted_price_formatted = edd_currency_filter( edd_format_amount( $discounted_price ) );
					$price = '<span class="screen-reader-text">' . __( 'Original price', 'sitewide-sales' ) . '</span> <s>' . $price . '</s> <span class="screen-reader-text">' . __( 'sale price', 'sitewide-sales' ) . '</span> ' . $discounted_price_formatted;
				}
			}
		}
		return $price;
	}

	/**
	 * Strike out prices when a coupon code is applied.
	 *
	 * @param  array $args Arguments that EDD will display in purchase link
	 * @return array
	 */
	public static function edd_purchase_link_args_strike_prices( $args ) {
		$original_price = explode( '&nbsp;', $args['text'] )[0];
		$discounted_price = self::strike_prices( $original_price, $args['download_id'] );
		if ( $original_price !== $discounted_price ) {
			$args['text'] = str_replace( $original_price, $discounted_price, $args['text'] );
		}
		return $args;
	}

	/**
	 * Set EDD module checkout conversion title for Sitewide Sale report.
	 *
	 * @param string               $cur_title     set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions_title( $cur_title, $sitewide_sale ) {
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_title;
		}
		$coupon_id = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );

		if ( null === $coupon_id || empty( $coupon_code ) ) {
			return $cur_title;
		}

		return sprintf(
			__( 'Purchases using <a href="%s">%s</a>', 'sitewide-sales' ),
			get_edit_post_link( $coupon_id ),
			$coupon_code->name
		);
	}

	/**
	 * Set EDD checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions( $cur_conversions, $sitewide_sale ) {
		global $wpdb;
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}

		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');
		
		$conversion_count = $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$wpdb->prefix}edd_orders as p
			INNER JOIN {$wpdb->prefix}edd_order_adjustments as eddoa ON p.id = eddoa.object_id
			WHERE p.type = 'sale'
			AND p.status = 'complete'
			AND p.date_completed >= '{$sale_start_date}'
			AND p.date_completed <= '{$sale_end_date}'
			AND upper(eddoa.description) = upper('{$coupon_code->code}')
			AND eddoa.type = 'discount'
		" );

		return strval( $conversion_count );
	}

	/**
	 * Set EDD total revenue for Sitewide Sale report.
	 *
	 * @param string $cur_revenue set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @param bool $format_price whether to format the price.
	 * @return string The sale revenue.
	 */
	public static function sale_revenue( $cur_revenue, $sitewide_sale, $format_price = false ) {
		global $wpdb;
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');
		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );

		$sale_revenue = $wpdb->get_var( "
			SELECT DISTINCT SUM(p.total)
			FROM {$wpdb->prefix}edd_orders as p
			INNER JOIN {$wpdb->prefix}edd_order_adjustments as eddoa ON p.id = eddoa.object_id			
			WHERE p.type = 'sale'
			AND p.status = 'complete' 
			AND p.date_completed >= '{$sale_start_date}' 
			AND p.date_completed <= '{$sale_end_date}' 
			AND eddoa.description = '{$coupon_code->code}' 
			AND eddoa.type = 'discount'
		" );

		return $format_price ?  wp_strip_all_tags( edd_currency_filter( edd_format_amount( $sale_revenue ) ) ) : $sale_revenue;
	}

	/**
	 * Generate data for daily revenue chart.
	 *
	 * @param array                 $daily_revenue_chart_data to be shown in chart
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @return bool
	 */
	public static function daily_revenue_chart_data( $daily_revenue_chart_data, $sitewide_sale ) {
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $daily_revenue_chart_data;
		}
		global $wpdb;
		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );

		$query_data = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DATE_FORMAT(p.date_completed, '%s') as date, SUM(p.total) as value
					FROM {$wpdb->prefix}edd_orders as p
				INNER JOIN {$wpdb->prefix}edd_order_adjustments as eddoa
					ON p.id = eddoa.object_id
				WHERE p.type = 'sale'
					AND p.status = 'complete' 
					AND p.date_completed >= %s
					AND p.date_completed <= %s
					AND upper(eddoa.description) = upper('%s')
					AND eddoa.type = 'discount'					
				GROUP BY date
				ORDER BY date
				",
				'%Y-%m-%d', // To prevent these from being seen as placeholders.
				$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
				$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59',
				$coupon_code->code
			)
		);
		foreach ( $query_data as $daily_revenue_obj ) {
			if ( array_key_exists( $daily_revenue_obj->date, $daily_revenue_chart_data ) ) {
				$daily_revenue_chart_data[$daily_revenue_obj->date] = floatval( $daily_revenue_obj->value );
			}
		}
		
		return $daily_revenue_chart_data;
	}

	public static function swsales_daily_revenue_chart_currency_format( $currency_format, $sitewide_sale ) {
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $currency_format;
		}
		return array(
			'currency_symbol' => edd_currency_symbol(),
			'decimals' => apply_filters( 'edd_sanitize_amount_decimals', 2 ), //Third val would normally be $amount
			'decimal_separator' => edd_get_option('decimal_separator', '.' ),
			'thousands_separator' => edd_get_option('thousands_separator', ',' ),
			'position' => strpos( edd_get_option( 'currency_position' ), 'after' ) !== false ? 'suffix' : 'prefix'
		);
	}

	/**
	 * Set EDD checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function legacy_checkout_conversions( $cur_conversions, $sitewide_sale ) {
		global $wpdb;
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}

		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');

		$conversion_count = $wpdb->get_results( "
			SELECT *
			FROM {$wpdb->prefix}posts as p
			INNER JOIN {$wpdb->prefix}postmeta as eddoa ON p.ID = eddoa.post_id
			WHERE p.post_type = 'edd_payment'
			AND p.post_status = 'publish'
			AND p.post_date >= '{$sale_start_date}'
			AND p.post_date <= '{$sale_end_date}'
			AND eddoa.meta_key = '_edd_payment_meta' 
		" );
		
		$conversion = array();

		if( $conversion_count ){
			foreach( $conversion_count as $con ){

				$payment_data = maybe_unserialize( $con->meta_value );
				
				if( $payment_data['user_info']['discount'] === $coupon_code->code ){
					$cart_total = 0;
					foreach( $payment_data['cart_details'] as $cart ){
						$cart_total = $cart_total + $cart['price'];
					}
					$conversion[] = $cart_total;
				}
			}
		}

		return count( $conversion );
	
	}

	/**
	 * Set EDD total revenue for Sitewide Sale report.
	 *
	 * @param string               $cur_revenue set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function legacy_sale_revenue( $cur_revenue, $sitewide_sale, $format_price = false ) {
		global $wpdb;
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');

		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );

		$sale_revenue = $wpdb->get_results( "
			SELECT *
			FROM {$wpdb->prefix}posts as p
			INNER JOIN {$wpdb->prefix}postmeta as eddoa ON p.ID = eddoa.post_id
			WHERE p.post_type = 'edd_payment'
			AND p.post_status = 'publish'
			AND p.post_date >= '{$sale_start_date}'
			AND p.post_date <= '{$sale_end_date}'
			AND eddoa.meta_key = '_edd_payment_meta' 
		" );

		$cart_total = 0;

		if( $sale_revenue ){
			foreach( $sale_revenue as $con ){

				$payment_data = maybe_unserialize( $con->meta_value );

				if( $payment_data['user_info']['discount'] === $coupon_code->code ){
					
					foreach( $payment_data['cart_details'] as $cart ){
						$cart_total = $cart_total + $cart['price'];
					}
				}
			}
		}

		return $format_price ?  wp_strip_all_tags( edd_currency_filter( edd_format_amount( $cart_total ) ) ) : $cart_total;
	}

	/**
	 * Generate data for daily revenue chart.
	 *
	 * @param array                 $daily_revenue_chart_data to be shown in chart
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @return bool
	 */
	public static function legacy_daily_revenue_chart_data( $daily_revenue_chart_data, $sitewide_sale ) {
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $daily_revenue_chart_data;
		}
		global $wpdb;
		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_edd_coupon_id', null );
		$coupon_code = new \EDD_Discount( $coupon_id );
		
		$start_date = $sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00';
		$end_date = $sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59';
				
		$query_data = $wpdb->get_results(
			"
			SELECT DATE_FORMAT(p.post_date, '%Y-%m-%d') as date, eddoa.meta_value as value
				FROM {$wpdb->prefix}posts as p
			INNER JOIN {$wpdb->prefix}postmeta as eddoa
				ON p.ID = eddoa.post_id
			WHERE p.post_type = 'edd_payment'
				AND p.post_status = 'publish' 
				AND p.post_date >= '$start_date' 
				AND p.post_date <= '$end_date' 
				AND eddoa.meta_key = '_edd_payment_meta' 					
			"
		);

		$daily_revenue = array();

		if( $query_data ){
			foreach( $query_data as $data ){
				$cart_total = 0;
				$payment_data = maybe_unserialize( $data->value );
				
				if( !empty( $payment_data['user_info']['discount'] ) && $payment_data['user_info']['discount'] == $coupon_code->code ){
					foreach( $payment_data['cart_details'] as $cart ){
						$cart_total = $cart_total + $cart['price'];
					}
					if( isset( $daily_revenue[$data->date] ) ){
						$daily_revenue[$data->date] += $cart_total;
					} else {
						$daily_revenue[$data->date] = $cart_total;
					}
				
				}

			}
		}

		foreach ( $daily_revenue as $key => $val  ) {
			if ( array_key_exists( $key, $daily_revenue_chart_data ) ) {
				$daily_revenue_chart_data[$key] = floatval( $val );
			}
		}
		
		return $daily_revenue_chart_data;
	}

	/**
	 * Get other revenue
	 *
	 * @param string $cur_other_revenue The current other revenue.
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sitewide sale being reported on.
	 * @param bool $format_price Whether to format the price.
	 * @return string
	 *
	 * @since 1.4
	 *
	 */
	public static function get_other_revenue ( $cur_other_revenue, $sitewide_sale, $format_price = false ) {
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_other_revenue;
		}

		$total_revenue = self::total_revenue( null, $sitewide_sale, false );
		$sale_revenue  = self::sale_revenue( null, $sitewide_sale, false );
		$other_revenue = (float)$total_revenue - (float)$sale_revenue;

		return $format_price ?  wp_strip_all_tags( edd_currency_filter( edd_format_amount( $other_revenue ) ) ) : $other_revenue;
	}

	/**
	 * Get total revenue
	 *
	 * @param string $cur_total_revenue The current total revenue.
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sitewide sale being reported on.
	 * @param bool $format_price Whether to format the price.
	 *
	 * @since 1.4
	 */
	public static function total_revenue( $cur_total_revenue, $sitewide_sale, $format_price = false ) {
		global $wpdb;
		if ( 'edd' !== $sitewide_sale->get_sale_type() ) {
			return $cur_total_revenue;
		}

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');

		$total_rev = $wpdb->get_var( "
			SELECT DISTINCT SUM(p.total)
			FROM {$wpdb->prefix}edd_orders as p
			INNER JOIN {$wpdb->prefix}edd_order_adjustments as pm ON p.id = pm.object_id
			WHERE p.type = 'sale'
			AND p.status = 'complete'
			AND p.date_completed >= '{$sale_start_date}'
			AND p.date_completed <= '{$sale_end_date}'
		" );

		return $format_price ?  wp_strip_all_tags( edd_currency_filter( edd_format_amount( $total_rev ) ) ) : $total_rev;
	}
}
SWSales_Module_EDD::init();
