DROP PROCEDURE IF EXISTS `log_arch_hr_proc`; 
DROP PROCEDURE IF EXISTS `log_arch_qrthr_proc`;
DROP PROCEDURE IF EXISTS `log_arch_daily_proc`; 
DROP PROCEDURE IF EXISTS `manage_logs_partitions`;
DROP PROCEDURE IF EXISTS `debug`;
DROP FUNCTION IF EXISTS `get_current_date`;

DELIMITER $$

-- ===============================================================================================

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_arch_hr_proc`()
BEGIN
	DECLARE yesterday varchar(20) DEFAULT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY), '%Y%m%d');
	DECLARE hmax bigint DEFAULT 0;      
	DECLARE arch_hour_start int DEFAULT hour(now())-1;
	DECLARE arch_hour_stop int DEFAULT hour(now());


       SELECT min(id)-1 into hmax FROM logs WHERE (fo>=date_add(date(curdate()),interval arch_hour_stop hour));

  	
	if (arch_hour_start < 0) then select 23 into arch_hour_start;
	end if;

       SET @s = CONCAT('CREATE OR REPLACE VIEW log_arch_hr_',arch_hour_start,' AS SELECT * FROM logs where fo>="',date_add(date(curdate()),interval arch_hour_start hour),'" AND fo<"',date_add(date(curdate()),interval arch_hour_stop hour),'"');
       PREPARE stmt FROM @s;
       EXECUTE stmt;
       DEALLOCATE PREPARE stmt;


	if hmax is NULL then
        select min_id-1 into hmax from view_limits where view_name=concat('log_arch_hr_',arch_hour_start);
	end if;

       insert into view_limits (view_name, max_id) values (concat('log_arch_hr_',arch_hour_start), greatest(hmax,1)) on duplicate key update max_id=greatest(hmax, min_id);
       insert into view_limits (view_name, min_id) values (concat('log_arch_hr_',arch_hour_stop), hmax+1) on duplicate key update min_id=hmax+1;


	  delete from sph_counter WHERE counter_id=3;
       
       if arch_hour_start<23 then
         INSERT INTO sph_counter (counter_id,max_id,index_name) VALUES (3,1,CONCAT('log_arch_hr_',arch_hour_start));
       end if;  
       
    
     if hmax>(select max_id from sph_counter WHERE index_name='idx_logs') then UPDATE sph_counter set max_id=hmax WHERE index_name='idx_logs';
     end if;

END$$

-- ===============================================================================================

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_arch_qrthr_proc`()
BEGIN
	DECLARE cur_start datetime DEFAULT from_unixtime((unix_timestamp(now()) div (60*15)) * (60*15) - (60*15));
	DECLARE cur_stop datetime DEFAULT from_unixtime((unix_timestamp(now()) div (60*15)) * (60*15));
	DECLARE cur_step int DEFAULT 0;
    DECLARE next_step int DEFAULT 0;
	DECLARE hmax bigint DEFAULT 0;  
	DECLARE cur_counter int DEFAULT 5;

	SET @cur_minute = extract(minute from curtime());

    SELECT min(id)-1 into hmax FROM logs WHERE fo>=cur_stop;

	SELECT if(@cur_minute>=15,if(@cur_minute>=30,if(@cur_minute>=45,45,30),15),0) into cur_step;
	SELECT if(@cur_minute>=15,if(@cur_minute>=30,if(@cur_minute>=45,0,45),30),15) into next_step;

	SELECT if(@cur_minute>=15,if(@cur_minute>=30,if(@cur_minute>=45,8,7),6),5) into cur_counter;

    SET @s = CONCAT('CREATE OR REPLACE VIEW log_arch_qrhr_',cur_step,' AS SELECT * FROM logs where fo>="',cur_start,'" AND fo<"',cur_stop,'"');
       PREPARE stmt FROM @s;
       EXECUTE stmt;
       DEALLOCATE PREPARE stmt;

	if hmax is NULL then
        select min_id-1 into hmax from view_limits where view_name=concat('log_arch_qrhr_',cur_step);
	end if;
 
       insert into view_limits (view_name, max_id) values (concat('log_arch_qrhr_',cur_step), greatest(hmax,1)) on duplicate key update max_id=greatest(min_id,hmax);
       insert into view_limits (view_name, min_id) values (concat('log_arch_qrhr_',next_step), hmax+1) on duplicate key update min_id=hmax+1;
 

       DELETE from sph_counter WHERE counter_id=cur_counter;
       
       if cur_step>0 then
         REPLACE INTO sph_counter(counter_id,max_id,index_name) VALUES (cur_counter,1, concat('log_arch_qrhr_',cur_step));
       end if; 
   
     if hmax>(select max_id from sph_counter WHERE index_name='idx_logs') then 
          UPDATE sph_counter set max_id=hmax WHERE index_name='idx_logs';
     end if;
END$$

-- ===============================================================================================

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_arch_daily_proc`()
BEGIN

	DECLARE hmax bigint DEFAULT 0;   

	set @yesterday = DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY), '%Y%m%d');
        set @today = DATE_FORMAT(CURDATE(), '%Y%m%d');
	set @y_start = DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY), '%Y-%m-%d 00:00:00');
	set @y_stop = DATE_FORMAT(CURDATE(), '%Y-%m-%d 00:00:00');

   SELECT min(id)-1 into hmax FROM logs WHERE (fo>=@y_stop);


	SET @s =
		CONCAT('CREATE OR REPLACE VIEW log_arch_day_',@yesterday,' AS SELECT * FROM logs where fo>="',@y_start,'" AND fo<"',@y_stop,'"');
	PREPARE stmt FROM @s;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;

	if hmax is NULL then
        select min_id-1 into hmax from view_limits where view_name=concat('log_arch_day_',@yesterday);
	end if;

    insert into view_limits (view_name, max_id) values (concat('log_arch_day_',@yesterday), greatest(hmax,1)) on duplicate key update max_id=greatest(hmax, min_id);
    insert into view_limits (view_name, min_id) values (concat('log_arch_day_',@today), hmax+1) on duplicate key update min_id=hmax+1;


	delete from sph_counter WHERE counter_id=4;
	INSERT INTO sph_counter (counter_id,max_id,index_name) VALUES (4,1,CONCAT('log_arch_day_',@yesterday));
END$$

-- ===============================================================================================

-- This should be called at least once a week, but better to call it every night
-- It creates new partitions for 10 following days - of course only if they are not 
-- created yet. It also checks if table 'logs' has partitioning set at all - and if
-- not, then proper 'alter table' is performed to add partitioning.
-- 
-- It is safe to call this procedure many times, as it checks for existing partitions 
-- before creating new ones - so it can be call during installation or during tests.
CREATE DEFINER=`root`@`localhost` PROCEDURE `manage_logs_partitions`()
BEGIN

    DECLARE max_part_name, part_name varchar(20);
    DECLARE date_from, date_to, d date;
    DECLARE days int;
    DECLARE part_list, part_def varchar(1024);

    SELECT max(partition_name) 
    INTO max_part_name
    FROM information_schema.partitions
    WHERE table_name = 'logs' AND table_schema = database();

    IF isnull(max_part_name) THEN
        SET date_from = get_current_date();
    ELSE
        SET date_from = greatest( get_current_date(), 
            str_to_date(max_part_name, 'p%Y%m%d' ) + interval 1 day );
    END IF;

    SET date_to = get_current_date() + INTERVAL 9 day;

    -- call debug( concat('date_from=', date_from, ', date_to=', date_to ) ); 

    SET d = date_from;
    WHILE d <= date_to DO
        SET part_name = date_format( d, 'p%Y%m%d' );
        SET days = to_days(d);
        SET part_def = concat( 'PARTITION ', part_name, ' VALUES LESS THAN (', days, ')' );
        IF isnull(max_part_name) THEN
            SET part_list = concat_ws( ',', part_list, part_def );
        ELSE
            SET @sql = concat( 'ALTER TABLE logs ADD PARTITION ( ', part_def, ' )' );
            -- call debug( concat( 'DOING stmt=[', @sql, ']' ) );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
        SET d = d + interval 1 day;
    END WHILE;
            
    IF ! isnull(part_list) THEN
        SET @sql = concat( 'ALTER TABLE logs PARTITION BY RANGE ( TO_DAYS(fo) ) ',
            '( ', part_list, ' )' );
        -- call debug( concat( 'DOING stmt=[', @sql, ']' ) );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;

END$$

-- ===============================================================================================

-- Helper for debugging, adds given message to the session variable, which can be later on
-- examined in perl code and displayed to the developer
CREATE PROCEDURE debug( msg varchar(2560) )
BEGIN
    SET @debug_msg = concat( @debug_msg, msg, '\n' );
END$$

-- This is used by tests to mock current date - if session variable is set (by test), 
-- then value of this variable is used - while on production (when this variable is not set)
-- it returns standard current_date() value.
CREATE FUNCTION get_current_date() RETURNS date
BEGIN
    RETURN coalesce( @test_current_date, current_date() );
END$$

-- ===============================================================================================

DELIMITER ;
