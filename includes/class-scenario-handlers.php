<?php
/**
 * Custom PWA Scenario CRUD Handlers
 *
 * Handles form submissions for creating, updating, and deleting scenarios.
 *
 * @package Custom_PWA
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize scenario action handlers.
 */
function custom_pwa_init_scenario_handlers() {
	add_action( 'admin_post_custom_pwa_save_scenario', 'custom_pwa_handle_save_scenario' );
}
add_action( 'admin_init', 'custom_pwa_init_scenario_handlers' );

/**
 * Handle scenario save (create or update).
 */
function custom_pwa_handle_save_scenario() {
	// Security check
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
	}

	check_admin_referer( 'custom_pwa_save_scenario', 'custom_pwa_scenario_nonce' );

	// Load required classes
	require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';

	// Get form data
	$mode = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'add';
	$scenario_id = isset( $_POST['scenario_id'] ) ? sanitize_key( $_POST['scenario_id'] ) : '';
	$scenario_data = isset( $_POST['scenario'] ) ? $_POST['scenario'] : array();

	// Process meta keys (convert from textarea to array)
	if ( isset( $scenario_data['fields_used']['meta_keys_text'] ) ) {
		$meta_keys_text = sanitize_textarea_field( $scenario_data['fields_used']['meta_keys_text'] );
		$meta_keys = array_filter( array_map( 'trim', explode( "\n", $meta_keys_text ) ) );
		$scenario_data['fields_used']['meta_keys'] = $meta_keys;
		unset( $scenario_data['fields_used']['meta_keys_text'] );
	}

	// Ensure post_types is an array (empty if not set)
	if ( ! isset( $scenario_data['post_types'] ) || ! is_array( $scenario_data['post_types'] ) ) {
		$scenario_data['post_types'] = array();
	}

	// Ensure core_fields is an array
	if ( ! isset( $scenario_data['fields_used']['core_fields'] ) || ! is_array( $scenario_data['fields_used']['core_fields'] ) ) {
		$scenario_data['fields_used']['core_fields'] = array();
	}

	// Process based on mode
	if ( $mode === 'add' ) {
		$result = Custom_PWA_Custom_Scenarios::create( $scenario_data );
		if ( $result ) {
			$redirect_url = add_query_arg(
				array(
					'page'    => 'custom-pwa-push',
					'tab'     => 'scenarios',
					'message' => 'scenario_created',
				),
				admin_url( 'admin.php' )
			);
		} else {
			$redirect_url = add_query_arg(
				array(
					'page'    => 'custom-pwa-push',
					'tab'     => 'scenarios',
					'action'  => 'add',
					'error'   => 'create_failed',
				),
				admin_url( 'admin.php' )
			);
		}
	} else {
		// Edit mode
		$result = Custom_PWA_Custom_Scenarios::update( $scenario_id, $scenario_data );
		if ( $result ) {
			$redirect_url = add_query_arg(
				array(
					'page'    => 'custom-pwa-push',
					'tab'     => 'scenarios',
					'message' => 'scenario_updated',
				),
				admin_url( 'admin.php' )
			);
		} else {
			$redirect_url = add_query_arg(
				array(
					'page'        => 'custom-pwa-push',
					'tab'         => 'scenarios',
					'action'      => 'edit',
					'scenario_id' => $scenario_id,
					'error'       => 'update_failed',
				),
				admin_url( 'admin.php' )
			);
		}
	}

	wp_safe_redirect( $redirect_url );
	exit;
}

/**
 * Display admin notices for scenario operations.
 */
function custom_pwa_scenario_admin_notices() {
	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'custom-pwa-push' ) {
		return;
	}

	if ( ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'scenarios' ) {
		return;
	}

	// Success messages
	if ( isset( $_GET['message'] ) ) {
		$message = sanitize_key( $_GET['message'] );
		$notice_text = '';

		switch ( $message ) {
			case 'scenario_created':
				$notice_text = __( 'Scenario created successfully.', 'custom-pwa' );
				break;
			case 'scenario_updated':
				$notice_text = __( 'Scenario updated successfully.', 'custom-pwa' );
				break;
		}

		if ( $notice_text ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice_text ) . '</p></div>';
		}
	}

	// Error messages
	if ( isset( $_GET['error'] ) ) {
		$error = sanitize_key( $_GET['error'] );
		$notice_text = '';

		switch ( $error ) {
			case 'create_failed':
				$notice_text = __( 'Failed to create scenario. Please check your input and try again.', 'custom-pwa' );
				break;
			case 'update_failed':
				$notice_text = __( 'Failed to update scenario. Please check your input and try again.', 'custom-pwa' );
				break;
		}

		if ( $notice_text ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $notice_text ) . '</p></div>';
		}
	}
}
add_action( 'admin_notices', 'custom_pwa_scenario_admin_notices' );
