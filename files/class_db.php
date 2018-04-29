<?php  
	




class db_class //extends 
{
 	var $DB_link   = null;  //указатель на соединение с БД в случае успешного выполнения, или FALSE при неудаче.
	var	$query_cnt = 0;  // количество запросов к БД (для отладки)
	var	$query_time= 0;  // общее время запросов к БД (для отладки)
//.......................................................................................................................
	function date_to_sql($datemix) // конвертируем формат даты для  вставки в БД 
	{	
		return date('Y-m-d H:i:s', strtotime($datemix) ) ;
	}
 
//.......................................................................................................................

 	function __construct($DB_user, $DB_passw, $DB_name, $DB_host='localhost', $charset='utf8') {
 		
		$this->DB_link = pg_connect("host=$DB_host port=5432 dbname=$DB_name user=$DB_user password=$DB_passw") or _d( [ 'error'=>'Нет соединения с базой данных: '.$DB_name.'', 'message' => pg_connect_error() ] ); 		
  	
	}
	
 	function query($do_print, $query_str){
echo '||  query_str: '.$query_str.'<br><hr><br>';
		$this->query_cnt++; $st_time1 = microtime(true);

		$ret = pg_query($this->DB_link, $query_str );

		$st_time1 = microtime(true)-$st_time1; $this->query_time += $st_time1;
		if ($do_print != 0)
		{	put_log( "<BR>=============================================================================<BR>".
				'<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR> ');
				
			if (!$ret) put_log( "<span  style=\"color:#FF0000;\">вызван с ошибкой:</span> " . pg_last_error($ret) . "<BR> "); 
			else put_log( "<span  style=\"font-size:90%;\">запрос затронул <strong>" .pg_affected_rows($ret) . "</strong> строк</span><BR> "); 
			put_log( "<span  style=\"font-size:90%;\">затраченое время: <b>".round($st_time1,7)."</b> сек.</span><BR>");
			put_log( "=============================================================================<BR><BR>");
		}
		
		return $ret;
	}	

 	function select($do_print, $tn, $sel_expr, $where_expr='', $group_str='', $order_str='', $having_str='', $limit_str='')
	{	
		$this->query_cnt++;$st_time1 = microtime(true);
		$query_str = "SELECT $sel_expr FROM $tn ";
		if ($where_expr != '')  $query_str .= " WHERE $where_expr ";
		if ($group_str != '')   $query_str .= " GROUP BY $group_str ";
		if ($order_str != '')   $query_str .= " ORDER BY $order_str ";
		if ($having_str != '')  $query_str .= " HAVING $having_str ";
		if ($limit_str != '')   $query_str .= " LIMIT $limit_str ";
		$ret = pg_query($this->DB_link, $query_str );$this->insert_id = pg_last_oid($ret);
		$st_time1 = microtime(true)-$st_time1; $this->query_time += $st_time1; 
		if ($do_print != 0)
		{	put_log( "<BR>=============================================================================<BR>".
				 '<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR> ');
			if (!$ret) put_log( "<span  style=\"color:#FF0000;\">вызван с ошибкой:</span> " .pg_last_error($ret) . "<BR> ");
			else put_log( "<span  style=\"font-size:90%;\">запрос затронул <strong>" .pg_affected_rows($ret) . "</strong> строк</span><BR> "); 
			put_log( "<span  style=\"font-size:90%;\">затраченое время: <b>".round($st_time1,7)."</b> сек.</span><BR>");
			put_log( "=============================================================================<BR><BR>");
		}
		return $ret;
	}
	
	function insert_a($do_print, $tn, $data)
	{
		if (!is_array($data)) return false;
		
		$this->query_cnt++;$st_time1 = microtime(true);
		$ret = 0;
		$query_str = db_construct_insert( $data, $tn ) . " RETURNING id;";
		$ret = pg_query($this->DB_link, $query_str ); 
		$st_time1 = microtime(true)-$st_time1; $this->query_time += $st_time1;  
//die('<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR>');		
		if ($do_print != 0)
		{	put_log( "<BR>=========================<[insert_a]>=======================================<BR>".
				 '<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR>');
			if (!$ret) put_log( "<span  style=\"color:#FF0000;\">вызван с ошибкой:</span> " .pg_last_error($ret) . "<BR> ");
			else put_log( "<span  style=\"font-size:90%;\">запрос затронул <strong>" .pg_affected_rows($ret) . "</strong> строк</span><BR> "); 
			put_log( "<span  style=\"font-size:90%;\">затраченое время: <b>".round($st_time1,7)."</b> сек.</span><BR>");
			put_log( "=============================================================================<BR><BR>");
		}
		
		return $this->get_row( $ret )['id'] ;
	}

	function insert($do_print, $tn, $columns, $values)
	{
		$this->query_cnt++;$st_time1 = microtime(true);
		$ret = 0;
		$query_str = "INSERT INTO $tn ($columns) VALUES ($values)";
		$ret = pg_query($this->DB_link, $query_str ); $this->insert_id = pg_last_oid($ret);
		$st_time1 = microtime(true)-$st_time1; $this->query_time += $st_time1;  
		if ($do_print != 0)
		{	put_log( "<BR>=============================================================================<BR>".
				 '<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR>');
			if (!$ret) put_log( "<span  style=\"color:#FF0000;\">вызван с ошибкой:</span> " .pg_last_error($ret) . "<BR> ");
			else put_log( "<span  style=\"font-size:90%;\">запрос затронул <strong>" .pg_affected_rows($ret) . "</strong> строк</span><BR> "); 
			put_log( "<span  style=\"font-size:90%;\">затраченое время: <b>".round($st_time1,7)."</b> сек.</span><BR>");
			put_log( "=============================================================================<BR><BR>");
		}
		if ($ret) return $this->insert_id; 
		return $ret ;
	}

	function delete($do_print, $tn, $where_expr)
	{
		$this->query_cnt++;$st_time1 = microtime(true);
		$ret = 0;
		$query_str = "DELETE FROM $tn WHERE $where_expr";
		$ret = pg_query($this->DB_link, $query_str);
		$st_time1 = microtime(true)-$st_time1; $this->query_time += $st_time1; 
		if ($do_print != 0)
 		{	put_log( "<BR>=============================================================================<BR>".
				 '<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR>');
			if (!$ret) put_log( "<span  style=\"color:#FF0000;\">вызван с ошибкой:</span> " .pg_last_error($ret) . "<BR> ");
			else put_log( "<span  style=\"font-size:90%;\">запрос затронул <strong>" .pg_affected_rows($ret) . "</strong> строк</span><BR> "); 
			put_log( "<span  style=\"font-size:90%;\">затраченое время: <b>".round($st_time1,7)."</b> сек.</span><BR>");
			put_log( "=============================================================================<BR><BR>");
		}
		return $ret;
	}

	function update($do_print, $tn, $set_expr, $where_expr)
	{			
		$this->query_cnt++;$st_time1 = microtime(true);
		$ret = 0;
		$query_str = "UPDATE $tn SET $set_expr WHERE $where_expr";
		$ret = pg_query($this->DB_link, $query_str);
		$st_time1 = microtime(true)-$st_time1; $this->query_time += $st_time1; 
		if ($do_print != 0)
		{	put_log( "<BR>=============================================================================<BR>".
				 '<strong>SQL Запрос:</strong><BR><BR><span class="SQL_query">' . $query_str . '</span><BR><BR> ');
			if (!$ret) put_log( "<span  style=\"color:#FF0000;\">вызван с ошибкой:</span> " .pg_last_error($ret) . "<BR> ");
			else put_log( "<span  style=\"font-size:90%;\">запрос затронул <strong>" .pg_affected_rows($ret) . "</strong> строк</span><BR> "); 
			put_log( "<span  style=\"font-size:90%;\">затраченое время: <b>".round($st_time1,7)."</b> сек.</span><BR>");
			put_log( "=============================================================================<BR><BR>");
		}
		return $ret;
	}
		
/*			
	function insert_id($ret)
	{			
		return pg_last_oid($ret);
	}
	
	function last_err()
	{			
		return pg_last_error($this->DB_link);
	}

	function affected_rows()
	{			
		return pg_affected_rows($this->DB_link);
	}*/
	
	function escape($var){ 
				
		$result = pg_real_escape_string( $this->DB_link, trim($var) );	
//pr_v($var,		'$var',		__LINE__);
//pr_v($result,	'$result',__LINE__);
		return $result;
	}
	
	function empty_result( $p )	
	{	
		if ($p=='' or pg_num_rows($p)==0) return true;
		return false;
	}
	
	function get_row( $p , $par = '')	 //MYSQL_BOTH
	{	
		return pg_fetch_array($p, NULL, PGSQL_ASSOC);
	}

	function get_res($do_print, $tn, $wh, $sort='', $fld_lst=null)	 
	{	
		$fld = ''; $dvdr = '';
		if( is_array($fld_lst) && count($fld_lst)>0 ){
			foreach($fld_lst as $val){
				$fld .= $dvdr.'"'.$val.'"';
				$dvdr = ', ';
			}
		}else{
			$fld = '*';
		}
		
		$res = $this->query($do_print,'SELECT '.$fld.' FROM "'.$tn.'" WHERE '.$wh.' '.$sort.' LIMIT 1 ;');
		if ( $this->empty_result($res)	) return false;
		$this->last_row = $this->get_row($res, PGSQL_ASSOC);
		if (is_array($this->last_row)) return $this->last_row;
		else return false;
	}



} // end: db_interface

	$DB = new db_class(DB_USER, DB_PASS, DB_NAME, DB_HOST);
