<?php

/**
 * Model class
 *
 * @author krvsingh
 *
 */
abstract class Model {
	
	// datbase access
	private static $db_interface = null;
	
	// rest request/response parser
	private static $rest_interface = null;
	
	/**
	 * function handle to interceptor function
	 * 
	 * The interceptor function can be used inbetween the Model and Database Adapter
	 * for mapping input schema to Database Table schema
	 */
	private $data_interceptor = null;
	
	/**
	 * implicit wiring flag
	 * 
	 * Setting it to true enables implicit wiring between the model and restcontroller.
	 * Then the rest side function aruments or data is implicity passed unaltered to this parent class.
	 * It does not required to be filtered by the subclasses.
	 * This actually makes the code easier for the people writing the subclasses 
	 * and doing wiring b/w router and their model definitions.
	 * 
	 */
	private static $implicit_wiring = true;
	
	function __construct($table, $interceptor_func = "") {
		global $restAdapter, $db_adapter;
		
		self::$db_interface = $db_adapter;
		self::$db_interface->setTable ( $table );
		
		self::$rest_interface = $restAdapter;
		$this->data_interceptor = $interceptor_func;
	}
	
	static function getDBI() {
		return self::$db_interface;
	}
	
	static function getRI() {
		return self::$rest_interface;
	}
	
	function setInterceptor($interceptor_func) {
		$this->data_interceptor = $interceptor_func;
	}
	
	function get($where_items = array(), $select_items = array()) {
		
		// We are not using the $select_items explicitly through our wiring
		// by default it selects *
		// Using $select_items will require change here i.e function arguments passed. 
		if(self::$implicit_wiring && $this->notSet($where_items)){
			$where_items = func_get_args ();
		}
		
		$dbi = self::$db_interface;
		$select = $dbi->querySelect ( $select_items );
		$where = $dbi->queryWhere ( $where_items );
		if ($where !== false) {
			$select .= $where;
		}
		
		$results = $dbi->querySubmit ( $select );
		return self::$rest_interface->response ( $results );
	}
	
	function insert($insert_items = array()) {
		
		// implicit wiring from restcontroller
		if(self::$implicit_wiring  && $this->notSet($insert_items)){
			$insert_items = func_get_arg ( 0 );
		}
		
		// trigger data modulation first
		$items = $this->trigger_data_modulation ( $insert_items );
		
		$dbi = self::$db_interface;
		$insert = $dbi->queryInsert ( $items );
		$results = $dbi->querySubmit ( $insert );
		return self::$rest_interface->response ( $results );
	}
	
	function update($where_items = array() , $update_items = array()) {
		
		// implicit wiring from restcontroller
		if(self::$implicit_wiring){
			if ($this->notSet ( $where_items )) {
				$where_items = func_get_arg ( 0 );
			}
			if ($this->notSet ( $update_items )) {
				$update_items = func_get_arg ( 1 );
			}
		}
		
		// trigger data modulation first
		$update_items = $this->trigger_data_modulation ( $update_items );
		
		$dbi = self::$db_interface;
		$update = $dbi->queryUpdate ( $update_items );
		$where = $dbi->queryWhere ( $where_items );
		if ($where !== false) {
			$update .= $where;
		}
		
		$results = $dbi->querySubmit ( $update );
		return self::$rest_interface->response ( $results );
	}
	
	function delete($where_items = array()) {
		
		// implicit wiring from restcontroller
		if(self::$implicit_wiring && $this->notSet($where_items)){
			$where_items = func_get_arg ( 0 );
		}
		
		$dbi = self::$db_interface;
		$delete = $dbi->queryDelete ();
		$where = $dbi->queryWhere ( $where_items );
		if ($where !== false) {
			$delete .= $where;
		}
		
		$results = $dbi->querySubmit ( $delete );
		return self::$rest_interface->response ( $results );
	}
	
	function notSet($arr){
		return (empty ( $arr ) || count ( $arr ) == 0) ;
	}
	
	// remove indexes with null value
	function sanitizeArray($arr) {
		foreach ( $arr as $key => $val ) {
			if ($val === null) {
				unset ( $arr [$key] );
			}
		}
		return $arr;
	}
	
	function trigger_data_modulation($data) {
		if (! empty ( $this->data_interceptor ) && function_exists ( $this->data_interceptor )) {
			$data_array = call_user_func ( $this->data_interceptor, $data );
			return $this->sanitizeArray ( $data_array );
		}
		return $this->sanitizeArray ( $data );
	}
}