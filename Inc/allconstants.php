<?php

define("RESULT_STATUS","status");
define("RESULT_PAYLOAD","payload");
define("ERRPAYLOAD_MESSAGE","error");
define("DBV_DBOO","dboo");


define("WIKIPEDIA_PAGEVIEWS_URL","http://stats.grok.se/json/en/latest90/");
define("WIKIPEDIA_BACKLINKS_URL","http://toolserver.org/~dispenser/cgi-bin/backlinkscount.py?title=");
define("WIKITRAVEL_API_URL","http://wikitravel.org/wiki/en/api.php?");
define("WIKIPEDIA_API_URL","http://en.wikipedia.org/w/api.php?");


define("DB_POI", "TT_POI");
define("DBT_CITIES", "cities");
define("DBT_COUNTRIES", "countries");
define("DBT_REGION", "regions");
define("DBT_CITYNEARBY", "citynearby");
define("DBT_CITYWIKISTATS","cityWikiStats");


define("CITY_CITYID",DBT_CITIES.".CityId");
define("CITY_COUNTRY",DBT_CITIES.".Country");
define("CITY_REGION",DBT_CITIES.".Region");
define("CITY_CITYNAME",DBT_CITIES.".City");
define("CITY_LAT",DBT_CITIES.".Latitude");
define("CITY_LON",DBT_CITIES.".Longitude");
define("CITY_TIMEZONE",DBT_CITIES.".TimeZone");

define("CITYNEARBY_PAIRID",DBT_CITYNEARBY.".PairId");
define("CITYNEARBY_SRCCITY",DBT_CITYNEARBY.".SourceCity");
define("CITYNEARBY_SRCCOUNTRY",DBT_CITYNEARBY.".SourceCountry");
define("CITYNEARBY_DESTCITY",DBT_CITYNEARBY.".DestCity");
define("CITYNEARBY_DESTCOUNTRY",DBT_CITYNEARBY.".DestCountry");
define("CITYNEARBY_DIST",DBT_CITYNEARBY.".Dist");

define("CITYWIKISTATS_CITYID", DBT_CITYWIKISTATS.".CityId");
define("CITYWIKISTATS_WIKIPAGEVIEW", DBT_CITYWIKISTATS.".WikiPageView");
define("CITYWIKISTATS_WIKILINKSTO", DBT_CITYWIKISTATS.".WikiLinksTo");
define("CITYWIKISTATS_WIKITRAVELSEE", DBT_CITYWIKISTATS.".WikiTravelSee");
define("CITYWIKISTATS_WIKITRAVELSLEEP", DBT_CITYWIKISTATS.".WikiTravelSleep");


define("NBE_VALIDATECITY",1000);
define("NBE_SOURCE_EXIST",1010);
define("NBE_COUNTRY_CITIES", 1020);
define("NBE_GEOCORD_CITYCOUNTRY",1030);
define("NBE_LISTOF_NEARBYCITIES",1040);
define("NBE_GETCITYID_CITY",1050);
define("NBE_WIKICITYSTATS_CITYID",1060);

define("NBE_VALIDATECOUNTRY",2000);



define("USER_NBE_INVALIDCITY", "Invalid City name");
define("USER_NBE_MULTIPLE_CITYMATCH", "More than 1 city for the given city country pair");
define("USER_NBE_CITYNOTFOUND", "City is not present in our database");
define("USER_NBE_WEEKENDGETAWAY_RESULTFAILED", "Unable to show results currently");
define("USER_NBE_INVALIDREQUEST", "Invalid Request");

define("USER_NBE_INVALIDCOUNTRY", "Invalid Country name");
define("USER_NBE_MULTIPLE_COUNTRYMATCH", "More than 1 country found");
define("USER_NBE_COUNTRYNOTFOUND", "Country not present in the database");

//**************************************DBOperations*********************
define("EXCP_DBERR111", "Exception_DBERR111::Unable to connect to database , please try again later");
define("EXCP_DBO_DATABASECONNECT", "Exception_DBO_DATABASECONNECT::Unable to connect the database!!");
define("EXCP_DBO_TRANSACTIONSTART_FAILED", "Exception_DBO_TransactionStartFailed::Unable to start transaction mode!!");
define("EXCP_DBO_DATABASESELECT", "Exception_DBO_DATABASESELECT::Unable to select the database!!");
define("EXCP_DBO_PREPARE", "Exception_DBO_PREPARE::Mysqli Prepare failed!!");
define("EXCP_DBO_SQLQUERY", "Exception_DBO_SQLQUERY::Mysqli Query failed!!");
define("EXCP_DBO_INSERTNUMCOLVAL", "Exception_DBO_INSERTNUMCOLVAL::Number of column names and values to insert dont match");
define("EXCP_DBO_INSERTUPDATENUMCOLVAL", "Exception_DBO_INSERTUPDATENUMCOLVAL::Number of values in insert and on duplicate to update dont match");
define("EXCP_DBO_UPDATENUMCOLVAL", "Exception_DBO_UPDATENUMCOLVAL::Number of values to update and number of columns to update dont match");
define("EXCP_DBO_EQUALORNUMCOLVAL", "Exception_DBO_EQUALORNUMCOLVAL::Number of column names and values dont match");

define("EXCP_DBO_BINDEXECUTE", "Exception_DBO_BINDEXECUTE::Bind parameters or execute failed");
?> 