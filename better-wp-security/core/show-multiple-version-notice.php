<?php

if ( ! function_exists( 'itsec_show_multiple_version_notice' ) ) {
	function itsec_show_multiple_version_notice() {
		echo '<div class="error"><p>' . __( 'Multiple versions of Solid Security are active. Please disable all extra versions of Solid Security.', 'better-wp-security' ) . '</p></div>';
	}

	add_action( 'all_admin_notices', 'itsec_show_multiple_version_notice' );
}
