<?php

$g_log_levels = array(
	LOG_MIN => 'min',
	LOG_AJAX => 'ajax',
	LOG_LDAP => 'ldap',
	LOG_DATABASE => 'database',
);

function log_event( $p_level, $p_msg ) {
	global $g_log_levels,
	       $g_complete_date_format,
	       $g_log_destination,
		   $g_global_log_level;

	if ( 0 == ( $g_global_log_level & $p_level ) ) {
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
