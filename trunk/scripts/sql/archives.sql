
--
-- Table structure for table `archives`
--

DROP TABLE IF EXISTS `archives`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;	
CREATE TABLE `archives` (
  `id` int(11)  unsigned NOT NULL AUTO_INCREMENT,
  `archive` varchar(32) NOT NULL,
  `records` int(11),
  `note` varchar(30),
  PRIMARY KEY (`id`) )
ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;


