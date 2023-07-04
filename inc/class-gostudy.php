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
	public $teacher_member_name = '老師';
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	public function __construct() {
		add_filter(
			'ld_dashboard_lesson_form_fields',
			function( $fields ) {
				$fields[6]['message']      = 'Require users to watch the full video as part of the course progression.';
				$fields[17]['choices'][''] = 'Manually grade (Instructor approval and grading required. The %s cannot be completed until the assignment is approved.)';
				return $fields;
			}
		);
		add_filter( 'tiny_mce_before_init', array( $this, 'tiny_mce_before_init' ), 100 );
		add_action( 'bp_setup_nav', array( $this, 'add_listing_in_bp_profile' ), 100 );
		add_action( 'init', array( $this, 'frontend_initialize' ), 999 );
		add_action( 'admin_init', array( $this, 'admin_initialize' ), 999 );
		add_action( 'bp_before_activation_page', array( $this, 'buddypress_activation_autoactivate' ) );
		add_action( 'wp_login', array( $this, 'update_amelia_providor_when_account_login' ), 20, 2 );
		add_action( 'wp_login', array( $this, 'insert_service_location_when_account_login' ), 25, 2 );
	}
	public function buddypress_activation_autoactivate() {
		$key = bp_get_current_activation_key();
		if ( empty( $key ) ) {
			return ;
		}
		$user_id = bp_core_activate_signup($key);
		if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
			return ;
		}
		$bp = buddypress();
		$bp->activation_complete = true;
	}
	public function update_amelia_providor_when_account_login( $user_login, $user ) {	
		global $wpdb;

		$ajax_nonce = wp_create_nonce('ajax-nonce');
		$admin_ajax_url = admin_url( 'admin-ajax.php' );

		/** Retrieve the member type of the registered user */
		$member_type = bp_get_member_type( $user->ID );
		$type_obj = bp_get_member_type_object( $member_type );
		$user_member_type = $type_obj->labels['singular_name'];
		$user_email = $user->user_email;

		if ( $this->teacher_member_name !== $user_member_type ) {
			return ;
		}

		$amelia_user_table_name = sprintf( '%samelia_users', $wpdb->prefix );
		$query_same_user_by_email = "SELECT id, type FROM $amelia_user_table_name WHERE email = '$user_email'";
		$result = $wpdb->get_results( $query_same_user_by_email, ARRAY_A );
		$found_user = empty( $result[0] ) ? null : $result[0] ;

		if ( ! empty( $found_user ) ) {
			$user_type = $found_user['type'];
			if ( 'provider' !== $user_type ) {
				$amelia_user_id = $wpdb->update( $amelia_user_table_name, array( 'type' => 'provider' ), array( 'id' => $found_user['id'] ) );
			}
			return ;
		}
		$wpdb->insert(
			$amelia_user_table_name,
			[
				"status" => "visible",
				"type" => "provider",
				"externalId" => $user->ID,
				"firstName" => $user->first_name,
				"lastName" => $user->last_name,
				"email" => $user_email,
				"phone" => '',
				"note" => '',
				"description" => '',
				"pictureFullPath" => '',
				"pictureThumbPath" => '',
				"usedTokens" => '',
				"zoomUserId" => '',
				"countryPhoneIso" => 'tw',
				"translations" => '',
				"timeZone" => '',
			]
		);
	}
	public function insert_service_location_when_account_login( $user_login, $user ) {
		global $wpdb;

		/** Retrieve the member type of the registered user */
		$member_type = bp_get_member_type( $user->ID );
		$type_obj = bp_get_member_type_object( $member_type );
		$user_member_type = $type_obj->labels['singular_name'];
		$user_email = $user->user_email;

		if ( $this->teacher_member_name !== $user_member_type ) {
			return ;
		}

		$amelia_user_table_name = sprintf( '%samelia_users', $wpdb->prefix );
		$query_same_user_by_email = "SELECT id, type FROM $amelia_user_table_name WHERE email = '$user_email'";
		$result_amelia_user = $wpdb->get_results( $query_same_user_by_email, ARRAY_A );

		if ( empty( $result_amelia_user ) ) {
			return ;
		}
		$amelia_user_id = $result_amelia_user[0]['id'];

		try {
			/** Retrieve the college and department name of the registered user */
			$college_field_id = xprofile_get_field_id_from_name( '大學名稱' );
			$department_of_college_field_id = xprofile_get_field_id_from_name( '科系或所屬單位' );
			$college_name_value_of_user = xprofile_get_field_data( $college_field_id, $user->ID );
			$department_of_college_name_value_of_user = xprofile_get_field_data( $department_of_college_field_id, $user->ID );

			/** Retrieve the cagetory data of simulated_interview */
			$amelia_categories_table_name = sprintf( '%samelia_categories', $wpdb->prefix );
			$query_amelia_cagetory_id_of_simulated_interview = "SELECT id FROM $amelia_categories_table_name WHERE name = '模擬面試'";
			$result_amelia_cagetory_id_of_simulated_interview = $wpdb->get_results( $query_amelia_cagetory_id_of_simulated_interview, ARRAY_A );

			if ( empty( $result_amelia_cagetory_id_of_simulated_interview ) ) {
				return ;
			}
			$simulated_interview_cagetory_id = $result_amelia_cagetory_id_of_simulated_interview[0]['id'];

			/** Retrieve the services data of simulated_interview */
			$amelia_services_table_name = sprintf( '%samelia_services', $wpdb->prefix );
			$query_amelia_services_of_simulated_interview = "SELECT id, name, status, categoryId FROM $amelia_services_table_name WHERE name = '$department_of_college_name_value_of_user'";
			$result_amelia_services_of_simulated_interview = $wpdb->get_results( $query_amelia_services_of_simulated_interview, ARRAY_A );
			$services_id = ! empty($result_amelia_services_of_simulated_interview) ? $result_amelia_services_of_simulated_interview[0]['id'] : null;

			if ( empty( $result_amelia_services_of_simulated_interview ) ) {
				$wpdb->insert(
					$amelia_services_table_name,
					[
						'name' => $department_of_college_name_value_of_user,
						'description' => '',
						'color' => '#1788FB',
						'price' => 0,
						'status' => 'visible',
						'categoryId' => $simulated_interview_cagetory_id,
						'minCapacity' => '1',
						'maxCapacity' => '1',
						'duration' => '900',
						'timeBefore' => null,
						'timeAfter' => '900',
						'bringingAnyone' => '1',
						'priority' => 'least_expensive',
						'show' => '1',
						'aggregatedPrice' => '1',
						'settings' => '{"payments":{"paymentLinks":{"enabled":false,"changeBookingStatus":false,"redirectUrl":null},"onSite":true,"payPal":{"enabled":false},"stripe":{"enabled":false},"mollie":{"enabled":false},"razorpay":{"enabled":false}},"general":{"minimumTimeRequirementPriorToCanceling":null,"minimumTimeRequirementPriorToRescheduling":null,"minimumTimeRequirementPriorToBooking":null},"zoom":{"enabled":false},"lessonSpace":{"enabled":false},"activation":{"version":"6.2.2"}}',
						'recurringPayment' => '0',
						'depositPayment' => '1',
						'depositPerPerson' => '1',
						'deposit' => '0',
						'fullPayment' => '0',
						'mandatoryExtra' => '0',
						'minSelectedExtras' => null,
						'customPricing' => '{"enabled":false,"durations":{}}',
						'limitPerCustomer' => '{"enabled":true,"numberOfApp":1,"timeFrame":"week","period":1,"from":"bookingDate"}',
					]
				);
				$result_amelia_services_of_simulated_interview = $wpdb->get_results( $query_amelia_services_of_simulated_interview, ARRAY_A );
				$services_id = $result_amelia_services_of_simulated_interview[0]['id'];
			}

			/** Retrieve the locations data of simulated_interview */
			$amelia_locations_table_name = sprintf( '%samelia_locations', $wpdb->prefix );
			$location_name = "線上面試";
			$query_amelia_locations_of_simulated_interview = "SELECT id, name, status, address FROM $amelia_locations_table_name WHERE name = '$location_name'";
			$result_amelia_localtions_of_simulated_interview = $wpdb->get_results( $query_amelia_locations_of_simulated_interview, ARRAY_A );
			$locations_id = ! empty($result_amelia_localtions_of_simulated_interview) ? $result_amelia_localtions_of_simulated_interview[0]['id'] : null;

			if ( empty( $result_amelia_localtions_of_simulated_interview ) ) {
				$wpdb->insert(
					$amelia_locations_table_name,
					[
						'name' => "$location_name",
						'status' => 'visible',
						'latitude' => '25.074309',
						'longitude' => '121.520645'
					]
				);
				$query_amelia_locations_of_simulated_interview = "SELECT id, name, status, address FROM $amelia_locations_table_name WHERE name = '$location_name'";
				$result_amelia_localtions_of_simulated_interview = $wpdb->get_results( $query_amelia_locations_of_simulated_interview, ARRAY_A );
				$locations_id = $result_amelia_localtions_of_simulated_interview[0]['id'];
			}

			$amelia_providers_to_locations_table_name = sprintf( '%samelia_providers_to_locations', $wpdb->prefix );
			$query_amelia_providers_to_locations = "SELECT id, userId, locationId FROM $amelia_providers_to_locations_table_name WHERE userId = $amelia_user_id";
			$result_amelia_providers_to_locations = $wpdb->get_results( $query_amelia_providers_to_locations, ARRAY_A );
			if ( empty( $result_amelia_providers_to_locations ) ) {
				$wpdb->insert(
					$amelia_providers_to_locations_table_name,
					[
						"userId" => $amelia_user_id,
						"locationId" => $locations_id
					]
				);
			}

			$amelia_providers_to_services_table_name = sprintf( '%samelia_providers_to_services', $wpdb->prefix );
			$query_amelia_providers_to_services = "SELECT id, userId, serviceId FROM $amelia_providers_to_services_table_name WHERE userId = $amelia_user_id AND serviceId = $services_id";
			$result_amelia_providers_to_services = $wpdb->get_results( $query_amelia_providers_to_services, ARRAY_A );

			if ( empty( $result_amelia_providers_to_services ) ) {
				$wpdb->insert(
					$amelia_providers_to_services_table_name,
					[
						'userId' => $amelia_user_id,
						'serviceId' => $services_id,
						'price' => 0,
						'minCapacity' => '1',
						'maxCapacity' => '1',
						'customPricing' => '{"enabled":false,"durations":{}}'
					]
				);
			}

			$amelia_providers_to_weekdays_table_name = sprintf( '%samelia_providers_to_weekdays', $wpdb->prefix );
			$query_amelia_providers_to_weekdays = "SELECT id, userId, dayIndex FROM $amelia_providers_to_weekdays_table_name WHERE userId = $amelia_user_id";
			$result_amelia_providers_to_weekdays = $wpdb->get_results( $query_amelia_providers_to_weekdays, ARRAY_A );

			if ( empty( $result_amelia_providers_to_weekdays ) ) {
				// 如果是工作日期空的時候
			}
		} catch ( Exception $err ) {
			error_log( $err );
		}
	}
	public function tiny_mce_before_init($mceInit) {
		return $mceInit;
	}
	public function add_listing_in_bp_profile() {
		if ( empty( get_current_user_id() ) ) {
			return;
		}
		$current_user_is_admin = $this->user_has_role( get_current_user_id(), 'administrator' );
		$current_user_is_group_leader = $this->user_has_role( get_current_user_id(), 'group_leader' );
		
		if ( ! $current_user_is_admin && ! $current_user_is_group_leader ) {
			bp_core_remove_subnav_item( dbb_get_listings_slug(), 'add' );
		}
	}
	public function frontend_initialize() {
		wp_enqueue_script(
			'force-amelia-upload-file-accepts',
			plugins_url( '/assets/public/script/frontend-listenser-unit-test.js', GOSTUDY_PLUGIN_FILE ),
			array('jquery'),
			'0.0.003'
		);
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_style(
			'gostudy-frontend-modifer-style',
			plugins_url( '/assets/public/css/gostudy-frontend-modifer-style.css', GOSTUDY_PLUGIN_FILE ),
			array(),
			'1.0.18.021'
		);
		wp_enqueue_script(
			'force-amelia-upload-file-accepts',
			plugins_url( '/assets/public/script/force-amelia-upload-file-accepts.js', GOSTUDY_PLUGIN_FILE ),
			array('jquery'),
			'0.0.002'
		);
		remove_filter('bp_get_requested_url', 'bb_support_learndash_course_other_language_permalink');
	}
	public function admin_initialize() {
		add_action( 'admin_enqueue_scripts', array( $this, 'gostudy_admin_styles_modifier' ) );
		$has_gostudy_console = array_map(
			function( $role_name ) {
				return $this->current_user_role_has( $role_name );
			},
			array( 'wpamelia-manager', 'ld_instructor', 'ld_instructor_pending', 'group_leader', 'editor' )
		);
		if ( in_array( true, $has_gostudy_console ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'hide_menu_page_modifier' ), 999 );
			add_action( 'admin_enqueue_scripts', array( $this, 'gostudy_role_manager_style_modifier' ) );
			add_action( 'wp_before_admin_bar_render', array( $this, 'remove_wp_admin_bar_my_account' ), 999 );
			add_action( 'admin_bar_menu', array( $this, 'remove_wp_admin_bar_new_content' ), 999 );
			add_action( 'add_meta_boxes', array( $this, 'modifier_meta_box_for_manager' ), 999 );
			add_filter( 'use_block_editor_for_post', '__return_false', 999 );
		}
	}
	public function modifier_meta_box_for_manager() {
		$current_screen = get_current_screen();
		if ( 'post' === $current_screen->base ) {
			remove_meta_box( 'learndash-course-grid-meta-box', $current_screen, 'advanced' );
			remove_meta_box( 'pageparentdiv', $current_screen, 'side' );
			remove_meta_box( 'formatdiv', $current_screen, 'side' );
		}
	}
	public function gostudy_admin_styles_modifier() {
		wp_enqueue_style(
			'gostudy-custom-style',
			plugins_url( '/assets/public/css/gostudy-custom-style.css', GOSTUDY_PLUGIN_FILE ),
			array(),
			'1.0.4'
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
		$wp_admin_bar->remove_node( 'updraft_admin_node' );
		$wp_admin_bar->remove_node( 'wp-cloudflare-super-page-cache-toolbar-container' );
	}
	public function remove_wp_admin_bar_new_content( $admin_bar ) {
		$admin_bar->remove_menu( 'new-content' );
		$admin_bar->remove_node( 'new-media' );
		$admin_bar->remove_menu( 'comments' );
		$admin_bar->remove_menu( 'archive' );
	}
	public function hide_menu_page_modifier() {
		wp_enqueue_style(
			'gostudy-hide-admin-menu-style',
			plugins_url( '/assets/public/css/gostudy-hide-admin-menu-style.css', GOSTUDY_PLUGIN_FILE ),
			array(),
			'1.0.0'
		);
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
	public function user_has_role($user_id, $role_name)	{
		$user_meta = get_userdata($user_id);
		$user_roles = $user_meta->roles;
		return in_array($role_name, $user_roles);
	}
}
