<?php

define ( 'OFF', 0 );
define ( 'ON', 1 );

# error types
define( 'ERROR', E_USER_ERROR );
define( 'WARNING', E_USER_WARNING );
define( 'NOTICE', E_USER_NOTICE );

# system logging
#  logging levels, can be OR'd together
define( 'LOG_NONE',     0 );  # no logging
define( 'LOG_MIN',      1 );  # minimum
define( 'LOG_AJAX',     2 );  # logging for AJAX / XmlHttpRequests
define( 'LOG_REST',     3 );  # logging for REST API
define( 'LOG_LDAP',     4  );  # logging for ldap
define( 'LOG_DATABASE', 5 );  # logging for database

