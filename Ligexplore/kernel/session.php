<?php
/**
 * Session操作
 * @author HexPang
 *
 */
if(!IN_EXPLORE){
	exit("Access Denied.");
}
/**
 * 设置或返回某Session值
 * @param Foo $session_name
 * @param Foo $session_value
 */
function s($session_name,$session_value = ""){
	if($session_value=="" && isset($_SESSION[$session_name])){
		return unserialize($_SESSION[$session_name]);
	}elseif($session_value!=""){
		if(isset($_REQUEST['dbg'])){
			echo "session {$session_name} set : ";
			var_dump($session_value);
		}
		$_SESSION[$session_name] = serialize($session_value);
	}else{
		return NULL;
	}
}
?>