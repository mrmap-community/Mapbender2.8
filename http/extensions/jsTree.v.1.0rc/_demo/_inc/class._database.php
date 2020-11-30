<?php
class _database {
	private $link		= false;
	private $result		= false;
	private $row		= false;

	public $settings	= array(
			"servername"=> "localhost",
			"serverport"=> "3306",
			"username"	=> false,
			"password"	=> false,
			"database"	=> false,
			"persist"	=> false,
			"dieonerror"=> false,
			"showerror"	=> false,
			"error_file"=> true
		);

	function __construct() {
		global $db_config;
		$this->settings = array_merge($this->settings, $db_config);
		if($this->settings["error_file"] === true) $this->settings["error_file"] = dirname(__FILE__)."/__mysql_errors.log";
	}

	function connect() {
		if (!$this->link) {
			$this->link = ($this->settings["persist"]) ? 
				mysqli_connect('p:' .
					$this->settings["servername"].":".$this->settings["serverport"], 
					$this->settings["username"], 
					$this->settings["password"]
				) : 
				mysqli_connect(
					$this->settings["servername"].":".$this->settings["serverport"], 
					$this->settings["username"], 
					$this->settings["password"]
				) or $this->error();
		}
		if (!mysqli_select_db($this->link, $this->settings["database"])) $this->error();
		if($this->link) mysqli_query($this->link, "SET NAMES 'utf8'");
		return ($this->link) ? true : false;
	}

	function query($sql) {
		if (!$this->link && !$this->connect()) $this->error();
		if (!($this->result = mysqli_query($this->link, $sql))) $this->error($sql);
		return ($this->result) ? true : false;
	}
	
	function nextr() {
		if(!$this->result) {
			$this->error("No query pending");
			return false;
		}
		unset($this->row);
		$this->row = mysqli_fetch_array($this->result, MYSQLI_BOTH);
		return ($this->row) ? true : false ;
	}

	function get_row($mode = "both") {
		if(!$this->row) return false;

		$return = array();
		switch($mode) {
			case "assoc":
				foreach($this->row as $k => $v) {
					if(!is_int($k)) $return[$k] = $v;
				}
				break;
			case "num":
				foreach($this->row as $k => $v) {
					if(is_int($k)) $return[$k] = $v;
				}
				break;
			default:
				$return = $this->row;
				break;
		}
		return $return;
	}

	function get_all($mode = "both", $key = false) {
		if(!$this->result) {
			$this->error("No query pending");
			return false;
		}
		$return = array();
		while($this->nextr()) {
			if($key !== false) $return[$this->f($key)] = $this->get_row($mode);
			else $return[] = $this->get_row($mode);
		}
		return $return;
	}

	function f($index) {
		return stripslashes($this->row[$index]);
	}

	function go_to($row) {
		if(!$this->result) {
			$this->error("No query pending");
			return false;
		}
		if(!mysqli_data_seek($this->result, $row)) $this->error();
	}

	function nf() {
		if ($numb = mysqli_num_rows($this->result) === false) $this->error();
		return mysqli_num_rows($this->result);
	}
	function af() {
		return mysqli_affected_rows($this->link);
	}
	function error($string="") {
		$error = mysqli_error($this->link);
		if($this->settings["show_error"]) echo $error;
		if($this->settings["error_file"] !== false) {
			$handle = @fopen($this->settings["error_file"], "a+");
			if($handle) {
				@fwrite($handle, "[".date("Y-m-d H:i:s")."] ".$string." <".$error.">\n");
				@fclose($handle);
			}
		}
		if($this->settings["dieonerror"]) {
			if(isset($this->result)) mysqli_free_result($this->result);
			mysqli_close($this->link);
			die();
		}
	}
	function insert_id() {
		if(!$this->link) return false;
		return mysqli_insert_id($this->link);
	}
	function escape($string){
		if(!$this->link) return addslashes($string);
		return mysqli_real_escape_string($string);
	}

	function destroy(){
		if (isset($this->result)) mysqli_free_result($this->result);
		if (isset($this->link)) mysqli_close($this->link);
	}


}
?>