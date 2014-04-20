<?php

/**
 * A class that represents a current reservation entry
 * @author Sharad
 *
 */
class NearByEntry {
	//private $dbcon=NULL;
	private $DBOO;

	public $HRID;
	public $UserID;
	/**
	 * Default constructor for this class.
	 * Holds the handle to access database if successfully created (ie if dbcon initialised then successfull) else throws exception
	 * @throws Exception("Exception_DBerr11::Unable to connect to database , please try again later")
	 */
	public function __construct($mDBOO) {
		if (is_null($mDBOO) || empty($mDBOO)) {
			$this -> DBOO = new DBOperations();
			$this -> DBOO -> SelectDatabase(DB_POI);
		} else {
			$this -> DBOO = $mDBOO;
		}
	}

	/**	 
	 * @throws Exception
	 * @return string
	 */
	public function GetWeekendGetaway($mcity, $mcountry, $sortby, $numres, $maxdist) {
		$mcity = trim($mcity);
		$mcountry = trim($mcountry);
		$result = array();

		if (empty($mcity)) {
			throw new Exception(USER_NBE_INVALIDCITY);
		}

		//check wether it exists or not in db,if exists and more then one occurence throw error
		$status = NBE_VALIDATECITY;
		$sqlquery_str = $this -> GetDBQueryString($status, array("city" => $mcity, "country" => $mcountry));
		//echo "<br> $sqlquery_str <br>";
		$out = (int)$this -> DBOO -> SelectCount($sqlquery_str);
		if ($out > 1) {
			throw new Exception(USER_NBE_MULTIPLE_CITYMATCH);
		} elseif ($out == 0) {
			throw new Exception(USER_NBE_CITYNOTFOUND);
		}
		//SO UNIQUE CITY EXISTS
		//get long lat, we know only 1 city-country pair
		$mSrcNBE_Exist = $this -> CheckSourceNBE_Exists($mcity, $mcountry);

		if (!$mSrcNBE_Exist) {
			//create entry

			$mres = $this -> CreateSourceNBE($mcity, $mcountry);
			if (!$mres) {
				throw new Exception(USER_NBE_WEEKENDGETAWAY_RESULTFAILED);
			}

		}

		$out = $this -> GetWeekendGetaway_Final($mcity, $mcountry, $sortby, $numres, $maxdist);
		$result[RESULT_STATUS] = TRUE;
		$result[RESULT_PAYLOAD] = $out;
		return $result;
	}

	public function AddNewCountry($mcountry){
		$mcountry = trim($mcountry);		
		if (empty($mcountry)) {
			throw new Exception(USER_NBE_INVALIDCOUNTRY);
		}
		//check wether it exists or not in db,if exists and more then one occurence throw error
		$status = NBE_VALIDATECOUNTRY;
		$sqlquery_str = $this -> GetDBQueryString($status, array("country" => $mcountry));		
		$out = (int)$this -> DBOO -> SelectCount($sqlquery_str);		
		if ($out == 0) {
			throw new Exception(USER_NBE_COUNTRYNOTFOUND);
		}
		
		$this->UpdateRankings_ForCountry($mcountry);
		$result[RESULT_STATUS] = TRUE;
		$result[RESULT_PAYLOAD] = array("CountryAdded" => $mcountry);
		return $result;
	}
	
	private function CheckSourceNBE_Exists($mcity, $mcountry) {
		$status = NBE_SOURCE_EXIST;
		$sqlquery_str = $this -> GetDBQueryString($status, array("city" => $mcity, "country" => $mcountry));
		$out = $this -> DBOO -> SelectCount($sqlquery_str);
		if ($out == 0) {
			return false;
		} else {
			return true;
		}
	}

	private function GetGeoCord($mcity, $mcountry) {
		//assumed unique pair
		$status = NBE_GEOCORD_CITYCOUNTRY;
		$sqlquery_str = $this -> GetDBQueryString($status, array("city" => $mcity, "country" => $mcountry));
		$SelectReturnArr = $this -> DBOO -> Select($sqlquery_str);
		$geocord = array();
		foreach ($SelectReturnArr as $curr_row) {
			$geocord['lat'] = $curr_row['Lat'];
			$geocord['lon'] = $curr_row['Lon'];
			//echo "<br>";
			//var_dump($geocord);
		}
		return $geocord;
	}

	private function CreateSourceNBE($mcity, $mcountry) {
		$geocord = $this -> GetGeoCord($mcity, $mcountry);
		$srclat = $geocord['lat'];
		$srclon = $geocord['lon'];
		if (is_null($srclat) || empty($srclat) || is_null($srclon) || empty($srclon)) {
			throw new Exception(USER_NBE_CITYNOTFOUND);
			//WHAT ABOUT ANTARTICA
		}
		//echo "<br> $srclat -- $srclon <br>";
		$status = NBE_COUNTRY_CITIES;
		$sqlquery_str = $this -> GetDBQueryString($status, array("city" => $mcity, "country" => $mcountry));
		$SelectReturnArr = $this -> DBOO -> Select($sqlquery_str);
		$out = array();
		foreach ($SelectReturnArr as $curr_row) {
			$dist = $this -> distanceGeoPoints($srclat, $srclon, $curr_row['DestLat'], $curr_row['DestLon']);

			if ($dist == 0) {
				continue;
			}
			$insertcol = array(CITYNEARBY_SRCCITY, CITYNEARBY_SRCCOUNTRY, CITYNEARBY_DESTCITY, CITYNEARBY_DESTCOUNTRY, CITYNEARBY_DIST);
			$insertval = array( array($mcity, $mcountry, $curr_row['DestCity'], $curr_row['DestCountry'], $dist));
			$mtablename = DBT_CITYNEARBY;
			$this -> DBOO -> Insert($mtablename, $insertcol, $insertval);
		}
		return true;
	}

	private function UpdateRankings_ForCountry($mcountry) {
		//IT TAKES time
		
		//get wikipedia pageviews
		//get number of pages which have a link to this page
		//see the sleep and see sections on wikitravel, get length

		//Get a list of all the cities in a country
		$status = NBE_COUNTRY_CITIES;
		$sqlquery_str = $this -> GetDBQueryString($status, array("country" => $mcountry));
		$SelectReturnArr = $this -> DBOO -> Select($sqlquery_str);
		$mtmp = 0;
		foreach ($SelectReturnArr as $curr_row) {
			//FIRST DELETE THE STATS ENTRY IF IT EXISTS
			$this->DBOO->DeleteFromTable(DBT_CITYWIKISTATS, array(CITYWIKISTATS_CITYID), array($curr_row['CityId']));
			$murl = WIKIPEDIA_PAGEVIEWS_URL . $curr_row['DestCity'];
			$murl = str_replace(' ', '%20', $murl);
			$jsonObj = json_decode(file_get_contents($murl));
			//get page views

			$mpageviews = 0;
			foreach ($jsonObj->daily_views as $dview) {
				$mpageviews = $mpageviews + $dview;
			}
			//NumBackLinks TODO:Later
			if ($mpageviews == 0) {
				continue;
			}
			$mbacklinks = 0;
			//

			$msleeplen = $this -> WikiTravelSectionStat($curr_row['DestCity'], "Sleep");
			//$msleeplen=0;
			if ($msleeplen == 0) {
				$mseelen = 0;
				//$mtmp=$mtmp+1;
				//echo $curr_row['DestCity'];
			} else {
				$mseelen = $this -> WikiTravelSectionStat($curr_row['DestCity'], "See");
			}
			//insert this into data base
			$insertcol = array(CITYWIKISTATS_CITYID, CITYWIKISTATS_WIKILINKSTO, CITYWIKISTATS_WIKIPAGEVIEW, CITYWIKISTATS_WIKITRAVELSEE, CITYWIKISTATS_WIKITRAVELSLEEP);
			$insertval = array( array($curr_row['CityId'], $mbacklinks, $mpageviews, $mseelen, $msleeplen));
			$mtablename = DBT_CITYWIKISTATS;
			$this -> DBOO -> Insert($mtablename, $insertcol, $insertval);
			var_dump($insertval);			
			echo "<br>";
		}
		return true;
	}

	private function WikiTravelSectionStat($title, $secname) {
		//returns length of section in bytes
		$seclen = 0;
		//check what is the section number for given secname
		$murl = WIKITRAVEL_API_URL . "format=json&action=parse&prop=sections&page=" . $title;
		$murl = str_replace(' ', '%20', $murl);
		$jsonObj = json_decode(file_get_contents($murl));
		//check for warning or error
		if (!is_null($jsonObj -> warnings) || !is_null($jsonObj -> error)) {
			return $seclen;
		}
		foreach ($jsonObj->parse->sections as $msec) {
			if (strcasecmp($msec -> line, $secname) == 0) {
				//section found, now with this secction get the size of the section
				$msecid = $msec -> index;
				$murl = WIKITRAVEL_API_URL . "format=json&action=query&prop=revisions&rvprop=content&rvsection=" . $msecid . "&titles=" . $title;
				$murl = str_replace(' ', '%20', $murl);
				$jsonObj = json_decode(file_get_contents($murl));
				if (!is_null($jsonObj -> warnings) || !is_null($jsonObj -> error)) {
					return $seclen;
				}
				foreach ($jsonObj->query->pages as $msec) {
					$tmparr = (array)$msec -> revisions[0];
					$seclen = strlen($tmparr["*"]);
					break;
				}
			}
		}
		return $seclen;

	}

	private function GetWeekendGetaway_Final($mcity, $mcountry, $sortby, $numres, $maxdist) {
		//extract city-dest pairs based from Cities near by table and sorted as asked
		if (is_null($numres) || empty($numres)) {
			$numres = 10;
		}
		if (is_null($maxdist) || empty($maxdist)) {
			$maxdist = 200;
		}

		$status = NBE_LISTOF_NEARBYCITIES;
		$sqlquery_str = $this -> GetDBQueryString($status, array("city" => $mcity, "country" => $mcountry, "maxdist" => $maxdist));
		$NearByCities = $this -> DBOO -> Select($sqlquery_str);
		$PairCityStats = array();

		foreach ($NearByCities as $curr_row) {

			$status = NBE_GETCITYID_CITY;
			$sqlquery_str = $this -> GetDBQueryString($status, array("country" => $mcountry, "city" => $curr_row['DestCity']));

			$SelectReturnArr = $this -> DBOO -> Select($sqlquery_str);
			$destcityid = $SelectReturnArr[0]['CityId'];

			$status = NBE_WIKICITYSTATS_CITYID;
			$sqlquery_str = $this -> GetDBQueryString($status, array("cityId" => $destcityid));
			$SelectReturnArr = $this -> DBOO -> Select($sqlquery_str);
			if (empty($SelectReturnArr)) {
				continue;
			}
			$WikiStatArr = $SelectReturnArr[0];
			if ($WikiStatArr['WikiTravelSee'] == 0) {
				continue;
			}
			$mrow['SourceCity'] = $curr_row['SourceCity'];
			$mrow['DestCity'] = $curr_row['DestCity'];
			$mrow['Dist'] = ceil($curr_row['Dist']);
			$mrow['WPageRank'] = .7 * $WikiStatArr['WikiTravelSee'] + .3 * $WikiStatArr['WikiTravelSleep'] + 0 * ($WikiStatArr['WikiPageViews'] + $WikiStatArr['WikiBackLinks']);
			$PairCityStats[] = $mrow;
		}
		$out = array();
		usort($PairCityStats, array('NearByEntry', 'RankCmpDesc'));
		$PairCityStats = array_slice($PairCityStats, 0, $numres);
		foreach ($PairCityStats as $mrow) {
			$mrow['Snippet'] = $this -> GetWikipediaSnippet($mrow['DestCity']);
			unset($mrow['WPageRank']);
			$out[] = $mrow;
		}
		if (strcasecmp($sortby, "dist") == 0) {
			usort($out, array('NearByEntry', 'DistCmpAsc'));
		}
		return $out;
	}

	private function GetWikipediaSnippet($title) {
		$murl = WIKIPEDIA_API_URL . "format=json&action=query&prop=extracts&exchars=500&exsectionformat=plain&redirects&titles=" . $title;
		$murl = str_replace(' ', '%20', $murl);
		$jsonObj = json_decode(file_get_contents($murl));
		//check for warning or error
		if (!is_null($jsonObj -> warnings) || !is_null($jsonObj -> error)) {
			return null;
		}
		$tmparr = (array)$jsonObj -> query -> pages;
		foreach ($tmparr as $mobj) {
			$mobjarr = (array)$mobj;
			$mwikisnip = strip_tags($mobjarr['extract']);
			$mwikisnip = preg_replace("/\([^)]+\)/", "", $mwikisnip);
			//$mwikisnip = preg_replace("/\&#160;[^&#160;]+\&#160;/", "", $mwikisnip);
			$mwikisnip = str_replace("&#160;", "", $mwikisnip);
			$mwikisnip = preg_replace("/\#[^#]+\#/", "", str_replace("/", "#", $mwikisnip));
			if (strlen($mwikisnip > 300)) {
				$mwikisnip = substr($mwikisnip, 0, 300) . "...";
			}
			return $mwikisnip;
		}
	}

	private function GetDBQueryString($status, $extra) {
		if ($status == NBE_VALIDATECITY) {
			//read city and country and return number of enteries found
			$mcity = $this -> DBOO -> RealEscapeString($extra['city']);
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);
			$sqlquery_str = "SELECT COUNT(*)" . " FROM " . DBT_CITIES . " WHERE " . CITY_CITYNAME . "=" . $mcity . " AND " . CITY_COUNTRY . "=" . $mcountry;
			
		}
		elseif ($status == NBE_VALIDATECOUNTRY) {
			//read country and return number of enteries found			
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);
			$sqlquery_str = "SELECT COUNT(*)" . " FROM " . DBT_CITIES . " WHERE " .CITY_COUNTRY . "=" . $mcountry;			
		} 
		elseif ($status == NBE_SOURCE_EXIST) {
			$mcity = $this -> DBOO -> RealEscapeString($extra['city']);
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);
			$sqlquery_str = "SELECT COUNT(*)" . " FROM " . DBT_CITYNEARBY . " WHERE " . CITYNEARBY_SRCCITY . "=" . $mcity . " AND " . CITYNEARBY_SRCCOUNTRY . "=" . $mcountry;
		} elseif ($status == NBE_COUNTRY_CITIES) {

			$selectarr = array(CITY_CITYID => "CityId", CITY_CITYNAME => "DestCity", CITY_COUNTRY => "DestCountry", CITY_LAT => "DestLat", CITY_LON => "DestLon");
			$selectstr = $this -> DBOO -> GetSelectColumn($selectarr);

			//$mcity = $this -> DBOO -> RealEscapeString($extra['city']);
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);

			$sqlquery_str = $selectstr . " FROM " . DBT_CITIES . " WHERE " . CITY_COUNTRY . "=" . $mcountry;
		} elseif ($status == NBE_GEOCORD_CITYCOUNTRY) {

			$selectarr = array(CITY_LAT => "Lat", CITY_LON => "Lon");
			$selectstr = $this -> DBOO -> GetSelectColumn($selectarr);

			$mcity = $this -> DBOO -> RealEscapeString($extra['city']);
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);

			$sqlquery_str = $selectstr . " FROM " . DBT_CITIES . " WHERE " . CITY_CITYNAME . "=" . $mcity . " AND " . CITY_COUNTRY . "=" . $mcountry;
		} elseif ($status == NBE_LISTOF_NEARBYCITIES) {

			$selectarr = array(CITYNEARBY_SRCCITY => "SourceCity", CITYNEARBY_DESTCITY => "DestCity", CITYNEARBY_DIST => "Dist", CITYNEARBY_SRCCOUNTRY => "Country");
			$selectstr = $this -> DBOO -> GetSelectColumn($selectarr);
			//$limitstr = " LIMIT 0," . $extra['numres'];
			$mcity = $this -> DBOO -> RealEscapeString($extra['city']);
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);
			$maxdist = $this -> DBOO -> RealEscapeString($extra['maxdist']);

			$sqlquery_str = $selectstr . " FROM " . DBT_CITYNEARBY . " WHERE " . CITYNEARBY_SRCCITY . "=" . $mcity . " AND " . CITYNEARBY_SRCCOUNTRY . "=" . $mcountry . " AND " . CITYNEARBY_DIST . "<=" . $maxdist . " ORDER BY " . CITYNEARBY_DIST . " ASC";

		} elseif ($status == NBE_GETCITYID_CITY) {

			$selectarr = array(CITY_CITYID => "CityId");
			$selectstr = $this -> DBOO -> GetSelectColumn($selectarr);

			$mcity = $this -> DBOO -> RealEscapeString($extra['city']);
			$mcountry = $this -> DBOO -> RealEscapeString($extra['country']);

			$sqlquery_str = $selectstr . " FROM " . DBT_CITIES . " WHERE " . CITY_COUNTRY . "=" . $mcountry . " AND " . CITY_CITYNAME . "=" . $mcity;
		} elseif ($status == NBE_WIKICITYSTATS_CITYID) {
			$selectarr = array(CITYWIKISTATS_WIKIPAGEVIEW => 'WikiPageViews', CITYWIKISTATS_WIKILINKSTO => 'WikiBackLinks', CITYWIKISTATS_WIKITRAVELSEE => 'WikiTravelSee', CITYWIKISTATS_WIKITRAVELSLEEP => 'WikiTravelSleep');
			$selectstr = $this -> DBOO -> GetSelectColumn($selectarr);

			$mcityId = $this -> DBOO -> RealEscapeString($extra['cityId']);

			$sqlquery_str = $selectstr . " FROM " . DBT_CITYWIKISTATS . " WHERE " . CITYWIKISTATS_CITYID . "=" . $mcityId;
		} else {
			throw new Exception(USER_NBE_INVALIDREQUEST);
		}
		return $sqlquery_str;
	}

	private function distance($lat1, $lon1, $lat2, $lon2, $unit) {

		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		if ($unit == "K") {
			return ($miles * 1.609344);
		} else if ($unit == "N") {
			return ($miles * 0.8684);
		} else {
			return $miles;
		}
	}

	private function distanceGeoPoints($lat1, $lng1, $lat2, $lng2) {

		$earthRadius = 3958.75;

		$dLat = deg2rad($lat2 - $lat1);
		$dLng = deg2rad($lng2 - $lng1);

		$a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		$dist = $earthRadius * $c;

		// from miles
		$meterConversion = 1609;
		$geopointDistance = $dist * $meterConversion;

		return $geopointDistance / 1000;
	}

	private static function RankCmpDesc($a, $b) {
		return $b['WPageRank'] - $a['WPageRank'];
	}

	private static function DistCmpAsc($a, $b) {
		return $a['Dist'] - $b['Dist'];
	}

}
?>