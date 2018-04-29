<?php
	
	// под json-rpc недоделано, пока весь функционал только по REST

	ob_start();
	require_once('config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/files/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/files/proc_db.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/files/class_db.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/files/class_session.php');

	require_once('methods.php');


	// проверяем формат вх. данных на соответствие стандарту json-rpc
	function check_rpc(){
		global $raw_data;
		$json_test = json_decode($raw_data, true); // в виде массива
		if ( !is_null($json_test) && isset($json_test['jsonrpc']) && $json_test['jsonrpc']=='2.0' ) return($json_test);
		return false;
	}

	$raw_data = file_get_contents('php://input');

	$method = $_SERVER['REQUEST_METHOD'];

	// if (isset($_GET['action'])) $method = $_GET['action'] // "костыль" - раскоментировать если клиент "тугой" не поддерживающий методы PUT и DELETE

	$method = strtolower($method);	

	$rpc_var = check_rpc();

	if ( $rpc_var!==false ) { $method = 'jsonrpc';}


/** =================================================
 * 		разбираем строку запроса и входной буфер 
 **/	
	
	$querystring = rawurldecode( $_SERVER["QUERY_STRING"] );

	if (substr($querystring,0,2)=='q=') $querystring = substr($querystring,2);

	if ( !empty($querystring) ) 
	{ 
		$par_string = trim($querystring);
		
		$pos1=strpos($par_string,'?');
		
		if ($pos1>0){
			
			$querystring	= substr($par_string, 0, $pos1);
			
			$GET_string 	= substr($par_string, $pos1+1);
			
		} else {
			
			$querystring	= $par_string;
			
			$GET_string 	= '';
		}

		$qp = ( strlen($querystring)>0 ) ? explode("/", $querystring) : null ;

		if ( !empty($GET_string) )
		{
			$param_arr = explode("&", $GET_string);
			
			foreach ($param_arr as $key1=>$value1)
			{	
				$z=explode("=",$value1);	
				
				if (!isset($z[1])) $z[1]="";
				
				$qp_get[$z[0]]=$z[1];
			}
			
		} else { 
			
			$qp_get = NULL; 
		}

		
	} else {
		
		$qp = null; 
		
		$resource_name=''; 
		
		$resource_param='';
	}

	if ( !empty($raw_data) )
	{
		$exploded = explode('&', $raw_data);
		
		foreach($exploded as $pair) {
			
			$item = explode('=', $pair);
			
			if(count($item) == 2) {
				
				$qp_raw[urldecode($item[0])] = urldecode($item[1]); 
			}
			
		}
		
	} else {
		
		$qp_raw = null; 
	}

	$resource_name  = isset($qp[0]) ? strtolower($qp[0]) : '';

	$resource_param = isset($qp[1]) ? strtolower($qp[1]) : '';

/** 
  * 	END: разбор строки запроса
  * ----------------------------------------*/
	
	$id = isset( $rpc_var['id'] ) ? intval( $rpc_var['id'] ) : 0 ;


pr_v($method , 'method');

	// выбираем метод для обработки запроса
	switch($method){
			
		case 'jsonrpc'	: choice_RPC_method();		break;
			
		case 'get'		: choice_GET_method();		break;
			
		case 'post'		: choice_POST_method();		break;
			
		case 'put'		: choice_PUT_method();		break;
			
		case 'delete'	: choice_DELETE_method();	break;
	}

	// выбираем обработчик для JSON-RPC
	function choice_RPC_method(){ // под json-rpc только задел (полностью не реализовано)
		global $rpc_var;
		
		if (function_exists($rpc_var['method'])) { call_user_func( $rpc_var['method'], $rpc_var['method'] ); }
		
	}

	function choice_POST_method(){
		global $resource_name, $resource_param;
pr_v($resource_name , 'resource_name');
pr_v($resource_param , 'resource_param');

		switch($resource_name){
			case 'photos'	:	add_photo($resource_param);	
								break;
				
			case 'recipes'	:	add_recipe();
								break;
								
			case 'authorise': 	login_user();		
								break;
				
			case 'users'		: add_user();		
		}
		
	}

	function choice_GET_method(){
		global $resource_name, $resource_param;
pr_v($resource_name , 'resource_name');
pr_v($resource_param , 'resource_param');
		
		switch($resource_name){
			case 'photos'	:	//add_photo($resource_param);	break;
			case 'recipes'	: 	if ( empty($resource_param) ) list_recipe();
								else detail_recipe($resource_param);
				
								break;
			case 'authorise'	: check_authorise();		
								break;
				
			case 'users'	: 	if ( empty($resource_param) ) {list_user();}
								else {detail_user($resource_param);	}
								break;
		}
		
	}


	function choice_DELETE_method(){
		global $resource_name, $resource_param;
pr_v($resource_name , 'resource_name');
pr_v($resource_param , 'resource_param');

		switch($resource_name){
			case 'photos'	:	delete_photo($resource_param);	break;
			case 'recipes'	:	delete_recipe($resource_param);	break;
			case 'authorise':	logoff_user($resource_param);	break;
			case 'users'	:	delete_user($resource_param);	break;
		}

		
	}


	function choice_PUT_method(){
		global $resource_name, $resource_param;
pr_v($resource_name , 'resource_name');
pr_v($resource_param , 'resource_param');

		switch($resource_name){
				
			case 'recipes'	: 	edit_recipe($resource_param);
				
								break;
				
			case 'users'	: 	edit_user($resource_param);		break;
			//case 'users'		: choice_PUT_method();		break;
			
		}

		
	}



	


	
	

	

	
