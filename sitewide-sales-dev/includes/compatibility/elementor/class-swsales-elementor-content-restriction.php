<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class SWSales_Elementor_Sale_Content_Restriction extends SWSales_Elementor {
	protected function sale_content_restriction() {
		// Setup controls
		$this->register_controls();

		// Filter elementor render_content hook
		add_action( 'elementor/widget/render_content', array( $this, 'swsales_elementor_render_content' ), 10, 2 );
		add_action( 'elementor/frontend/section/should_render', array( $this, 'swsales_elementor_should_render' ), 10, 2 );
	}

	// Register controls to sections and widgets
	protected function register_controls() {
		foreach ( $this->locations as $where ) {
			add_action('elementor/element/'.$where['element'].'/'.$this->section_name.'/before_section_end', array( $this, 'add_controls' ), 10, 2 );
		}
	}

	// Define controls
	public function add_controls( $element, $args ) {
		$element->add_control(
			'swsales_sale_period_heading', array(
				'label' => __( 'Sale Period', 'sitewide-sales' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$element->add_control(
			'swsales_sale_period', array(
				'type'=> Controls_Manager::SELECT,
				'options' => array(
					'' => __( 'Always', 'sitewide-sales' ),
					'pre-sale' => __( 'Before Sale', 'sitewide-sales' ),
					'sale' => __( 'During Sale', 'sitewide-sales' ),
					'post-sale' => __( 'After Sale', 'sitewide-sales' )
				),
				'label_block' => 'true',
				'description' => __( 'Select the sale period this content is visible for.', 'sitewide-sales' ),
			)
		);
	}

	/**
	 * Filter sections to render sale content or not.
	 * If sale period doesn't match setting, hide the section.
	 * @return boolean whether to show or hide section.
	 * @since 1.3.0
	 */
	public function swsales_elementor_should_render( $should_render, $element ) {

		// Don't hide content in editor mode.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $should_render;
		}

		// Bypass if it's already hidden.
		if ( $should_render === false ) {
			return $should_render;
		}
		
		// Checks if the element is restricted by sale period and whether it should show based on active sale period.
		$should_render = $this->swsales_elementor_has_sale_period( $element );

		return apply_filters( 'swsales_elementor_section_access', $should_render, $element );
	}

	/**
	 * Filter individual content by sale period.
	 * @return string Returns the content set from Elementor.
	 * @since 1.3.0
	 */
	public function swsales_elementor_render_content( $content, $widget ){

		// Don't hide content in editor mode.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $content;
		}

		$show = $this->swsales_elementor_has_sale_period( $widget );
		
		if ( ! $show ) {
			$content = '';
		}

		return $content;
	}

	/**
	 * Figure out if the active sale period is equal to the selected content period.
	 * @return bool True or false based if the sale content should be shown or not.
	 * @since 1.3.0
	 */
	public function swsales_elementor_has_sale_period( $element ) {

		$element_settings = $element->get_active_settings();

		$sale_period_setting = $element_settings['swsales_sale_period'];

		// Just bail if the content isn't restricted by sale period at all.
		if ( ! $sale_period_setting ) {
			return true;
		}

		// Assume content is visible.
		$is_visible = true;

		// Is this post a sale landing page? If so, load the sale.
		$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'swsales_sitewide_sale_id', true );
		if ( ! empty( $sitewide_sale_id ) ) {
			$sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
			$sale_found = $sitewide_sale->get_sitewide_sale( $sitewide_sale_id );
			$sitewide_sale = $sale_found;
		}

		// Or, try to load the active sale.
		if ( empty( $sale_found ) ) {
			$sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
			$sale_found = $sitewide_sale->get_active_sitewide_sale();
			$sitewide_sale = $sale_found;
		}

		// Still no sale? Return nothing and don't render the content.
		if ( ! $sale_found ) {
			return;
		}

		// Get the time period for the sale based on sale settings and current date.
		$sale_period = $sitewide_sale->get_time_period();

		// Allow admins to preview the sale period using a URL attribute.
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_time_period'] ) ) {
			$sale_period = $_REQUEST['swsales_preview_time_period'];
		}
		// If the builder's setting for period does not match the sale period, set to false.
		if ( $sale_period != $sale_period_setting ) {
			$is_visible = false;
		}

		return apply_filters( 'swsales_elementor_has_sale_period', $is_visible, $element, $sale_period );
	}
}

new SWSales_Elementor_Sale_Content_Restriction;
