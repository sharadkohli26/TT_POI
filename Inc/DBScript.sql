CREATE DATABASE `TT_POI` ;

CREATE TABLE TT_POI.Cities(
CityId int AUTO_INCREMENT NOT NULL ,
City varchar( 45 ) NOT NULL ,
Latitude float NOT NULL ,
Longitude float NOT NULL ,
TimeZone varchar( 10 ) NOT NULL ,
Country varchar( 30 ) NOT NULL ,
Region varchar( 50 ) NOT NULL ,
PRIMARY KEY ( CityId )
);

CREATE TABLE TT_POI.citynearby(
PairId int AUTO_INCREMENT NOT NULL ,
SourceCity varchar( 45 ) NOT NULL ,
SourceCountry varchar( 30 ) NOT NULL ,
DestCity varchar( 45 ) NOT NULL ,
DestCountry varchar( 30 ) NOT NULL ,
Dist float NOT NULL ,
PRIMARY KEY ( PairId )
);

CREATE TABLE TT_POI.cityWikiStats(
CityId int NOT NULL ,
WikiPageView float NOT NULL,
WikiLinksTo float NOT NULL,
WikiTravelSee float Not NULL,
WikiTravelSleep float Not NULL,
PRIMARY KEY ( CityId )
);
