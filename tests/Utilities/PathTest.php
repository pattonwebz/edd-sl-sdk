<?php
/**
 * Tests for the path utility.
 *
 * @package EasyDigitalDownloads\Updater
 */

namespace EasyDigitalDownloads\Updater\Tests\Utilities;

use EasyDigitalDownloads\Updater\Tests\TestCase;
use EasyDigitalDownloads\Updater\Utilities\Path;

/**
 * Covers SDK path, URL and version storage.
 */
class PathTest extends TestCase {

	/**
	 * The SDK directory used by the tests.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * Sets up the SDK directory inside the stubbed content directory.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->dir = WP_CONTENT_DIR . '/plugins/sample-plugin';

		Path::set( $this->dir . '/sample-plugin.php', '1.2.3' );
	}

	/**
	 * The directory is stored as given.
	 */
	public function test_set_stores_the_directory() {
		$this->assertSame( $this->dir, Path::get_dir() );
	}

	/**
	 * The version is stored.
	 */
	public function test_set_stores_the_version() {
		$this->assertSame( '1.2.3', Path::get_version() );
	}

	/**
	 * The version defaults to 1.0.0.
	 */
	public function test_set_defaults_the_version() {
		Path::set( $this->dir . '/sample-plugin.php' );

		$this->assertSame( '1.0.0', Path::get_version() );
	}

	/**
	 * The URL is built relative to the content directory.
	 */
	public function test_set_builds_the_url_relative_to_the_content_directory() {
		$this->assertSame( 'https://example.test/wp-content/plugins/sample-plugin/', Path::get_url() );
	}
}
