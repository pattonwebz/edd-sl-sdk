<?php
/**
 * Tests for the plugin updater.
 *
 * @package EasyDigitalDownloads\Updater
 */

namespace EasyDigitalDownloads\Updater\Tests\Updaters;

use EasyDigitalDownloads\Updater\Tests\TestCase;
use EasyDigitalDownloads\Updater\Updaters\Plugin;
use ReflectionClass;

/**
 * Covers the "Tested up to" handling in the plugin updater.
 *
 * The method under test is private, so it is called through reflection rather than
 * changing the class API for the sake of the tests.
 */
class PluginTest extends TestCase {

	/**
	 * The updater under test.
	 *
	 * @var Plugin
	 */
	private $updater;

	/**
	 * Reflection for the updater under test.
	 *
	 * @var ReflectionClass
	 */
	private $reflection;

	/**
	 * Sets up the updater.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->updater    = new Plugin(
			'https://store.example/',
			array(
				'file'    => dirname( __DIR__ ) . '/fixtures/sample-plugin/sample-plugin.php',
				'version' => '1.0.0',
				'license' => 'test-license',
				'item_id' => 1,
			)
		);
		$this->reflection = new ReflectionClass( Plugin::class );
	}

	/**
	 * Calls a method on the updater, including non public ones.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Method arguments.
	 * @return mixed
	 */
	private function call( $method, array $args = array() ) {
		$reflected = $this->reflection->getMethod( $method );

		if ( PHP_VERSION_ID < 80100 ) {
			$reflected->setAccessible( true );
		}

		return $reflected->invokeArgs( $this->updater, $args );
	}

	/**
	 * A partial minor version is expanded to the current patch release.
	 */
	public function test_tested_version_expands_a_partial_minor_version() {
		$this->set_wp_version( '7.1.2' );

		$this->assertSame( '7.1.2', $this->call( 'get_tested_version', array( (object) array( 'tested' => '7.1' ) ) ) );
	}

	/**
	 * A version from another minor release is left alone.
	 */
	public function test_tested_version_leaves_another_minor_version_alone() {
		$this->set_wp_version( '7.1.2' );

		$this->assertSame( '7.0', $this->call( 'get_tested_version', array( (object) array( 'tested' => '7.0' ) ) ) );
	}

	/**
	 * A version from another major release is left alone.
	 */
	public function test_tested_version_leaves_another_major_version_alone() {
		$this->set_wp_version( '7.1.2' );

		$this->assertSame( '6.9', $this->call( 'get_tested_version', array( (object) array( 'tested' => '6.9' ) ) ) );
	}

	/**
	 * A tested version newer than the site is left alone.
	 */
	public function test_tested_version_leaves_a_newer_version_alone() {
		$this->set_wp_version( '7.1.2' );

		$this->assertSame( '7.2', $this->call( 'get_tested_version', array( (object) array( 'tested' => '7.2' ) ) ) );
	}

	/**
	 * No tested version returns null.
	 */
	public function test_tested_version_is_null_when_not_set() {
		$this->assertNull( $this->call( 'get_tested_version', array( (object) array( 'tested' => '' ) ) ) );
	}


	/**
	 * A single part version is expanded instead of reading an undefined array key.
	 */
	public function test_tested_version_expands_a_single_part_version() {
		$this->set_wp_version( '7.1.2' );

		$this->assertSame( '7.1.2', $this->call( 'get_tested_version', array( (object) array( 'tested' => '7' ) ) ) );
	}

	/**
	 * Extra version data on the WordPress version is stripped before comparing.
	 */
	public function test_tested_version_handles_a_beta_wordpress_version() {
		$this->set_wp_version( '7.2-beta1' );

		$this->assertSame( '7.2', $this->call( 'get_tested_version', array( (object) array( 'tested' => '7' ) ) ) );
	}

	/**
	 * A cached entry written by an earlier version is corrected when it is read.
	 */
	public function test_cached_version_info_expands_the_tested_version() {
		$this->set_wp_version( '7.1.2' );

		$this->call( 'set_version_info_cache', array( $this->version_info( '7.1' ) ) );

		$this->assertSame( '7.1.2', $this->call( 'get_cached_version_info' )->tested );
	}

	/**
	 * The plugin information response reports the expanded tested version.
	 */
	public function test_plugin_information_response_expands_the_tested_version() {
		$this->set_wp_version( '7.1.2' );

		$this->call( 'set_version_info_cache', array( $this->version_info( '7.1' ) ) );

		$response = $this->updater->plugins_api_filter(
			false,
			'plugin_information',
			(object) array( 'slug' => $this->call( 'get_slug' ) )
		);

		$this->assertSame( '7.1.2', $response->tested );
	}

	/**
	 * A response fetched from the store is expanded before it is cached.
	 */
	public function test_remote_response_is_expanded_before_caching() {
		$this->set_wp_version( '7.1.2' );
		$this->set_http_response(
			$this->http_response(
				array(
					'name'        => 'Sample Plugin',
					'new_version' => '1.0.1',
					'tested'      => '7.1',
					'sections'    => serialize( array( 'changelog' => 'Notes' ) ),
				)
			)
		);

		$response = $this->updater->plugins_api_filter(
			false,
			'plugin_information',
			(object) array( 'slug' => $this->call( 'get_slug' ) )
		);

		$this->assertSame( '7.1.2', $response->tested );
		$this->assertSame( '7.1.2', $this->call( 'get_cached_version_info' )->tested );

		// Read the stored value directly: the read path expands too, so only the raw
		// cache proves the response was expanded before it was written.
		$stored = get_option( $this->call( 'get_cache_key' ) );
		$this->assertSame( '7.1.2', json_decode( $stored['value'] )->tested );
	}

	/**
	 * Builds a version info object.
	 *
	 * @param string $tested The tested version to use.
	 * @return object
	 */
	private function version_info( $tested ) {
		return (object) array(
			'name'        => 'Sample Plugin',
			'new_version' => '1.0.1',
			'tested'      => $tested,
			'sections'    => array(),
			'banners'     => array(),
			'icons'       => array(),
		);
	}
}
