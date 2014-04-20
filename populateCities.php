<?php
require_once './Inc/allconstants.php';
require_once './Inc/DBOperations.Class.php';
//read data from remote file      
	   
$data_file = file_get_contents("./Inc/cities2.csv"); 

// put data in a local file
$temp_file = tempnam(sys_get_temp_dir(),'TMP');
file_put_contents($temp_file,$data_file);
$temp_file=str_replace("\\","/",$temp_file);

$DBOO=new DBOperations();
$DBOO->SelectDatabase(DB_POI);

$retval = $DBOO->LoadDataFromCSV($temp_file, DBT_CITIES);
unlink($temp_file);
if($retval){
	//todo retrieve data
	echo "Cities Populated";
}
else{
	echo "Fail!!";
	die();	
}
  
//Delete temp file

?>
