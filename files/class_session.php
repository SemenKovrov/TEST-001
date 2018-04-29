<?php 

/**
  * обработка сессии 
  * ----------------
  *
  *
  *
  */	



define("SES_NAME", 'ses_id_01' ); 

define("dbg_ses", 1+false ); 

	
// require_once("config_session.php"); 


class session_class	{
	public $sid = '';
	public $uid = 0; 			  // user id >0 сессия авторизованная, иначе - нет.
	public $data = array(); 	  // данные сессии из БД

 

 //...................................................................................................
	function __construct() {  		
		
		if ( $this->session_exist()===false) $this->generate_session(); 
		else $this->update_session(); 

 	}

  function session_exist()	{  
	  global $DB;	
	  
	  if ( isset($_COOKIE[SES_NAME]) ) 
	  {
		  $session_id = $_COOKIE[SES_NAME];
		  $res = $DB->SELECT(dbg_ses, T_SESSIONS, "*", "sid='".$_COOKIE[SES_NAME]."' ");
		  if ( $DB->empty_result($res) ) return false;	
		  
		  $row = $DB->get_row($res);
		  $this->data	= $row;
		  $this->sid	= $row['sid'];
		  $this->uid	= $row['user_id'];
		  
		  if ( empty($this->sid) ) return false;
		 
		  return true;	
	  }
	  return false;
  }
 
 
 
/**
  * генерируем новую сессию
  * ----------------
  *
  * @global $DB
  * @return $session->sid 
  * @access private
  */	
	function generate_session() { 
		global $DB;
		$this->sid 	 = md5(rand(0, getrandmax() )); // генерируем новый sid 
echo '<p>sid =  '.$this->sid.'</p>';

		$this->uid 	 = 0;

		$ip1 	 = (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"]:'';
		$ip2 	 = (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) ? $_SERVER["HTTP_X_FORWARDED_FOR"]:'';
		$hrefr   = (isset($_SERVER['HTTP_REFERER'])) ? substr($_SERVER['HTTP_REFERER'], 0, 1024):''; // 1024 -длинна поля в БД
		$usr_agnt = substr($_SERVER['HTTP_USER_AGENT'], 0, 128);	// 128 - длинна поля в БД
  
		$dd = [
			'sid'=>"'".$this->sid."'",
			'begin'=>'NOW()',
			'lastvisit'=>'NOW()',
		];
		setcookie(SES_NAME, $this->sid, time() + 9999999, '/'); 
		$row_id = $DB->insert_a(dbg_ses, T_SESSIONS, $dd); 
echo '<p>$row_id =  '.$row_id.'</p>';
		if ($row_id>0) { $res = $DB->SELECT(dbg_ses, T_SESSIONS, "*", "sid='".$this->sid."' "); $this->data = $DB->get_row($res); }
	
	  return $this->sid;
  } 
 
 
 
/**
  * обновляем сессию 
  * ----------------
  * обновляем куки и значение в БД
  *
  */	
  function update_session() { 
	  global $DB;
	  setcookie( SES_NAME, $this->sid, time() + 99999999, '/'); 
	  $ret = $DB->UPDATE(dbg_ses, T_SESSIONS, "lastvisit=NOW()" ,  " sid='".$this->sid."'");
	  
  }  
 
 
 
/**
  * присвоение сессии статуса авторизованной 
  * ----------------------------------------
  * если фун-я вызывается без параметров - деавторизация сессиии (пользователь вышел)
  *
  */	
  function set_uid( $user_id=0 , $user_sec_key='')	{ 
	  global $DB;
	  $this->uid = (int)$user_id;
	  $ret = $DB->UPDATE(dbg_ses, T_SESSIONS, ' "user_id"='.$this->uid.',  sid="'.$this->sid.'"');

  } 
	
	 
}// END: session_class();
	
	$session = new session_class();

