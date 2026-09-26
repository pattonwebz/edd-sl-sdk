<?php
/**
 * Minimal WordPress stubs so the SDK can be tested without WordPress.
 *
 * @package EasyDigitalDownloads\Updater
 */

// The declarations below are unconditional, so this file can only be loaded when WordPress is
// absent. tests/Bootstrap.php checks for that: a guard here could not work, because PHP compiles
// this file's declarations before running any statement in it.

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

$GLOBALS['edd_sl_sdk_test_state'] = array();

function edd_sl_sdk_test_reset() {
	$GLOBALS['edd_sl_sdk_test_state'] = array(
		'options'     => array(),
		'transients'  => array(),
		'hooks'       => array(),
		'http'        => null,
		'http_calls'  => 0,
		'wp_version'  => '6.6.2',
		'php_version' => '7.4',
	);
}

edd_sl_sdk_test_reset();

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
	}
}

function edd_sl_sdk_test_state( $key, $value = null ) {
	if ( func_num_args() > 1 ) {
		$GLOBALS['edd_sl_sdk_test_state'][ $key ] = $value;
	}
	return $GLOBALS['edd_sl_sdk_test_state'][ $key ] ?? null;
}

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$hooks                         = (array) edd_sl_sdk_test_state( 'hooks' );
	$hooks[ $hook ][ $priority ][] = array( $callback, $args );
	edd_sl_sdk_test_state( 'hooks', $hooks );
	return true;
}
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	return add_action( $hook, $callback, $priority, $args );
}
function apply_filters( $hook, $value ) {
	$args = array_slice( func_get_args(), 1 );

	foreach( edd_sl_sdk_test_hooks( $hook ) as $callback ) {
		$args[0] = $value;
		$value   = call_user_func_array( $callback[0], array_slice( $args, 0, $callback[1] ) );
	}

	return $value;
}
function do_action( $hook ) {
	$args = array_slice( func_get_args(), 1 );

	foreach( edd_sl_sdk_test_hooks( $hook ) as $callback ) {
		call_user_func_array( $callback[0], array_slice( $args, 0, $callback[1] ) );
	}
}
function edd_sl_sdk_test_hooks( $hook ) {
	$hooks = (array) edd_sl_sdk_test_state( 'hooks' );

	if ( empty( $hooks[ $hook ] ) ) {
		return array();
	}

	ksort( $hooks[ $hook ] );

	$callbacks = array();

	foreach ( $hooks[ $hook ] as $priority_callbacks ) {
		foreach ( $priority_callbacks as $callback ) {
			$callbacks[] = $callback;
		}
	}

	return $callbacks;
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }

function get_option( $key, $default = false ) {
	$options = (array) edd_sl_sdk_test_state( 'options' );
	return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
}
function update_option( $key, $value, $autoload = null ) {
	$options          = (array) edd_sl_sdk_test_state( 'options' );
	$options[ $key ]  = $value;
	edd_sl_sdk_test_state( 'options', $options );
	return true;
}
function delete_option( $key ) {
	$options = (array) edd_sl_sdk_test_state( 'options' );
	unset( $options[ $key ] );
	edd_sl_sdk_test_state( 'options', $options );
	return true;
}
function get_transient( $key ) {
	$transients = (array) edd_sl_sdk_test_state( 'transients' );

	if ( ! isset( $transients[ $key ] ) ) {
		return false;
	}

	$transient = $transients[ $key ];

	if ( 0 !== $transient['expires'] && time() > $transient['expires'] ) {
		return false;
	}

	return $transient['value'];
}
function set_transient( $key, $value, $expiration = 0 ) {
	$transients         = (array) edd_sl_sdk_test_state( 'transients' );
	$transients[ $key ] = array(
		'value'   => $value,
		'expires' => $expiration ? time() + $expiration : 0,
	);

	edd_sl_sdk_test_state( 'transients', $transients );
	return true;
}
function get_site_transient( $key ) { return get_transient( $key ); }
function set_site_transient( $key, $value, $expiration = 0 ) { return set_transient( $key, $value, $expiration ); }

// HTTP. Tests set a canned response, or leave it null to fail every request.
function wp_remote_get( $url, $args = array() ) {
	edd_sl_sdk_test_state( 'http_calls', (int) edd_sl_sdk_test_state( 'http_calls' ) + 1 );
	$response = edd_sl_sdk_test_state( 'http' );

	if ( null === $response ) {
		return new WP_Error( 'no_reqs_in_unit_tests', 'HTTP requests are disabled in unit tests.' );
	}

	return $response;
}
function wp_remote_post( $url, $args = array() ) { return wp_remote_get( $url, $args ); }
function wp_remote_retrieve_response_code( $response ) { return is_array( $response ) && isset( $response['response']['code'] ) ? $response['response']['code'] : ''; }
function wp_remote_retrieve_body( $response ) { return is_array( $response ) ? ( $response['body'] ?? '' ) : ''; }
function wp_remote_retrieve_response_message( $response ) { return is_array( $response ) ? ( $response['response']['message'] ?? '' ) : ''; }

function wp_json_encode( $data ) { return json_encode( $data ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( (array) $defaults, (array) $args ); }
function maybe_unserialize( $data ) {
	if ( ! is_string( $data ) ) {
		return $data;
	}

	$unserialized = @unserialize( $data );

	return false === $unserialized && 'b:0;' !== $data ? $data : $unserialized;
}
function wp_normalize_path( $path ) { return str_replace( '\\', '/', $path ); }
function trailingslashit( $value ) { return rtrim( $value, '/\\' ) . '/'; }
function untrailingslashit( $value ) { return rtrim( $value, '/\\' ); }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function content_url( $path = '' ) { return home_url( '/wp-content' . $path ); }
function plugin_basename( $file ) {
	$file = wp_normalize_path( $file );
	$dir  = wp_normalize_path( WP_PLUGIN_DIR );

	if ( 0 === strpos( $file, $dir ) ) {
		return ltrim( substr( $file, strlen( $dir ) ), '/' );
	}

	return basename( $file );
}
function plugin_dir_path( $file ) { return trailingslashit( dirname( $file ) ); }
function get_bloginfo( $show = '' ) { return 'version' === $show ? edd_sl_sdk_test_state( 'wp_version' ) : ''; }
function is_admin() { return false; }
function is_multisite() { return false; }
function current_user_can() { return true; }
function is_ssl() { return true; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_text_field( $value ) { return $value; }
function wp_get_environment_type() { return 'production'; }
function esc_html( $value ) { return $value; }
function esc_attr( $value ) { return $value; }
function esc_url( $value ) { return $value; }
function wp_kses_post( $value ) { return $value; }
function self_admin_url( $path = '' ) { return home_url( '/wp-admin/' . $path ); }
function add_query_arg( $args, $url = '', $value = null ) {
	if ( is_array( $args ) ) {
		$query = $args;
	} else {
		$query = array( $args => $url );
		$url   = $value;
	}

	$url      = (string) $url;
	$fragment = '';

	if ( false !== strpos( $url, '#' ) ) {
		list( $url, $fragment ) = explode( '#', $url, 2 );
		$fragment               = '#' . $fragment;
	}

	$parts    = explode( '?', $url, 2 );
	$existing = array();

	if ( isset( $parts[1] ) ) {
		parse_str( $parts[1], $existing );
	}

	return $parts[0] . '?' . http_build_query( array_merge( $existing, $query ) ) . $fragment;
}
function __( $text, $domain = null ) { return $text; }
function _x( $text, $context = null, $domain = null ) { return $text; }
