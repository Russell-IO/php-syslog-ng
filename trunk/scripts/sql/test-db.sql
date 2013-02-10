-- MySQL dump 10.13  Distrib 5.5.24, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: logzilla
-- ------------------------------------------------------
-- Server version	5.5.24-6

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
-- Table structure for table `archives`
--

DROP TABLE IF EXISTS `archives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archives` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `archive` varchar(32) NOT NULL,
  `records` int(11) DEFAULT NULL,
  `note` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banned_ips`
--

DROP TABLE IF EXISTS `banned_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banned_ips` (
  `bannedIp` varchar(15) NOT NULL,
  `expirationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bannedIp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `name` varchar(512) NOT NULL,
  `value` bigint(20) unsigned NOT NULL,
  `updatetime` datetime NOT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY `name_value` (`name`,`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `code` enum('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','100','101','102','103') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crc_id` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `groupname` varchar(15) NOT NULL DEFAULT 'users',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_group` (`userid`,`groupname`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `help`
--

DROP TABLE IF EXISTS `help`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help` (
  `id` tinyint(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` tinyint(3) unsigned NOT NULL,
  `urlname` varchar(20) NOT NULL,
  `url` varchar(2000) NOT NULL,
  `spanid` varchar(20) NOT NULL,
  `lastupdate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hosts`
--

DROP TABLE IF EXISTS `hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hosts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(128) NOT NULL,
  `lastseen` datetime NOT NULL,
  `seen` int(10) unsigned NOT NULL DEFAULT '1',
  `rbac_key` int(10) unsigned NOT NULL DEFAULT '1',
  `hidden` enum('false','true') DEFAULT 'false',
  PRIMARY KEY (`id`),
  UNIQUE KEY `host` (`host`),
  KEY `rbac` (`rbac_key`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(128) NOT NULL,
  `facility` enum('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23') NOT NULL,
  `severity` enum('0','1','2','3','4','5','6','7') NOT NULL,
  `program` int(10) unsigned NOT NULL,
  `msg` varchar(2048) NOT NULL,
  `mne` int(10) unsigned NOT NULL,
  `eid` int(10) unsigned NOT NULL DEFAULT '0',
  `suppress` datetime NOT NULL DEFAULT '2010-03-01 00:00:00',
  `counter` int(11) NOT NULL DEFAULT '1',
  `fo` datetime NOT NULL,
  `lo` datetime NOT NULL,
  `notes` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`fo`),
--  KEY `facility` (`facility`),
--  KEY `severity` (`severity`),
--  KEY `host` (`host`),
--  KEY `mne` (`mne`),
--  KEY `eid` (`eid`),
--  KEY `program` (`program`),
--  KEY `suppress` (`suppress`),
  KEY `lo` (`lo`),
  KEY `fo` (`fo`) USING BTREE
--  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lzecs`
--

DROP TABLE IF EXISTS `lzecs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lzecs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `psr` enum('7','6','5','4','3','2','1','0') NOT NULL,
  `si` enum('No','Yes') NOT NULL,
  `suppress` enum('No','Yes') NOT NULL,
  `trig_amt` int(10) unsigned NOT NULL,
  `trig_win` int(10) unsigned NOT NULL,
  `pairwith` bigint(20) unsigned NOT NULL,
  `vendor` varchar(50) NOT NULL,
  `type` enum('Software','Hardware') NOT NULL,
  `fac` varchar(20) NOT NULL,
  `sev` enum('7','6','5','4','3','2','1','0') NOT NULL,
  `mne` varchar(20) NOT NULL,
  `class` enum('Fault','Configuration','Accounting','Performance','Security','Information Only') NOT NULL,
  `name` varchar(255) NOT NULL,
  `preg_name` varchar(255) NOT NULL,
  `preg_msg` varchar(255) NOT NULL,
  `msg_sample` varchar(2048) NOT NULL,
  `explanation` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `add_date` datetime NOT NULL,
  `lastupdate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `fac_sev_mne` (`fac`,`sev`,`mne`),
  KEY `vendor` (`vendor`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mne`
--

DROP TABLE IF EXISTS `mne`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mne` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `crc` int(10) unsigned NOT NULL,
  `seen` int(10) unsigned NOT NULL DEFAULT '1',
  `lastseen` datetime NOT NULL,
  `hidden` enum('false','true') DEFAULT 'false',
  PRIMARY KEY (`id`),
  UNIQUE KEY `crc_id` (`crc`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `crc` int(10) unsigned NOT NULL,
  `seen` int(10) unsigned NOT NULL DEFAULT '1',
  `lastseen` datetime NOT NULL,
  `hidden` enum('false','true') DEFAULT 'false',
  PRIMARY KEY (`id`),
  UNIQUE KEY `crc_id` (`crc`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rbac`
--

DROP TABLE IF EXISTS `rbac`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbac` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbac_bit` int(10) unsigned NOT NULL,
  `rbac_text` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbac_text` (`rbac_text`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` varchar(2047) DEFAULT NULL,
  `type` enum('enum','int','varchar') NOT NULL DEFAULT 'varchar',
  `options` varchar(125) NOT NULL,
  `default` varchar(125) NOT NULL,
  `description` text NOT NULL,
  `hide` enum('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `severities`
--

DROP TABLE IF EXISTS `severities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `severities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `code` enum('0','1','2','3','4','5','6','7') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crc_id` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `snare_eid`
--

DROP TABLE IF EXISTS `snare_eid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snare_eid` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `eid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `lastseen` datetime NOT NULL,
  `seen` int(10) unsigned NOT NULL DEFAULT '1',
  `hidden` enum('false','true') DEFAULT 'false',
  PRIMARY KEY (`id`),
  UNIQUE KEY `eid` (`eid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sph_counter`
--

DROP TABLE IF EXISTS `sph_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sph_counter` (
  `counter_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `max_id` bigint(20) unsigned NOT NULL,
  `index_name` varchar(32) NOT NULL DEFAULT '',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`counter_id`),
  KEY `index_name` (`index_name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suppress`
--

DROP TABLE IF EXISTS `suppress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppress` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `col` enum('notes','counter','msg','program','priority','facility','eid','host') NOT NULL,
  `expire` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_col` (`name`,`col`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_log`
--

DROP TABLE IF EXISTS `system_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `action` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `system_log`
        BEFORE INSERT ON system_log
        FOR EACH ROW
        BEGIN
        SET NEW.timestamp = NOW();
        END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `totd`
--

DROP TABLE IF EXISTS `totd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `totd` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `tipnum` int(3) unsigned NOT NULL,
  `name` varchar(15) NOT NULL,
  `text` varchar(512) NOT NULL,
  `lastshown` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `triggers`
--

DROP TABLE IF EXISTS `triggers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `triggers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `pattern` varchar(255) NOT NULL,
  `mailto` varchar(255) NOT NULL DEFAULT 'root@localhost',
  `mailfrom` varchar(255) NOT NULL DEFAULT 'root@localhost',
  `subject` varchar(255) NOT NULL,
  `body` text CHARACTER SET utf8 NOT NULL,
  `disabled` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ui_layout`
--

DROP TABLE IF EXISTS `ui_layout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ui_layout` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `userid` smallint(5) unsigned NOT NULL DEFAULT '1',
  `pagename` varchar(255) NOT NULL DEFAULT 'Main',
  `col` smallint(5) unsigned NOT NULL DEFAULT '1',
  `rowindex` int(9) NOT NULL DEFAULT '0',
  `header` varchar(40) NOT NULL,
  `group_access` varchar(255) NOT NULL DEFAULT 'users',
  `content` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_header` (`userid`,`header`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL,
  `pwhash` char(40) NOT NULL,
  `sessionid` char(32) NOT NULL,
  `exptime` datetime NOT NULL,
  `group` int(3) NOT NULL DEFAULT '2',
  `totd` enum('show','hide') NOT NULL DEFAULT 'show',
  `rbac_key` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rbac` (`rbac_key`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `events_per_second`;
CREATE TABLE `events_per_second` (
  `name` varchar(10) NOT NULL DEFAULT 'msg',
  `ts_from` int(10) unsigned NOT NULL,
  `count` bigint(20) unsigned NOT NULL DEFAULT 0,
  UNIQUE KEY (`name`, `ts_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TRIGGER IF EXISTS `events_per_second_insert`;
DELIMITER ;;
CREATE TRIGGER `events_per_second_insert`
        BEFORE INSERT ON events_per_second
        FOR EACH ROW
        BEGIN
            INSERT INTO events_per_minute
            SET name = NEW.name,
                ts_from = FLOOR( NEW.ts_from / 60 ) * 60,
                count = NEW.count
            ON DUPLICATE KEY 
            UPDATE count = count + NEW.count;
        END
;;
DELIMITER ;

DROP TABLE IF EXISTS `events_per_minute`;
CREATE TABLE `events_per_minute` (
  `name` varchar(10) NOT NULL DEFAULT 'msg',
  `ts_from` int(10) unsigned NOT NULL,
  `count` bigint(20) unsigned NOT NULL DEFAULT 0,
  UNIQUE KEY (`name`, `ts_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TRIGGER IF EXISTS `events_per_minute_insert`;
DELIMITER ;;
CREATE TRIGGER `events_per_minute_insert`
        BEFORE INSERT ON events_per_minute
        FOR EACH ROW
        BEGIN
            INSERT INTO events_per_hour
            SET name = NEW.name,
                ts_from = FLOOR( NEW.ts_from / 3600 ) * 3600,
                count = NEW.count
            ON DUPLICATE KEY 
            UPDATE count = count + NEW.count;
        END
;;
DELIMITER ;

DROP TABLE IF EXISTS `events_per_hour`;
CREATE TABLE `events_per_hour` (
  `name` varchar(10) NOT NULL DEFAULT 'msg',
  `ts_from` int(10) unsigned NOT NULL,
  `count` bigint(20) unsigned NOT NULL DEFAULT 0,
  UNIQUE KEY (`name`, `ts_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'ADMIN_EMAIL','pp@idea7.pl','varchar','','None','This variable sets the email address for the site Administrator','no'),
(2,'ADMIN_NAME','admin','varchar','','admin','This variable sets the user name for the site Administrator, some features of the site, such as the server configuration, will be locked out if this variable does not match the logged in user.','no'),
(3,'TBL_AUTH','users','varchar','','users','This variable sets the auth table name for local user information.','yes'),
(4,'AUTHTYPE','local','enum','local,ldap,msad,none','local','This variable is used to set the authentication method to one of the following:<br><ul><li>Local Authentication</li><li>LDAP Authentication</li><li>Microsoft AD Domain Authentication</li><li>None (No Authentication)</li></ul>','no'),
(5,'DEBUG','0','enum','0,1,2,3,4,5','0','This variable enables and disables site-wide debugging','no'),
(6,'DEDUP','0','enum','0,1','1','This variable is used to Enable or Disable Message Deduplication in the log_processor script','no'),
(7,'DEDUP_DIST','5','int','','5','This variable is used to set distance for message deduplication.<br>The higher the number, the more likely compared messages will match.','no'),
(8,'DEDUP_WINDOW','300','int','','300','If Message deduplication is enabled, this setting is used to indicate the amount of time (in seconds) to compare messages from the same host.<br>When an event arrives, messages from the same host within this time frame are compared.','no'),
(9,'DEMO','0','enum','0,1','0','This variable is used to place the server into Demo mode. This setting is only used by us on the demo website and should not normally need to be changed.','yes'),
(10,'EMDB','1','enum','0,1','1','This variable is used to enable or disable the Error Message Database.','yes'),
(11,'EMDB_TBL_CISCO','cemdb','varchar','','cemdb','If the EMDB is enabled, this table is used to retrieve Cisco Error message information.','yes'),
(12,'EXCEL_DRK','E2E4FF','varchar','','C0C0C0','This variable sets the dark row color for Excel exports.','yes'),
(13,'EXCEL_HDR','FB9E01','varchar','','96AED2','This variable sets the header row color for Excel exports.','yes'),
(14,'EXCEL_LT','FFFFFF','varchar','','E0E0E0','This variable sets the light row color for Excel exports.','yes'),
(15,'GRAPHS','1','enum','0,1','1','This variable is used to indicate whether or not the main page graphs should be shown.','yes'),
(16,'LDAP_BASE_DN','ou=active, ou=employees, ou=people, o=somewhere.com','varchar','','ou=active:ou=employees:ou','This variable sets the LDAP Base DN if LDAP is enabled.','no'),
(17,'LDAP_CN','uid','varchar','','uid','This variable is used to set the LDAP CN.','no'),
(18,'LDAP_DOMAIN','somewhere.com','varchar','','gdd.net',' LDAP Domain name','no'),
(19,'LDAP_MS','0','enum','0,1','0','This variable is used to enable MS-type LDAP authentication when LDAP is enabled.','no'),
(20,'LDAP_PRIV','0','enum','0,1','0','This setting is used to enable LDAP Authentication for read-only and read-write groups.<br>It is not yet implemented and should not be set to 1.','yes'),
(21,'LDAP_RO_FILTERS','','varchar','','None','This variable can be used to specify which hosts will be shown (or NOT shown) to the ldap_ro users. <br>Hosts should be separated by a colon (:) and may include ! (for NOT) and * for a wildcard match<br>Example:<br>192.168.*.*:!192.168.1.*<br>Would allow all hosts in the 192.168.*.* network to be viewed by the ldap_ro group, EXCLUDING the 192.168.1.* subnet.','yes'),
(22,'LDAP_RO_GRP','users','varchar','','users','This variable is used to set the LDAP read-only group name, users in this group will have limited access to the site.','yes'),
(23,'LDAP_RW_GRP','admins','varchar','','admins','This variable is used to set the LDAP read-write group name, users in this group will have full access to the site.','yes'),
(24,'LDAP_SRV','ldap.somewhere.com','varchar','','None','This variable sets the LDAP server name to use if LDAP is enabled.','no'),
(25,'MSG_EXPLODE','1','enum','0,1','1','This variable is used to enable or disable message filtering by words when they are displayed.','yes'),
(26,'PATH_BASE','/home/kompas/vworker/logzilla/logzilla','varchar','','/var/www/logzilla/html','This variable is used to set the base path of your LogZilla installation html directory, <b><u>DO NOT</u></b> include a trailing slash<br>Example: /var/www/logzilla/html','no'),
(27,'PATH_LOGS','/var/log/logzilla','varchar','','/var/log/logzilla','This variable is used to indicate which directory to store logs in.<br>Note: Be sure the directory exists!','no'),
(29,'PROGNAME','LogZilla','varchar','','LogZilla','This variable sets the internal program name and should not be changed.','yes'),
(30,'RETENTION','7','int','','30','This variable is used to determine the number of days to keep data in the database. <br>Any data older than this setting will be automatically archived and stored as a gzip in the \"exports\" directory.<br>Admins may re-import any archived data using the Import\" menu option. <br>Note that the \"Import\" menu will not show anything if there are no available archives to import.','no'),
(31,'SEQ_DISP','0','enum','0,1','0','This setting is used to enable or disable displaying of Sequence columns in search results.<br>The Sequence field is not very accurate as many systems do not use them. I will probably be getting rid of it completely in a future release.','yes'),
(32,'SESS_EXP','3600','varchar','','3600','This variable sets the default session expiration time in seconds.','no'),
(33,'SITE_NAME','LogZilla','varchar','','LogZilla','This variable sets the Website Name.','no'),
(34,'SITE_URL','/','varchar','','/','This variable is used to set the website url, including trailing slash <br>Example: /logs/','no'),
(35,'SPX_PORT','3312','varchar','','3312','This variable sets the Sphinx Server port.','no'),
(36,'SPX_SRV','127.0.0.1','varchar','','localhost','This variable sets the Sphinx Server address.','no'),
(38,'TBL_CACHE','cache','varchar','','cache','This variable is used to set the name of the cache table.','yes'),
(39,'TBL_MAIN','logs','varchar','','logs','This variable sets the name of the main table used to store log data.','yes'),
(40,'VERSION','4.0','varchar','','','This variable sets the LogZilla version number.','yes'),
(41,'TBL_ACTIONS','actions','varchar','','actions','This variable sets the name of the actions table used to store default authentication actions for local users.','yes'),
(42,'TBL_USER_ACCESS','user_access','varchar','','user_access','This variable sets the name of the user_access table used to store default access for local users.','yes'),
(55,'OPTION_HGRID_SEARCH','LIKE','enum','LIKE, RLIKE','LIKE','This variable is used to set the type of search to perform when filtering the Hosts grid.<br>Using LIKE will speed up searches on large systems<br>Using RLIKE will allow for regular expression searches.','yes'),
(44,'CISCO_MNE_PARSE','1','enum','0,1','1','This variable is used to Enable or Disable extraction of messages for Cisco-based events.<br>If enabled, all incoming messages will be reformatted to strip out the syslog mnemonic between the \'%\' and \':\' delimiters.','yes'),
(45,'SPX_MEM_LIMIT','256','int','','256','Set the Sphinx Memory limit your liking: The default is 256M<br>\r\nThe max recommended is 1024M<br>\r\n256M will process about 600k rows at a time<br>\r\nSee http://sphinxsearch.com/docs/current.html#conf-mem-limit for more information.','no'),
(46,'SPX_MAX_MATCHES','5000','int','','5000','Sets the maximum results to return on a search<br>','yes'),
(47,'CACHE_CHART_TOPHOSTS','30','int','','30','Sets the cache timeout (in minutes) for the Top Hosts chart.','no'),
(48,'CACHE_CHART_TOPMSGS','60','int','','60','Sets the cache timeout (in minutes) for the Top Messages chart.','no'),
(49,'CHART_MPD_DAYS','30','int','','30','Sets the number of days back to display on the Messages Per Day chart.','no'),
(50,'CACHE_CHART_MPH','24','int','','24','Sets the number of hours back to display on the Messages Per Hour chart.','no'),
(51,'CHART_SOW','Sun','enum','Sun,Mon','Sun','This variable is used to format the chart data on the Messages Per Week chart and is used to indicate the first day of the week for your region. <br>The options are:<ul><li>Sun</li><li>Mon</li></ul><br>','no'),
(52,'VERSION_SUB','.403','varchar','','None','Sets the sub-version number.','yes'),
(53,'CACHE_CHART_MPW','4','int','','4','Sets the number of weeks back to display on the Messages Per Week chart.','no'),
(54,'SHOWCOUNTS','1','enum','0,1','1','This variable enables the portal counts on the main page.<br>\r\nIf you have a large system (> 20m events), you may opt to disable this to increase the page load times.','no'),
(57,'PAGINATE','10','int','','10','This option sets the number of items to display on a single Search Results page.','yes'),
(58,'TOOLTIP_REPEAT','60','int','','60','This variable sets the time (in minutes) before the same tip will be repeated (tips are show during the main page load).','no'),
(59,'TOOLTIP_GLOBAL','1','enum','0,1','1','This setting will enable or disable the Main page Tips on a global level (all users).<br>To disable Tips for an individual user, please edit the \"totd\" value for that user in the \"users\" table.','no'),
(60,'LZECS_SYSID','','varchar','','','This sets the system id for your server. This option is used to submit unknown events to the LogZilla Error Classification System.','yes'),
(62,'Q_LIMIT','25000','int','','25000','This option sets the limit on the number of messages to be processed before running the batch import to the database.<br>Note that if the Q_TIME kicks in before this, it will supercede this limit.','no'),
(63,'Q_TIME','1','int','','1','This option sets the TIME limit on the messages to be processed before running the batch import to the database.<br>Note that if this timer kicks in before the Q_LIMIT, it will supercede the Q_LIMIT.<br>You should increase this number to a higher value for larger systems to improve performance.','no'),
(64,'SPX_ENABLE','1','enum','0,1','1','Deprecated. Do not modify.','yes'),
(65,'LDAP_DNU_GRP','users','varchar','','users','This option specifies the default group to place new LDAP users into when they don\'t exist locally.','no'),
(67,'SPX_ADV','0','enum','0,1','0','No longer necessary in later Sphinx code (post 0.9.9) as all searches now use extended mode.','yes'),
(68,'MAILHOST','localhost','varchar','','localhost','This option specifies the mail host to use when sending alerts.','no'),
(69,'MAILHOST_PORT','25','int','','25','This option specifies the mail host\'s post to use when sending alerts.','no'),
(70,'MAILHOST_USER','','varchar','','','This option specifies the mail host\'s username to use when sending alerts.<br>\r\nLeave this field blank if no username is necessary (like sending from localhost).','no'),
(71,'MAILHOST_PASS','','varchar','','','This option specifies the mail host\'s password to use when sending alerts.<br>\r\nLeave this field blank if no username is necessary (like sending from localhost).','no'),
(72,'PORTLET_HOSTS_LIMIT','10','int','','10','This option specifies the default number of hosts to display on the main page\'s host portlet.<br>\r\nThe list will contain only the last N hosts that have reported in (sorted by \"lastseen\" column in descending order).<br>\r\nIf there are more hosts than what is set here, you can click the \"Expand\" icon (magnifying glass icon in the top right corner of the portlet) and get a full listing.<br>\r\n<b>For large deployments where thousands of hosts are collected, this is a much more effective solution.</b>','no'),
(73,'SPARKLINES','1','enum','0,1','1','This variable is used to enable/disable the Events Per Second ticker on the main page.<br>\r\nThe EPS Ticker is the small graph-like count of the average messages per second entering the server.<br>\r\nBecause the call queries the server every second, some users on large systems may want to disable this feature.','no'),
(80,'ARCHIVE_PATH','/var/www/logzilla/exports/','varchar','','/var/www/logzilla/exports/','This variable is used to set the base path of your LogZilla backup directory','no'),
(81,'LZECS','0','enum','0,1','0','This variable is used to enable the LogZilla Error Classification System','yes'),
(82,'PORTLET_MNE_LIMIT','10','int','','10','This option specifies the default number of Mnemonics to display on the main page\'s mnemonic portlet.<br>\r\nThe list will contain only the last N hosts that have reported in (sorted by \"lastseen\" column in descending order).<br>\r\nIf there are more mnemonics than what is set here, you can click the \"Expand\" icon (magnifying glass icon in the top right corner of the portlet) and get a full listing.<br>\r\n<b>For large deployments where thousands of mnemonics are collected, this is a much more effective solution.</b>','no'),
(83,'SNARE','0','enum','0,1','0','This option will enable Snare windows events to be processed.<br>\r\nNote that after enabling Snare, you must restart your syslog daemon so that the db_insert preprocessor will pick up events.','no'),
(84,'PORTLET_EID_LIMIT','10','int','','10','This option specifies the default number of Snare EventId\'s to display on the main page\'s EID portlet.<br>\r\nThe list will contain only the last N EventId\'s that have reported in (sorted by \"lastseen\" column in descending order).<br>\r\nIf there are more EID\'s than what is set here, you can click the \"Expand\" icon (magnifying glass icon in the top right corner of the portlet) and get a full listing.<br>\r\n<b>For large deployments where thousands of EID\'s are collected, this is a much more effective solution.</b>','no'),
(85,'SNARE_EID_URL','http://eventid.net/display.asp?eventid=','enum','http://eventid.net/display.asp?eventid=,http://www.microsoft.com/technet/support/ee/SearchResults.aspx?Type=1&ID=','http://eventid.net/display.asp?eventid=','This option sets the URL to use when linking Snare/Windows Event ID\'s<br>\r\nThe default is http://eventid.net/display.asp?eventid=<br>\r\nAnother option is http://www.microsoft.com/technet/support/ee/SearchResults.aspx?Type=1&ID=<br>\r\nNote that in both cases, the actual event id is left off after the = character - it will be inserted when results are displayed.','yes'),
(86,'FEEDBACK','1','enum','0,1','0','This variable will enable or disable the \"Submit Idea\" button on the bottom right of the screen.<br>\r\nServers with no internet access should disable this.','no'),
(87,'LDAP_USERS_RO',NULL,'varchar','','','Comma separated list of user ID\'s that are allowed RO access','yes'),
(88,'LDAP_USERS_RW',NULL,'varchar','','','Comma separated list of user ID\'s that are allowed RW access','yes'),
(89,'SYSTEM_LOG_FILE','1','enum','0,1','0','Write all audit information to the $LOG_PATH/audit.log file','no'),
(90,'SYSTEM_LOG_DB','0','enum','0,1','0','Send all audit information to the LogZilla Database','no'),
(93,'SYSTEM_LOG_SYSLOG','0','enum','0,1','0','Write all audit information to syslog.','no'),
(94,'PORTLET_PROGRAMS_LIMIT','10','int','','10','This option specifies the default number of Programs to display on the main page\'s program portlet.<br>\r\nThe list will contain only the last N programs that have reported in (sorted by \"lastseen\" column in descending order).<br>\r\nIf there are more Programs than what is set here, you can click the \"Expand\" icon (magnifying glass icon in the top right corner of the portlet) and get a full listing.<br>\r\n<b>For large deployments where thousands of programs are collected, this is a much more effective solution.</b>','no'),
(95,'RETENTION_DROPS_HOSTS','0','enum','0,1','0','This option enables/disables pruning of old hosts from the database.','no'),
(96,'ARCHIVE_BACKUP','','varchar','','','Command to archive a day to another host<br>\r\n $1 is the archive name with full path w/o .gz<br>\r\nExample: scp $1.gz remotehost:/backup/.','no'),
(97,'ARCHIVE_RESTORE','','varchar','','','Command to restore a day from another host<br>\r\n $1 is the archive filename with .gz; $2 is the restore path<br>\r\nExample: scp remotehost:/backup/$1 $2','no'),
(98,'RBAC_ALLOW_DEFAULT','1','enum','0,1','1','This option specifies the default behavior when a new user is added to the system.<br>\r\nOptions are:<br>\r\n<ul>\r\n<li>0 - All new users must be given permissions (using the Admin>RBAC menu) to see newly added hosts. (implicit deny)</li>\r\n<li>1 - Allow newly created users to see all new (unassigned) hosts by default. (implicit allow)</li>\r\n</ul>\r\n','no'),
(99,'SNMP_TRAPDEST','','varchar','','BLANK (no value)','Sets a destination TRAP hosts for alerts','no'),
(100,'SNMP_COMMUNITY','public','varchar','','public','Sets the community for SNMP_TRAPHOST','no'),
(101,'SNMP_SENDTRAPS','0','enum','0,1','0','Enables trap forwarding to the host set in SNMP_TRAPHOST.<br>\r\nNote: You must have a license for Email triggers in order for this function to work.','no');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-08-18  8:02:44
