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
