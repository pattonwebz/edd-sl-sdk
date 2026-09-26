<?php
/**
 * Base test case.
 *
 * @package EasyDigitalDownloads\Updater
 */

namespace EasyDigitalDownloads\Updater\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for the SDK. Resets the stubbed WordPress state between tests.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Resets the stubbed WordPress state.
	 */
	protected function setUp(): void {
		parent::setUp();

		edd_sl_sdk_test_reset();
	}

	/**
	 * Sets the WordPress version reported by get_bloginfo().
	 *
	 * @param string $version The version to report.
	 */
	protected function set_wp_version( $version ) {
		edd_sl_sdk_test_state( 'wp_version', $version );
	}

	/**
	 * Sets the canned response for remote requests.
	 *
	 * @param array|null $response Response array, or null to fail every request.
	 */
	protected function set_http_response( $response ) {
		edd_sl_sdk_test_state( 'http', $response );
	}

	/**
	 * Builds a canned 200 response with a JSON body.
	 *
	 * @param array $body The body to encode.
	 * @return array
	 */
	protected function http_response( array $body ) {
		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => wp_json_encode( $body ),
		);
	}
}
