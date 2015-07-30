<?php

$g_log_levels = array(
	LOG_MIN => '',
	LOG_AJAX => 'AJAX',
	LOG_REST => 'REST',
	LOG_LDAP => 'LDAP',
	LOG_DATABASE => 'DB',
);

function log_event( $p_level, $p_msg ) {
	global $g_log_levels,
	       $g_complete_date_format,
	       $g_log_destination,
		   $g_global_log_level;

	if ( $p_level > $g_global_log_level ) {
		return;
	}

	$t_level = $g_log_levels[$p_level];

	$t_php_event = date( $g_complete_date_format ) . ' ' . $t_level . ' ' . $p_msg;
	list( $t_destination, $t_modifiers ) = explode( ':', $g_log_destination , 2 );

	if( 'file' == $t_destination ){
		error_log( $t_php_event . PHP_EOL, 3, $t_modifiers );
	}else {
		error_log( $t_php_event . PHP_EOL );
	}
}
