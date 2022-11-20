SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET @@SESSION.SQL_LOG_BIN= 0;
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';
SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS `sakila`;
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sakila` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
CREATE TABLE `sakila`.`actor` (
`actor_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`first_name` varchar(45) NOT NULL,
`last_name` varchar(45) NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DROP VIEW IF EXISTS `sakila`.`actor_info`;
CREATE VIEW `sakila`.`actor_info` AS SELECT
 1 AS `actor_id`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `film_info`
;
CREATE TABLE `sakila`.`address` (
`address_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`address` varchar(50) NOT NULL,
`address2` varchar(50) DEFAULT NULL,
`district` varchar(20) NOT NULL,
`city_id` smallint unsigned NOT NULL,
`postal_code` varchar(10) DEFAULT NULL,
`phone` varchar(20) NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`category` (
`category_id` tinyint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(25) NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`city` (
`city_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`city` varchar(50) NOT NULL,
`country_id` smallint unsigned NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`country` (
`country_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`country` varchar(50) NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`customer` (
`customer_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`store_id` tinyint unsigned NOT NULL,
`first_name` varchar(45) NOT NULL,
`last_name` varchar(45) NOT NULL,
`email` varchar(50) DEFAULT NULL,
`address_id` smallint unsigned NOT NULL,
`active` tinyint(1) NOT NULL DEFAULT '1',
`create_date` datetime NOT NULL,
`last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DROP VIEW IF EXISTS `sakila`.`customer_list`;
CREATE VIEW `sakila`.`customer_list` AS SELECT
 1 AS `ID`,
 1 AS `name`,
 1 AS `address`,
 1 AS `zip code`,
 1 AS `phone`,
 1 AS `city`,
 1 AS `country`,
 1 AS `notes`,
 1 AS `SID`
;
CREATE TABLE `sakila`.`film` (
`film_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(128) NOT NULL,
`description` text,
`release_year` year DEFAULT NULL,
`language_id` tinyint unsigned NOT NULL,
`original_language_id` tinyint unsigned DEFAULT NULL,
`rental_duration` tinyint unsigned NOT NULL DEFAULT '3',
`rental_rate` decimal(4,2) NOT NULL DEFAULT '4.99',
`length` smallint unsigned DEFAULT NULL,
`replacement_cost` decimal(5,2) NOT NULL DEFAULT '19.99',
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`film_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`film_actor` (
`actor_id` smallint unsigned NOT NULL,
`film_id` smallint unsigned NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`actor_id`,`film_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`film_category` (
`film_id` smallint unsigned NOT NULL,
`category_id` tinyint unsigned NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`film_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DROP VIEW IF EXISTS `sakila`.`film_list`;
CREATE VIEW `sakila`.`film_list` AS SELECT
 1 AS `FID`,
 1 AS `title`,
 1 AS `description`,
 1 AS `category`,
 1 AS `price`,
 1 AS `length`,
 1 AS `actors`
;
CREATE TABLE `sakila`.`film_text` (
`film_id` smallint NOT NULL,
`title` varchar(255) NOT NULL,
`description` text,
PRIMARY KEY (`film_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`inventory` (
`inventory_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
`film_id` smallint unsigned NOT NULL,
`store_id` tinyint unsigned NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`language` (
`language_id` tinyint unsigned NOT NULL AUTO_INCREMENT,
`name` char(20) NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DROP VIEW IF EXISTS `sakila`.`nicer_but_slower_film_list`;
CREATE VIEW `sakila`.`nicer_but_slower_film_list` AS SELECT
 1 AS `FID`,
 1 AS `title`,
 1 AS `description`,
 1 AS `category`,
 1 AS `price`,
 1 AS `length`,
 1 AS `actors`
;
CREATE TABLE `sakila`.`payment` (
`payment_id` smallint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` smallint unsigned NOT NULL,
`staff_id` tinyint unsigned NOT NULL,
`rental_id` int DEFAULT NULL,
`amount` decimal(5,2) NOT NULL,
`payment_date` datetime NOT NULL,
`last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
CREATE TABLE `sakila`.`rental` (
`rental_id` int NOT NULL AUTO_INCREMENT,
`rental_date` datetime NOT NULL,
`inventory_id` mediumint unsigned NOT NULL,
`customer_id` smallint unsigned NOT NULL,
`return_date` datetime DEFAULT NULL,
`staff_id` tinyint unsigned NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`rental_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DROP VIEW IF EXISTS `sakila`.`sales_by_film_category`;
CREATE VIEW `sakila`.`sales_by_film_category` AS SELECT
 1 AS `category`,
 1 AS `total_sales`
;
DROP VIEW IF EXISTS `sakila`.`sales_by_store`;
CREATE VIEW `sakila`.`sales_by_store` AS SELECT
 1 AS `store`,
 1 AS `manager`,
 1 AS `total_sales`
;
CREATE TABLE `sakila`.`staff` (
`staff_id` tinyint unsigned NOT NULL AUTO_INCREMENT,
`first_name` varchar(45) NOT NULL,
`last_name` varchar(45) NOT NULL,
`address_id` smallint unsigned NOT NULL,
`picture` blob,
`email` varchar(50) DEFAULT NULL,
`store_id` tinyint unsigned NOT NULL,
`active` tinyint(1) NOT NULL DEFAULT '1',
`username` varchar(16) NOT NULL,
`password` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DROP VIEW IF EXISTS `sakila`.`staff_list`;
CREATE VIEW `sakila`.`staff_list` AS SELECT
 1 AS `ID`,
 1 AS `name`,
 1 AS `address`,
 1 AS `zip code`,
 1 AS `phone`,
 1 AS `city`,
 1 AS `country`,
 1 AS `SID`
;
CREATE TABLE `sakila`.`store` (
`store_id` tinyint unsigned NOT NULL AUTO_INCREMENT,
`manager_staff_id` tinyint unsigned NOT NULL,
`address_id` smallint unsigned NOT NULL,
`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;
DELIMITER //
CREATE FUNCTION `sakila`.`get_customer_balance`(p_customer_id INT, p_effective_date DATETIME) RETURNS decimal(5,2)
    READS SQL DATA
    DETERMINISTIC
BEGIN

       
       
       
       
       
       

  DECLARE v_rentfees DECIMAL(5,2); 
  DECLARE v_overfees INTEGER;      
  DECLARE v_payments DECIMAL(5,2); 

  SELECT IFNULL(SUM(film.rental_rate),0) INTO v_rentfees
    FROM film, inventory, rental
    WHERE film.film_id = inventory.film_id
      AND inventory.inventory_id = rental.inventory_id
      AND rental.rental_date <= p_effective_date
      AND rental.customer_id = p_customer_id;

  SELECT IFNULL(SUM(IF((TO_DAYS(rental.return_date) - TO_DAYS(rental.rental_date)) > film.rental_duration,
        ((TO_DAYS(rental.return_date) - TO_DAYS(rental.rental_date)) - film.rental_duration),0)),0) INTO v_overfees
    FROM rental, inventory, film
    WHERE film.film_id = inventory.film_id
      AND inventory.inventory_id = rental.inventory_id
      AND rental.rental_date <= p_effective_date
      AND rental.customer_id = p_customer_id;


  SELECT IFNULL(SUM(payment.amount),0) INTO v_payments
    FROM payment

    WHERE payment.payment_date <= p_effective_date
    AND payment.customer_id = p_customer_id;

  RETURN v_rentfees + v_overfees - v_payments;
END//
DELIMITER ;
;
DELIMITER //
CREATE FUNCTION `sakila`.`inventory_held_by_customer`(p_inventory_id INT) RETURNS int
    READS SQL DATA
BEGIN
  DECLARE v_customer_id INT;
  DECLARE EXIT HANDLER FOR NOT FOUND RETURN NULL;

  SELECT customer_id INTO v_customer_id
  FROM rental
  WHERE return_date IS NULL
  AND inventory_id = p_inventory_id;

  RETURN v_customer_id;
END//
DELIMITER ;
;
DELIMITER //
CREATE FUNCTION `sakila`.`inventory_in_stock`(p_inventory_id INT) RETURNS tinyint(1)
    READS SQL DATA
BEGIN
    DECLARE v_rentals INT;
    DECLARE v_out     INT;

    
    

    SELECT COUNT(*) INTO v_rentals
    FROM rental
    WHERE inventory_id = p_inventory_id;

    IF v_rentals = 0 THEN
      RETURN TRUE;
    END IF;

    SELECT COUNT(rental_id) INTO v_out
    FROM inventory LEFT JOIN rental USING(inventory_id)
    WHERE inventory.inventory_id = p_inventory_id
    AND rental.return_date IS NULL;

    IF v_out > 0 THEN
      RETURN FALSE;
    ELSE
      RETURN TRUE;
    END IF;
END//
DELIMITER ;
;
DELIMITER //
CREATE PROCEDURE `sakila`.`film_in_stock`(IN p_film_id INT, IN p_store_id INT, OUT p_film_count INT)
    READS SQL DATA
BEGIN
     SELECT inventory_id
     FROM inventory
     WHERE film_id = p_film_id
     AND store_id = p_store_id
     AND inventory_in_stock(inventory_id);

     SELECT COUNT(*)
     FROM inventory
     WHERE film_id = p_film_id
     AND store_id = p_store_id
     AND inventory_in_stock(inventory_id)
     INTO p_film_count;
END//
DELIMITER ;
;
DELIMITER //
CREATE PROCEDURE `sakila`.`film_not_in_stock`(IN p_film_id INT, IN p_store_id INT, OUT p_film_count INT)
    READS SQL DATA
BEGIN
     SELECT inventory_id
     FROM inventory
     WHERE film_id = p_film_id
     AND store_id = p_store_id
     AND NOT inventory_in_stock(inventory_id);

     SELECT COUNT(*)
     FROM inventory
     WHERE film_id = p_film_id
     AND store_id = p_store_id
     AND NOT inventory_in_stock(inventory_id)
     INTO p_film_count;
END//
DELIMITER ;
;
DELIMITER //
CREATE PROCEDURE `sakila`.`rewards_report`(
    IN min_monthly_purchases TINYINT UNSIGNED
    , IN min_dollar_amount_purchased DECIMAL(10,2)
    , OUT count_rewardees INT
)
    READS SQL DATA
    COMMENT 'Provides a customizable report on best customers'
proc: BEGIN

    DECLARE last_month_start DATE;
    DECLARE last_month_end DATE;

    
    IF min_monthly_purchases = 0 THEN
        SELECT 'Minimum monthly purchases parameter must be > 0';
        LEAVE proc;
    END IF;
    IF min_dollar_amount_purchased = 0.00 THEN
        SELECT 'Minimum monthly dollar amount purchased parameter must be > $0.00';
        LEAVE proc;
    END IF;

    
    SET last_month_start = DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH);
    SET last_month_start = STR_TO_DATE(CONCAT(YEAR(last_month_start),'-',MONTH(last_month_start),'-01'),'%Y-%m-%d');
    SET last_month_end = LAST_DAY(last_month_start);

    
    CREATE TEMPORARY TABLE tmpCustomer (customer_id SMALLINT UNSIGNED NOT NULL PRIMARY KEY);

    
    INSERT INTO tmpCustomer (customer_id)
    SELECT p.customer_id
    FROM payment AS p
    WHERE DATE(p.payment_date) BETWEEN last_month_start AND last_month_end
    GROUP BY customer_id
    HAVING SUM(p.amount) > min_dollar_amount_purchased
    AND COUNT(customer_id) > min_monthly_purchases;

    
    SELECT COUNT(*) FROM tmpCustomer INTO count_rewardees;

    
    SELECT c.*
    FROM tmpCustomer AS t
    INNER JOIN customer AS c ON t.customer_id = c.customer_id;

    
    DROP TABLE tmpCustomer;
END//
DELIMITER ;
;
USE `sakila`;
ALTER TABLE `sakila`.`actor` ADD KEY `idx_actor_last_name` (`last_name`);
USE `sakila`;
ALTER TABLE `sakila`.`address` ADD KEY `idx_fk_city_id` (`city_id`);
ALTER TABLE `sakila`.`address` ADD CONSTRAINT `fk_address_city` FOREIGN KEY (`city_id`) REFERENCES `city` (`city_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`city` ADD KEY `idx_fk_country_id` (`country_id`);
ALTER TABLE `sakila`.`city` ADD CONSTRAINT `fk_city_country` FOREIGN KEY (`country_id`) REFERENCES `country` (`country_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`customer` ADD KEY `idx_fk_store_id` (`store_id`);
ALTER TABLE `sakila`.`customer` ADD KEY `idx_fk_address_id` (`address_id`);
ALTER TABLE `sakila`.`customer` ADD KEY `idx_last_name` (`last_name`);
ALTER TABLE `sakila`.`customer` ADD CONSTRAINT `fk_customer_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`customer` ADD CONSTRAINT `fk_customer_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`store_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`film` ADD KEY `idx_title` (`title`);
ALTER TABLE `sakila`.`film` ADD KEY `idx_fk_language_id` (`language_id`);
ALTER TABLE `sakila`.`film` ADD KEY `idx_fk_original_language_id` (`original_language_id`);
ALTER TABLE `sakila`.`film` ADD CONSTRAINT `fk_film_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`film` ADD CONSTRAINT `fk_film_language_original` FOREIGN KEY (`original_language_id`) REFERENCES `language` (`language_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
DELIMITER //
/*!50017 CREATE*/ /*!50003 DEFINER=`root`@`%`*/ /*!50017 TRIGGER `sakila`.`ins_film` AFTER INSERT ON `film` FOR EACH ROW BEGIN
    INSERT INTO film_text (film_id, title, description)
        VALUES (new.film_id, new.title, new.description);
  END */
//
DELIMITER ;
;
DELIMITER //
/*!50017 CREATE*/ /*!50003 DEFINER=`root`@`%`*/ /*!50017 TRIGGER `sakila`.`upd_film` AFTER UPDATE ON `film` FOR EACH ROW BEGIN
    IF (old.title != new.title) OR (old.description != new.description) OR (old.film_id != new.film_id)
    THEN
        UPDATE film_text
            SET title=new.title,
                description=new.description,
                film_id=new.film_id
        WHERE film_id=old.film_id;
    END IF;
  END */
//
DELIMITER ;
;
DELIMITER //
/*!50017 CREATE*/ /*!50003 DEFINER=`root`@`%`*/ /*!50017 TRIGGER `sakila`.`del_film` AFTER DELETE ON `film` FOR EACH ROW BEGIN
    DELETE FROM film_text WHERE film_id = old.film_id;
  END */
//
DELIMITER ;
;
USE `sakila`;
ALTER TABLE `sakila`.`film_actor` ADD KEY `idx_fk_film_id` (`film_id`);
ALTER TABLE `sakila`.`film_actor` ADD CONSTRAINT `fk_film_actor_actor` FOREIGN KEY (`actor_id`) REFERENCES `actor` (`actor_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`film_actor` ADD CONSTRAINT `fk_film_actor_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`film_text` ADD FULLTEXT KEY `idx_title_description` (`title`,`description`);
USE `sakila`;
ALTER TABLE `sakila`.`film_category` ADD KEY `fk_film_category_category` (`category_id`);
ALTER TABLE `sakila`.`film_category` ADD CONSTRAINT `fk_film_category_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`film_category` ADD CONSTRAINT `fk_film_category_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`inventory` ADD KEY `idx_fk_film_id` (`film_id`);
ALTER TABLE `sakila`.`inventory` ADD KEY `idx_store_id_film_id` (`store_id`,`film_id`);
ALTER TABLE `sakila`.`inventory` ADD CONSTRAINT `fk_inventory_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`inventory` ADD CONSTRAINT `fk_inventory_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`store_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`payment` ADD KEY `idx_fk_staff_id` (`staff_id`);
ALTER TABLE `sakila`.`payment` ADD KEY `idx_fk_customer_id` (`customer_id`);
ALTER TABLE `sakila`.`payment` ADD KEY `fk_payment_rental` (`rental_id`);
ALTER TABLE `sakila`.`payment` ADD CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`payment` ADD CONSTRAINT `fk_payment_rental` FOREIGN KEY (`rental_id`) REFERENCES `rental` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `sakila`.`payment` ADD CONSTRAINT `fk_payment_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`rental` ADD UNIQUE KEY `rental_date` (`rental_date`,`inventory_id`,`customer_id`);
ALTER TABLE `sakila`.`rental` ADD KEY `idx_fk_inventory_id` (`inventory_id`);
ALTER TABLE `sakila`.`rental` ADD KEY `idx_fk_customer_id` (`customer_id`);
ALTER TABLE `sakila`.`rental` ADD KEY `idx_fk_staff_id` (`staff_id`);
ALTER TABLE `sakila`.`rental` ADD CONSTRAINT `fk_rental_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`rental` ADD CONSTRAINT `fk_rental_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`rental` ADD CONSTRAINT `fk_rental_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`staff` ADD KEY `idx_fk_store_id` (`store_id`);
ALTER TABLE `sakila`.`staff` ADD KEY `idx_fk_address_id` (`address_id`);
ALTER TABLE `sakila`.`staff` ADD CONSTRAINT `fk_staff_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`staff` ADD CONSTRAINT `fk_staff_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`store_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
USE `sakila`;
ALTER TABLE `sakila`.`store` ADD UNIQUE KEY `idx_unique_manager` (`manager_staff_id`);
ALTER TABLE `sakila`.`store` ADD KEY `idx_fk_address_id` (`address_id`);
ALTER TABLE `sakila`.`store` ADD CONSTRAINT `fk_store_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `sakila`.`store` ADD CONSTRAINT `fk_store_staff` FOREIGN KEY (`manager_staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
DROP VIEW IF EXISTS `sakila`.`actor_info`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`actor_info` AS select `a`.`actor_id` AS `actor_id`,`a`.`first_name` AS `first_name`,`a`.`last_name` AS `last_name`,group_concat(distinct concat(`c`.`name`,': ',(select group_concat(`f`.`title` order by `f`.`title` ASC separator ', ') from ((`sakila`.`film` `f` join `sakila`.`film_category` `fc` on((`f`.`film_id` = `fc`.`film_id`))) join `sakila`.`film_actor` `fa` on((`f`.`film_id` = `fa`.`film_id`))) where ((`fc`.`category_id` = `c`.`category_id`) and (`fa`.`actor_id` = `a`.`actor_id`)))) order by `c`.`name` ASC separator '; ') AS `film_info` from (((`sakila`.`actor` `a` left join `sakila`.`film_actor` `fa` on((`a`.`actor_id` = `fa`.`actor_id`))) left join `sakila`.`film_category` `fc` on((`fa`.`film_id` = `fc`.`film_id`))) left join `sakila`.`category` `c` on((`fc`.`category_id` = `c`.`category_id`))) group by `a`.`actor_id`,`a`.`first_name`,`a`.`last_name`
;
DROP VIEW IF EXISTS `sakila`.`customer_list`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`customer_list` AS select `cu`.`customer_id` AS `ID`,concat(`cu`.`first_name`,_utf8mb4' ',`cu`.`last_name`) AS `name`,`a`.`address` AS `address`,`a`.`postal_code` AS `zip code`,`a`.`phone` AS `phone`,`sakila`.`city`.`city` AS `city`,`sakila`.`country`.`country` AS `country`,if(`cu`.`active`,_utf8mb4'active',_utf8mb4'') AS `notes`,`cu`.`store_id` AS `SID` from (((`sakila`.`customer` `cu` join `sakila`.`address` `a` on((`cu`.`address_id` = `a`.`address_id`))) join `sakila`.`city` on((`a`.`city_id` = `sakila`.`city`.`city_id`))) join `sakila`.`country` on((`sakila`.`city`.`country_id` = `sakila`.`country`.`country_id`)))
;
DROP VIEW IF EXISTS `sakila`.`film_list`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`film_list` AS select `sakila`.`film`.`film_id` AS `FID`,`sakila`.`film`.`title` AS `title`,`sakila`.`film`.`description` AS `description`,`sakila`.`category`.`name` AS `category`,`sakila`.`film`.`rental_rate` AS `price`,`sakila`.`film`.`length` AS `length`,group_concat(concat(`sakila`.`actor`.`first_name`,_utf8mb4' ',`sakila`.`actor`.`last_name`) separator ', ') AS `actors` from ((((`sakila`.`film` left join `sakila`.`film_category` on((`sakila`.`film_category`.`film_id` = `sakila`.`film`.`film_id`))) left join `sakila`.`category` on((`sakila`.`category`.`category_id` = `sakila`.`film_category`.`category_id`))) left join `sakila`.`film_actor` on((`sakila`.`film`.`film_id` = `sakila`.`film_actor`.`film_id`))) left join `sakila`.`actor` on((`sakila`.`film_actor`.`actor_id` = `sakila`.`actor`.`actor_id`))) group by `sakila`.`film`.`film_id`,`sakila`.`category`.`name`
;
DROP VIEW IF EXISTS `sakila`.`nicer_but_slower_film_list`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`nicer_but_slower_film_list` AS select `sakila`.`film`.`film_id` AS `FID`,`sakila`.`film`.`title` AS `title`,`sakila`.`film`.`description` AS `description`,`sakila`.`category`.`name` AS `category`,`sakila`.`film`.`rental_rate` AS `price`,`sakila`.`film`.`length` AS `length`,group_concat(concat(concat(upper(substr(`sakila`.`actor`.`first_name`,1,1)),lower(substr(`sakila`.`actor`.`first_name`,2,length(`sakila`.`actor`.`first_name`))),_utf8mb4' ',concat(upper(substr(`sakila`.`actor`.`last_name`,1,1)),lower(substr(`sakila`.`actor`.`last_name`,2,length(`sakila`.`actor`.`last_name`)))))) separator ', ') AS `actors` from ((((`sakila`.`film` left join `sakila`.`film_category` on((`sakila`.`film_category`.`film_id` = `sakila`.`film`.`film_id`))) left join `sakila`.`category` on((`sakila`.`category`.`category_id` = `sakila`.`film_category`.`category_id`))) left join `sakila`.`film_actor` on((`sakila`.`film`.`film_id` = `sakila`.`film_actor`.`film_id`))) left join `sakila`.`actor` on((`sakila`.`film_actor`.`actor_id` = `sakila`.`actor`.`actor_id`))) group by `sakila`.`film`.`film_id`,`sakila`.`category`.`name`
;
DROP VIEW IF EXISTS `sakila`.`sales_by_film_category`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`sales_by_film_category` AS select `c`.`name` AS `category`,sum(`p`.`amount`) AS `total_sales` from (((((`sakila`.`payment` `p` join `sakila`.`rental` `r` on((`p`.`rental_id` = `r`.`rental_id`))) join `sakila`.`inventory` `i` on((`r`.`inventory_id` = `i`.`inventory_id`))) join `sakila`.`film` `f` on((`i`.`film_id` = `f`.`film_id`))) join `sakila`.`film_category` `fc` on((`f`.`film_id` = `fc`.`film_id`))) join `sakila`.`category` `c` on((`fc`.`category_id` = `c`.`category_id`))) group by `c`.`name` order by `total_sales` desc
;
DROP VIEW IF EXISTS `sakila`.`sales_by_store`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`sales_by_store` AS select concat(`c`.`city`,_utf8mb4',',`cy`.`country`) AS `store`,concat(`m`.`first_name`,_utf8mb4' ',`m`.`last_name`) AS `manager`,sum(`p`.`amount`) AS `total_sales` from (((((((`sakila`.`payment` `p` join `sakila`.`rental` `r` on((`p`.`rental_id` = `r`.`rental_id`))) join `sakila`.`inventory` `i` on((`r`.`inventory_id` = `i`.`inventory_id`))) join `sakila`.`store` `s` on((`i`.`store_id` = `s`.`store_id`))) join `sakila`.`address` `a` on((`s`.`address_id` = `a`.`address_id`))) join `sakila`.`city` `c` on((`a`.`city_id` = `c`.`city_id`))) join `sakila`.`country` `cy` on((`c`.`country_id` = `cy`.`country_id`))) join `sakila`.`staff` `m` on((`s`.`manager_staff_id` = `m`.`staff_id`))) group by `s`.`store_id` order by `cy`.`country`,`c`.`city`
;
DROP VIEW IF EXISTS `sakila`.`staff_list`;
CREATE ALGORITHM=UNDEFINED VIEW `sakila`.`staff_list` AS select `s`.`staff_id` AS `ID`,concat(`s`.`first_name`,_utf8mb4' ',`s`.`last_name`) AS `name`,`a`.`address` AS `address`,`a`.`postal_code` AS `zip code`,`a`.`phone` AS `phone`,`sakila`.`city`.`city` AS `city`,`sakila`.`country`.`country` AS `country`,`s`.`store_id` AS `SID` from (((`sakila`.`staff` `s` join `sakila`.`address` `a` on((`s`.`address_id` = `a`.`address_id`))) join `sakila`.`city` on((`a`.`city_id` = `sakila`.`city`.`city_id`))) join `sakila`.`country` on((`sakila`.`city`.`country_id` = `sakila`.`country`.`country_id`)))
;
SET TIME_ZONE=@OLD_TIME_ZONE;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET SQL_MODE=@OLD_SQL_MODE;
