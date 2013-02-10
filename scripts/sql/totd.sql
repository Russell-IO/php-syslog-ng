-- MySQL dump 10.13  Distrib 5.1.41, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: syslog
-- ------------------------------------------------------
-- Server version	5.1.41-3ubuntu12.8

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
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `totd`
--

LOCK TABLES `totd` WRITE;
/*!40000 ALTER TABLE `totd` DISABLE KEYS */;
INSERT INTO `totd` VALUES (1,1,'Portlets','All portlets can be rearranged to your liking<br>Their positions are saved when you logout.','2010-03-28 23:20:01'),(7,3,'Themes','You can select a different look and feel for the pages by selecting \"Switch Theme\" from the top right.','2010-03-29 19:23:52'),(8,4,'Help','Each portlet contains a context-sensitive help icon, try clicking on one of the question marks.','2010-03-29 16:28:43'),(9,5,'Status Messages','Certain actions will trigger a status messages in the bottom right corner of the page.','2010-03-29 19:05:13'),(10,6,'Menu','You can add additional menu items by editing the navmenu.php file.','2010-03-29 14:00:43'),(11,7,'Portlets','Each portlet is managed through the database table called \"ui_layout\".<br>You can add new portlets simply by adding the appropriate information to the ui_layout table!','2010-03-29 19:18:49'),(15,10,'Tip of the Day','You can disable these messages permanently by setting totd=\'hide\' in the users table, or by adjusting the TOOLTIP_DISABLE_GLOBAL setting in the Server Settings page.','2010-03-29 18:16:22'),(17,11,'Tip of the Day','You can add new tips like this one by editing the \"totd\" table in the database.','2011-02-16 23:04:09'),(18,12,'Help','Context-sensitive help text can be modified in the \"help\" database table.','2010-03-29 19:04:04'),(19,13,'Tip of the Day','These tips will not be repeated more than once per hour.<br>You can change this setting by altering the TOOLTIP_REPEAT setting in the Server Settings.','2010-03-29 16:01:30'),(20,14,'Known Bugs','You can get a list of the currently known bugs and Todo\'s by selecting Bugs/Todo from the menu (admin access only).','2011-02-16 23:04:04'),(22,16,'Table Tab','You can add notes to individual syslog messages by selecting the pencil icon.','2010-03-29 18:45:41'),(23,17,'Table Tab','You can filter by message severity when viewing the results page.','2011-02-16 23:04:07'),(24,18,'Table Tab','You can export search results to Excel, CSV, or PDF by clicking the Export button on the results page.','2010-03-29 19:22:17'),(25,19,'History','You can save your search by clicking the \"Disk\" icon in the results page portlet header.','2010-03-29 17:17:36');
/*!40000 ALTER TABLE `totd` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-02-16 23:07:04
