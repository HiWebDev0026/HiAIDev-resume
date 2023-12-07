<?php

namespace Sitewide_Sales\modules;

use Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Module_PMPro {

	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/modules
	 */
	public static function init() {
		// Register sale type.
		add_filter( 'swsales_sale_types', array( __CLASS__, 'register_sale_type' ) );

		// Add fields to Edit Sitewide Sale page.
		add_action( 'swsales_after_choose_sale_type', array( __CLASS__, 'add_choose_discount_code' ) );
		add_action( 'swsales_after_choose_sale_type', array( __CLASS__, 'add_hide_sale_by_level' ) );
		add_action( 'swsales_after_choose_landing_page', array( __CLASS__, 'add_set_landing_page_default_level' ) );

		// Migration functionality from PMPro SWS
		add_action( 'admin_init', array( __CLASS__, 'migrate_from_pmprosws' ) );
		add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) );
		add_action( 'swsales_about_text_bottom', array( __CLASS__, 'swsales_about_text_bottom' ) );

		// Bail on additional functionality if PMPro is not active.
		if ( ! defined( 'PMPRO_VERSION' ) ) {
			return;
		}

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'save_metaboxes' ), 10, 2 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// AJAX to create a discount code.
		add_action( 'wp_ajax_swsales_pmpro_create_discount_code', array( __CLASS__, 'create_discount_code_ajax' ) );

		// For the swsales_hide helper function
		add_filter( 'swsales_hide', array( __CLASS__, 'swsales_hide' ), 10, 2 );

		// For the swsales_coupon helper function
		add_filter( 'swsales_coupon', array( __CLASS__, 'swsales_coupon' ), 10, 2 );

		// Default level for sale page.
		add_action( 'wp', array( __CLASS__, 'load_pmpro_preheader' ), 0 ); // Priority 0 so that the discount code applies.

		// Custom PMPro banner rules (hide for levels and hide at checkout).
		add_filter( 'swsales_is_checkout_page', array( __CLASS__, 'is_checkout_page' ), 10, 2 );

		// PMPro automatic discount application.
		add_action( 'init', array( __CLASS__, 'automatic_discount_application' ) );

		// Hide discount code fields on SWSales landing page.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ) );		

		// PMPro-specific reports.
		add_filter( 'swsales_checkout_conversions_title', array( __CLASS__, 'checkout_conversions_title' ), 10, 2 );
		add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'checkout_conversions' ), 10, 3 );
		add_filter( 'swsales_get_revenue', array( __CLASS__, 'sale_revenue' ), 10, 3 );
		add_filter( 'swsales_get_other_revenue', array( __CLASS__, 'get_other_revenue' ), 10, 3 );
		add_filter( 'swsales_get_total_revenue', array( __CLASS__, 'total_revenue' ), 10, 3 );
		add_filter( 'swsales_get_renewal_revenue', array( __CLASS__, 'get_renewal_revenue' ), 10, 3 );

		add_filter( 'swsales_daily_revenue_chart_data', array( __CLASS__, 'swsales_daily_revenue_chart_data' ), 10, 4 );
		add_filter( 'swsales_daily_revenue_chart_currency_format', array( __CLASS__, 'swsales_daily_revenue_chart_currency_format' ), 10, 2 );
	}


	/**
	 * Register PMPro module with SWSales
	 *
	 * @param  array $sale_types that are registered in SWSales.
	 * @return array
	 */
	public static function register_sale_type( $sale_types ) {
		$sale_types['pmpro'] = 'Paid Memberships Pro';
		return $sale_types;
	} // end register_sale_type()

	/**
	 * Adds option to choose discount code in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_choose_discount_code( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td>
					<div class="sitewide_sales_message sitewide_sales_error">
						<p><?php echo esc_html( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></p>
					</div>
				</td>
				<?php
			} else {
				global $wpdb;

				// Query the database for the discount codes.
				$codes = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_discount_codes", OBJECT );

				// Get the discount code (if set) for the sale.
				$current_discount = $cur_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null );
				?>
					<th><label for="swsales_pmpro_discount_code_id"><?php esc_html_e( 'Discount Code', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="discount_code_select swsales_option" id="swsales_pmpro_discount_code_select" name="swsales_pmpro_discount_code_id">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$code_found = false;
							foreach ( $codes as $code ) {
								$selected_modifier = '';
								if ( $code->id === $current_discount ) {
									$selected_modifier = ' selected="selected"';
									$code_found        = $code;
								}
								echo '<option value="' . esc_attr( $code->id ) . '"' . esc_html( $selected_modifier ) . '>' . esc_html( $code->code ) . '</option>';
							}
							?>
						</select>
						<?php
						if ( false !== $code_found ) {
							if ( $cur_sale->get_end_date( 'Y-m-d' ) > $code_found->expires ) {
								echo "<p id='swsales_pmpro_discount_code_error' class='sitewide_sales_message sitewide_sales_error'>" . __( "This discount code expires before the Sitewide Sale's end date.", 'sitewide-sales' ) . '</p>';
							} elseif ( $cur_sale->get_start_date( 'Y-m-d' ) < $code_found->starts ) {
								echo "<p id='swsales_pmpro_discount_code_error' class='sitewide_sales_message sitewide_sales_error'>" . __( "This discount code starts after the Sitewide Sale's start date.", 'sitewide-sales' ) . '</p>';
							}
						}
						?>
						<p>
							<span id="swsales_pmpro_after_discount_code_select">
							<?php
							if ( false !== $code_found ) {
								$edit_code_url = admin_url( 'admin.php?page=pmpro-discountcodes&edit=' . $current_discount );
							} else {
								$edit_code_url = '#';
							}
							?>
								<a target="_blank" class="button button-secondary" id="swsales_pmpro_edit_discount_code" href="<?php echo esc_url( $edit_code_url ); ?>"><?php esc_html_e( 'edit code', 'sitewide-sales' ); ?></a>
								<?php
								esc_html_e( ' or ', 'sitewide-sales' );
								?>
							</span>
							<button type="button" id="swsales_pmpro_create_discount_code" class="button button-secondary"><?php esc_html_e( 'create a new discount code', 'sitewide-sales' ); ?></button>
							<p class="description"><?php esc_html_e( 'Select the code that will be automatically applied for users that complete an applicable membership checkout after visiting your Landing Page.', 'sitewide-sales' ); ?></p>
						</p>
					</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_choose_discount_code()

	/**
	 * Adds option to hide sale for users who have certain levels
	 * in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_hide_sale_by_level( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td>
					<div class="sitewide_sales_message sitewide_sales_error">
						<p><?php echo esc_html( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></p>
					</div>
				</td>
				<?php
			} else {
				?>
				<th scope="row" valign="top"><label><?php esc_html_e( 'Hide Sale by Level', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="hidden" name="swsales_pmpro_hide_for_levels_exists" value="1" />
						<select multiple class="swsales_option" id="swsales_pmpro_hide_levels_select" name="swsales_pmpro_hide_for_levels[]" style="width:12em">
						<?php
							// Get all levels in PMPro settings.
							$all_levels = pmpro_getAllLevels( true, true );
							$all_levels = pmpro_sort_levels_by_order( $all_levels );

							// Get the meta value for levels this banner should be hidden for.
							$hide_for_levels = json_decode( $cur_sale->get_meta_value( 'swsales_pmpro_hide_for_levels', '' ) );

							// If the hidden levels is an empty string, convert to an array.
							$hide_for_levels = empty( $hide_for_levels ) ? array() : $hide_for_levels;

							// Loop through and display all level options.
							foreach ( $all_levels as $level ) {
								$selected_modifier = in_array( $level->id, $hide_for_levels ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $level->id ) . '"' . $selected_modifier . '>' . esc_html( $level->name ) . '</option>';
							}
						?>
						</select>
						<p class="description"><?php esc_html_e( 'This setting will completely hide the sale from users with the selected levels (including the banner and discount logic).', 'sitewide-sales' ); ?></p>
					</td>
					<?php
			}
			?>
		</tr>
		<?php
	}

	/**
	 * Adds option to choose the default level for checkout on SWSale
	 * landing page in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_set_landing_page_default_level( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td>
					<div class="sitewide_sales_message sitewide_sales_error">
						<p><?php echo esc_html( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></p>
					</div>
				</td>
				<?php
			} else {
				?>
				<th><label for="swsales_pmpro_landing_page_default_level"><?php esc_html_e( 'Checkout Level', 'sitewide-sales' ); ?></label></th>
				<td>
					<select id="swsales_pmpro_landing_page_default_level" name="swsales_pmpro_landing_page_default_level">
					<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
					<?php
						$all_levels = pmpro_getAllLevels( true, true );
						$all_levels = pmpro_sort_levels_by_order( $all_levels );
						$default_level = $cur_sale->get_meta_value( 'swsales_pmpro_landing_page_default_level', null );
					foreach ( $all_levels as $level ) {
						?>
						<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $default_level, $level->id ); ?>><?php echo esc_textarea( $level->name ); ?></option>
						<?php
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Using the [pmpro_checkout] shortcode on your Landing Page will display a checkout form for this level.', 'sitewide-sales' ); ?></p>
				</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_set_landing_page_default_level

	/**
	 * Show notice to migrate from PMPro SWS.
	 */
	static function migration_notice() {
		global $wpdb, $pagenow;
		static $swsales_migration_notice_shown;

		// Did we already show the migration notice?
		if ( ! empty( $swsales_migration_notice_shown ) ) {
			return;
		}
		$swsales_migration_notice_shown = true;

		// Is this a SWSales page?
		if ( empty( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] !== 'sitewide_sale' ) {
			return;
		}

		// Is this the "Add New Sitewide Sale" page?
		if ( 'post-new.php' === $pagenow ) {
			return;
		}

		if ( ! empty( $_REQUEST['swsales_pmpro_migrated_sales'] ) ) {
			?>
			<div class="notice notice-success notice-large inline">
					<p><?php esc_html_e( sprintf( _n( 'Successfully migrated %d Sitewide Sale from the Sitewide Sales Add On for Paid Memberships Pro.', 'Successfully migrated %d Sitewide Sales from the Sitewide Sales Add On for Paid Memberships Pro.', intval($_REQUEST['swsales_pmpro_migrated_sales']), 'sitewide-sales' ), $_REQUEST['swsales_pmpro_migrated_sales'] ) ); ?></p>
				</div>
			<?php
		}

		// Was this notice already dismissed?
		$swsales_migration_notice_dismissed = get_option( 'swsales_pmpro_migration_notice_dismissed', 0 );
		if ( $swsales_migration_notice_dismissed ) {
			return;
		} elseif ( ! empty($_REQUEST['swsales_pmpro_migration_notice_dismissed'] ) && current_user_can( 'manage_options' ) ) {
			update_option('swswsales_pmpro_migration_notice_dismissed_nag_paused', 1, 'no');
			return;
		}

		// Are there PMPro SWS to migrate
		$sql = "SELECT count(*)
			FROM $wpdb->posts
			WHERE post_type = 'pmpro_sitewide_sale'
		";
		if ( empty( $wpdb->get_var( $sql ) ) ) {
			return;
		}

		// Okay, show migration notice. (Note: Same text is on the about page below.)
		?>
		<div class="notice notice-warning notice-large inline">
				<h3><?php esc_html_e( 'Migrate Your Previous Sales Data', 'sitewide-sales' ); ?></h3>
				<p><?php esc_html_e( 'We have detected data from the Sitewide Sales Add On for Paid Memberships Pro. You can migrate this data into the new Sitewide Sales plugin and maintain access to previous sales, settings, and reports. The database migration process will attempt to run in a single process, so please be patient.', 'sitewide-sales' ); ?>
				</p>
				<p class="submit">
					<a href="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'swsales_pmpro_migrate', 'swsales_pmpro_migrate' ); ?>" class="button-primary">
						<?php esc_html_e( 'Migrate PMPro Sitewide Sales Data', 'sitewide-sales' ); ?>
					</a>
					<a href="<?php echo add_query_arg('swsales_pmpro_migration_notice_dismissed', '1', $_SERVER['REQUEST_URI']);?>" class="button-secondary">
						<?php esc_html_e( 'Dismiss This Notice', 'sitewide-sales' ); ?>
					</a>
				</p>
			</div>
		<?php
	} // end migration_notice()

	/**
	 * Sitewide Sales About Page text about migrating.
	 */
	public static function swsales_about_text_bottom() {
		global $wpdb;

		// Are there PMPro SWS to migrate
		$sql = "SELECT count(*)
			FROM $wpdb->posts
			WHERE post_type = 'pmpro_sitewide_sale'
		";
		
		// Show the migration info. (Note: Same text is in a notice above.)
		if ( ! empty( $wpdb->get_var( $sql ) ) && current_user_can( 'manage_options' ) ) { ?>
			<h3><?php esc_html_e( 'Migrate Your Previous Sales Data', 'sitewide-sales' ); ?></h3>
			<p><?php esc_html_e( 'We have detected data from the Sitewide Sales Add On for Paid Memberships Pro. You can migrate this data into the new Sitewide Sales plugin and maintain access to previous sales, settings, and reports. The database migration process will attempt to run in a single process, so please be patient.', 'sitewide-sales' ); ?>
			</p>
			<p>
				<a href="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'swsales_pmpro_migrate', 'swsales_pmpro_migrate' ); ?>">
					<?php esc_html_e( 'Migrate PMPro Sitewide Sales Data &raquo;', 'sitewide-sales' ); ?>
				</a>
			</p>
		<?php }
	}

	/**
	 * Migration script to move data from PMPro Sitewide Sales
	 * to this standalone Sitewide Sales plugin.
	 */
	public static function migrate_from_pmprosws() {
		global $wpdb, $pagenow;

		// Is this a SWSales page?
		if ( empty( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] !== 'sitewide_sale' ) {
			return;
		}

		// Is this the "Add New Sitewide Sale" page?
		if ( 'post-new.php' === $pagenow ) {
			return;
		}

		// Is URL param set to migrate PMPro data?
		if ( empty( $_REQUEST['swsales_pmpro_migrate'] ) ) {
			return;
		}
		check_admin_referer( 'swsales_pmpro_migrate', 'swsales_pmpro_migrate' );

		// Perform migration from PMPro SWS.
		$pmpro_sws_sale_ids = $wpdb->get_results( "SELECT id
			FROM $wpdb->posts
			WHERE post_type = 'pmpro_sitewide_sale'
		" );
		if ( empty( $pmpro_sws_sale_ids ) ) {
			return;
		}
		$metadata_migrations = array(
			'pmpro_sws_landing_page_post_id'                => 'swsales_landing_page_post_id',
			'pmpro_sws_landing_page_template'               => 'swsales_landing_page_template',
			'pmpro_sws_pre_sale_content'                    => 'swsales_pre_sale_content',
			'pmpro_sws_sale_content'                        => 'swsales_sale_content',
			'pmpro_sws_post_sale_content'                   => 'swsales_post_sale_content',
			'pmpro_sws_use_banner'                          => 'swsales_use_banner',
			'pmpro_sws_banner_template'                     => 'swsales_banner_template',
			'pmpro_sws_banner_title'                        => 'swsales_banner_title',
			'pmpro_sws_link_text'                           => 'swsales_link_text',
			'pmpro_sws_css_option'                          => 'swsales_css_option',
			'pmpro_sws_hide_on_checkout'                    => 'swsales_hide_on_checkout',
			'pmpro_sws_discount_code_id'                    => 'swsales_pmpro_discount_code_id',
			'pmpro_sws_landing_page_default_level_id' => 'swsales_pmpro_landing_page_default_level',
			'pmpro_sws_hide_for_levels'                     => 'swsales_pmpro_hide_for_levels',
		);
		foreach ( $pmpro_sws_sale_ids as $pmpro_sws_sale ) {
			$pmpro_sws_sale_id = $pmpro_sws_sale->id;

			// Migrate post meta.
			$post_meta = get_post_meta( $pmpro_sws_sale_id );
			foreach ( $post_meta as $key => $value_obj ) {
				$value = $value_obj[0];
				switch( $key ) {
					case 'pmpro_sws_start_date':
						$is_start_date = true;
					case 'pmpro_sws_end_date':
						$time_period = isset( $is_start_date ) ? 'start' : 'end';
						unset( $is_start_date );
						$date = strtotime( $value );
						update_post_meta( $pmpro_sws_sale_id, 'swsales_' . $time_period . '_day', date("d", $date) );
						update_post_meta( $pmpro_sws_sale_id, 'swsales_' . $time_period . '_month', date("n", $date) );
						update_post_meta( $pmpro_sws_sale_id, 'swsales_' . $time_period . '_year', date("Y", $date) );
						delete_post_meta( $pmpro_sws_sale_id, $key );
						break;
					case 'pmpro_sws_hide_for_levels':
						$value = json_encode( unserialize( $value ) );
					default:
						if ( array_key_exists( $key, $metadata_migrations ) ) {
							update_post_meta( $pmpro_sws_sale_id, $metadata_migrations[ $key ], $value );
							delete_post_meta( $pmpro_sws_sale_id, $key );
						}
				}
				
				clean_post_cache( $pmpro_sws_sale_id );
			}

			// Deleted deprecated post meta.
			delete_post_meta( $pmpro_sws_sale_id, 'pmpro_sws_upsell_enabled' );
			delete_post_meta( $pmpro_sws_sale_id, 'pmpro_sws_upsell_levels' );
			delete_post_meta( $pmpro_sws_sale_id, 'pmpro_sws_upsell_text' );

			// Add new post metadata
			update_post_meta( $pmpro_sws_sale_id, 'swsales_sale_type', 'pmpro');
			update_post_meta( $pmpro_sws_sale_id, 'swsales_automatic_discount', 'none' );

			// Migrate report data.
			$reports = get_option( 'pmpro_sws_' . $pmpro_sws_sale_id . '_tracking', false );
			if ( ! empty( $reports ) ) {
				update_post_meta( $pmpro_sws_sale_id, 'swsales_banner_impressions', $reports['banner_impressions'] );
				update_post_meta( $pmpro_sws_sale_id, 'swsales_landing_page_visits', $reports['landing_page_visits'] );
				delete_option( 'pmpro_sws_' . $pmpro_sws_sale_id . '_tracking' );
			}

			// Update shortcodes in landing page post content and landing page template.
			$landing_page_post_id = get_post_meta( $pmpro_sws_sale_id, 'swsales_landing_page_post_id', true );
			if ( ! empty( $landing_page_post_id ) ) {
				$wpdb->get_results( "UPDATE $wpdb->posts
					SET post_content = REPLACE(post_content, '[pmpro_sws', '[sitewide_sales')
					WHERE ID = $landing_page_post_id
				" );
				update_post_meta( $landing_page_post_id, '_wp_page_template', 'swsales-page-template.php' );
			}

			// Change post type.
			$wpdb->get_results( "UPDATE $wpdb->posts
				SET post_type = 'sitewide_sale'
				WHERE ID = $pmpro_sws_sale_id
			" );

		}
		// After all CPTs are converted, clean up and deactivate PMPro SWS.
		delete_option( 'pmpro_sitewide_sales' );
		deactivate_plugins( '/pmpro-sitewide-sales/pmpro-sitewide-sales.php' );
		deactivate_plugins( '/pmpro-sitewide-sales-master/pmpro-sitewide-sales.php' );
		deactivate_plugins( '/pmpro-sitewide-sales-dev/pmpro-sitewide-sales.php' );
		
		wp_redirect( admin_url( '/edit.php?post_type=sitewide_sale&swsales_pmpro_migrated_sales=' . count( $pmpro_sws_sale_ids ) ) );
		exit;
	}

	/**
	 * Saves PMPro module fields when saving Sitewide Sale.
	 *
	 * @param int     $post_id of the sitewide sale being edited.
	 * @param WP_Post $post object of the sitewide sale being edited.
	 */
	public static function save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_pmpro_discount_code_id'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_discount_code_id', intval( $_POST['swsales_pmpro_discount_code_id'] ) );
		}
		if ( isset( $_POST['swsales_pmpro_landing_page_default_level'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_landing_page_default_level', intval( $_POST['swsales_pmpro_landing_page_default_level'] ) );
		}

		if ( ! empty( $_POST['swsales_pmpro_hide_for_levels'] ) && is_array( $_POST['swsales_pmpro_hide_for_levels'] ) ) {
			$swsales_pmpro_hide_for_levels = array_map( 'intval', $_POST['swsales_pmpro_hide_for_levels'] );
			update_post_meta( $post_id, 'swsales_pmpro_hide_for_levels', wp_json_encode( $swsales_pmpro_hide_for_levels ) );
		} else {
			update_post_meta( $post_id, 'swsales_pmpro_hide_for_levels', wp_json_encode( array() ) );
		}
	}

	/**
	 * Enqueues /modules/ecommerce/pmpro/swsales-module-pmpro-metaboxes.js
	 */
	public static function admin_enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_pmpro_metaboxes', plugins_url( 'modules/ecommerce/pmpro/swsales-module-pmpro-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_pmpro_metaboxes' );

			wp_localize_script(
				'swsales_module_pmpro_metaboxes',
				'swsales_pmpro_metaboxes',
				array(
					'create_discount_code_nonce' => wp_create_nonce( 'swsales_pmpro_create_discount_code' ),
					'admin_url'                  => admin_url(),
				)
			);

		}
	} // end admin_enqueue_scripts()

	/**
	 * Whether the current sitewide sale should be hidden.
	 * Callback for the swsales_hide filter.
	 */
	public static function swsales_hide( $hide_sale, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
 			return $hide_sale;
 		}

		// Get the meta value for levels this sale should be hidden for.
		$hide_for_levels = json_decode( $sitewide_sale->get_meta_value( 'swsales_pmpro_hide_for_levels', '' ) );

		// Return if there is no data for hiding sale by level.
		if ( empty( $hide_for_levels ) ) {
			return $hide_sale;
		}

		// If this sale is hidden by level, check if the current user should see it.
		if ( pmpro_hasMembershipLevel( $hide_for_levels ) ) {
			$hide_sale = true;
		}

		return $hide_sale;
	}

	/**
	 * Get the coupon for a sitewide sale.
	 * Callback for the swsales_coupon filter.
	 */
	public static function swsales_coupon( $coupon, $sitewide_sale ) {
		global $wpdb;
		if ( $sitewide_sale->get_sale_type() === 'pmpro' ) {
			$discount_code_id = $sitewide_sale->swsales_pmpro_discount_code_id;
			if ( ! empty( $discount_code_id ) ) {
				$coupon = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );
			}
		}
		return $coupon;
	}

	/**
	 * AJAX callback to create a new discount code for your sale
	 */
	public static function create_discount_code_ajax() {
		global $wpdb;
		check_ajax_referer( 'swsales_pmpro_create_discount_code', 'nonce' );
		if ( ! function_exists( 'pmpro_getDiscountCode' ) ) {
			exit;
		}
		$sitewide_sale_id = intval( $_REQUEST['swsales_pmpro_id'] );
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
		$wpdb->insert(
			$wpdb->pmpro_discount_codes,
			array(
				'id'      => 0,
				'code'    => pmpro_getDiscountCode(),
				'starts'  => sanitize_text_field( $_REQUEST['swsales_start'] ),
				'expires' => sanitize_text_field( $_REQUEST['swsales_end'] ),
				'uses'    => 0,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
			)
		);
		if ( ! empty( $wpdb->last_error ) ) {
			$r = array(
				'status' => 'error',
				'error'  => esc_html__( 'Error inserting discount code. Try doing it manually.', 'sitewide-sales' ),
			);
		} else {
			$discount_code = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $wpdb->insert_id ) . "' LIMIT 1" );
			$r             = array(
				'status' => 'success',
				'code'   => $discount_code,
			);
		}
		echo json_encode( $r );
		exit;
	} // end create_discount_code_ajax()

	/**
	 * Get the default level to use on a landing page
	 *
	 * @param int $post_id Post ID of the landing page.
	 */
	public static function get_default_level( $post_id = null ) {
		global $post, $wpdb;

		// Guess.
		$all_levels = pmpro_getAllLevels( true, true );
		if ( ! empty( $all_levels ) ) {
			$keys     = array_keys( $all_levels );
			$level_id = $keys[0];
		} else {
			return false;
		}
		// Default post_id.
		if ( empty( $post_id ) ) {
			$post_id = $post->ID;
		}
		// Must have a post_id.
		if ( empty( $post_id ) ) {
			return $level_id;
		}
		
		// Get sale for this $post_id.
		$sitewide_sale = classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $post_id );
		if ( null !== $sitewide_sale ) {
			// Check for setting.
			$default_level_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_landing_page_default_level' );
			// No default set? get the discount code for this sale.
			if ( ! empty( $default_level_id ) ) {
				// Use the setting.
				$level_id = $default_level_id;
			} else {
				// Check for discount code.
				$discount_code_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id' );
				// Get first level that uses this code.
				if ( ! empty( $discount_code_id ) ) {
					$first_code_level_id = $wpdb->get_var( "SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . esc_sql( $discount_code_id ) . "' ORDER BY level_id LIMIT 1" );
					if ( ! empty( $first_code_level_id ) ) {
						$level_id = $first_code_level_id;
					}
				}
			}
		}
		return $level_id;
	}

	/**
	 * Load the checkout and levels preheaders on the landing page.
	 */
	public static function load_pmpro_preheader() {
		global $wpdb;
		// Make sure PMPro is loaded.
		if ( ! defined( 'PMPRO_DIR' ) ) {
			return;
		}
		// Don't do this in the dashboard.
		if ( is_admin() ) {
			return;
		}
		// Check if this is the landing page.
		$queried_object = get_queried_object();
		if ( empty( $queried_object ) || empty( $queried_object->ID ) || null === classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $queried_object->ID ) ) {
			return;
		}

		// Choose a default level if none specified.
		if ( empty( $_REQUEST['level'] ) ) {
			$_REQUEST['level'] = self::get_default_level( $queried_object->ID );
		}
		
		// Set the discount code if none specified.		
		if ( empty( $_REQUEST['discount_code'] ) ) {
			$sitewide_sale = classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $queried_object->ID );
			if ( $sitewide_sale->is_running() ) {
				$discount_code_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id' );			
				$discount_code    = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );

				/**
				 * Filters the discount code that is automatically applied on a Sitewide Sale's landing page.
				 *
				 * Warning: If the discount code applied after this filter is not the same as the discount code for
				 * the current Sitewide Sale associated with this landing page, the revenue from this checkout will
				 * not be counted in the sale's revenue report.
				 *
				 * @param string $discount_code being applied.
				 * @param SWSales_Sitewide_Sale for the current landing page.
				 */
				$discount_code = apply_filters( 'swsales_pmpro_landing_page_default_discount_code', $discount_code, $sitewide_sale );
				if ( pmpro_checkDiscountCode( $discount_code, $_REQUEST['level'] ) ) {
					$_REQUEST['discount_code'] = apply_filters( 'swsales_pmpro_landing_page_default_discount_code', $discount_code, $sitewide_sale );
				}
			}
		}

		// Make sure that [pmpro_checkout] and [pmpro_levels] are available in [sitewide_sales].
		if ( ! has_shortcode( $queried_object->post_content, 'sitewide_sales' ) ) {
			return;
		}

		// May be overwritten by PMPro.
		add_shortcode( 'pmpro_checkout', array( __CLASS__, 'manual_pmpro_checkout_shortcode_implementation' ) );
		require_once PMPRO_DIR . '/preheaders/checkout.php';
		add_shortcode( 'pmpro_levels', array( __CLASS__, 'manual_pmpro_levels_shortcode_implementation' ) );
		require_once PMPRO_DIR . '/preheaders/levels.php';
	}

	public static function manual_pmpro_checkout_shortcode_implementation() {
		$temp_content = pmpro_loadTemplate( 'checkout', 'local', 'pages' );
		return apply_filters( 'pmpro_pages_shortcode_checkout', $temp_content );
	}

	public static function manual_pmpro_levels_shortcode_implementation() {
		$temp_content = pmpro_loadTemplate( 'levels', 'local', 'pages' );
		return apply_filters( 'pmpro_pages_shortcode_levels', $temp_content );
	}

	/**
	 * Returns whether the current page is the landing page
	 * for the passed Sitewide Sale.
	 *
	 * @param boolean               $is_checkout_page current value from filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being checked.
	 * @return boolean
	 */
	public static function is_checkout_page( $is_checkout_page, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $is_checkout_page;
		}
		global $pmpro_pages;
		return ( ! empty( $pmpro_pages['checkout'] ) && is_page( $pmpro_pages['checkout'] ) ) ? true : $is_checkout_page;
	}

	/**
	 * Automatically applies discount code if user has the cookie set from sale page
	 */
	public static function automatic_discount_application() {
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( null === $active_sitewide_sale || 'pmpro' !== $active_sitewide_sale->get_sale_type() || ! $active_sitewide_sale->should_apply_automatic_discount() ) {
			return;
		}

		global $wpdb, $pmpro_pages;
		if ( empty( $_REQUEST['level'] ) || ! empty( $_REQUEST['discount_code'] ) ) {
			return;
		}
		$discount_code_id = $active_sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null );
		if ( null === $discount_code_id ) {
			return;
		}
		$code = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );
 		if ( pmpro_checkDiscountCode( $code, $_REQUEST['level'] ) ) {
 			$_REQUEST['discount_code'] = $code;
 		}
	}

	/**
	 * Load our module's CSS.
	 */
	public static function wp_enqueue_scripts() {
		wp_register_style( 'swsales_module_pmpro', plugins_url( 'modules/ecommerce/pmpro/swsales-module-pmpro.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_module_pmpro' ); 
	}

	/**
	 * Set PMPro module checkout conversion title for Sitewide Sale report.
	 *
	 * @param string               $cur_title     set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions_title( $cur_title, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_title;
		}
		global $wpdb;
		$discount_code_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null );
		$discount_code = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );;

		if ( null === $discount_code_id || empty( $discount_code ) ) {
			return $cur_title;
		}

		return sprintf(
			__( 'Checkouts using <a href="%s">%s</a>', 'sitewide-sales' ),
			admin_url( 'admin.php?page=pmpro-orders&filter=with-discount-code&discount-code=' . $discount_code_id ),
			$discount_code
		);
	}

	/**
	 * Set PMPro module checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions( $cur_conversions, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT COUNT(*)
				FROM $wpdb->pmpro_discount_codes_uses
				WHERE code_id = %d
					AND timestamp >= %s
					AND timestamp < %s
			",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				get_gmt_from_date( $sitewide_sale->get_start_date( 'Y-m-d H:i:s' ) ),
				get_gmt_from_date( $sitewide_sale->get_end_date( 'Y-m-d H:i:s' ) )
			) . ''
		);
	}

	/**
	 * Total Revenue for the period of the Sitewide Sale.
	 *
	 * @param string $cur_revenue set by filter. N/A
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string total revenue for the Sitewide Sale's period, despite belongs to the given Sitewide Sale
	 *
	 * @since 1.4
	 */
	public static function total_revenue($cur_revenue, $sitewide_sale, $format_price = false) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		global $wpdb;
		$total_rev = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM(mo.total)
				FROM $wpdb->pmpro_membership_orders mo
				WHERE mo.status NOT IN('refunded', 'review', 'token', 'error')
					AND mo.timestamp >= %s
					AND mo.timestamp < %s
				",
				get_gmt_from_date( $sitewide_sale->get_start_date( 'Y-m-d H:i:s' ) ),
				get_gmt_from_date( $sitewide_sale->get_end_date( 'Y-m-d H:i:s' ) )
			)
		);

		return $format_price ? pmpro_formatPrice( $total_rev ) : $total_rev;
	}

	/**
	 * Set PMPro module total revenue for Sitewide Sale report.
	 *
	 * @param string $cur_revenue set by filter. N/A
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string revenue for the period of the Sitewide Sale.
	 *
	 * @since 1.4
	 */
	public static function sale_revenue( $cur_revenue, $sitewide_sale, $format_price = false ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}

		global $wpdb;
		$sale_revenue = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM(total) FROM (
					SELECT mo.total  as total
					FROM $wpdb->pmpro_membership_orders mo
						LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
							ON dcu.order_id = mo.id
					WHERE dcu.code_id = %d #discount code is used
						AND mo.status NOT IN('refunded', 'review', 'token', 'error')
						AND mo.timestamp >= %s
						AND mo.timestamp < %s
					GROUP BY mo.id
				) temp
				",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				get_gmt_from_date( $sitewide_sale->get_start_date( 'Y-m-d H:i:s' ) ),
				get_gmt_from_date( $sitewide_sale->get_end_date( 'Y-m-d H:i:s' ) )
			)
		);

		return $format_price ? pmpro_formatPrice( $sale_revenue ) : $sale_revenue;
	}

	/**
	 * Get revenue from other sales during the same time period.
	 *
	 * @param string $cur_revenue set by filter. N/A
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string revenue from other sales during the same time period.
	 */
	public static function get_other_revenue ($cur_revenue, $sitewide_sale, $format_price = false) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}
		global $wpdb;
		$other_revenue = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM(total) FROM (
					SELECT mo.total  as total
					FROM $wpdb->pmpro_membership_orders mo
						LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
							ON dcu.order_id = mo.id
						LEFT JOIN $wpdb->pmpro_membership_orders mo2
							ON mo.user_id = mo2.user_id
								AND mo2.id <> mo.id
								AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
					WHERE (dcu.code_id IS NULL OR dcu.code_id <> %d) #null or different code
						AND mo.status NOT IN('refunded', 'review', 'token', 'error')
						AND mo.timestamp >= %s
						AND mo.timestamp < %s
						#no other order for the same user
						AND mo2.id IS NULL
					GROUP BY mo.id
					) temp
				",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				get_gmt_from_date( $sitewide_sale->get_start_date( 'Y-m-d H:i:s' ) ),
				get_gmt_from_date( $sitewide_sale->get_end_date( 'Y-m-d H:i:s' ) )
			)
		);

		return $format_price ? pmpro_formatPrice( $other_revenue ) : $other_revenue;
	}

	/**
	 * Get revenue from renewals during the same time period.
	 *
	 * @param string $cur_revenue set by filter. N/A
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @param bool $format_price whether to run output through pmpro_formatPrice().
	 * @return string revenue from renewals during the same time period.
	 */
	public static function get_renewal_revenue($cur_revenue, $sitewide_sale, $format_price = false) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}
		global $wpdb;

		$renewal_revenue = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM(total) FROM (
					SELECT mo.total  as total
					FROM $wpdb->pmpro_membership_orders mo
						LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
							ON dcu.order_id = mo.id
						LEFT JOIN $wpdb->pmpro_membership_orders mo2
							ON mo.user_id = mo2.user_id
								AND mo2.id <> mo.id
								AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
					WHERE (dcu.code_id IS NULL OR dcu.code_id <> %d) #null or different code
						AND mo.status NOT IN('refunded', 'review', 'token', 'error')
						AND mo.timestamp >= %s
						AND mo.timestamp < %s
						#another order for the same user
						AND mo2.id IS NOT NULL
					GROUP BY mo.id
					) temp
				",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				get_gmt_from_date( $sitewide_sale->get_start_date( 'Y-m-d H:i:s' ) ),
				get_gmt_from_date( $sitewide_sale->get_end_date( 'Y-m-d H:i:s' ) )
			)
		);

		return $format_price ? pmpro_formatPrice( $renewal_revenue ) : $renewal_revenue;
	}

	/**
	 * Generate data for daily revenue chart.
	 *
	 * @param array                 $daily_revenue_chart_data to be shown in chart
	 * @param SWSales_Sitewide_Sale $sitewide_sale being reported on.
	 * @return bool
	 */
	public static function swsales_daily_revenue_chart_data( $daily_revenue_chart_data, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $daily_revenue_chart_data;
		}
		global $wpdb;
		$query_data = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DATE_FORMAT( DATE_ADD( o.timestamp, INTERVAL %d HOUR ), '%s') as date, SUM(o.total) as value
					FROM $wpdb->pmpro_membership_orders o
				LEFT JOIN $wpdb->pmpro_discount_codes_uses dc
					ON o.id = dc.order_id
				WHERE dc.code_id = %s
					AND o.status NOT IN('refunded', 'review', 'token', 'error')
					AND o.timestamp >= %s
					AND o.timestamp < %s
				GROUP BY date
				ORDER BY date
				",
				get_option( 'gmt_offset' ), // Convert to local time.
				'%Y-%m-%d', // To prevent these from being seen as placeholders.
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				get_gmt_from_date( $sitewide_sale->get_start_date( 'Y-m-d H:i:s' ) ),
				get_gmt_from_date( $sitewide_sale->get_end_date( 'Y-m-d H:i:s' ) )
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
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $currency_format;
		}
		global $pmpro_currency_symbol, $pmpro_currency, $pmpro_currencies;
		return array(
			'currency_symbol' => $pmpro_currency_symbol,
			'decimals' => isset( $pmpro_currencies[ $pmpro_currency ]['decimals'] ) ? (int) $pmpro_currencies[ $pmpro_currency ]['decimals'] : 2,
			'decimal_separator' => isset( $pmpro_currencies[ $pmpro_currency ]['decimal_separator'] ) ? $pmpro_currencies[ $pmpro_currency ]['decimal_separator'] : '.',
			'thousands_separator' => isset( $pmpro_currencies[ $pmpro_currency ]['thousands_separator'] ) ? $pmpro_currencies[ $pmpro_currency ]['thousands_separator'] : ',',
			'position' => pmpro_getCurrencyPosition() == 'right' ? 'suffix' : 'prefix'
		);
	}
}
SWSales_Module_PMPro::init();
