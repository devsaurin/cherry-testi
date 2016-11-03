<?php
/**
 * Plugin options API.
 *
 * @package    Cherry_Testi
 * @subpackage Public
 * @author     Template Monster
 * @license    GPL-3.0+
 * @copyright  2002-2016, Template Monster
 */

/**
 * Class for Testimonials admin functionality.
 *
 * @since 1.0.0
 */
class TM_Testimonials_Options {

	/**
	 * Holds the instances of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * Holds the instances of `Cherry Interface Builder` class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private $builder = null;

	/**
	 * Sets up needed actions/filters for the admin to initialize.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {
		$hook_suffix = $this->get_page_hook_suffix();

		add_action( "load-{$hook_suffix}", array( $this, 'init_modules' ) );
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Run initialization for required modules.
	 *
	 * @since 1.0.0
	 */
	public function init_modules() {
		$this->builder = tm_testimonials_plugin()->get_core()->init_module( 'cherry-interface-builder' );

		tm_testimonials_plugin()->get_core()->init_module( 'cherry-js-core' );
	}

	/**
	 * Add a settings page to `Testimonials` menu item.
	 *
	 * @since 1.0.0
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=' . tm_testimonials_plugin()->get_post_type_name(),
			esc_html__( 'Settings', 'cherry-testi' ),
			esc_html__( 'Settings', 'cherry-testi' ),
			'manage_options',
			$this->get_menu_slug(),
			array( $this, 'settings_callback' )
		);
	}

	/**
	 * Output the content for settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_callback() {
		$this->builder->register_form( array(
			'cherry-testi-option-form' => array(
				'type' => 'form',
			),
		) );

		$this->builder->register_section( array(
			'general_section' => array(
				'type'   => 'section',
				'parent' => 'cherry-testi-option-form',
				'scroll' => false,
				'title'  => sprintf( '<span class="dashicons dashicons-admin-settings"></span> %s', esc_html__( 'Settings', 'cherry-testi' ) ),
			),
		) );

		$this->builder->register_settings( array(
			'general_settings' => array(
				'type'   => 'settings',
				'parent' => 'general_section',
				'title'  => esc_html__( 'General', 'cherry-testi' ),
			),
		) );

		$this->builder->register_control( array(
			'archive_page' => array(
				'type'        => 'select',
				'parent'      => 'general_settings',
				'title'       => esc_html__( 'Testimonails archive page', 'cherry-testi' ),
				'multiple'    => false,
				'filter'      => true,
				'value'       => $this->get_option( 'archive_page' ),
				'options'     => $this->get_pages(),
			),
			'posts_per_page' => array(
				'type'        => 'stepper',
				'parent'      => 'general_settings',
				'title'       => esc_html__( 'Posts number per archive page', 'cherry-testi' ),
				'value'       => $this->get_option( 'posts_per_page' ),
				'max_value'   => 100,
				'min_value'   => 1,
				'step_value'  => 1,
			),
		) );

		$this->builder->register_settings( array(
			'cherry-testi-option-form__buttons' => array(
				'type'   => 'settings',
				'parent' => 'general_section',
			),
		) );

		$this->builder->register_control( array(
			'cherry-testi-option-form__reset' => array(
				'type'          => 'button',
				'parent'        => 'cherry-testi-option-form__buttons',
				'content'       => esc_html__( 'Reset', 'cherry-testi' ),
				'view_wrapping' => false,
				'form'          => 'cherry-testi-option-form',
			),
			'cherry-testi-option-form__save' => array(
				'type'          => 'button',
				'parent'        => 'cherry-testi-option-form__buttons',
				'style'         => 'success',
				'content'       => esc_html__( 'Save', 'cherry-testi' ),
				'view_wrapping' => false,
				'form'          => 'cherry-testi-option-form',
			),
		) );

		$this->builder->render();
	}

	/**
	 * Enqueue assets files for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets( $hook_suffix ) {

		if ( $hook_suffix != $this->get_page_hook_suffix() ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'cherry-testi-admin',
			TM_TESTI_URI . "admin/assets/js/admin{$min}.js",
			array( 'cherry-js-core', 'cherry-handler-js' ),
			TM_TESTI_VERSION,
			true
		);

		wp_enqueue_style(
			'cherry-testi-admin',
			TM_TESTI_URI . 'admin/assets/css/admin.css',
			array(),
			TM_TESTI_VERSION,
			'all'
		);
	}

	/**
	 * Retrieve a set of defaults option.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_option_defaults() {
		return apply_filters( 'tm_testimonials_option_defaults', array(
			'archive_page'   => 0,
			'posts_per_page' => TM_Testimonials_Page_Template::$posts_per_page,
		) );
	}

	/**
	 * Retrieve a option value by name (key).
	 *
	 * @since  1.0.0
	 * @param  string $name Option key.
	 * @return mixed
	 */
	public function get_option( $name ) {
		$options = wp_parse_args(
			get_option( 'cherry-testi', array() ),
			$this->get_option_defaults()
		);

		return ! empty( $options[ $name ] ) ? $options[ $name ] : false;
	}

	/**
	 * Retrieve a set of all pages (key - page slug, value - page title).
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_pages() {
		$all_pages = get_pages( apply_filters( 'tm_testimonials_get_pages_args', array(
				'hierarchical' => 1,
				'parent'       => -1,
				'post_status'  => 'publish',
			)
		) );

		$pages = array( esc_attr__( '&mdash;&nbsp;Select&nbsp;&mdash;', 'cherry-testi' ) );

		foreach ( $all_pages as $page ) {
			$pages[ $page->post_name ] = $page->post_title;
		}

		return $pages;
	}

	/**
	 * Retrieve a settings admin page.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_page_hook_suffix() {
		$hook_suffix = tm_testimonials_plugin()->get_post_type_name() . '_page_' . $this->get_menu_slug();

		return apply_filters( 'tm_testimonials_hook_suffix', $hook_suffix );
	}

	/**
	 * Retrieve a slug for settings page.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_menu_slug() {
		return apply_filters( 'tm_testimonials_menu_slug', 'settings' );
	}

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}

TM_Testimonials_Options::get_instance();

if ( ! function_exists( 'tm_testimonials_plugin_get_option') ) {

	/**
	 * Wrapper-function for retrieve a option value by name (key).
	 *
	 * @since  1.0.0
	 * @param  string $name Option key.
	 * @return mixed
	 */
	function tm_testimonials_plugin_get_option( $name ) {
		$instance = TM_Testimonials_Options::get_instance();

		return $instance->get_option( $name );
	}
}

if ( ! function_exists( 'tm_testimonials_plugin_get_defaults_option') ) {

	/**
	 * Wrapper-function for retrieve a defaults option.
	 *
	 * @since  1.0.0
	 * @param  string $name Option key.
	 * @return mixed
	 */
	function tm_testimonials_plugin_get_defaults_option() {
		$instance = TM_Testimonials_Options::get_instance();

		return $instance->get_option_defaults();
	}
}