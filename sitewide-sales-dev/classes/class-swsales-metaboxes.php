<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Register a meta box using a class.
 */
class SWSales_MetaBoxes {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'load-post.php', array( __CLASS__, 'init_metabox' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'init_metabox' ) );
		add_action( 'enter_title_here', array( __CLASS__, 'update_title_placeholder_text' ), 10, 2 );
		add_action( 'wp_ajax_swsales_create_landing_page', array( __CLASS__, 'create_landing_page_ajax' ) );
	}

	/**
	 * Enqueues js/swsales-cpt-meta.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_cpt_meta', plugins_url( 'js/swsales-cpt-meta.js', SWSALES_BASENAME ), array( 'jquery' ), SWSALES_VERSION );
			wp_enqueue_script( 'swsales_cpt_meta' );

			$pages_with_swsales_shortcode = $wpdb->get_col(
				"SELECT ID
				 FROM $wpdb->posts
				 WHERE post_type = 'page'
				 	AND post_status IN( 'publish', 'draft' )
					AND post_content LIKE '%[sitewide_sale%'"
			);

			wp_localize_script(
				'swsales_cpt_meta',
				'swsales',
				array(
					'create_landing_page_nonce'  => wp_create_nonce( 'swsales_create_landing_page' ),
					'home_url'                   => home_url(),
					'admin_url'                  => admin_url(),
					'pages_with_shortcodes'      => $pages_with_swsales_shortcode,
					'str_draft'                  => esc_html__( 'Draft', 'sitewide-sales' ),
				)
			);

		}
	}

	/**
	 * Meta box initialization.
	 */
	public static function init_metabox() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_swsales_metaboxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_swsales_metaboxes' ), 10, 2 );
	}

	/**
	 * Add/remove the metaboxes.
	 */
	public static function add_swsales_metaboxes() {
		add_meta_box(
			'swsales_cpt_publish_sitewide_sale',
			__( 'Admin', 'sitewide-sales' ),
			array( __CLASS__, 'publish_sitewide_sale' ),
			array( 'sitewide_sale' ),
			'side',
			'high'
		);
		add_meta_box(
			'swsales_cpt_reports',
			__( 'Quick Reports', 'sitewide-sales' ),
			array( __CLASS__, 'display_cpt_reports' ),
			array( 'sitewide_sale' ),
			'side',
			'high'
		);
		add_meta_box(
			'swsales_documentation',
			__( 'Documentation', 'sitewide-sales' ),
			array( __CLASS__, 'documentation' ),
			array( 'sitewide_sale' ),
			'side',
			'default'
		);
		add_meta_box(
			'swsales_cpt_step_dates',
			__( 'Start and End Dates', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_dates' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_type',
			__( 'Sale Type', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_type' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_banner',
			__( 'Sale Banner', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_banner' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_landing_page',
			__( 'Landing Page', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_landing_page' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);

		// remove some default metaboxes
		remove_meta_box( 'slugdiv', 'sitewide_sale', 'normal' );
		remove_meta_box( 'submitdiv', 'sitewide_sale', 'side' );
	}

	public static function publish_sitewide_sale( $post ) {
		wp_nonce_field( 'custom_nonce_action', 'custom_nonce' );

		global $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		// TODO: Think about whether should automatically be set as active sitewide sale
		$init_checked = false;
		if ( isset( $_REQUEST['set_sitewide_sale'] ) && 'true' === $_REQUEST['set_sitewide_sale'] ) {
			$init_checked = true;
		} else {
			$options = SWSales_Settings::get_options();
			if ( empty( $options['active_sitewide_sale_id'] ) && $post->post_status == 'auto-draft'
				|| $cur_sale->is_active_sitewide_sale() ) {
				$init_checked = true;
			}
		}
		?>
		<?php
			$sale_status_running = $cur_sale->is_running();

			if ( $sale_status_running === true ) {
				echo '<div class="sitewide_sales_message sitewide_sales_success">';
				echo '<strong>' . esc_html( 'Running.', 'sitewide-sales' ) . '</strong>';
				echo ' ' .  esc_html( 'This is the active sitewide sale.', 'sitewide-sales' );
			} else {
				echo '<div class="sitewide_sales_message sitewide_sales_alert">';
				echo '<strong>' . esc_html( 'Not Running.', 'sitewide-sales' ) . '</strong>';

				if ( ! $cur_sale->is_active_sitewide_sale() ) {
					$error_message = esc_html( 'This is not the active sitewide sale.', 'sitewide-sales' );
				} else {
					switch ( $cur_sale->get_time_period() ) {
						case 'error':
							$error_message = esc_html( 'Invalid timeframe.', 'sitewide-sales' );
							break;
						case 'pre-sale':
							$error_message = esc_html( 'Sale has not yet started.', 'sitewide-sales' );
							break;
						case 'post-sale':
							$error_message = esc_html( 'Sale has ended.', 'sitewide-sales' );
							break;
					}
				}
				echo ' ' . $error_message . ' ' . esc_html( 'Banner will not be shown.', 'sitewide-sales' );
			}
			echo '</div>';
		?>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<p>
					<label for="swsales_set_as_sitewide_sale"><strong><?php esc_html_e( 'Set as Current Sitewide Sale', 'sitewide-sales' ); ?></strong></label>
					<input name="swsales_set_as_sitewide_sale" id="swsales_set_as_sitewide_sale" type="checkbox" <?php checked( $init_checked, true ); ?> />
				</p>
			</div>
		</div>
		<div id="major-publishing-actions">
			<div id="publishing-action">
				<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Save All Settings', 'sitewide-sales' ); ?>">
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	public static function documentation( $post ) { ?>
		<p><?php esc_html_e( 'Explore how to set up a sale using the Sitewide Sales plugin.' ); ?></p>
		<ul>
			<li><a href="https://sitewidesales.com/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=about-plugin" target="_blank" title="<?php esc_attr_e( 'About the Plugin', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'About the Plugin', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/getting-started/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Getting Started With Sitewide Sales', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Getting Started With Sitewide Sales', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/create-sale/sale-start-and-end-date/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Setting the Sale Start and End Date', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Setting the Sale Start and End Date', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/create-sale/sale-type/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Choosing a Sale Type and Discount Code or Coupon', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Choosing a Sale Type and Discount Code or Coupon', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/create-sale/landing-page/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Designing Your Sale Landing Page', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Designing Your Sale Landing Page', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/create-sale/sale-banners/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Setting Up the Active Sales Banner', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Setting Up the Active Sales Banner', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/reports/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Viewing Sitewide Sale Reports', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Viewing Sitewide Sale Reports', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
			<li><a href="https://sitewidesales.com/documentation/action-and-filter-hooks/?utm_source=plugin&utm_medium=edit-swsales-meta-box&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Extend Sitewide Sales via Action and Filter Hooks', 'sitewide-sales' ); ?>">
				<?php esc_html_e( 'Extend Sitewide Sales via Action and Filter Hooks', 'sitewide-sales' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sitewide-sales' ); ?></span>
			</a></li>
		</ul>
		<?php
	}

	/**
	 * Filter the "Enter title here" placeholder in the title field
	 */
	public static function update_title_placeholder_text( $text, $post ) {
		if ( $post->post_type == 'sitewide_sale' ) {
			$text = esc_html__( 'Enter title here. (For reference only.)', 'sitewide-sales' );
		}

		return $text;
	}

	public static function display_step_dates( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}
		?>
		<p><?php esc_html_e( 'These fields control when the banner (if applicable) and built-in sale reporting will be active for your site. They also control what content is displayed on your sale Landing Page according to the "Landing Page" settings below.', 'sitewide-sales' ); ?></p>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="swsales_start_date"><?php esc_html_e( 'Sale Start Date', 'sitewide-sales' ); ?></label></th>
					<td>
						<input id="swsales_start_day" name="swsales_start_day" type="date" lang="<?php echo esc_attr( get_locale() ); ?>" value="<?php echo esc_attr( $cur_sale->get_start_date( 'Y-m-d' ) ); ?>" />
						<input id="swsales_start_time" name="swsales_start_time" type="time" lang="<?php echo esc_attr( get_locale() ); ?>" value="<?php echo esc_attr( $cur_sale->get_start_date( 'H:i' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Set this date and time to when your sale should begin.', 'sitewide-sales' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="swsales_end_date"><?php esc_html_e( 'Sale End Date', 'sitewide-sales' ); ?></label></th>
					<td>
						<input id="swsales_end_day" name="swsales_end_day" type="date" lang="<?php echo esc_attr( get_locale() ); ?>" value="<?php echo esc_attr( $cur_sale->get_end_date( 'Y-m-d' ) ); ?>" />
						<input id="swsales_end_time" name="swsales_end_time" type="time" lang="<?php echo esc_attr( get_locale() ); ?>" value="<?php echo esc_attr( $cur_sale->get_end_date( 'H:i' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Set this date and time to when your sale should end.', 'sitewide-sales' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_type( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		// Additional modules can be added via this filter in the format array( 'short_name' => 'Nice Name', )
		$sale_types = apply_filters( 'swsales_sale_types', array() );
		$current_sale_type = $cur_sale->get_sale_type();
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="swsales_sale_type"><?php esc_html_e( 'Sale Type', 'sitewide-sales' );?></label></th>
					<td>
						<select class="sale_type_select swsales_option" id="swsales_sale_type_select" name="swsales_sale_type">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$sale_type_found = false;
							foreach ( $sale_types as $sale_type_short => $sale_type_nice ) {
								$selected_modifier = '';
								if ( $sale_type_short === $current_sale_type ) {
									$selected_modifier = ' selected="selected"';
									$sale_type_found        = true;
								}
								echo '<option value="' . esc_attr( $sale_type_short ) . '"' . $selected_modifier . '>' . esc_html( $sale_type_nice ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<?php 
				// Filter to add custom settings from module .
				do_action( 'swsales_after_choose_sale_type', $cur_sale );
				?>
				<tr>
					<th><label for="swsales_hide_for_roles_select"><?php esc_html_e( 'Hide Sale by Role', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="hidden" name="swsales_hide_for_roles_exists" value="1" />
						<select multiple class="swsales_option" id="swsales_hide_for_roles_select" name="swsales_hide_for_roles[]">
						<?php
							$all_roles = get_editable_roles();
							$all_roles['logged_out'] = array(
								'name' => __( 'Logged Out', 'sitewide-sales' ),
							);
							$hide_for_roles = json_decode( $cur_sale->get_meta_value( 'swsales_hide_for_roles', '[]' ) );
							foreach ( $all_roles as $slug => $role_data ) {
								$selected_modifier = in_array( $slug, $hide_for_roles ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $slug ) . '"' . $selected_modifier . '>' . esc_html( $role_data['name'] ) . '</option>';
							}
						?>
						</select>
						<p class="description"><?php esc_html_e( 'This setting will completely hide the sale from users with the selected roles (including the banner and discount logic).', 'sitewide-sales' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="swsales_automatic_discount"><?php esc_html_e( 'Apply Discount Automatically', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="swsales_option" id="swsales_automatic_discount_select" name="swsales_automatic_discount">
							<option value="none"
								<?php
								$cur_sale_automatic_discount = $cur_sale->get_automatic_discount();
								echo( 'none' === $cur_sale_automatic_discount ? " selected='selected'>" : '>' );
								esc_html_e( 'Do not apply discount automatically.', 'sitewide-sales' );
								?>
							</option>
							<option value="landing"
								<?php
								echo( 'landing' === $cur_sale_automatic_discount ? " selected='selected'>" : '>' );
								esc_html_e( 'Apply discount automatically if user has seen the landing page.', 'sitewide-sales' );
								?>
							</option>
							<option value="all"
								<?php
								echo( 'all' === $cur_sale_automatic_discount ? " selected='selected'>" : '>' );
								esc_html_e( 'Always apply discount automatically.', 'sitewide-sales' );
								?>
							</option>
						</select>
						<p class="description"><?php esc_html_e( 'Caching plugins may interfere with this functionality. If using caching on your site, consider never giving an automatic discount or always giving an automatic discount.', 'sitewide-sales' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_banner( $post ) {
		global $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		$banner_modules        = apply_filters( 'swsales_banner_modules', array() );
		ksort( $banner_modules );
		$current_banner_module = $cur_sale->swsales_banner_module;

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="swsales_banner_module"><?php esc_html_e( 'Banner Type', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="swsales_option" id="swsales_banner_module" name="swsales_banner_module">
							<option value=""><?php esc_html_e( '- No Banner -', 'sitewide-sales' ); ?></option>
							<?php
							foreach ( $banner_modules as $label => $module ) {
								echo '<option value="' . esc_attr( $module ) . '"' . selected( $current_banner_module, $module ) . '>' . esc_html( $label ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		foreach ( $banner_modules as $label => $module ) {
			?>
			<table class="form-table swsales_banner_module_settings" id="swsales_banner_settings_<?php echo esc_attr( $module ) ?>">
			<?php
			$module::echo_banner_settings_html_inner( $cur_sale );
			?>
			</table>
			<?php
		}
		?>
		<table class="form-table" id="swsales_banner_options">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="swsales_banner_close_behavior"><?php esc_html_e( 'Banner Close Behavior', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="swsales_option" id="swsales_banner_close_behavior" name="swsales_banner_close_behavior">
							<option value="refresh" <?php selected( $cur_sale->get_meta_value('swsales_banner_close_behavior'), 'refresh' ); ?>><?php esc_html_e( 'Close Until Refresh', 'sitewide-sales' ); ?></option>
							<option value="session" <?php selected( $cur_sale->get_meta_value('swsales_banner_close_behavior'), 'session' ); ?>><?php esc_html_e( 'Close Until New Session', 'sitewide-sales' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Select when the banner will reappear if the user closes or dismisses the banner.', 'sitewide-sales' ); ?></p>
					</td>
				</tr>
				<tr>
					<?php
						$checked_modifier = $cur_sale->get_hide_on_checkout() ? ' checked' : '';
					?>
					<th scope="row" valign="top"><label for="swsales_hide_on_checkout"><?php esc_html_e( 'Hide Banner at Checkout', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="hidden" name="swsales_hide_on_checkout_exists" value="1" />
						<input class="swsales_option" type="checkbox" id="swsales_hide_on_checkout" name="swsales_hide_on_checkout" <?php checked( $cur_sale->get_hide_on_checkout(), 1 ); ?>> <label for="swsales_hide_on_checkout"><?php esc_html_e( 'Check this box to hide the banner on checkout pages.', 'sitewide-sales' ); ?></label>
						<p class="description"><?php esc_html_e( 'Recommended: Leave checked so only users using your landing page will pay the sale price.', 'sitewide-sales' ); ?></p>
					</td>
				</tr>
				<?php
					// Old filter to hide banner by role. This is being deprecated in place of the field swsales_hide_for_roles.
					$hide_for_roles = json_decode( $cur_sale->get_meta_value( 'swsales_hide_banner_by_role', '[]' ) );
					if ( ! empty( $hide_for_roles ) ) { ?>
						<tr>
							<th><label for="swsales_hide_banner_by_role"><?php esc_html_e( 'Hide Banner by Role', 'sitewide-sales' ); ?></label></th>
							<td>
								<input type="hidden" name="swsales_hide_banner_by_role_exists" value="1" />
								<select multiple class="swsales_option" id="swsales_hide_banner_by_role_select" name="swsales_hide_banner_by_role[]">
								<?php
									$all_roles = get_editable_roles();
									$all_roles['logged_out'] = array(
										'name' => __( 'Logged Out', 'sitewide-sales' ),
									);
									$hide_for_roles = json_decode( $cur_sale->get_meta_value( 'swsales_hide_banner_by_role', '[]' ) );
									foreach ( $all_roles as $slug => $role_data ) {
										$selected_modifier = in_array( $slug, $hide_for_roles ) ? ' selected="selected"' : '';
										echo '<option value="' . esc_attr( $slug ) . '"' . $selected_modifier . '>' . esc_html( $role_data['name'] ) . '</option>';
									}
								?>
								</select>
								<p class="description"><?php esc_html_e( 'This setting will hide the banner for users with the selected roles.', 'sitewide-sales' ); ?></p>
							</td>
						</tr>
						<?php
					}
				?>
				<?php
				//  Add filter for modlues (ex. hide banner for level)
				do_action( 'swsales_after_banners_settings', $cur_sale );
				?>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_landing_page( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		$pages        = get_pages( array( 'post_status' => 'publish,draft' ) );
		$current_page = $cur_sale->get_landing_page_post_id();
		$landing_template = $cur_sale->get_landing_page_template();
		?>
		<input type="hidden" id="swsales_old_landing_page_post_id" name="swsales_old_landing_page_post_id" value="<?php echo esc_attr( $current_page ); ?>" />
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="swsales_landing_page_post_id"><?php esc_html_e( 'Landing Page', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="landing_page_select swsales_option" id="swsales_landing_page_select" name="swsales_landing_page_post_id">
							<option value="0"><?php esc_html_e( '- No Landing Page -', 'sitewide-sales' ); ?></option>
							<?php
							$page_found = false;
							foreach ( $pages as $page ) {
								$selected_modifier = '';
								if ( $page->ID . '' === $current_page ) {
									$selected_modifier = ' selected="selected"';
									$page_found        = true;
								}
								if ( $page->post_status == 'draft' ) {
									$status_part = ' (' . esc_html__( 'Draft', 'sitewide-sales' ) . ')';
								} else {
									$status_part = '';
								}
								echo '<option value="' . esc_attr( $page->ID ) . '"' . $selected_modifier . '>' . esc_html( $page->post_title ) . $status_part . '</option>';
							}
							?>
						</select>

						<?php
							$current_page_post = get_post( $current_page );
						if ( ! empty( $current_page_post->post_content ) && strpos( $current_page_post->post_content, '[sitewides_sale' ) !== false ) {
							$show_shortcode_warning = false;
						} else {
							$show_shortcode_warning = true;
						}
						?>
						<p>
							<span id="swsales_after_landing_page_select" 
							<?php
							if ( ! $page_found ) {
								?>
 style="display: none;"<?php } ?>>
							<?php
								$edit_page_url = add_query_arg( array( 'post' => $current_page, 'action' => 'edit' ), admin_url( 'post.php' ) );
								$view_page_url = add_query_arg( array( 'page_id' => $current_page ), home_url( '/' ) );
							?>
							<a target="_blank" class="button button-secondary" id="swsales_edit_landing_page" href="<?php echo esc_url( $edit_page_url ); ?>"><?php esc_html_e( 'edit page', 'sitewide-sales' ); ?></a>
							&nbsp;
							<a target="_blank" class="button button-secondary" id="swsales_view_landing_page" href="<?php echo esc_url( $view_page_url ); ?>"><?php esc_html_e( 'view page', 'sitewide-sales' ); ?></a>
							<?php
								esc_html_e( ' or ', 'sitewide-sales' );
							?>
							</span>
							<button type="button" id="swsales_create_landing_page" class="button button-secondary"><?php esc_html_e( 'create a new landing page', 'sitewide-sales' ); ?></button>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<div id="swsales_landing_page_options">
			<table class="form-table">
				<tbody>
					<?php if ( ! empty( $view_page_url ) ) { ?>
						<tr>
							<th><label><?php esc_html_e( 'Preview Landing Page', 'sitewide-sales' ); ?></label></th>
							<td>
								<p>
									<?php esc_html_e( 'Select a period to preview the landing page for this sale:', 'sitewide-sales' ); ?>
									<br />
									<a target="_blank" id="swsales_view_landing_page_pre_sale" href="<?php echo esc_url( add_query_arg( 'swsales_preview_time_period', 'pre-sale', $view_page_url ) ); ?>"><?php esc_html_e( 'Before (pre-sale)', 'sitewide-sales' ); ?></a>
									&nbsp;|&nbsp;
									<a target="_blank" id="swsales_view_landing_page_sale" href="<?php echo esc_url( add_query_arg( 'swsales_preview_time_period', 'sale', $view_page_url ) ); ?>"><?php esc_html_e( 'During (sale)', 'sitewide-sales' ); ?></a>
									&nbsp;|&nbsp;
									<a target="_blank" id="swsales_view_landing_page_post_sale" href="<?php echo esc_url( add_query_arg( 'swsales_preview_time_period', 'post-sale', $view_page_url ) ); ?>"><?php esc_html_e( 'After (post-sale)', 'sitewide-sales' ); ?></a>
								</p>
							</td>
						</tr>
					<?php } ?>

					<?php
						// Add filter for modules here.
						do_action( 'swsales_after_choose_landing_page', $cur_sale );
					?>

				</tbody>
			</table>
			<hr />
			<div class="swsales-table-trigger">
				<?php
					$allowed_html = array (
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
						),
						'strong' => array(),
						'em' => array(),		);
				?>
				<p><?php echo wp_kses( __( 'Edit your landing page to insert content shown before, during, or after the sale. Use the <strong>Sale Content Block</strong> to insert content in grouped sections or the <strong>Sale Period Visibility</strong> setting to toggle visibility on individual block groups.', 'sitewide-sales' ), $allowed_html ); ?></p>
				<p>
					<?php
						/* translators: Strings here are button open and close tags. */
						$button_text = __( 'Or, use the [sitewide_sales] shortcode on your page and %sthe legacy fields here to create a basic landing page%s.', 'sitewide-sales' );
						printf( wp_kses( $button_text, $allowed_html), '<button class="swsales-table-trigger-button" type="button">', '</button>' );
					?>
				</p>
			</div>
			<table id="basic-landing-page-content" class="form-table" style="display: none;">
				<tbody>
					<tr>
						<th><label for="swsales_landing_page_template"><?php esc_html_e( 'Landing Page Template', 'sitewide-sales' ); ?></label></th>
						<td>
							<select class="landing_page_select_template swsales_option" id="swsales_landing_page_template" name="swsales_landing_page_template">
								<option value="0"><?php esc_html_e( 'None', 'sitewide-sales' ); ?></option>
								<?php
								$templates = SWSales_Templates::get_templates();
								$templates = apply_filters( 'swsales_landing_page_templates', $templates );
								foreach ( $templates as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $landing_template, esc_html( $key ) ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p class="sitewide_sales_message sitewide_sales_alert swsales_shortcode_warning"
								<?php if ( ! $show_shortcode_warning ) { ?> style="display: none;"<?php } ?>>
								<?php echo wp_kses_post( '<strong>Warning:</strong> The chosen Landing Page does not include the [sitewide_sales] shortcode, so the following sections will not be displayed.', 'sitewide-sales' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label><?php esc_html_e( 'Pre-Sale Content', 'sitewide-sales' ); ?></label>
						</th>
						<td>
							<textarea class="swsales_option" rows="4" name="swsales_pre_sale_content"><?php echo( esc_textarea( $cur_sale->get_pre_sale_content() ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label><?php esc_html_e( 'Sale Content', 'sitewide-sales' ); ?></label>
						</th>
						<td>
							<textarea class="swsales_option" rows="4" name="swsales_sale_content"><?php echo( esc_html( $cur_sale->get_sale_content() ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label><?php esc_html_e( 'Post-Sale Content', 'sitewide-sales' ); ?></label>
						</th>
						<td>
							<textarea class="swsales_option" rows="4" name="swsales_post_sale_content"><?php echo( esc_html( $cur_sale->get_post_sale_content() ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<script>
				jQuery(document).ready(function() {
					swsales_prep_general_click_events();
				});

				// Function to prep click events for admin settings.
				function swsales_prep_general_click_events() {
					jQuery( 'button.swsales-table-trigger-button' ).on( 'click', function(event){
						// Toggle content within the settings sections boxes.
						event.preventDefault();

						let thebutton = jQuery(event.target).parents('.swsales-table-trigger').find('button.swsales-table-trigger-button');
						let sectionshow = jQuery( thebutton ).parents('.swsales-table-trigger').next('table');
						let sectionhide = jQuery(event.target).parents('.swsales-table-trigger');

						jQuery( sectionshow ).show();
						jQuery( sectionhide ).hide();
					});
				}
			</script>
		</div> <!-- end #swsales_landing_page_options -->

		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_cpt_reports( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}
		SWSales_Reports::show_quick_report( $cur_sale );
		?>
		<div class="swsales_reports-quick-data-action">
			<a class="button button-secondary" target="_blank" href="<?php echo esc_url( admin_url( 'edit.php?post_type=sitewide_sale&page=sitewide_sales_reports&sitewide_sale=' . $post->ID ) ); ?>"><?php esc_html_e( 'View Detailed Sale Report', 'sitewide-sales' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Handles saving the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return null
	 */
	public static function save_swsales_metaboxes( $post_id, $post ) {
		global $wpdb;

		if ( 'sitewide_sale' !== $post->post_type ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
			return;
		}

		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['custom_nonce'] ) ? sanitize_text_field( $_POST['custom_nonce'] ) : '';
		$nonce_action = 'custom_nonce_action';

		// Check if nonce is set.
		if ( ! isset( $nonce_name ) ) {
			return;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			die( '<br/>Nonce failed' );
		}

		// Make sure the post title is not blank
		if ( isset( $_POST['post_title'] ) && empty( $_POST['post_title'] ) ) {
			$post->post_title = sanitize_post_field(
				'post_title',
				__( 'Sitewide Sale', 'sitewide-sales' ),
				$post->ID,
				'edit'
			);
		}

		if ( isset( $_POST['swsales_sale_type'] ) ) {
			update_post_meta( $post_id, 'swsales_sale_type', sanitize_text_field( $_POST['swsales_sale_type'] ) );
		}

		if ( ! empty( $_POST['swsales_hide_banner_by_role'] ) && is_array( $_POST['swsales_hide_banner_by_role'] )) {
			$swsales_hide_banner_by_role = array_map( 'sanitize_text_field', $_POST['swsales_hide_banner_by_role'] );
			update_post_meta( $post_id, 'swsales_hide_banner_by_role', wp_json_encode( $swsales_hide_banner_by_role ) );
		} elseif ( ! empty( $_POST['swsales_hide_banner_by_role_exists'] ) ) {
			update_post_meta( $post_id, 'swsales_hide_banner_by_role', wp_json_encode( array() ) );
		}

		if ( ! empty( $_POST['swsales_hide_for_roles'] ) && is_array( $_POST['swsales_hide_for_roles'] )) {
			$swsales_hide_for_roles = array_map( 'sanitize_text_field', $_POST['swsales_hide_for_roles'] );
			update_post_meta( $post_id, 'swsales_hide_for_roles', wp_json_encode( $swsales_hide_for_roles ) );
		} elseif ( ! empty( $_POST['swsales_hide_for_roles_exists'] ) ) {
			update_post_meta( $post_id, 'swsales_hide_for_roles', wp_json_encode( array() ) );
		}

		if ( isset( $_POST['swsales_automatic_discount'] ) ) {
			update_post_meta( $post_id, 'swsales_automatic_discount', sanitize_text_field( $_POST['swsales_automatic_discount'] ) );
		}

		if ( ! empty( $_POST['swsales_landing_page_post_id'] ) ) {
			update_post_meta( $post_id, 'swsales_landing_page_post_id', intval( $_POST['swsales_landing_page_post_id'] ) );
			update_post_meta( intval( $_POST['swsales_landing_page_post_id'] ), 'swsales_sitewide_sale_id', $post_id );
		} elseif ( isset( $_POST['swsales_landing_page_post_id'] ) ) {
			update_post_meta( $post_id, 'swsales_landing_page_post_id', false );
			delete_post_meta( intval( $_REQUEST['swsales_old_landing_page_post_id'] ), 'swsales_sitewide_sale_id' );
		}

		if ( isset( $_POST['swsales_landing_page_template'] ) ) {
			update_post_meta( $post_id, 'swsales_landing_page_template', sanitize_text_field( $_POST['swsales_landing_page_template'] ) );
		}

		if ( isset( $_POST['swsales_start_day'] ) &&
				isset( $_POST['swsales_start_time'] ) &&
				isset( $_POST['swsales_end_day'] ) &&
				isset( $_POST['swsales_end_time'] )
		) {
			$start_date = $_POST['swsales_start_day'] . ' ' . $_POST['swsales_start_time'] . ':00' ;
			update_post_meta( $post_id, 'swsales_start_date', $start_date );
			$end_date = $_POST['swsales_end_day'] . ' ' . $_POST['swsales_end_time'] . ':00' ;
			update_post_meta( $post_id, 'swsales_end_date', $end_date );
		}

		if ( isset( $_POST['swsales_pre_sale_content'] ) ) {
			update_post_meta( $post_id, 'swsales_pre_sale_content', wp_kses_post( $_POST['swsales_pre_sale_content'] ) );
		}

		if ( isset( $_POST['swsales_sale_content'] ) ) {
			update_post_meta( $post_id, 'swsales_sale_content', wp_kses_post( $_POST['swsales_sale_content'] ) );
		}

		if ( isset( $_POST['swsales_post_sale_content'] ) ) {
			update_post_meta( $post_id, 'swsales_post_sale_content', wp_kses_post( $_POST['swsales_post_sale_content'] ) );
		}

		if ( isset( $_POST['swsales_banner_module'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_module', sanitize_text_field( $_POST['swsales_banner_module'] ) );
		}

		if ( isset( $_POST['swsales_banner_close_behavior'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_close_behavior', sanitize_text_field( $_POST['swsales_banner_close_behavior'] ) );
		}

		if ( ! empty( $_POST['swsales_hide_on_checkout'] ) ) {
			update_post_meta( $post_id, 'swsales_hide_on_checkout', true );
		} elseif ( isset( $_POST['swsales_hide_on_checkout_exists'] ) ) {
			update_post_meta( $post_id, 'swsales_hide_on_checkout', false );
		}

		$options = SWSales_Settings::get_options();
		if ( isset( $_POST['swsales_set_as_sitewide_sale'] ) ) {
			$options['active_sitewide_sale_id'] = $post_id;
		} elseif ( $options['active_sitewide_sale_id'] == $post_id ) {
			$options['active_sitewide_sale_id'] = false;
		}
		SWSales_Settings::save_options( $options );
	
		$banner_modules = apply_filters( 'swsales_banner_modules', array() );
		foreach ( $banner_modules as $label => $module ) {
			$module::save_banner_settings_if_module_active( $post_id, $post );
		}

		do_action( 'swsales_save_metaboxes', $post_id, $post );

		if ( isset( $_POST['swsales_preview'] ) ) {
			$url_to_open = get_home_url() . '?swsales_preview_sale_banner=' . $post_id;
			wp_redirect( esc_url_raw( $url_to_open ) );
			exit();
		}
		if ( isset( $_POST['swsales_view_reports'] ) ) {
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=pmpro-reports&report=swsales_reports' ) ) );
			exit();
		}
	}

	/**
	 * AJAX callback to create a new landing page for your sale
	 */
	public static function create_landing_page_ajax() {
		check_ajax_referer( 'swsales_create_landing_page', 'nonce' );

		$sitewide_sale_id = intval( $_REQUEST['swsales_id'] );
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

		$landing_page_title = sanitize_text_field( $_REQUEST['swsales_landing_page_title'] );
		if ( empty( $landing_page_title ) ) {
			$landing_page_title = esc_html__( 'Sitewide Sale Landing Page', 'sitewide-sales' );
		}

		$landing_page_post_id = wp_insert_post(
			array(
				'post_title'    => $landing_page_title,
				'post_content'  => '<!-- wp:swsales/sale-content {\"period\":\"pre-sale\"} --><div class=\"wp-block-swsales-sale-content\"><!-- wp:paragraph --><p>Edit this section to show content before your sale starts.</p><!-- /wp:paragraph --></div><!-- /wp:swsales/sale-content --><!-- wp:swsales/sale-content {\"period\":\"sale\"} --><div class=\"wp-block-swsales-sale-content\"><!-- wp:paragraph --><p>Edit this section to show content while your sale is running.</p><!-- /wp:paragraph --></div><!-- /wp:swsales/sale-content --><!-- wp:swsales/sale-content {\"period\":\"post-sale\"} --><div class=\"wp-block-swsales-sale-content\"><!-- wp:paragraph --><p>Edit this section to show content after your sale ends.</p><!-- /wp:paragraph --></div><!-- /wp:swsales/sale-content -->',
				'post_type'     => 'page',
				'post_status'   => 'draft',
				'page_template' => 'swsales-page-template.php',
			)
		);

		if ( empty( $landing_page_post_id ) ) {
			$r = array(
				'status' => 'error',
				'error'  => esc_html__( 'Error inserting post. Try doing it manually.', 'sitewide-sales' ),
			);
		} else {
			$r = array(
				'status' => 'success',
				'post'   => get_post( $landing_page_post_id ),
			);
		}

		echo json_encode( $r );
		exit;
	}
}
