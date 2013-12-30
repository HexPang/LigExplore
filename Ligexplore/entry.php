<?php
/**
 * 访问入口点
 * @author HexPang
 * @copyright JI'AO Information Technology Co. Limited
 * @version 1.0
 *
 */
require_once 'kernel/common.php';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$func = isset($_REQUEST['func']) ? $_REQUEST['func'] : "";
$data = isset($_REQUEST['data']) ? $_REQUEST['data'] : "";
if($func != ""){
	require_once "interface/I{$func}.php";
	require_once "function/{$func}.php";
}
$class = null;
$result = array('code'=>0,'message'=>'','data'=>$data,'total'=>0);
if($action!='' && $func != ''){
	try {
		$class = new ReflectionClass($func);
	} catch (Exception $e) {
		$result['data'] = array('code'=>0,'error'=>$e,'message'=>"Class {$func} Not Exists In {$func}.php .",'data'=>$data,'action'=>$action);
		exit(json_encode($result));
	}
	if($class->hasMethod($action)){
		$ec=$class->getmethod($action);
		$fuc=$class->newInstance();
		$result['data'] = $ec->invoke($fuc,$_REQUEST);
		if(is_array($result['data'])){
			if(count($result['data']) == 0){
				$result['data'] = "";
			}else{
				$result['data'] = json_encode($result['data']);
			}
		}
		if($result['data']!=null) {
			$result['code'] = 1;
			global $SQL;
			$result['total'] = $SQL->ResultCount;
		}
	}else{
		$result['message'] = "method {$action} not exists.";
	}
}
exit(json_encode($result));
?>