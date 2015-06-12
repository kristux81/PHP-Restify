<?php

/**
 * Model class
 *
 * @author krvsingh
 *
 */
class Model {
	
	// datbase access
	private static $db_interface = null;
	
	// rest response parser
	private static $rest_interface = null;
	
	function __construct($table) {
		global $restAdapter, $db_adapter;
		
		self::$db_interface = $db_adapter;
		self::$db_interface->setTable ( $table );
		self::$rest_interface = $restAdapter;
	}
	
	static function getDBI() {
		return self::$db_interface;
	}
	
	static function getRI() {
		return self::$rest_interface;
	}
	
	function get($where_items, $select_items = array()) {
		
		$dbi = self::$db_interface;
		$select = $dbi->querySelect ( $select_items );
		$where = $dbi->queryWhere ( $where_items );
		if ($where !== false) {
			$select .= $where;
		}
		
		$results = $dbi->querySubmit ( $select );
		return self::$rest_interface->response ( $results );
	}
	
	function insert( $insert_items) {
		
		$dbi = self::$db_interface;
		$insert = $dbi->queryInsert ( $insert_items );
		$results = $dbi->querySubmit ( $insert );
		return self::$rest_interface->response ( $results );
	}
	
	function update( $where_items, $update_items) {
		
		$dbi = self::$db_interface;
		$update = $dbi->queryUpdate ( $update_items );
		$where = $dbi->queryWhere ( $where_items );
		if ($where !== false) {
			$update .= $where;
		}
		
		$results = $dbi->querySubmit ( $update );
		return self::$rest_interface->response ( $results );
	}
	
	function delete( $where_items ) {
		
		$dbi = self::$db_interface;
		$delete = $dbi->queryDelete();
		$where = $dbi->queryWhere ( $where_items );
		if ($where !== false) {
			$delete .= $where;
		}
		
		$results = $dbi->querySubmit ( $delete );
		return self::$rest_interface->response ( $results );
	}
	
}