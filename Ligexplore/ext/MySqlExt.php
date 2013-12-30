<?php
require_once '../kernel/mysql.php';
class MySQLExt extends MySQL {
	/* (non-PHPdoc)
	 * @see MySQL::insertClass()
	 */
	static $instance = null;
	
	function __construct(){
		$this->instance() = $this;
	}
	
	public function insertClass($cls, $table) {
		// TODO Auto-generated method stub
		$arr = getClassMembers ( $cls );
		$tab = getClassMembersToTable ( $arr );
		$vals = getClassValueToTable ( $arr );
		$newId = 0;
		if ($this->Query ( "INSERT INTO `{$table}` ({$tab}) VALUES ({$vals});" )) {
			$newId = $this->NewID ();
		}
		//MySQL::$instance->Query("");
		return $newId;
	}

	
}
?>