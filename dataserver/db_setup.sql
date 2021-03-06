-- MySQL dump 10.13  Distrib 5.7.30, for Linux (x86_64)
--
-- Host: localhost    Database: Bearweb
-- ------------------------------------------------------
-- Server version	5.7.30-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `Bearweb`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `Bearweb` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `Bearweb`;

--
-- Table structure for table `BW_Config`
--

DROP TABLE IF EXISTS `BW_Config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_Config` (
  `Site` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `Key` varchar(255) NOT NULL,
  `Value` varchar(4096) NOT NULL DEFAULT '',
  PRIMARY KEY (`Site`,`Key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BW_Object`
--

DROP TABLE IF EXISTS `BW_Object`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_Object` (
  `Site` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `URL` varchar(255) NOT NULL,
  `MIME` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL DEFAULT 'text/plian',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Keywords` varchar(255) NOT NULL DEFAULT '',
  `Description` varchar(4096) NOT NULL DEFAULT '',
  `Binary` longblob,
  PRIMARY KEY (`Site`,`URL`),
  CONSTRAINT `BW_Object__URLLink` FOREIGN KEY (`Site`, `URL`) REFERENCES `BW_Sitemap` (`Site`, `URL`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BW_Session`
--

DROP TABLE IF EXISTS `BW_Session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_Session` (
  `SessionID` char(64) COLLATE latin1_general_cs NOT NULL,
  `CreateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `LastUsed` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Expire` tinyint(4) NOT NULL DEFAULT '0',
  `Username` varchar(16) CHARACTER SET ascii DEFAULT NULL,
  `JSKey` char(64) COLLATE latin1_general_cs NOT NULL,
  `Salt` char(64) COLLATE latin1_general_cs NOT NULL,
  PRIMARY KEY (`SessionID`,`CreateTime`),
  KEY `BW_Session__UsernameLink` (`Username`),
  KEY `Alive` (`Expire`),
  CONSTRAINT `BW_Session__UsernameLink` FOREIGN KEY (`Username`) REFERENCES `BW_User` (`Username`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BW_Sitemap`
--

DROP TABLE IF EXISTS `BW_Sitemap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_Sitemap` (
  `Site` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `URL` varchar(255) NOT NULL,
  `Category` varchar(45) NOT NULL,
  `TemplateMain` varchar(45) NOT NULL,
  `TemplateSub` varchar(45) NOT NULL,
  `Author` varchar(16) DEFAULT NULL,
  `CreateTime` datetime DEFAULT CURRENT_TIMESTAMP,
  `LastModify` datetime DEFAULT CURRENT_TIMESTAMP,
  `Copyright` varchar(255) DEFAULT NULL,
  `Status` char(1) NOT NULL DEFAULT 'O',
  `Info` json NOT NULL,
  PRIMARY KEY (`Site`,`URL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `Bearweb`.`BW_Sitemap_BEFORE_INSERT` BEFORE INSERT ON `BW_Sitemap` FOR EACH ROW
BEGIN
	IF (NEW.Info IS NULL) THEN
		SET NEW.Info = '{}';
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `BW_Transaction`
--

DROP TABLE IF EXISTS `BW_Transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_Transaction` (
  `RecordID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `RequestTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TransactionID` varchar(255) COLLATE latin1_general_cs NOT NULL,
  `SessionID` char(64) COLLATE latin1_general_cs DEFAULT NULL,
  `Username` varchar(16) CHARACTER SET ascii DEFAULT NULL,
  `IP` varchar(45) COLLATE latin1_general_cs DEFAULT NULL,
  `URL` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `ExecutionTime` decimal(10,2) unsigned DEFAULT NULL,
  `Status` char(3) COLLATE latin1_general_cs DEFAULT NULL,
  PRIMARY KEY (`RecordID`),
  KEY `BW_Transaction__UsernameLink` (`Username`),
  KEY `BW_Transaction__SIDLink` (`SessionID`),
  CONSTRAINT `BW_Transaction__SIDLink` FOREIGN KEY (`SessionID`) REFERENCES `BW_Session` (`SessionID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `BW_Transaction__UsernameLink` FOREIGN KEY (`Username`) REFERENCES `BW_User` (`Username`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=160870 DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BW_User`
--

DROP TABLE IF EXISTS `BW_User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_User` (
  `Username` char(16) CHARACTER SET ascii NOT NULL,
  `Nickname` char(16) NOT NULL,
  `Group` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `Password` char(32) CHARACTER SET ascii NOT NULL,
  `LastActiveTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `RegisterIP` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `RegisterTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Email` varchar(128) DEFAULT NULL,
  `Data` json NOT NULL,
  `Photo` blob,
  PRIMARY KEY (`Username`),
  UNIQUE KEY `Username_UNIQUE` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `Bearweb`.`BW_User_BEFORE_INSERT` BEFORE INSERT ON `BW_User` FOR EACH ROW
BEGIN
	IF (NEW.`Data` IS NULL) THEN
		SET NEW.`Data` = '{}';
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `BW_Webpage`
--

DROP TABLE IF EXISTS `BW_Webpage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BW_Webpage` (
  `Site` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `URL` varchar(255) NOT NULL,
  `Language` varchar(5) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `Title` varchar(255) NOT NULL DEFAULT 'Bearweb webpage',
  `Keywords` varchar(255) NOT NULL DEFAULT '',
  `Description` varchar(4096) NOT NULL DEFAULT '',
  `Content` longtext,
  `Source` longtext NOT NULL,
  `Style` varchar(45) NOT NULL DEFAULT 'plaintext',
  PRIMARY KEY (`Site`,`URL`,`Language`),
  CONSTRAINT `BW_Webpage__URLLink` FOREIGN KEY (`Site`, `URL`) REFERENCES `BW_Sitemap` (`Site`, `URL`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'Bearweb'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `Session_autoExpire` */;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8 */ ;;
/*!50003 SET character_set_results = utf8 */ ;;
/*!50003 SET collation_connection  = utf8_general_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = '+00:00' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`root`@`localhost`*/ /*!50106 EVENT `Session_autoExpire` ON SCHEDULE EVERY 1 HOUR STARTS '2019-11-11 16:48:03' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
	UPDATE BW_Session SET `Expire` = 1 WHERE `Expire` = 0 AND LastUsed < SUBTIME(CURRENT_TIMESTAMP,'01:00:00');
END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
/*!50106 DROP EVENT IF EXISTS `Sitemap_GenXML` */;;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb4 */ ;;
/*!50003 SET character_set_results = utf8mb4 */ ;;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = '+00:00' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`root`@`localhost`*/ /*!50106 EVENT `Sitemap_GenXML` ON SCHEDULE EVERY 6 HOUR STARTS '2020-04-02 05:29:36' ON COMPLETION NOT PRESERVE ENABLE DO CALL Event_generateSitemapXMLManager() */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

--
-- Dumping routines for database 'Bearweb'
--
/*!50003 DROP PROCEDURE IF EXISTS `Config_get` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Config_get`(
	IN in_sitename	VARCHAR(45)
)
BEGIN
	SELECT * FROM BW_Config C WHERE C.Site = '' OR C.Site = in_sitename;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Config_write` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Config_write`(
	IN in_site	VARCHAR(45),
    IN in_Key	VARCHAR(255),
    IN in_value	VARCHAR(4096)
)
BEGIN
	INSERT INTO BW_Config (Site,`Key`,`Value`) VALUES (in_site,in_key,in_value)
    ON DUPLICATE KEY UPDATE `Value` = in_value ;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Event_generateSitemapXML` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Event_generateSitemapXML`(
	IN in_site			VARCHAR(45),
    IN in_domainName	VARCHAR(255)
)
BEGIN
	DECLARE done		INT DEFAULT FALSE;
    DECLARE txt			LONGTEXT;
    
    DECLARE site		VARCHAR(45);
    DECLARE url			VARCHAR(255);
    DECLARE languages	VARCHAR(20000);
    
    DECLARE currentLanguages		VARCHAR(20000);
    DECLARE currentLang				VARCHAR(5);
    DECLARE currentLanguagesInner	VARCHAR(20000);
    DECLARE currentLangInner		VARCHAR(5);
    
    DECLARE curs CURSOR FOR
		SELECT S.Site, S.URL, GROUP_CONCAT(W.`Language`)
		FROM BW_Sitemap S LEFT JOIN BW_Webpage W ON (S.Site=W.Site AND S.URL=W.URL)
		WHERE FIND_IN_SET(S.`Status`,'R,r,O,C,D') AND (S.Site = '@ALL' OR S.Site = in_site)
		GROUP BY S.Site, S.URL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
	OPEN curs;
    
    SET txt = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    
    sitemapLoop: LOOP
		/* Read sitemap table */
		FETCH curs INTO site, url, languages;
        IF done THEN
			LEAVE sitemapLoop;
		END IF;
        
        /* Object: No language, language field will be NULL */
        IF languages IS NULL THEN
			SET txt = CONCAT(txt,'<url><loc>',in_domainName,url,'</loc></url>');
        
        /* Webpage: Multilingual, comma separated */
        ELSE
			/* Phase 1: Give the default URL with multilingual field */
            SET currentLanguages = languages;
			SET txt = CONCAT(txt,'<url><loc>',in_domainName,url,'</loc>');
            WHILE ( LENGTH(currentLanguages) > 0 ) DO
				SET currentLang = SUBSTRING_INDEX(currentLanguages,',',1);
				SET currentLanguages = RIGHT( currentLanguages, LENGTH(currentLanguages) - LENGTH(currentLang) - 1 ); /* Truncate the current language and the comma. The last run will give right(x,-1) but it will not break */
                SET txt = CONCAT(txt,'<xhtml:link rel="alternate" hreflang="',currentLang,'" href="',in_domainName,currentLang,'/',url,'"/>');
            END WHILE;
			SET txt = CONCAT(txt,'</url>');
            
            /* Phase 2: For each alternative language, give multilingual info */
            SET currentLanguages = languages;
            WHILE ( LENGTH(currentLanguages) > 0 ) DO
				SET currentLang = SUBSTRING_INDEX(currentLanguages,',',1);
				SET currentLanguages = RIGHT( currentLanguages, LENGTH(currentLanguages) - LENGTH(currentLang) - 1 ); 
                SET txt = CONCAT(txt,'<url><loc>',in_domainName,currentLang,'/',url,'</loc>');
						SET currentLanguagesInner = languages;
						WHILE ( LENGTH(currentLanguagesInner) > 0 ) DO
							SET currentLangInner = SUBSTRING_INDEX(currentLanguagesInner,',',1);
							SET currentLanguagesInner = RIGHT( currentLanguagesInner, LENGTH(currentLanguagesInner) - LENGTH(currentLangInner) - 1 ); 
							SET txt = CONCAT(txt,'<xhtml:link rel="alternate" hreflang="',currentLangInner,'" href="',in_domainName,currentLangInner,'/',url,'"/>');
						END WHILE;
                SET txt = CONCAT(txt,'</url>');
            END WHILE;
            
            
        END IF;
    END LOOP;
    CLOSE curs;
    SET txt = CONCAT(txt,'</urlset>');
    
    /* Write to Object table */
    START TRANSACTION;
    INSERT INTO BW_Sitemap (Site,URL,Category,TemplateMain,TemplateSub,LastModify,`Status`) VALUES (in_site,'sitemap.xml','SEO','object','blob',CURRENT_TIMESTAMP,'S')
		ON DUPLICATE KEY UPDATE LastModify = CURRENT_TIMESTAMP;
    INSERT INTO BW_Object (Site,URL,MIME,Title,`Binary`) VALUES (in_site,'sitemap.xml','application/xml','Sitemap XML',txt)
		ON DUPLICATE KEY UPDATE `Binary` = txt;
    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Event_generateSitemapXMLManager` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Event_generateSitemapXMLManager`()
BEGIN
	DECLARE done		INT DEFAULT FALSE;
    DECLARE sitename	VARCHAR(45);
    DECLARE domain		VARCHAR(4096);
    
    DECLARE curs CURSOR FOR SELECT Site, `Value` FROM BW_Config WHERE `Key` = 'GenSitemapXML';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
	OPEN curs;
    
    sitemapLoop: LOOP
		FETCH curs INTO sitename, domain;
        IF done THEN
			LEAVE sitemapLoop;
		END IF;
		CALL Event_generateSitemapXML(sitename,domain);
    END LOOP;
    CLOSE curs;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Object_get` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Object_get`(
	IN in_site		VARCHAR(45),
    IN in_url		VARCHAR(255)
)
BEGIN
	SELECT * FROM BW_Object WHERE (Site = in_site OR Site = '@ALL') AND URL = in_url;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Session_bind` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Session_bind`(
	IN in_sessionid	CHAR(64),
	IN in_username VARCHAR(16)
)
BEGIN
	UPDATE BW_Session SET Username = in_username WHERE SessionID = in_sessionid AND `Expire` = 0 LIMIT 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Session_expire` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Session_expire`(
	IN in_sessionid	CHAR(64)
)
BEGIN
	UPDATE BW_Session SET `Expire` = 1 WHERE SessionID = in_sessionid;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Session_get` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Session_get`(
	IN in_sessionid	CHAR(64)
)
BEGIN
	SELECT * FROM BW_Session WHERE SessionID = in_sessionid AND `Expire` = 0 LIMIT 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Session_new` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Session_new`()
BEGIN
	/*
	BW_Session table restriction: PK are SessionID and CreateTime. The table contains all active sessions and expired old sesions.
	Application logic restriction: Only one active session for each SessionID.
	*/
	
	DECLARE sid CHAR(64) DEFAULT TO_BASE64(RANDOM_BYTES(48));
	DECLARE jsk CHAR(64) DEFAULT TO_BASE64(RANDOM_BYTES(48));
	DECLARE slt CHAR(64) DEFAULT TO_BASE64(RANDOM_BYTES(48));
	
	DECLARE ok TINYINT;
	
	/* Avoid PK conflic: Which should not happen, just in case a record is insert just after COUNT and before INSERT */
	DECLARE CONTINUE HANDLER FOR 1062 BEGIN
		SET ok = 0;
		SET sid = TO_BASE64(RANDOM_BYTES(48));
	END;
	
	REPEAT
		SET ok = 1;
		
		/* Application logic restriction: Multiple active session with the same SID */
		IF ( SELECT COUNT(NULL) FROM BW_Session WHERE SessionID = sid AND `Expire` = 0) THEN
			SET ok = 0;
			SET sid = TO_BASE64(RANDOM_BYTES(48));
			
		ELSE
			INSERT INTO BW_Session (SessionID,JSKey,Salt) VALUES (sid,jsk,slt);
			
		END IF;
		
	UNTIL ok = 1 END REPEAT;
	
	SELECT * FROM BW_Session WHERE SessionID = sid AND `Expire` = 0 LIMIT 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Session_renew` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Session_renew`(
	IN in_sessionid	CHAR(64)
)
BEGIN
	UPDATE BW_Session SET LastUsed = CURRENT_TIMESTAMP WHERE SessionID = in_sessionid AND `Expire` = 0;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Session_unbind` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Session_unbind`(
	IN in_sessionid	CHAR(64)
)
BEGIN
	UPDATE BW_Session SET Username = NULL WHERE SessionID = in_sessionid AND `Expire` = 0 LIMIT 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Sitemap_create` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Sitemap_create`(
	IN in_site			VARCHAR(45),
    IN in_url			VARCHAR(255),
    IN in_category		VARCHAR(45),
    In in_templatemain	VARCHAR(45),
    In in_templatesub	VARCHAR(45),
    IN in_author		VARCHAR(16),
    IN in_copyright		VARCHAR(255),
    IN in_status		CHAR(1),
    IN in_info			JSON
)
BEGIN
	INSERT INTO BW_Sitemap (Site,URL,Category,TemplateMain,TemplateSub,Author,Copyright,`Status`,Info)
    VALUES (in_site, in_url, in_category, in_templatemain, in_templatesub, in_author, in_copyright, in_status, in_info);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Sitemap_get` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Sitemap_get`(
	IN in_site		VARCHAR(45),
	IN in_url		VARCHAR(255),
	IN in_category	VARCHAR(255),
	IN in_status	VARCHAR(255)
)
BEGIN
	SELECT * FROM BW_Sitemap WHERE
		(in_site IS NULL OR Site = in_site OR Site = '@ALL') AND
        (in_url IS NULL OR URL LIKE in_url) AND
        (in_category IS NULL OR FIND_IN_SET(Category,in_category) ) AND
        (in_status IS NULL OR FIND_IN_SET(`Status`,in_status) )
	;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Sitemap_getRecentWebpageIndex` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Sitemap_getRecentWebpageIndex`(
	IN in_site		VARCHAR(45),
	IN in_category	VARCHAR(255),
	IN in_size		INT,
    In in_offset	INT
)
BEGIN
	SELECT
		Web.URL,
        Web.`Language`,
		Web.Title,
		Map.Category,
		Web.Keywords,
		Web.Description,
		Map.Author,
		Map.LastModify,
		Map.`Status`,
		Map.`Info`->>'$.poster' AS Poster
	/* Step 2 - Get page index from all language those are recent */
	FROM BW_Webpage Web RIGHT JOIN BW_Sitemap Map
    ON (Web.Site = Map.Site AND Web.URL = Map.URL)
    WHERE (Web.Site = in_site OR Web.Site = '@ALL') AND Web.URL IN (
		SELECT URL FROM (
			/* Step 1 - Get recent pages from BW_Sitemap */
			SELECT DISTINCT S.URL, S.LastModify
            FROM BW_Sitemap S RIGHT JOIN BW_Webpage W
            ON (S.Site = W.Site AND S.URL = W.URL)
            WHERE
				(S.Site = in_site OR S.Site = '@ALL') AND
				S.`Status` NOT IN ('A','P') AND
				FIND_IN_SET(S.Category,in_category)
			ORDER BY S.LastModify DESC
            LIMIT in_size OFFSET in_offset
		) AS Site
	)
    ORDER BY Map.LastModify DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Sitemap_modify` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Sitemap_modify`(
	IN in_site			VARCHAR(45),
    IN in_url			VARCHAR(255),
    IN in_category		VARCHAR(45),
    In in_templatemain	VARCHAR(45),
    In in_templatesub	VARCHAR(45),
    IN in_author		VARCHAR(16),
    IN in_copyright		VARCHAR(255),
    IN in_status		CHAR(1),
    IN in_info			JSON
)
BEGIN
	UPDATE BW_Sitemap SET
		Category		= IFNULL(in_category,Category),
        TemplateMain	= IFNULL(in_templatemain,TemplateMain),
        TemplateSub		= IFNULL(in_templatesub,TemplateSub),
        Author			= IFNULL(in_author,Author),
        LastModify		= CURRENT_TIMESTAMP,
        Copyright		= IFNULL(in_copyright,Copyright),
        `Status`		= IFNULL(in_status,`Status`),
        `Info`			= IFNULL(in_info,`Info`)
    WHERE Site = in_site AND URL = in_url;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Transaction_bindClientInfo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Transaction_bindClientInfo`(
	IN in_recordid		INT,
	IN in_sessionid		CHAR(64),
	IN in_username		VARCHAR(16),
	IN in_ip			VARCHAR(15)
)
BEGIN
	UPDATE BW_Transaction SET
		SessionID = in_sessionid,
        Username = in_username,
        IP = in_ip
	WHERE RecordID = in_recordid;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Transaction_bindPageInfo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Transaction_bindPageInfo`(
	IN in_recordid		INT,
	IN in_url			VARCHAR(255)
)
BEGIN
	UPDATE BW_Transaction SET URL = in_url WHERE RecordID = in_recordid;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Transaction_bindStatisticInfo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Transaction_bindStatisticInfo`(
	IN in_recordid		INT,
	IN in_executiontime	DECIMAL(10,2),
    IN in_status		CHAR(3)
)
BEGIN
	UPDATE BW_Transaction SET ExecutionTime = in_executiontime, `Status` = in_status WHERE RecordID = in_recordid;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Transaction_new` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Transaction_new`(
	IN in_transactionid		VARCHAR(255)
)
BEGIN
	INSERT INTO BW_Transaction (TransactionID) VALUES (in_transactionid);
	SELECT * FROM BW_Transaction WHERE RecordID = LAST_INSERT_ID();
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `User_active` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `User_active`(
	IN in_username	CHAR(16)
)
BEGIN
	UPDATE BW_User SET LastActiveTime = CURRENT_TIMESTAMP WHERE Username = in_username;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `User_get` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `User_get`(
	IN in_username	CHAR(16)
)
BEGIN
	SELECT * FROM BW_User WHERE Username = in_username;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `User_modify` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `User_modify`(
	IN in_username	CHAR(16),
    IN in_nickname	CHAR(16),
    IN in_group		VARCHAR(255),
    IN in_password	CHAR(32),
    IN in_email		VARCHAR(128),
    IN in_data		LONGTEXT,
    IN in_photo		BLOB
)
BEGIN
	UPDATE BW_User SET
		Nickname	= IFNULL(in_nickname,Nickname),
		`Group`		= IFNULL(in_group,`Group`),
		`Password`	= IFNULL(in_password,`Password`),
		Email		= IFNULL(in_email,Email),
		`Data`		= IFNULL(in_data,`Data`),
		Photo		= IFNULL(in_photo,Photo)
	WHERE Username = in_username;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `User_new` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `User_new`(
	IN in_username	CHAR(16),
	IN in_nickname	CHAR(16),
	IN in_password	CHAR(32),
	IN in_ip		CHAR(15)
)
BEGIN
	INSERT INTO BW_User (Username,Nickname,`Password`,RegisterIP) VALUES (in_username,in_nickname,in_password,in_ip);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `User_works` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `User_works`(
	IN in_username	CHAR(16),
    IN in_site		VARCHAR(45)
)
BEGIN
	SELECT S.Site,S.URL, S.Category, S.CreateTime, S.LastModify, S.`Status`, (SELECT GROUP_CONCAT(DISTINCT W.Title SEPARATOR '/') FROM BW_Webpage W WHERE W.Site=S.Site AND W.URL=S.URL) AS Title
    FROM BW_Sitemap S
    WHERE (in_site IS NULL OR Site = in_site) AND (Author = in_username)
    ORDER BY Site, URL;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Webpage_createModify` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Webpage_createModify`(
	IN in_site			VARCHAR(45),
    IN in_url			VARCHAR(255),
    IN in_language		VARCHAR(5),
    IN in_title			VARCHAR(255),
    IN in_keywords		VARCHAR(255),
    IN in_description	VARCHAR(4096),
    IN in_content		LONGTEXT,
    IN in_source		LONGTEXT,
    IN in_style			VARCHAR(45)
)
BEGIN
	INSERT INTO BW_Webpage (Site, URL, `Language`, Title, Keywords,` Description`, Content, `Source`, Style)
	VALUES (in_site, in_url, in_language, in_title, in_keywords, in_description, in_content, in_source, in_style)
    ON DUPLICATE KEY UPDATE
		Title			= in_title,
        Keywords		= in_keywords,
        `Description`	= in_description,
        Content			= in_content,
        `Source`		= in_source,
        Style			= in_style;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Webpage_get` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Webpage_get`(
	IN in_site		VARCHAR(45),
    IN in_url		VARCHAR(255),
    IN in_language	VARCHAR(5)
)
BEGIN
	SELECT * FROM BW_Webpage
    WHERE
		(Site = in_site OR Site = '@ALL') AND
		URL = in_url AND
        (in_language IS NULL OR `Language` = in_language);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `Webpage_getLanguageIndex` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `Webpage_getLanguageIndex`(
	IN in_site	VARCHAR(45),
    IN in_url	VARCHAR(255)
)
BEGIN
	SELECT `Language` FROM BW_Webpage WHERE (Site = in_site OR Site = '@ALL') AND URL = in_url;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-07-12  9:03:43
