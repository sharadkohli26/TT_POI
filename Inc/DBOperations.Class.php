<?php

class DBOperations {
	private $in_transaction;
	private $dbcon;

	public function __construct() {
		$this -> dbcon = $this -> db_connect();
		$this -> in_transaction = false;
	}

	/**
	 * Connects to database
	 * @return dbconnection or NULL
	 */
	public static function db_connect() {
		require_once ("./Inc/dbconnect.php");
		$link = mysqli_connect($dbserverid, $dbuserid, $dbpass);
		if (!$link) {
			MyErrorHandeler::SQLError(EXCP_DBO_DATABASECONNECT, mysqli_connect_error($link), debug_backtrace(), array());
			throw new Exception(EXCP_DBO_DATABASECONNECT);
		} else {
			return $link;
		}
	}

	public static function db_close() {
		unset($this -> in_transaction);
		mysqli_close($this -> dbcon);
		unset($this -> dbcon);
	}

	public function SelectDatabase($mdb) {
		//checks if there is an existing connection to the given databse ..if yes then does not set it
		/* return name of current default database */
		if ($result = mysqli_query($this -> dbcon, "SELECT DATABASE()")) {
			$row = mysqli_fetch_row($result);
			if (strcmp($row[0], $mdb) == 0)
				return;
		}

		//objects are passed by reference...so this will change it everywhere...

		if (!mysqli_select_db($this -> dbcon, $mdb)) {
			$this -> ThrowDBException(EXCP_DBO_DATABASESELECT, mysqli_error($this -> dbcon));
		}
	}

	public function StartTransaction() {
		if (!$this -> in_transaction) {
			$this -> in_transaction = mysqli_query($this -> dbcon, "START TRANSACTION");
			if (!$this -> in_transaction) {
				MyErrorHandeler::SQLError(EXCP_DBO_TRANSACTIONSTART_FAILED, mysqli_connect_error($this -> dbcon), debug_backtrace(), array());
			}
		}
		return $this -> in_transaction;
	}

	public function GetTransactionMode() {
		return $this -> in_transaction;
	}

	public function Commit() {
		if ($this -> in_transaction) {
			mysqli_commit($this -> dbcon);
			$this -> in_transaction = FALSE;
		}
	}

	public function Rollback() {
		if ($this -> in_transaction) {
			mysqli_rollback($this -> dbcon);
			$this -> in_transaction = FALSE;
		}
	}

	private function ThrowDBException($mexcp, $dbexcp) {
		MyErrorHandeler::SQLError($mexcp, $dbexcp, debug_backtrace(), array());
		$this -> Rollback();
		throw new Exception(USER_DBACTIONFAILED);
	}

	private function ThrowUserException($mexcp) {
		MyErrorHandeler::UserError($mexcp, debug_backtrace(), array());
		$this -> Rollback();
		throw new Exception(USER_DBACTIONFAILED);
	}

	//*****************************DB Functions start**************************************
	public function Insert($tablename, $colnames, $colvalues) {
		$insertarr = $colnames;
		$arr_valarr = $colvalues;
		$insertstr = implode(',', $insertarr);
		$Qvaluesarr = array_fill(0, count($insertarr), "?");
		$Qvaluesstr = implode(',', $Qvaluesarr);
		$sqlquery_str = "INSERT INTO " . $tablename . " ( " . $insertstr . " ) VALUES ( " . $Qvaluesstr . " )";
		$sqlquery = mysqli_prepare($this -> dbcon, $sqlquery_str);
		if (!$sqlquery) {
			$this -> ThrowDBException(EXCP_DBO_PREPARE, mysqli_error($this -> dbcon));
		}
		foreach ($arr_valarr as $valarr) {
			if (count($valarr) != count($insertarr)) {
				$this -> ThrowUserException(EXCP_DBO_INSERTNUMCOLVAL);
			}
			$bindvalarr = array();
			foreach ($valarr as $key => $value) {
				$bindvalarr[] = &$valarr[$key];
			}
			$bindvaltype = $this -> GetMYSQLI_BindValueTypeString($bindvalarr);
			$paramarr = array_merge(array($bindvaltype), $bindvalarr);
			$rc = (call_user_func_array(array($sqlquery, "bind_param"), $paramarr) and mysqli_stmt_execute($sqlquery));
			if (!$rc) {
				$this -> ThrowDBException(EXCP_DBO_BINDEXECUTE, mysqli_error($this -> dbcon));
			}
		}
		$result = array();
		$result[RESULT_STATUS] = TRUE;
		$result[RESULT_PAYLOAD] = array("ID" => mysqli_insert_id($this -> dbcon));
		mysqli_stmt_close($sqlquery);
		return $result;
	}

	public function InsertOnDuplicateUpdate($tablename, $colnames, $colvalues, $updatecol, $updateval, $extra_array) {
		if (count($colvalues) != count($updateval)) {
			$this -> ThrowUserException(EXCP_DBO_INSERTUPDATENUMCOLVAL);
		}
		$insertarr = $colnames;
		$arr_valarr = $colvalues;
		$insertstr = implode(',', $insertarr);
		$Qvaluesarr = array_fill(0, count($insertarr), "?");
		$Qvaluesstr = implode(',', $Qvaluesarr);
		$UpdateQString = $this -> Update_QString($updatecol);

		$sqlquery_str = "INSERT INTO " . $tablename . " ( " . $insertstr . " ) VALUES ( " . $Qvaluesstr . " ) ON DUPLICATE KEY UPDATE " . $UpdateQString;
		$sqlquery = mysqli_prepare($this -> dbcon, $sqlquery_str);
		if (!$sqlquery) {
			$this -> ThrowDBException(EXCP_DBO_PREPARE, mysqli_error($this -> dbcon));
		}
		foreach ($arr_valarr as $mainkey => $valarr) {
			if (count($valarr) != count($insertarr)) {
				$this -> ThrowUserException(EXCP_DBO_INSERTNUMCOLVAL);
			}
			$bindvalarr = array();
			foreach ($valarr as $key => $value) {
				$bindvalarr[] = &$valarr[$key];
			}
			$uvalarr = $updateval[$mainkey];
			if (count($uvalarr) != count($updatecol)) {
				$this -> ThrowUserException(EXCP_DBO_UPDATENUMCOLVAL);
			}
			foreach ($uvalarr as $ukey => $uvalue) {
				$bindvalarr[] = &$uvalarr[$ukey];
			}

			$bindvaltype = $this -> GetMYSQLI_BindValueTypeString($bindvalarr);
			$paramarr = array_merge(array($bindvaltype), $bindvalarr);
			$rc = (call_user_func_array(array($sqlquery, "bind_param"), $paramarr) and mysqli_stmt_execute($sqlquery));
			if (!$rc) {
				$this -> ThrowDBException(EXCP_DBO_BINDEXECUTE, mysqli_error($this -> dbcon));
			}
		}
		$result = array();
		$result[RESULT_STATUS] = TRUE;
		$result[RESULT_PAYLOAD] = array("ID" => mysqli_insert_id($this -> dbcon));
		mysqli_stmt_close($sqlquery);
		return $result;
	}

	public function Update_Set_QString($update_set_array) {
		$out = "";
		foreach ($update_set_array as $element) {
			$out = empty($out) ? "SET " . $element . "=?" : $out . ", " . $element . "=?";
		}
		return $out;
	}

	public function Update_QString($update_set_array) {
		$out = "";
		foreach ($update_set_array as $element) {
			$out = empty($out) ? $element . "=?" : $out . ", " . $element . "=?";
		}
		return $out;
	}

	public function Update_WhereEQAnd_QString($update_where_array) {
		$out = "";
		foreach ($update_where_array as $element) {
			$out = empty($out) ? $element . "=?" : $out . " AND " . $element . "=?";
		}
		return $out;

	}

	public function Update_WhereEQOr_QString($update_where_array) {
		$out = "";
		foreach ($update_where_array as $element) {
			$out = empty($out) ? $element . "=?" : $out . " OR " . $element . "=?";
		}
		return $out;
	}

	public function In_QString($melement, $incount) {
		$inString = implode(",", array_fill(0, $incount, "?"));
		$out = $melement . " IN (" . $inString . ")";
		return $out;
	}

	public function SelectWhere_In_QString($mcolname, $valarr) {
		$out = "";

		foreach ($valarr as $element) {
			$element = $this -> RealEscapeString($element);
			$out = empty($out) ? $mcolname . " IN (" . $element : $out . ", " . $element;
		}
		$out = empty($out) ? "" : $out . ")";
		return $out;
	}

	public function EqualOR_String($mcolnamearr, $valarr) {
		if (count($mcolnamearr) != count($valarr)) {
			$this -> ThrowUserException(EXCP_DBO_EQUALORNUMCOLVAL);
		}
		$out = "";
		foreach ($valarr as $key => $value) {
			$value = $this -> RealEscapeString($value);
			$cond = $mcolnamearr[$key] . "=" . $value;
			$out = empty($out) ? "( " . $cond : $out . " OR " . $cond;
		}
		$out = empty($out) ? "" : $out . ")";
		return $out;
	}

	public function Update($sqlquery_str, $valarr) {
		$bindvalarr = $valarr;
		$bindvaltype = $this -> GetMYSQLI_BindValueTypeString($bindvalarr);
		$bindvalarr_ref = array();
		foreach ($bindvalarr as $key => $val) {
			$bindvalarr_ref[] = &$bindvalarr[$key];
			//SOMEHOW THIS CHANGES BINDVALARR TOO...BE CAREFULLLL..
			//TODO::DISCUSS WITH MOHIT
		}
		$paramarr = array_merge(array($bindvaltype), $bindvalarr_ref);
		$sqlquery = mysqli_prepare($this -> dbcon, $sqlquery_str);
		if (!$sqlquery) {
			$this -> ThrowDBException(EXCP_DBO_PREPARE, mysqli_error($this -> dbcon));
		}
		$rc = (call_user_func_array(array($sqlquery, "bind_param"), $paramarr) and mysqli_stmt_execute($sqlquery));
		if (!$rc) {
			$this -> ThrowDBException(EXCP_DBO_BINDEXECUTE, mysqli_error($this -> dbcon));
		}
		$affectedrows = mysqli_stmt_affected_rows($sqlquery);
		mysqli_stmt_close($sqlquery);
		return $affectedrows;
	}

	public function GetSelectColumn($select) {
		$selectstr = "";
		foreach ($select as $key => $value) {
			$value = empty($value) ? "" : " AS " . $value;
			$selectstr = empty($selectstr) ? "SELECT " . $key . $value : $selectstr . ", " . $key . $value;
		}
		return $selectstr;
	}

	public function GetSelectFrom($from) {
		$selectstr = "";
		foreach ($from as $key => $value) {
			$value = empty($value) ? "" : " AS " . $value;
			$selectstr = empty($selectstr) ? "FROM " . $key . $value : $selectstr . ", " . $key . $value;
		}
	}

	public function SelectString_Select($select, $from, $where, $orderby, $join, $groupby, $having, $pageno, $rows_per_page) {
		$selectstr = $this -> GetSelectColumn($select);
		$selectstr = $selectstr . " " . $this -> GetSelectFrom($from);

		foreach ($join as $joinarr) {
			foreach ($joinarr as $key => $value) {
				if (strcmp($key, "ON") == 0) {
					$onstarted = FALSE;
					foreach ($value as $key2 => $value2) {
						if (!$onstarted) {
							$selectstr = $selectstr . " ON " . $key2 . " " . $this -> RealEscapeString($value2);
							$onstarted = TRUE;
							continue;
						}
						$selectstr = $selectstr . " AND " . $key2 . " " . $this -> RealEscapeString($value2);
					}
				} else {
					$selectstr = $selectstr . " " . $key . " " . $value;
				}
			}
		}

		$wherestart = FALSE;
		foreach ($where as $key => $value) {
			if (!$wherestart) {
				$selectstr = $selectstr . " WHERE " . $key . $this -> RealEscapeString($value);
				$wherestart = TRUE;
				continue;
			}
			$selectstr = $selectstr . " AND " . $key . $this -> RealEscapeString($value);
		}

		$orderstart = FALSE;
		foreach ($orderby as $key => $value) {
			if (!$orderstart) {
				$selectstr = $selectstr . " ORDER BY " . $value;
				$fromstart = TRUE;
				continue;
			}
			$selectstr = $selectstr . ", " . $value;
		}
		//echo "<br>Select String <br>".$selectstr."<br>";
		//var_dump($join);
		//die();
		return $this -> Select($selectstr);
	}

	public function Select($sqlquery_str) {
		$sqlquery = mysqli_query($this -> dbcon, $sqlquery_str);
		if (!$sqlquery) {
			$this -> ThrowDBException(EXCP_DBO_SQLQUERY, mysqli_error($this -> dbcon));
		}
		$out = array();
		while ($row = mysqli_fetch_assoc($sqlquery)) {
			$out[] = $row;
		}
		return $out;
	}

	public function SelectCount($sqlquery_str) {
		$sqlquery = mysqli_query($this -> dbcon, $sqlquery_str);
		if (!$sqlquery) {
			$this -> ThrowDBException(EXCP_DBO_SQLQUERY, mysqli_error($this -> dbcon));
		}
		$out = mysqli_fetch_row($sqlquery);
		return $out[0];
	}

	private function GetMYSQLI_BindValueTypeString($minarr) {
		$outstr = "";
		foreach ($minarr as $bindval) {
			if (is_numeric($bindval)) {
				if ((string)(int)$bindval == $bindval) {
					$outstr = $outstr . "i";
				} else {
					$outstr = $outstr . "d";
				}
			} else {
				$outstr = $outstr . "s";
			}
		}
		return $outstr;
	}

	public function LoadDataFromCSV($filename, $TableName) {
		mysqli_query($this -> dbcon, "TRUNCATE TABLE " . $TableName);
		$sqlstr = "LOAD DATA INFILE '" . $filename . "' INTO TABLE " . $TableName . " FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES;";
		$ret = mysqli_query($this -> dbcon, $sqlstr);
		return $ret;
	}

	public function DeleteFromTable($TableName, $colname, $colvalue) {
		//TODO:: Change it DeleteFromTable($TableName,$colname,$colvalue)
		$out = "";
		foreach ($colvalue as $key => $value) {
			$value = $this -> RealEscapeString($value);
			$cond = $colname[$key] . "=" . $value;
			$out = empty($out) ? " WHERE " . $cond : $out . " AND " . $cond;
		}		
		$sqlstr="DELETE FROM ".$TableName.$out;
		$ret = mysqli_query($this -> dbcon, $sqlstr);
		return $ret;
	}

	public function RealEscapeString($mstr) {
		$mstr = mysqli_real_escape_string($this -> dbcon, $mstr);
		return $this -> GetMYSQLI_QuotesString($mstr);
	}

	public function GetMYSQLI_QuotesString($mstr) {
		if (is_numeric($mstr))
			$outstr = $mstr;
		else {
			$outstr = "'" . $mstr . "'";
		}
		return $outstr;
	}

}
?>
