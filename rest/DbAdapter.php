<?php

/**
 * database Adapter class
 *
 * @author krvsingh
 *
 */
class DatabaseAdapter {
	
	// default value
	private $primary_key = "id";
	private $result_format = array ();
	private $table;
	private $mode;
	
	private $debug = false ;
	
	/**
	 * constructor
	 */
	function __construct() {
	
		//debug mode
		//$this->debug = true ;
		
		//if database not connected then no requests can be processed
		if(!db_is_connected()){
			error_500();
		}
	}
	
	public function setTable($table) {
		$this->table = $table;
	}
	
	public function setResultSetFormat($format) {
		$this->primary_key = $format ['id'];
		$this->result_format = $format ['values'];
	}
	
	protected function ValidateItems( $items ){
		
		if( $this->debug ){
			echo print_r( $items, true ), " ";
		}
		
		if (empty ( $items ) || count ( $items ) == 0) {
			return false;
		} else
			return $items;
	}
	
	public function queryDelete() {
		$this->mode = "queryDelete";
		
		return "DELETE FROM " . $this->table ;
	}
	
	public function queryUpdate( $items ) {
		
		if (! $this->ValidateItems ( $items )) {
			return "" ;
		}
		$this->mode = "queryUpdate";
		
		$sSQL = "UPDATE " . $this->table . " SET " ;
		foreach ($items as $key => $val ) {
			$sSQL .= $key . "='" . $val . "'," ;
		}
		
		return rtrim ( $sSQL, "," );
	}
	
	public function queryInsert( $items ) {
		
		if (! $this->ValidateItems ( $items )) {
			return "" ;
		}
		$this->mode = "queryInsert";
		
		$keys = array_keys ( $items );
		$values = array_values ( $items );
		
		return "INSERT INTO " . $this->table . " ( `" . implode ( "`, `", $keys ) . "` ) " . " VALUES ( '" . implode ( "', '", $values ) . "' )";
	}
	
	public function querySelect($items = array()) {
		
		$sSQL = "SELECT ";
		if (empty ( $items ) || count ( $items ) == 0) {
			$sSQL .= "* ";
		} else {
			$sSQL .= " `" . implode ( "`, `", $items ) . "` ";
		}
		$sSQL .= " FROM " . $this->table;
		
		return $sSQL;
	}
	
	/**
	 * Sample Supported formats:
	 *
	 * /projects/id={value}
	 * /projects/id={value};project_id={'1234'} => where
	 * /id={value}|project_id={'1234'}
	 */
	private function getDbWhere( $queryStr ) {
		
		$t_where = urldecode ( $queryStr );
		
		// replace ';' with ' AND '
		$t_where = preg_replace ( '/(;)/', ' AND ', $t_where );
		
		// replace '|' with ' OR '
		$t_where = preg_replace ( '/(\|)/', ' OR ', $t_where );
		
		return $t_where;
	}
	
	public function queryWhere( $args ) {
		
		if (! $this->ValidateItems ( $args )) {
			return false;
		}
		
		if (is_array ( $args )) {
			if ($args [0] == "search" && ! empty ( $args [1] )) {
				return " WHERE " . $this->getDbWhere ( $args [1] );
			} else {
				return " WHERE " . $this->primary_key . "=" . $args [0];
			}
		} else {
			return " WHERE " . $this->primary_key . "=" . $args;
		}
	}
	
	public function querySubmit( $sql ) {
		
		if ($this->debug) {
			echo $sql, " ";
		}
		
		$rows = db_query_bound ( $sql );
		if (($err = db_error_num ()) > 0) {
			$err = "[" . $err . "] : " . db_error_msg ();
			log_event ( LOG_DATABASE, "SQL : " . $sql . " ERROR : " . $err );
		}
		
		switch ($this->mode) {
			
			case "queryDelete" :
			case "queryUpdate" :
				
				if ($err == "0") {
					if (db_affected_rows () >= 1) {
						ok_202();
					} else {
						$this->error_db();
					}
				} else {
					error_400 ();
				}
			
			case "queryInsert" :
				
				if ($err == "0") {
					http_response_code ( 201 );
					return array( "result" => db_insert_id ( $this->table, $this->primary_key ));
				} else {
					error_400 ();
				}
			
			default :
				return $this->getResultRows ( $rows );
		}
	}
	
	private function getResultRows( $rows ){
		
		$primary_lbl = $this->primary_key;
		$secondary_lbl = $this->result_format;
		
		$results = array ();
		foreach ( $rows as $row ) {
			$t_id = $row [$primary_lbl];
			$t_value = array ();
			$t_value [$primary_lbl] = $t_id;
			
			if (is_array ( $secondary_lbl )) {
				foreach ( $secondary_lbl as $lbl ) {
					$t_value [ $lbl ] = $row [ $lbl ];
				}
			} else {
				$t_value = $row [ $secondary_lbl ];
			}
			
			$results [ $t_id ] = $t_value;
		}
		
		return $results;
	} 

	private function error_db(){
		
		if($this->mode == "queryUpdate"){
			echo " NO OPERATION or Resource Not found" ;
			exit();
		}else {
			error_404 ();
		}
	}
	
}
