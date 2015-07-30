<?php

/**************************
 * DataBase Settings *
 **************************/

/**
 * global database type
 */
$g_db_type = 'mysqli' ;

$g_hostname = 'localhost';
$g_db_username = 'root';
$g_db_password = '';
$g_database_name = 'rest';

/**
 * table names
 * @global array $g_db_table
 */
$g_db_table['sfdc_vone_update']	  = 'sfdc_vone_update';


/**************************
 * Date & Time Settings
 **************************/

$g_server_time_zone = 'Europe/Paris';
$g_complete_date_format = 'Y-m-d H:i T';

/**************************
 * Logging & Debug Settings
 **************************/
/**
 * UNIX : 'file:/home/user/ccb.log'
 * WIN  : 'file:d:/ccb.log'
 */
$g_log_destination = 'file:d:/ccb.log';
$g_global_log_level = LOG_AJAX ;

//database queries in log (ON/OFF)
// log_level must be >= LOG_DATABASE
$g_db_log_queries = OFF ;
