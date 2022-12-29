<?php
/**
 * GOSTUDY setup
 *
 * @package GOSTUDY
 * @since   3.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GOSTUDY_Admin_Config
 */
final class GOSTUDY {


	/**
	 * TODO: Doc instance function
	 *
	 * @var string
	 */
	protected static $_instance = null;
	/**
	 * TODO: Doc instance function
	 *
	 * @var string
	 */
	public $version = '1.0.0';
	/**
	 * TODO: Doc instance function
	 *
	 * @var string
	 */
	public $plugin_name = 'gostudy';
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	public function __construct() {
		add_action( 'admin_init', array( $this, 'initialize' ) );

	}
	public function initialize() {
		add_action( 'admin_enqueue_scripts', array( $this, 'gostudy_admin_styles_modifier' ) );
		if ( $this->current_user_role_has( 'wpamelia-manager' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'gostudy_role_manager_style_modifier' ) );
			add_action( 'wp_before_admin_bar_render', array( $this, 'remove_wp_admin_bar_my_account' ), 999 );
			add_action( 'admin_bar_menu', array( $this, 'remove_wp_admin_bar_new_content' ), 999 );
		}
	}
	public function gostudy_admin_styles_modifier() {
		wp_enqueue_style(
			'gostudy-custom-style',
			plugins_url( '/assets/public/css/gostudy-custom-style.css', GOSTUDY_PLUGIN_FILE ),
			array(),
			'1.0.2'
		);
	}
	public function gostudy_role_manager_style_modifier() {
		wp_enqueue_style(
			'gostudy-role-manager-style',
			plugins_url( '/assets/public/css/gostudy-role-manager-style.css', GOSTUDY_PLUGIN_FILE ),
			array(),
			'1.0.2'
		);
	}
	public function remove_wp_admin_bar_my_account() {
        global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'my-account' );
		$wp_admin_bar->remove_node( 'my-account-buddypress' );
		$wp_admin_bar->remove_node( 'my-account-buddyblog' );
	}
	public function remove_wp_admin_bar_new_content( $admin_bar ) {
		$admin_bar->remove_menu( 'new-content' );
		$admin_bar->remove_node( 'new-media' );
	}
	public function current_user_role_has( $role_str ) {
		$user  = wp_get_current_user();
		$roles = $user->roles;
		return in_array( $role_str, $roles );
	}
	public function current_user_login_is( $name ) {
		$user  = wp_get_current_user();
		$login = $user->login;
		return $name === $login;
	}
}
