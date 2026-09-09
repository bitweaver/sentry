<?php
/**
 * @package sentry
 * @subpackage functions
 */

global $gBitSystem;

$registerHash = array(
	'package_name' => 'sentry',
	'package_path' => dirname( dirname( __FILE__ ) ).'/',
	'homeable'     => FALSE,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'sentry' ) ) {
	require_once( SENTRY_PKG_CLASS_PATH.'SentryReporter.php' );

	// Register only when a DSN is configured; Kernel notify is a no-op otherwise.
	if( $gBitSystem->getConfig( 'sentry_dsn' ) ) {
		bit_error_register_reporter( array( 'SentryReporter', 'report' ) );
	}
}
