<?php

namespace Sitewide_Sales\modules;

use Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Module_WC {

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

		// Bail on additional functionality if WC is not active.
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return;
		}

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'save_metaboxes' ), 10, 2 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Generate coupons from editing sitewide sale.
		add_action( 'wp_ajax_swsales_wc_create_coupon', array( __CLASS__, 'create_coupon_ajax' ) );

		// Custom WC banner rules (hide at checkout or if landing page is 'Shop' page).
		add_filter( 'swsales_is_checkout_page', array( __CLASS__, 'is_checkout_page' ), 10, 2 );
		add_filter( 'swsales_show_banner', array( __CLASS__, 'show_banner' ), 10, 2 );		

		// For the swsales_coupon helper function
		add_filter( 'swsales_coupon', array( __CLASS__, 'swsales_coupon' ), 10, 2 );

		// Automatic coupon application.
		add_filter( 'wp', array( __CLASS__, 'automatic_coupon_application' ) );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'strike_prices' ), 10, 2 );

		// Show products with a coupon applied as "on sale".
		add_filter( 'woocommerce_product_is_on_sale', array( __CLASS__, 'woocommerce_product_is_on_sale'), 10, 2 );

		//Revenue Breakdown specific filter
		add_filter( 'swsales_get_other_revenue', array( __CLASS__, 'get_other_revenue' ), 10, 3 );
		add_filter( 'swsales_get_total_revenue', array( __CLASS__, 'total_revenue' ), 10, 3 );
		add_filter( 'swsales_get_renewal_revenue', array( __CLASS__, 'get_renewal_revenue' ), 10, 3 );

		// WC-specific reports.
		add_filter( 'swsales_checkout_conversions_title', array( __CLASS__, 'checkout_conversions_title' ), 10, 2 );
		add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'checkout_conversions' ), 10, 2 );
		add_filter( 'swsales_get_revenue', array( __CLASS__, 'sale_revenue' ), 10, 3 );
		add_filter( 'swsales_daily_revenue_chart_data', array( __CLASS__, 'swsales_daily_revenue_chart_data' ), 10, 2 );
		add_filter( 'swsales_daily_revenue_chart_currency_format', array( __CLASS__, 'swsales_daily_revenue_chart_currency_format' ), 10, 2 );
	}

	/**
	 * Register WooCommerce module with SWSales
	 *
	 * @param  array $sale_types that are registered in SWSales.
	 * @return array
	 */
	public static function register_sale_type( $sale_types ) {
		$sale_types['wc'] = 'WooCommerce';
		return $sale_types;
	} // end register_sale_type()

	/**
	 * Adds option to choose coupon in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_choose_coupon( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-wc'>
			<?php if ( ! class_exists( 'WooCommerce', false ) ) { ?>
				<th></th>
				<td>
					<div class="sitewide_sales_message sitewide_sales_error">
						<p><?php echo esc_html( 'The WooCommerce plugin is not active.', 'sitewide-sales' ); ?></p>
					</div>
				</td>
				<?php
			} else {
				global $wpdb;

				// Query the database for the coupons and only retrieve ID and post_title (coupon name).
				$coupon_limit = apply_filters( 'swsales_wc_coupon_limit', 5000 );
				$coupons = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'shop_coupon' AND post_status = 'publish' LIMIT %d", $coupon_limit ), OBJECT );

				// Get the current coupon (if set) for the sale.
				$current_coupon = intval( $cur_sale->get_meta_value( 'swsales_wc_coupon_id', null ) );
				?>
					<th><label for="swsales_wc_coupon_id"><?php esc_html_e( 'Coupon', 'sitewide-sales' );?></label></th>
					<td>
						<select class="coupon_select swsales_option" id="swsales_wc_coupon_select" name="swsales_wc_coupon_id">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$coupon_found = false;
							foreach ( $coupons as $coupon ) {
								$selected_modifier = '';
								if ( (int)$coupon->ID === $current_coupon ) {
									$selected_modifier = ' selected="selected"';
									$coupon_found      = $coupon;
								}
								echo '<option value="' . esc_attr( $coupon->ID ) . '"' . $selected_modifier . '>' . esc_html( $coupon->post_title ) . '</option>';
							}
							?>
						</select>
						<?php
						if ( false !== $coupon_found ) {
							$coupon_obj = new \WC_Coupon( $coupon_found->ID );
							if ( null !== $coupon_obj->get_date_expires() && $cur_sale->get_end_date( 'Y-m-d H:i:s' ) > $coupon_obj->get_date_expires()->date_i18n( 'Y-m-d' ) . ' 23:59:00' ) {
								echo "<p id='swsales_wc_coupon_expiry_error' class='sitewide_sales_message sitewide_sales_error'>" . __( "This coupon expires on or before the Sitewide Sale's end date.", 'sitewide-sales' ) . '</p>';
							}
						}
						?>
						<p>
							<span id="swsales_wc_after_coupon_select">
							<?php
							if ( false !== $coupon_found ) {
								$edit_coupon_url = get_edit_post_link( $coupon_found->ID );
							} else {
								$edit_coupon_url = '#';
							}
							?>
								<a target="_blank" class="button button-secondary" id="swsales_wc_edit_coupon" href="<?php echo esc_url( $edit_coupon_url ); ?>"><?php esc_html_e( 'edit coupon', 'sitewide-sales' ); ?></a>
								<?php
								esc_html_e( ' or ', 'sitewide-sales' );
								?>
							</span>
							<button type="button" id="swsales_wc_create_coupon" class="button button-secondary"><?php esc_html_e( 'create a new coupon', 'sitewide-sales' ); ?></button>
							<p class="description"><?php esc_html_e( 'Select the coupon that will be automatically applied for users when they visit your Landing Page.', 'sitewide-sales' ); ?></p>
						</p>
					</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_choose_coupon()

	/**
	 * Saves WC module fields when saving Sitewide Sale.
	 *
	 * @param int     $post_id of the sitewide sale being edited.
	 * @param WP_Post $post object of the sitewide sale being edited.
	 */
	public static function save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_wc_coupon_id'] ) ) {
			update_post_meta( $post_id, 'swsales_wc_coupon_id', intval( $_POST['swsales_wc_coupon_id'] ) );
		}
	}

	/**
	 * Enqueues modules/ecommerce/wc/swsales-module-wc-metaboxes.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_wc_metaboxes', plugins_url( 'modules/ecommerce/wc/swsales-module-wc-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_wc_metaboxes' );

			wp_localize_script(
				'swsales_module_wc_metaboxes',
				'swsales_wc_metaboxes',
				array(
					'create_coupon_nonce' => wp_create_nonce( 'swsales_wc_create_coupon' ),
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
		check_ajax_referer( 'swsales_wc_create_coupon', 'nonce' );

		$sitewide_sale_id = intval( $_REQUEST['swsales_wc_id'] );
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
			if ( get_page_by_title( $coupon_code, OBJECT, 'shop_coupon' ) !== null || is_numeric( $coupon_code ) ) {
				$coupon_code = null;
			}
		}
		$amount        = '50'; // Amount.
		$discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product.

		$coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		);

		$new_coupon_id = wp_insert_post( $coupon );

		// Add meta.
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
		update_post_meta( $new_coupon_id, 'individual_use', 'no' );
		update_post_meta( $new_coupon_id, 'product_ids', '' );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', '' );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

		$r = array(
			'status'      => 'success',
			'coupon_id'   => $new_coupon_id,
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
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $is_checkout_page;
		}
		return ( ! empty( wc_get_page_id( 'cart' ) ) && is_page( wc_get_page_id( 'cart' ) ) ) ? true : $is_checkout_page;
	}

	/**
	 * Returns whether the banner should be shown for the current Sitewide Sale.
	 *
	 * @param boolean               $show_banner current value from filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being checked.
	 * @return boolean
	 */
	public static function show_banner( $show_banner, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $show_banner;
		}

		// If the landing page for sale is the "Shop" page, hide the banner.
		$landing_page_post_id = $sitewide_sale->get_landing_page_post_id();
		if ( ! empty( $landing_page_post_id ) && get_option( 'woocommerce_shop_page_id' ) === $landing_page_post_id && is_shop() ) {
			return false;
		}
		return $show_banner;
	}

	/**
	 * Get the coupon for a sitewide sale.
	 * Callback for the swsales_coupon filter.
	 */
	public static function swsales_coupon( $coupon, $sitewide_sale ) {
		if ( $sitewide_sale->get_sale_type() === 'wc' ) {
			$coupon_object = new \WC_Coupon( $sitewide_sale->swsales_wc_coupon_id );
			if ( ! empty( $coupon_object ) ) {
				$coupon = $coupon_object->get_code();
			}
		}

		return $coupon;
	}

	public static function automatic_coupon_application() {
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( null === $active_sitewide_sale || 'wc' !== $active_sitewide_sale->get_sale_type() || is_admin() || ! $active_sitewide_sale->should_apply_automatic_discount() ) {
			return;
		}

		// Check that we are on the cart page or at checkout.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		// Apply the discount if valid.
		$cart = WC()->cart;
		$coupon = new \WC_Coupon( $active_sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null ) );
		if ( ! $cart->has_discount( $coupon->get_code() ) && $coupon->is_valid() ) {
			$cart->apply_coupon( $coupon->get_code() );
		}
	}

	/**
	 * Strike out prices when a coupon code is applied.
	 *
	 * @param  string     $price   being displayed.
	 * @param  WC_Product $product that price is being generated for.
	 * @return string     new price.
	 */
	public static function strike_prices( $price, $product ) {		
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		
		// If we're previewing a landing page, override the sale.
		if ( ! empty( $_REQUEST['swsales_preview_time_period'] ) && $_REQUEST['swsales_preview_time_period'] === 'sale' && current_user_can( 'administrator' ) ) {
			$queried_object = get_queried_object();
			if ( ! empty( $queried_object ) ) {
				$landing_page_sale = classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $queried_object->ID );
				if ( ! empty( $landing_page_sale) ) {
					$active_sitewide_sale = $landing_page_sale;
				}
			}
		}
		
		// Make sure there is a sale and it's for WC.
		if ( null === $active_sitewide_sale || 'wc' !== $active_sitewide_sale->get_sale_type() || is_admin() ) {
			return $price;
		}
		
		// Get coupon for this sale.
		$coupon_id = $active_sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		if ( null === $coupon_id ) {
			return $price;
		}
		
		// Check if we are on the landing page
		$landing_page_post_id             = intval( $active_sitewide_sale->get_landing_page_post_id() );
		$on_landing_page = false;
		if (
			( ! empty( $_SERVER['REQUEST_URI'] ) && intval( url_to_postid( $_SERVER['REQUEST_URI'] ) ) === $landing_page_post_id ) ||
			( ! empty( $_SERVER['HTTP_REFERER'] ) && intval( url_to_postid( $_SERVER['HTTP_REFERER'] ) ) === $landing_page_post_id )
		) {
			$on_landing_page = true;
		}
		$should_apply_discount_on_landing = ( 'none' !== $active_sitewide_sale->get_automatic_discount() );

		// If discount code is already applied or we are on the landing page and should apply discount...
		if ( 
			( ! empty( WC()->cart ) && WC()->cart->has_discount( wc_get_coupon_code_by_id( $coupon_id ) ) ) ||
			( $on_landing_page && $should_apply_discount_on_landing )||
			$active_sitewide_sale->should_apply_automatic_discount()
		) {
			$coupon = new \WC_Coupon( wc_get_coupon_code_by_id( $coupon_id ) );
			if ( $coupon->is_valid_for_product( $product ) ) {
				// Get pricing for simple products and similar.
				$simple_product_types = array( 'simple', 'variation' );
				if ( $product->is_type( $simple_product_types ) ) {
					$regular_price = get_post_meta( $product->get_id(), '_regular_price', true );
					$discount_amount  = $coupon->get_discount_amount( $regular_price );
					if ( $discount_amount > 0 ) {
						$discount_amount  = min( $regular_price, $discount_amount );
						$discounted_price = max( $regular_price - $discount_amount, 0 );
						// Update price variable so we can return it later.
						$price = '<del aria-hidden="true">' . wc_price( $regular_price ) . '</del> <ins>' . wc_price( $discounted_price ) . '</ins>';
					}
				}

				// Get pricing for variable products.


				if ( $product->is_type( 'variable' ) ) {
					$prices           = $product->get_variation_prices( true );
					$min_price        = current( $prices['price'] );
					$max_price        = end( $prices['price'] );

					$min_discount_amount  = $coupon->get_discount_amount( $min_price );
					$min_discount_amount  = min( $min_price, $min_discount_amount );
					$min_discounted_price = max( $min_price - $min_discount_amount, 0 );

					$max_discount_amount  = $coupon->get_discount_amount( $max_price );
					$max_discount_amount  = min( $max_price, $max_discount_amount );
					$max_discounted_price = max( $max_price - $max_discount_amount, 0 );

					if ( $min_discount_amount > 0 || $max_discount_amount > 0 ) {
						if ( $min_price == $max_price && $min_discounted_price == $max_discounted_price ) {
								// All variations are the same price. Show as a single price with strikethrough.
								$price = '<del aria-hidden="true">' . wc_price( $min_price ) . '</del> <ins>' . wc_price( $min_discounted_price ) . '</ins>';
						} else {
							// Show variations as a range of prices with strikethrough range.
							$regular_range    = wc_format_price_range( $min_price, $max_price );
							$discounted_range = wc_format_price_range( $min_discounted_price, $max_discounted_price );
							$price            = '<del aria-hidden="true">' . $regular_range . '</del> <ins>' . $discounted_range . '</ins>';
						}
					}
				}
			}
		}
		return $price;
	}

	/**
	 * Show the product as a "sale" product in WooCommerce if the coupon code is applied.
	 *
	 * @param  bool $on_sale Whether the product is on sale via discount code.
	 * @param  WC_Product $product The product to that is being generated for.
	 * @return bool Whether the product is on sale via discount code.
	 */
	public static function woocommerce_product_is_on_sale( $on_sale, $product ) {
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( null === $active_sitewide_sale || 'wc' !== $active_sitewide_sale->get_sale_type() || is_admin() ) {
			return $on_sale;
		}
		$coupon_id = $active_sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		if ( null === $coupon_id ) {
			return $on_sale;
		}

		// Check if we are on the landing page
		$landing_page_post_id             = intval( $active_sitewide_sale->get_landing_page_post_id() );
		$on_landing_page = false;
		if (
			( ! empty( $_SERVER['REQUEST_URI'] ) && intval( url_to_postid( $_SERVER['REQUEST_URI'] ) ) === $landing_page_post_id ) ||
			( ! empty( $_SERVER['HTTP_REFERER'] ) && intval( url_to_postid( $_SERVER['HTTP_REFERER'] ) ) === $landing_page_post_id )
		) {
			$on_landing_page = true;
		}
		$should_apply_discount_on_landing = ( 'none' !== $active_sitewide_sale->get_automatic_discount() );

		// If discount code is already applied or we are on the landing page and should apply discount, show the product on sale.
		if ( 
			( ! empty( WC()->cart ) && WC()->cart->has_discount( wc_get_coupon_code_by_id( $coupon_id ) ) ) ||
			( $on_landing_page && $should_apply_discount_on_landing )||
			$active_sitewide_sale->should_apply_automatic_discount()
		) {
			$coupon = new \WC_Coupon( wc_get_coupon_code_by_id( $coupon_id ) );
			if ( $coupon->is_valid_for_product( $product ) ) {
				$on_sale = true;
			}
		}
		return $on_sale;
	}

	/**
	 * Set WC module checkout conversion title for Sitewide Sale report.
	 *
	 * @param string               $cur_title     set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions_title( $cur_title, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_title;
		}
		$coupon_id = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

		if ( null === $coupon_id || empty( $coupon_code ) ) {
			return $cur_title;
		}

		return sprintf(
			__( 'Purchases using <a href="%s">%s</a>', 'sitewide-sales' ),
			get_edit_post_link( $coupon_id ),
			$coupon_code
		);
	}

	/**
	 * Set WC module checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions( $cur_conversions, $sitewide_sale ) {
		global $wpdb;
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}

		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');
		$conversion_count = $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$wpdb->prefix}posts as p
			INNER JOIN {$wpdb->prefix}woocommerce_order_items as wcoi ON p.ID = wcoi.order_id
			WHERE p.post_type = 'shop_order'
			AND p.post_status IN ('wc-processing','wc-completed')
			AND p.post_date >= '{$sale_start_date}'
			AND p.post_date <= '{$sale_end_date}'
			AND upper(wcoi.order_item_name) = upper('{$coupon_code}')
			AND wcoi.order_item_type = 'coupon'
		" );

		return strval( $conversion_count );
	}

	/**
	 * Set WC module total revenue for Sitewide Sale report.
	 *
	 * @param string $cur_revenue set by filter. N/A
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string revenue for the period of the Sitewide Sale.
	 *
	 * @since 1.4
	 */
	public static function sale_revenue( $cur_revenue, $sitewide_sale, $format_price = false ) {
		global $wpdb;
		// Bail if not a WC sale.
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');
		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );
		$sale_revenue = $wpdb->get_var( "
			SELECT DISTINCT SUM(pm.meta_value)
			FROM {$wpdb->prefix}posts as p
			INNER JOIN {$wpdb->prefix}woocommerce_order_items as wcoi ON p.ID = wcoi.order_id
			INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
			WHERE p.post_type = 'shop_order'
			AND p.post_status IN ('wc-processing','wc-completed')
			AND p.post_date >= '{$sale_start_date}'
			AND p.post_date <= '{$sale_end_date}'
			AND upper(wcoi.order_item_name) = upper('{$coupon_code}')
			AND wcoi.order_item_type = 'coupon'
			AND pm.meta_key = '_order_total'
		" );
		return $format_price ? wp_strip_all_tags( wc_price( $sale_revenue ) ) : $sale_revenue;
	}

	/**
	 * Generate data for daily revenue chart.
	 *
	 * @param array                 $daily_revenue_chart_data to be shown in chart
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @return bool
	 */
	public static function swsales_daily_revenue_chart_data( $daily_revenue_chart_data, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $daily_revenue_chart_data;
		}
		global $wpdb;
		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );
		$query_data = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DATE_FORMAT(p.post_date, '%s') as date, SUM(pm.meta_value) as value
					FROM {$wpdb->prefix}posts as p
				INNER JOIN {$wpdb->prefix}woocommerce_order_items as wcoi
					ON p.ID = wcoi.order_id
				INNER JOIN {$wpdb->prefix}postmeta as pm
					ON p.ID = pm.post_id
				WHERE p.post_type = 'shop_order'
					AND p.post_status IN ('wc-processing','wc-completed')
					AND p.post_date >= %s
					AND p.post_date <= %s
					AND upper(wcoi.order_item_name) = upper('%s')
					AND wcoi.order_item_type = 'coupon'
					AND pm.meta_key = '_order_total'
				GROUP BY date
				ORDER BY date
				",
				'%Y-%m-%d', // To prevent these from being seen as placeholders.
				$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
				$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59',
				$coupon_code
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
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $currency_format;
		}
		return array(
			'currency_symbol' => get_woocommerce_currency_symbol(),
			'decimals' => wc_get_price_decimals(),
			'decimal_separator' => wc_get_price_decimal_separator(),
			'thousands_separator' => wc_get_price_thousand_separator(),
			'position' => strpos( get_option( 'woocommerce_currency_pos' ), 'right' ) !== false ? 'suffix' : 'prefix'
		);
	}

	/**
	 * Get other revenue
	 *
	 * @param string $cur_revenue set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 *
	 * @since 1.4
	 *
	 */
	public static function get_other_revenue ( $cur_revenue, $sitewide_sale, $format_price = false) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		$total_revenue = self::total_revenue( null, $sitewide_sale, false );
		$sale_revenue  = self::sale_revenue( null, $sitewide_sale, false );
		$renewal_revenue = self::get_renewal_revenue( null, $sitewide_sale, false );
		$other_revenue = (float)$total_revenue - (float)$sale_revenue - (float)$renewal_revenue;

		return $format_price ? wp_strip_all_tags( wc_price( $other_revenue ) ) : $other_revenue;
	}

	/**
	 * get WC Renewals
	 *
	 * @param string $cur_revenue set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string
	 *
	 * @since 1.4
	 */
	public static function get_renewal_revenue( $cur_revenue, $sitewide_sale, $format_price = false) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		global $wpdb;
		$renewal_revenue = 0;
		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');
		if ( class_exists( 'WC_Subscriptions' ) ) {
			// WC Subscrtions enabled, see if there are any renewals in the period.
			$renewal_revenue = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(order_total_meta.meta_value)
						FROM {$wpdb->postmeta} as order_total_meta
						RIGHT JOIN
						(
							SELECT DISTINCT wcorder.ID
							FROM {$wpdb->posts} AS wcorder
							INNER JOIN {$wpdb->postmeta} AS meta__subscription_renewal
								ON (
									wcorder.id = meta__subscription_renewal.post_id
									AND
									meta__subscription_renewal.meta_key = '_subscription_renewal'
								)
							WHERE wcorder.post_type IN ( 'shop_order' )
								AND wcorder.post_status IN ( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded' )
								AND wcorder.post_date >= '%s'
								AND wcorder.post_date < '%s'
						) AS orders ON orders.ID = order_total_meta.post_id
						WHERE order_total_meta.meta_key = '_order_total'",
					$sale_start_date,
					$sale_end_date
				)
			);
		}
		return $format_price ? wp_strip_all_tags( wc_price( $renewal_revenue ) ) : $renewal_revenue;
	}

	/**
	 * Get total revenue
	 *
	 * @param string $cur_revenue set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string
	 *
	 * @since 1.4
	 */
	public static function total_revenue( $cur_revenue, $sitewide_sale, $format_price = false ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}
		global $wpdb;
		$sale_start_date = $sitewide_sale->get_start_date('Y-m-d H:i:s');
		$sale_end_date = $sitewide_sale->get_end_date('Y-m-d H:i:s');
		$total_rev = $wpdb->get_var( "
			SELECT DISTINCT SUM(pm.meta_value)
			FROM {$wpdb->prefix}posts as p
			INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
			WHERE p.post_type = 'shop_order'
			AND p.post_status IN ('wc-processing','wc-completed')
			AND p.post_date >= '{$sale_start_date}'
			AND p.post_date <= '{$sale_end_date}'
			AND pm.meta_key = '_order_total'
		" );

		return $format_price ? wp_strip_all_tags( wc_price( $total_rev ) ) : $total_rev;
	}

}
SWSales_Module_WC::init();
