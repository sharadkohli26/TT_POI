<?php
//called when somebody wants to add, edit or delete a booking
class NearBy
{
	private $mparams;
	//	
	
	public function __construct($params){
		$this->mparams=$params;
	}
		
	/**	 
	 @return an associative array.	
	 */
	public function WeekendGetawayAction(){
		
		$nbentry = new NearByEntry($this->mparams[DBV_DBOO]);
		//TODO:check if size of params is correct as per this method				 	
					
		$mcity=$this->mparams['city'];
		$mcountry=$this->mparams['country'];
		$msortby=$this->mparams['sortby'];
		$numres=$this->mparams['numres'];
		$maxdist = $this->mparams['maxdist'];
		

		return $nbentry->GetWeekendGetaway($mcity, $mcountry, $msortby, $numres,$maxdist);
		//TODO: handle successful entry or no entry		
	}
	
	public function AddCountryAction(){
		ini_set('max_execution_time', 0);
		$nbentry = new NearByEntry($this->mparams[DBV_DBOO]);
		//TODO:check if size of params is correct as per this method				 	
							
		$mcountry=$this->mparams['country'];
		return $nbentry->AddNewCountry($mcountry);
	}
}
?> 