<?php
/**
 * Minimal Sentry / GlitchTip HTTP reporter (Store API).
 *
 * Works with sentry.io and self-hosted Sentry-compatible servers (e.g. GlitchTip).
 * No Composer SDK required — POSTs JSON to /api/{project}/store/.
 *
 * @package sentry
 */

class SentryReporter {
	/**
	 * Kernel error-reporter callback.
	 *
	 * @param array $pHash from bit_error_build_report_hash()
	 */
	public static function report( $pHash ) {
		global $gBitSystem;

		if( !is_object( $gBitSystem ) || !$gBitSystem->isPackageActive( 'sentry' ) ) {
			return;
		}

		$dsn = trim( (string) $gBitSystem->getConfig( 'sentry_dsn', '' ) );
		if( $dsn === '' ) {
			return;
		}

		if( !self::levelAllowed( $pHash, $gBitSystem->getConfig( 'sentry_report_levels', 'notice,fatal' ) ) ) {
			return;
		}

		$parsed = self::parseDsn( $dsn );
		if( empty( $parsed ) ) {
			error_log( 'sentry: invalid sentry_dsn' );
			return;
		}

		$event = self::buildEvent( $pHash, $gBitSystem->getConfig( 'sentry_environment', '' ) );
		self::postStore( $parsed, $event );
	}

	/**
	 * @param array $pHash
	 * @param string $pLevelsCsv e.g. "notice,fatal,warning"
	 * @return bool
	 */
	public static function levelAllowed( $pHash, $pLevelsCsv ) {
		$allowed = array();
		foreach( explode( ',', strtolower( (string) $pLevelsCsv ) ) as $token ) {
			$token = trim( $token );
			if( $token !== '' ) {
				$allowed[$token] = TRUE;
			}
		}
		if( empty( $allowed ) ) {
			return FALSE;
		}

		$errtype = strtolower( (string) self::hashGet( $pHash, 'errtype', '' ) );
		$errno = (int) self::hashGet( $pHash, 'errno', 0 );

		if( !empty( $allowed['notice'] ) && ( $errno & ( E_NOTICE | E_USER_NOTICE ) || strpos( $errtype, 'notice' ) !== FALSE ) ) {
			return TRUE;
		}
		if( !empty( $allowed['warning'] ) && ( $errno & ( E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING ) || strpos( $errtype, 'warning' ) !== FALSE ) ) {
			return TRUE;
		}
		if( !empty( $allowed['deprecated'] ) && ( $errno & ( E_DEPRECATED | E_USER_DEPRECATED ) || strpos( $errtype, 'deprecated' ) !== FALSE ) ) {
			return TRUE;
		}
		if( !empty( $allowed['fatal'] ) && (
			$errno & ( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR )
			|| strpos( $errtype, 'fatal' ) !== FALSE
			|| strpos( $errtype, 'parse' ) !== FALSE
			|| strpos( $errtype, 'compile' ) !== FALSE
			|| strpos( $errtype, 'recoverable' ) !== FALSE
		) ) {
			return TRUE;
		}
		if( !empty( $allowed['error'] ) && strpos( $errtype, 'error' ) !== FALSE ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param string $pDsn
	 * @return array|null keys: scheme, host, port, path_prefix, public_key, project_id, store_url
	 */
	public static function parseDsn( $pDsn ) {
		$parts = @parse_url( trim( $pDsn ) );
		if( empty( $parts['scheme'] ) || empty( $parts['host'] ) || empty( $parts['user'] ) || empty( $parts['path'] ) ) {
			return NULL;
		}

		$projectId = trim( $parts['path'], '/' );
		// path may be /<project> or /prefix/<project>
		if( strpos( $projectId, '/' ) !== FALSE ) {
			$segs = explode( '/', $projectId );
			$projectId = array_pop( $segs );
			$pathPrefix = '/'.implode( '/', $segs );
		} else {
			$pathPrefix = '';
		}
		if( $projectId === '' || !preg_match( '/^[A-Za-z0-9_-]+$/', $projectId ) ) {
			return NULL;
		}

		$port = empty( $parts['port'] ) ? '' : ':'.$parts['port'];
		$storeUrl = $parts['scheme'].'://'.$parts['host'].$port.$pathPrefix.'/api/'.$projectId.'/store/';

		return array(
			'scheme'      => $parts['scheme'],
			'host'        => $parts['host'],
			'port'        => isset( $parts['port'] ) ? $parts['port'] : NULL,
			'path_prefix' => $pathPrefix,
			'public_key'  => $parts['user'],
			'secret_key'  => isset( $parts['pass'] ) ? $parts['pass'] : '',
			'project_id'  => $projectId,
			'store_url'   => $storeUrl,
		);
	}

	/**
	 * @param array $pHash
	 * @param string $pEnvironment
	 * @return array
	 */
	public static function buildEvent( $pHash, $pEnvironment = '' ) {
		$errtype = self::hashGet( $pHash, 'errtype', 'Error' );
		$message = self::hashGet( $pHash, 'message', '' );
		$file = self::hashGet( $pHash, 'file', '' );
		$line = (int) self::hashGet( $pHash, 'line', 0 );
		$level = self::sentryLevel( $errtype, (int) self::hashGet( $pHash, 'errno', 0 ) );

		$eventId = str_replace( '-', '', self::uuid4() );
		$timestamp = gmdate( 'Y-m-d\TH:i:s' );

		$ip = self::hashGet( $pHash, 'ip', '' );
		$url = self::hashGet( $pHash, 'url', '' );
		if( $url === '' ) {
			$host = self::hashGet( $pHash, 'host', '' );
			$uri = self::hashGet( $pHash, 'uri', '' );
			$url = ( $host !== '' ? 'https://'.$host : '' ).$uri;
		}
		$userHash = ( !empty( $pHash['user'] ) && is_array( $pHash['user'] ) ) ? $pHash['user'] : array();

		$event = array(
			'event_id'    => $eventId,
			'timestamp'   => $timestamp,
			'platform'    => 'php',
			'logger'      => 'bitweaver.sentry',
			'level'       => $level,
			'server_name' => self::hashGet( $pHash, 'host', php_uname( 'n' ) ),
			'culprit'     => $file.( $line ? ':'.$line : '' ),
			'message'     => $errtype.': '.$message,
			'tags'        => array(
				'errtype' => $errtype,
				'channel' => self::hashGet( $pHash, 'channel', 'php_error' ),
				'sapi'    => self::hashGet( $pHash, 'sapi', PHP_SAPI ),
				'is_live' => !empty( $pHash['is_live'] ) ? 'y' : 'n',
				'ip'      => $ip,
				'login'   => self::hashGet( $userHash, 'login', '' ),
			),
			// Client IP / UA / URL — not the GlitchTip ingest connection IP.
			'user'        => array(
				'id'         => self::hashGet( $userHash, 'id', NULL ),
				'username'   => self::hashGet( $userHash, 'login', NULL ),
				'email'      => self::hashGet( $userHash, 'email', NULL ),
				'ip_address' => $ip !== '' ? $ip : NULL,
			),
			'request'     => array(
				'url'     => $url,
				'method'  => self::hashGet( $pHash, 'request_method', NULL ),
				'headers' => array(
					'User-Agent' => self::hashGet( $pHash, 'user_agent', '' ),
					'Referer'    => self::hashGet( $pHash, 'referrer', '' ),
				),
				'env'     => array(
					'REMOTE_ADDR' => $ip,
					'HTTP_HOST'   => self::hashGet( $pHash, 'host', '' ),
				),
			),
			'extra'       => array(
				'file'       => $file,
				'line'       => $line,
				'errno'      => (int) self::hashGet( $pHash, 'errno', 0 ),
				'script'     => self::hashGet( $pHash, 'script', '' ),
				'uri'        => self::hashGet( $pHash, 'uri', '' ),
				'url'        => $url,
				'referrer'   => self::hashGet( $pHash, 'referrer', '' ),
				'user_agent' => self::hashGet( $pHash, 'user_agent', '' ),
				'ip'         => $ip,
				'acct'       => self::formatAcct( $userHash ),
				'db'         => self::hashGet( $pHash, 'db', '' ),
				'stack'      => self::hashGet( $pHash, 'stack', '' ),
			),
		);

		$env = trim( (string) $pEnvironment );
		if( $env === '' ) {
			$env = !empty( $pHash['is_live'] ) ? 'live' : 'development';
		}
		$event['environment'] = $env;

		return $event;
	}

	/**
	 * @param array $pParsed from parseDsn()
	 * @param array $pEvent
	 */
	public static function postStore( $pParsed, $pEvent ) {
		$body = json_encode( $pEvent );
		if( $body === FALSE ) {
			error_log( 'sentry: json_encode failed' );
			return;
		}

		$auth = sprintf(
			'Sentry sentry_version=7, sentry_client=bitweaver-sentry/1.0, sentry_key=%s',
			$pParsed['public_key']
		);
		if( !empty( $pParsed['secret_key'] ) ) {
			$auth .= ', sentry_secret='.$pParsed['secret_key'];
		}

		$headers = array(
			'Content-Type: application/json',
			'X-Sentry-Auth: '.$auth,
			'Content-Length: '.strlen( $body ),
		);

		if( function_exists( 'curl_init' ) ) {
			$ch = curl_init( $pParsed['store_url'] );
			curl_setopt( $ch, CURLOPT_POST, TRUE );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 3 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE );
			$result = curl_exec( $ch );
			$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$err = curl_error( $ch );
			curl_close( $ch );
			if( $result === FALSE || ( $code > 0 && ( $code < 200 || $code >= 300 ) ) ) {
				error_log( 'sentry: store failed http='.$code.' '.$err );
			}
			return;
		}

		$context = stream_context_create( array(
			'http' => array(
				'method'  => 'POST',
				'header'  => implode( "\r\n", $headers ),
				'content' => $body,
				'timeout' => 3,
				'ignore_errors' => TRUE,
			),
		) );
		$result = @file_get_contents( $pParsed['store_url'], FALSE, $context );
		if( $result === FALSE ) {
			error_log( 'sentry: store failed (file_get_contents)' );
		}
	}

	/**
	 * @param string $pErrtype
	 * @param int $pErrno
	 * @return string
	 */
	public static function sentryLevel( $pErrtype, $pErrno ) {
		if( $pErrno & ( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR ) || stripos( $pErrtype, 'fatal' ) !== FALSE ) {
			return 'fatal';
		}
		if( $pErrno & ( E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_DEPRECATED | E_USER_DEPRECATED ) || stripos( $pErrtype, 'warning' ) !== FALSE || stripos( $pErrtype, 'deprecated' ) !== FALSE ) {
			return 'warning';
		}
		if( $pErrno & ( E_NOTICE | E_USER_NOTICE ) || stripos( $pErrtype, 'notice' ) !== FALSE ) {
			return 'info';
		}
		return 'error';
	}

	/**
	 * @return string UUID v4
	 */
	public static function uuid4() {
		$data = random_bytes( 16 );
		$data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 );
		$data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/**
	 * @param array $pHash
	 * @param string $pKey
	 * @param mixed $pDefault
	 * @return mixed
	 */
	protected static function hashGet( $pHash, $pKey, $pDefault = NULL ) {
		return ( is_array( $pHash ) && array_key_exists( $pKey, $pHash ) && $pHash[$pKey] !== NULL && $pHash[$pKey] !== '' )
			? $pHash[$pKey]
			: $pDefault;
	}

	/**
	 * Match bit_error_string ACCT line shape (no secrets beyond login/email).
	 *
	 * @param array $pUser
	 * @return string
	 */
	protected static function formatAcct( $pUser ) {
		if( empty( $pUser ) || !is_array( $pUser ) ) {
			return 'User unknown';
		}
		$id = self::hashGet( $pUser, 'id', NULL );
		$login = self::hashGet( $pUser, 'login', NULL );
		$email = self::hashGet( $pUser, 'email', NULL );
		if( $id === NULL && $login === NULL && $email === NULL ) {
			return 'User unknown';
		}
		return 'ID: '.( $id !== NULL ? $id : '' ).' - Login: '.( $login ? $login : '' ).' - e-mail: '.( $email ? $email : '' );
	}
}
