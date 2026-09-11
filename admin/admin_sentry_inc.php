<?php
/**
 * @package sentry
 * @subpackage admin
 */

require_once( KERNEL_PKG_INCLUDE_PATH.'simple_form_functions_lib.php' );

$formSentry = array(
	'sentry_dsn' => array(
		'label' => 'Sentry DSN',
		'note'  => 'Sentry or GlitchTip DSN, e.g. https://&lt;public_key&gt;@host/&lt;project_id&gt;. Leave empty to disable reporting.',
		'type'  => 'input',
	),
	'sentry_environment' => array(
		'label' => 'Environment tag',
		'note'  => 'Optional Sentry environment (live, stage, sandbox). Empty defaults to live when IS_LIVE, else development.',
		'type'  => 'input',
	),
	'sentry_report_levels' => array(
		'label' => 'Report levels',
		'note'  => 'Comma-separated: notice, warning, deprecated, fatal, error. Default: notice,fatal. Does not filter bit_error_log() (channel error_log); those are always sent when a DSN is set.',
		'type'  => 'input',
	),
);
$gBitSmarty->assign( 'formSentry', $formSentry );

if( !empty( $_REQUEST['sentry_prefs'] ) ) {
	simple_set_value( 'sentry_dsn', SENTRY_PKG_NAME );
	simple_set_value( 'sentry_environment', SENTRY_PKG_NAME );
	simple_set_value( 'sentry_report_levels', SENTRY_PKG_NAME );

	if( !empty( $_REQUEST['sentry_send_test'] ) && class_exists( 'SentryReporter' ) ) {
		$testHash = bit_error_build_report_hash(
			E_USER_NOTICE,
			'NOTICE',
			'Bitweaver sentry test event from admin',
			__FILE__,
			__LINE__,
			'php_error'
		);
		SentryReporter::report( $testHash );
		$gBitSmarty->assign( 'sentryTestSent', TRUE );
	}
}
