<?php
/**
 * Ligexplore Common Library (INCLUDE).
 * @author HexPang
 *
 */
define ( 'IN_EXPLORE', true );
ini_set('date.timezone','Asia/Urumqi');
define ('ENCODE','iMenuScrEnCrIpT');
require_once 'DBTable.php';
require_once 'mysql.php';
require_once 'global.php';
require_once 'session.php';
@session_start ();
function require_file($fileName,$path = ''){
	require_once "{$path}interface/I{$fileName}.php";
	require_once "{$path}function/{$fileName}.php";
}
function GetUser(){
	return s('user');
}
function downloadFile($url,$saveDir){
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$content=curl_exec($ch);
	if(curl_errno($ch)){
		echo curl_error($ch);
		curl_close($ch);
		return -1;
	}
	else {
		curl_close($ch);
		//提取文件名和文件类型
		$nameArr=explode('/',$url);
		$last_index=count($nameArr)-1;
		$file_name=$nameArr[$last_index];
		$typeeArr = array();
		$typeArr=explode('.',$url);
		$last_index=count($typeArr)-1;
		$file_type=@$typeeArr[intval($last_index)];
		//获得文件大小
		$file_size=strlen($content);
		//通知浏览器下载文件
		Header("Content-type: application/$file_type");
		header('Content-Disposition: attachment; filename="'.$file_name.'"');
		header("Content-Length: ".$file_size);
		$fileName = $saveDir . '/' . $file_name;
		if(file_exists($fileName)){
			unlink($fileName);
		}
		$handle = fopen($fileName, "w+");
		if($handle){
			fwrite($handle, $content);
			fclose($handle);
			return 1;
		}else{
			return -2;
		}
	}
}
function unzip_file($file, $destination){
	// create object
	if(class_exists("ZipArchive")){
		$zip = new ZipArchive() ;
		// open archive
		if ($zip->open($file) !== TRUE) {
			return false;
		}
		// extract contents to destination directory
		$zip->extractTo($destination);
		// close archive
		$zip->close();
	}else{
		return false;
	}
	return true;
}
function requestURLWithSecret($url,$method = 'GET',$data = null){
	require_once 'version.php';
	$appId = AppID;
	$secKey = AppSecret;
	if(stripos($url,"?")===false){
		$url = "{$url}?appId={$appId}&appSecret={$secKey}&version={$version}";
	}else{
		$url = "{$url}&appId={$appId}&appSecret={$secKey}&version={$version}";
	}
	if(isset($_REQUEST['dbg']))
		var_dump($url);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$result = curl_exec($ch);
	curl_close($ch);
	//$result = stripslashes($result);
	$json = json_decode($result,true);
	return $json;
}
/*********************************************************************
 函数名称:encrypt
函数作用:加密解密字符串
使用方法:
加密     :encrypt('str','E','nowamagic');
解密     :encrypt('被加密过的字符串','D','nowamagic');
参数说明:
$string   :需要加密解密的字符串
$operation:判断是加密还是解密:E:加密   D:解密
$key      :加密的钥匙(密匙);
*********************************************************************/
function encrypt($string,$operation,$key='')
{
	$key=md5($key);
	$key_length=strlen($key);
	$string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
	$string_length=strlen($string);
	$rndkey=$box=array();
	$result='';
	for($i=0;$i<=255;$i++)
	{
		$rndkey[$i]=ord($key[$i%$key_length]);
		$box[$i]=$i;
	}
	for($j=$i=0;$i<256;$i++)
	{
		$j=($j+$box[$i]+$rndkey[$i])%256;
		$tmp=$box[$i];
		$box[$i]=$box[$j];
		$box[$j]=$tmp;
	}
	for($a=$j=$i=0;$i<$string_length;$i++)
	{
		$a=($a+1)%256;
		$j=($j+$box[$a])%256;
		$tmp=$box[$a];
		$box[$a]=$box[$j];
		$box[$j]=$tmp;
		$result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
	}
	if($operation=='D')
	{
		if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8))
		{
			return substr($result,8);
		}
		else
		{
			return'';
		}
	}
	else
	{
		return str_replace('=','',base64_encode($result));
	}
}
function checkEmail($email) {
	return eregi ( '^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$', $email ) >= 1;
}
/**
 * 判断字符串类型
 * @param String $str
 * @return string
 */
function checkStr($str) {
	$output = '';
	$a = ereg ( '[' . chr ( 0xa1 ) . '-' . chr ( 0xff ) . ']', $str );
	$b = ereg ( '[0-9]', $str );
	$c = ereg ( '[a-zA-Z]', $str );
	if ($a && $b && $c) {
		$output = '1';
	} //汉字数字英文的混合字符串
	elseif ($a && $b && ! $c) {
		$output = '2';
	} //汉字数字的混合字符串
	elseif ($a && ! $b && $c) {
		$output = '3';
	} //汉字英文的混合字符串
	elseif (! $a && $b && $c) {
		$output = '4';
	} //数字英文的混合字符串
	elseif ($a && ! $b && ! $c) {
		$output = '5';
	} //纯汉字
	elseif (! $a && $b && ! $c) {
		$output = '6';
	} //纯数字
	elseif (! $a && ! $b && $c) {
		$output = '7';
	} //纯英文
	return $output;
}

/**
 * 获取当前时间
 * @return number
 */
function getTime() {
	return time () /*+ 3600 * 8 */;
}
function convertSec($num){
	return intval($num / 60) . "分钟";
	/*$hour = floor($num/3600);
	 $minute = floor(($num-3600*$hour)/60);
	$second = floor((($num-3600*$hour)-60*$minute)%60);
	return $hour.'小时'.$minute.'分';
	*/
}
/**
 * 返回当前IP
 * @return String IP
 */
function GetMyIP() {
	//获取IP地址
	$ip = "";
	if (isset ( $_SERVER ["HTTP_X_FORWARDED_FOR"] ))
		$ip = $_SERVER ["HTTP_X_FORWARDED_FOR"];
	elseif (isset ( $_SERVER ["HTTP_CLIENT_IP"] ))
	$ip = $_SERVER ["HTTP_CLIENT_IP"];
	elseif (isset ( $_SERVER ["REMOTE_ADDR"] ))
	$ip = $_SERVER ['REMOTE_ADDR'];
	elseif (@getenv ( "HTTP_X_FORWARDED_FOR" ))
	$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
	elseif (@getenv ( "HTTP_CLIENT_IP" ))
	$ip = getenv ( "HTTP_CLIENT_IP" );
	elseif (@getenv ( "REMOTE_ADDR" ))
	$ip = getenv ( "REMOTE_ADDR" );
	else
		$ip = "127.0.0.1";
	return $ip;
}
/**
 * 获取某个类中的成员
 * @param Class $object
 * @return multitype:
 */
function getClassMembers($object) {
	return get_object_vars ( $object );
}
/**
 * 将成员数组转换为参数提取
 * @param unknown_type $array
 * @param Array $except
 * @param unknown_type $tag
 * @param unknown_type $split
 * @return string
 */
function getClassMembersToTable($array, $except = null, $tag = "`", $split = ',') {
	$str = "";
	foreach ( $array as $k => $v ) {
		if ($except == null || ! strInArray ( $except, $k )) {
			$str .= $tag . $k . $tag . $split;
		}
	}
	$str = substr ( $str, 0, strlen ( $str ) - 1 );
	return $str;
}
/**
 * 将成员数组转换为参数提取
 * @param unknown_type $array
 * @param Array $except
 * @param unknown_type $tag
 * @param unknown_type $split
 * @return string
 */
function getClassValueToTable($array, $except = null, $tag = "'", $split = ",") {
	$str = "";
	foreach ( $array as $v => $k ) {
		if ($except == null || ! strInArray ( $except, $k )) {
			$str .= $tag . mysql_escape_string($k) . $tag . $split;
		}
	}
	$str = substr ( $str, 0, strlen ( $str ) - 1 );
	return $str;
}
/**
 * 数组中是否包含某字符串
 * @param unknown_type $arr
 * @param unknown_type $str
 * @return boolean
 */
function strInArray($arr, $str) {
	if ($arr == null)
		return false;
	foreach ( $arr as $s ) {
		if ($s == $str)
			return true;
	}
	return false;
}
/**
 * Get Request Value
 * @param unknown_type $key
 * @return Ambigous <NULL, unknown>
 */
function getValue($key,$default = NULL) {
	if($default != null && gettype($default) == "integer"){
		if(!is_numeric($_REQUEST[$key])){
			return $default;
		}
	}
	return isset ( $_REQUEST [$key] ) ? $_REQUEST [$key] : $default;
}

/**
 * 获取上个月的今天
 * @param Time $time
 * @return string
 */
function Last_MonthOfToday($time){
	$last_month_time = mktime(date("G", $time), date("i", $time),
			date("s", $time), date("n", $time), 0, date("Y", $time));
	$last_month_t = date("t", $last_month_time);
	if ($last_month_t < date("j", $time)) {
		return date("Y-m-t H:i:s", $last_month_time);
	}
	return date(date("Y-m", $last_month_time) . "-d", $time);
}
?>