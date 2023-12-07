<?php

class SWSales_Banner_Module_SWSales extends SWSales_Banner_Module {
	/**
	 * Set up the module.
	 */
	public static function init() {
		parent::init();

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// Set up showing banner on frontend.
		add_action( 'wp', array( __CLASS__, 'choose_banner' ) );
		add_action( 'wp_head', array( __CLASS__, 'apply_custom_css' ), 10 );

		// Run some filters we like on banner content
		add_filter( 'swsales_banner_content', 'do_shortcode', 10, 1 );
	}

	/**
	 * Enqueues /modules/banners/swsales/swsales-banner-module-swsales-settings.js
	 */
	public static function admin_enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_banner_module_swsales_settings', plugins_url( 'modules/banner/swsales/swsales-banner-module-swsales-settings.js', SWSALES_BASENAME ), array( 'jquery' ), SWSALES_VERSION );
			wp_enqueue_script( 'swsales_banner_module_swsales_settings' );
		}
	}

	/**
	 * Logic for when to show banners/which banner to show
	 */
	public static function choose_banner() {
		// are we previewing?
		$preview = false;
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
			$preview              = true;
		} else {
			$active_sitewide_sale = self::is_used_by_active_sitewide_sale();
		}

		if ( empty( $active_sitewide_sale ) ) {
			return;
		}

		$banner_info = self::get_banner_info( $active_sitewide_sale );

		// Unless we are previewing, don't show the banner on certain pages.
		$show_banner = true;
		if ( ! $preview ) {
			$show_banner = self::banner_should_be_shown( $active_sitewide_sale );

			// Don't show on login page.
			if ( Sitewide_Sales\classes\SWSales_Setup::is_login_page() ) {
				$show_banner = false;
			}
		}

		// If the banner module isn't custom, don't show the banner.
		if ( isset( $banner_info['module'] ) && $banner_info['module'] != 'SWSales_Banner_Module_SWSales' ) {
			$show_banner = false;
		}

		// Return nothing if we shouldn't show the banner.
		if ( empty( $show_banner ) ) {
			return;
		}
		
		// Display the appropriate banner
		$registered_banners = self::get_registered_banners();

		if ( array_key_exists( $banner_info['location'], $registered_banners ) && array_key_exists( 'callback', $registered_banners[ $banner_info['location'] ] ) ) {
			$callback_func = $registered_banners[ $banner_info['location'] ]['callback'];

			if ( is_array( $callback_func ) ) {
				if ( 2 >= count( $callback_func ) ) {
					call_user_func( $callback_func[0] . '::' . $callback_func[1] );
				}
			} elseif ( is_string( $callback_func ) ) {
				if ( is_callable( $callback_func ) ) {
					call_user_func( $callback_func );
				}
			}
		}
	}

	/**
	 * Applies user's custom css to banner
	 */
	public static function apply_custom_css() {
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
		} else {
			$active_sitewide_sale = self::is_used_by_active_sitewide_sale();
		}

		if ( empty( $active_sitewide_sale ) ) {
			return;
		}

		$css = $active_sitewide_sale->swsales_banner_css;
		?>
		<!--Sitewide Sale Add On for Paid Memberships Pro Custom CSS-->
		<style type="text/css">
		<?php
		if ( ! empty( $css ) ) {
			echo $css;
		}
		?>
		</style>
		<!--/Sitewide Sale Add On for Paid Memberships Pro Custom CSS-->
		<?php
	}

	/**
	 * Handles the process of showing a banner.
	 */
	public static function __callStatic( $name, $arguments ) {
		switch ( $name ) {
			case 'hook_top_banner':
				add_action( 'wp_body_open', array( __CLASS__, 'show_top_banner' ) );
				break;
			case 'hook_bottom_banner':
				add_action( 'wp_body_open', array( __CLASS__, 'show_bottom_banner' ) );
				break;
			case 'hook_bottom_right_banner':
				add_action( 'wp_body_open', array( __CLASS__, 'show_bottom_right_banner' ) );
				break;
			case 'show_top_banner':
			case 'show_bottom_banner':
			case 'show_bottom_right_banner':
				if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
					$active_sitewide_sale = Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
				} else {
					$active_sitewide_sale = Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
				}
				$banner_info = self::get_banner_info( $active_sitewide_sale );
				$banner_location_nicename = str_replace( '_', '-', $banner_info['location'] );

				// The HTML to show the banner dismiss link.
				$banner_dismiss_link_html = '<a href="javascript:void(0);" class="swsales-dismiss" title="Dismiss"><span class="screen-reader-text"><?php esc_html_e( "Dismiss", "sitewide-sales" ); ?></a>';

				/**
				 * Filter to disable or modify the banner dismiss link HTML.
				 * Set to empty string to hide the link.
				 *
				 * @since 1.3.0
				 *
				 * @param string $banner_dismiss_link_html The full HTML of the banner dismiss link.
				 * @param array $banner_info The full banner info for this banner.
				 *
				 * @return string $banner_dismiss_link_html The HTML to render.
				 */
				$banner_dismiss_link_html = apply_filters( 'swsales_banner_dismiss_link_html', $banner_dismiss_link_html, $banner_info );

				// Get the landing page URL.
				$landing_page_id = $active_sitewide_sale->get_landing_page_post_id();
				if ( ! empty( $landing_page_id ) ) {
					$landing_page_url = get_permalink( $landing_page_id );
				}

				ob_start();
				?>
				<div id="swsales-banner-<?php echo esc_attr( $banner_location_nicename ); ?>" class="swsales-banner" style="display: none;">
					<div class="swsales-banner-inner">
						<?php
						switch ( $name ) {
							case 'show_top_banner':
								echo $banner_dismiss_link_html;
								?>
								<p class="swsales-banner-title"><?php echo wp_kses_post( $banner_info['title'] ); ?></p>
								<p class="swsales-banner-content"><?php echo apply_filters( 'swsales_banner_text', $banner_info['text'], 'top', $active_sitewide_sale ); ?></p>
								<?php do_action( 'swsales_before_banner_button', $active_sitewide_sale ); ?>
								<?php
									if ( ! empty( $landing_page_url ) && ! is_wp_error( $landing_page_url ) ) { ?>
										<span class="swsales-banner-button-wrap"><a class="swsales-banner-button" href="<?php echo esc_url( $landing_page_url ); ?>"><?php echo wp_kses_post( $banner_info['button_text'] ); ?></a></span>
										<?php
									}
								?>
								<?php
								break;
							case 'show_bottom_banner':
								echo $banner_dismiss_link_html;
								?>
								<div class="swsales-banner-inner-left">
									<p class="swsales-banner-title"><?php echo wp_kses_post( $banner_info['title'] ); ?></p>
									<p class="swsales-banner-content"><?php echo apply_filters( 'swsales_banner_text', $banner_info['text'], 'bottom', $active_sitewide_sale ); ?></p>
								</div>
								<div class="swsales-banner-inner-right">
									<?php do_action( 'swsales_before_banner_button', $active_sitewide_sale ); ?>
									<?php
										if ( ! empty( $landing_page_url ) ) { ?>
											<span class="swsales-banner-button-wrap"><a class="swsales-banner-button" href="<?php echo esc_url( $landing_page_url ); ?>"><?php echo wp_kses_post( $banner_info['button_text'] ); ?></a></span>
											<?php
										}
									?>
								</div>
								<?php
								break;
							case 'show_bottom_right_banner':
								echo $banner_dismiss_link_html;
								?>
								<p class="swsales-banner-title"><?php echo wp_kses_post( $banner_info['title'] ); ?></p>
								<p class="swsales-banner-content"><?php echo apply_filters( 'swsales_banner_text', $banner_info['text'], 'bottom_right', $active_sitewide_sale ); ?></p>
								<?php do_action( 'swsales_before_banner_button', $active_sitewide_sale ); ?>
								<?php
									if ( ! empty( $landing_page_url ) && ! is_wp_error( $landing_page_url ) ) { ?>
										<span class="swsales-banner-button-wrap"><a class="swsales-banner-button" href="<?php echo esc_url( $landing_page_url ); ?>"><?php echo wp_kses_post( $banner_info['button_text'] ); ?></a></span>
										<?php
									}
								?>
								<?php
								break;
						}
						?>
					</div> <!-- end swsales-banner-inner -->
				</div> <!-- end swsales-banner -->
				<?php

				$content = ob_get_contents();
				ob_end_clean();

				// Filter for templates to modify the banner content.
				if ( ! empty( $banner_info['template'] ) ) {
					$content = apply_filters( 'swsales_banner_content_' . $banner_info['template'], $content, $banner_info['location'] );
				}

				// Filter for themes and plugins to modify the banner content.
				$content = apply_filters( 'swsales_banner_content', $content, $banner_info['template'], $banner_info['location'] );

				// Echo the banner content.	
				echo $content;
				break;
			default:
				// Throw exception if method not supported.
				throw new Exception('The ' . $name . ' method is not supported.');
		}
	}

	/**
	 * Returns a human-readable name for this module.
	 *
	 * @return string
	 */
	protected static function get_module_label() {
		return __( 'Custom Banner', 'sitewide-sales' );
	}

	/**
	 * Returns whether the plugin associaited with this module is active.
	 *
	 * @return bool
	 */
	protected static function is_module_active() {
		return true;
	}

	/**
	 * Echos the HTML for the settings that should be displayed
	 * if this module is active and selected while editing a
	 * sitewide sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sale being edited.
	 */
	public static function echo_banner_settings_html_inner( $sitewide_sale ) {
		// Gather information information needed to display settings.
		$banner_info          = self::get_banner_info( $sitewide_sale );
		$registered_locations = self::get_registered_banners();

		$registered_templates = \Sitewide_Sales\classes\SWSales_Templates::get_templates();
		$registered_templates = apply_filters( 'swsales_banner_templates', $registered_templates );
		?>
		<tr>
			<th scope="row" valign="top"><label><?php esc_html_e( 'Banner Location', 'sitewide-sales' ); ?></label></th>
			<td>
				<select class="use_banner_select swsales_option" id="swsales_banner_location_select" name="swsales_banner_location">
					<?php
					foreach ( $registered_locations as $registered_location_slug => $registered_location_data ) {
						if ( is_string( $registered_location_slug ) && is_array( $registered_location_data ) && ! empty( $registered_location_data['option_title'] ) && is_string( $registered_location_data['option_title'] ) ) {
							echo '<option value="' . esc_attr( $registered_location_slug ) . '"' . selected( $banner_info['location'], $registered_location_slug ) . '>' . esc_html( $registered_location_data['option_title'] ) . '</option>';
						}
					}
					?>
				</select>
				<input type="submit" class="button button-secondary" id="swsales_preview" name="swsales_preview" value="<?php esc_attr_e( 'Save and Preview', 'sitewide-sales' ); ?>">
				<p class="description"><?php esc_html_e( 'Optionally display a banner, which you can customize using additional settings below, to advertise your sale.', 'sitewide-sales' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="swsales_banner_template"><?php esc_html_e( 'Banner Template', 'sitewide-sales' ); ?></label></th>
			<td>
				<select class="banner_select_template swsales_option" id="swsales_banner_template" name="swsales_banner_template">
					<option value="0"><?php esc_html_e( 'None', 'sitewide-sales' ); ?></option>
					<?php
					foreach ( $registered_templates as $registered_template_slug => $registered_template_label ) {
						echo '<option value="' . esc_attr( $registered_template_slug ) . '" ' . selected( $banner_info['template'], $registered_template_slug ) . '>' . esc_html( $registered_template_label ) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="swsales_banner_title"><?php esc_html_e( 'Banner Title', 'sitewide-sales' ); ?></label></th>
			<td>
				<input type="text" name="swsales_banner_title" value="<?php echo stripslashes( $banner_info['title'] ); ?>">
				<p class="description"><?php esc_html_e( 'A brief title for your sale, such as the holiday or purpose of the sale. (i.e. "Limited Time Offer")', 'sitewide-sales' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="swsales_banner_text"><?php esc_html_e( 'Banner Text', 'sitewide-sales' ); ?></label></th>
			<td>
				<textarea class="swsales_option" id="swsales_banner_text" name="swsales_banner_text"><?php echo stripslashes( $banner_info['text'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'A brief message about your sale. (i.e. "Save 50% on membership through December.")', 'sitewide-sales' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label><?php esc_html_e( 'Button Text', 'sitewide-sales' ); ?></label></th>
			<td>
				<input class="swsales_option" type="text" name="swsales_banner_button_text" value="<?php echo esc_attr( $banner_info['button_text'] ); ?>">
				<p class="description"><?php esc_html_e( 'The text displayed on the button of your banner that links to the Landing Page. If you do not set a landing page, no button will be shown.', 'sitewide-sales' ); ?></p>
			</td>
		</tr>
		<tr class="swsales-row-trigger">
			<th></th>
			<td>
				<button class="swsales-row-trigger-button" type="button">
					<?php esc_html_e( '+ Add custom banner CSS', 'sitewide-sales' ); ?>
				</button>
			</td>
		</tr>
		<tr style="display: none;">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Custom Banner CSS', 'sitewide-sales' ); ?></label></th>
			<td>
				<textarea class="swsales_option" name="swsales_banner_css"><?php echo esc_textarea( $banner_info['css'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Optional. Use this area to add custom styles to modify the banner appearance.', 'sitewide-sales' ); ?></p>

				<p id="swsales_css_selectors_description" class="description"><?php esc_html_e( 'Use these selectors to alter the appearance of your banners.', 'sitewide-sales' ); ?></p>
				<?php foreach ( $registered_locations as $registered_location_slug => $registered_location_data ) { ?>
					<div data-swsales-banner="<?php echo esc_attr( $registered_location_slug ); ?>" class="swsales_banner_css_selectors" style="display: none">
						<?php
						$css_selectors = $registered_location_data['css_selectors'];
						if ( is_string( $css_selectors ) ) {
							echo $css_selectors;
						} elseif ( is_array( $css_selectors ) ) {
							foreach ( $css_selectors as $css_selector ) {
								if ( is_string( $css_selector ) ) {
									echo $css_selector . ' { }<br/>';
								}
							}
						}
						?>
					</div>
				<?php } ?>
			</td>
		</tr>
		<script>
			jQuery(document).ready(function() {
				swsales_prep_click_events();
			});

			// Function to prep click events for admin settings.
			function swsales_prep_click_events() {
				jQuery( 'button.swsales-row-trigger-button' ).on( 'click', function(event){
					// Toggle content within the settings sections boxes.
					event.preventDefault();

					let thebutton = jQuery(event.target).parents('.swsales-row-trigger').find('button.swsales-row-trigger-button');
					let sectionshow = jQuery( thebutton ).parents('.swsales-row-trigger').next('tr');
					let sectionhide = jQuery(event.target).parents('.swsales-row-trigger');

					jQuery( sectionshow ).show();
					jQuery( sectionhide ).hide();
				});
			}
		</script>
		<?php
	}

	/**
	 * Saves settings shown by echo_banner_settings_html_inner().
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post The post being saved.
	 */
    protected static function save_banner_settings( $post_id, $post ) {
		if ( isset( $_POST['swsales_banner_location'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_location', sanitize_text_field( $_POST['swsales_banner_location'] ) );
			delete_post_meta( $post_id, 'swsales_use_banner' );
		}

		if ( isset( $_POST['swsales_banner_template'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_template', sanitize_text_field( $_POST['swsales_banner_template'] ) );
		}

		if ( ! empty( $_POST['swsales_banner_title'] ) ) {
			$swsales_banner_title = wp_kses_post( wp_unslash( $_POST['swsales_banner_title'] ) );
			update_post_meta( $post_id, 'swsales_banner_title', $swsales_banner_title );
		} elseif ( isset( $_POST['swsales_banner_title'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_title', $post->post_title );
		}

		if ( isset( $_POST['swsales_banner_text'] ) ) {
			$swsales_banner_text = wp_kses_post( wp_unslash( $_POST['swsales_banner_text'] ) );
			update_post_meta( $post_id, 'swsales_banner_text', $swsales_banner_text );
		}

		if ( ! empty( $_POST['swsales_banner_button_text'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_button_text', wp_kses_post( stripslashes( $_POST['swsales_banner_button_text'] ) ) );
			delete_post_meta( $post_id, 'swsales_link_text' );
		} elseif ( isset( $_POST['swsales_banner_button_text'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_button_text', 'Buy Now' );
			delete_post_meta( $post_id, 'swsales_link_text' );
		}

		if ( isset( $_POST['swsales_banner_css'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_css', wp_kses_post( stripslashes( $_POST['swsales_banner_css'] ) ) );
			delete_post_meta( $post_id, 'swsales_css_option' );
		}
	}

	/**
	 * Get banner info for the given sitewide sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sitewide sale to get the banner info for.
	 * @return array The banner info.
	 */
	private static function get_banner_info( $sitewide_sale ) {
		$banner_info = array();
		$banner_info['module'] = $sitewide_sale->get_meta_value( 'swsales_banner_module' );
		$banner_info['location'] = $sitewide_sale->get_meta_value( 'swsales_banner_location' );
		if ( null === $banner_info['location'] ) {
			// If we don't have a banner location, try to get it from legacy setup.
			$banner_info['location'] = $sitewide_sale->get_meta_value( 'swsales_use_banner', '' );
		}
		// Update location in case we are previewing.
		if ( ! is_admin() && current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner_type'] ) ) {
			$banner_info['location'] = $_REQUEST['swsales_preview_sale_banner_type'];
		}

		$banner_info['template'] = $sitewide_sale->get_meta_value( 'swsales_banner_template', '' );
		$banner_info['title'] = $sitewide_sale->get_meta_value( 'swsales_banner_title', 'Limited Time Offer' );
		$banner_info['text'] = $sitewide_sale->get_meta_value( 'swsales_banner_text' );
		if ( null === $banner_info['text'] ) {
			// If we don't have banner text, try to get it from legacy setup.
			// Note, this is the content of the Sitewide Sale CPT so we can just get meta.
			$banner_info['text'] = $sitewide_sale->get_banner_text();
		}

		$banner_info['button_text'] = $sitewide_sale->get_meta_value( 'swsales_banner_button_text' );
		if ( null === $banner_info['button_text'] ) {
			// If we don't have banner button text, try to get it from legacy setup.
			$banner_info['button_text'] = $sitewide_sale->get_meta_value( 'swsales_link_text', 'Buy Now' );
		}

		$banner_info['css'] = $sitewide_sale->get_meta_value( 'swsales_banner_css' );
		if ( null === $banner_info['css'] ) {
			// If we don't have banner CSS, try to get it from legacy setup.
			$banner_info['css'] = $sitewide_sale->get_meta_value( 'swsales_css_option', '' );
		}

		$banner_info['close_behavior'] = $sitewide_sale->get_meta_value( 'swsales_banner_close_behavior' );

		return $banner_info;
	}

	/**
	 * Gets info about available banners including name and available
	 * css selectors.
	 *
	 * @return array banner_name => array( option_title=>string, callback=>string, css_selctors=>array(strings) )
	 */
	private static function get_registered_banners() {

		$registered_banners = array(
			'bottom_right' => array(
				'option_title'  => __( 'Bottom Right of Site', 'sitewide-sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_right_banner' ),
				'css_selectors' => array(
					'#swsales-banner-bottom-right',
					'#swsales-banner-bottom-right .swsales-dismiss',
					'#swsales-banner-bottom-right .swsales-banner-title',
					'#swsales-banner-bottom-right .swsales-banner-content',
					'#swsales-banner-bottom-right .swsales-banner-button-wrap',
					'#swsales-banner-bottom-right .swsales-banner-button',
				),
			),
			'bottom'       => array(
				'option_title'  => __( 'Bottom of Site', 'sitewide-sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_banner' ),
				'css_selectors' => array(
					'#swsales-banner-bottom',
					'#swsales-banner-bottom .swsales-dismiss',
					'#swsales-banner-bottom .swsales-banner-title',
					'#swsales-banner-bottom .swsales-banner-content',
					'#swsales-banner-bottom .swsales-banner-button-wrap',
					'#swsales-banner-bottom .swsales-banner-button',
					'#swsales-banner-bottom .swsales-banner-inner',
					'#swsales-banner-bottom .swsales-banner-inner-left',
					'#swsales-banner-bottom .swsales-banner-inner-right',
				),
			),
			'top'          => array(
				'option_title'  => __( 'Top of Site', 'sitewide_Sales' ),
				'callback'      => array( __CLASS__, 'hook_top_banner' ),
				'css_selectors' => array(
					'#swsales-banner-top',
					'#swsales-banner-top .swsales-banner-title',
					'#swsales-banner-top .swsales-banner-content',
					'#swsales-banner-top .swsales-banner-button-wrap',
					'#swsales-banner-top .swsales-banner-button',
				),
			),
		);

		/**
		 * Modify Registered Banners
		 *
		 * @since 0.0.1
		 *
		 * @param array $registered_banners contains all currently registered banners.
		 */
		$registered_banners = apply_filters( 'swsales_registered_banners', $registered_banners );

		return $registered_banners;
	}
}
SWSales_Banner_Module_SWSales::init();