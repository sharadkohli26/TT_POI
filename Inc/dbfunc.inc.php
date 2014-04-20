<?php

class Database {

	
	/**
	 * Connects to database
	 * @return dbconnection or NULL
	 */
	public static function db_connect() {
		$dbserverid = "localhost";
		$dbuserid = "root";
		$dbpass = "root";
		//$link=mysqli_connect($dbserverid, $dbuserid, $dbpass,TRUE);
		$link = mysqli_connect($dbserverid, $dbuserid, $dbpass);

		if (!$link) {
			//echo("<br>Mysqli_ConnectError::db_connect11::" . mysqli_connect_error($link) . "::Failed to connect to database <br>");
			//TODO:do error logging
			MyErrorHandeler::SQLError(EXCP_DBERR111, mysqli_connect_error($link), debug_backtrace(), array());
			return NULL;
		} else {
			//echo("db accessed successfuly");
			return $link;
		}
	}
	
	/**
	 * Closes the db connection 
	 */
	 public static function db_close($mdbcon){
	 	return mysqli_close($mdbcon);
	 }
	 

}
?>