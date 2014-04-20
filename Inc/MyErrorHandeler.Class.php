<?php

class MyErrorHandeler{
	public static function UserError($Excp_String,$backtracearr,$extra_array){
		$errstring = "\n-----------User Error::".date('Y-m-d H:i:s')."-------------------------------------\n";
		$errstring = $errstring."<Exception>".$Excp_String."</Exception>\n";
		foreach ($extra_array as $key=>$val) {
			$errstring = $errstring."<ExtraInfo_".$key.">".$val."</ExtraInfo_".$key.">\n";
		}
		$errstring=$errstring.self::GetBackTraceString_FileFuncLineArgs($backtracearr);
		$errstring=$errstring."----------------------------------------------------------------------------------------\n";
		self::logger($errstring,"User");
	}
	
	public static function SQLError($Excp_String,$SQL_Error,$backtracearr,$extra_array){
		$errstring = "\n-----------SQL Error::".date('Y-m-d H:i:s')."-------------------------------------\n";
		$errstring = $errstring."<Exception>".$Excp_String."</Exception>\n";
		$errstring = $errstring."<SQL_Error>".$SQL_Error."</SQL_Error>\n";
		foreach ($extra_array as $key=>$val) {
			$errstring = $errstring."<ExtraInfo_".$key.">".$val."</ExtraInfo_".$key.">\n";
		}
		$errstring=$errstring.self::GetBackTraceString_FileFuncLineArgs($backtracearr);
		$errstring=$errstring."----------------------------------------------------------------------------------------\n";
		self::logger($errstring,"SQL");
	}
	
	public static function SimpleLogger($inarray){
		$logstring = "\n---------Logger::".date('Y-M-D H:i:s')."--------------\n";
		foreach ($inarray as $key => $value) {
			$logstring = $logstring.">".$key.">>".$value."\n";
		}
		$logstring = $logstring."\n---------------------------------------------\n";
		self::logger($logstring, "Logger");
	}
	
	public static function GetBackTraceString_FileFuncLineArgs($backtracearr){
		$out="\t<BackTraceDumpStart>\n";
		$tabtab = "\t";
		foreach ($backtracearr as $row) {
			$out=$out
			.$tabtab."<File>".$row['file']."</File>\n"
			.$tabtab."<Function>".$row['function']."</Function>\n"
			.$tabtab."<Line>".$row['line']."</Line>\n";
			foreach ($row['args'] as $key => $val) {
				if(is_array($val) ){
					//split $val
					//var_dump($val);
					$valstr = implode("<--->", $val);
					
					$out=$out.$tabtab."<ArrArg_".$key.">".$valstr."</ArrArg_".$key.">\n";
				}
				else{
					$out=$out.$tabtab."<Arg_".$key.">".$val."</Arg_".$key.">\n";
				}
			}
		//$tabtab = $tabtab."\t";	
		}
		$out=$out."\t</BackTraceDumpEnd>\n";
		return $out;
	}
	
	// user defined error handling function
public static function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) 
{
    // timestamp for the error entry
    $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
                E_ERROR              => 'Error',
                E_WARNING            => 'Warning',
                E_PARSE              => 'Parsing Error',
                E_NOTICE             => 'Notice',
                E_CORE_ERROR         => 'Core Error',
                E_CORE_WARNING       => 'Core Warning',
                E_COMPILE_ERROR      => 'Compile Error',
                E_COMPILE_WARNING    => 'Compile Warning',
                E_USER_ERROR         => 'User Error',
                E_USER_WARNING       => 'User Warning',
                E_USER_NOTICE        => 'User Notice',
                E_STRICT             => 'Runtime Notice',
                E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
                );
    // set of errors for which a var trace will be saved
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE,E_ERROR,E_WARNING);
    
    $err = "<ErrorEntry>\n";
    $err .= "\t<datetime>" . $dt . "</datetime>\n";
    $err .= "\t<errornum>" . $errno . "</errornum>\n";
    $err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
    $err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
    $err .= "\t<scriptname>" . $filename . "</scriptname>\n";
    $err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";

    if (in_array($errno, $user_errors)) {
        $err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
    }
    $err .= "</ErrorEntry>\n\n";
    
    // for testing
    // echo $err;

    // save to the error log, and e-mail me if there is a critical user error
    self::logger($err,"PHPError");
    //error_log($err, 3, "/usr/local/php4/error.log");
	/*
    if ($errno == E_USER_ERROR) {
        mail("phpdev@example.com", "Critical User Error", $err);
    }*/
}
	
	public static function logger($errstring,$errtype){
		$logfolder="./logs/";
		$filename=$errtype."-".date('Y-m-d').".txt";
		$fullfilepath=$logfolder.$filename;
		$doesLogFileExists = file_exists($fullfilepath);
		if(!$doesLogFileExists){
			$ourFileHandle = fopen($fullfilepath, 'w') ;
			if($ourFileHandle){
					fclose($ourFileHandle);
			}
		}
		file_put_contents($fullfilepath, $errstring,FILE_APPEND);
	}
}
?>
