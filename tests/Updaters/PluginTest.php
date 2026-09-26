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

		$this->assertSame( '7.2.0', $this->call( 'get_tested_version', array( (object) array( 'tested' => '7.2.0' ) ) ) );
	}

	/**
	 * No tested version returns null.
	 */
	public function test_tested_version_is_null_when_not_set() {
		$this->assertNull( $this->call( 'get_tested_version', array( (object) array( 'tested' => '' ) ) ) );
	}
}
