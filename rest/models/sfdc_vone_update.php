<?php

class SfdcVOneUpdate extends Model{
	
	// table name ;
	private $table = "salesforce_versionone_update" ;
	
	// database columns
	private $allFields = array( 'sf_case_field_name', 
			                    'changed_at', 
			                    'changed_by',
			                    'old_value',
			                    'new_value',
			                    'status',
                            	'sf_case_number',
			                    'vone_defect_id',
			                    'user_notified',
			                    'user_notifed_at'
	                          );
	
	// primary key
	private $primary_idx = 'id';
	
	function __construct(){
		
		parent::__construct ( $this->table );
		parent::getDBI ()->setResultSetFormat ( array (
				"id" => $this->primary_idx,
				"values" => $this->allFields 
		) );
	}

}


function get_sfdc_v1_data() {
	return (new SfdcVOneUpdate())->get(func_get_args ());
}

function set_sfdc_v1_data() {
	return (new SfdcVOneUpdate())->insert(func_get_arg(0));
}

function update_sfdc_v1_data(){
	return (new SfdcVOneUpdate())->update(func_get_arg(0), func_get_arg(1));
}

function delete_sfdc_v1_data(){
	return (new SfdcVOneUpdate())->delete(func_get_arg(0));
}
