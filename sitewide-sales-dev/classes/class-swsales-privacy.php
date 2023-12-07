<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Privacy {

    /**
     * Initialize the class and all it's functions.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'add_privacy_policy_content' ) );
    }

    public static function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content') ) {
            return;
        }

		$content = '<p class="privacy-policy-tutorial">' . esc_html__( 'This sample language includes the basics around what personal data your Sitewide Sale may be collecting, storing, and sharing, as well as who may have access to that data. Depending on what settings are enabled and which integrated plugin is used, the specific information shared by your Sitewide Sale will vary. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.', 'sitewide-sales' ) .'</p>';

		$content .= '<h2>' . esc_html__( 'What we collect and store', 'sitewide-sales' ) . '</h2>';

		$content .= '<p>';
		$content .= '<strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'sitewide-sales' ) . '</strong>';
        $content .= esc_html__( "While a sale is active, we will track:", 'sitewide-sales' );
        $content .= '</p>';

        $content .= '<ul>';
		$content .= '<li>' . esc_html__( 'Sale Banners you view: We store a numeric value in a cookie. This data is used to report on sale performance.', 'sitewide-sales' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Landing pages you visit: We store a numeric value in a cookie. This data is used to report on sale performance.', 'sitewide-sales' ) . '</li>';
        $content .= '<li>' . esc_html__( 'Purchases you complete through a sale: We store a numeric value in a cookie. This data is used to link the purchase to the sale for reporting.', 'sitewide-sales' ) . '</li>';
        $content .= '</ul>';

        $content .= '<h2>' . esc_html__( 'Who has access to sale information', 'sitewide-sales' ) . '</h2>';
        $content .= '<p>' . esc_html__( 'Administrators of our site have access to view sale reports. These reports include aggregate data that is non-personalized, including conversion rates, number of banner or landing page views, and total sale revenue data.', 'sitewide-sales' );
        

        wp_add_privacy_policy_content( 'Sitewide Sales', $content );
    }

} // End of Class