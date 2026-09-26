<?php
/**
 * PHPUnit bootstrap.
 *
 * The suite runs without WordPress and without a database. WordPress functions the
 * SDK touches are stubbed in tests/Stubs/wordpress.php.
 *
 * @package EasyDigitalDownloads\Updater
 */

// The SDK classes exit if this is not defined, as WordPress defines it.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$edd_sl_sdk_autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $edd_sl_sdk_autoloader ) ) {
	echo "Composer dependencies are missing. Run 'composer install'." . PHP_EOL;
	exit( 1 );
}

// The stubs declare WordPress functions unconditionally, so the suite cannot run in a process
// where WordPress is already loaded. The check belongs here rather than in the stub file: inside
// that file the redeclare fatal fires while it is compiled, before any guard there could run.
if ( class_exists( 'WP' ) || defined( 'WPINC' ) ) {
	echo 'The SDK test suite must run without WordPress loaded.' . PHP_EOL;
	exit( 1 );
}

require_once $edd_sl_sdk_autoloader;
require_once __DIR__ . '/Stubs/wordpress.php';
require_once __DIR__ . '/TestCase.php';
