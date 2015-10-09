<?php if(!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-5-28
 * Time: 9:22:59
 */
class CXT_mysql_driver {

	var $version = '';
	var $querynum = 0;
	var $link = null;

	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $dbcharset = '') {
		$func = empty($pconnect) ? 'mysql_connect' : 'mysql_pconnect';
		if(!$this->link = $func($dbhost, $dbuser, $dbpw, 1)) {
			$this->halt('Can not connect to MySQL server');
		} else {
			if($this->version() > '4.1') {
				//$serverset = $dbcharset ? 'character_set_connection='.$dbcharset.', character_set_results='.$dbcharset.', character_set_client=binary' : '';
				//$serverset .= $this->version() > '5.0.1' ? ((empty($serverset) ? '' : ',').'sql_mode=\'\'') : '';
				//$serverset && mysql_query("SET $serverset", $this->link);
				mysql_query("SET NAMES $dbcharset", $this->link);
			}
			$dbname && mysql_select_db($dbname, $this->link);
		}
        return $this;
	}

	function select_db($dbname) {
		return mysql_select_db($dbname, $this->link);
	}

	function fetch_array($query, $result_type = MYSQL_ASSOC) {
		return mysql_fetch_array($query, $result_type);
	}

	function fetch_first($sql) {
		return $this->fetch_array($this->query($sql));
	}

	function result_first($sql) {
		return $this->result($this->query($sql), 0);
	}

	function query($sql, $type = '') {
		$func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ?
			'mysql_unbuffered_query' : 'mysql_query';
		if(!($query = $func($sql, $this->link))) {
			/*if(in_array($this->errno(), array(2006, 2013)) && substr($type, 0, 5) != 'RETRY') {
				$this->close();
				require DISCUZ_ROOT.'./config.inc.php';
				$this->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
				$this->query($sql, 'RETRY'.$type);
			} elseif($type != 'SILENT' && substr($type, 5) != 'SILENT') {
				$this->halt('MySQL Query Error', $sql);
			}*/
			$this->halt('MySQL Query Error', $sql);
		}

		$this->querynum++;
		return $query;
	}

	function affected_rows() {
		return mysql_affected_rows($this->link);
	}

	function error() {
		return (($this->link) ? mysql_error($this->link) : mysql_error());
	}

	function errno() {
		return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
	}

	function result($query, $row = 0) {
		$query = @mysql_result($query, $row);
		return $query;
	}

	function num_rows($query) {
		$query = mysql_num_rows($query);
		return $query;
	}


	function num_fields($query) {
		return mysql_num_fields($query);
	}

	function free_result($query) {
		return mysql_free_result($query);
	}

	function insert_id() {
	    $id = mysql_insert_id($this->link);
		return $id >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		$query = mysql_fetch_row($query);
		return $query;
	}

	function fetch_fields($query) {
		return mysql_fetch_field($query);
	}

	function version() {
		if(empty($this->version)) {
			$this->version = mysql_get_server_info($this->link);
		}
		return $this->version;
	}

    function select($sql, $keyfield = '') {
		$array = array();
		$result = $this->query($sql);
		if($keyfield) {
		    while($r = $this->fetch_array($result)){
		        $key = $r[$keyfield];
				$array[$key] = $r;   
		    }
		} else {
		    while($r = $this->fetch_array($result)){
		       $array[] = $r;
		    }    
		}
		$this->free_result($result);
		return $array;
	}
	
    function insert($tablename, $inserarr, $returnid = 0, $type = '', $replace = FALSE, $silent=0) {
        $insertkeysql = $insertvaluesql = $comma = '';
        foreach ($inserarr as $k => $v) {
            $insertkeysql .= $comma.'`'.$k.'`';
            //$insertvaluesql .= $comma.'\''.addslashes($v).'\'';
            $insertvaluesql .= $comma.'\''.$v.'\'';
            $comma = ',';
        }
        $method = $replace?'REPLACE':'INSERT';
        $r = $this->query($method.' INTO '.xt_tname($tablename,$type).' ('.$insertkeysql.') VALUES ('.$insertvaluesql.') ', $silent?'SILENT':'');
        if($returnid && !$replace) {
            return $this->insert_id();
        }
        return $r;
    }
    function remove($tablename, $wheresqlarr, $type = '') {
        if(empty($wheresqlarr)) {
            return $this->query('TRUNCATE TABLE '.xt_tname($tablename,$type));
        } else {
            return $this->query('DELETE FROM '.xt_tname($tablename,$type).' WHERE '.$this->getwheresql($wheresqlarr));
        }
    }
    function update($tablename, $setsqlarr, $wheresqlarr, $type = ''){
        $setsql = $comma = '';
        foreach ($setsqlarr as $k => $v) {
            $setsql .= $comma.'`'.$k.'`=\''.$v.'\'';
            $comma = ', ';
        }
        return $this->query('UPDATE '.xt_tname($tablename,$type).' SET '.$setsql.' WHERE '.$this->getwheresql($wheresqlarr));
    }
    

    function get_primary($tablename, $type = '') {
		$result = $this->query('SHOW COLUMNS FROM '.xt_tname($tablename,$type));
		while($r = $this->fetch_array($result)) {
			if($r['Key'] == 'PRI') break;
		}
		$this->free_result($result);
		return $r['Field'];
	}

	function check_fields($tablename, $array, $type = '') {
		$fields = $this->get_fields(xt_tname($tablename,$type));
		foreach($array as $k=>$v) {
			if(!in_array($k,$fields)) {
				$this->halt('MySQL Query Error', "Unknown column '$k' in field list");
				return false;
			}
		}
	}

	function get_fields($tablename, $type = '') {
		$fields = array();
		$result = $this->query("SHOW COLUMNS FROM ".xt_tname($tablename,$type));
		while($r = $this->fetch_array($result)) {
			$fields[] = $r['Field'];
		}
		$this->free_result($result);
		return $fields;
	}
	
	function table_status($tablename, $type = ''){
		return $this->fetch_first("SHOW TABLE STATUS LIKE '".xt_tname($tablename,$type)."'");
	}
	
	function close() {
		return mysql_close($this->link);
	}

	function halt($message = '', $sql = '') {
	    trigger_error($message.':'.$sql, E_USER_WARNING);
	}

	function getwheresql($wheresqlarr) {
        $result = $comma = '';
        if(empty($wheresqlarr)) {
            $result = '1';
        } elseif(is_array($wheresqlarr)) {
            foreach ($wheresqlarr as $key => $value) {
                $result .= $comma.$key.'=\''.$value.'\'';
                $comma = ' AND ';
            }
        } else {
            $result = $wheresqlarr;
        }
        return $result;
    }
}
?>