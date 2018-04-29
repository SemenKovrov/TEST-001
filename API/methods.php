<?php

// ---------------------------- // 
//    ОСНОВНЫЕ МЕТОДЫ			//
// ---------------------------- // 

	// добавляем нового пользователя
	// POST
	function add_user(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		// check_permission();
		global $DB;
		if ( empty($_POST['passw1']) || $_POST['passw1']!=$_POST['passw2'] ) _d( ['error'=>'введенные пароли должны совпадать и не быть пустыми'] );
		if ( empty($_POST['name']) ) _d( ['error'=>'имя не должно быть пустым'] );
		
		$dd = [ 
				'name'=> "'".$_POST['name']."'",
				'passw' => "'".MD5($_POST['passw1'])."'",
				'userkey' => "'".password_generate(5)."'",
			  ];
		$r = $DB->insert_a(1, T_USERS, $dd);	
		if($r) _d( [ 'result'=> 'success', 'user_id' => $r,'user_name' => $_POST['name'] ] );		
		_d( ['error'=>'ошибка добавления нового пользователя'] );
		
	}

	// редактируем профиль пользователя (перед этим мы считали профиль GET)
	// PUT
	function edit_user(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		// check_permission();
		
		
	}

	// удаляем пользователя
	// DELETE
	function delete_user($user_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB;
		$row = $DB->get_res( 1, T_USERS, "id=".$user_id, '', ['protected'] );	
		if ($row['protected']>0) _d( ['result'=>'success', 'user_id'=>-1, 'message'=>'<br>данного пользователя удалить нельзя (protected)'] );					   
		$r = $DB->delete( 1, T_USERS, '"id"='.$user_id );
				
		if ($r) _d( ['result'=>'success', 'user_id'=>$user_id, 'message'=>'<br>успешно удален пользователь с ID:'.$user_id  ] );
		_d( ['error'=>'<br>ошибка удаления пользователя ID:'.$user_id ] );				   

	}

	// список всех пользователей
	// GET
	function list_user(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		// check_permission(); 
		global $DB;
		$result = [];
		$res = $DB->select( 1, T_USERS, '*' );
		if( $DB->empty_result( $res ) ) { _d( ['error'=>'список пользователей пуст' ] ); }
		
		while ( $row = $DB->get_row( $res ) ) {
			$result[] = ['user_id'=>$row['id']  , 'user_name'=>$row['name']  ];
		}
		_d( [ 'result'=> 'success', 'users_list'=>$result ] );
	}

	// детально профиль пользователя
	// GET
	function detail_user($user_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		// check_permission();
		global $DB;
		$row = $DB->get_res( 1, T_USERS, "id=".$user_id." ");
		if ( $row===false ) _d( ['error'=>'ошибка авторизации'] );
		$DB->update( 0, T_USERS, "sid='".$session->sid."' ", "id=".$row['id'] );
		$DB->update( 0, T_SESSIONS, '"user_id"='.$row['id'], "sid='".$session->sid."' ");
		_d( ['user_id'=>$row['id'], 'users_profile_str'=>'<div class="user_prof_bl">данные профиля пользователя: <b>' . $row['name'] . '</b> (пока так...)<br><pre>'.print_r($row,1).'</pre></div>'] );

	}

// ================================================

	// авторизоваться
	// POST //мы создаем авторизованный сеанс
	function login_user(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB,$session;			
		$row = $DB->get_res( 1, T_USERS, "name='".trim($_POST['name'])."' and passw='".MD5($_POST['passw'])."' ");
		if ( $row===false ) _d( ['error'=>'ошибка авторизации'] );
		$DB->update( 1, T_USERS, "sid='".$session->sid."' ", "id=".$row['id'] );
		$DB->update( 1, T_SESSIONS, '"user_id"='.$row['id'], "sid='".$session->sid."' ");
		_d( ['user_id'=>$row['id'], 'user_name'=>$row['name'] ] );
	}

	// завершить сеанс
	//DELETE  //мы удаляем авторизованный сеанс
	function logoff_user($user_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB,$session;
		if ( $user_id!=uid() ) _d( ['error'=>'недостаточно полномочий для проведения операции' ] );// можем закрыть только свою сессию
		$DB->update( 0, T_USERS, "sid=''", '"id"='.$user_id );
		$DB->update( 0, T_SESSIONS, '"user_id"=0', "sid='".$session->sid."' ");
		_d( ['user_id'=>0, 'user_name'=>'' ] );
	}

	// GET
	function check_authorise(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB,$session;
		$row = $DB->get_res( 1, T_USERS, "sid='".$session->sid."' ");
		if($row===false) _d( ['user_id'=>0, 'user_name'=>'' ] );
		_d( ['user_id'=>$row['id'], 'user_name'=>$row['name'] ] );
	}

// ================================================recipe_descr

	// добавляем новый рецепт
	function add_recipe(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB;
		
		$dd = [ 
				'title'=> "'".$_POST['recipe_title']."'",
				'description' => "'".$_POST['recipe_descr']."'",
				'who_modif' => uid(),
			  ];
		$r = $DB->insert_a(0, T_RECIPES, $dd);	
		if($r) _d( [ 'result'=> 'success', 'recipe_id' => $r, 'upload_url' => '/api/photos/' ] );		
		_d( ['error'=>'ошибка добавления нового рецепта'] );
	}

	// редактируем рецепт
	// PUT
	function edit_recipe($recipe_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB, $qp_raw;
		
		$dd = [ 
				'title'=> "'".$qp_raw['recipe_title']."'",
				'description' => "'".$qp_raw['recipe_descr']."'",
				'who_modif' => uid(),
			  ];
		
		$r = $DB->query(0, db_construct_update( $dd, T_RECIPES, 'id='.$recipe_id ) );	
		
		if($r) _d( [ 'result'=> 'success', 'recipe_id' => $recipe_id ] );		
		_d( ['error'=>'ошибка обновления рецепта'] );
	}

	// удаляем рецепт
	//DELETE
	function delete_recipe($recipe_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB;
		$r = $DB->delete( 0, T_FOTOS, '"id"='.$recipe_id );
		
		$p = recipe_del_all_photo($recipe_id);
		
		if ($r&&$p) _d( ['result'=>'success'] );
		_d( ['error'=>'ошибка удаления рецепта ID:'.$recipe_id ] );
	}



	// список всех рецептов (пока без пагинации)
	// GET
	function list_recipe(){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		//check_permission();
		global $DB;
		$result = [];
		$sqlstr = 'SELECT r.*, u.name FROM "'.T_RECIPES.'" r left join "'.T_USERS.'"u ON r.who_modif = u.id';				   
		$res = $DB->query( 1, $sqlstr );
		if( $DB->empty_result( $res ) ) { _d( ['error'=>'список рецептов пуст' ] ); }
		
		while ( $row = $DB->get_row( $res ) ) {
			$result[] = ['recipe_id'=>$row['id']  , 'recipe_title'=>$row['title'], 'recipe_modif'=>$row['name']  ];
		}
		_d( [ 'result'=> 'success', 'recipelist'=>$result ] );
	}

	// рецепт детально
	// GET
	function detail_recipe($recipe_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		// check_permission();
		global $DB;
		$row = $DB->get_res( 0, T_RECIPES, '"id"='.$recipe_id);
		if($row===false) _d( ['error'=>'рецепта с указанным ID:'.$recipe_id.' не найдено.'] );
		$row['photos'] = recipe_all_photo($recipe_id);
		
		_d( $row );
	}


// ================================================



	// добавляем фото
	function add_photo($recipe_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB;
		
		//if( isset( $_POST['recipe_photo_upload'] ) ){  // пока для отладки
			$uploaddir = DIR_PHOTOS; 
			
			// cоздадим папку если её нет
			if( !is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );

			$files = $_FILES; // полученные файлы[T_FOTOS]
	
			$done_files = array();
			// переместим файлы из временной директории в указанную
			foreach( $files as $file ){
				
				$ext = pathinfo( $file['name'], PATHINFO_EXTENSION);
				$file_name = password_generate(16) . '.' .  $ext; // меняем имя файла на случайное значение 

				if( move_uploaded_file( $file['tmp_name'], "$uploaddir/$file_name" ) ){
					$done_files[] = realpath( "$uploaddir/$file_name" );
					
					$dd = [
						'recipe_id' => $recipe_id,
						'filename' => "'".$file_name."'",
					];
					
					$r = $DB->insert_a(0, T_FOTOS, $dd);
					
				}
			}

			$data = $done_files ? array('result'=> $r, 'files' => $done_files ) : array('error' => 'Ошибка загрузки файлов.');

			$r = $DB->query(1, 'UPDATE "'.T_RECIPES.'" set "who_modif"='.uid().'  WHERE "id"='.$recipe_id );					   
								   
			_d( $data );
		//}
		_d( ['error'=>'неверные входные параметры'] );
		
	}


	// удаляем фото
	// DELETE
	function delete_photo($photo_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB;
		$r = $DB->delete( 0, T_FOTOS, '"id"='.$photo_id );
		if ($r) _d( ['result'=>'success'] );
		_d( ['error'=>'ошибка удаления фото ID:'.$photo_id ] );
		// $r = $DB->query(0, 'UPDATE "'.T_RECIPES.'" set "who_modif"='.uid().'  WHERE "id"='.$recipe_id );  // не забыть раскоментировать и переместить
	}


// -------------------------------- // 
//    вспомогательные процедуры		//
// -------------------------------- // 

	// список всех фото для конкретного рецепта
	function recipe_all_photo($recipe_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		// check_permission();
		global $DB;
		$result = [];
		$res = $DB->select( 1, T_FOTOS, '*', 'recipe_id='.$recipe_id );
		if( $DB->empty_result( $res ) ) { return $result; }
		while ( $row = $DB->get_row( $res ) ) {
			$result[] = ['photo_id'=>$row['id']  , 'photo_filename'=>$row['filename']  ];
			
		}
		
		return $result;
	}

	// удалить все фото для конкретного рецепта
	function recipe_del_all_photo($recipe_id){ put_log_f(__FUNCTION__,__LINE__,__FILE__);
		check_permission();
		global $DB;
		$r = $DB->delete( 0, T_FOTOS, '"recipe_id"='.$recipe_id );
		return $r;
		//if ($r) _d( ['result'=>'success'] );
		//_d( ['error'=>'ошибка удаления фоток для рецепта с ID:'.$recipe_id ] );
	}

	
	// проверяем допустимо ли проведение манипуляций
	// если есть ограничения -> завершаем скрипт
	function check_permission() { put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $DB, $session;
		$restriction = empty( uid() );
		if ($restriction) _d( ['error'=>'недостаточно полномочий для проведения операции' ] );
		
 		return $sqlstr;
	}

	// user ID 
	function uid() {  put_log_f(__FUNCTION__,__LINE__,__FILE__);
		global $session;
		
 		return $session->uid;
	}