<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Settings {

	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sales/includes
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Init settings page
	 */
	public static function admin_init() {
		register_setting( 'swsales-group', 'swsales_sitewide_sales', array( __CLASS__, 'validate' ) );
	}

	/**
	 * Get the Sitewide Sale Options
	 *
	 * @return array [description]
	 */
	public static function get_options() {
		static $options;

		if ( empty( $options ) ) {
			$options = get_option( 'swsales_options' );

			// Set the defaults.
			if ( empty( $options ) || ! array_key_exists( 'active_sitewide_sale_id', $options ) ) {
				$options = self::reset_options();
			}
		}
		return $options;
	}

	/**
	 * Sets SWSales settings to default
	 */
	public static function reset_options() {
		return array(
			'active_sitewide_sale_id' => 0,
		);
	}

	/**
	 * Save options
	 *
	 * @param array $options contains information about sale to be saved.
	 */
	public static function save_options( $options ) {
		update_option( 'swsales_options', $options, 'no' );
	}

	/**
	 * Validates sitewide sale options
	 *
	 * @param  array $input info to be validated.
	 */
	public static function validate( $input ) {
		$options = self::get_options();
		if ( ! empty( $input['active_sitewide_sale_id'] ) && '-1' !== $input['active_sitewide_sale_id'] ) {
			$options['active_sitewide_sale_id'] = trim( $input['active_sitewide_sale_id'] );
		} else {
			$options['active_sitewide_sale_id'] = 0;
		}
		return $options;
	}

	/**
	 * Is the current page the active sitewide sale landing page?
	 */
	public static function is_active_sitewide_sale_landing_page( $post_id = false ) {
		global $post;

		// default to global post
		if ( empty( $post_id ) ) {
			$post_id = $post->ID;
		}

		if ( empty( $post_id ) ) {
			return false;
		}

		$options = self::get_options();

		if ( empty( $options['active_sitewide_sale_id'] ) ) {
			return false;
		}

		$landing_page_id = get_post_meta( $options['active_sitewide_sale_id'], 'swsales_landing_page_post_id', true );

		if ( ! empty( $landing_page_id ) && $landing_page_id == $post_id ) {
			return true;
		} else {
			return false;
		}
	}
}
