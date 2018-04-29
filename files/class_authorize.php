<?php	 if (cms_name!='Velez_cms') die( 'Please, do not access this page directly.' ); load_file_log(__FILE__);
/*
Переменные которые могут придти в $_POST для данного класса:

authorise_user	- авторизация пользователя
logoff_user 	- завершение сессии

user_login		- имя пользователя
user_passw		- пароль
remember_user	- запомнить пользователя на данном компьютере
session_expiretime	- время неактивности сессии до закрытия (перекрывается `remember_user`)
session_iplinked	- привязать сессию к IP адресу для повышения безопасности
captcha			- данные для проверки CAPTCHA 
redirect_to		- URL на который перейти после успешной авторизации



*/
/*
	05.11.2016
*/
if (!defined("T_USERS"))	define("T_USERS",		DB_PREFIX."authentication_users");
if (!defined("T_GROUPS"))	define("T_GROUPS",		DB_PREFIX."authentication_groups");
if (!defined("T_AUTH_ERR"))	define("T_AUTH_ERR",	DB_PREFIX."authentication_errcount");

if (!defined("V_strong_passw")) define("V_strong_passw",     true);

define("dbg_auth", DEBUG_MODE);	
define("save_passw_hint", 1); // сохранить значение пароля незашифрованным (для тестирования и хз зачем)	

//define("T_LOGINCOUNT",     SITE_PREFIX."authentication__count");
//define("V_AuthMaxCount",     5); // если 0 то попыток аутентификации не ограничено, иначе = n
 

class authorise_class	{
 	
	var $uid = 0;			 	// user id (если $uid>0 то вход авторизованн)
	var $userkey = '';			// случайное число генерируемое при создании пользователя и смене пароля
	var $userdata = array();	// данные залогиневшегося пользователя
	var $pid = 0;			 	// partner id 
		
	var $ses_sec_key = '';			// секретный ключ для сессии
	
	var $auth_login	 = '';			// логин авторизации
	var $auth_passw = '';			// пароль авторизации

 	var $time_to_lastlogin=0;
	var $login_blocked	= false;	// возможность залогинится для данного имени заблокирована или нет
	var $blocked_type	= 0;		// почему заблокирована возможность залогинится (тип блокировки)
	var $blocked_msg	= '';		// мессага почему заблокирован логин
	var $blocked_time	= 30;		// осталось времени до разблокировки

	var $login_err_cnt	= 0;		// количество уже совершенных ошибок входа (неправильного ввода пароля)
 	var $login_err_msg	= array();	// сообщения об ошибках при авторизации (для последующего отображения пользователю)
	var $auth_table	= ''; 			// название таблицы для использования в авторизации
	private $group = 0; 	//false пользователь обладает админскими правами
	private $admin = false; 	//false пользователь обладает админскими правами
 	
    
    /**
     * authorise_class::__construct()
     * данный класс при создании отрабатывает только следующие функции:
     * - проверяет есть ли данные формы авторизации (выполняет процедуру входа)
     * - или есть команда окончания сеанса "logoff" (выполняет процедуру выхода)
     * 
     * @param  string $users_table - название таблицы где записаны учетные записи пользователей
     * @return void
     */
    function __construct( $users_table = '' ){ if (dbg_auth) put_log_f(__METHOD__,__LINE__,__FILE__);
	//put_log( '<p style="color:#090; ">CLASS: <b>'.__CLASS__.'</b>   METHOD: <b>'. .'</b> [ line: <b>'.__LINE__.' ]</b></p>');
		global $GV;
		
		$this->conf = &$GV->cfg['authorize'];
        $this->auth_table = (empty($users_table))? $this->conf['users_table'] : $users_table;
		
		$auth_action = $this->__check_auth_action(); //смотрим пришли данные форм авторизации, выхода, регистрации...
pr_v($auth_action, 'auth_action',__LINE__);
		switch ( $auth_action )
		{	
			case 'authorise'	 : $this->__authorize();		break;
			case 'logoff'		 : $this->__logof_user();		break;
			//case 'change_passw'	 : $this->__change_passw();		break; 
			//case 'remember_passw': $this->__remember_passw();	break; 
			//case 'register' 	 : $this->__register_user();	break;
			case 'undefined': 
			default:    $this->__check_auth_sess();
		}
	}

/**---------------------------------------------------------------------------
  *
 **/	
	function __gen_hash_passw($passw)	{ if (dbg_auth) put_log_f(__FUNCTION__ ,__LINE__,__FILE__);
		global $GV,$DB,$session;
		$hash  = vlz_hash( $passw . $GV->cfg['authorize']['secure_key'], $GV->cfg['authorize']['hash_method'],$GV->cfg['authorize']['secure_key']) ;
//pr_v($hash, 'hash_passw',__LINE__);
		return $hash;
	}

/**---------------------------------------------------------------------------
  *
 **/	
 	/**
	 * authorise_class::__authorize()
	 * фун-я авторизации
	 * @return
	 */
	function __authorize()	{ if (dbg_auth) put_log_f(__FUNCTION__ ,__LINE__,__FILE__);
		global $GV,$DB,$session;
//echo 'rfcrc3fc3rpci2ejnc3opo2wbhuoi2b3ecu8yc,4$session$session=';
//var_dump($session);		
		$this->auth_login = $this->__get_var_and_check('user_login');
		$this->auth_passw = $this->__get_var_and_check('user_passw');
		if ( $this->auth_login === false or $this->auth_passw === false ) return false;
pr_r($this->conf, '','$this->conf');
pr_r($_POST, '','$_POST');
pr_v($this->auth_login, 'user_login',__LINE__);
pr_v($this->auth_passw, 'user_passw',__LINE__);
/**/
		// проверяем были ли ошибки при заполнении полей формы
		if ( count($this->login_err_msg) > 0 ) return false;
		// проверяем наложена ли блокировка на пользователя
		if ( $this->checkUserBlocked() )  return false;

		// здесь проверяем были ли ранее ошибки при авторизации
		$this->login_err_cnt = $this->__get_num_auth_err();
		// @todo - сделать антибрутфорс (всегда фальзе если превышен порог)
/*		$DB->delete(dbg_auth, T_AUTH_ERR, "( last_attempt < NOW()-" . $this->conf['time_to_user_blocked'] . ") "	);
		
		$paranoya_str1 = ( true )	? "(`sid`='".$session->sid."') or " : ""; // учитываем сессию логинящегося пользователя
		$paranoya_str2 = ( false )	? "(`user_ip`='".$session->ip1."' and `user_ip2`='".$session->ip2."') or " : ""; // учитываем IP адреса логинящегося пользователя
		$ret = $DB->select(dbg_auth, T_AUTH_ERR, "*", $paranoya_str1.$paranoya_str2." `username`='".$_POST['user_login']."' ");
		if 	($ret!='' && mysqli_num_rows($ret)>0) // в БД остались ошибки входа с использованием   $_POST['user_login']
		{ 	
			$row = mysqli_fetch_array($ret); 
			$this->login_err_cnt = $row['error_count'];
			$this->blocked_time = time()-$row['last_attempt'] + $this->conf['time_to_user_blocked'];
		}*/
		
		
		/*// проверяем заблокирован пользователь или нет
		if ($this->checkUserBlocked())	return -1;
		//проверяем код captcha если необходимо
		if (!$this->checkCaptcha())		return -1; 
		// проверяем заполненность полей авторизации (логин + пароль)
		if (!$this->validate_field_fill())	return -1;*/


		// сверяем логин пароль в БД
  		//$auth_login     = strtolower(trim($_POST['user_login']));
        
        // алгоритм: HASH = хешируем (пароль + секретный ключ CMS )  
 		$auth_password  = $this->__gen_hash_passw($this->auth_passw);
		//vlz_hash( $this->auth_passw . $GV->cfg['authorize']['secure_key'], $GV->cfg['authorize']['hash_method'],$GV->cfg['authorize']['secure_key']) ;
pr_v($this->auth_passw, 'dexfdlkhjiohwoiuxhe3ioxh<br>$this->auth_passw',	__LINE__);
pr_v($GV->cfg['authorize']['secure_key'], '$GV->cfg[authorize][secure_key]',	__LINE__);
pr_v($GV->cfg['authorize']['hash_method'], '$GV->cfg[authorize][hash_method]',	__LINE__);
pr_v($auth_password, '$auth_password',	__LINE__);
		
		
 		if ($row = $DB->get_res(dbg_auth, 
                            $this->auth_table,
                            "`".$GV->cfg['authorize']['login_field']."`='".$this->auth_login."'".
                            " and `".$GV->cfg['authorize']['passw_field']."`='".$auth_password."' "
                            ) ) 
		{	// авторизация успешная
			$this->userdata	= $row;
			$this->uid		= $row['uid'];
			$this->userkey	= $row['userkey'];
			$sec_key = $this->__auth_sec_key($row); // генерируем секретный ключ для авторизации сессии
			
			$session->set_uid( $this->uid , $sec_key ); // обновляем сессию
			
pr_v($sec_key,		'$sec_key',		__LINE__);
            $DB->update( dbg_auth,	$this->auth_table, 
									"lastvisit=NOW(), `sid`='".$session->sid."' ",
									" uid=".$this->uid ); // заносим значение SID последней активной сессии в акаунт пользователя
                                                          // чтобы можно было ограничивать вход только из одного браузера 
			$this->__clear_auth_err(); // очищаем журнал и счетчики ошибок
			return true; 
		}
		else	// ошибка авторизации
		{
			$this->login_err_msg[] ="Не верно указан логин или пароль";
			$this->login_err_cnt++;
			/*@todo
            $sql ="INSERT INTO `".T_AUTH_ERR."` ( `username`, `sid`, `user_ip`, `user_ip2`, `last_attempt`, `error_count`) VALUES ".
											 "  ( '".$this->auth_passw."', '".$session->sid."', '".$session->ip1."', '".$session->ip2."', NOW(), '". $this->login_err_cnt ."');";
			$DB->query(dbg_ses,$sql); */
			return false; 
		}
        return false; 
	}
		
 //...................................................................................................
	function __logof_user()	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $GV, $session;
		$DB->update(dbg_auth, $this->auth_table, "sid=''", " uid=".$this->uid );
		unset($this->userdata);
		$this->uid = 0;
		$session->set_uid( 0 , '' );
		@_redirect_to($GV->cfg['authorize']['exitpage']); // переходим в корень (объявлена в cfunc.php)
		return ;
	}
 
 
 //...................................................................................................
 
	function show_username() { 
		global $GV;
		$mask = ( !empty($GV->cfg['authorize']['username_mask']) )?$GV->cfg['authorize']['username_mask']:'%nikname%';
		
		$src[] = '%nikname%'; 	$dst[] = $this->userdata['nikname'];
		$src[] = '%name%'; 		$dst[] = $this->userdata['name'];
		$src[] = '%family%'; 	$dst[] = $this->userdata['family'];
		$src[] = '%otchestvo%'; $dst[] = $this->userdata['otchestvo'];
		
		$src[] = '%nn%'; 		$dst[] = strtoupper( substr($this->userdata['name'],0,1) ) ;
		$src[] = '%oo%'; 		$dst[] = strtoupper( substr($this->userdata['otchestvo'],0,1) ) ;
				
		//$mask = str_replace('%nikname%', $this->userdata['nikname'], $mask);
		//$mask = str_replace('%name%', $this->userdata['name'], $mask);
		//$mask = str_replace('%family%', $this->userdata['family'], $mask);
		//$mask = str_replace('%otchestvo%', $this->userdata['otchestvo'], $mask);
		return  str_replace($src, $dst, $mask);
	}
		
	
	/**
	 * authorise_class::__auth_sec_key()
     * генерируем секретный ключ для авторизации сессии
     * 
	 * @param mixed $userdata
	 * @return string
	 */
	function __auth_sec_key($userdata)	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $GV, $session;
		$ses_db_id = (isset($session->data['id']) )? $session->data['id']:'';
        //= хешь( ID_сессии + секретный ключ CMS + UID + секретный персональный ключ пользователя )
		$result = vlz_hash( $ses_db_id . $GV->cfg['authorize']['secure_key'] . $userdata['uid'] . $userdata['userkey'], $GV->cfg['authorize']['hash_method'], $GV->cfg['authorize']['secure_key'] ) ;
		$this->ses_sec_key = $result;
pr_v( $result , '__auth_sec_key::result',__LINE__)	;	
		return $result;
	}
		
/**
  *
  * определяем специальные команды для модуля авторизации  
  *
  *
  *
 **/	
	function __check_auth_action() { if (dbg_auth) put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $session, $captcha ;
		
		if ( isset($_POST['authorise_user']) or isset($_GET['authorise_user']) ) return 'authorise';
		if ( isset($_POST['form_action']) and strtolower($_POST['form_action'])=='authorise') return 'authorise';
		
		if ( isset($_POST['logoff_user']) or isset($_GET['logoff_user']) ) return 'logoff';
		if ( isset($_POST['form_action']) and strtolower($_POST['form_action'])=='logoff') return 'logoff';

		//if ( isset($_POST['change_passw']) or isset($_GET['change_passw']) ) return 'change_passw';
		//if ( isset($_POST['form_action']) and strtolower($_POST['form_action'])=='change_passw') return 'change_passw';
		
		//if ( isset($_POST['register_user']) or isset($_GET['register_user']) ) return 'register';
		//if ( isset($_POST['form_action']) and strtolower($_POST['form_action'])=='register') return 'register';
		
		return 'undefined';
	}
	
	
/**
  *
  * получаем значения переменных форм модуля авторизации и проверяем  
  * правильность их заполнения.
  *
  * в случае ошибки добавляем строку с описанием ошибки в массив $this->login_err_msg[]
  *
 **/	
	function __get_var_and_check( $name_data ) { if (dbg_auth) put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB;//, $session, $captcha ;
		$result='';
		switch ( $name_data )
		{	
			case 'user_login'	: 
								if ( isset($_POST['user_login']) and trim($_POST['user_login'])!="") return $DB->escape( trim(strtolower($_POST['user_login']) ) );		
								//if (!isset($_GET['user_login'])  and trim($_GET['user_login'])!="")  return trim(strtolower($_GET['user_login']));		
								$this->login_err_msg[] ="Не указано имя! "; return false; break;
								
			case 'user_passw'	: 
								if ( isset($_POST['user_passw']) and $_POST['user_passw'] != "") return $DB->escape($_POST['user_passw']);		
								//if (!isset($_GET['user_passw'])  and $_GET['user_passw']  != "") return $_GET['user_passw'];		
								$this->login_err_msg[] ="Не указан пароль! "; return false; break;
								
			/*case 'user_new_passw'	: 
								if (!isset($_POST['new_passw_1']) and $_POST['new_passw_1'] != "") $new_passw_1 = $_POST['new_passw_1'];		
								if (!isset($_GET['new_passw_1'])  and $_GET['new_passw_1']  != "") $new_passw_1 = $_GET['new_passw_1'];		
								if (!isset($_POST['new_passw_2']) and $_POST['new_passw_2'] != "") $new_passw_2 = $_POST['new_passw_2'];		
								if (!isset($_GET['new_passw_2'])  and $_GET['new_passw_2']  != "") $new_passw_2 = $_GET['new_passw_2'];		
								
								if ( $new_passw_1 == '' or $new_passw_2 == '') $this->login_err_msg[] ="новый пароль не может быть пустым! ";
								if ( $new_passw_1 != $new_passw_2 ) $this->login_err_msg[] ="введенные пароли не совпадают! ";
								if ( $new_passw_1 == $new_passw_2 and $new_passw_1 != '') return $DB->escape($new_passw_1);
								return false; break;
								

			case 'register' 	 : $this->__register_user();	break;
			case 'undefined': */
			default:  return false;
		}
	}
	
	
/**
  * конструктор класса
  *
  *
  * @global $HTTP_POST_VARS, $_POST 
  * @see    Auth
  * @return void 
  * @access private
 **/	
	 
	 function __construct222( $users_table ){ if (dbg_auth) put_log( '<p style="color:#090; ">CLASS: <b>'.__CLASS__.'</b>   METHOD: <b>'.__METHOD__ .'</b> [ line: <b>'.__LINE__.' ]</b></p>');
		global $GV;//,  $DB, $session, $captcha ;
		$this->auth_table = $users_table;
		$this->conf = &$GV->cfg['authorize'];
		
		$auth_action = $this->__check_auth_action(); // пришли данные формы авторизации, выхода, регистрации...
pr_v($auth_action, 'auth_action',__LINE__);
		switch ( $auth_action )
		{	
			case 'authorise'	 : $this->__authorize();		break;
			case 'logoff'		 : $this->__logof_user();		break;
			//case 'change_passw'	 : $this->__change_passw();		break; 
			//case 'remember_passw': $this->__remember_passw();	break; 
			//case 'register' 	 : $this->__register_user();	break;
			case 'undefined': 
			default:    $this->__check_auth_sess();
		}
	}
		
	 

		
 //...................................................................................................
	function __check_auth_sess()	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $GV, $session;

pr_v( $session->uid , 'session->uid',__LINE__)	;	
		
		if ( $session->uid == 0 ) return false;
		
		$ses_sec_key = (isset($session->data['time_line']))?$session->data['time_line']:'';
        //time_line - и есть $ses_sec_key для маскировки (в таблице БД сесии )
        
		
pr_v( $session->sid , 'session->sid',__LINE__)	;	
pr_v( $ses_sec_key , 'ses_sec_key',__LINE__)	;	
		
		
		// проверяем наложена ли блокировка на акаунт пользователя
		if ( $this->checkUserBlocked() )  return false;

		// проверяем валидность авторизованной сессии
        // 1- проверяем на мультиподключение из разных браузеров
		$denied_str = ($GV->cfg['authorize']['denied_multi_browser'])? " `sid`='".$session->sid."' and " : "";
pr_v($denied_str, 'denied_str',__LINE__)	;	

        // 2- проверяем на мультиподключение из разных браузеров
		$res = $DB->select(dbg_auth, $this->auth_table, "*", $denied_str." `uid`='".$session->uid."' ");
		if 	($res!='' && mysqli_num_rows($res)>0) // нашли ID пользователя в таблицах
		{	$row=mysqli_fetch_assoc($res);
			
			$auth_sec_key = $this->__auth_sec_key($row);
			
			
put_log( '<p>проверяем : ($ses_sec_key == $auth_sec_key)</p>');
pr_v($ses_sec_key,  '$ses_sec_key', __LINE__)	;	
pr_v($auth_sec_key, '$auth_sec_key',__LINE__)	;	
			if ( $ses_sec_key == $auth_sec_key )
			{
put_log( '<p>прошли проверку: ($ses_sec_key == $auth_sec_key)</p>');
				$this->userdata	= $row;
				$this->uid		= $row['uid'];
				$this->userkey	= $row['userkey'];
				
				$session->authorised = true;
				$DB->update(dbg_auth, $this->auth_table, "lastvisit=NOW() ", " uid=".$this->uid);
                
                $this->read_permission($this->uid);
			}
		}
	}
    
	/**---------------------------------------------------------------------------
  	* _constr_permis_str - генерируем JSON строку с правами пользователя
	*---------------------------------------------------------------------------
  	* ф-я пока не доделана  */	
    function _constr_permis_str($userkey, $gr=0, $a=false){
		global $GV;//,$DB,  $session;
        
		/*        if($gr>0)
        {
            $ret = $DB->select(dbg_auth, T_AUTH_GROUPS, "id, perm_key, name", "`id`=".$gr);
    		if 	( !empty_result( $ret ) ) 
            {
                $row = $DB->get_row($ret);
            }
        }*/
        //$e_z - хешь группы
        //$r_x - хешь админа
        
        $e_z = vlz_hash( $gr.$userkey, $GV->cfg['authorize']['hash_method'], $GV->cfg['authorize']['secure_key'] ) ;
        $r_x = ($a)? vlz_hash( 'admin'.$userkey.'root'.$GV->cfg['authorize']['secure_key'], $GV->cfg['authorize']['hash_method'], $GV->cfg['authorize']['secure_key']) : vlz_hash( vlz_uniqid(), $GV->cfg['authorize']['hash_method'], $GV->cfg['authorize']['secure_key']);
        
        $permit = '{"e_z":"'.$e_z.'","r_x":"'.$r_x.'"}';
        
        
        //$r = $DB->update(dbg_auth, $this->auth_table, "id, permissions, name", "`id`=".$gr);
		return $permit;
	       
    }

    
    function read_permission($uid){
		global $DB, $GV, $session;
        
        $user_prm = json_decode($this->userdata['permissions'], true);
        if(empty($user_prm)){ $this->group = 0; $this->admin = false; }
        else 
        {
            //$user_prm['e_z'] - хешь группы
            //$user_prm['r_x'] - хешь админа
            $ret = $DB->select(dbg_auth, T_AUTH_GROUPS, "id, perm_key, name", "1");
    		if 	( !$DB->empty_result( $ret ) ) 
            {
                while ($row = $DB->get_row($ret)) 
                {
                    $tmp_hash = vlz_hash( $row['perm_key'].$this->userdata['userkey'].$GV->cfg['authorize']['secure_key']. $this->userdata['uid'] , $GV->cfg['authorize']['hash_method'], $GV->cfg['authorize']['secure_key'] ) ;
                    if ( $tmp_hash == $user_prm['e_z'] ) {$this->group = $row['id']; break;}
                } 
                $tmp_hash = vlz_hash( 'admin'.$this->userdata['userkey'].'root'.$GV->cfg['authorize']['secure_key']);
                if ( $tmp_hash == $user_prm['r_x'] ) {$this->admin = true;}
            }    
        }
        
		
	       
    }
		
 //...................................................................................................
	function __get_num_auth_err()	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $GV, $session;
	return 0;
		$DB->delete(dbg_auth, T_AUTH_ERR, "( last_attempt < NOW()-" . $this->conf['time_to_user_blocked'] . ") "	);
		
		$paranoya_str1 = ( true )	? "(`sid`='".$session->sid."') or " : ""; // учитываем сессию логинящегося пользователя
		$paranoya_str2 = ( false )	? "(`user_ip`='".$session->ip1."' and `user_ip2`='".$session->ip2."') or " : ""; // учитываем IP адреса логинящегося пользователя
		$ret = $DB->select(dbg_auth, T_AUTH_ERR, "*", $paranoya_str1.$paranoya_str2." `username`='".$_POST['user_login']."' ");
		if 	($ret=='' && mysqli_num_rows($ret)==0) return 0;
		$row = mysqli_fetch_array($ret); 
		$this->login_err_cnt = $row['error_count'];
		$this->blocked_time = time()-$row['last_attempt'] + $this->conf['time_to_user_blocked'];
		return $this->login_err_cnt;
	}
		
 //...................................................................................................
	function __clear_auth_err()	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $GV, $session;
	return;
		$DB->delete(dbg_auth, T_AUTH_ERR, "`username`='".$this->auth_passw."' and `sid`='".$session->sid."' " ); //*/

	}
 //...................................................................................................
	function __set_auth_err_cnt($num_err)	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $GV, $session;
		$sql ="INSERT INTO `".T_AUTH_ERR."` ( `username`, `sid`, `user_ip`, `user_ip2`, `last_attempt`, `error_count`) VALUES ".
										 "  ( '".$auth_login."', '".$session->sid."', '".$session->ip1."', '".$session->ip2."', NOW(), '". $num_err ."');";
		$DB->query(dbg_ses,$sql); 
	}
		
 	//...................................................................................................
	function __cexedxedxe_sess()	{ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $GV, $session;
 	
 	

		// если пришли данные логина, то "логинимся" даже если сессия уже авторизована
 		if ($loginquery) 
 		{	// пришли данные формы авторизации пользователя
if (dbg_auth) put_log( '<p style="color:#666; font-size:0.8em; ">'.basename(__FILE__).' [ line: <b>'.__LINE__.' ]</b>  пришли данные формы авторизации пользователя</p>');		

 	 		
			
			//
			// проверяем были ли уже сделаны ошибки при входе на акаунт пользователя
			// а. удаляем "устаревшие" данные ошибок
		}
		
		
		if ($loginquery && count($this->login_err_msg)==0) // запрос авторизации + ошибок заполнения полей нет 
		{	
if (dbg_auth) put_log(  '<p>if ( loginquery && count( this->login_err_msg)==0) [ line: <b>'.__LINE__.' ]</b></p>');
 		
			if ($this->check_auth_login($_POST['user_login'], $_POST['user_passw'] )) 
				 {	//echo "пароль правильный"; 
				 	$this->setdata_login_correct();
					$session->authorised = true;

					return 1; 
				 }	
			else { 	//echo "пароль неправильный [попытка ".$this->login_err_cnt."]";
					mt_srand(microtime(1)*10000000);	$cnt_varname = 'cnt'.mt_rand(1,100000000000000); // уникальная переменная нужна чтобы избежать наложений при большой нагрузке 
					mysqli_query("SET @".$cnt_varname." =0; "); 
					mysqli_query("SELECT @".$cnt_varname.":=`error_count` FROM `".T_AUTH_ERR."` WHERE `username`='".$_POST['user_login']."'; ");
					mysqli_query("REPLACE `".T_AUTH_ERR."` SET `username`='".$_POST['user_login']."', `error_count`= @".$cnt_varname."+1, `last_attempt`=now(); ");
					
					$session->authorised = false;
					$this->login_err_cnt ++;
					if ($this->checkUserBlocked(true))	return -1; 
					if ($this->login_err_cnt >= $this->conf['num_err_captcha']) 	$this->show_captcha = true;
					$this->login_err_msg[]  = "Ошибка авторизации! Проверьте правильность ввода имени и пароля. ";
					return -1;
					//$GV->cfg['template']['template_name'] = 'authpage';
        		  }
		} 
 		
		if ( isset($_POST['logoff_user']) or isset($_GET['logoff_user'] ) ) // закончить сессию
		{   $this->__logof_user();//echo "завершаем сессиию пользователя"; 
		}
		
 	}
	
	
	
	/**---------------------------------------------------------------------------
  	* _force_change_password - принудительная смена пароля для пользователя
	*---------------------------------------------------------------------------*/	
	function _force_change_password($uid, $passw, $adm=false)	{  if (dbg_auth) put_log_f(__FUNCTION__ ,__LINE__,__FILE__);
		global $GV,$DB,$session;

		if ( $row = $DB->get_res(dbg_auth, $this->auth_table, "`uid`='".$uid."'" ) ) {
			// авторизация успешная
			$this->userdata	= $row;
			//$this->uid		= $row['uid'];
			//$this->userkey	= $row['userkey'];
			
 			$this->userkey	= vlz_password_generate(8,'1'); // генерируем новый ключ
			$new_password	= $this->__gen_hash_passw($passw);
			$new_permis	= $this->_constr_permis_str($uid, 0, $adm);
			$DB->update( dbg_auth,	$this->auth_table, 
									"`passw`='".$new_password."', `userkey`='".$this->userkey."', `permissions`='".$new_permis."'  ",
									" `uid`='".$uid."'" ); 
			return true; 
		}
		
 
        return false; 
	
	}
	
	
	
	
	/**---------------------------------------------------------------------------
  	* проверяем наложены блокировки на акаунт пользователя или нет
  	*---------------------------------------------------------------------------
  	* ф-я пока не доделана  */	
	function checkUserBlocked($check_loginErrorOnly=false)	{ if (dbg_auth) put_log(  '<p>function <b>'.__FUNCTION__.'</b> [ line: <b>'.__LINE__.' ]</b></p>');		
		global $DB, $GV, $session;
	return false;
		// проверяем блокировку до подтверждения кода регистрации
		// ...
		
		// проверяем блокировку по "бану" 
		// ...
		
		// проверяем блокировку expired_account_time 
		// ...
		
		// и т.д.
		return false; // блокировать не нужно
	}
	
	/**---------------------------------------------------------------------------
  	* проверка капчи
  	*---------------------------------------------------------------------------
  	* ф-я пока не доделана  */	
	function checkCaptcha()   {if (dbg_auth) put_log(  '<p>function <b>'.__FUNCTION__.'</b> [ line: <b>'.__LINE__.' ]</b></p>' );		
		global $captcha;

		if ( $this->conf['num_err_captcha'] == -1 )  return 1; // мы не используем проверку captcha при авторизации
		if ( $this->login_err_cnt < $this->conf['num_err_captcha'] ) return 1; // все нормально
	
		// количество сделаных ошибок показывает, что мы должны были запрашивать при авторизации код captcha 
		// и соответственно теперь его нужно проверить
		$this->show_captcha = true;
		if (isset($_POST['captcha']) and captcha_class::checkcode($_POST['captcha']))
		{	
			$this->show_captcha = false;
			return 1; // все нормально
		}
		else
		{	
			$this->login_err_msg[] = "Не вверно введен код проверки! <br />";
			return 0;       
		}
			 
	}

 	
}// END: authorise_class();

	function is_admin() 		{ global $auth; return  ( isset($auth->userdata['admin']) and  $auth->userdata['admin']==1);  } /*return (md5($userdata['permiss'])==md5($userdata['name'].$userdata['uid'])&true:false;*/	
	function is_authorized() 	{ global $auth; return  ( isset($auth->uid) and  $auth->uid>0);  }  	
	function is_NOT_authorized(){ return  !is_authorized();  }  // для наглядности

