<?php

/**
 * Rest Controller class
 *
 * @author krvsingh
 *
 */
class RestController {

	private $http_method;
	private $http_accept_type = array ();
	private $http_content_type = array ();
	private $rest_url;
	
	private $debug = false ;

	static $verbs = array("GET", "POST", "PUT", "DELETE");
	
	static $routes = array ( "GET" => array('isalive' => "alive"),
	                        );
	
	static $content = array (
			'json' => array (
					"application/json",
					"text/json"
			),
			'xml' => array (
					"application/xml",
					"application/xhtml+xml",
					"text/xml"
			),
			'text' => array (
					"text/plain",
					"text/html"
			)
	);


	/**
	 * constructor
	*/
	function __construct() {

		//debug mode
		//$this->debug = true ;
		
		// var_dump($_SERVER);
		// var_dump($_REQUEST);
		
		$this->rest_url = rtrim ( urldecode ( $_REQUEST ['q'] ), "\/" );
		$this->http_method = $_SERVER ['REQUEST_METHOD'];
		$this->setContentType();
		$this->setAccept();
	}

	private function setAccept(){
		
		$s_accept = $_SERVER ['HTTP_ACCEPT'];
		$a_accept = preg_split ( "/,/", $s_accept );
		
		foreach ( $a_accept as $accept ) {
		
			$pos = strpos ( $accept, ";" );
			if ($pos !== false) {
				$this->http_accept_type [] = substr ( $accept, 0, $pos );
			} else {
				$this->http_accept_type [] = $accept;
			}
		}
		
		if (is_array ( $this->http_accept_type )) {
			$content_types = array_values ( self::$content );
			//$this->http_accept_type = array_intersect ( $this->http_accept_type, $content_types );
		}
		
		// default to json if accept not set
		if (count ( $this->http_accept_type ) == 0) {
			$this->http_accept_type [] = self::$content ['json'] [0];
		}
		
		//var_dump($this->http_accept_type);
	}
	
	private function setContentType(){
		
		if(isset($_SERVER ['CONTENT_TYPE'])){
			$this->http_content_type = trim( $_SERVER ['CONTENT_TYPE'] );
		} 
	}
	
	private function Array2Xml(SimpleXMLElement $object, array $data)
	{
		foreach ( $data as $key => $value ) {
			if (is_integer ( $key )) {
				$key = "result";
			}
			if (is_array ( $value )) {
				$new_object = $object->addChild ( $key );
				$this->Array2Xml ( $new_object, $value );
			} else {
				$object->addChild ( $key, $value );
			}
		}
	}
	
	private function unsetEmptyKeys( $array ){
		
		$output = array ();
		foreach ( $array as $key => $val ) {
			if (! empty ( $val )) {
				$output [$key] = $val;
			}
		}
		
		return $output;
	}
	
	private function Json2Array( $content ){
		
		$array = json_decode ( $content, true );
		if (json_last_error () != 0) {
			error_400 ();
		}
		return $this->unsetEmptyKeys ( $array );
	}
	
	private function Xml2Array( $content ){
		
		$xml = simplexml_load_string ( $content );
		if ($xml === false) {
			error_400 ();
		}
		
		$json = json_encode ( $xml );
		return $this->Json2Array ( $json );
	}
	
	private function Form2Array( $content ){

		// may be empty body or
		// content-type other than application/x-www-form-urlencoded
		if (! strpos ( $content, "=" )) {
			error_400 ();
		}

		$pairs = preg_split ( "/(,)/", $content );
		
		$output = array();
		foreach ( $pairs as $pair ) {
			$t_pair = trim ( $pair );
			
			//skip empty key pairs (i.e ,  , ,, )
			if (empty ( $t_pair ) || ! strpos ( $t_pair, "=" )) {
				continue;
			}
		
			$tmp = preg_split ("/(=)/", $t_pair );
			$key = $tmp [0];
			$val = $tmp [1];
			
			if (isset( $key )) {
				$output [$key] = $val;
			}
		}
		
		return $output ;
	}
	
	
	/** 
	 * Add routes
	 * 
	 * @param $resource : path url
	 * @param $verb : HTTP REQUEST_METHOD (GET,POST,PUT,DELETE)
	 * @param $method : landing method
	 */
	public function addRoute($resource, $verb, $method) {
		
		if(in_array($verb, self::$verbs)){
			self::$routes [$verb][$resource] = $method;
		}
	}

	public function addRoutes($resources = array()) {
		self::$routes = array_merge ( self::$routes, $resources );
	}

    /**
     * Handle request in case of supported HTTP REQUEST_METHOD
     */
	public function handle() {
		
		if(in_array( $this->http_method, self::$verbs)){
			$this->dispatch();
		}else {
			error_405();
		}
	}

	/**
	 * Dispatches all GET, PUT, POST, DELETE requests
	 */
	protected function dispatch(){
	
		$verb = $this->http_method;
		$url_list = preg_split ( '/(\/)+/', $this->rest_url );
	
		//sanitize $url_list
		$resource_lIst = array();
		foreach ($url_list as $url) {
			if( $url != '' ){
				$resource_lIst[] = $url ;
			}
		}
		if( $this->debug ){
			echo print_r( $resource_lIst, true), " ";
		}
	
		$resource_url = $resource_lIst [0];
		if (! empty ( self::$routes [$verb] [$resource_url] )) {
				
			$callback = self::$routes [$verb] [$resource_url];
				
			// PUT & DELETE requests must have a resource id or search string
			$args = array_splice ( $resource_lIst, 1 );
			if($verb != 'POST' && $verb != 'GET'){
				if( count($args) == 0 ){
					error_400();
				}
			}
				
			// for PUT or POST requests add payload(body) to end of array
			if($verb == 'POST' || $verb == 'PUT'){
				$key_pairs = $this->parseRequestBody() ;
	
				if( $verb == 'POST' ){
					$args[0] = $key_pairs ;
				}else {
					$args[] = $key_pairs ;
				}
			}
				
			if( $this->debug ){
				echo print_r( $args, true), " ", $callback , " " ;
			}
				
			call_user_func_array ( $callback, $args );
		} else {
			error_404();
		}
		
	}
	
	/**
	 * Parse request body for PUT & POST requests
	 * 
	 * @return application/x-www-form-urlencoded array
	 */
	protected function parseRequestBody(){
		
		$body = file_get_contents ( 'php://input' );
		if ($this->debug) {
			echo print_r ( $body, true ), " ";
		}
		
		// empty body not allowed
		if( empty(trim( $body ))){
			error_400();
		}
		
		$output = array ();
		
		// JSON Request
		if (in_array ( $this->http_content_type, self::$content ['json'] )) {
			$output = $this->Json2Array( $body );
		}		

		// XML Request
		elseif (in_array ( $this->http_content_type, self::$content ['xml'] )) {
			$output = $this->Xml2Array ( $body );
		} 		

		// Text Request (application/x-www-form-urlencoded)
		else {
			$output = $this->Form2Array ( $body );
		}
		
		if ($this->debug) {
			echo print_r ( $output, true ), " ";
		}

		return $output;
	}
	
	/**
	 * Return Response for request
	 * 
	 * @param $results : from server data store (database)
	 */
	public function response( $results ) {
	
		// JSON Response
		if (in_array ( $this->http_accept_type [0], self::$content ['json'] )) {
				
			header ( 'Content-Type: ' . self::$content ['json'] [0] . '; charset=utf-8' );
			echo json_encode ( $results );
		}
	
		// XML Response
		elseif (in_array ( $this->http_accept_type [0], self::$content ['xml'] )) {
				
			header ( 'Content-Type: ' . self::$content ['xml'] [0] . '; charset=utf-8' );
				
			$xml = new SimpleXMLElement ( '<resultset/>' );
			$this->Array2Xml ( $xml, $results );
				
			echo $xml->asXML ();
		}
	
		// Text Response
		else {
			echo print_r ( $results, true );
		}
	}
	
	
}



/**
 * Default Handler methods
 */

function alive() {
	echo "CCB REST SERVER ALIVE !!";
}

function error_400() {
	http_response_code ( 400 );
	echo " Bad Request ";
	exit();
}

function error_404() {
	http_response_code ( 404 );
	echo " Resource Not found ";
	exit();
}

function error_405() {
	http_response_code ( 405 );
	echo " Method Not Allowed ";
	exit();
}

function error_500(){
	http_response_code ( 500 );
	echo " Internal Server Error ";
	exit();
}

function ok_202(){
	http_response_code ( 202 );
	echo "SUCCESS";
	exit();
}