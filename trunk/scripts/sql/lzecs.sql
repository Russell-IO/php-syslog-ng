-- MySQL dump 10.13  Distrib 5.1.31, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: syslog
-- ------------------------------------------------------
-- Server version	5.1.31-1ubuntu2-log

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
-- Table structure for table `lzecs`
--

DROP TABLE IF EXISTS `lzecs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `lzecs`
--

LOCK TABLES `lzecs` WRITE;
/*!40000 ALTER TABLE `lzecs` DISABLE KEYS */;
INSERT INTO `lzecs` VALUES (1,'5','No','No',0,0,0,'Cisco','Hardware','SYS','5','CONFIG_I','Configuration','SYS-5-CONFIG_I','.*%(.*?):.*','[Cc]onfigured from (\\S+) by (\\S+)','%SYS-5-CONFIG_I: Configured from $1 by $2','The router configuration has been changed.','This is a notification message only. No action is required.','2010-03-07 20:04:05','2010-03-07 20:04:12'),(5,'7','No','No',0,0,0,'Balabit','Software','','7','','Information Only','syslog-ng','.*(syslog-ng).*','(Log statistics.*)','syslog-ng[28340]: Log statistics; dropped=pipe(/dev/xconsole)=2144450\\\\','These are syslog-ng statistics','This message is for informational purposes only, no further action is required.','2010-03-29 13:55:57','2010-03-29 13:56:00');
/*!40000 ALTER TABLE `lzecs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-03-30  1:36:06
