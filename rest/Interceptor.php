<?php

function get_field($array, $field){
	if (array_key_exists ( $field, $array )) {
		return $array [$field];
	}
	return null;
}

/**
 * Additional inline methods to transform/map/modify data
 * before being presented to database adapter
 */

/* 
 Sample Input body :
 -------------------
 
 {"Case":{"attributes":{"type":"Case","url":"/services/data/v34.0/sobjects/Case/500d000000Jvi8iAAB"},
          "Id":"500d000000Jvi8iAAB",
          "Type":"Electrical",
          "Status":"Escalated",
  },
 "ModifiedBy":{"attributes":{"type":"User","url":"/services/data/v34.0/sobjects/User/005d0000002lAtRAAU"},
               "Id":"005d0000002lAtRAAU",
               "Name":"Service"
  },
 "Account":{"attributes":{"type":"Account"},
            "Id":null
  }
}

*/            

/**
 * Mapping input schema to Database Table schema
 * In case of changes in input model or format make changes here and
 * in SfdcVOneUpdate class
 */
function modulate_data_array( $in ){
		// var_dump($in);
	$out = array ();
	$out ['rec_status'] = '0'; // NOT PROCESSED YET
	                           
	// mandatory object : case
	$caseObj = $in ['Case'];
	$out ['caseid'] = $caseObj ['Id'];
	$out ['type'] = get_field ( $caseObj, 'Type' );
	$out ['status'] = get_field ( $caseObj, 'Status' );
	                                
	// optional objects : owner
	$ownerObj = get_field ( $in, 'Owner' );
	if ($ownerObj !== null) {
		$out ['ownerid'] = $ownerObj ['Id'];
	}
	
	// optional objects : modifiedBy
	$modifiedByObj = get_field ( $in, 'ModifiedBy' );
	if ($modifiedByObj !== null) {
		$out ['lastmodifiedbyid'] = $modifiedByObj ['Id'];
	}
	
	// optional objects : Account
	$accountObj = get_field ( $in, 'Account' );
	if ($accountObj !== null) {
		$out ['accountid'] = $accountObj ['Id'];
	}
	
	return $out;
}
