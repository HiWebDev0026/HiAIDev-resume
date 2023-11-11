<?php
/**
 * Add restriction options to Elementor Widgets For Sitewide Sales.
 *
 * @since 1.3.0
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class SWSales_Elementor {
    private static $_instance = null;

    public $locations = array(
        array(
            'element' => 'common',
            'action'  => '_section_style',
        ),
        array(
            'element' => 'section',
            'action'  => 'section_advanced',
        )
    );
    public $section_name = 'swsales_elementor_section';

	/**
	 * Register new section for Sale Content.
	 */
	public function __construct() {
        
        require_once( __DIR__ . '/class-swsales-elementor-content-restriction.php' );
        // Register new section to display restriction controls
        $this->register_sections();

        $this->sale_content_restriction();
	}

    /**
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return SWSales_Elementor An instance of the class.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();

        return self::$_instance;
    }

    private function register_sections() {
        foreach( $this->locations as $where ) {
            add_action( 'elementor/element/'.$where['element'].'/'.$where['action'].'/after_section_end', array( $this, 'add_section' ), 10, 2 );
        }
    }

    public function add_section( $element, $args ) {
        $exists = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $this->section_name );

        if( ! is_wp_error( $exists ) )
            return false;

        $element->start_controls_section(
            $this->section_name, array(
                'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
                'label' => __( 'Sitewide Sales', 'sitewide-sales' ),
            )
        );

        $element->end_controls_section();
    }

    protected function sale_content_restriction(){}
}

// Instantiate Plugin Class
SWSales_Elementor::instance();
