<?php
require_once ('path.php');
require_once ('constants.php');
require_once ('config.php');
require_once ('logging_api.php');
require_once ('database_api.php');

/**
 * Initialize rest adapter
 */
include_once ('RestController.php');
$restAdapter = new RestController ();

/**
 * Additional inline methods to transform/map/modify data
 * before being presented to database adapter
 */
include_once ('Interceptor.php');

/**
 * Initialize Database adapter
 */
include_once ('DbAdapter.php');
$db_adapter = new DatabaseAdapter ();

/**
 * Add models here
 */
include_once ('Model.php');
include_once ('sfdc_vone_update.php');
$tableHandle = new SfdcVOneUpdate ();

/**
 * register routes => methods here
 */

$resource = 'sfdcvone' ;

/*
 * Urls : http://localhost/ccb/rest/sfdcvone
 * http://localhost/ccb/rest/sfdcvone/{id}
 * http://localhost/ccb/rest/sfdcvone/search/{where query}
 */
$restAdapter->addWire ( $resource, 'GET', array (
		$tableHandle,
		'get' 
) );

/*
 * Url : http://localhost/ccb/rest/sfdcvone
 */
$restAdapter->addWire ( $resource, 'POST', array (
		$tableHandle,
		'insert' 
) );

/*
 * Url : http://localhost/ccb/rest/sfdcvone/{id}
 */
$restAdapter->addWire ( $resource, 'PUT', array (
		$tableHandle,
		'update' 
) );

/*
 * Url : http://localhost/ccb/rest/sfdcvone/{id}
 */
$restAdapter->addWire ( $resource, 'DELETE', array (
		$tableHandle,
		'delete' 
) );

// TBD : Regex based multiple {id:[0-9]+} GET, PUT, DELETE

/**
 * Handle Rest Request ( in the end )
 */
$restAdapter->handle ();

