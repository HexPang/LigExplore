<?php
/**
 * MYSQL Lib
 * @author HexPang
 *
*/
if (! IN_EXPLORE) {
	exit ( "Access Denied." );
}
define("DB_ASC", "ASC");
define("DB_DESC","DESC");
class MySQL { //数据库部分
	public static $instance = null; //全局
	var $DBServer = 'localhost';
	var $DBName = ''; //数据库名称
	var $DBUser = ''; //数据库用户
	var $DBPass = ''; //数据库密码
	var $OnErrorResume = 1; //错误提示关闭
	var $LinkID = 0; //连接句柄
	var $QueryID = 0; //查询句柄
	/**
	 * 查询结果集
	 * @var Array()
	*/
	var $ResultS = array (); //查询结果集
	var $Error = ''; //错误信息
	var $QueryStr = ''; //查询语句
	var $autoCounter = true;//开启SQL_CALC_FOUND_ROWS功能
	var $ResultCount = 0; //查询条数

	/**
	 * 构造
	 */
	function instance(){
		if(MySQL::$instance==null){
			global $SQL;
			$SQL = new MySQL();
			MySql::$instance = $SQL;
		}
		return MySQL::$instance;
	}
	/**
	 * 连接数据库
	 * @param String $Srv
	 * @param String $Usr
	 * @param String $Pass
	 * @param String $DB
	 * @return number
	 */
	function Connect($Srv = "", $Usr = "", $Pass = "", $DB = "") {
		if ($Srv == "")
			$Srv = $this->DBServer;
		if ($Usr == "")
			$Usr = $this->DBUser;
		if ($Pass == "")
			$Pass = $this->DBPass;
		if ($DB == "")
			$DB = $this->DBName;
		if ($this->LinkID == 0) {
			$this->LinkID = mysql_connect ( $Srv, $Usr, $Pass ) or die ( 'cannot connect to database.' );
		}
		@mysql_select_db ( $DB, $this->LinkID ) or die ( "cannot select database." );
		if (function_exists ( 'mysql_set_charset' ))
			mysql_set_charset ( 'utf8', $this->LinkID );
		return $this->LinkID;
	}
	/**
	 * 释放查询结果
	 */
	function Free() {
		@mysql_free_result ( $this->QueryID );
		$this->QueryID = 0;
		$this->QueryStr = null;
	}
	/**
	 * 查询到的记录总数
	 * @return number
	 */
	function RowS() {
		if (! $this->QueryID)
			return 0;
		return @mysql_num_rows ( $this->QueryID );
	}
	/**
	 * 获取Insert后的新ID
	 * @return number
	 */
	function NewID() {
		if (! $this->QueryID)
			return - 1;
		return mysql_insert_id ( $this->LinkID );
	}
	/**
	 * 下一条记录
	 * @param MYSQL_ASSOC | MYSQL_BOTH $TYPE
	 * @return number
	 */
	function NextRecord($TYPE = MYSQL_ASSOC) {
		if (! $this->QueryID)
			return 0;
		$this->ResultS = @mysql_fetch_array ( $this->QueryID, $TYPE );
	}

	/**
	 * 定位游标
	 * @param number $seek
	 * @return number
	 */
	function Seek($seek) {
		if (! $this->QueryID)
			return 0;
		@mysql_data_seek ( $this->QueryID, $seek );
	}
	/**
	 * 执行查询
	 * @param String $Sql
	 * @return number
	 */
	function Query($Sql = NULL,$return = false) { //
		//echo $Sql."<br>";
		$Sql = $Sql == NULL ? $this->QueryStr : $Sql;
		if ($Sql == "")
			return 0;
		if ($this->LinkID == 0)
			$this->Connect ();
		if ($this->QueryID)
			$this->Free (); //释放原来查询结果
		$this->QueryStr = $Sql;
		if (isset ( $_REQUEST ['dbg'] ))
			echo "{$Sql}<br>";
		$this->QueryID = @mysql_query ( $Sql, $this->LinkID );
		$this->Error = mysql_error ( $this->LinkID );
		if ($this->Error != '') {
			echo "<hr>" . $this->Error . "<br>";
		}
		if($return){
			$this->NextRecord();
			return $this->ResultS;
		}
		return $this->QueryID;
	}
	/**
	 * 返回错误信息
	 * @return string
	 */
	function GetError() {
		return $this->Error;
	}
	/**
	 * 返回结果集
	 * @param String $Name
	 * @return number|multitype:
	 */
	function GetRecord($Name) {
		if (! $this->QueryID)
			return 0;
		return $this->ResultS [$Name];
	}
	/**
	 * 将某个类插入到数据库中
	 * @param unknown_type $cls
	 * @param unknown_type $table
	 */
	function insertClass($cls, $table) {
		$arr = getClassMembers ( $cls );
		$tab = getClassMembersToTable ( $arr );
		$vals = getClassValueToTable ( $arr );
		$newId = 0;
		if ($this->Query ( "INSERT INTO `{$table}` ({$tab}) VALUES ({$vals});" )) {
			$newId = $this->NewID ();
		}
		//$this->Free();
		return $newId;
	}
	/**
	 * 将某个Array插入到数据库中
	 * @param unknown_type $cls
	 * @param unknown_type $table
	 */
	function insertArray($arr, $table) {
		$tab = getClassMembersToTable ( $arr );
		$vals = getClassValueToTable ( $arr );
		$newId = 0;
		if ($this->Query ( "INSERT INTO `{$table}` ({$tab}) VALUES ({$vals});" )) {
			$newId = $this->NewID ();
		}
		//$this->Free();
		return $newId;
	}
	/**
	 * 将结果转换为类
	 * @return Array()
	 */
	function QueryAsObject() {
		$this->NextRecord ();
		return ( object ) $this->ResultS;
	}
	/**
	 * 返回查询所包含的数量
	 */
	function queryAsCount($tab, $arr = NULL) {
		$this->QueryStr = "SELECT COUNT(*) FROM `{$tab}`";
		if ($arr != NULL) {
			$this->QueryStr .= " WHERE ";
			foreach ( $arr as $k => $v ) {
				$this->QueryStr .= "`{$k}` = '{$v}' AND ";
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 4 );
		}
		$this->Query ();
		$rt = 0;
		if ($this->RowS () > 0) {
			$this->NextRecord ();
			$rt = intval ( $this->ResultS ['COUNT(*)'] );
		}
		$this->Free ();
		return $rt;
	}
	/**
	 * Get Query Data
	 * @param String $tab
	 * @param Array $colums
	 * @param Array Needle
	 * @return Ambigous <NULL, Array()>
	 */
	function getResult($tab = NULL, $colums = NULL, $arr = NULL,$groupBy = NULL,$orderBy = NULL) {
		if($tab == NULL && $colums == NULL){
			$this->NextRecord();
			$result = $this->ResultS;
			return $result;
		}
		$this->QueryStr = "SELECT ";
		foreach ( $colums as $v ) {
			if(strpos($v, "`") === false){
				$this->QueryStr .= "`{$v}`,";
			}else{
				$this->QueryStr .= "{$v},";
			}
		}
		$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 1 );
		$this->QueryStr .= "FROM `{$tab}`";
		if ($arr != NULL) {
			$this->QueryStr .= " WHERE ";
			foreach ( $arr as $k => $v ) {
				$val = "";
				$vv = substr ( $k, strlen ( $k ) - 1, 1 );
				if ($vv == ">") {
					$val = ">=";
				} else if ($vv == "<") {
					$val = "<=";
				} else if ($vv == "%"){
					$val = "like";
				}
				if ($val == "") {
					$val = "=";
				} else {
					$k = substr ( $k, 0, strlen ( $k ) - 1 );
				}
				$this->QueryStr .= "`{$k}` {$val} '{$v}' AND ";
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 4 );
		}
		if($groupBy != NULL){
			$this->QueryStr .= " Group By {$groupBy} ";
		}
		if($orderBy != NULL){
			$this->QueryStr .= " ORDER BY";
			foreach($orderBy as $ord=>$by){
				$this->QueryStr .= "`{$ord}` $by ,";
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 1 );
		}
		$this->Query ();
		$rt = NULL;
		if ($this->RowS () > 0) {
			$this->NextRecord ();
			$rt = $this->ResultS;
		}
		$this->Free ();
		return $rt;
	}
	/**
	 * Get Query Datas
	 * @param String $tab
	 * @param Array $colums
	 * @param Array Needle
	 * @param int $limit
	 * @param int $pageshow
	 * @todo split page you can use $limit as page,$pageshow is count.
	 * @return Ambigous <NULL, Array()>
	 */
	function getResults($tab = NULL, $colums = NULL, $arr = NULL, $page = 0,$pageshow = 0,$orderBy = NULL,$groupBy = NULL) {
		if($tab == NULL && $colums == NULL){
			$result = array();
			for($i=0;$i<$this->RowS();$i++){
				$this->NextRecord();
				$result[] = $this->ResultS;
			}
			return $result;
		}
		$this->QueryStr = "SELECT ";
		if($this->autoCounter){
			$this->QueryStr .= "SQL_CALC_FOUND_ROWS ";
		}
		foreach ( $colums as $v ) {
			if(strpos($v, "`") === false){
				$this->QueryStr .= "`{$v}`,";
			}else{
				$this->QueryStr .= "{$v},";
			}
		}
		$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 1 );
		$this->QueryStr .= "FROM `{$tab}`";
		if ($arr != NULL) {
			if(isset($_GET['dbg'])){
				echo "<br>\r\n";
				var_dump($arr);
			}
			$this->QueryStr .= " WHERE ";
			foreach ( $arr as $k => $v ) {
				$val = "";
				$vv = substr ( $k, strlen ( $k ) - 1, 1 );
				if ($vv == ">") {
					$val = ">=";
				} else if ($vv == "<") {
					$val = "<=";
				} else if ($vv == "%"){
					$val = "like";
				} else if ($vv == "!"){
					$val = "<>";
				}
				if ($val == "") {
					$val = "=";
				} else {
					$k = substr ( $k, 0, strlen ( $k ) - 1 );
				}
				$this->QueryStr .= "`{$k}` {$val} '{$v}' AND ";
				if(isset($_GET['dbg'])){
					echo "<br> WHERE {$k} {$val} {$v} <br>";
				}
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 4 );
		}
		if($groupBy != NULL){
			$this->QueryStr .= " Group By {$groupBy} ";
		}
		if($orderBy != NULL){
			$this->QueryStr .= " ORDER BY";
			foreach($orderBy as $ord=>$by){
				$this->QueryStr .= "`{$ord}` $by ,";
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 1 );
		}
		if ($page > 0 && $pageshow == 0) {
			$this->QueryStr = $this->QueryStr . " LIMIT {$page}";
		}else if($page > 0 && $pageshow > 0){
			$offset = $page * $pageshow - $pageshow;
			$this->QueryStr = $this->QueryStr . " LIMIT {$offset},{$pageshow}";
		}
		$this->Query ();
		$rt = array ();
		for($i = 0; $i < $this->RowS (); $i ++) {
			$this->NextRecord ();
			$rt [] = $this->ResultS;
		}
		if($this->autoCounter){
			$this->Query("SELECT FOUND_ROWS();");
			$this->NextRecord();
			$this->ResultCount = intval($this->ResultS['FOUND_ROWS()']);
		}else{
			$this->ResultCount = $this->queryAsCount($tab,$arr);
		}
		$this->Free ();
		return $rt;
	}
	function update($tab, $colums, $arr = NULL) {
		$this->QueryStr = "UPDATE `{$tab}` SET ";
		foreach ( $colums as $k => $v ) {
			$this->QueryStr .= "`{$k}` = '{$v}',";
		}
		$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 1 );
		if ($arr != NULL) {
			$this->QueryStr .= "WHERE ";
			foreach ( $arr as $k => $v ) {
				$this->QueryStr .= "`{$k}` = '{$v}' AND ";
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 4 );
		}
		$this->Query ();
		$this->Free ();
		return true;
	}
	function remove($tab, $arr = NULL) {
		$this->QueryStr = "DELETE FROM `{$tab}`";
		if ($arr != NULL) {
			$this->QueryStr .= "WHERE ";
			foreach ( $arr as $k => $v ) {
				$this->QueryStr .= "`{$k}` = '{$v}' AND ";
			}
			$this->QueryStr = substr ( $this->QueryStr, 0, strlen ( $this->QueryStr ) - 4 );
		}
		$this->Query ();
		$this->Free ();
		return true;
	}
}
?>