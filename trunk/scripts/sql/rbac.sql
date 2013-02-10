-- MySQL dump 10.13  Distrib 5.1.58, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: syslog
-- ------------------------------------------------------
-- Server version	5.1.58

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
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbac`
--

LOCK TABLES `rbac` WRITE;
/*!40000 ALTER TABLE `rbac` DISABLE KEYS */;
INSERT INTO `rbac` VALUES (1,0,'New Collected Host'),(2,1,'RBAC Group 01'),(3,2,'RBAC Group 02'),(4,3,'RBAC Group 03'),(5,4,'RBAC Group 04'),(6,5,'RBAC Group 05'),(7,6,'RBAC Group 06'),(8,7,'RBAC Group 07'),(9,8,'RBAC Group 08'),(10,9,'RBAC Group 09'),(11,10,'RBAC Group 10'),(12,11,'RBAC Group 11'),(13,12,'RBAC Group 12'),(14,13,'RBAC Group 13'),(15,14,'RBAC Group 14'),(16,15,'RBAC Group 15'),(17,16,'RBAC Group 16'),(18,17,'RBAC Group 17'),(19,18,'RBAC Group 18'),(20,19,'RBAC Group 19'),(21,20,'RBAC Group 20'),(22,21,'RBAC Group 21'),(23,22,'RBAC Group 22'),(24,23,'RBAC Group 23'),(25,24,'RBAC Group 24'),(26,25,'RBAC Group 25'),(27,26,'RBAC Group 26'),(28,27,'RBAC Group 27'),(29,28,'RBAC Group 28'),(30,29,'RBAC Group 29'),(31,30,'RBAC Group 30'),(32,31,'RBAC Group 31');
/*!40000 ALTER TABLE `rbac` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


--
-- Dumping routines for database 'honzik'
--
/*!50003 DROP FUNCTION IF EXISTS `rbac` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50020 DEFINER=`root`@`localhost`*/ /*!50003 FUNCTION `rbac`(have decimal(11,0), should decimal(11,0)) RETURNS tinyint(1) return ((have&should)/should)=1 */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

-- Dump completed on 2011-10-14 17:51:13
