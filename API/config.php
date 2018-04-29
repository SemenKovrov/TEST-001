<?php
	
// параметры подключения к БД

define("DB_NAME", 'test-001' ); 

define("DB_USER", 'postgres' );

define("DB_PASS", '1234' 	 );

define("DB_HOST", 'localhost');



// отпределяем имена таблиц в БД

define("T_SESSIONS",	'sessions'); 

define("T_USERS",		'users'   ); 

define("T_RECIPES",		'recipes' ); 

define("T_FOTOS",		'photo'  ); 


// каталог для сохранения фото рецептов
define("DIR_PHOTOS",	$_SERVER['DOCUMENT_ROOT'] . '/photo' );  



// режим отладки
define("DEBUG_MODE",	true  ); 
