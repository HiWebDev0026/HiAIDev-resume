<?php

class SWSales_Banner_Module_PUM extends SWSales_Banner_Module {
	/**
	 * Set up the module.
	 */
	public static function init() {
		parent::init();

		// Make sure that Popup Maker is active.
		if ( ! self::is_module_active() ) {
			return;
		}

		// Prevent popups from showing when they shouldn't.
		add_filter( 'pum_popup_is_loadable', array( __CLASS__, 'filter_pum_popup_is_loadable' ), 10, 2 );

		// Show if a popup is linked to a sitewide sale in the popup's WP List Table.
		add_filter( 'pum_popup_columns', array( __CLASS__, 'add_list_table_column' ) );
		add_action( 'manage_posts_custom_column', array( __CLASS__, 'render_list_table_column' ), 10, 2 );
	}

	/**
	 * Filters whether a popup is loadable so that we can hide popups that
	 * are linked to inactive sales.
	 *
	 * @param bool   $loadable Whether the popup is loadable.
	 * @param int    $popup_id The ID of the popup.
	 * @return bool Whether the popup is loadable.
	 */
	public static function filter_pum_popup_is_loadable( $loadable, $popup_id ) {
		// If the popup is already not loadable, don't do anything.
		if ( empty( $loadable ) ) {
			return false;
		}
	
		// Get all the sitewide sales that this popup is linked to.
		$sitewide_sales_using_this_popup = self::get_sitewide_sales_using_this_popup( $popup_id );

		// If no sitewide sales are using this popup, then it's still loadable.
		if ( empty( $sitewide_sales_using_this_popup ) ) {
			return true;
		}

		// If one of the sitewide sales is active, then the popup is loadable.
		foreach ( $sitewide_sales_using_this_popup as $sitewide_sale_cpt ) {
			$sitewide_sale = Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_sitewide_sale( $sitewide_sale_cpt->ID );
			if ( ! empty( $sitewide_sale ) && self::banner_should_be_shown( $sitewide_sale ) ) {
				return true;
			}
		}

		// If we made it this far, then the popup is linked to an inactive sitewide sale and should not be shown.
		return false;
	}

	/**
	 * Adds a column to the WP List Table for popups that are linked to sitewide sales.
	 *
	 * @param array $columns The columns to display in the WP List Table.
	 * @return array The columns to display in the WP List Table.
	 */
	public static function add_list_table_column( $columns ) {
		$columns["swsale"] = "Linked to Sitewide Sale";
		return $columns;
	}

	/**
	 * Render Columns
	 *
	 * @param string $column_name Column name
	 * @param int    $post_id     (Post) ID
	 */
	public static function render_list_table_column( $column_name, $post_id ) {
		$post = get_post( $post_id );
		if ( 'popup' === $post->post_type && 'swsale' === $column_name ) {
			$sitewide_sales_using_this_popup = self::get_sitewide_sales_using_this_popup( $post_id );
			if ( ! empty( $sitewide_sales_using_this_popup ) ) {
				$sitewide_sale_to_link_to = \Sitewide_Sales\Classes\SWSales_Sitewide_Sale::get_sitewide_sale( $sitewide_sales_using_this_popup[0]->ID );
				echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $sitewide_sale_to_link_to->get_id() . '&action=edit' ) ) . '">' . $sitewide_sale_to_link_to->get_name() . '</a>';
			}
		}
	}

    /**
	 * Returns a human-readable name for this module.
	 *
	 * @return string
	 */
	protected static function get_module_label() {
        return 'Popup Maker';
    }

	/**
	 * Returns whether the plugin associaited with this module is active.
	 *
	 * @return bool
	 */
	protected static function is_module_active() {
        return function_exists( 'pum' );
    }

	/**
	 * Echos the HTML for the settings that should be displayed
	 * if this module is active and selected while editing a
	 * sitewide sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale The sale being edited.
	 */
	public static function echo_banner_settings_html_inner( $sitewide_sale ) {
		// Make sure that Popup Maker is active.
		if ( ! self::is_module_active() ) {
			return;
		}
		
		$all_popups = pum()->popups->get_items();
		$selected_popup = $sitewide_sale->swsales_banner_pum_popup_id;
		?>
		<tr>
			<th scope="row" valign="top"><label><?php esc_html_e( 'Popup', 'sitewide-sales' ); ?></label></th>
			<td>
				<select class="swsales_option" id="swsales_banner_pum_popup_id" name="swsales_banner_pum_popup_id">
					<option></option>
					<?php
					foreach ( $all_popups as $popup ) {
						echo '<option value="' . esc_attr( $popup->ID ) . '"' . selected( $selected_popup, $popup->ID ) . '>' . esc_html( $popup->post_title ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select a Popup Maker popup to use for this sale.', 'sitewide-sales' ); ?></p>
			</td>
		</tr>
		<?php
    }

	/**
	 * Saves settings shown by echo_banner_settings_html_inner().
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post The post being saved.
	 */
	protected static function save_banner_settings( $post_id, $post ) {
		if ( isset( $_POST['swsales_banner_pum_popup_id'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_pum_popup_id', sanitize_text_field( $_POST['swsales_banner_pum_popup_id'] ) );
		}
    }

	/**
	 * Get an array of Sitewide Sales that are linked to this popup.
	 *
	 * @param int $popup_id The ID of the popup.
	 * @return array An array of Sitewide Sales that are linked to this popup.
	 */
	private static function get_sitewide_sales_using_this_popup( $popup_id ) {
		// Get all the sitewide sales that this popup is linked to.
		return get_posts( array(
			'post_type'      => 'sitewide_sale',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'swsales_banner_module',
					'value'   => __CLASS__,
					'compare' => '=',
				),
				array(
					'key'     => 'swsales_banner_pum_popup_id',
					'value'   => $popup_id,
					'compare' => '=',
				),
			),
		) );
	}
}
SWSales_Banner_Module_PUM::init();