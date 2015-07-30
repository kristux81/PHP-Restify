<?php
class SfdcVOneUpdate extends Model {
	
	// table name ;
	private $table = "sfdc_vone_update";
	
	// database columns
	private $allFields = array(
			'rec_status',
			'caseid',
			'ownerid',
			'accountid',
			'lastmodifiedbyid',
			'type',
			'status',
	);
	
	// primary key
	private $primary_idx = 'id';
	
	/**
	 * constructor : with table name and interceptors (if any)
	 */
	function __construct() {
		parent::__construct ( $this->table, "modulate_data_array" );
		parent::getDBI ()->setResultSetFormat ( array (
				"id" => $this->primary_idx,
				"values" => $this->allFields 
		) );
	}
}
