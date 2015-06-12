<?php
require_once ('core.php');

/**
 * Initialize rest adapter
 */
include_once ( 'RestController.php' );
$restAdapter = new RestController ();

/**
 * Initialize Database adapter
 */
include_once ( 'DbAdapter.php' );
$db_adapter = new DatabaseAdapter() ;

/**
 * Add models here
 */
include_once ('Model.php');
include_once ('sfdc_vone_update.php');

/**
 * register routes => methods here
 */

/* 
 * Urls : http://localhost/sample/rest/api/sfdcvone
 * 		  http://localhost/sample/rest/api/sfdcvone/{id}
 * 		  http://localhost/sample/rest/api/sfdcvone/search/{where query}
 */
$restAdapter->addRoute ( 'sfdcvone', 'GET', "get_sfdc_v1_data" );

/*
 * Url : http://localhost/sample/rest/api/sfdcvone
 */
$restAdapter->addRoute ( 'sfdcvone', 'POST', "set_sfdc_v1_data" );

/*
 * Url : http://localhost/sample/rest/api/sfdcvone/{id}
 */
$restAdapter->addRoute ( 'sfdcvone', 'PUT', "update_sfdc_v1_data" );

/*
 * Url : http://localhost/sample/rest/api/sfdcvone/{id}
 */
$restAdapter->addRoute ( 'sfdcvone', 'DELETE', "delete_sfdc_v1_data" );



// TBD : Regex based multiple {id:[0-9]+}  GET, PUT, DELETE

/**
 * Handle Rest Request ( in the end )
 */
$restAdapter->handle ();

