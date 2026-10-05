<?php
class DB {
	var $conlink;
	var $row;			// Current row in query result set
	var $record = array();	// Current row record data
	var $error = "";		// Error Message
	var $errno = "";		// Error Number
	var $insert_id = 0;
	var $info = "";
	var $result;
	
	public static function &singleton($autoLoad = true)
	{
		static $instance;
		if (!isset($instance)) {
			$class = __CLASS__;
			$instance = new $class($autoLoad);
		}
		return $instance;
	}
	
	function connect()
	{
		if ($this->conlink != '') {
			return $this->conlink;
		} else {
			@$this->conlink = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
			if (mysqli_connect_errno()) {
				//header("Location:autoSetup.php");
				//$this->halt("Connect failed:".mysqli_connect_error());
				$isConnectError = "Connect failed:".mysqli_connect_error();
				include_once(PATH_ADMIN.'/include/autoSetup.php');
				exit();
			}
			
			$this->query("SET NAMES utf8");
			return $this->conlink;
		}
	}
	
	function query($q, $isHas=true)
	{
		if (empty($q)) {
			return 0;
		}
	
		if (!$this->connect()) {
			return 0;
		}
	
		$time_start = microtime(true);
		$this->result = $this->conlink->query($q);
		if (!$this->result) {
			$this->halt("Invalid SQL: ".$q);
		}
		///////// logs ////////
		$time_end = microtime(true);
		$logs_sql['hash'] = $isHas;
		$logs_sql['sql'] = preg_replace('/\s\s+/',' ',$q);
		$logs_sql['time'] = substr($time_end - $time_start,0,8);
		$logs_sql['status'] = ($this->errno) ? $this->error : 'OK';
		$_SESSION['logs_sql'][] = $logs_sql;
		return $this->result;
	}
	
	function next_record()
	{
		if (!$this->connect()) {
			return 0;
		}
		return @$this->data = $this->result->fetch_array(MYSQLI_ASSOC);
	}

	public function escape($val) {
        return $this->conlink->real_escape_string($val);
    }
	
	function f($field_name)
	{
		$r = $this->allRows();
		return $r[$field_name];
	}
	
	function allRows()
	{
		return $this->data;
	}
	
	function num_rows()
	{
		return $this->result->num_rows;
	}
	
	function getInsertID()
	{
		return $this->insert_id = $this->conlink->insert_id;
	}
	
	function halt($msg)
	{
		global $_printerror;
        $time = _TIME_;
		if (!$_printerror) {
			echo '<div style="height:4px; background-color:#990000;position: fixed; top:0px;width:100%;left:0px;" onclick="document.getElementById(\'e_r_r_o_r'.$time.'\').style.display =\'\'; "></div>';
			$_printerror = true;
		}

		echo '<div style="color:#FFF;display:none;background-color:#000;position: fixed; top:5px;width:100%;left:0px;" id="e_r_r_o_r'.$time.'" ondblclick="this.style.display=\'none\' "><pre>';
		
		
		$this->error = @mysqli_connect_error();
		$this->errno = @mysqli_connect_errno();
		echo '
		-----------------------------------------------------------------------
		Database error : '.$msg.'
		MySQL Error : '.$this->conlink->errno.'
		MySQL Error : '.$this->conlink->error.'
		';
		echo 'MYSQL ERROR';
		echo '</pre></div> ';
        $_SESSION['logs_sql'][] = array('type'=>'Database error', $msg, 'MySQL Error : '.$this->error);
	}
	
	
	function pager($func_name, $sql, $num=0, $page=1)
	{
		$this->query($sql, $func_name);

		$aData = array();
		$aData['data'] = array();
		$aData['num_rows'] = $this->num_rows();
		$aData['maxpage'] = ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
		$aData['nextpage'] = (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
		$aData['backpage'] = (($page - 1) > 1) ? ($page - 1) : 1;

		if ($num > 0 && $aData['maxpage'] > 1) {
			$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
			$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
			$this->query($sql, $func_name);
		}

		while ($this->next_record()) {
			$aData['data'][] = $this->allRows();
		}
		$aData['sql'] = $sql;
		return $aData;
	}
	
	function close()
	{
		$res = $this->conlink->close();
	}
}