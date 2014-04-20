<?php


ini_set('error_reporting', E_ALL);
error_reporting(E_ALL);
ini_set('log_errors',TRUE);
ini_set('html_errors',FALSE);
ini_set('display_errors',false);
//ini_set('max_execution_time', 0);

require_once './Inc/allconstants.php';
require_once './Inc/MyErrorHandeler.Class.php';
require_once './Inc/DBOperations.Class.php';

require_once "./model/NearBy_Class.php";

try {
	//get all of the parameters in REQUEST
	//$params=$_REQUEST;	
	
	//get the controller and format it correctly so the first 
   //get all the post variables...
   $params=array();
   foreach ($_GET as $key => $value) {
   		//echo "$key::$value<br>";
   		if(strcmp($key, "controller")==0){
   			$controller_str = $value;
			continue;
   		}
		if(strcmp($key, "action")==0){
			$action_str = $value."Action";
			continue;
		}
       $params[$key]=$value;
   }
   //var_dump($_POST);
   	if(!isset($controller_str) or !isset($action_str) ){
		MyErrorHandeler::UserError("Missing Controller or Action.",debug_backtrace(), $_POST);
		throw new Exception('Missing Controller or Action.');
	} 
   
   //check if the controller exists. if not, throw an exception
   if( file_exists("./controller/{$controller_str}_Controller_Class.php") ) {
      include_once "./controller/{$controller_str}_Controller_Class.php";
   } else {
   		   		
		throw new Exception('Invalid Action.');
   }
               
   //check if the action exists in the controller. if not, throw an exception.
   if( method_exists($controller_str, $action_str) === false ) {		
      	throw new Exception('Action is invalid.');
   }     	
   //create a new instance of the controller, and pass
   //it the parameters from the request
   
   //launch the database connection and store it into params
   	$params[DBV_DBOO] = NULL;	
   	$controller = new $controller_str($params);
   	$result = $controller->$action_str(); 
	
} catch (Exception $e) {
	//echo($e->getMessage());
	$result = array();
   	$result[RESULT_STATUS] = FALSE;
   	$result[RESULT_PAYLOAD] = array(ERRPAYLOAD_MESSAGE=>$e->getMessage());
}

//echo json_encode($result);
echo "<br>Success:".($result[RESULT_STATUS]*1)."<br>";
if($result[RESULT_STATUS]){
	echo "<br>Data:<br>";
	foreach($result[RESULT_PAYLOAD] as $resarr) {
		foreach ($resarr as $key => $value) {
			echo "---->$key:$value<br>";
		}
		echo "<br>";	
	}
}
else{
	echo "<br>Message:".$result[RESULT_PAYLOAD][ERRPAYLOAD_MESSAGE]."<br>";
}

?> 
