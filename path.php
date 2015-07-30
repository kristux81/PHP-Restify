<?php

/**************************
 * Path Settings *
 **************************/
if (isset ( $_SERVER ['SCRIPT_NAME'] )) {
	$t_protocol = 'http';
	if (isset ( $_SERVER ['HTTP_X_FORWARDED_PROTO'] )) {
		$t_protocol = $_SERVER ['HTTP_X_FORWARDED_PROTO'];
	} else if (isset ( $_SERVER ['HTTPS'] ) && (strtolower ( $_SERVER ['HTTPS'] ) != 'off')) {
		$t_protocol = 'https';
	}
	
	// $_SERVER['SERVER_PORT'] is not defined in case of php-cgi.exe
	if (isset ( $_SERVER ['SERVER_PORT'] )) {
		$t_port = ':' . $_SERVER ['SERVER_PORT'];
		if ((':80' == $t_port && 'http' == $t_protocol) || (':443' == $t_port && 'https' == $t_protocol)) {
			$t_port = '';
		}
	} else {
		$t_port = '';
	}
	
	if (isset ( $_SERVER ['HTTP_X_FORWARDED_HOST'] )) { // Support ProxyPass
		$t_hosts = explode ( ',', $_SERVER ['HTTP_X_FORWARDED_HOST'] );
		$t_host = $t_hosts [0];
	} else if (isset ( $_SERVER ['HTTP_HOST'] )) {
		$t_host = $_SERVER ['HTTP_HOST'];
	} else if (isset ( $_SERVER ['SERVER_NAME'] )) {
		$t_host = $_SERVER ['SERVER_NAME'] . $t_port;
	} else if (isset ( $_SERVER ['SERVER_ADDR'] )) {
		$t_host = $_SERVER ['SERVER_ADDR'] . $t_port;
	} else {
		$t_host = 'localhost';
	}
	
	$t_path = str_replace ( basename ( $_SERVER ['PHP_SELF'] ), '', $_SERVER ['PHP_SELF'] );
	$t_url = $t_protocol . '://' . $t_host . $t_path;
} else {
	$t_path = '';
	$t_host = '';
	$t_protocol = '';
}

$g_path = isset ( $t_url ) ? $t_url : 'http://localhost/';

/**
 * path to your images directory (for icons)
 * requires trailing /
 * 
 * @global string $g_icon_path
 */
$g_icon_path = $g_path . 'images/';

$g_short_path = $t_path;

// application root
$g_base_path = realpath ( dirname ( __FILE__ ) );

// lib root.
$g_lib_path = $g_base_path . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;

// core root.
$g_core_path = $g_base_path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;

/** ----------- REST Directories */
// Rest Path
$g_rest_path = $g_base_path . DIRECTORY_SEPARATOR . 'rest' . DIRECTORY_SEPARATOR ;

// Rest models.
$g_rest_models = $g_rest_path . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR ;

/* ------------REST Directories */


$path = array (
		$g_base_path,
		$g_lib_path,
		$g_core_path,
		$g_rest_path,
		$g_rest_models,
		get_include_path () 
);

set_include_path ( implode ( PATH_SEPARATOR, $path ) );
