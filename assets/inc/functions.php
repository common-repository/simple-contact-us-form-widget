<?php
/**
 * Personalized functions for Greg Bialowas' WP plugins
 *
 * @package gb-simple-contact-us-widget
 */

if ( ! function_exists( 'grebia_custom_wp_plugins_fn_sanitize_array' ) ) {

	/**
	 * Sanitize_array
	 *
	 * @param array $array Array to be sanitized.
	 * @return array Returns cleaned array.
	 */
	function grebia_custom_wp_plugins_fn_sanitize_array( $array ) {
		foreach ( (array) $array as $k => $v ) {
			if ( is_array( $v ) ) {
				$array[ $k ] = grebia_custom_wp_plugins_fn_sanitize_array( $v );
			} else {
				$array[ $k ] = wp_kses_post( $v );
			}
		}

		return $array;
	}
}
