<?php

set_time_limit('6000'); 

/**
 * requires adodb library
 */
 require_once( 'adodb' . DIRECTORY_SEPARATOR . 'adodb.inc.php' );

/**
 * An array in which all executed queries are stored.  This is used for profiling
 * @global array $g_queries_array
 	 */
$g_queries_array = array();

/**
 * Stores whether a database connection was succesfully opened.
 * @global bool $g_db_connected
 	 */
$g_db_connected = false;


/**
 * set adodb fetch mode
 * @global bool $ADODB_FETCH_MODE
 	 */
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

/**
 * Tracks the query parameter count for use with db_aparam().
 * @global int $g_db_param_count
 */
$g_db_param_count = 0;

/**
 * Open a connection to the database.
 * @param string $p_dsn Database connection string ( specified instead of other params)
 * @param string $p_hostname Database server hostname
 * @param string $p_username database server username
 * @param string $p_password database server password
 * @param string $p_database_name database name
 * @param string $p_db_schema Schema name (only used if database type is DB2)
 * @param bool $p_pconnect Use a Persistent connection to database
 * @return bool indicating if the connection was successful
 */
function db_connect( $p_dsn, $p_hostname = null, $p_username = null, $p_password = null, $p_database_name = null, $p_pconnect = false ) {
	global $g_db_connected,
	       $g_db,
	       $g_db_type;
	
	
	$t_db_type = $g_db_type;

	if( !db_check_database_support( $t_db_type ) ) {
		trigger_error( 'PHP Support for database is not enabled', ERROR );
	}

	if( $p_dsn === false ) {
		$g_db = ADONewConnection( $t_db_type );

		if( $p_pconnect ) {
			$t_result = $g_db->PConnect( $p_hostname, $p_username, $p_password, $p_database_name );
		} else {
			$t_result = $g_db->Connect( $p_hostname, $p_username, $p_password, $p_database_name );
		}
	} else {
		$g_db = ADONewConnection( $p_dsn );
		$t_result = $g_db->IsConnected();
	}

	if( $t_result ) {
	   db_query_bound( 'SET NAMES UTF8' );
	} else {
		trigger_error( "DATABASE CONNECTION FAILED", ERROR );
		return false;
	}

	$g_db_connected = true;

	return true;
}

/**
 * Returns whether a connection to the database exists
 * @global stores database connection state
 * @return bool indicating if the a database connection has been made
 */
function db_is_connected() {
	global $g_db_connected;

	return $g_db_connected;
}

/**
 * Returns whether php supprot for a database is enabled
 * @return bool indicating if php current supports the given database type
 */
function db_check_database_support( $p_db_type ) {
	$t_support = false;
	switch( $p_db_type ) {
		case 'mysql':
			$t_support = function_exists( 'mysql_connect' );
			break;
		case 'mysqli':
			$t_support = function_exists( 'mysqli_connect' );
			break;
		default:
			$t_support = false;
	}
	return $t_support;
}


/**
 * execute query, requires connection to be opened
 * An error will be triggered if there is a problem executing the query.
 * @global array of previous executed queries for profiling
 * @global adodb database connection object
 * @global boolean indicating whether queries array is populated
 * @param string $p_query Query string to execute
 * @param int $p_limit Number of results to return
 * @param int $p_offset offset query results for paging
 * @return ADORecordSet|bool adodb result set or false if the query failed.
 * @deprecated db_query_bound should be used in preference to this function. This function will likely be removed in 1.2.0 final
 */
function db_query( $p_query, $p_limit = -1, $p_offset = -1 ) {
	global $g_queries_array, $g_db, $g_db_log_queries;

	if( ON == $g_db_log_queries ) {
		$t_start = microtime(true);

		$t_backtrace = debug_backtrace();
		$t_caller = basename( $t_backtrace[0]['file'] );
		$t_caller .= ":" . $t_backtrace[0]['line'];

		# Is this called from another function?
		if( isset( $t_backtrace[1] ) ) {
			$t_caller .= ' ' . $t_backtrace[1]['function'] . '()';
		} else {
			# or from a script directly?
			$t_caller .= ' ' . $_SERVER['SCRIPT_NAME'];
		}
	}

	if(( $p_limit != -1 ) || ( $p_offset != -1 ) ) {
		$t_result = $g_db->SelectLimit( $p_query, $p_limit, $p_offset );
	} else {
		$t_result = $g_db->Execute( $p_query );
	}

	if( ON == $g_db_log_queries ) {
		$t_elapsed = number_format( microtime(true) - $t_start, 4 );

		log_event( LOG_DATABASE, var_export( array( $p_query, $t_elapsed, $t_caller ), true ) );
		array_push( $g_queries_array, array( $p_query, $t_elapsed, $t_caller ) );
	} else {
		array_push( $g_queries_array, 1 );
	}

	if( !$t_result ) {
		trigger_error( "DATABASE Query : [".$p_query."] Failed " , ERROR );
		return false;
	} else {
		return $t_result;
	}
}

/**
 * execute query, requires connection to be opened
 * An error will be triggered if there is a problem executing the query.
 * @global array of previous executed queries for profiling
 * @global adodb database connection object
 * @global boolean indicating whether queries array is populated
 * @param string $p_query Parameterlised Query string to execute
 * @param array $arr_parms Array of parameters matching $p_query
 * @param int $p_limit Number of results to return
 * @param int $p_offset offset query results for paging
 * @return ADORecordSet|bool adodb result set or false if the query failed.
 */
function db_query_bound( $p_query, $arr_parms = null, $p_limit = -1, $p_offset = -1 ) {
	global $g_queries_array, $g_db, $g_db_log_queries, $g_db_param_count;

	if( ON == $g_db_log_queries ) {
		$t_start = microtime(true);

		$t_backtrace = debug_backtrace();
		$t_caller = basename( $t_backtrace[0]['file'] );
		$t_caller .= ":" . $t_backtrace[0]['line'];

		# Is this called from another function?
		if( isset( $t_backtrace[1] ) ) {
			$t_caller .= ' ' . $t_backtrace[1]['function'] . '()';
		} else {
			# or from a script directly?
			$t_caller .= ' - ';
		}
	}

	if(( $p_limit != -1 ) || ( $p_offset != -1 ) ) {
		$t_result = $g_db->SelectLimit( $p_query, $p_limit, $p_offset, $arr_parms );
	} else {
		$t_result = $g_db->Execute( $p_query, $arr_parms );
	}

	if( ON == $g_db_log_queries ) {
		$t_elapsed = number_format( microtime(true) - $t_start, 4 );

		$lastoffset = 0;
		$i = 1;
		if( !( is_null( $arr_parms ) || empty( $arr_parms ) ) ) {
			while( preg_match( '/(\?)/', $p_query, $matches, PREG_OFFSET_CAPTURE, $lastoffset ) ) {
				if( $i <= count( $arr_parms ) ) {
					if( is_null( $arr_parms[$i - 1] ) ) {
						$replace = 'NULL';
					}
					else if( is_string( $arr_parms[$i - 1] ) ) {
						$replace = "'" . $arr_parms[$i - 1] . "'";
					}
					else if( is_integer( $arr_parms[$i - 1] ) || is_float( $arr_parms[$i - 1] ) ) {
						$replace = (float) $arr_parms[$i - 1];
					}
					else if( is_bool( $arr_parms[$i - 1] ) ) {
						$replace = $arr_parms[$i - 1] ;
					} else {
						echo( "Invalid argument type passed to query_bound(): $i" );
						exit( 1 );
					}
					$p_query = utf8_substr( $p_query, 0, $matches[1][1] ) . $replace . utf8_substr( $p_query, $matches[1][1] + utf8_strlen( $matches[1][0] ) );
					$lastoffset = $matches[1][1] + utf8_strlen( $replace );
				} else {
					$lastoffset = $matches[1][1] + 1;
				}
				$i++;
			}
		}
		log_event( LOG_DATABASE, var_export( array( $p_query, $t_elapsed, $t_caller ), true ) );
		array_push( $g_queries_array, array( $p_query, $t_elapsed, $t_caller ) );
	} else {
		array_push( $g_queries_array, 1 );
	}

	# We can't reset the counter because we have queries being built
	# and executed while building bigger queries in filter_api. -jreese
	# $g_db_param_count = 0;

	if( !$t_result ) {
		trigger_error( "DATABASE Query : [".$p_query."] Failed " , ERROR );
		return false;
	} else {
		return $t_result;
	}
}

/**
 * Generate a string to insert a parameter into a database query string
 * @return string 'wildcard' matching a paramater in correct ordered format for the current database.
 */
function db_param() {
	global $g_db;
	global $g_db_param_count;

	return $g_db->Param( $g_db_param_count++ );
}

/**
 * Retrieve number of rows returned for a specific database query
 * @param ADORecordSet $p_result Database Query Record Set to retrieve record count for.
 * @return int Record Count
 */
function db_num_rows( $p_result ) {
	global $g_db;

	return $p_result->RecordCount();
}

/**
 * Retrieve number of rows affected by a specific database query
 * @param ADORecordSet $p_result Database Query Record Set to retrieve affected rows for.
 * @return int Affected Rows
 */
function db_affected_rows() {
	global $g_db;

	return $g_db->Affected_Rows();
}

/**
 * Retrieve the next row returned from a specific database query
 * @param bool|ADORecordSet $p_result Database Query Record Set to retrieve next result for.
 * @return array Database result
 */
function db_fetch_array( &$p_result ) {
	global $g_db, $g_db_type;

	if( $p_result->EOF ) {
		return false;
	}

	# mysql obeys FETCH_MODE_BOTH, hence ->fields works, other drivers do not support this
	if( $g_db_type == 'mysql' || $g_db_type == 'odbc_mssql' ) {
		$t_array = $p_result->fields;
		$p_result->MoveNext();
		return $t_array;
	} else {
		$t_row = $p_result->GetRowAssoc( false );
		static $t_array_result;
		static $t_array_fields;

		if ($t_array_result != $p_result) {
			// new query
			$t_array_result = $p_result;
			$t_array_fields = null;
		} else {
			if ( $t_array_fields === null ) {
				$p_result->MoveNext();
				return $t_row;
			}
		}

		$t_convert = false;
		$t_fieldcount = $p_result->FieldCount();
		for( $i = 0; $i < $t_fieldcount; $i++ ) {
			if (isset( $t_array_fields[$i] ) ) {
				$t_field = $t_array_fields[$i];
			} else {
				$t_field = $p_result->FetchField( $i );
				$t_array_fields[$i] = $t_field;
			}
			switch( $t_field->type ) {
				case 'bool':
					switch( $t_row[$t_field->name] ) {
						case 'f':
							$t_row[$t_field->name] = false;
							break;
						case 't':
							$t_row[$t_field->name] = true;
							break;
					}
					$t_convert= true;
					break;
				default :
					break;
			}
		}

		if ( $t_convert == false ) {
			$t_array_fields = null;
		}
		$p_result->MoveNext();
		return $t_row;
	}
}

/**
 * Retrieve a result returned from a specific database query
 * @param bool|ADORecordSet $p_result Database Query Record Set to retrieve next result for.
 * @param int $p_index1 Row to retrieve (optional)
 * @param int $p_index2 Column to retrieve (optional)
 * @return mixed Database result
 */
function db_result( $p_result, $p_index1 = 0, $p_index2 = 0 ) {
	global $g_db;

	if( $p_result && ( db_num_rows( $p_result ) > 0 ) ) {
		$p_result->Move( $p_index1 );
		$t_result = $p_result->GetArray();

		if( isset( $t_result[0][$p_index2] ) ) {
			return $t_result[0][$p_index2];
		}

		// The numeric index doesn't exist. FETCH_MODE_ASSOC may have been used.
		// Get 2nd dimension and make it numerically indexed
		$t_result = array_values( $t_result[0] );
		return $t_result[$p_index2];
	}

	return false;
}

/**
 * return the last inserted id for a specific database table
 * @param string $p_table a valid database table name
 * @return int last successful insert id
 */
function db_insert_id( $p_table = null, $p_field = "id" ) {
	global $g_db;

	return $g_db->Insert_ID();
}

/**
 * Check if the specified table exists.
 * @param string $p_table_name a valid database table name
 * @return bool indicating whether the table exists
 */
function db_table_exists( $p_table_name ) {
	global $g_db, $g_db_schema;

	if( empty( $p_table_name ) ) {
		return false;
	}

	if( db_is_db2() ) {
		// must pass schema
		$t_tables = $g_db->MetaTables( 'TABLE', false, '', $g_db_schema );
	} else {
		$t_tables = $g_db->MetaTables( 'TABLE' );
	}

	# Can't use in_array() since it is case sensitive
	$t_table_name = utf8_strtolower( $p_table_name );
	foreach( $t_tables as $t_current_table ) {
		if( utf8_strtolower( $t_current_table ) == $t_table_name ) {
			return true;
		}
	}

	return false;
}

/**
 * Check if the specified table index exists.
 * @param string $p_table_name a valid database table name
 * @param string $p_index_name a valid database index name
 * @return bool indicating whether the index exists
 */
function db_index_exists( $p_table_name, $p_index_name ) {
	global $g_db, $g_db_schema;

	if( empty( $p_index_name ) || empty( $p_table_name ) ) {
		return false;

		// no index found
	}

	$t_indexes = $g_db->MetaIndexes( $p_table_name );

	# Can't use in_array() since it is case sensitive
	$t_index_name = utf8_strtolower( $p_index_name );
	foreach( $t_indexes as $t_current_index_name => $t_current_index_obj ) {
		if( utf8_strtolower( $t_current_index_name ) == $t_index_name ) {
			return true;
		}
	}
	return false;
}

/**
 * Check if the specified field exists in a given table
 * @param string $p_field_name a database field name
 * @param string $p_table_name a valid database table name
 * @return bool indicating whether the field exists
 */
function db_field_exists( $p_field_name, $p_table_name ) {
	global $g_db;
	$columns = db_field_names( $p_table_name );
	return in_array( $p_field_name, $columns );
}

/**
 * Retrieve list of fields for a given table
 * @param string $p_table_name a valid database table name
 * @return array array of fields on table
 */
function db_field_names( $p_table_name ) {
	global $g_db;
	$columns = $g_db->MetaColumnNames( $p_table_name );
	return is_array( $columns ) ? $columns : array();
}

/**
 * Returns the last error number. The error number is reset after every call to Execute(). If 0 is returned, no error occurred.
 * @return int last error number
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_error_num() {
	global $g_db;

	return $g_db->ErrorNo();
}

/**
 * Returns the last status or error message. Returns the last status or error message. The error message is reset when Execute() is called.
 * This can return a string even if no error occurs. In general you do not need to call this function unless an ADOdb function returns false on an error.
 * @return string last error string
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_error_msg() {
	global $g_db;

	return $g_db->ErrorMsg();
}

/**
 * close the connection.
 * Not really necessary most of the time since a connection is automatically closed when a page finishes loading.
 */
function db_close() {
	global $g_db;

	$t_result = $g_db->Close();
}

/**
 * prepare a string before DB insertion
 * @param string $p_string unprepared string
 * @return string prepared database query string
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 */
function db_prepare_string( $p_string ) {
	global $g_db, $g_db_type;
	$t_db_type = $g_db_type;

	switch( $t_db_type ) {
		case 'mysql':
			return mysql_real_escape_string( $p_string );
			
		case 'mysqli':
			# For some reason mysqli_escape_string( $p_string ) always returns an empty
			# string.  This is happening with PHP v5.0.2.
			$t_escaped = $g_db->qstr( $p_string, false );
			return utf8_substr( $t_escaped, 1, utf8_strlen( $t_escaped ) - 2 );
	}
}

/**
 * prepare a binary string before DB insertion
 * @param string $p_string unprepared binary data
 * @return string prepared database query string
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_binary_string( $p_string ) {

	return '\'' . db_prepare_string( $p_string ) . '\'';
}

/**
 * prepare a int for database insertion.
 * @param int $p_int integer
 * @return int integer
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_int( $p_int ) {
	return (int) $p_int;
}

/**
 * prepare a double for database insertion.
 * @param double $p_double double
 * @return double double
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_double( $p_double ) {
	return (double) $p_double;
}

/**
 * prepare a boolean for database insertion.
 * @param boolean $p_boolean boolean
 * @return int integer representing boolean
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_bool( $p_bool ) {
	return (int) (bool) $p_bool;
}

/**
 * return current timestamp for DB
 * @todo add param bool $p_gmt whether to use GMT or current timezone (default false)
 * @return string Formatted Date for DB insertion e.g. 1970-01-01 00:00:00 ready for database insertion
 */
function db_now() {
	global $g_db;

	return time();
}

/**
 * convert minutes to a time format [h]h:mm
 * @param int $p_min integer representing number of minutes
 * @return string representing formatted duration string in hh:mm format.
 */
function db_minutes_to_hhmm( $p_min = 0 ) {
	return sprintf( '%02d:%02d', $p_min / 60, $p_min % 60 );
}

/**
 * A helper function that generates a case-sensitive or case-insensitive like phrase based on the current db type.
 * The field name and value are assumed to be safe to insert in a query (i.e. already cleaned).
 * @param string $p_field_name The name of the field to filter on.
 * @param bool $p_case_sensitive true: case sensitive, false: case insensitive
 * @return string returns (field LIKE 'value') OR (field ILIKE 'value')
 */
function db_helper_like( $p_field_name, $p_case_sensitive = false ) {

	return "($p_field_name 'LIKE' " . db_param() . ')';
}


/**
 * count queries
 * @return int
 */
function db_count_queries() {
	global $g_queries_array;

	return count( $g_queries_array );
}

/**
 * count unique queries
 * @return int
 */
function db_count_unique_queries() {
	global $g_queries_array;

	$t_unique_queries = 0;
	$t_shown_queries = array();
	foreach( $g_queries_array as $t_val_array ) {
		if( !in_array( $t_val_array[0], $t_shown_queries ) ) {
			$t_unique_queries++;
			array_push( $t_shown_queries, $t_val_array[0] );
		}
	}
	return $t_unique_queries;
}

/**
 * get total time for queries
 * @return int
 */
function db_time_queries() {
	global $g_queries_array;
	$t_count = count( $g_queries_array );
	$t_total = 0;
	for( $i = 0;$i < $t_count;$i++ ) {
		$t_total += $g_queries_array[$i][1];
	}
	return $t_total;
}

/**
 * get database table name
 * @return string containing full database table name
 */
function db_get_table( $p_option ) {
	global $g_db_table;
	
	if(isset($g_db_table[$p_option])){
		return $g_db_table[$p_option] ;
	}
	else {
		trigger_error( "Table : [" . $p_option ."] DOES NOT EXIST", ERROR );
	}
}

// connect database
db_connect( false, $g_hostname, $g_db_username, $g_db_password, $g_database_name );
