<?php
/**
 * Tests for the Versions class.
 *
 * @package EasyDigitalDownloads\Updater
 */

namespace EasyDigitalDownloads\Updater\Tests;

use EasyDigitalDownloads\Updater\Versions;

/**
 * Covers version registration and lookup.
 */
class VersionsTest extends TestCase {

	/**
	 * Registering a version returns true.
	 */
	public function test_register_returns_true_for_a_new_version() {
		$versions = new Versions();

		$this->assertTrue( $versions->register( '1.0.0', '__return_null' ) );
	}

	/**
	 * Registering the same version twice returns false.
	 */
	public function test_register_returns_false_for_a_duplicate_version() {
		$versions = new Versions();
		$versions->register( '1.0.0', '__return_null' );

		$this->assertFalse( $versions->register( '1.0.0', '__return_null' ) );
	}

	/**
	 * Registered callbacks are returned as given.
	 */
	public function test_get_versions_returns_registered_callbacks() {
		$versions = new Versions();
		$versions->register( '1.0.0', 'callback_one' );

		$this->assertSame( array( '1.0.0' => 'callback_one' ), $versions->get_versions() );
	}

	/**
	 * With nothing registered there is no latest version.
	 */
	public function test_latest_version_is_false_when_nothing_is_registered() {
		$versions = new Versions();

		$this->assertFalse( $versions->latest_version() );
	}

	/**
	 * The latest version is compared as a version, not as a string.
	 */
	public function test_latest_version_uses_version_compare() {
		$versions = new Versions();
		$versions->register( '1.0.9', '__return_null' );
		$versions->register( '1.0.10', '__return_null' );
		$versions->register( '1.0.2', '__return_null' );

		$this->assertSame( '1.0.10', $versions->latest_version() );
	}

	/**
	 * The callback for the latest version is returned.
	 */
	public function test_latest_version_callback_returns_the_latest_callback() {
		$versions = new Versions();
		$versions->register( '1.0.0', 'callback_one' );
		$versions->register( '2.0.0', 'callback_two' );

		$this->assertSame( 'callback_two', $versions->latest_version_callback() );
	}

	/**
	 * With nothing registered the fallback callback is returned.
	 */
	public function test_latest_version_callback_falls_back_when_empty() {
		$versions = new Versions();

		$this->assertSame( '__return_null', $versions->latest_version_callback() );
	}

	/**
	 * The instance method returns the same object each time.
	 */
	public function test_instance_returns_a_singleton() {
		$this->assertSame( Versions::instance(), Versions::instance() );
	}
}
