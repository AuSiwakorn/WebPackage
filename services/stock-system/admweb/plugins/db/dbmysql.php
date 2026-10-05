<?php

define("DB_HOST","$db_host");
define("DB_NAME","$db_name");
define("DB_USER","$db_user");
define("DB_PWD","$db_passwd");

$_SESSION['logs_sql'] = array();
/************************************************************************
 mySQL Database Access Class

 Heavily based on the PHPLIB database access class available at
 http://phplib.netuse.de.


 Methods in the class are:
 query($q) - Established connection to database and runs the query returning
 a query ID if successfull.

 next_record() - Returns the next row in the RecordSet for the last query run.
 Returns False if RecordSet is empty or at the end.

 num_rows()  -Returns the number of rows in the RecordSet from a query.

 f($field_name) - Returns the value of the given field name for the current
 record in the RecordSet.

 sf($field_name) - Returns the value of the field name from the $vars variable
 if it is set, otherwise returns the value of the current
 record in the RecordSet.  Useful for handling forms that have
 been submitted with errors.  This way, fields retain the values
 sent in the $vars variable (user input) instead of the database
 values.

 p($field_name) - Prints the value of the given field name for the current
 record in the RecordSet.

 sp($field_name) - Prints the value of the field name from the $vars variable
 if it is set, otherwise prints the value of the current
 record in the RecordSet.  Useful for handling forms that have
 been submitted with errors.  This way, fields retain the values
 sent in the $vars variable (user input) instead of the database
 values.

 ************************************************************************/


class DB {
	var $lid = 0;             	// Link ID for database connection
	var $qid = 0;			// Query ID for current query
	var $row;			// Current row in query result set
	var $record = array();	// Current row record data
	var $error = "";		// Error Message
	var $errno = "";		// Error Number
	var $insert_id = 0;
	var $info = "";

	public static function &singleton($autoLoad = true)
	{
		static $instance;
		if (!isset($instance)) {
			$class = __CLASS__;
			$instance = new $class($autoLoad);
		}
		return $instance;
	}

	// Connects to DB and returns DB lid
	// PRIVATE
	function connect()
	{
		if ($this->lid == 0) {
			$this->lid = @mysql_connect(DB_HOST,DB_USER,DB_PWD);
			if (!$this->lid) {
				$this->halt("connect(" . DB_HOST . "," . DB_USER . ",PASSWORD)  failed.");
			}

			if (!@mysql_select_db(DB_NAME,$this->lid)) {
				$this->halt("Cannot connect to database ".DB_NAME);
				mysql_query("SET NAMES latin");
				$_SESSION['logs_sql'][] = "SET NAMES latin";
				return 0;
			}
	  mysql_query("SET NAMES utf8");
	  $_SESSION['logs_sql'][] = "SET NAMES utf8";
		}
		return $this->lid;
	}

	function connect2($host, $user, $pass, $dbname)
	{
		if ($this->lid == 0) {
			$this->lid = mysql_connect($host,$user,$pass);
			if (!$this->lid) {
				$this->halt("connect(" . $host . "," . $user . ",PASSWORD)  failed.");
			}

			if (!@mysql_select_db($dbname,$this->lid)) {
				$this->halt("Cannot connect to database ".$dbname);
				mysql_query("SET NAMES latin");
				$_SESSION['logs_sql'][] = "SET NAMES latin";
				return 0;
			}
			mysql_query("SET NAMES utf8");
			$_SESSION['logs_sql'][] = "SET NAMES utf8";
		}
		return $this->lid;
	}

	// Runs query and sets up the query id for the class.
	// PUBLIC
	function query($q, $isHas=true)
	{
		if (empty($q))
		return 0;

		if (!$this->connect()) {
			return 0;
		}

		if ($this->qid) {
			@mysql_free_result($this->qid);
			$this->qid = 0;
		}
		
		///////// logs ////////
		$time_start = microtime(true);
		$this->qid = @mysql_query($q, $this->lid);
		$this->row   = 0;
		$this->errno = mysql_errno();
		$this->error = mysql_error();
		if (!$this->qid) {
			$this->halt("Invalid SQL: ".$q);
		}
			
		///////// logs ////////
		$time_end = microtime(true);
		$logs_sql['sql'] = preg_replace('/\s\s+/',' ',$q);
		$logs_sql['time'] = substr($time_end - $time_start,0,8);
		$logs_sql['status'] = ($this->errno) ? $this->error : 'OK';
		$_SESSION['logs_sql'][] = $logs_sql;//$q;
		return $this->qid;
	}

	function getInsertID() {
		return $this->insert_id = mysql_insert_id();
	}

	function info () {
		return $this->info = mysql_info();
	}

	// Return next record in result set
	// PUBLIC
	function next_record()
	{
		if (!$this->qid) {
			$this->halt("next_record called with no query pending.");
			return 0;
		}

		$this->record = @mysql_fetch_array($this->qid, MYSQL_ASSOC);
		$this->row   += 1;
		$this->errno  = mysql_errno();
		$this->error  = mysql_error();

		$stat = is_array($this->record);
		return $stat;
	}

	// Field Value
	// PUBLIC
	function f($field_name) {
		@$fname = $this->record[$field_name];
		return stripslashes($fname);
	}

	function allRows() {
		return $this->record;
	}

	// Selective field value
	// PUBLIC
	function sf($field_name) {
		global $vars, $default;

		if ($vars["error"] and $vars["$field_name"]) {
			return stripslashes($vars["$field_name"]);
		} elseif ($default["$field_name"]) {
			return stripslashes($default["$field_name"]);
		} else {
			return stripslashes($this->record[$field_name]);
		}
	}

	// Print field
	// PUBLIC
	function p($field_name) {
		print stripslashes($this->record[$field_name]);
	}

	// Selective print field
	// PUBLIC
	function sp($field_name) {
		global $vars, $default;

		if ($vars["error"] and $vars["$field_name"]) {
			print stripslashes($vars["$field_name"]);
		} elseif ($default["$field_name"]) {
			print stripslashes($default["$field_name"]);
		} else {
			print stripslashes($this->record[$field_name]);
		}
	}

	// Returns the number of rows in query
	function num_rows() {

		if ($this->lid) {
			return @mysql_numrows($this->qid);
		}
		else {
			return 0;
		}
	}


	// Halt and display error message
	// PRIVATE
	function halt($msg)
	{
		global $_printerror;
        $time = _TIME_;
		if (!$_printerror) {
			echo '<div style="height:4px; background-color:#990000;position: fixed; top:0px;width:100%;left:0px;" onclick="document.getElementById(\'e_r_r_o_r'.$time.'\').style.display =\'\'; "></div>';
			$_printerror = true;
		}

		echo '<div style="color:#FFF;display:none;background-color:#000;position: fixed; top:5px;width:100%;left:0px;" id="e_r_r_o_r'.$time.'" ondblclick="this.style.display=\'none\' "><pre>';
		$this->error = @mysql_error($this->lid);
		$this->errno = @mysql_errno($this->lid);
		echo '
	-----------------------------------------------------------------------
	Database error : '.$msg.'
	MySQL Error : '.$this->errno.'
	MySQL Error : '.$this->error.'
	';
		echo '</pre></div> ';
        $_SESSION['logs_sql'][] = array('type'=>'Database error', $msg, 'MySQL Error : '.$this->error);
	}
}

//$db = new ps_DB;
$db = &DB::singleton();
?>