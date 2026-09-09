<?php
/**
 * @package sentry
 * @subpackage install
 */

global $gBitInstaller;

$gBitInstaller->registerPackageInfo( SENTRY_PKG_NAME, array(
	'description' => 'Report PHP notices and fatals to a Sentry-compatible server (sentry.io or self-hosted GlitchTip/Sentry).',
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
) );

$gBitInstaller->registerPreferences( SENTRY_PKG_NAME, array(
	array( SENTRY_PKG_NAME, 'sentry_dsn', '' ),
	array( SENTRY_PKG_NAME, 'sentry_environment', '' ),
	array( SENTRY_PKG_NAME, 'sentry_report_levels', 'notice,fatal' ),
) );

$gBitInstaller->registerUserPermissions( SENTRY_PKG_NAME, array(
	array( 'p_sentry_admin', 'Can admin the sentry package', 'admin', SENTRY_PKG_NAME ),
) );
