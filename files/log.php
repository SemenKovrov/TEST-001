<?php 
$LOG_BUFF =''; // сюда будем скидывать отладочную информацию

 

if(!defined("LOG_ENABLE")) define("LOG_ENABLE",	DEBUG_MODE ); // true // ведение журнала событий (LOG журнала)

function pr_r($array,$tex='', $fn='') //отображаем массив // вызываем так: pr_r( $array, 'array', __FUNCTION__);
{	 
    if ($tex!='') $tex.='=';
	put_log( '<fieldset>');if ($fn!='') put_log( '<legend>'.$fn.'</legend>');
	put_log('<pre><strong>'.$tex.'</strong>'.print_r($array,1).'</pre></fieldset>'); 	
	//put_log('<div class="dumpspoiler"><pre>'.$tex.''.var_dump($array).'</pre></div>'); 	
}

function pr_v($var, $varname='',$line='',$file='') // именование точки прохода// вызываем так:  pr_v($var, 'varname',__LINE__,__FILE__);
{	$s =''; 
    if ($varname!='') $varname = '<b>'.$varname.'</b> ';
    if ($line!='') $line = ' <span style="font-size:80%;">(строка:[<b>'.$line.'</b>])</span>';
    if ($file!='') $file = ' <span style="font-size:80%;">(файл:[<b>'.$file.'</b>])</span>';
	$s.='<div class="dump_var">';
	$s.='<span>Значение переменной '.$varname.'= [<b>'.$var.'</b>] '.$line.' '.$file.'</span>';
	$s.='</div>';
	put_log($s); 
}



function pr__($file,$line,$funcname='') // именование точки прохода// вызываем так: PR__(__FILE__,__LINE__,__FUNCTION__);
{	 
    if ($funcname!='') $funcname = ', функция: [<b>'.$funcname.'</b>]';
	put_log('<p>line: ['.$line.'], file: ['.$file.']'.$funcname.' ----------------- step point ---------------</p>'); 	
	//put_log('<div class="dumpspoiler"><pre>'.$tex.''.var_dump($array).'</pre></div>'); 	
}
		 

function put_log_f($func, $line='',$file='') // именование точки прохода// вызываем так: put_log_f(__FUNCTION__,__LINE__,__FILE__);
{	if (!DEBUG_MODE) return; 
    global $log_funcs_list; $log_funcs_list[]='вызвана функция: <b>'.$func.'</b> <small>из [ файла: <b>'.$file.' </b>]</small>';

    $s = '<p class="log_func" style="color:#060; ">вызвана функция: <b>'.$func.'</b> из'; 
    if ($file !='') $s .= ' <small>[ файла: <b>'.$file.' </b>]</small>';
    if ($line !='') $s .= ' <small>[ line: <b>'.$line.' </b>]</small>';
	$s.='</p>';
	put_log($s); 
}
		 
function load_file_log( $file, $cmnt='') // помещаем в начале файла (для отслеживания работы CMS) load_file_log(__FILE__);
{	if (!DEBUG_MODE) return; 
	global $log_files_list;$log_files_list[]=$file;
    $s = '<p class="log_load_file">Загружен файл: '.$file.'</p>'; 
	put_log($s);	
}

$debug_buffer =''; // сюда сливается весь мусор какой мог выдаваться при работе скрипта до вывода заголовков и контента
$log_buffer = array();

$log_files_list = array();
$log_funcs_list = array();

$log_dir = $_SERVER['DOCUMENT_ROOT']. '/logs';


function put_log($logtext) //запись лога
{	if (!LOG_ENABLE) return;
	global $log_buffer , $LOG_BUFF;
    $log_buffer[]=$logtext;	
 	$LOG_BUFF .= $logtext . PHP_EOL;
}

function put_file($logtext, $filename='') //запись лога
{	global $log_dir, $LogDir_available;
	if (!LOG_ENABLE or !$LogDir_available) return;
	if ($filename=='')$fn = $log_dir . '/common.log';
	else $fn = $log_dir .'/'. basename($filename, ".php").'.log';
	//file_put_contents ($fn, "[".microtime(1)."]".date("Y-m-d H:i:s") ." >> ".$logtext."\r\n" , FILE_APPEND + LOCK_EX);
	file_put_contents ($fn, date("Y-m-d H:i:s") ." >> ".$logtext."\r\n" , FILE_APPEND + LOCK_EX);
}

function print_log($par='') //вывод лога
{	//if (!LOG_ENABLE) return;

	$cfg_WS = ( function_exists('_cfg') )?_cfg('show_work_stat'):0;
	if ( LOG_ENABLE || $cfg_WS>0) // подгружаем таблицу стилей
	{
		echo "<style>";
		echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/out/css/logging.css');
		echo "</style>";
	}
	
	if ( LOG_ENABLE )
	{
		show_debugBuf() ;
		global $log_buffer,$log_funcs_list,$log_files_list;
		
		echo '<div style="clear:both; margin-top:100px; position: relative;"></div>';
		echo '<div class="log_wrapper">';
		echo '<h4>===========================<strong>   START LOG   </strong>==================================</h4><hr>';
		foreach ($log_buffer as $outtext) 	  
		{   
			echo $outtext;
		}
		echo '<hr><h4>===========================<strong>   STOP LOG   </strong>==================================</h4>';
		echo '</div><br><br><br><br><br><br><br><br>';

	    echo '<div class="list_frame">   порядок вызова файлов <hr>';
		foreach ($log_files_list as $k=>$outtext) 	  
		{   
			echo ''.$outtext.'<br>';
		}
		echo '</div>';

	    echo '<div class="list_frame">   порядок вызова функций <hr>';
		foreach ($log_funcs_list as $k=>$outtext) 	  
		{   
			echo ''.$outtext.'<br>';
		}
		echo '</div>';

    }
	
	if ($cfg_WS>0) show_work_stat();
	
}

function show_debugBuf() 
{	//if (!LOG_ENABLE) 
	return;
	global $debug_buffer;
	echo '<div style="clear:both; margin-top:500px; margin-bottom:-50px;"></div>';
	echo '<div class="debugFrame">';
	echo '<h4>===========================   debug_buffer   ==================================</h4><hr>';
	pr_r($_POST,'_POST');
	echo  $debug_buffer;
	echo '<hr><h4>===========================   end debug_buffer   ==================================</h4><br></div><br><br><br><br><br><br><br>';
}

function show_work_stat() 
{	global $end_memory_usage, $start_memory_usage,$end_time,$start_time, $DB;
//if (!LOG_ENABLE) 
	return;
echo( '<br><div class="work_stat_wrapper">');
// вычисляем время, затраченное на работу скрипта и записываем в лог
echo(  '<h1>статистические данные работы скриптов CMS</h1>');
echo(  "Общее время исполнения: <b>".round(($end_time-$start_time),5)."</b> секунд <br><br><small>");
// вычисляем объем памяти, которая была использована во время работы скрипта и записываем в лог
$total_memory_usage = $end_memory_usage - $start_memory_usage;
echo( "Расход памяти: <b>" . number_format($total_memory_usage, 0, ',', ' ') . "</b> байт<br>");
	// получаем пиковый объем памяти, которая была использована во время работы скрипта и записываем в лог
	if(function_exists('memory_get_peak_usage')){
		$sss = memory_get_peak_usage();$sss2 = round($sss/1024,2);$sss3 = round($sss2/1024,2);
		echo( "Пиковый расход памяти: <b>" . number_format( $sss, 0, ',', ' ') . "</b> байт   // <b> " . number_format( $sss2, 2, ',', ' ') . "</b> Kбайт  //  <b>" . number_format( $sss3, 2, ',', ' ') . "</b> Mбайт<br>");
	}
echo(  "Количество запросов к БД: <b>".$DB->query_cnt."</b>   ");
echo(  "это заняло время: <b>".round(($DB->query_time),5)."</b> секунд <br>");
echo(  "чистое время скрипта: <b>".round(($end_time-$start_time-$DB->query_time),5)."</b> секунд </div>");
echo(  "<br><br><br><br>");
}
