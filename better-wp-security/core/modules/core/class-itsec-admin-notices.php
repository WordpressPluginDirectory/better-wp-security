<?php

use iThemesSecurity\Contracts\Runnable;

class ITSEC_Admin_Notices implements Runnable {
	const ACTION = 'itsec-admin-notice';

	/** @var WP_Error[] */
	private $errors = array();

	public function run() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		if ( isset( $_GET['action'] ) && self::ACTION === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'handle_admin_action' ) );
		}
	}

	public function rest_api_init() {
		require_once( dirname( __FILE__ ) . '/class-rest-core-admin-notices-controller.php' );

		$controller = new ITSEC_REST_Core_Admin_Notices_Controller();
		$controller->register_routes();
	}

	public function handle_admin_action() {
		$error = $this->handle_action( $_GET );

		if ( is_wp_error( $error ) ) {
			$this->errors[] = $error;
		}
	}

	private function handle_action( $request ) {
		if ( ! isset( $request['notice_id'], $request['itsec_action'], $request['nonce'] ) ) {
			return new WP_Error( 'itsec-admin-notices.invalid-request-format', esc_html__( 'Invalid request format.', 'better-wp-security' ) );
		}

		if ( ! wp_verify_nonce( $request['nonce'], self::ACTION ) ) {
			return new WP_Error( 'itsec-admin-notices.invalid-nonce', esc_html__( 'Request Expired. Please refresh and try again.', 'better-wp-security' ) );
		}

		ITSEC_Lib::load( 'admin-notices' );
		$notices = ITSEC_Lib_Admin_Notices::get_notices( new ITSEC_Admin_Notice_Context(
			wp_get_current_user(),
			wp_doing_ajax() ? ITSEC_Admin_Notice_Context::AJAX : ITSEC_Admin_Notice_Context::ADMIN_ACTION
		) );

		$notice = null;
		foreach ( $notices as $maybe_notice ) {
			if ( (string) $maybe_notice->get_id() === $request['notice_id'] ) {
				$notice = $maybe_notice;
				break;
			}
		}

		if ( ! $notice ) {
			return new WP_Error( 'itsec-admin-notices.invalid-notice', esc_html__( 'Notice not found.', 'better-wp-security' ) );
		}

		$actions = $notice->get_actions();

		if ( ! isset( $actions[ $request['itsec_action'] ] ) ) {
			return new WP_Error( 'itsec-admin-notices.invalid-action', esc_html__( 'Action not found.', 'better-wp-security' ) );
		}

		$data = $request;

		unset( $data['notice_id'], $data['itsec_action'], $data['nonce'], $data['action'] );

		$error = $actions[ $request['itsec_action'] ]->handle( wp_get_current_user(), $data );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return null;
	}
}
