<?php  

// завершаем API-скрипт и выводим "ответку"
	function _d($data){ 
		global $id;
		ob_clean();
		header('Content-Type: application/json');
		if ( $method == 'jsonrpc' ) $data['jsonrpc'] = "2.0";
		if ( !empty($id) ) $data['id'] = $id; // для случая асинхронки и для json-rpc
		
		if( DEBUG_MODE ) { global $LOG_BUFF; $data['debug'] = $LOG_BUFF; }
		
		die( json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ); // маскируем Тэги, Апострофы, Кавычки
	}

	

	function password_generate($len=8,$mask='sS1'){
    	$arr  = array();
        $arr1 = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
        $arr2 = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arr3 = array('1','2','3','4','5','6','7','8','9','0');
        $arr4 = array('(',')','[',']','!','?','&','^','%','@','*','$','|','+','-','{','}','`','~');
    
        if (strpos($mask,'s') !== false) {$arr = array_merge($arr, $arr1); }
        if (strpos($mask,'S') !== false) {$arr = array_merge($arr, $arr2); }
        if (strpos($mask,'1') !== false) {$arr = array_merge($arr, $arr3); }
        if (strpos($mask,'!') !== false) {$arr = array_merge($arr, $arr4); }
    
        $pass = ""; $l = count($arr) - 1;
        for($i = 0; $i < $len; $i++)
        {
          $index = rand(0, $l);
          $pass .= $arr[$index];
        }
        return $pass;
    }
	


	/** ----------------------------------------------------------
	 * db_construct_insert - конструируем запрос "INSERT" из значений массива
	 * ----------------------------------------------------------*/
	function db_construct_insert( $src, $tn ) {	
		if (!is_array($src)) return false;
		$fl = ''; $vl=''; $dvdr = '';
		foreach($src as $key=>$val){
			$fl .= $dvdr.'"'.$key.'"';
			$vl .= $dvdr.$val;
			$dvdr = ', ';
		}
		$sqlstr = 'INSERT INTO "'. $tn .'" ('.  $fl  .") VALUES (".$vl.") ";
 		return $sqlstr;
	}

	/** ----------------------------------------------------------
	 * db_construct_update - конструируем запрос "UPDATE" из значений массива
	 * ----------------------------------------------------------*/
	function db_construct_update( $src, $tn, $wh='1' ) {	
		if (!is_array($src)) return false;
		$set = ''; $dvdr = '';
		foreach($src as $key=>$val){
			$set .= $dvdr.'"'.$key.'"='.$val;
			$dvdr = ',';
		}
		$sqlstr = "UPDATE `". $tn ."` SET ".$set." WHERE ".$wh."  ";
 		return $sqlstr;
	}

	function show_query_data( $p ){	 
		global $DB;
		$result = '<table class="simple_table" border="1" ><tbody>';
		$hh = '';$cnt=0;
		$bb = '';
		while ( $row = $DB->get_row( $p ) )
		{	$cnt++;
			$rr = '';
			foreach($row as $field=>$val){
				if ($cnt==1){ $hh .= '<th>'.$field.'</th>'; }
				$rr .= '<td>'.$val.'</td>';
			}
			$bb .= '<tr>'.$rr.'</tr>'.PHP_EOL;
		}
		
		return $result . '<tr>'.$hh.'</tr>'.$bb . '</tbody></table>';
	}


