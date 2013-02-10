-- MySQL dump 10.13  Distrib 5.5.28, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: syslog
-- ------------------------------------------------------
-- Server version	5.5.28-0ubuntu0.12.04.3

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
-- Table structure for table `view_limits`
--

DROP TABLE IF EXISTS `view_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `view_limits` (
  `idview_limits` int(11) NOT NULL AUTO_INCREMENT,
  `view_name` varchar(45) NOT NULL DEFAULT '',
  `min_id` bigint(20) unsigned NOT NULL DEFAULT '1',
  `max_id` bigint(20) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`idview_limits`),
  UNIQUE KEY `view_name_UNIQUE` (`view_name`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `view_limits`
--

LOCK TABLES `view_limits` WRITE;
/*!40000 ALTER TABLE `view_limits` DISABLE KEYS */;
INSERT INTO `view_limits` VALUES (1,'log_arch_hr_0',1,1),(2,'log_arch_hr_1',1,1),(3,'log_arch_hr_2',1,1),(4,'log_arch_hr_3',1,1),(5,'log_arch_hr_4',1,1),(6,'log_arch_hr_5',1,1),(7,'log_arch_hr_6',1,1),(8,'log_arch_qrhr_0',1,1),(9,'log_arch_qrhr_15',1,1),(10,'log_arch_qrhr_30',1,1),(11,'log_arch_qrhr_45',1,1),(12,'log_arch_hr_7',1,1),(13,'log_arch_hr_8',1,1),(14,'log_arch_hr_9',1,1),(15,'log_arch_hr_10',1,1),(16,'log_arch_hr_11',1,1),(17,'log_arch_hr_12',1,1),(18,'log_arch_hr_13',1,1),(19,'log_arch_hr_14',1,1),(20,'log_arch_hr_15',1,1),(21,'log_arch_hr_16',1,1),(22,'log_arch_hr_17',1,1),(23,'log_arch_hr_18',1,1),(24,'log_arch_hr_19',1,1),(25,'log_arch_hr_20',1,1),(26,'log_arch_hr_21',1,1),(27,'log_arch_hr_22',1,1),(28,'log_arch_hr_23',1,1);
/*!40000 ALTER TABLE `view_limits` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;


/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-01-07 17:32:38
